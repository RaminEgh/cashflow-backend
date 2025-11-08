<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Banking\BankAdapterFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ParsianTestController extends Controller
{
    public function __construct(
        private BankAdapterFactory $bankFactory
    ) {}

    /**
     * دریافت موجودی حساب از بانک پارسیان
     */
    public function getBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'accountNumber' => 'required|string',
        ]);

        try {
            $adapter = $this->bankFactory->make('parsian');

            $adapter->setAccount([
                'accountNumber' => $validated['accountNumber'],
            ]);

            $balanceInfo = $adapter->getAccountBalance();

            return response()->json([
                'success' => true,
                'data' => $balanceInfo,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Parsian balance', [
                'accountNumber' => $validated['accountNumber'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * دریافت فقط مقدار موجودی (ساده)
     */
    public function getSimpleBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'accountNumber' => 'required|string',
        ]);

        try {
            $adapter = $this->bankFactory->make('parsian');

            $adapter->setAccount([
                'accountNumber' => $validated['accountNumber'],
            ]);

            $balance = $adapter->getBalance();

            return response()->json([
                'success' => true,
                'balance' => $balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تست اتصال به بانک پارسیان
     */
    public function testConnection(): JsonResponse
    {
        try {
            // استفاده از یک شماره حساب نمونه
            $testAccountNumber = '85000005464007';

            $adapter = $this->bankFactory->make('parsian');

            $adapter->setAccount([
                'accountNumber' => $testAccountNumber,
            ]);

            $balanceInfo = $adapter->getAccountBalance();

            return response()->json([
                'success' => true,
                'message' => 'اتصال به بانک پارسیان موفق بود',
                'data' => $balanceInfo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اتصال به بانک پارسیان',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
