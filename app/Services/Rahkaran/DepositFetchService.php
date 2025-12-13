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
                    $bankName = $this->cleanBankTitle($data['BankTitle']);
                    $enName = $this->mapBankNameToEnglish($bankName);
                    $bank = Bank::whereName($bankName)->first();
                    if (! $bank) {
                        $bank = Bank::Create([
                            'name' => $bankName,
                            'en_name' => $enName,
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

    /**
     * Clean bank title by removing special characters, numbers, and "بانک" prefix.
     */
    private function cleanBankTitle(string $bankTitle): string
    {
        // Remove "بانک" prefix
        $cleaned = trim(str_replace('بانک', '', $bankTitle));

        // Remove special characters and numbers: * ! @ 1 2 3 4 5 6 7 8 9 0
        $cleaned = preg_replace('/[*!@0-9]/u', '', $cleaned);

        return trim($cleaned);
    }

    /**
     * Map Persian bank name to English name.
     */
    private function mapBankNameToEnglish(string $persianName): string
    {
        $mapping = [
            'ملی' => 'melli',
            'ملّی' => 'melli',
            'سپه' => 'sepah',
            'صنعت و معدن' => 'sanat va madan',
            'صنعت‌و‌معدن' => 'sanat va madan',
            'صنعت معدن' => 'sanat va madan',
            'صنعت‌معدن' => 'sanat va madan',
            'کشاورزی' => 'keshavarzi',
            'مسکن' => 'maskan',
            'توسعه صادرات' => 'tosee saderat',
            'توسعه‌صادرات' => 'tosee saderat',
            'توسعه تعاون' => 'tosee taavon',
            'توسعه‌تعاون' => 'tosee taavon',
            'پست بانک' => 'post bank',
            'پست‌بانک' => 'post bank',
            'پست' => 'post bank',
            'صادرات' => 'saderat',
            'ملت' => 'mellat',
            'تجارت' => 'tejarat',
            'رفاه' => 'refah',
            'رفاه کارگران' => 'refah',
            'رفاه‌کارگران' => 'refah',
            'پارسیان' => 'parsian',
            'سامان' => 'saman',
            'سینا' => 'sina',
            'خاورمیانه' => 'khavarmiane',
            'شهر' => 'shahr',
            'دی' => 'day',
            'گردشگری' => 'gardeshgari',
            'ایران زمین' => 'iran zamin',
            'ایران‌زمین' => 'iran zamin',
            'قوامین' => 'ghavamin',
            'پاسارگاد' => 'pasargad',
            'اقتصاد نوین' => 'eghtesad novin',
            'اقتصاد‌نوین' => 'eghtesad novin',
            'اقتصادنوین' => 'eghtesad novin',
            'کارآفرین' => 'karafarin',
            'کار آفرین' => 'karafarin',
            'کار‌آفرین' => 'karafarin',
            'آینده' => 'ayandeh',
            'حکمت ایرانیان' => 'hekmat iranian',
            'حکمت‌ایرانیان' => 'hekmat iranian',
            'انصار' => 'ansar',
            'سرمایه' => 'sarmayeh',
            'مشترک ایران و ونزوئلا' => 'moshterk iran va vanzoola',
            'مشترک‌ایران‌و‌ونزوئلا' => 'moshterk iran va vanzoola',
            'ایران و ونزوئلا' => 'moshterk iran va vanzoola',
            'ایران‌و‌ونزوئلا' => 'moshterk iran va vanzoola',
            'ایران ونزوئلا' => 'moshterk iran va vanzoola',
            'ایران‌ونزوئلا' => 'moshterk iran va vanzoola',
            'مشترک ایران-ونزوئلا' => 'moshterk iran va vanzoola',
            'مشترک‌ایران-ونزوئلا' => 'moshterk iran va vanzoola',
            'مشترک ایران_ونزوئلا' => 'moshterk iran va vanzoola',
            'مشترک‌ایران_ونزوئلا' => 'moshterk iran va vanzoola',

            'قرض الحسنه مهر' => 'qarzolhasaneh mehr iran',
            'قرض‌الحسنه‌مهر' => 'qarzolhasaneh mehr iran',
            'مهر' => 'qarzolhasaneh mehr iran',
            'قرض الحسنه مهر ایران' => 'qarzolhasaneh mehr iran',
            'مهر ایران' => 'qarzolhasaneh mehr iran',
            'مهر‌ایران' => 'qarzolhasaneh mehr iran',
            'قرض‌الحسنه مهر ایران' => 'qarzolhasaneh mehr iran',
            'قرض‌الحسنه‌مهر‌ایران' => 'qarzolhasaneh mehr iran',
            'قرض الحسنه رسالت' => 'qarzolhasaneh resalat',
            'قرض‌الحسنه رسالت' => 'qarzolhasaneh resalat',
            'قرض‌الحسنه‌رسالت' => 'qarzolhasaneh resalat',
            'رسالت' => 'qarzolhasaneh resalat',
            'موسسه مالی و اعتباری کوثر' => 'moasse mali va atabari kousar',
            'موسسه‌مالی‌و‌اعتباری‌کوثر' => 'moasse mali va atabari kousar',
            'کوثر' => 'moasse mali va atabari kousar',
            'کوثر' => 'moasse mali va atabari kousar',
            'مهر اقتصاد' => 'mehre eghtesad',
            'مهر‌اقتصاد' => 'mehre eghtesad',
            'بلو سامان' => 'blu',
            'بلو‌سامان' => 'blu',
            'بلو' => 'blu',
            'بلوبانک' => 'blu',
            'بلو بانک' => 'blu',
            'بلو‌بانک' => 'blu',
        ];

        // Normalize the Persian name (remove extra spaces)
        $normalized = preg_replace('/\s+/u', ' ', trim($persianName));

        // First, try direct mapping
        if (isset($mapping[$normalized])) {
            return $mapping[$normalized];
        }

        // If not found, try to extract bank name from the string
        $extractedBankName = $this->extractBankNameFromString($normalized, array_keys($mapping));
        if ($extractedBankName && isset($mapping[$extractedBankName])) {
            return $mapping[$extractedBankName];
        }

        return Helper::persianToLatin($normalized);
    }

    /**
     * Extract bank name from a string if it contains one of the mapping keys.
     */
    private function extractBankNameFromString(string $text, array $bankNames): ?string
    {
        // Sort by length (longest first) to match longer names first
        usort($bankNames, function ($a, $b) {
            return mb_strlen($b) <=> mb_strlen($a);
        });

        foreach ($bankNames as $bankName) {
            // Remove spaces and half-spaces for comparison
            $normalizedText = preg_replace('/[\s\u200C]+/u', '', $text);
            $normalizedBankName = preg_replace('/[\s\u200C]+/u', '', $bankName);

            // Check if bank name exists in the text
            if (mb_strpos($normalizedText, $normalizedBankName) !== false) {
                return $bankName;
            }
        }

        return null;
    }
}
