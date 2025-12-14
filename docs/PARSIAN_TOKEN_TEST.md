# تست و ذخیره توکن بانک پارسیان

## تست گرفتن توکن

برای بررسی اینکه آیا می‌توانید توکن از API بانک پارسیان دریافت کنید، از دستور زیر استفاده کنید:

```bash
php artisan parsian:test-token
```

### خروجی موفق

```
Testing Parsian Bank OAuth token generation...
Client ID: 33710540474830709
Client Secret: ed2c7664-7...
Testing Sandbox URL: https://sandbox.parsian-bank.ir/oauth2/token
✓ Successfully obtained token from Sandbox!
Token: eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJyZXF1ZXN0...
Expires in: 3599 seconds
Token type: bearer
```

### خروجی ناموفق

```
Testing Parsian Bank OAuth token generation...
Client ID: xxxxx
Failed to get token from Sandbox
Status: 401
Response: {"error": "Unauthorized"}
```

## نحوه ذخیره توکن

توکن‌ها به صورت خودکار در **Cache** ذخیره می‌شوند:

### مشخصات Cache

- **Cache Key**: `parsian_bank_token_{environment}_{client_id}`
  - برای Sandbox: `parsian_bank_token_sandbox_33710540474830709`
  - برای Production: `parsian_bank_token_production_33710540474830709`
- **مدت زمان**: `expires_in - 60` ثانیه (با 60 ثانیه buffer)
  - معمولاً توکن 3599 ثانیه (تقریباً 1 ساعت) اعتبار دارد
  - در cache به مدت 3539 ثانیه ذخیره می‌شود

### مزایای استفاده از Cache

1. **کاهش درخواست‌های غیرضروری**: توکن تا زمان انقضا از cache استفاده می‌شود
2. **بهبود سرعت**: نیازی به درخواست OAuth در هر بار نیست
3. **کاهش فشار بر API بانک**: درخواست‌های کمتری به سرور بانک ارسال می‌شود

### بررسی Cache

برای بررسی توکن ذخیره‌شده در cache:

```php
use Illuminate\Support\Facades\Cache;

$clientId = config('banks.parsian.client_id');
$environment = 'sandbox'; // یا 'production'
$cacheKey = "parsian_bank_token_{$environment}_{$clientId}";

$token = Cache::get($cacheKey);
if ($token) {
    echo "Token exists in cache: " . substr($token, 0, 50) . "...";
} else {
    echo "No token in cache";
}
```

### پاک کردن Cache

برای پاک کردن توکن از cache (برای تست):

```php
Cache::forget("parsian_bank_token_sandbox_{$clientId}");
Cache::forget("parsian_bank_token_production_{$clientId}");
```

یا از Artisan:

```bash
php artisan cache:forget parsian_bank_token_sandbox_33710540474830709
```

## جریان کار

1. **اولین درخواست**:
   - `ParsianBankAdapter` تلاش می‌کند توکن از cache بگیرد
   - اگر وجود نداشت، از API بانک درخواست می‌کند
   - توکن را در cache ذخیره می‌کند

2. **درخواست‌های بعدی** (در مدت 1 ساعت):
   - توکن از cache خوانده می‌شود
   - هیچ درخواست OAuth جدیدی ارسال نمی‌شود

3. **بعد از انقضا**:
   - Cache خودکار توکن را حذف می‌کند
   - درخواست بعدی، توکن جدید دریافت و ذخیره می‌کند

## تنظیمات .env

### برای Sandbox

```env
PARSIAN_CLIENT_ID=4836766166044676016
PARSIAN_CLIENT_SECRET=6040bf64-bf1e-4285-84ea-68b1614f440d
PARSIAN_USE_SANDBOX=true
```

### برای Production

```env
PARSIAN_CLIENT_ID=33710540474830709
PARSIAN_CLIENT_SECRET=ed2c7664-74d3-4365-90fd-b9dadf257954
PARSIAN_USE_SANDBOX=false
```

## لاگ‌ها

تمام عملیات توکن در لاگ‌ها ثبت می‌شود:

```
[INFO] Using cached Parsian Bank token
[INFO] Successfully obtained and cached Parsian Bank token
[WARNING] Parsian Bank authentication failed with primary URL
[ERROR] Failed to authenticate with Parsian Bank
```

برای بررسی لاگ‌ها:

```bash
tail -f storage/logs/laravel.log | grep "Parsian Bank"
```
