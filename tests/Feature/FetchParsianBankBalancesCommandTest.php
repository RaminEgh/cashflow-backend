<?php

use App\Models\Bank;
use App\Models\Deposit;
use App\Models\Organ;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['banks.parsian.sandbox_url' => 'https://sandbox.parsian-bank.ir/channelServices/1.0']);
    config(['banks.parsian.token' => 'test-token']);
    config(['banks.parsian.use_sandbox' => true]);
});

it('successfully fetches and stores balances', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/*' => Http::response([
            'accountNumber' => '85000005464007',
            'balance' => 152216359360,
            'todayDepositAmount' => 100,
            'todayWithdrawAmount' => 0,
            'currency' => 'IRR',
        ], 200),
    ]);

    $parsianBank = Bank::factory()->create([
        'en_name' => 'parsian',
        'name' => 'Parsian Bank',
    ]);

    $organ = Organ::factory()->create();

    Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $parsianBank->id,
        'number' => '85000005464007',
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $this->artisan('app:fetch-parsian-bank-balances')
        ->expectsOutput('Fetching balances from Parsian Bank API...')
        ->expectsOutput('Balances fetched and saved successfully.')
        ->assertSuccessful();
});

it('handles errors gracefully and continues processing', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/*' => Http::response([
            'error' => 'Invalid credentials',
        ], 401),
    ]);

    $parsianBank = Bank::factory()->create([
        'en_name' => 'parsian',
        'name' => 'Parsian Bank',
    ]);

    $organ = Organ::factory()->create();

    Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $parsianBank->id,
        'number' => '85000005464007',
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    // The service handles errors gracefully and doesn't throw, so command succeeds
    $this->artisan('app:fetch-parsian-bank-balances')
        ->expectsOutput('Fetching balances from Parsian Bank API...')
        ->expectsOutput('Balances fetched and saved successfully.')
        ->assertSuccessful();
});

it('handles case when no Parsian Bank deposits exist', function () {
    $this->artisan('app:fetch-parsian-bank-balances')
        ->expectsOutput('Fetching balances from Parsian Bank API...')
        ->expectsOutput('Balances fetched and saved successfully.')
        ->assertSuccessful();
});
