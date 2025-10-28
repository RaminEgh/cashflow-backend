<?php

use App\Services\Banking\Adapters\ParsianBankAdapter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['banks.parsian.sandbox_url' => 'https://sandbox.parsian-bank.ir/channelServices/1.0']);
    config(['banks.parsian.token' => 'test-token']);
});

it('implements bank adapter interface', function () {
    $adapter = new ParsianBankAdapter();
    expect($adapter)->toBeInstanceOf(\App\Services\Banking\BankAdapterInterface::class);
});

it('returns account balance when API call is successful', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/*' => Http::response([
            'accountNumber' => '85000005464007',
            'balance' => 152216359360,
            'todayDepositAmount' => 100,
            'todayWithdrawAmount' => 0,
            'currency' => 'IRR',
        ], 200),
    ]);

    $adapter = new ParsianBankAdapter();
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    $balance = $adapter->getBalance();

    expect($balance)->toBe(152216359360.0);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sandbox.parsian-bank.ir/channelServices/1.0/getAccountBalance'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request['accountNumber'] === '85000005464007';
    });
});

it('returns full account balance information', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/*' => Http::response([
            'accountNumber' => '85000005464007',
            'balance' => 152216359360,
            'todayDepositAmount' => 100,
            'todayWithdrawAmount' => 0,
            'currency' => 'IRR',
        ], 200),
    ]);

    $adapter = new ParsianBankAdapter();
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    $balanceInfo = $adapter->getAccountBalance();

    expect($balanceInfo)->toBe([
        'accountNumber' => '85000005464007',
        'balance' => 152216359360.0,
        'todayDepositAmount' => 100.0,
        'todayWithdrawAmount' => 0.0,
        'currency' => 'IRR',
    ]);
});

it('throws exception when account number is not provided', function () {
    $adapter = new ParsianBankAdapter();
    $adapter->setAccount([]);

    expect(fn() => $adapter->getBalance())->toThrow('Account number is required');
});

it('throws exception when API returns error', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/*' => Http::response([
            'error' => 'Invalid account number',
        ], 400),
    ]);

    $adapter = new ParsianBankAdapter();
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    expect(fn() => $adapter->getBalance())->toThrow('Failed to fetch balance from Parsian Bank');
});

it('throws exception when account is not found', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/*' => Http::response([
            'exception' => 'ChAccountNotFoundException',
        ], 200),
    ]);

    $adapter = new ParsianBankAdapter();
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    expect(fn() => $adapter->getAccountBalance())->toThrow('Account not found: 85000005464007');
});

it('handles missing optional fields in getAccountBalance', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/*' => Http::response([
            'accountNumber' => '85000005464007',
            'balance' => 152216359360,
        ], 200),
    ]);

    $adapter = new ParsianBankAdapter();
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    $balanceInfo = $adapter->getAccountBalance();

    expect($balanceInfo)->toBe([
        'accountNumber' => '85000005464007',
        'balance' => 152216359360.0,
        'todayDepositAmount' => 0.0,
        'todayWithdrawAmount' => 0.0,
        'currency' => 'IRR',
    ]);
});

it('converts balance to float correctly', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/*' => Http::response([
            'accountNumber' => '85000005464007',
            'balance' => '152216359360',
            'todayDepositAmount' => '100',
            'todayWithdrawAmount' => '0',
            'currency' => 'IRR',
        ], 200),
    ]);

    $adapter = new ParsianBankAdapter();
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    $balanceInfo = $adapter->getAccountBalance();

    expect($balanceInfo['balance'])->toBe(152216359360.0)
        ->and($balanceInfo['todayDepositAmount'])->toBe(100.0)
        ->and($balanceInfo['todayWithdrawAmount'])->toBe(0.0);
});

it('throws exception when API returns 500 error', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/*' => Http::response([], 500),
    ]);

    $adapter = new ParsianBankAdapter();
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    expect(fn() => $adapter->getBalance())->toThrow('Failed to fetch balance from Parsian Bank');
});

it('throws exception when API returns 404 error', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/*' => Http::response([
            'error' => 'Method not found',
        ], 404),
    ]);

    $adapter = new ParsianBankAdapter();
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    expect(fn() => $adapter->getBalance())->toThrow('Failed to fetch balance from Parsian Bank');
});
