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
        $this->organSlug = $credentials['organSlug'];
        $this->apiEndpoint = $this->getApiUrl();
        $this->token = $this->getAccessToken();

        return $this;
    }


    protected function getApiUrl(): string
    {
        return config('banks.parsian.api_url');
    }

    protected function getOAuthTokenUrl(): string
    {
        return config('banks.parsian.oauth_token_url');
    }

    protected function getAccessToken(): string
    {
        $clientId = $this->getClientId();
        $clientSecret = $this->getClientSecret();

        Log::info('getAccessToken called', [
            'organ_slug' => $this->organSlug,
            'has_client_id' => ! empty($clientId),
            'has_client_secret' => ! empty($clientSecret),
        ]);

        if (! $clientId || ! $clientSecret) {
            throw new \Exception('Parsian Bank client credentials not configured');
        }

        if (! $this->organSlug) {
            throw new \Exception('Organ slug is required for Parsian Bank token caching. Each organ must have its own token.');
        }

        $cacheKey = "parsian_bank_token_{$this->organSlug}";

        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken) {
            Log::info('Using cached token from Parsian Bank', [
                'organ_slug' => $this->organSlug,
                'cache_key' => $cacheKey,
            ]);

            return $cachedToken;
        }

        try {
            $accountNumber = $this->credentials['accountNumber'] ?? $this->credentials['number'] ?? 'unknown';
            Log::info('Getting new token from Parsian Bank', [
                'organ_slug' => $this->organSlug,
                'account_number' => $accountNumber,
                'client_id' => $clientId,
                'client_secret' => $this->maskSecret($clientSecret),
                'url' => $this->getOAuthTokenUrl(),
            ]);
            $response = Http::timeout(10)
                ->withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post($this->getOAuthTokenUrl(), [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                $token = $tokenData['access_token'];
                $expiresIn = $tokenData['expires_in'] ?? 3600;

                // Cache token for slightly less than its expiration time to be safe
                $cacheDuration = max(1, $expiresIn - 60); // 60 seconds buffer

                Cache::put($cacheKey, $token, $cacheDuration);

                Log::info('Successfully retrieved and cached token from Parsian Bank', [
                    'organ_slug' => $this->organSlug,
                    'expires_in' => $expiresIn,
                    'cache_duration' => $cacheDuration,
                ]);

                return $token;
            } else {
                $statusCode = $response->status();
                $responseBody = $response->body();
                $responseData = $response->json();

                Log::error('Failed to get token from Parsian Bank', [
                    'status_code' => $statusCode,
                    'response' => $responseBody,
                ]);

                // If we got a response but no token, check for specific error messages
                if (isset($responseData['error'])) {
                    throw new \Exception("Parsian Bank authentication failed (HTTP {$statusCode}): {$responseData['error']}");
                }

                throw new \Exception("Parsian Bank authentication failed (HTTP {$statusCode}): Invalid response format");
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \Exception('Failed to authenticate with Parsian Bank - connection error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw $e;
        }
    }


    /**
     * Get client ID based on organ slug if available
     */
    protected function getClientId(): ?string
    {
        if ($this->organSlug) {
            $configKey = strtolower($this->organSlug) . '_client_id';
            $clientId = config("banks.parsian.{$configKey}");

            if ($clientId) {
                return $clientId;
            }
        }
        $defaultClientId = config('banks.parsian.client_id');
        return $defaultClientId;
    }

    /**
     * Get client secret based on organ slug if available
     */
    protected function getClientSecret(): ?string
    {
        if ($this->organSlug) {
            // Use config() to read from banks.php config file
            $configKey = strtolower($this->organSlug) . '_client_secret';
            $clientSecret = config("banks.parsian.{$configKey}");

            if ($clientSecret) {
                return $clientSecret;
            }
        }

        // Fallback to default
        $defaultClientSecret = config('banks.parsian.client_secret');
        return $defaultClientSecret;
    }


    /**
     * Mask secret for safe logging (shows only last 4 characters)
     */
    protected function maskSecret(?string $secret): string
    {
        if (! $secret) {
            return 'NOT SET';
        }

        $length = strlen($secret);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . substr($secret, -4);
    }

    /**
     * Generate curl command for authentication request
     */
    protected function generateCurlCommand(string $url, string $clientId, string $clientSecret): string
    {
        $basicAuth = base64_encode("{$clientId}:{$clientSecret}");

        $curlCommand = "curl -X POST '{$url}' \\\n";
        $curlCommand .= "  -H 'Authorization: Basic {$basicAuth}' \\\n";
        $curlCommand .= "  -H 'Content-Type: application/x-www-form-urlencoded' \\\n";
        $curlCommand .= "  -d 'grant_type=client_credentials' \\\n";
        $curlCommand .= "  -d 'client_id={$clientId}' \\\n";
        $curlCommand .= "  -d 'client_secret={$clientSecret}'";

        return $curlCommand;
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
     * Get full account balance information including today's deposit and withdraw amounts
     *
     * @return array{accountNumber: string, balance: int, todayDepositAmount: int, todayWithdrawAmount: int, currency: string}
     *
     * @throws \Exception
     */
    public function getBalance(): array
    {
        $accountNumber = $this->credentials['accountNumber'] ?? $this->credentials['number'] ?? throw new \Exception('Account number is required');

        $url = $this->apiEndpoint . '/' . self::SERVICE_GET_ACCOUNT_BALANCE;

        $requestBody = [
            'accountNumber' => $accountNumber,
        ];

        $requestHeaders = [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders($requestHeaders)
                ->post($url, $requestBody);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \Exception('Connection timeout or error when fetching balance from Parsian Bank: ' . $e->getMessage());
        }

        $data = $response->json();

        if (! $response->successful()) {
            throw new \Exception($response->body() ?? 'Unknown error, response has no body');
        }

        // Check for ChAccountNotFoundException or similar
        if (isset($data['exception']) || (isset($data['error']) && str_contains(strtolower($data['error']), 'account not found'))) {
            throw new \Exception('Account not found: ' . $accountNumber);
        }

        if (! isset($data['balance'])) {
            throw new \Exception('Invalid response from Parsian Bank');
        }

        return [
            'accountNumber' => $data['accountNumber'] ?? $accountNumber,
            'balance' => (int) $data['balance'],
            'todayDepositAmount' => (int) ($data['todayDepositAmount'] ?? 0),
            'todayWithdrawAmount' => (int) ($data['todayWithdrawAmount'] ?? 0),
            'currency' => $data['currency'] ?? 'IRR',
        ];
    }
}
