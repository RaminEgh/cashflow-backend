<?php

namespace App\Console\Commands;

use App\Services\Rahkaran\OrganizationFetchService;
use Illuminate\Console\Command;

class FetchOrgansFromRahkaranCommand extends Command
{
    public function __construct(protected OrganizationFetchService $organizationFetchService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-organs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to fetch organs from rahkaran';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching organs from rahkaran...');

        try {
            $this->organizationFetchService->fetchAndStore();
            $this->info('Organizations fetched and saved successfully.');
        } catch (\Exception $e) {
            $this->error('Failed: '.$e->getMessage());
        }

        $this->info('All balance update jobs have been dispatched successfully!');

    }
}
