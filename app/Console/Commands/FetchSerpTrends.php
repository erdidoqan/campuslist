<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\University;
use App\Services\GooglePlaces\GooglePlacesClient;
use App\Services\MediaLibrary\MediaLibrary;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FetchSerpTrends extends Command
{
    protected GooglePlacesClient $placesClient;
    protected MediaLibrary $mediaLibrary;

    /**
     * @var array<string, array<mixed>>
     */
    protected array $placeCache = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serpapi:fetch-trends 
                            {--query=university : Google Trends ana anahtar kelimesi} 
                            {--geo=US : Google Trends coğrafi kodu} 
                            {--category=74 : Google Trends kategori kodu} 
                            {--date=now 1-H : Veri aralığı} 
                            {--tz=420 : Saat dilimi offset}
                            {--ll= : Google Maps araması için merkez koordinat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SerpApi Google Trends yükselen sorgularını Google Places detaylarıyla birlikte kaydeder';

    public function __construct(GooglePlacesClient $placesClient, MediaLibrary $mediaLibrary)
    {
        parent::__construct();

        $this->placesClient = $placesClient;
        $this->mediaLibrary = $mediaLibrary;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apiKey = config('services.serpapi.key', env('SERPAPI_KEY'));

        if (empty($apiKey)) {
            $this->error('SerpApi anahtarı bulunamadı. Lütfen .env içinde SERPAPI_KEY değerini tanımlayın.');

            return self::FAILURE;
        }

        $placesKey = config('services.google_places.key');

        if (empty($placesKey)) {
            $this->error('Google Places API anahtarı bulunamadı. Lütfen .env dosyasında X_Goog_Api_Key değerini tanımlayın.');

            return self::FAILURE;
        }

        $query = $this->option('query');
        $geo = $this->option('geo');
        $category = $this->option('category');
        $date = $this->option('date');
        $tz = $this->option('tz');

        $this->primePlaceCache();

        $trendsResponse = Http::retry(3, 500)
            ->timeout(100)
            ->get('https://serpapi.com/search', [
                'api_key' => $apiKey,
                'engine' => 'google_trends',
                'q' => $query,
                'include_low_search_volume' => 'true',
                'tz' => $tz,
                'hl' => 'en',
                'geo' => $geo,
                'cat' => $category,
                'date' => $date,
                'no_cache' => 'true',
                'data_type' => 'RELATED_QUERIES',
            ]);

        if ($trendsResponse->failed()) {
            $this->error('Google Trends verisi alınamadı: '.$trendsResponse->body());

            return self::FAILURE;
        }

        $risingQueries = Arr::get($trendsResponse->json(), 'related_queries.rising', []);

        if (empty($risingQueries)) {
            $this->warn('Yükselen sorgu bulunamadı.');

            return self::SUCCESS;
        }

        $this->info(sprintf('%d adet yükselen sorgu bulundu.', count($risingQueries)));

        foreach ($risingQueries as $index => $item) {
            $trendQuery = Arr::get($item, 'query');

            if (empty($trendQuery)) {
                $this->warn('Sorgu değeri boş olduğu için atlandı.');
                continue;
            }

            $this->line(sprintf('[%d/%d] "%s" sorgusu işleniyor...', $index + 1, count($risingQueries), $trendQuery));

            $normalizedKey = $this->normalizeInstitutionKey($trendQuery);

            if ($matchedKeyword = $this->matchSkipKeyword($trendQuery)) {
                University::query()
                    ->where('query', $trendQuery)
                    ->delete();

                $this->warn(sprintf('"%s" sorgusu "%s" ifadesini içeriyor. Google Places isteği atlanıyor ve kayıt temizlendi.', $trendQuery, $matchedKeyword));

                continue;
            }

            $place = $this->resolvePlaceFromCache($normalizedKey, $trendQuery);

            if (! $place) {
                $place = $this->fetchPlaceFromGoogle($trendQuery);

                if ($place) {
                    $this->cachePlace($normalizedKey, $place);
                } else {
                    $this->warn(sprintf('"%s" için Google Places sonucu bulunamadı.', $trendQuery));
                }
            }

            $attributes = array_merge(['query' => $trendQuery], $this->buildPlaceAttributes($place));

            $existing = $this->findExistingRecord($trendQuery, $place);

            $attributes['slug'] = $this->generateSlug($attributes, $trendQuery, $existing);
            $attributes['meta_description'] = $this->resolveMetaDescription($attributes, $trendQuery, $existing);

            if ($existing) {
                $existing->fill($attributes);
                $existing->query = $trendQuery;
                $existing->save();

                $record = $existing;
            } else {
                if (empty($attributes['name'])) {
                    $this->warn(sprintf('"%s" için geçerli bir kurum adı bulunamadı, kayıt oluşturulmadı.', $trendQuery));

                    continue;
                }

                $record = University::create($attributes);
            }

            if ($place && isset($record)) {
                $this->storePlacePhotos($place, $record);
            }
        }

        $this->info('Tüm sorgular işlendi.');

        return self::SUCCESS;
    }

    protected function generateSlug(array $attributes, string $trendQuery, ?University $existing = null): string
    {
        if ($existing && ! empty($existing->slug)) {
            return $existing->slug;
        }

        $source = Arr::get($attributes, 'name')
            ?? Arr::get($attributes, 'place_title')
            ?? $trendQuery;

        $slug = Str::slug((string) $source);

        if ($slug === '') {
            $slug = 'universite-'.Str::lower(Str::random(6));
        }

        return $this->ensureUniqueSlug($slug, $existing?->id);
    }

    protected function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $baseSlug = $slug;
        $suffix = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $suffix++;
            $slug = $baseSlug.'-'.$suffix;
        }

        return $slug;
    }

    protected function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return University::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    protected function resolveMetaDescription(array $attributes, string $trendQuery, ?University $existing = null): ?string
    {
        if ($existing && ! empty($existing->meta_description)) {
            return $existing->meta_description;
        }

        $name = Arr::get($attributes, 'name')
            ?? Arr::get($attributes, 'place_title')
            ?? $trendQuery;

        if (! $name) {
            return null;
        }

        $templates = Lang::get('university.meta_descriptions');

        if (is_string($templates)) {
            $templates = [$templates];
        }

        if (! is_array($templates) || empty($templates)) {
            $templates = [
                'Find everything you need to know about :name, including tuition & financial aid, student life, application info, academics & more.',
            ];
        }

        $template = Arr::random($templates);

        $replacements = [
            'name' => $name,
            'type' => $this->resolveType($attributes, $existing),
        ];

        $description = __($template, $replacements);

        return Str::limit(trim($description), 300, '');
    }

    protected function resolveType(array $attributes, ?University $existing = null): string
    {
        $typeCandidates = [
            Arr::get($attributes, 'type'),
            $existing?->type,
        ];

        $placeRaw = Arr::get($attributes, 'place_raw');

        if (is_array($placeRaw)) {
            $typeCandidates[] = $this->extractPrimaryType($placeRaw);
        }

        if ($existing && is_array($existing->place_raw)) {
            $typeCandidates[] = $this->extractPrimaryType($existing->place_raw);
        }

        foreach ($typeCandidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return Str::lower($candidate);
            }
        }

        return 'university';
    }

    /**
     * @param  array<string, mixed>|null  $place
     * @return array<string, float>|null
     */
    protected function extractCoordinates(?array $place): ?array
    {
        if (empty($place)) {
            return null;
        }

        $coordinates = Arr::get($place, 'gps_coordinates');

        if (! is_array($coordinates)) {
            return null;
        }

        $latitude = Arr::get($coordinates, 'latitude', Arr::get($coordinates, 'lat'));
        $longitude = Arr::get($coordinates, 'longitude', Arr::get($coordinates, 'lng'));

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return [
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        ];
    }

    protected function matchSkipKeyword(string $query): ?string
    {
        $lowerQuery = Str::lower($query);
        $skipKeywords = Config::get('serpapi.skip_keywords', []);
    

        foreach ($skipKeywords as $keyword) {
            if (Str::contains($lowerQuery, $keyword)) {
                return $keyword;
            }
        }

        return null;
    }

    protected function normalizeInstitutionKey(string $query): string
    {
        $value = Str::of($query)->lower();
        $trimPhrases = Config::get('serpapi.trim_phrases', []);

        foreach ($trimPhrases as $phrase) {
            $value = $value->replace($phrase, '');
        }

        $value = $value
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish();

        return $value->toString();
    }

    protected function buildPlaceAttributes(?array $place): array
    {
        if (empty($place)) {
            return [];
        }

        $name = Arr::get($place, 'title');

        $location = Arr::get($place, 'location') ?? Arr::get($place, 'address');

        if (is_array($location)) {
            $location = implode(', ', array_filter(array_map('strval', $location)));
        }

        $type = $this->extractPrimaryType($place);

        return [
            'name' => is_string($name) && $name !== '' ? $name : null,
            'place_title' => Arr::get($place, 'title'),
            'location' => $location ?: null,
            'type' => $type,
            'google_maps_uri' => Arr::get($place, 'google_maps_uri'),
            'gps_coordinates' => $this->extractCoordinates($place),
            'address' => Arr::get($place, 'address'),
            'website' => Arr::get($place, 'website'),
            'phone' => Arr::get($place, 'phone'),
            'open_state' => Arr::get($place, 'open_state'),
            'hours' => Arr::get($place, 'hours'),
            'region_code' => Arr::get($place, 'region_code'),
            'administrative_area' => Arr::get($place, 'administrative_area'),
            'locality' => Arr::get($place, 'locality'),
            'place_raw' => $place,
        ];
    }

    protected function storePlacePhotos(array $place, University $record): void
    {
        $photos = Arr::get($place, 'photos', []);

        if (! is_array($photos) || empty($photos)) {
            return;
        }

        // Her üniversite için maksimum 5 fotoğraf al
        $photos = array_slice($photos, 0, 5);

        $slug = $record->slug
            ?? Str::slug($record->name ?? 'universite-'.$record->id);

        if (! is_string($slug) || $slug === '') {
            $slug = 'universite-'.$record->id;
        }

        $directory = 'universities/'.$slug.'/photos';
        $baseName = Str::slug($record->slug ?? $record->name ?? 'university-photo');

        foreach ($photos as $photo) {
            $photoName = Arr::get($photo, 'name');

            if (! $photoName) {
                continue;
            }

            if ($this->googlePhotoAlreadyStored($photoName, $record->id)) {
                continue;
            }

            $photoUri = $this->placesClient->resolvePhotoUri($photoName);

            if (! $photoUri) {
                continue;
            }

            try {
                $meta = [
                    'google_photo_name' => $photoName,
                    'university_id' => $record->id,
                    'place_id' => Arr::get($place, 'id'),
                    'width_px' => Arr::get($photo, 'widthPx'),
                    'height_px' => Arr::get($photo, 'heightPx'),
                    'attributions' => Arr::get($photo, 'authorAttributions'),
                    'slug' => $slug,
                ];

                $this->mediaLibrary->storeFromUrl(
                    $photoUri,
                    $baseName.'-'.Str::random(6),
                    $directory,
                    $meta
                );
            } catch (Throwable $exception) {
                Log::warning('Google Places fotoğrafı kaydedilemedi.', [
                    'photo_name' => $photoName,
                    'university_id' => $record->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    protected function googlePhotoAlreadyStored(string $photoName, int $universityId): bool
    {
        return Media::query()
            ->where('meta->google_photo_name', $photoName)
            ->where('meta->university_id', $universityId)
            ->exists();
    }

    protected function fetchPlaceFromGoogle(string $query): ?array
    {
        try {
            return $this->placesClient->fetchPlace($query);
        } catch (Throwable $exception) {
            Log::warning('Google Places isteği sırasında hata oluştu.', [
                'query' => $query,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function extractPrimaryType(array $place): ?string
    {
        $type = Arr::get($place, 'type');

        if (is_string($type) && $type !== '') {
            return Str::lower($type);
        }

        if (is_array($type) && ! empty($type)) {
            $type = $type[0];

            if (is_string($type) && $type !== '') {
                return Str::lower($type);
            }
        }

        $types = Arr::get($place, 'types');

        if (is_array($types) && ! empty($types)) {
            foreach ($types as $candidate) {
                if (is_string($candidate) && $candidate !== '') {
                    return Str::lower($candidate);
                }
            }
        }

        return null;
    }

    protected function primePlaceCache(): void
    {
        $cache = [];

        University::query()
            ->whereNotNull('place_raw')
            ->get(['query', 'place_title', 'place_raw'])
            ->each(function (University $record) use (&$cache) {
                $placeRaw = $record->place_raw;

                $keys = array_filter([
                    $this->normalizeInstitutionKey($record->query),
                    $this->normalizeInstitutionKey(Arr::get($placeRaw, 'title', '')),
                    $this->normalizeInstitutionKey((string) $record->place_title),
                ]);

                foreach (array_unique($keys) as $key) {
                    if ($key !== '') {
                        $cache[$key] = $placeRaw;
                    }
                }
            });

        $this->placeCache = $cache;
    }

    protected function resolvePlaceFromCache(string $normalizedKey, string $query): ?array
    {
        if (isset($this->placeCache[$normalizedKey])) {
            $this->line(sprintf('"%s" için önbellekteki yer verisi kullanıldı.', $query));

            return $this->placeCache[$normalizedKey];
        }

        $existingRecord = University::query()
            ->where('query', $query)
            ->whereNotNull('place_raw')
            ->first();

        if ($existingRecord) {
            $place = $existingRecord->place_raw;
            if (is_array($place)) {
                $this->cachePlace($normalizedKey, $place);
                $this->line(sprintf('"%s" için mevcut kayıt verisi yeniden kullanıldı.', $query));
            }

            return $place;
        }

        return null;
    }

    protected function cachePlace(string $normalizedKey, array $place): void
    {
        $this->placeCache[$normalizedKey] = $place;
    }

    protected function findExistingRecord(string $trendQuery, ?array $place): ?University
    {
        $existing = University::query()
            ->where('query', $trendQuery)
            ->first();

        if ($existing) {
            return $existing;
        }

        $placeTitle = Arr::get($place, 'title');

        if ($placeTitle) {
            return University::query()
                ->where('place_title', $placeTitle)
                ->orWhere('name', $placeTitle)
                ->first();
        }

        return null;
    }
}
