<?php

namespace App\Services\Glide;

use League\Flysystem\FilesystemOperator;
use League\Glide\Responses\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaravelResponseFactory implements ResponseFactoryInterface
{
    /**
     * Create response.
     *
     * @param  FilesystemOperator  $cache
     * @param  string  $path
     * @return StreamedResponse
     */
    public function create(FilesystemOperator $cache, $path): StreamedResponse
    {
        $stream = $cache->readStream($path);
        $mimeType = $cache->mimeType($path);

        return new StreamedResponse(
            function () use ($stream) {
                if (is_resource($stream)) {
                    fpassthru($stream);
                    fclose($stream);
                }
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Length' => $cache->fileSize($path),
                'Cache-Control' => 'public, max-age=31536000',
            ]
        );
    }
}

