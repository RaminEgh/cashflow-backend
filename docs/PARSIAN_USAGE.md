# نحوه استفاده از سرویس بانک پارسیان

## پیش‌نیازها

ابتدا در فایل `.env` خود این مقادیر را تنظیم کنید:

```env
PARSIAN_CLIENT_ID=4836766166044676016
PARSIAN_CLIENT_SECRET=6040bf64-bf1e-4285-84ea-68b1614f440d
PARSIAN_USE_SANDBOX=true
```

## روش‌های استفاده

### 1️⃣ استفاده با Factory Pattern (پیشنهادی)

```php
use App\Services\Banking\BankAdapterFactory;

// دریافت Factory از Container
$bankFactory = app(BankAdapterFactory::class);

// ساخت adapter برای بانک پارسیان
$adapter = $bankFactory->make('parsian');

// تنظیم اطلاعات حساب
$adapter->setAccount([
    'accountNumber' => '85000005464007'
]);

// دریافت موجودی ساده
$balance = $adapter->getBalance();
echo "موجودی: " . number_format($balance) . " ریال";

// یا دریافت اطلاعات کامل
$balanceInfo = $adapter->getAccountBalance();
/*
[
    'accountNumber' => '85000005464007',
    'balance' => 152216359360.0,
    'todayDepositAmount' => 100.0,
    'todayWithdrawAmount' => 0.0,
    'currency' => 'IRR',
]
*/
```

### 2️⃣ استفاده مستقیم

```php
use App\Services\Banking\Adapters\ParsianBankAdapter;

$adapter = new ParsianBankAdapter;

$adapter->setAccount([
    'accountNumber' => '85000005464007'
]);

$balanceInfo = $adapter->getAccountBalance();
```

### 3️⃣ استفاده در Controller

```php
namespace App\Http\Controllers;

use App\Services\Banking\BankAdapterFactory;
use Illuminate\Http\JsonResponse;

class BankBalanceController extends Controller
{
    public function __construct(
        private BankAdapterFactory $bankFactory
    ) {}

    public function getParsianBalance(string $accountNumber): JsonResponse
    {
        try {
            $adapter = $this->bankFactory->make('parsian');
            
            $adapter->setAccount([
                'accountNumber' => $accountNumber
            ]);
            
            $balanceInfo = $adapter->getAccountBalance();
            
            return response()->json([
                'success' => true,
                'data' => $balanceInfo
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

### 4️⃣ استفاده در Job (Queue)

```php
namespace App\Jobs;

use App\Models\Deposit;
use App\Services\Banking\BankAdapterFactory;
use Illuminate\Contracts\Queue\ShouldQueue;

class FetchParsianBalance implements ShouldQueue
{
    public function __construct(
        public Deposit $deposit
    ) {}

    public function handle(BankAdapterFactory $bankFactory): void
    {
        $adapter = $bankFactory->make('parsian');
        
        $adapter->setAccount([
            'accountNumber' => $this->deposit->number
        ]);
        
        $balanceInfo = $adapter->getAccountBalance();
        
        // ذخیره موجودی
        $this->deposit->update([
            'balance' => $balanceInfo['balance'],
            'balance_synced_at' => now(),
            'last_balance_sync_success' => true
        ]);
    }
}
```

### 5️⃣ استفاده در Service Class

```php
namespace App\Services;

use App\Services\Banking\BankAdapterFactory;

class BankBalanceService
{
    public function __construct(
        private BankAdapterFactory $bankFactory
    ) {}

    public function fetchParsianBalance(string $accountNumber): array
    {
        $adapter = $this->bankFactory->make('parsian');
        
        $adapter->setAccount([
            'accountNumber' => $accountNumber
        ]);
        
        return $adapter->getAccountBalance();
    }
    
    public function fetchMultipleBalances(array $accounts): array
    {
        $results = [];
        
        foreach ($accounts as $account) {
            try {
                $adapter = $this->bankFactory->make($account['bank_type']);
                
                $adapter->setAccount([
                    'accountNumber' => $account['number']
                ]);
                
                $results[] = [
                    'account' => $account['number'],
                    'success' => true,
                    'data' => $adapter->getAccountBalance()
                ];
                
            } catch (\Exception $e) {
                $results[] = [
                    'account' => $account['number'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}
```

## تست سریع از طریق API

یک endpoint تستی در `routes/api.php` ایجاد شده است:

```bash
POST http://localhost:8000/api/test-parsian
```

همچنین می‌توانید با Tinker تست کنید:

```bash
php artisan tinker
```

```php
$factory = app(\App\Services\Banking\BankAdapterFactory::class);
$adapter = $factory->make('parsian');
$adapter->setAccount(['accountNumber' => '85000005464007']);
$balance = $adapter->getAccountBalance();
```

## نکات مهم

1. **Sandbox Mode**: در محیط توسعه، `PARSIAN_USE_SANDBOX=true` تنظیم شود
2. **Production**: در محیط تولید، `PARSIAN_USE_SANDBOX=false` و URL واقعی استفاده می‌شود
3. **Token Caching**: توکن در هر request جدید دریافت می‌شود. برای بهینه‌سازی می‌توانید cache اضافه کنید
4. **Error Handling**: همیشه از try-catch استفاده کنید چون API ممکن است خطا برگرداند
5. **Account Number**: پارامتر `accountNumber` الزامی است
6. **OAuth URLs**:
   - Sandbox: `https://sandbox.parsian-bank.ir/oauth2/token`
   - Production: `https://openapi.parsian-bank.ir/oauth2/token`

## خطاهای رایج

### خطای "Account number is required"

```php
// ❌ اشتباه
$adapter->setAccount([]);

// ✅ درست
$adapter->setAccount(['accountNumber' => '85000005464007']);
```

### خطای "Failed to authenticate with Parsian Bank"

این خطا معمولاً به دلیل Client ID یا Secret اشتباه رخ می‌دهد. بررسی کنید:

- `PARSIAN_CLIENT_ID` صحیح است
- `PARSIAN_CLIENT_SECRET` صحیح است
- اتصال اینترنت برقرار است

### خطای "Account not found"

شماره حساب در سیستم بانک موجود نیست یا غیرفعال است.
