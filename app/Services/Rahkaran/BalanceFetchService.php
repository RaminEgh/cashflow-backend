<?php

namespace App\Services\Rahkaran;

use App\Helpers\Helper;
use App\Models\Balance;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\Organ;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BalanceFetchService
{

    public function fetchAndStore()
    {
        $organs = Organ::all();
        $rahkaranApi = env("RAHKARAN_BASE_ENDPOINT");

        foreach ($organs as $organ) {
            $deposits = $organ->deposits;

            foreach ($deposits as $deposit) {
                try {
                    $response = Http::get("$rahkaranApi/$organ->slug/$deposit->number");
                    if (!$response->successful()) {
                        Log::error("Failed to fetch balances for deposit: $rahkaranApi/$organ->slug/$deposit->number");
                        continue;
                    }
                    $rahkaranBalance = $response->json();
                    $rahkaranFetchedDate = (Carbon::parse($rahkaranBalance['job_Date']))->toDateTimeString();

                    if (!$deposit->rahkaran_balance_last_synced_at ||
                        !Carbon::parse($deposit->rahkaran_balance_last_synced_at)->eq(Carbon::parse($rahkaranFetchedDate))) {
                        $deposit->update([
                            'balance' => null,
                            'balance_last_synced_at' => null,
                            'rahkaran_balance_last_synced_at' => $rahkaranFetchedDate,
                            'rahkaran_balance' => $rahkaranBalance['balance'],
                        ]);
                    }
                    $exists = Balance::where('deposit_id', $deposit->id)
                        ->whereDate('rahkaran_fetched_at', Carbon::parse($rahkaranFetchedDate)->toDateString())
                        ->exists();

                    if (!$exists) {
                        Balance::Create([
                            'deposit_id' => $deposit->id,
                            'fetched_at' => now(),
                            'rahkaran_fetched_at' => $rahkaranFetchedDate,
                            'rahkaran_status' => $rahkaranBalance && $rahkaranBalance['balance'] ? 'success' : 'fail',
                            'status' => 'fail',
                            'balance' => null,
                            'rahkaran_balance' => $rahkaranBalance['balance'],
                        ]);
                    }

                } catch (\Throwable $e) {
                    Log::error("Error fetching/storing balance for deposit {$deposit->number}: " . $e->getMessage());
                    continue;
                }
            }
        }


    }
}
