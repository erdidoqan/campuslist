<?php

namespace App\Console\Commands;

use App\Models\Major;
use Illuminate\Console\Command;

class UpdateMajorsTitleAndMetaDescription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'majors:update-title-meta 
                            {--force : Force update even if title/meta_description already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update title and meta_description for existing majors';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');

        $query = Major::query();

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('title')
                    ->orWhereNull('meta_description')
                    ->orWhere('title', '')
                    ->orWhere('meta_description', '');
            });
        }

        $majors = $query->get();
        $total = $majors->count();

        if ($total === 0) {
            $this->info('Güncellenecek major bulunamadı.');

            return Command::SUCCESS;
        }

        $this->info("Toplam {$total} major güncellenecek...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;

        foreach ($majors as $major) {
            $needsUpdate = false;

            if (empty($major->title) || $force) {
                $major->title = $major->generateTitle();
                $needsUpdate = true;
            }

            if (empty($major->meta_description) || $force) {
                $major->meta_description = $major->generateMetaDescription();
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $major->save();
                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$updated} major başarıyla güncellendi.");

        return Command::SUCCESS;
    }
}
