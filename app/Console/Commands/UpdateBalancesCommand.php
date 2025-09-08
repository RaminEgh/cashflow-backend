<?php

namespace App\Console\Commands;

use App\Jobs\FetchBankAccountBalance;
use App\Models\Deposit;
use Illuminate\Console\Command;

class UpdateBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to update balances for all bank accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Finding all bank accounts to dispatch update jobs...');
        $deposits = Deposit::all();

        foreach ($deposits as $deposit) {
            // It serializes the job and puts it into the 'jobs' database table.
            FetchBankAccountBalance::dispatch($deposit);
            $this->line(" - Dispatched job for account: {$deposit->id}");
        }

        $this->info('All balance update jobs have been dispatched successfully!');

    }
}
