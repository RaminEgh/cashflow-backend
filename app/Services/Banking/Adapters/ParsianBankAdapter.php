<?php

namespace App\Services\Banking\Adapters;

use App\Services\Banking\BankAdapterInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ParsianBankAdapter implements BankAdapterInterface
{
    protected array $credentials;

    protected string $apiEndpoint;

    protected string $token;

    protected ?string $organSlug = null;

    protected const SERVICE_GET_ACCOUNT_BALANCE = 'getAccountBalance';

    public function setAccount(array $credentials): BankAdapterInterface
    {
        $this->credentials = $credentials;
        $this->organSlug = $credentials['organSlug'] ?? null;
        $this->apiEndpoint = $this->shouldUseSandbox() ? $this->getSandboxUrl() : $this->getApiUrl();
        $this->token = $this->getAccessToken();

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

    protected function getAccessToken(): string
    {
        $clientId = $this->getClientId();
        $clientSecret = $this->getClientSecret();

        if (! $clientId || ! $clientSecret) {
            throw new \Exception('Parsian Bank client credentials not configured');
        }

        // Try to get token from cache first
        $environment = $this->shouldUseSandbox() ? 'sandbox' : 'production';

        // Build cache key based on organ slug if available, otherwise use client ID
        if ($this->organSlug) {
            $cacheKey = "parsian_bank_token_{$environment}_{$this->organSlug}";
        } else {
            $cacheKey = "parsian_bank_token_{$environment}_{$clientId}";
        }

        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken) {
            Log::debug('Using cached Parsian Bank token', [
                'environment' => $environment,
                'organSlug' => $this->organSlug,
                'cache_key' => $cacheKey,
                'token_preview' => substr($cachedToken, 0, 20) . '...',
            ]);

            return $cachedToken;
        }

        // Try sandbox first, then fallback to production
        $urls = [
            'sandbox' => config('banks.parsian.oauth_sandbox_token_url', 'https://sandbox.parsian-bank.ir/oauth2/token'),
            'production' => config('banks.parsian.oauth_token_url', 'https://oauth2.parsian-bank.ir/oauth2/token'),
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
                $tokenData = $response->json();
                $token = $tokenData['access_token'];
                $expiresIn = $tokenData['expires_in'] ?? 3600;

                // Cache token for slightly less than its expiration time to be safe
                $cacheDuration = max(1, $expiresIn - 60); // 60 seconds buffer

                Cache::put($cacheKey, $token, $cacheDuration);

                Log::info('Successfully obtained and cached Parsian Bank token', [
                    'environment' => $environment,
                    'organSlug' => $this->organSlug,
                    'cache_key' => $cacheKey,
                    'expires_in' => $expiresIn,
                    'cache_duration' => $cacheDuration,
                ]);

                return $token;
            }

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::warning('Parsian Bank authentication failed with primary URL', [
                'url' => $authUrl,
                'status' => $statusCode,
                'response' => $responseBody,
                'data' => $responseData,
            ]);

            // If we got a response but no token, check for specific error messages
            if (isset($responseData['error'])) {
                $lastException = new \Exception("Parsian Bank authentication failed (HTTP {$statusCode}): {$responseData['error']}");
            } else {
                $lastException = new \Exception("Parsian Bank authentication failed (HTTP {$statusCode}): Invalid response format");
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $lastException = $e;
            Log::warning('Parsian Bank primary URL connection failed', [
                'url' => $authUrl,
                'error' => $e->getMessage(),
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
                $tokenData = $response->json();
                $token = $tokenData['access_token'];
                $expiresIn = $tokenData['expires_in'] ?? 3600;

                // Cache token for slightly less than its expiration time to be safe
                $cacheDuration = max(1, $expiresIn - 60); // 60 seconds buffer

                Cache::put($cacheKey, $token, $cacheDuration);

                Log::info('Parsian Bank authentication succeeded with fallback URL and cached', [
                    'fallback_url' => $fallbackUrl,
                    'organSlug' => $this->organSlug,
                    'cache_key' => $cacheKey,
                    'expires_in' => $expiresIn,
                    'cache_duration' => $cacheDuration,
                ]);

                return $token;
            }

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::error('Failed to authenticate with Parsian Bank (both URLs)', [
                'primary_url' => $authUrl,
                'fallback_url' => $fallbackUrl,
                'status' => $statusCode,
                'response' => $responseBody,
                'data' => $responseData,
                'primary_error' => $lastException?->getMessage(),
            ]);

            // Include error message from response if available
            $errorMessage = $responseData['error'] ?? $responseData['message'] ?? 'Unknown error';
            throw new \Exception("Failed to authenticate with Parsian Bank (HTTP {$statusCode}): {$errorMessage}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Failed to authenticate with Parsian Bank - connection error', [
                'primary_url' => $authUrl,
                'fallback_url' => $fallbackUrl,
                'primary_error' => $lastException?->getMessage(),
                'fallback_error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to authenticate with Parsian Bank - connection error: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to authenticate with Parsian Bank', [
                'primary_url' => $authUrl,
                'fallback_url' => $fallbackUrl,
                'primary_error' => $lastException?->getMessage(),
                'fallback_error' => $e->getMessage(),
            ]);

            // If the exception already has a detailed message, use it; otherwise create a new one
            if (str_contains($e->getMessage(), 'Failed to authenticate')) {
                throw $e;
            }

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
     * Get client ID based on organ slug if available
     */
    protected function getClientId(): ?string
    {
        if ($this->organSlug) {
            $envKey = $this->buildOrganEnvKey('PARSIAN_CLIENT_ID');
            $clientId = env($envKey);

            if ($clientId) {
                return $clientId;
            }
        }

        // Fallback to default
        return config('banks.parsian.client_id');
    }

    /**
     * Get client secret based on organ slug if available
     */
    protected function getClientSecret(): ?string
    {
        if ($this->organSlug) {
            $envKey = $this->buildOrganEnvKey('PARSIAN_CLIENT_SECRET');
            $clientSecret = env($envKey);

            if ($clientSecret) {
                return $clientSecret;
            }
        }

        // Fallback to default
        return config('banks.parsian.client_secret');
    }

    /**
     * Build environment variable key from organ slug
     * Converts slug to uppercase and replaces hyphens with underscores
     */
    protected function buildOrganEnvKey(string $suffix): string
    {
        $slug = strtoupper($this->organSlug);
        $slug = str_replace('-', '_', $slug);

        return "{$slug}_{$suffix}";
    }

    /**
     * Format error message for better readability
     */
    protected function formatErrorMessage(string $errorMessage, string $persianMessage, string $accountNumber, int $statusCode, ?int $errorCode): string
    {
        $parts = [];

        // Add status code
        $parts[] = "خطای HTTP {$statusCode}";

        // Add error code if available
        if ($errorCode !== null) {
            $parts[] = "کد خطا: {$errorCode}";
        }

        // Add account number that was sent
        $parts[] = "شماره حساب ارسالی: {$accountNumber}";

        // Add Persian message (usually more descriptive)
        if ($persianMessage && $persianMessage !== $errorMessage) {
            $parts[] = "پیام خطا: {$persianMessage}";
        } else {
            $parts[] = "پیام خطا: {$errorMessage}";
        }

        // Check if error mentions account numbers that don't match pattern
        if (str_contains($persianMessage, 'با الگو مطابقت ندارد') || str_contains($errorMessage, 'does not match')) {
            $parts[] = "\n⚠️ توجه: شماره حساب ارسالی با الگوی مورد انتظار API مطابقت ندارد.";
            $parts[] = "لطفاً شماره حساب را بررسی کنید یا با پشتیبانی بانک پارسیان تماس بگیرید.";
        }

        return implode("\n", $parts);
    }

    /**
     * @throws \Exception
     */
    public function getBalance(): int
    {
        $accountNumber = $this->credentials['accountNumber'] ?? $this->credentials['number'] ?? throw new \Exception('Account number is required');

        // Service name is camelCase, no URL encoding needed
        $url = $this->apiEndpoint . '/' . self::SERVICE_GET_ACCOUNT_BALANCE;

        Log::info('Fetching balance from Parsian Bank', [
            'accountNumber' => $accountNumber,
            'url' => $url,
            'apiEndpoint' => $this->apiEndpoint,
            'serviceName' => self::SERVICE_GET_ACCOUNT_BALANCE,
        ]);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ])->post($url, [
                    'accountNumber' => $accountNumber,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Connection timeout or error when fetching balance from Parsian Bank', [
                'accountNumber' => $accountNumber,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Connection timeout or error when fetching balance from Parsian Bank: ' . $e->getMessage());
        }

        if (! $response->successful()) {
            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            // Extract error message with better formatting
            $errorMessage = $responseData['message'] ?? $responseData['error'] ?? $responseData['exceptionDetail'] ?? 'Unknown error';
            $errorCode = $responseData['code'] ?? $responseData['errorCode'] ?? null;
            $persianMessage = $responseData['description']['fa_IR'] ?? $errorMessage;

            Log::error('Failed to fetch balance from Parsian Bank', [
                'accountNumber' => $accountNumber,
                'status' => $statusCode,
                'errorCode' => $errorCode,
                'errorMessage' => $errorMessage,
                'persianMessage' => $persianMessage,
                'response' => $responseBody,
                'data' => $responseData,
                'url' => $url,
            ]);

            // Handle authentication errors
            if ($statusCode === 401 || $statusCode === 403) {
                throw new \Exception("خطای احراز هویت: توکن منقضی شده یا نامعتبر است (HTTP {$statusCode})");
            }

            // Handle account not found errors
            if (isset($responseData['exception']) || (isset($responseData['error']) && str_contains(strtolower($responseData['error']), 'account not found'))) {
                throw new \Exception("حساب بانکی پیدا نشد: {$accountNumber}");
            }

            // Format error message for better readability
            $formattedError = $this->formatErrorMessage($errorMessage, $persianMessage, $accountNumber, $statusCode, $errorCode);
            throw new \Exception($formattedError);
        }

        $data = $response->json();

        // Check for ChAccountNotFoundException or similar
        if (isset($data['exception']) || (isset($data['error']) && str_contains(strtolower($data['error']), 'account not found'))) {
            Log::error('Parsian Bank account not found', [
                'accountNumber' => $accountNumber,
                'url' => $url,
                'data' => $data,
            ]);

            throw new \Exception("Account not found: {$accountNumber}");
        }

        if (isset($data['error']) || ! isset($data['balance'])) {
            Log::error('Parsian Bank returned an error or missing balance field', [
                'accountNumber' => $accountNumber,
                'url' => $url,
                'data' => $data,
                'hasError' => isset($data['error']),
                'hasBalance' => isset($data['balance']),
                'responseBody' => $response->body(),
            ]);

            throw new \Exception($data['error'] ?? $data['message'] ?? 'Unknown error from Parsian Bank - response does not contain balance field');
        }

        Log::info('Successfully fetched balance from Parsian Bank', [
            'accountNumber' => $accountNumber,
            'balance' => $data['balance'],
        ]);

        return (int) $data['balance'];
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
        $accountNumber = $this->credentials['accountNumber'] ?? $this->credentials['number'] ?? throw new \Exception('Account number is required');

        $url = $this->apiEndpoint . '/' . self::SERVICE_GET_ACCOUNT_BALANCE;

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
            'balance' => (int) $data['balance'],
            'todayDepositAmount' => (int) ($data['todayDepositAmount'] ?? 0),
            'todayWithdrawAmount' => (int) ($data['todayWithdrawAmount'] ?? 0),
            'currency' => $data['currency'] ?? 'IRR',
        ];
    }
}
