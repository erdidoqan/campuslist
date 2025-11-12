<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled Tasks
// ABD gündüz saatleri (9:00-18:00 EST) arasında her saat çalışan task chain
Schedule::command('serpapi:fetch-trends')
    ->hourly()
    ->between('9:00', '21:00')
    ->timezone('America/New_York')
    ->then(function () {
        // Trends fetch'ten sonra university data'yı güncelle
        Artisan::call('openai:fetch-university-data', ['--limit' => 20]);
    })
    ->then(function () {
        // University data'dan sonra scoring yap
        Artisan::call('universities:score', ['--limit' => 20]);
    })
    ->name('university-data-pipeline')
    ->withoutOverlapping()
    ->onOneServer();
