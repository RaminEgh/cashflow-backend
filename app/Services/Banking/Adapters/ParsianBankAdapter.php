<?php

namespace App\Services\Banking\Adapters;

use App\Services\Banking\BankAdapterInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ParsianBankAdapter implements BankAdapterInterface
{
    protected array $credentials;

    protected string $apiEndpoint;

    protected string $token;

    public function setAccount(array $credentials): BankAdapterInterface
    {
        $this->credentials = $credentials;
        $this->apiEndpoint = $this->shouldUseSandbox() ? $this->getSandboxUrl() : $this->getApiUrl();
        $this->token = $this->getToken();

        return $this;
    }

    protected function getSandboxUrl(): string
    {
        return config('banks.parsian.sandbox_url');
    }

    protected function getApiUrl(): string
    {
        return config('banks.parsian.api_url');
    }

    protected function getToken(): string
    {
        // If token is explicitly set in config, use it
        $token = config('banks.parsian.token');
        if ($token) {
            return $token;
        }

        // Otherwise, get token using OAuth2 client credentials
        return $this->getAccessToken();
    }

    protected function getAccessToken(): string
    {
        $clientId = config('banks.parsian.client_id');
        $clientSecret = config('banks.parsian.client_secret');

        if (! $clientId || ! $clientSecret) {
            throw new \Exception('Parsian Bank client credentials not configured');
        }

        // Try sandbox first, then fallback to production
        $urls = [
            'sandbox' => 'https://sandbox.parsian-bank.ir/oauth2/token',
            'production' => 'https://openapi.parsian-bank.ir/oauth2/token',
        ];

        $authUrl = $this->shouldUseSandbox() ? $urls['sandbox'] : $urls['production'];
        $lastException = null;

        // Try primary URL first
        try {
            $response = Http::timeout(10)
                ->withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post($authUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->successful() && isset($response->json()['access_token'])) {
                return $response->json()['access_token'];
            }

            Log::warning('Parsian Bank authentication failed with primary URL', [
                'url' => $authUrl,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        } catch (\Exception $e) {
            $lastException = $e;
            Log::warning('Parsian Bank primary URL failed', [
                'url' => $authUrl,
                'error' => $e->getMessage(),
            ]);
        }

        // Try fallback URL if primary failed
        $fallbackUrl = $this->shouldUseSandbox() ? $urls['production'] : $urls['sandbox'];

        try {
            $response = Http::timeout(10)
                ->withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post($fallbackUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->successful() && isset($response->json()['access_token'])) {
                Log::info('Parsian Bank authentication succeeded with fallback URL', [
                    'fallback_url' => $fallbackUrl,
                ]);

                return $response->json()['access_token'];
            }

            Log::error('Failed to authenticate with Parsian Bank (both URLs)', [
                'primary_url' => $authUrl,
                'fallback_url' => $fallbackUrl,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new \Exception('Failed to authenticate with Parsian Bank');
        } catch (\Exception $e) {
            Log::error('Failed to authenticate with Parsian Bank', [
                'primary_url' => $authUrl,
                'fallback_url' => $fallbackUrl,
                'primary_error' => $lastException?->getMessage(),
                'fallback_error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to authenticate with Parsian Bank: ' . $e->getMessage());
        }
    }

    protected function shouldUseSandbox(): bool
    {
        $explicit = config('banks.parsian.use_sandbox');
        if ($explicit !== null) {
            return (bool) $explicit;
        }

        return config('app.env') !== 'production';
    }

    /**
     * @throws \Exception
     */
    public function getBalance(): float
    {
        $accountNumber = $this->credentials['accountNumber'] ?? throw new \Exception('Account number is required');

        $url = $this->apiEndpoint . '/getAccountBalance';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'accountNumber' => $accountNumber,
        ]);

        if (! $response->successful()) {
            Log::error('Failed to fetch balance from Parsian Bank', [
                'accountNumber' => $accountNumber,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new \Exception('Failed to fetch balance from Parsian Bank');
        }

        $data = $response->json();

        if (isset($data['error']) || ! isset($data['balance'])) {
            Log::error('Parsian Bank returned an error', [
                'accountNumber' => $accountNumber,
                'data' => $data,
            ]);

            throw new \Exception($data['error'] ?? 'Unknown error from Parsian Bank');
        }

        return (float) $data['balance'];
    }

    /**
     * Get full account balance information including today's deposit and withdraw amounts
     *
     * @return array{accountNumber: string, balance: float, todayDepositAmount: float, todayWithdrawAmount: float, currency: string}
     *
     * @throws \Exception
     */
    public function getAccountBalance(): array
    {
        $accountNumber = $this->credentials['accountNumber'] ?? throw new \Exception('Account number is required');

        $url = $this->apiEndpoint . '/getAccountBalance';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'accountNumber' => $accountNumber,
        ]);

        if (! $response->successful()) {
            Log::error('Failed to fetch balance from Parsian Bank', [
                'accountNumber' => $accountNumber,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new \Exception('Failed to fetch balance from Parsian Bank');
        }

        $data = $response->json();

        // Check for ChAccountNotFoundException or similar
        if (isset($data['exception']) || (isset($data['error']) && str_contains(strtolower($data['error']), 'account not found'))) {
            throw new \Exception('Account not found: ' . $accountNumber);
        }

        if (! isset($data['balance'])) {
            Log::error('Parsian Bank returned an invalid response', [
                'accountNumber' => $accountNumber,
                'data' => $data,
            ]);

            throw new \Exception('Invalid response from Parsian Bank');
        }

        return [
            'accountNumber' => $data['accountNumber'],
            'balance' => (float) $data['balance'],
            'todayDepositAmount' => (float) ($data['todayDepositAmount'] ?? 0),
            'todayWithdrawAmount' => (float) ($data['todayWithdrawAmount'] ?? 0),
            'currency' => $data['currency'] ?? 'IRR',
        ];
    }
}
