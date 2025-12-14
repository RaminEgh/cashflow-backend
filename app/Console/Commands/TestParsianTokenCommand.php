<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestParsianTokenCommand extends Command
{
    protected $signature = 'parsian:test-token';

    protected $description = 'Test Parsian Bank OAuth token generation';

    public function handle(): int
    {
        $this->info('Testing Parsian Bank OAuth token generation...');

        $clientId = config('banks.parsian.client_id');
        $clientSecret = config('banks.parsian.client_secret');

        if (! $clientId || ! $clientSecret) {
            $this->error('Parsian Bank client credentials not configured in .env');
            $this->info('Please set PARSIAN_CLIENT_ID and PARSIAN_CLIENT_SECRET');

            return 1;
        }

        $this->info("Client ID: {$clientId}");
        $this->info('Client Secret: ' . substr($clientSecret, 0, 10) . '...');

        // Test sandbox URL
        $sandboxUrl = config('banks.parsian.oauth_sandbox_token_url', 'https://sandbox.parsian-bank.ir/oauth2/token');
        $this->info("Testing Sandbox URL: {$sandboxUrl}");

        try {
            $response = Http::timeout(10)
                ->withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post($sandboxUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['access_token'])) {
                    $this->info('✓ Successfully obtained token from Sandbox!');
                    $this->info('Token: ' . substr($data['access_token'], 0, 50) . '...');
                    $this->info('Expires in: ' . $data['expires_in'] . ' seconds');
                    $this->info('Token type: ' . $data['token_type']);

                    return 0;
                }
            }

            $this->warn('Failed to get token from Sandbox');
            $this->info('Status: ' . $response->status());
            $this->info('Response: ' . $response->body());
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
        }

        // Test production URL
        $productionUrl = config('banks.parsian.oauth_token_url', 'https://oauth2.parsian-bank.ir/oauth2/token');
        $this->info("\nTesting Production URL: {$productionUrl}");

        try {
            $response = Http::timeout(10)
                ->withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post($productionUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['access_token'])) {
                    $this->info('✓ Successfully obtained token from Production!');
                    $this->info('Token: ' . substr($data['access_token'], 0, 50) . '...');
                    $this->info('Expires in: ' . $data['expires_in'] . ' seconds');
                    $this->info('Token type: ' . $data['token_type']);

                    return 0;
                }
            }

            $this->error('Failed to get token from Production');
            $this->info('Status: ' . $response->status());
            $this->info('Response: ' . $response->body());

            return 1;
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());

            return 1;
        }
    }
}
