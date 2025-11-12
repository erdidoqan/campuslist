<?php

namespace App\Console\Commands;

use App\Models\University;
use App\Models\UniversityScore;
use App\Services\OpenAI\OpenAIClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScoreUniversities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'universities:score 
                            {--limit=10 : İşlenecek maksimum üniversite sayısı}
                            {--force : Mevcut puanları da güncelle}
                            {--university-id= : Belirli bir üniversiteyi puanla}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'OpenAI kullanarak üniversiteleri puanlar (place_raw verisi gereklidir)';

    public function __construct(
        private readonly OpenAIClient $openAIClient
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apiKey = config('services.openai.key');

        if (empty($apiKey)) {
            $this->error('OpenAI API anahtarı bulunamadı. Lütfen .env dosyasında OPENAI_API_KEY değerini tanımlayın.');

            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $force = $this->option('force');
        $universityId = $this->option('university-id');

        // Query oluştur
        $query = University::query()->whereNotNull('place_raw');

        // Belirli bir üniversite
        if ($universityId) {
            $query->where('id', $universityId);
        } else {
            // Puanı olmayanları çek (force değilse)
            if (! $force) {
                $query->doesntHave('score');
            }
        }

        $universities = $query->limit($limit)->get();

        if ($universities->isEmpty()) {
            $this->info('Puanlanacak üniversite bulunamadı.');

            return self::SUCCESS;
        }

        $this->info(sprintf('%d adet üniversite puanlanacak.', $universities->count()));

        $successCount = 0;
        $errorCount = 0;

        foreach ($universities as $index => $university) {
            $this->line(sprintf('[%d/%d] "%s" puanlanıyor...', $index + 1, $universities->count(), $university->name));

            try {
                $scoreData = $this->openAIClient->fetchUniversityScore($university->place_raw);

                if (empty($scoreData)) {
                    $this->warn(sprintf('"%s" için OpenAI\'dan puan alınamadı.', $university->name));
                    $errorCount++;
                    continue;
                }

                // OpenAI'dan gelen field adları: overall_grade, ratings
                $overallGrade = $scoreData['overall_grade'] ?? null;
                $ratings = $scoreData['ratings'] ?? null;

                if ($overallGrade === null && $ratings === null) {
                    $this->warn(sprintf('"%s" için geçerli puan verisi yok.', $university->name));
                    $this->line('OpenAI Response Keys: '.implode(', ', array_keys($scoreData)));
                    $errorCount++;
                    continue;
                }

                UniversityScore::updateOrCreate(
                    ['university_id' => $university->id],
                    [
                        'overall_grade' => $overallGrade,
                        'ratings' => $ratings,
                        'response_raw' => $scoreData,
                    ]
                );

                $this->info(sprintf('"%s" için puan kaydedildi (Overall: %s).', $university->name, $overallGrade ?? 'N/A'));
                $successCount++;
            } catch (Throwable $exception) {
                Log::error('Üniversite puanlaması sırasında hata oluştu.', [
                    'university_id' => $university->id,
                    'university_name' => $university->name,
                    'message' => $exception->getMessage(),
                ]);

                $this->error(sprintf('"%s" puanlaması yapılırken hata oluştu: %s', $university->name, $exception->getMessage()));
                $errorCount++;
            }

            // Rate limiting için bekleme
            if ($index < $universities->count() - 1) {
                sleep(2);
            }
        }

        $this->info(sprintf('Puanlama tamamlandı. Başarılı: %d, Hatalı: %d', $successCount, $errorCount));

        return self::SUCCESS;
    }
}
