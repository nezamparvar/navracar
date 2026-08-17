# ناوراکار Android — Capacitor 8

## معماری

- کد رابط بسته‌بندی‌شده در `mobile/` قرار دارد و `mobile/index.html` نقطه شروع اپ است.
- پروژه native قابل بازتولید در `android/` داخل Git نگهداری می‌شود.
- اپ هیچ فرمول یا نرخ تجاری را داخل APK نگهداری نمی‌کند؛ درخواست بدون `customs_price_aed` به `POST /api/vehicle-pricing/calculate` ارسال می‌شود و `VehiclePricingService` قیمت گمرکی پیشنهادی و جمع‌ها را از تنظیمات جاری محاسبه می‌کند.
- API عمومی stateless است، CSRF وب را استفاده نمی‌کند، rate limit دارد و CORS فقط originهای Capacitor محلی را می‌پذیرد.
- نسخه فعلی برای محاسبه به اینترنت نیاز دارد. Offline calculation، استخراج خودکار از Marketplace و انتشار Google Play جزو قابلیت‌های تکمیل‌شده ادعا نمی‌شوند.

## نیازمندی‌ها

- Node.js 22 یا جدیدتر
- JDK 21
- Android SDK / compile SDK 36
- Android Studio سازگار با SDK 36

## ساخت و همگام‌سازی

```bash
npm ci
npm audit --audit-level=high
npm run build
npx cap sync android
cd android
./gradlew assembleDebug
```

خروجی debug:

```text
android/app/build/outputs/apk/debug/app-debug.apk
```

CI همین مسیر را در check مستقل `Android build` اجرا و APK آزمایشی را به‌عنوان artifact ذخیره می‌کند.

## تنظیم endpoint

آدرس پایه API در این meta tag قرار دارد:

```html
<meta name="navracar-api-base" content="https://navracar.com">
```

برای آزمون Staging، فقط در artifact آزمایشی آدرس را به endpoint تأییدشده Staging تغییر دهید، `npx cap sync android` و build را دوباره اجرا کنید. APK پذیرفته‌شده باید با SHA و hash ثبت شود. endpoint آزمایشی نباید پشت HTTP Basic Auth یا Directory Privacy باشد، چون کلاینت Capacitor نمی‌تواند challenge مرورگر را به‌شکل قابل‌اعتماد پاسخ دهد. هیچ credential یا token را در فایل‌های `mobile/` یا `capacitor.config.json` قرار ندهید.

## آزمون پذیرش

1. اپ بدون crash باز شود و صفحه محاسبه‌گر محلی را نمایش دهد.
2. قیمت واقعی و دسته‌بندی ارسال شوند؛ قیمت گمرکی و جمع کل از پاسخ server-authoritative نمایش داده شوند.
3. درصدهای 0، 30، اعشاری و 100 تنظیمات سرور نتیجه درست بدهند.
4. قطع اینترنت پیام روشن نشان دهد و داده جعلی/قدیمی را نتیجه موفق معرفی نکند.
5. تاریخچه فقط شامل نام خودرو، جمع، و زمان باشد و حداکثر ده مورد محلی نگه دارد.
6. CORS درخواست از `https://localhost` را بپذیرد و originهای تصادفی را رد کند.
7. `npm audit`, `cap sync`, و `assembleDebug` سبز باشند.

## انتشار

امضا، ساخت AAB، ثبت در Google Play و هرگونه Deploy تنها پس از پذیرش Staging و تأیید صریح Mostafa انجام می‌شود. keystore و رمزهای امضا نباید وارد Git شوند.

