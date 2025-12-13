<?php

namespace App\Services\Rahkaran;

use App\Enums\DepositType;
use App\Helpers\Helper;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\Organ;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DepositFetchService
{
    public function fetchAndStore()
    {

        $organs = Organ::all();
        $rahkaranApi = config('services.rahkaran.base_endpoint');

        if (! $rahkaranApi) {
            throw new \Exception('RAHKARAN_BASE_ENDPOINT is not set in .env file');
        }

        // Ensure URL doesn't have trailing slash
        $rahkaranApi = rtrim($rahkaranApi, '/');

        foreach ($organs as $organ) {
            try {
                $api = "$rahkaranApi/$organ->slug";
                Log::info("Fetching deposits for organ: {$api}");
                $response = Http::timeout(30)->get($api);
                if (! $response->successful()) {
                    Log::error("Failed to fetch deposits for organ: {$organ->slug}");

                    continue;
                }
                $deposits = $response->json();
                foreach ($deposits as $data) {
                    $bankName = trim(str_replace('بانک', '', $data['BankTitle']));
                    $bank = Bank::whereName($bankName)->first();
                    if (! $bank) {
                        $bank = Bank::Create([
                            'name' => $bankName,
                            'en_name' => Helper::persianToLatin($bankName),
                            'created_by' => 1,
                            'updated_by' => 1,
                            'logo' => null,
                        ]);
                    }

                    $deposit = Deposit::whereNumber($data['AccountNumber'])->first();
                    if (! $deposit) {
                        Deposit::Create([
                            'organ_id' => $organ->id,
                            'bank_id' => $bank->id,
                            'branch_code' => $data['BranchCode'],
                            'branch_name' => $data['BankBranch'],
                            'number' => $data['AccountNumber'],
                            'type' => DepositType::Current,
                            'currency' => 'IRR',
                            'created_by' => 1,
                            'updated_by' => 1,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error("Error fetching/storing deposits for organ {$organ->slug}: " . $e->getMessage());

                continue;
            }
        }
    }
}
