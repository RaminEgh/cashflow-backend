<?php

namespace App\Console\Commands;

use App\Services\Rahkaran\TimelineFetchService;
use Illuminate\Console\Command;

class FetchTimelineCommand extends Command
{
    public function __construct(protected TimelineFetchService $timelineFetchService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-timeline
                            {--organ= : Fetch timeline for specific organ slug}
                            {--all : Fetch timeline for all organs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch timeline data from external API for organizations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $organSlug = $this->option('organ');
        $all = $this->option('all');

        if ($organSlug) {
            $this->fetchForSpecificOrgan($organSlug);
        } elseif ($all) {
            $this->fetchForAllOrgans();
        } else {
            $this->error('Please specify --organ=slug or --all option');
            return 1;
        }

        return 0;
    }

    private function fetchForSpecificOrgan(string $organSlug): void
    {
        $this->info("Fetching timeline for organ: {$organSlug}");

        try {
            $organ = \App\Models\Organ::where('slug', $organSlug)->first();

            if (!$organ) {
                $this->error("Organ with slug '{$organSlug}' not found");
                return;
            }

            $this->timelineFetchService->fetchAndStoreForOrgan($organ);
            $this->info("Timeline data fetched and saved successfully for organ: {$organSlug}");
        } catch (\Exception $e) {
            $this->error("Failed to fetch timeline for organ '{$organSlug}': " . $e->getMessage());
        }
    }

    private function fetchForAllOrgans(): void
    {
        $this->info('Fetching timeline data for all organizations...');

        try {
            $this->timelineFetchService->fetchAndStore();
            $this->info('Timeline data fetched and saved successfully for all organizations!');
        } catch (\Exception $e) {
            $this->error('Failed to fetch timeline data: ' . $e->getMessage());
        }
    }
}
