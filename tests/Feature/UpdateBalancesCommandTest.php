<?php

use App\Jobs\FetchBankAccountBalance;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\Organ;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

beforeEach(function () {
    Bus::fake();
});

it('dispatches jobs for all deposits when no options are provided', function () {
    $organ = Organ::factory()->create();
    $bank = Bank::factory()->create();

    $deposit1 = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $deposit2 = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $this->artisan('app:update-balances')
        ->expectsOutput('Finding all bank accounts to dispatch update jobs...')
        ->expectsOutput(" - Dispatched job for account: {$deposit1->id}")
        ->expectsOutput(" - Dispatched job for account: {$deposit2->id}")
        ->expectsOutput('All balance update jobs have been dispatched successfully! (2 deposits)')
        ->assertSuccessful();

    Bus::assertDispatched(FetchBankAccountBalance::class, 2);
    Bus::assertDispatched(FetchBankAccountBalance::class, function ($job) use ($deposit1) {
        return $job->deposit->id === $deposit1->id;
    });
    Bus::assertDispatched(FetchBankAccountBalance::class, function ($job) use ($deposit2) {
        return $job->deposit->id === $deposit2->id;
    });
});

it('dispatches jobs only for deposits of a specific organ when --organ option is provided', function () {
    $organ1 = Organ::factory()->create(['name' => 'Organ 1']);
    $organ2 = Organ::factory()->create(['name' => 'Organ 2']);
    $bank = Bank::factory()->create();

    $deposit1 = Deposit::factory()->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank->id,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $deposit2 = Deposit::factory()->create([
        'organ_id' => $organ2->id,
        'bank_id' => $bank->id,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $this->artisan("app:update-balances --organ={$organ1->id}")
        ->expectsOutput("Finding bank accounts for organ: {$organ1->name} (ID: {$organ1->id})...")
        ->expectsOutput(" - Dispatched job for account: {$deposit1->id} (Deposit: {$deposit1->number})")
        ->expectsOutput("Balance update jobs dispatched successfully for organ: {$organ1->name} (1 deposits)")
        ->assertSuccessful();

    Bus::assertDispatched(FetchBankAccountBalance::class, 1);
    Bus::assertDispatched(FetchBankAccountBalance::class, function ($job) use ($deposit1) {
        return $job->deposit->id === $deposit1->id;
    });
});

it('returns error when organ is not found with --organ option', function () {
    $this->artisan('app:update-balances --organ=999')
        ->expectsOutput('Organ with ID 999 not found.')
        ->assertFailed();
});

it('dispatches jobs for all organs when --all-organs option is provided', function () {
    $organ1 = Organ::factory()->create(['name' => 'Organ 1']);
    $organ2 = Organ::factory()->create(['name' => 'Organ 2']);
    $bank = Bank::factory()->create();

    $deposit1 = Deposit::factory()->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank->id,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $deposit2 = Deposit::factory()->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank->id,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $deposit3 = Deposit::factory()->create([
        'organ_id' => $organ2->id,
        'bank_id' => $bank->id,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $this->artisan('app:update-balances --all-organs')
        ->expectsOutput('Finding all organs to dispatch update jobs...')
        ->expectsOutput("Processing organ: {$organ1->name} (2 deposits)")
        ->expectsOutput("   - Dispatched job for account: {$deposit1->id} (Deposit: {$deposit1->number})")
        ->expectsOutput("   - Dispatched job for account: {$deposit2->id} (Deposit: {$deposit2->number})")
        ->expectsOutput("Processing organ: {$organ2->name} (1 deposits)")
        ->expectsOutput("   - Dispatched job for account: {$deposit3->id} (Deposit: {$deposit3->number})")
        ->expectsOutput('All balance update jobs have been dispatched successfully! (2 organs, 3 deposits)')
        ->assertSuccessful();

    Bus::assertDispatched(FetchBankAccountBalance::class, 3);
});

it('skips organs with no deposits when --all-organs option is provided', function () {
    $organ1 = Organ::factory()->create(['name' => 'Organ 1']);
    $organ2 = Organ::factory()->create(['name' => 'Organ 2']);
    $bank = Bank::factory()->create();

    $deposit1 = Deposit::factory()->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank->id,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $this->artisan('app:update-balances --all-organs')
        ->expectsOutput('Finding all organs to dispatch update jobs...')
        ->expectsOutput("Processing organ: {$organ1->name} (1 deposits)")
        ->expectsOutput("   - Dispatched job for account: {$deposit1->id} (Deposit: {$deposit1->number})")
        ->expectsOutput(" - Skipping organ: {$organ2->name} (no deposits)")
        ->expectsOutput('All balance update jobs have been dispatched successfully! (2 organs, 1 deposits)')
        ->assertSuccessful();

    Bus::assertDispatched(FetchBankAccountBalance::class, 1);
});

it('returns warning when no deposits exist for default behavior', function () {
    $this->artisan('app:update-balances')
        ->expectsOutput('Finding all bank accounts to dispatch update jobs...')
        ->expectsOutput('No deposits found.')
        ->assertFailed();
});

it('returns warning when organ has no deposits', function () {
    $organ = Organ::factory()->create(['name' => 'Organ 1']);

    $this->artisan("app:update-balances --organ={$organ->id}")
        ->expectsOutput("Finding bank accounts for organ: {$organ->name} (ID: {$organ->id})...")
        ->expectsOutput("No deposits found for organ: {$organ->name}")
        ->assertFailed();
});

it('returns warning when no organs exist with --all-organs option', function () {
    $this->artisan('app:update-balances --all-organs')
        ->expectsOutput('Finding all organs to dispatch update jobs...')
        ->expectsOutput('No organs found.')
        ->assertFailed();
});
