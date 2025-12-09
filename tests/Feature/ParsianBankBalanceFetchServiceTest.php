<?php

use App\Models\Balance;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\Organ;
use App\Services\Banking\BankAdapterFactory;
use App\Services\ParsianBank\BalanceFetchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['banks.parsian.sandbox_url' => 'https://sandbox.parsian-bank.ir/channelServices/1.0']);
    config(['banks.parsian.token' => 'test-token']);
    config(['banks.parsian.use_sandbox' => true]);
});

it('fetches and stores balances for all Parsian Bank deposits', function () {
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

    $deposit1 = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $parsianBank->id,
        'number' => '85000005464007',
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $deposit2 = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $parsianBank->id,
        'number' => '85000005464008',
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $service = new BalanceFetchService(new BankAdapterFactory);
    $service->fetchAndStore();

    expect(Balance::count())->toBe(2);
    expect(Balance::where('deposit_id', $deposit1->id)->first())
        ->not->toBeNull()
        ->and(Balance::where('deposit_id', $deposit1->id)->first()->balance)->toBe(152216359360)
        ->and(Balance::where('deposit_id', $deposit1->id)->first()->status)->toBe('success');

    $deposit1->refresh();
    expect($deposit1->balance)->toBe(152216359360)
        ->and($deposit1->balance_last_synced_at)->not->toBeNull();
});

it('skips deposits that already have balance for today', function () {
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

    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $parsianBank->id,
        'number' => '85000005464007',
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    // Create existing balance for today
    Balance::create([
        'deposit_id' => $deposit->id,
        'fetched_at' => now(),
        'status' => 'success',
        'balance' => 100000000,
    ]);

    $service = new BalanceFetchService(new BankAdapterFactory);
    $service->fetchAndStore();

    // Should still have only one balance record
    expect(Balance::count())->toBe(1);
    expect(Balance::first()->balance)->toBe(100000000);
});

it('handles API errors gracefully and creates failed balance records', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/*' => Http::response([
            'error' => 'Invalid account number',
        ], 400),
    ]);

    $parsianBank = Bank::factory()->create([
        'en_name' => 'parsian',
        'name' => 'Parsian Bank',
    ]);

    $organ = Organ::factory()->create();

    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $parsianBank->id,
        'number' => '85000005464007',
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $service = new BalanceFetchService(new BankAdapterFactory);
    $service->fetchAndStore();

    expect(Balance::count())->toBe(1);
    expect(Balance::first()->status)->toBe('fail')
        ->and(Balance::first()->balance)->toBeNull();
});

it('does nothing when Parsian Bank is not found', function () {
    $service = new BalanceFetchService(new BankAdapterFactory);
    $service->fetchAndStore();

    expect(Balance::count())->toBe(0);
});

it('does nothing when no Parsian Bank deposits exist', function () {
    $parsianBank = Bank::factory()->create([
        'en_name' => 'parsian',
        'name' => 'Parsian Bank',
    ]);

    $service = new BalanceFetchService(new BankAdapterFactory);
    $service->fetchAndStore();

    expect(Balance::count())->toBe(0);
});

it('only processes Parsian Bank deposits', function () {
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

    $otherBank = Bank::factory()->create([
        'en_name' => 'mellat',
        'name' => 'Mellat Bank',
    ]);

    $organ = Organ::factory()->create();

    $parsianDeposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $parsianBank->id,
        'number' => '85000005464007',
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $otherDeposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $otherBank->id,
        'number' => '85000005464008',
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $service = new BalanceFetchService(new BankAdapterFactory);
    $service->fetchAndStore();

    expect(Balance::count())->toBe(1);
    expect(Balance::first()->deposit_id)->toBe($parsianDeposit->id);
});

it('fetches and stores balance for a specific deposit', function () {
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

    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $parsianBank->id,
        'number' => '85000005464007',
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $service = new BalanceFetchService(new BankAdapterFactory);
    $service->fetchAndStoreForDeposit($deposit);

    expect(Balance::count())->toBe(1);
    expect(Balance::first()->balance)->toBe(152216359360)
        ->and(Balance::first()->status)->toBe('success');

    $deposit->refresh();
    expect($deposit->balance)->toBe(152216359360);
});
