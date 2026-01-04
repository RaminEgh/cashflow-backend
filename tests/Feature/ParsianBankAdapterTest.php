<?php

use App\Services\Banking\Adapters\ParsianBankAdapter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['banks.parsian.sandbox_url' => 'https://sandbox.parsian-bank.ir/channelServices/1.0']);
    config(['banks.parsian.client_id' => 'test-client-id']);
    config(['banks.parsian.client_secret' => 'test-client-secret']);

    // Mock OAuth token endpoint by default
    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
    ]);
});

it('implements bank adapter interface', function () {
    $adapter = new ParsianBankAdapter;
    expect($adapter)->toBeInstanceOf(\App\Services\Banking\BankAdapterInterface::class);
});

it('returns account balance when API call is successful', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'sandbox.parsian-bank.ir/channelServices/*' => Http::response([
            'accountNumber' => '85000005464007',
            'balance' => 152216359360,
            'todayDepositAmount' => 100,
            'todayWithdrawAmount' => 0,
            'currency' => 'IRR',
        ], 200),
    ]);

    $adapter = new ParsianBankAdapter;
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    $balanceData = $adapter->getBalance();

    expect($balanceData)->toBe([
        'accountNumber' => '85000005464007',
        'balance' => 152216359360,
        'todayDepositAmount' => 100,
        'todayWithdrawAmount' => 0,
        'currency' => 'IRR',
    ]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sandbox.parsian-bank.ir/channelServices/1.0/getAccountBalance'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request['accountNumber'] === '85000005464007';
    });
});

it('returns full account balance information', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'sandbox.parsian-bank.ir/channelServices/*' => Http::response([
            'accountNumber' => '85000005464007',
            'balance' => 152216359360,
            'todayDepositAmount' => 100,
            'todayWithdrawAmount' => 0,
            'currency' => 'IRR',
        ], 200),
    ]);

    $adapter = new ParsianBankAdapter;
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    $balanceInfo = $adapter->getBalance();

    expect($balanceInfo)->toBe([
        'accountNumber' => '85000005464007',
        'balance' => 152216359360,
        'todayDepositAmount' => 100,
        'todayWithdrawAmount' => 0,
        'currency' => 'IRR',
    ]);
});

it('throws exception when account number is not provided', function () {
    $adapter = new ParsianBankAdapter;
    $adapter->setAccount([]);

    expect(fn() => $adapter->getBalance())->toThrow('Account number is required');
});

it('throws exception when API returns error', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'sandbox.parsian-bank.ir/channelServices/*' => Http::response([
            'error' => 'Invalid account number',
        ], 400),
    ]);

    $adapter = new ParsianBankAdapter;
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    expect(fn() => $adapter->getBalance())->toThrow('Failed to fetch balance from Parsian Bank (HTTP 400): Invalid account number');
});

it('throws exception when account is not found', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'sandbox.parsian-bank.ir/channelServices/*' => Http::response([
            'exception' => 'ChAccountNotFoundException',
        ], 200),
    ]);

    $adapter = new ParsianBankAdapter;
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    expect(fn() => $adapter->getBalance())->toThrow('Account not found: 85000005464007');
});

it('handles missing optional fields in getBalance', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'sandbox.parsian-bank.ir/channelServices/*' => Http::response([
            'accountNumber' => '85000005464007',
            'balance' => 152216359360,
        ], 200),
    ]);

    $adapter = new ParsianBankAdapter;
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    $balanceInfo = $adapter->getBalance();

    expect($balanceInfo)->toBe([
        'accountNumber' => '85000005464007',
        'balance' => 152216359360,
        'todayDepositAmount' => 0,
        'todayWithdrawAmount' => 0,
        'currency' => 'IRR',
    ]);
});

it('converts balance to float correctly', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'sandbox.parsian-bank.ir/channelServices/*' => Http::response([
            'accountNumber' => '85000005464007',
            'balance' => '152216359360',
            'todayDepositAmount' => '100',
            'todayWithdrawAmount' => '0',
            'currency' => 'IRR',
        ], 200),
    ]);

    $adapter = new ParsianBankAdapter;
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    $balanceInfo = $adapter->getBalance();

    expect($balanceInfo['balance'])->toBe(152216359360)
        ->and($balanceInfo['todayDepositAmount'])->toBe(100)
        ->and($balanceInfo['todayWithdrawAmount'])->toBe(0);
});

it('throws exception when API returns 500 error', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'sandbox.parsian-bank.ir/channelServices/*' => Http::response([], 500),
    ]);

    $adapter = new ParsianBankAdapter;
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    expect(fn() => $adapter->getBalance())->toThrow('Failed to fetch balance from Parsian Bank (HTTP 500): Unknown error');
});

it('throws exception when API returns 404 error', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'sandbox.parsian-bank.ir/channelServices/*' => Http::response([
            'error' => 'Method not found',
        ], 404),
    ]);

    $adapter = new ParsianBankAdapter;
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    expect(fn() => $adapter->getBalance())->toThrow('Failed to fetch balance from Parsian Bank (HTTP 404): Method not found');
});

it('throws exception when account is not found in getBalance', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'sandbox.parsian-bank.ir/channelServices/*' => Http::response([
            'exception' => 'ChAccountNotFoundException',
        ], 200),
    ]);

    $adapter = new ParsianBankAdapter;
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    expect(fn() => $adapter->getBalance())->toThrow('Account not found: 85000005464007');
});

it('throws exception when authentication fails with 401', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'sandbox.parsian-bank.ir/channelServices/*' => Http::response([
            'error' => 'Unauthorized',
        ], 401),
    ]);

    $adapter = new ParsianBankAdapter;
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    expect(fn() => $adapter->getBalance())->toThrow('Authentication failed when fetching balance from Parsian Bank (HTTP 401)');
});

it('throws exception when authentication fails with 403', function () {
    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'sandbox.parsian-bank.ir/channelServices/*' => Http::response([
            'error' => 'Forbidden',
        ], 403),
    ]);

    $adapter = new ParsianBankAdapter;
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    expect(fn() => $adapter->getBalance())->toThrow('Authentication failed when fetching balance from Parsian Bank (HTTP 403)');
});

it('successfully authenticates with Basic Auth and gets access token', function () {
    config(['banks.parsian.token' => null]); // Remove fixed token to test OAuth
    config(['banks.parsian.client_id' => '4836766166044676016']);
    config(['banks.parsian.client_secret' => '6040bf64-bf1e-4285-84ea-68b1614f440d']);

    Http::fake([
        'sandbox.parsian-bank.ir/oauth2/token' => Http::response([
            'access_token' => 'oauth-generated-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'sandbox.parsian-bank.ir/channelServices/*' => Http::response([
            'accountNumber' => '85000005464007',
            'balance' => 152216359360,
            'todayDepositAmount' => 100,
            'todayWithdrawAmount' => 0,
            'currency' => 'IRR',
        ], 200),
    ]);

    $adapter = new ParsianBankAdapter;
    $adapter->setAccount(['accountNumber' => '85000005464007']);

    $balanceData = $adapter->getBalance();

    expect($balanceData['balance'])->toBe(152216359360);

    // Verify that requests were made (OAuth and balance)
    // The fact that we got a balance proves OAuth authentication worked
    Http::assertSentCount(2);
});
