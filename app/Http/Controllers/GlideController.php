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
     * @param  string  $path
     * @return mixed
     */
    public function __invoke(Request $request, string $path)
    {
        // Decode path if needed
        $path = urldecode($path);

        // Find media by path or UUID
        $media = Media::where('path', $path)
            ->orWhere('uuid', $path)
            ->first();

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

        // Create temporary cache directory if it doesn't exist
        $cachePath = storage_path('app/glide-cache');
        if (! is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        // For remote disks (like R2), download to temp first
        $tempSourcePath = null;
        $sourcePath = $media->path;

        if ($media->disk !== 'local' && $media->disk !== 'public') {
            // Download file to temp directory for processing
            $tempSourcePath = storage_path('app/temp-glide/'.basename($media->path));
            $tempDir = dirname($tempSourcePath);
            
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Download from remote disk
            $fileContents = $disk->get($media->path);
            file_put_contents($tempSourcePath, $fileContents);
            $sourcePath = basename($media->path);
        }

        // Create Glide server
        $serverConfig = [
            'response' => new LaravelResponseFactory($request),
            'cache' => Storage::disk('local')->getDriver(),
            'cache_path_prefix' => 'glide-cache',
            'driver' => config('laravel-glide.driver', 'gd'),
        ];

        // Set source based on disk type
        if ($tempSourcePath) {
            // Use temp directory as source
            $serverConfig['source'] = dirname($tempSourcePath);
        } else {
            // Use disk driver directly
            $serverConfig['source'] = $disk->getDriver();
        }

        $server = ServerFactory::create($serverConfig);

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
        $response = $server->getImageResponse($sourcePath, $params);

        // Clean up temp file after response is sent
        if ($tempSourcePath && file_exists($tempSourcePath)) {
            register_shutdown_function(function () use ($tempSourcePath) {
                @unlink($tempSourcePath);
            });
        }

        return $response;
    }
}

