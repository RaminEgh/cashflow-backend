<?php

use App\Enums\BalanceStatus;
use App\Jobs\FetchBankAccountBalance;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\Organ;
use App\Services\Banking\BankAdapterFactory;
use App\Services\Banking\BankAdapterInterface;
use Illuminate\Support\Facades\Http;

it('skips bank adapter when deposit has no banking api access and still fetches rahkaran balance', function () {
    config()->set('services.rahkaran.base_endpoint', 'http://rahkaran.test/cashflow/api');

    $organ = Organ::factory()->create(['slug' => 'org-1']);
    $bank = Bank::factory()->create(['slug' => 'mellat']);
    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'number' => '1234567890',
        'has_access_banking_api' => false,
        'balance' => null,
        'rahkaran_balance' => null,
    ]);

    Http::fake([
        'http://rahkaran.test/cashflow/api/org-1/1234567890' => Http::response([
            'balance' => 777,
            'job_Date' => '2025-12-17 10:00:00',
        ], 200),
    ]);

    $bankFactory = \Mockery::mock(BankAdapterFactory::class);
    $bankFactory->shouldNotReceive('make');

    (new FetchBankAccountBalance($deposit))->handle($bankFactory);

    $deposit->refresh();

    expect($deposit->rahkaran_balance)->toBe(777)
        ->and($deposit->balance)->toBeNull();

    $balanceRow = $deposit->balances()->latest('id')->first();

    expect($balanceRow)->not->toBeNull()
        ->and($balanceRow->status)->toBe(BalanceStatus::Fail)
        ->and($balanceRow->rahkaran_status)->toBe(BalanceStatus::Success)
        ->and($balanceRow->rahkaran_balance)->toBe(777);
});

it('fetches bank balance via adapter when deposit has banking api access even if rahkaran fails', function () {
    config()->set('services.rahkaran.base_endpoint', 'http://rahkaran.test/cashflow/api');

    $organ = Organ::factory()->create(['slug' => 'org-1']);
    $bank = Bank::factory()->create(['slug' => 'mellat']);
    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'number' => '1234567890',
        'has_access_banking_api' => true,
        'balance' => null,
        'rahkaran_balance' => null,
    ]);

    Http::fake([
        'http://rahkaran.test/cashflow/api/org-1/1234567890' => Http::response([], 500),
    ]);

    $adapter = \Mockery::mock(BankAdapterInterface::class);
    $adapter->shouldReceive('setAccount')
        ->once()
        ->with(['accountNumber' => '1234567890'])
        ->andReturnSelf();
    $adapter->shouldReceive('getBalance')
        ->once()
        ->andReturn(12345);

    $bankFactory = \Mockery::mock(BankAdapterFactory::class);
    $bankFactory->shouldReceive('make')
        ->once()
        ->with('mellat')
        ->andReturn($adapter);

    (new FetchBankAccountBalance($deposit))->handle($bankFactory);

    $deposit->refresh();

    expect($deposit->balance)->toBe(12345);

    $balanceRow = $deposit->balances()->latest('id')->first();

    expect($balanceRow)->not->toBeNull()
        ->and($balanceRow->status)->toBe(BalanceStatus::Success)
        ->and($balanceRow->balance)->toBe(12345)
        ->and($balanceRow->rahkaran_status)->toBe(BalanceStatus::Fail);
});
