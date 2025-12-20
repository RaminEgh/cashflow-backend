<?php

namespace App\Console\Commands;

use App\Services\Rahkaran\BalanceFetchService;
use Illuminate\Console\Command;

class FetchBalancesFromRahkaranCommand extends Command
{
    public function __construct(protected BalanceFetchService $balanceFetchService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-rahkaran-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to fetch balances from rahkaran';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching balances from rahkaran...');

        try {
            $this->balanceFetchService->fetchAndStore();
            $this->info('Balances fetched and saved successfully.');
        } catch (\Exception $e) {
            $this->error('Failed: '.$e->getMessage());
        }

        $this->info('All balance update jobs have been dispatched successfully!');

    }
}
