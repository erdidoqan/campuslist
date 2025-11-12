<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAIClient
{
    private const BASE_URL = 'https://api.openai.com/v1';
    private const PROMPT_ID = 'pmpt_690be6b69b5c8190af5809d395af13cf006fbeb382118543';
    private const PROMPT_VERSION = '8';

    private ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.openai.key');
    }

    /**
     * Fetch university data from OpenAI using web search
     *
     * @param  string  $universityName
     * @return array<string, mixed>|null
     */
    public function fetchUniversityData(string $universityName): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('OpenAI API anahtarı tanımlı değil.');

            return null;
        }

        try {
            $response = Http::retry(2, 1000)
                ->timeout(120)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$this->apiKey,
                ])
                ->post(self::BASE_URL.'/responses', [
                    'prompt' => [
                        'id' => self::PROMPT_ID,
                        'version' => self::PROMPT_VERSION,
                        'variables' => [
                            'university_name' => $universityName,
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('OpenAI API isteği başarısız oldu.', [
                    'university_name' => $universityName,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (empty($data)) {
                return null;
            }

            // Response'dan JSON string'i çıkar
            $jsonText = $this->extractJsonFromResponse($data);

            if (empty($jsonText)) {
                Log::warning('OpenAI response\'dan JSON çıkarılamadı.', [
                    'university_name' => $universityName,
                ]);

                return null;
            }

            // JSON'u parse et
            $parsed = json_decode($jsonText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('OpenAI response JSON parse edilemedi.', [
                    'university_name' => $universityName,
                    'json_error' => json_last_error_msg(),
                ]);

                return null;
            }

            return $parsed;
        } catch (Throwable $exception) {
            Log::error('OpenAI API isteği sırasında hata oluştu.', [
                'university_name' => $universityName,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Extract JSON text from OpenAI response
     *
     * @param  array<string, mixed>  $response
     * @return string|null
     */
    protected function extractJsonFromResponse(array $response): ?string
    {
        $output = $response['output'] ?? [];

        if (! is_array($output)) {
            return null;
        }

        // output array'inde type: "message" olan item'ı bul
        foreach ($output as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            $content = $item['content'] ?? [];

            if (! is_array($content) || empty($content)) {
                continue;
            }

            // İlk content item'ının text'ini al
            $firstContent = $content[0] ?? null;

            if (! is_array($firstContent)) {
                continue;
            }

            $text = $firstContent['text'] ?? null;

            if (is_string($text) && $text !== '') {
                // JSON string'i temizle (code block varsa kaldır)
                $text = trim($text);
                $text = preg_replace('/^```json\s*/i', '', $text);
                $text = preg_replace('/\s*```$/', '', $text);
                $text = trim($text);

                return $text;
            }
        }

        return null;
    }
}

