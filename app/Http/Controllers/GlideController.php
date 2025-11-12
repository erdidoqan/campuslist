<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\Glide\LaravelResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Glide\ServerFactory;

class GlideController extends Controller
{
    /**
     * Generate optimized image using Glide
     *
     * @param  Request  $request
     * @param  string  $hashName
     * @return mixed
     */
    public function __invoke(Request $request, string $hashName)
    {
        // Find media by hash_name
        $media = Media::where('hash_name', $hashName)->first();

        if (! $media) {
            abort(404, 'Medya bulunamadı.');
        }

        // Only process images
        if (! str_starts_with($media->mime_type ?? '', 'image/')) {
            abort(400, 'Bu dosya tipi desteklenmiyor.');
        }

        // Check if file exists on disk
        $sourceDisk = Storage::disk($media->disk);
        if (! $sourceDisk->exists($media->path)) {
            abort(404, 'Dosya R2\'de bulunamadı.');
        }

        // Use public disk for Glide source and cache
        $publicDisk = Storage::disk('public');
        $tempPath = 'glide-source/'.$media->hash_name;

        // Copy file from R2 to local public disk
        try {
            $fileContents = $sourceDisk->get($media->path);
            $publicDisk->put($tempPath, $fileContents);
        } catch (\Exception $e) {
            Log::error('Glide: R2\'den dosya kopyalanamadı', [
                'path' => $media->path,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Dosya işlenemedi.');
        }

        // Create Glide server
        $server = ServerFactory::create([
            'response' => new LaravelResponseFactory(),
            'source' => $publicDisk->getDriver(),
            'cache' => $publicDisk->getDriver(),
            'cache_path_prefix' => 'glide-cache',
            'driver' => config('laravel-glide.driver', 'gd'),
        ]);

        // Get Glide parameters
        $params = $request->only(['w', 'h', 'fit', 'q', 'fm', 'filt', 'blur', 'pixel', 'dpr']);

        // Set defaults
        $params['fit'] = $params['fit'] ?? 'contain';
        $params['q'] = $params['q'] ?? 90;

        // Return optimized image
        try {
            $response = $server->getImageResponse($tempPath, $params);
        } catch (\Exception $e) {
            Log::error('Glide: Görsel işlenemedi', [
                'temp_path' => $tempPath,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            
            // Clean up
            if ($publicDisk->exists($tempPath)) {
                $publicDisk->delete($tempPath);
            }
            
            abort(500, 'Görsel işlenemedi.');
        }

        // Clean up temp file after response
        register_shutdown_function(function () use ($publicDisk, $tempPath) {
            try {
                if ($publicDisk->exists($tempPath)) {
                    $publicDisk->delete($tempPath);
                }
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        });

        return $response;
    }
}

