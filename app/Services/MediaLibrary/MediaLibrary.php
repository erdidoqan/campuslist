<?php

namespace App\Services\MediaLibrary;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaLibrary
{
    public function __construct(
        protected string $defaultDisk = 'r2'
    ) {
    }

    public function storeUploadedFile(UploadedFile $file, ?string $directory = null, array $meta = [], ?string $disk = null): Media
    {
        $disk = $disk ?: $this->defaultDisk;

        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension();
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();
        $size = $file->getSize();

        $hashName = $this->generateHashName($extension);
        $normalizedDirectory = $this->normalizeDirectory($directory);
        $path = $this->buildPath($normalizedDirectory, $hashName);

        $this->ensureDiskExists($disk);

        Storage::disk($disk)->putFileAs(
            $normalizedDirectory ?: '/',
            $file,
            $hashName,
            [
                'visibility' => 'public',
            ]
        );

        return $this->createMediaRecord([
            'disk' => $disk,
            'directory' => $normalizedDirectory,
            'filename' => pathinfo($originalName, PATHINFO_FILENAME),
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size' => $size,
            'original_name' => $originalName,
            'hash_name' => $hashName,
            'path' => $path,
            'url' => $this->resolveUrl($disk, $path),
            'meta' => $meta,
        ]);
    }

    public function storeFromContents(string $fileName, string $contents, ?string $mimeType = null, ?string $directory = null, array $meta = [], ?string $disk = null): Media
    {
        $disk = $disk ?: $this->defaultDisk;

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $hashName = $this->generateHashName($extension);
        $normalizedDirectory = $this->normalizeDirectory($directory);
        $path = $this->buildPath($normalizedDirectory, $hashName);

        $this->ensureDiskExists($disk);

        Storage::disk($disk)->put(
            $path,
            $contents,
            [
                'visibility' => 'public',
            ]
        );

        return $this->createMediaRecord([
            'disk' => $disk,
            'directory' => $normalizedDirectory,
            'filename' => pathinfo($fileName, PATHINFO_FILENAME),
            'extension' => $extension ?: null,
            'mime_type' => $mimeType,
            'size' => strlen($contents),
            'original_name' => $fileName,
            'hash_name' => $hashName,
            'path' => $path,
            'url' => $this->resolveUrl($disk, $path),
            'meta' => $meta,
        ]);
    }

    public function storeFromUrl(string $url, ?string $fileName = null, ?string $directory = null, array $meta = [], ?string $disk = null): Media
    {
        $disk = $disk ?: $this->defaultDisk;

        $response = Http::retry(2, 500)
            ->timeout(15)
            ->withHeaders([
                'User-Agent' => 'CampusListBot/1.0',
            ])
            ->get($url);

        if ($response->failed()) {
            throw new RuntimeException(sprintf('Uzak dosya indirilemedi: %s', $url));
        }

        $mimeType = $response->header('Content-Type');
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path ?? '', PATHINFO_EXTENSION);

        if (! $extension && $mimeType) {
            $extension = $this->extensionFromMime($mimeType);
        }

        $fileName = $fileName ?: ($path ? basename($path) : Str::uuid()->toString());

        $effectiveFileName = $fileName;

        if ($extension && ! Str::endsWith(Str::lower($effectiveFileName), '.'.Str::lower($extension))) {
            $effectiveFileName .= '.'.$extension;
        }

        return $this->storeFromContents(
            $effectiveFileName,
            $response->body(),
            $mimeType,
            $directory,
            array_merge($meta, ['source_url' => $url]),
            $disk
        );
    }

    protected function generateHashName(?string $extension = null): string
    {
        $hash = Str::uuid()->toString().'-'.Str::random(16);

        if ($extension) {
            return $hash.'.'.ltrim($extension, '.');
        }

        return $hash;
    }

    protected function normalizeDirectory(?string $directory): ?string
    {
        if ($directory === null || $directory === '') {
            return null;
        }

        return trim(preg_replace('#/+#', '/', $directory), '/');
    }

    protected function buildPath(?string $directory, string $hashName): string
    {
        return $directory ? $directory.'/'.$hashName : $hashName;
    }

    protected function createMediaRecord(array $attributes): Media
    {
        return Media::create($attributes);
    }

    protected function resolveUrl(string $disk, string $path): ?string
    {
        try {
            return Storage::disk($disk)->url($path);
        } catch (RuntimeException) {
            $base = rtrim(config('filesystems.disks.'.$disk.'.url'), '/');

            return $base ? $base.'/'.ltrim($path, '/') : null;
        }
    }

    protected function ensureDiskExists(string $disk): void
    {
        if (! array_key_exists($disk, config('filesystems.disks'))) {
            throw new RuntimeException(sprintf('"%s" disk is not configured.', $disk));
        }
    }

    protected function extensionFromMime(string $mimeType): ?string
    {
        $parts = explode('/', $mimeType);

        return isset($parts[1]) ? strtolower($parts[1]) : null;
    }
}

