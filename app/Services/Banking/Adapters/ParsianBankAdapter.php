<?php

namespace App\Services\Banking\Adapters;

use App\Services\Banking\BankAdapterInterface;
use Illuminate\Support\Facades\Http;

class ParsianBankAdapter implements BankAdapterInterface
{
    protected array $credentials;
    protected string $apiEndpoint;
    public function setAccount(array $credentials): BankAdapterInterface
    {
        $this->credentials = $credentials;
        $this->apiEndpoint = config('banks.parsian.api_url');
        return $this;
    }

    public function getBalance(): float
    {
        $response = Http::withToken($this->apiEndpoint)->post($this->apiEndpoint, [
            'number' => $this->credentials['number'],
        ]);

        return (float) $response->json('data.current_balance');
    }
}
