<?php

namespace App\Services\GooglePlaces;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesClient
{
    private const BASE_URL = 'https://places.googleapis.com/v1';
    private const SEARCH_FIELD_MASK = 'places.displayName,places.formattedAddress,places.id,places.rating,places.types';
    private const DETAILS_FIELD_MASK = '*';
    private const PHOTO_FIELD_MASK = 'photoUri';

    private ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.google_places.key');
    }

    public function fetchPlace(string $query): ?array
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        if (! $this->apiKey) {
            Log::warning('Google Places API anahtarı tanımlı değil.');

            return null;
        }

        $placeId = $this->findPlaceId($query);

        if (! $placeId) {
            return null;
        }

        $details = $this->getPlaceDetails($placeId);

        if (! $details) {
            return null;
        }

        return $this->transformPlaceDetails($details);
    }

    protected function findPlaceId(string $query): ?string
    {
        $response = $this->searchRequest()->post(self::BASE_URL.'/places:searchText', [
            'textQuery' => $query,
            'maxResultCount' => 5,
        ]);

        if ($response->failed()) {
            Log::warning('Google Places search isteği başarısız oldu.', [
                'query' => $query,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return Arr::get($response->json(), 'places.0.id');
    }

    protected function getPlaceDetails(string $placeId): ?array
    {
        $response = $this->detailsRequest()->get(self::BASE_URL.'/places/'.$placeId);

        if ($response->failed()) {
            Log::warning('Google Places detay isteği başarısız oldu.', [
                'place_id' => $placeId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    protected function transformPlaceDetails(array $details): array
    {
        $title = Arr::get($details, 'displayName.text');

        $coordinates = [
            'latitude' => Arr::get($details, 'location.latitude'),
            'longitude' => Arr::get($details, 'location.longitude'),
        ];

        if ($coordinates['latitude'] === null || $coordinates['longitude'] === null) {
            $coordinates = null;
        }

        $types = Arr::get($details, 'types', []);

        $postalAddress = Arr::get($details, 'postalAddress', []);

        return [
            'id' => Arr::get($details, 'id'),
            'title' => $title,
            'place_title' => $title,
            'location' => Arr::get($details, 'shortFormattedAddress') ?? Arr::get($details, 'formattedAddress'),
            'address' => Arr::get($details, 'formattedAddress'),
            'website' => Arr::get($details, 'websiteUri'),
            'phone' => Arr::get($details, 'internationalPhoneNumber'),
            'google_maps_uri' => Arr::get($details, 'googleMapsUri'),
            'gps_coordinates' => $coordinates,
            'open_state' => Arr::get($details, 'businessStatus'),
            'hours' => Arr::get($details, 'regularOpeningHours.weekdayDescriptions'),
            'types' => $types,
            'type' => Arr::first($types),
            'rating' => Arr::get($details, 'rating'),
            'user_rating_count' => Arr::get($details, 'userRatingCount'),
            'photos' => Arr::get($details, 'photos', []),
            'region_code' => Arr::get($postalAddress, 'regionCode'),
            'administrative_area' => Arr::get($postalAddress, 'administrativeArea'),
            'locality' => Arr::get($postalAddress, 'locality'),
            'raw_details' => $details,
        ];
    }

    public function resolvePhotoUri(string $photoName, array $params = []): ?string
    {
        $photoName = ltrim($photoName, '/');

        $defaultParams = [
            'maxWidthPx' => 1600,
            'skipHttpRedirect' => true,
        ];

        $response = $this->photoRequest()->get(
            self::BASE_URL.'/'.$photoName.'/media',
            array_merge($defaultParams, $params)
        );

        if ($response->failed()) {
            Log::warning('Google Places fotoğraf isteği başarısız oldu.', [
                'photo_name' => $photoName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return Arr::get($response->json(), 'photoUri');
    }

    protected function searchRequest(): PendingRequest
    {
        return $this->baseRequest()
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-FieldMask' => self::SEARCH_FIELD_MASK,
            ]);
    }

    protected function detailsRequest(): PendingRequest
    {
        return $this->baseRequest()
            ->withHeaders([
                'X-Goog-FieldMask' => self::DETAILS_FIELD_MASK,
            ]);
    }

    protected function photoRequest(): PendingRequest
    {
        return $this->baseRequest()
            ->withHeaders([
                'X-Goog-FieldMask' => self::PHOTO_FIELD_MASK,
            ]);
    }

    protected function baseRequest(): PendingRequest
    {
        if (! $this->apiKey) {
            Log::warning('Google Places API anahtarı tanımlı değil.');
        }

        return Http::retry(2, 500)
            ->timeout(10)
            ->withHeaders([
                'X-Goog-Api-Key' => $this->apiKey ?? '',
            ]);
    }
}

