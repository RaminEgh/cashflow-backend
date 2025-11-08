<?php

namespace App\Console\Commands;

use App\Services\Rahkaran\DepositFetchService;
use Illuminate\Console\Command;

class FetchDepositsFromRahkaranCommand extends Command
{
    public function __construct(protected DepositFetchService $depositFetchService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-deposits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to fetch deposits from rahkaran';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching deposits from rahkaran...');

        try {
            $this->depositFetchService->fetchAndStore();
            $this->info('Deposits fetched and saved successfully.');
        } catch (\Exception $e) {
            $this->error('Failed: '.$e->getMessage());
        }

        $this->info('All balance update jobs have been dispatched successfully!');

    }
}
