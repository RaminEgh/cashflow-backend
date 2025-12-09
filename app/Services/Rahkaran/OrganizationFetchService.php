<?php

namespace App\Services\Rahkaran;

use App\Models\Organ;
use Illuminate\Support\Facades\Http;

class OrganizationFetchService
{
    public function fetchAndStore()
    {
        $rahkaranApi = config('services.rahkaran.base_endpoint');

        if (! $rahkaranApi) {
            throw new \Exception('RAHKARAN_BASE_ENDPOINT is not set in .env file');
        }

        // Ensure URL doesn't have trailing slash
        $rahkaranApi = rtrim($rahkaranApi, '/');
        $url = "$rahkaranApi/companies";

        $response = Http::timeout(30)->get($url);
        if (! $response->successful()) {
            throw new \Exception('Failed to fetch organizations');
        }

        $organs = $response->json();

        foreach ($organs as $data) {
            $organ = Organ::where('en_name', '=', $data['En_CompanyName'])->first();
            if (! $organ) {
                Organ::Create([
                    'name' => $data['CompanyName'],
                    'en_name' => $data['En_CompanyName'],
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);
            }
        }
    }
}
