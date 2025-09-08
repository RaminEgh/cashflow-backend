<?php

namespace App\Services\Rahkaran;

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
        $rahkaranApi = env("RAHKARAN_BASE_ENDPOINT");

        foreach ($organs as $organ) {
            try {
                $response = Http::get("$rahkaranApi/$organ->slug");
                if (!$response->successful()) {
                    Log::error("Failed to fetch deposits for organ: {$organ->slug}");
                    continue;
                }
                $deposits = $response->json();
                foreach ($deposits as $data) {
                    $bankName = trim(str_replace("بانک", "", $data['BankTitle'])) ;
                    $bank = Bank::whereName($bankName)->first();
                    if (!$bank) {
                        $bank = Bank::Create([
                            'name' => $bankName,
                            'en_name' => Helper::persianToLatin($bankName),
                            'created_by' => 1,
                            'updated_by' => 1,
                            'logo' => null,
                        ]);
                    }

                    $deposit = Deposit::whereNumber($data['AccountNumber'])->first();
                    if (!$deposit) {
                        Deposit::Create([
                            'organ_id' => $organ->id,
                            'bank_id' => $bank->id,
                            'branch_code' => $data['BranchCode'],
                            'branch_name' => $data['BankBranch'],
                            'number' => $data['AccountNumber'],
                            'type' => Deposit::DEPOSIT_CURRENT,
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
