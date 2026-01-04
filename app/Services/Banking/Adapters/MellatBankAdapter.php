<?php

namespace App\Services\Banking\Adapters;

use App\Services\Banking\BankAdapterInterface;
use Illuminate\Support\Facades\Http;

class MellatBankAdapter implements BankAdapterInterface
{
    protected array $credentials;

    protected string $apiEndpoint;

    public function setAccount(array $credentials): BankAdapterInterface
    {
        $this->credentials = $credentials;
        $this->apiEndpoint = config('banks.mellat.api_url');

        return $this;
    }

    public function getBalance(): array
    {
        $accountNumber = $this->credentials['accountNumber'] ?? $this->credentials['number'] ?? '';

        $response = Http::withToken($this->apiEndpoint)->post($this->apiEndpoint, [
            'number' => $this->credentials['number'],
        ]);

        $balance = (int) $response->json('data.current_balance');

        return [
            'accountNumber' => $accountNumber,
            'balance' => $balance,
            'todayDepositAmount' => 0,
            'todayWithdrawAmount' => 0,
            'currency' => 'IRR',
        ];
    }
}
