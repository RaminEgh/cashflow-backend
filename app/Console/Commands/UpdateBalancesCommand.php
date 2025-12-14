<?php

namespace App\Console\Commands;

use App\Jobs\FetchBankAccountBalance;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\Organ;
use Illuminate\Console\Command;

class UpdateBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-balances
                            {--organ= : Update balances for all deposits of a specific organ (organ ID)}
                            {--bank= : Update balances for all deposits of a specific bank (bank slug or ID)}
                            {--all-organs : Update balances for all organs, grouped by organ}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to update balances for all bank accounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $organId = $this->option('organ');
        $bankIdentifier = $this->option('bank');
        $allOrgans = $this->option('all-organs');

        // Combination: organ + bank
        if ($organId && $bankIdentifier) {
            return $this->updateForOrganAndBank((int) $organId, $bankIdentifier);
        }

        // Single filters
        if ($allOrgans) {
            return $this->updateForAllOrgans();
        } elseif ($organId) {
            return $this->updateForOrgan((int) $organId);
        } elseif ($bankIdentifier) {
            return $this->updateForBank($bankIdentifier);
        }

        // Default: all deposits
        return $this->updateForAllDeposits();
    }

    /**
     * Update balances for all deposits (default behavior).
     */
    private function updateForAllDeposits(): int
    {
        $this->info('Finding all bank accounts to dispatch update jobs...');
        $deposits = Deposit::all();

        if ($deposits->isEmpty()) {
            $this->warn('No deposits found.');

            return 1;
        }

        foreach ($deposits as $deposit) {
            FetchBankAccountBalance::dispatch($deposit)
                ->tags(['balance-update', 'scheduled']);
            $this->line(" - Dispatched job for account: {$deposit->id}");
        }

        $this->info("All balance update jobs have been dispatched successfully! ({$deposits->count()} deposits)");

        return 0;
    }

    /**
     * Update balances for all deposits of a specific organ.
     */
    private function updateForOrgan(int $organId): int
    {
        $organ = Organ::find($organId);

        if (! $organ) {
            $this->error("Organ with ID {$organId} not found.");

            return 1;
        }

        $this->info("Finding bank accounts for organ: {$organ->name} (ID: {$organ->id})...");
        $deposits = $organ->deposits;

        if ($deposits->isEmpty()) {
            $this->warn("No deposits found for organ: {$organ->name}");

            return 1;
        }

        foreach ($deposits as $deposit) {
            FetchBankAccountBalance::dispatch($deposit)
                ->tags(['balance-update', 'scheduled']);
            $this->line(" - Dispatched job for account: {$deposit->id} (Deposit: {$deposit->number})");
        }

        $this->info("Balance update jobs dispatched successfully for organ: {$organ->name} ({$deposits->count()} deposits)");

        return 0;
    }

    /**
     * Update balances for all organs, grouped by organ.
     */
    private function updateForAllOrgans(): int
    {
        $this->info('Finding all organs to dispatch update jobs...');
        $organs = Organ::with('deposits')->get();

        if ($organs->isEmpty()) {
            $this->warn('No organs found.');

            return 1;
        }

        $totalDeposits = 0;

        foreach ($organs as $organ) {
            $deposits = $organ->deposits;

            if ($deposits->isEmpty()) {
                $this->line(" - Skipping organ: {$organ->name} (no deposits)");

                continue;
            }

            $this->info("Processing organ: {$organ->name} ({$deposits->count()} deposits)");

            foreach ($deposits as $deposit) {
                FetchBankAccountBalance::dispatch($deposit)
                    ->tags(['balance-update', 'scheduled']);
                $this->line("   - Dispatched job for account: {$deposit->id} (Deposit: {$deposit->number})");
            }

            $totalDeposits += $deposits->count();
        }

        $this->info("All balance update jobs have been dispatched successfully! ({$organs->count()} organs, {$totalDeposits} deposits)");

        return 0;
    }

    /**
     * Update balances for all deposits of a specific bank.
     */
    private function updateForBank(string $bankIdentifier): int
    {
        // Try to find bank by slug first, then by ID
        $bank = Bank::where('slug', $bankIdentifier)->first() ?? Bank::find($bankIdentifier);

        if (! $bank) {
            $this->error("Bank with identifier '{$bankIdentifier}' not found.");
            $this->info('Available banks:');
            Bank::all()->each(fn($b) => $this->line("  - {$b->name} (slug: {$b->slug}, ID: {$b->id})"));

            return 1;
        }

        $this->info("Finding bank accounts for bank: {$bank->name} (Slug: {$bank->slug})...");
        $deposits = $bank->deposits;

        if ($deposits->isEmpty()) {
            $this->warn("No deposits found for bank: {$bank->name}");

            return 1;
        }

        foreach ($deposits as $deposit) {
            FetchBankAccountBalance::dispatch($deposit)
                ->tags(['balance-update', 'scheduled', "bank:{$bank->slug}"]);
            $this->line(" - Dispatched job for account: {$deposit->id} (Deposit: {$deposit->number})");
        }

        $this->info("Balance update jobs dispatched successfully for bank: {$bank->name} ({$deposits->count()} deposits)");

        return 0;
    }

    /**
     * Update balances for all deposits of a specific organ and bank combination.
     */
    private function updateForOrganAndBank(int $organId, string $bankIdentifier): int
    {
        $organ = Organ::find($organId);

        if (! $organ) {
            $this->error("Organ with ID {$organId} not found.");

            return 1;
        }

        // Try to find bank by slug first, then by ID
        $bank = Bank::where('slug', $bankIdentifier)->first() ?? Bank::find($bankIdentifier);

        if (! $bank) {
            $this->error("Bank with identifier '{$bankIdentifier}' not found.");

            return 1;
        }

        $this->info("Finding bank accounts for organ: {$organ->name} and bank: {$bank->name}...");
        $deposits = Deposit::where('organ_id', $organId)
            ->where('bank_id', $bank->id)
            ->get();

        if ($deposits->isEmpty()) {
            $this->warn("No deposits found for organ: {$organ->name} and bank: {$bank->name}");

            return 1;
        }

        foreach ($deposits as $deposit) {
            FetchBankAccountBalance::dispatch($deposit)
                ->tags(['balance-update', 'scheduled', "organ:{$organ->slug}", "bank:{$bank->slug}"]);
            $this->line(" - Dispatched job for account: {$deposit->id} (Deposit: {$deposit->number})");
        }

        $this->info("Balance update jobs dispatched successfully for organ: {$organ->name} and bank: {$bank->name} ({$deposits->count()} deposits)");

        return 0;
    }
}

