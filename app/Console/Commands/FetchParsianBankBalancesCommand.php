<?php

namespace App\Console\Commands;

use App\Services\ParsianBank\BalanceFetchService;
use Illuminate\Console\Command;

class FetchParsianBankBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-parsian-bank-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch balances from Parsian Bank API for all Parsian Bank deposits';

    /**
     * Execute the console command.
     */
    public function handle(BalanceFetchService $balanceFetchService): int
    {
        $this->info('Fetching balances from Parsian Bank API...');

        try {
            $balanceFetchService->fetchAndStore();
            $this->info('Balances fetched and saved successfully.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
