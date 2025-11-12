<?php

namespace App\Providers;

use App\Console\Commands\FetchSerpTrends;
use App\Console\Commands\FetchUniversityDataFromOpenAI;
use App\Console\Commands\GenerateApiToken;
use App\Console\Commands\ScoreUniversities;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->commands([
            FetchSerpTrends::class,
            FetchUniversityDataFromOpenAI::class,
            GenerateApiToken::class,
            ScoreUniversities::class,
        ]);
    }
}
