<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use League\Glide\Responses\LaravelResponseFactory;
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
        // Find media by hash_name (filename with extension)
        // hash_name is unique and matches the CDN filename (e.g., "69fb0f11-a4f2-4064-bcdf-d85d92be4764-7ZTulWaBfAOnVk0g.jpeg")
        $media = Media::where('hash_name', $hashName)->first();

        if (! $media) {
            abort(404, 'Medya bulunamadı.');
        }

        // Check if file exists on disk
        $disk = Storage::disk($media->disk);
        if (! $disk->exists($media->path)) {
            abort(404, 'Dosya bulunamadı.');
        }

        // Only process images
        if (! str_starts_with($media->mime_type ?? '', 'image/')) {
            abort(400, 'Bu dosya tipi desteklenmiyor.');
        }

        // For remote disks (like R2), we need to use a local source
        // Glide works with Flysystem adapters, so we'll use local disk for both source and cache
        $localDisk = Storage::disk('local');
        $tempSourcePath = 'glide-temp/'.$media->hash_name;
        $sourcePath = $media->path;

        // If file is on remote disk, copy to local temp first
        if ($media->disk !== 'local' && $media->disk !== 'public') {
            try {
                // Download from remote disk and store in local temp
                $fileContents = $disk->get($media->path);
                $localDisk->put($tempSourcePath, $fileContents);
                $sourcePath = $tempSourcePath;
            } catch (\Exception $e) {
                \Log::error('Glide: Dosya okunamadı', [
                    'path' => $media->path,
                    'disk' => $media->disk,
                    'error' => $e->getMessage(),
                ]);
                abort(404, 'Dosya okunamadı.');
            }
        } else {
            // For local disks, use the file directly
            $sourcePath = $media->path;
        }

        // Create Glide server with Flysystem adapters
        // According to Glide docs: source and cache should be Flysystem adapters
        $server = ServerFactory::create([
            'response' => new LaravelResponseFactory($request),
            'source' => $localDisk->getDriver(),
            'cache' => $localDisk->getDriver(),
            'cache_path_prefix' => 'glide-cache',
            'driver' => config('laravel-glide.driver', 'gd'),
        ]);

        // Get Glide parameters from request
        $params = $request->only(['w', 'h', 'fit', 'q', 'fm', 'filt', 'blur', 'pixel', 'dpr']);

        // Set defaults
        if (! isset($params['fit'])) {
            $params['fit'] = 'contain';
        }

        if (! isset($params['q'])) {
            $params['q'] = 90;
        }

        // Return optimized image
        try {
            $response = $server->getImageResponse($sourcePath, $params);
        } catch (\Exception $e) {
            \Log::error('Glide: Görsel işlenemedi', [
                'path' => $sourcePath,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            
            // Clean up temp file if it was created
            if ($media->disk !== 'local' && $media->disk !== 'public' && $localDisk->exists($tempSourcePath)) {
                $localDisk->delete($tempSourcePath);
            }
            
            abort(500, 'Görsel işlenemedi: '.$e->getMessage());
        }

        // Clean up temp file after response is sent (for remote disks)
        if ($media->disk !== 'local' && $media->disk !== 'public' && $localDisk->exists($tempSourcePath)) {
            register_shutdown_function(function () use ($localDisk, $tempSourcePath) {
                try {
                    $localDisk->delete($tempSourcePath);
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            });
        }

        return $response;
    }
}

