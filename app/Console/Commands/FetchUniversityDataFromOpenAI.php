<?php

namespace App\Console\Commands;

use App\Models\Major;
use App\Models\University;
use App\Services\OpenAI\OpenAIClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FetchUniversityDataFromOpenAI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openai:fetch-university-data 
                            {--limit=25 : İşlenecek maksimum üniversite sayısı}
                            {--force : Mevcut verileri de güncelle}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'OpenAI web search API kullanarak üniversite verilerini tamamlar';

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

        // founded alanı boş olanları çek
        $query = University::query()
            ->whereNull('founded');

        if (! $force) {
            // Sadece boş alanları doldur, mevcut verileri güncelleme
            $query->where(function ($q) {
                $q->whereNull('short_name')
                    ->orWhereNull('type')
                    ->orWhereNull('acceptance_rate')
                    ->orWhereNull('enrollment')
                    ->orWhereNull('tuition')
                    ->orWhereNull('overview');
            });
        }

        $universities = $query->limit($limit)->get();

        if ($universities->isEmpty()) {
            $this->info('İşlenecek üniversite bulunamadı.');

            return self::SUCCESS;
        }

        $this->info(sprintf('%d adet üniversite işlenecek.', $universities->count()));

        $successCount = 0;
        $errorCount = 0;

        foreach ($universities as $index => $university) {
            $this->line(sprintf('[%d/%d] "%s" işleniyor...', $index + 1, $universities->count(), $university->place_title ?? $university->name));

            $universityName = $university->place_title ?? $university->name;

            if (empty($universityName)) {
                $this->warn('Üniversite adı bulunamadı, atlanıyor.');
                $errorCount++;
                continue;
            }

            try {
                $data = $this->openAIClient->fetchUniversityData($universityName);

                if (empty($data)) {
                    $this->warn(sprintf('"%s" için OpenAI\'dan veri alınamadı.', $universityName));
                    $errorCount++;
                    continue;
                }

                $attributes = $this->mapOpenAIDataToAttributes($data, $university);

                if (empty($attributes)) {
                    $this->warn(sprintf('"%s" için veri map edilemedi.', $universityName));
                    $errorCount++;
                    continue;
                }

                // Sadece boş alanları doldur (force değilse)
                if (! $force) {
                    $attributes = $this->filterEmptyFields($attributes, $university);
                }

                if (empty($attributes)) {
                    $this->line(sprintf('"%s" için güncellenecek yeni veri yok.', $universityName));
                    continue;
                }

                $university->fill($attributes);
                $university->save();

                // Majors ilişkisini kur
                if (isset($data['majors']) || isset($data['notable_majors'])) {
                    $this->syncMajors($university, $data);
                }

                $this->info(sprintf('"%s" başarıyla güncellendi.', $universityName));
                $successCount++;
            } catch (Throwable $exception) {
                Log::error('Üniversite verisi güncellenirken hata oluştu.', [
                    'university_id' => $university->id,
                    'university_name' => $universityName,
                    'message' => $exception->getMessage(),
                ]);

                $this->error(sprintf('"%s" işlenirken hata oluştu: %s', $universityName, $exception->getMessage()));
                $errorCount++;
            }

            // Rate limiting için kısa bir bekleme
            if ($index < $universities->count() - 1) {
                sleep(2);
            }
        }

        $this->info(sprintf('İşlem tamamlandı. Başarılı: %d, Hatalı: %d', $successCount, $errorCount));

        return self::SUCCESS;
    }

    /**
     * Map OpenAI response data to University model attributes
     *
     * @param  array<string, mixed>  $data
     * @param  University  $university
     * @return array<string, mixed>
     */
    protected function mapOpenAIDataToAttributes(array $data, University $university): array
    {
        $attributes = [];

        // Basit string alanlar
        if (isset($data['name']) && is_string($data['name']) && $data['name'] !== '') {
            $attributes['name'] = $data['name'];
        }

        if (isset($data['short_name']) && is_string($data['short_name'])) {
            $attributes['short_name'] = $data['short_name'];
        }

        if (isset($data['location']) && is_string($data['location'])) {
            $attributes['location'] = $data['location'];
        }

        if (isset($data['website']) && is_string($data['website'])) {
            $attributes['website'] = $data['website'];
        }

        if (isset($data['type']) && is_string($data['type'])) {
            $attributes['type'] = $data['type'];
        }

        if (isset($data['overview']) && is_string($data['overview'])) {
            $attributes['overview'] = $data['overview'];
        }

        // Founded date - string'den date'e çevir
        if (isset($data['founded']) && is_string($data['founded']) && $data['founded'] !== '') {
            try {
                // Sadece yıl varsa (örn: "1850")
                if (preg_match('/^\d{4}$/', $data['founded'])) {
                    $attributes['founded'] = Carbon::createFromFormat('Y', $data['founded'])->startOfYear();
                } else {
                    // ISO 8601 formatı (YYYY-MM-DD)
                    $attributes['founded'] = Carbon::parse($data['founded']);
                }
            } catch (Throwable $e) {
                Log::warning('Founded date parse edilemedi.', [
                    'founded' => $data['founded'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Acceptance rate
        if (isset($data['acceptance_rate']) && is_numeric($data['acceptance_rate'])) {
            $attributes['acceptance_rate'] = (int) $data['acceptance_rate'];
        }

        // JSON alanlar
        if (isset($data['ranking']) && is_array($data['ranking'])) {
            $attributes['ranking'] = $data['ranking'];
        }

        if (isset($data['enrollment']) && is_array($data['enrollment'])) {
            $attributes['enrollment'] = $data['enrollment'];
            // Filtreleme için ayrı kolonlar
            if (isset($data['enrollment']['total']) && is_numeric($data['enrollment']['total'])) {
                $attributes['enrollment_total'] = (int) $data['enrollment']['total'];
            }
            if (isset($data['enrollment']['undergraduate']) && is_numeric($data['enrollment']['undergraduate'])) {
                $attributes['enrollment_undergraduate'] = (int) $data['enrollment']['undergraduate'];
            }
            if (isset($data['enrollment']['graduate']) && is_numeric($data['enrollment']['graduate'])) {
                $attributes['enrollment_graduate'] = (int) $data['enrollment']['graduate'];
            }
        }

        if (isset($data['tuition']) && is_array($data['tuition'])) {
            $attributes['tuition'] = $data['tuition'];
            // Filtreleme için ayrı kolonlar
            if (isset($data['tuition']['undergraduate']) && is_numeric($data['tuition']['undergraduate'])) {
                $attributes['tuition_undergraduate'] = (int) $data['tuition']['undergraduate'];
            }
            if (isset($data['tuition']['graduate']) && is_numeric($data['tuition']['graduate'])) {
                $attributes['tuition_graduate'] = (int) $data['tuition']['graduate'];
            }
            if (isset($data['tuition']['intl']) && is_numeric($data['tuition']['intl'])) {
                $attributes['tuition_international'] = (int) $data['tuition']['intl'];
            }
            if (isset($data['tuition']['currency']) && is_string($data['tuition']['currency'])) {
                $attributes['tuition_currency'] = strtoupper(substr($data['tuition']['currency'], 0, 3));
            }
        }

        if (isset($data['deadlines']) && is_array($data['deadlines'])) {
            $attributes['deadlines'] = $data['deadlines'];
        }

        if (isset($data['requirements']) && is_array($data['requirements'])) {
            $attributes['requirements'] = $data['requirements'];
            // Filtreleme için ayrı kolonlar
            if (isset($data['requirements']['gpa_min']) && is_numeric($data['requirements']['gpa_min'])) {
                $attributes['requirement_gpa_min'] = (float) $data['requirements']['gpa_min'];
            }
            if (isset($data['requirements']['sat']) && is_numeric($data['requirements']['sat'])) {
                $attributes['requirement_sat'] = (int) $data['requirements']['sat'];
            }
            if (isset($data['requirements']['act']) && is_numeric($data['requirements']['act'])) {
                $attributes['requirement_act'] = (int) $data['requirements']['act'];
            }
            if (isset($data['requirements']['toefl']) && is_numeric($data['requirements']['toefl'])) {
                $attributes['requirement_toefl'] = (int) $data['requirements']['toefl'];
            }
            if (isset($data['requirements']['ielts']) && is_numeric($data['requirements']['ielts'])) {
                $attributes['requirement_ielts'] = (float) $data['requirements']['ielts'];
            }
        }

        // Majors JSON olarak da sakla (geriye dönük uyumluluk için)
        if (isset($data['majors']) && is_array($data['majors'])) {
            $attributes['majors'] = $data['majors'];
        }

        if (isset($data['notable_majors']) && is_array($data['notable_majors'])) {
            $attributes['notable_majors'] = $data['notable_majors'];
        }

        if (isset($data['scholarships']) && is_array($data['scholarships'])) {
            $attributes['scholarships'] = $data['scholarships'];
        }

        if (isset($data['housing']) && is_array($data['housing'])) {
            $attributes['housing'] = $data['housing'];
        }

        if (isset($data['campus_life']) && is_array($data['campus_life'])) {
            $attributes['campus_life'] = $data['campus_life'];
        }

        if (isset($data['contact']) && is_array($data['contact'])) {
            $attributes['contact'] = $data['contact'];
            // contact içindeki phone'u ayrı field'a da kaydet
            if (isset($data['contact']['phone']) && is_string($data['contact']['phone'])) {
                $attributes['phone'] = $data['contact']['phone'];
            }
            // contact içindeki address'i ayrı field'a da kaydet
            if (isset($data['contact']['address']) && is_string($data['contact']['address'])) {
                $attributes['address'] = $data['contact']['address'];
            }
        }

        if (isset($data['faq']) && is_array($data['faq'])) {
            $attributes['faq'] = $data['faq'];
        }

        return $attributes;
    }

    /**
     * Filter attributes to only include fields that are empty in the database
     *
     * @param  array<string, mixed>  $attributes
     * @param  University  $university
     * @return array<string, mixed>
     */
    protected function filterEmptyFields(array $attributes, University $university): array
    {
        $filtered = [];

        foreach ($attributes as $key => $value) {
            $currentValue = $university->getAttribute($key);

            // Null veya boş ise ekle
            if ($currentValue === null || $currentValue === '' || $currentValue === []) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Sync majors relationship for university
     *
     * @param  University  $university
     * @param  array<string, mixed>  $data
     * @return void
     */
    protected function syncMajors(University $university, array $data): void
    {
        $allMajors = [];
        $notableMajors = [];

        // Tüm majors'ları topla
        if (isset($data['majors']) && is_array($data['majors'])) {
            $allMajors = array_map('trim', $data['majors']);
            $allMajors = array_filter($allMajors);
        }

        // Notable majors'ları topla
        if (isset($data['notable_majors']) && is_array($data['notable_majors'])) {
            $notableMajors = array_map('trim', $data['notable_majors']);
            $notableMajors = array_filter($notableMajors);
        }

        // Notable majors'ları da tüm majors listesine ekle
        $allMajors = array_unique(array_merge($allMajors, $notableMajors));

        if (empty($allMajors)) {
            return;
        }

        $majorIds = [];

        foreach ($allMajors as $majorName) {
            if (empty($majorName)) {
                continue;
            }

            // Major'ı bul veya oluştur
            $major = Major::firstOrCreate(
                ['name' => $majorName],
                ['slug' => Str::slug($majorName)]
            );

            $majorIds[$major->id] = [
                'is_notable' => in_array($majorName, $notableMajors, true),
            ];
        }

        // Many-to-many ilişkiyi sync et
        $university->majorsRelation()->sync($majorIds);

        $this->line(sprintf('"%s" için %d major ilişkilendirildi.', $university->name, count($majorIds)));
    }
}

