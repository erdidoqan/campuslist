<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled Tasks
// ABD gündüz saatleri (9:00-21:00 EST) arasında her saat çalışan task chain
Schedule::command('serpapi:fetch-trends')
    ->hourly()
    ->between('9:00', '21:00')
    ->timezone('America/New_York')
    ->before(function () {
        \Log::info('University Data Pipeline başlatılıyor...', [
            'time' => now()->toDateTimeString(),
            'timezone' => 'America/New_York',
        ]);
    })
    ->then(function () {
        \Log::info('serpapi:fetch-trends tamamlandı, openai:fetch-university-data başlatılıyor...');
        
        // Trends fetch'ten sonra university data'yı güncelle
        $exitCode = Artisan::call('openai:fetch-university-data', ['--limit' => 25]);
        
        \Log::info('openai:fetch-university-data tamamlandı', [
            'exit_code' => $exitCode,
        ]);
    })
    ->then(function () {
        \Log::info('universities:score başlatılıyor...');
        
        // University data'dan sonra scoring yap
        $exitCode = Artisan::call('universities:score', ['--limit' => 25]);
        
        \Log::info('universities:score tamamlandı', [
            'exit_code' => $exitCode,
        ]);
    })
    ->then(function () {
        \Log::info('Revalidate isteği gönderiliyor...');
        
        try {
            // Tüm işler bittikten sonra revalidate isteği at
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->post('https://www.greetingbirds.com/api/university/revalidate');
            
            if ($response->successful()) {
                \Log::info('Revalidate isteği başarılı', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } else {
                \Log::warning('Revalidate isteği başarısız', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Revalidate isteği gönderilirken hata oluştu', [
                'error' => $e->getMessage(),
            ]);
        }
    })
    ->onSuccess(function () {
        \Log::info('University Data Pipeline başarıyla tamamlandı!', [
            'completed_at' => now()->toDateTimeString(),
        ]);
    })
    ->onFailure(function () {
        \Log::error('University Data Pipeline başarısız oldu!', [
            'failed_at' => now()->toDateTimeString(),
        ]);
    })
    ->name('university-data-pipeline')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/schedule.log'));
