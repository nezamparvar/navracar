# ناوراکار — پنل مدیریت و محاسبه‌گر واردات خودرو

اپلیکیشن Laravel برای ناوراکار: محاسبه‌گر عمومی هزینه واردات خودرو (با استعلام VIN)، فرم ثبت تماس فروش، و پنل مدیریت CRM شامل پایپ‌لاین کانبان، مدیریت درخواست‌ها، پیش‌فاکتورها، قالب‌های پیام و کاربران.

## پشته فنی

- **Laravel 12** + PHP 8.3
- **Blade** + **Tailwind CSS** (از طریق Vite) برای رابط کاربری
- **Alpine.js** برای تعامل‌های سمت کاربر (کانبان، تاگل حالت تاریک، فرم‌های AJAX)
- **MySQL** در محیط عملیاتی (SQLite برای توسعه سریع محلی کافی است)

## نصب برای توسعه

```bash
composer install
npm ci

cp .env.example .env
php artisan key:generate

# برای تست سریع محلی با SQLite:
touch database/database.sqlite
# در .env مقدار DB_CONNECTION=sqlite بگذارید

php artisan migrate --seed
npm run build   # یا npm run dev برای توسعه با Hot Reload

php artisan serve
```

سیدر `AdminUserSeeder` فقط در محیط `local` یک حساب مدیر پیش‌فرض (`admin` / `password`) می‌سازد — صرفاً برای توسعه.

## ساخت حساب کاربری (مدیر / کارشناس فروش)

به‌جای اسکریپت عمومی قدیمی (`create-admin.php`)، از دستور Artisan استفاده کنید:

```bash
php artisan admin:create-user USERNAME --password=STRONG_PASSWORD --role=admin --name="نام کامل"
# role می‌تواند admin یا sales باشد
```

## ساختار پروژه

```
app/
  Http/Controllers/Admin/    کنترلرهای پنل مدیریت (داشبورد، کانبان، درخواست‌ها، پیش‌فاکتورها، ...)
  Http/Controllers/Public/   کنترلرهای عمومی (محاسبه‌گر، فرم سرنخ، endpointهای لاگ)
  Http/Controllers/Auth/     ورود/خروج پنل (بدون ثبت‌نام عمومی)
  Models/                    مدل‌های Eloquent منطبق بر جداول CRM
  Services/GeoLookupService  تشخیص کشور/شهر از روی IP
  Support/ActivityLogger     لاگ فعالیت/خطا (جایگزین debug-log.php قدیمی)
  Mail/                      ایمیل‌های اطلاع‌رسانی سرنخ/استعلام جدید

resources/views/
  components/layouts/admin.blade.php    قالب سایدبار پنل مدیریت (+ حالت تاریک)
  components/layouts/public.blade.php   قالب صفحات عمومی
  admin/                                صفحات پنل مدیریت
  public/                               محاسبه‌گر و فرم سرنخ عمومی

routes/
  admin.php    مسیرهای پنل مدیریت (نیازمند ورود؛ بخشی فقط برای نقش مدیر)
  public.php   مسیرهای عمومی (محاسبه‌گر، فرم سرنخ، endpointهای لاگ)
  auth.php     ورود/خروج
```

## دسترسی‌ها (CRM)

- **مدیر (`admin`)**: دسترسی کامل به همه سرنخ‌ها، می‌تواند الحاق کند، و صفحات «قالب‌های پیام»، «کاربران» و «لاگ سیستم» فقط برایش باز است.
- **کارشناس فروش (`sales`)**: فقط سرنخ‌های الحاق‌شده به خودش را در لیست/کانبان می‌بیند.

## نکات امنیتی نسبت به نسخه PHP قبلی

- ساخت حساب دیگر از طریق یک آدرس عمومی (`create-admin.php`) انجام نمی‌شود؛ فقط از خط فرمان سرور با دستور `admin:create-user` ممکن است.
- رمزهای عبور و نشست‌ها از مکانیزم استاندارد Auth لاراول استفاده می‌کنند (قفل موقت بعد از ۶ تلاش ناموفق ورود، انقضای نشست بعد از ۴ ساعت بی‌فعالیتی — مطابق رفتار نسخه قبلی).
- تمام فرم‌های پنل مدیریت با CSRF token محافظت می‌شوند.

## تست

```bash
php artisan test
```

## اپ Android

نسخه Android یک پوستهٔ Capacitor 8 با رابط محلی در `mobile/` است و محاسبات را از API مرکزی ناوراکار دریافت می‌کند؛ فرمول و نرخ‌ها داخل اپ تکرار نمی‌شوند.

```bash
npm ci
npm run build
npx cap sync android
cd android
./gradlew assembleDebug
```

نیازمندی‌ها: Node.js 22، JDK 21 و Android SDK 36. فایل APK آزمایشی نیز در job با نام `Android build` در CI ساخته می‌شود.
