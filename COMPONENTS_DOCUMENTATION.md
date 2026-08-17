# 🎨 Components و UI Library | Navracar

**نسخه**: 1.0  
**تاریخ آخرین به‌روزرسانی**: 17 آگوست 2026  
**مکان**: `resources/views/components/`

---

## 📑 فهرست

1. [Layouts](#layouts)
2. [Base Components](#base-components)
3. [Feature Components](#feature-components)
4. [Email Templates](#email-templates)
5. [استفاده و نمونه‌ها](#استفاده-و-نمونه‌ها)

---

## 🏗️ Layouts

### `layouts/admin.blade.php`
**نوع**: Layout اصلی مدیریتی  
**استفاده**: تمام صفحات Admin  
**قسمت‌های اصلی**:
```php
@extends('components.layouts.admin')
@section('content')
  <!-- صفحه محتوا -->
@endsection
```

**ویژگی‌ها**:
- ✅ ناویگیشن بالایی
- ✅ سایدبار کناری
- ✅ Toast notifications
- ✅ Auth guard
- ✅ Breadcrumb navigation
- ✅ Dark mode support

**CSS Classes**:
- `.admin-layout`: Container اصلی
- `.sidebar`: سایدبار ناویگیشن
- `.topbar`: نوار بالایی
- `.content`: ناحیهٔ محتوا
- `.main-wrapper`: Wrapper پایگاه

**فایل‌های وابسته**:
- Tailwind CSS
- Alpine.js برای تعاملات
- Font Awesome برای آیکون‌ها

---

### `layouts/public.blade.php`
**نوع**: Layout صفحات عمومی  
**استفاده**: صفحات `/`, `/calculator`, `/blog`, etc  
**قسمت‌های اصلی**:
```php
@extends('components.layouts.public')
@section('content')
  <!-- محتوای عمومی -->
@endsection
```

**ویژگی‌ها**:
- ✅ Header و Footer
- ✅ Navigation Menu
- ✅ Mobile responsive
- ✅ SEO meta tags
- ✅ Social media links
- ✅ Newsletter signup

**CSS Classes**:
- `.public-layout`: Container
- `.header`: سر صفحه
- `.footer`: پایین صفحه
- `.navbar`: ناویگیشن
- `.container`: Wrapper محتوا

---

## 🎨 Base Components

### `button.blade.php`
**نوع**: دکمهٔ تعاملی  
**مقصد**: CTA، submit، navigation  

**پارامترها**:
```php
<x-button 
  type="submit|button|link"         // نوع دکمه
  variant="primary|secondary|danger" // رنگ
  size="sm|md|lg"                    // اندازه
  disabled="false"                   // غیرفعال
  href="string"                      // برای link
  onclick="string"                   // Event
>
  متن دکمه
</x-button>
```

**نمونه‌ها**:
```blade
<!-- Primary Button -->
<x-button type="submit" variant="primary">ثبت</x-button>

<!-- Danger Button -->
<x-button type="button" variant="danger" onclick="delete()">حذف</x-button>

<!-- Link Button -->
<x-button type="link" href="/dashboard" variant="secondary">برو به داشبورد</x-button>

<!-- Disabled State -->
<x-button disabled="true" variant="primary">فعال نیست</x-button>
```

**CSS Classes و استایل‌ها**:
```css
.btn { /* Base button */ }
.btn-primary { /* آبی، primary */ }
.btn-secondary { /* خاکستری، secondary */ }
.btn-danger { /* قرمز، خطرناک */ }
.btn-sm { font-size: 0.875rem; padding: 0.25rem 0.75rem; }
.btn-md { font-size: 1rem; padding: 0.5rem 1rem; }
.btn-lg { font-size: 1.125rem; padding: 0.75rem 1.5rem; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn:hover:not(:disabled) { /* Hover effect */ }
```

**رفتار**:
- Hover: رنگ تیره‌تر شود
- Active: shadow اضافه شود
- Disabled: opacity کم شود
- Focus: outline مرئی

---

### `card.blade.php`
**نوع**: کارت شامل متن یا محتوا  
**مقصد**: نمایش محتوای گروپ‌بندی شده  

**پارامترها**:
```php
<x-card 
  title="string"        // عنوان کارت
  footer="string"       // متن پایین
  class="string"        // CSS اضافی
  padded="true|false"   // Padding داخلی
>
  محتوای کارت
</x-card>
```

**نمونه‌ها**:
```blade
<x-card title="آمار فروش">
  <p>100 درخواست این ماه</p>
</x-card>

<x-card title="لیست" padded="false">
  <ul>
    <li>مورد 1</li>
    <li>مورد 2</li>
  </ul>
</x-card>
```

---

### `badge.blade.php`
**نوع**: برچسب / Tag  
**مقصد**: نمایش وضعیت، دسته‌بندی  

**پارامترها**:
```php
<x-badge 
  color="blue|red|green|yellow|gray" // رنگ
  variant="solid|outline"             // نوع
>
  متن برچسب
</x-badge>
```

**نمونه‌ها**:
```blade
<!-- Status Badges -->
<x-badge color="green">✓ تأیید شده</x-badge>
<x-badge color="red">✗ رد شده</x-badge>
<x-badge color="yellow">⏳ در انتظار</x-badge>

<!-- Role Badges -->
<x-badge color="blue">مدیر</x-badge>
<x-badge color="blue">فروشنده</x-badge>
```

**رنگ‌ها**:
- `blue`: معلومات، primary
- `red`: خطر، خطا
- `green`: موفقیت
- `yellow`: هشدار
- `gray`: neutral

---

### `icon.blade.php`
**نوع**: آیکون (Font Awesome)  
**مقصد**: نمایش نماد‌ها  

**پارامترها**:
```php
<x-icon 
  name="fa-icon-name"   // نام آیکون
  size="sm|md|lg|xl"    // اندازه
  color="string"        // رنگ
>
```

**نمونه‌ها**:
```blade
<x-icon name="fa-check" color="green" />
<x-icon name="fa-trash" size="lg" />
<x-icon name="fa-edit" />
```

---

## 🎯 Feature Components

### `stat-card.blade.php`
**نوع**: کارت آماری  
**مقصد**: نمایش شاخص‌های عددی  

**پارامترها**:
```php
<x-stat-card 
  label="string"      // برچسب
  value="string|int"  // مقدار
  change="string"     // تغییر (%)
  trend="up|down"     // روند
  icon="string"       // آیکون
>
```

**نمونه‌ها**:
```blade
<x-stat-card 
  label="کل فروش"
  value="125,000,000"
  change="+12.5%"
  trend="up"
  icon="fa-chart-line"
/>

<x-stat-card 
  label="درخواست‌های بسته"
  value="45"
  change="-2.3%"
  trend="down"
/>
```

---

### `empty-state.blade.php`
**نوع**: حالت خالی  
**مقصد**: نمایش وقتی داده‌ای نیست  

**پارامترها**:
```php
<x-empty-state 
  icon="fa-icon-name"      // آیکون
  title="string"           // عنوان
  description="string"     // توضیح
  action-text="string"     // متن دکمه
  action-url="string"      // لینک دکمه
>
```

**نمونه‌ها**:
```blade
<x-empty-state 
  icon="fa-inbox"
  title="هیچ درخواستی نیست"
  description="تمام درخواست‌ها پردازش شده‌اند"
  action-text="برو به صفحهٔ اصلی"
  action-url="/"
/>
```

---

### `toast-container.blade.php`
**نوع**: نوتیفیکیشن (Toaster)  
**مقصد**: نمایش پیام‌های موقتی  

**پارامترها**:
```php
<x-toast-container 
  position="top-right|top-left|bottom-right|bottom-left"
/>
```

**استفاده از JavaScript**:
```javascript
// نمایش پیام موفقیت
showToast('عملیات با موفقیت انجام شد', 'success');

// نمایش پیام خطا
showToast('خطا در انجام عملیات', 'error');

// نمایش هشدار
showToast('توجه به این نکته', 'warning');

// نمایش اطلاع
showToast('این یک اطلاع است', 'info');
```

**انواع**:
- `success`: سبز، موفقیت
- `error`: قرمز، خطا
- `warning`: زرد، هشدار
- `info`: آبی، اطلاع

---

### `car-calculator.blade.php`
**نوع**: Component محاسب‌ه قیمت خودرو  
**مقصد**: ابزار محاسب‌ه تعاملی  

**پارامترها**:
```php
<x-car-calculator 
  :categories="$categories"   // دسته‌بندی‌ها
  :makes="$makes"             // برندها
  :prices="$prices"           // ضرایب قیمت
/>
```

**فیلدهای ورودی**:
- Category (Sedan, SUV, Truck, etc.)
- Make (BMW, Toyota, etc.)
- Model
- Year
- Mileage
- Condition

**خروجی**:
- Base Price
- Customs Tax
- Import Fees
- Service Fee
- **Total Price**

**رویدادها**:
```javascript
// بعد از محاسبه
on('priceCalculated', (result) => {
  console.log(result.total);
  console.log(result.breakdown);
});
```

---

### `schema-breadcrumbs.blade.php`
**نوع**: Breadcrumb ناویگیشن  
**مقصد**: نشان دادن موقعیت فعلی  

**پارامترها**:
```php
<x-schema-breadcrumbs 
  :items="[
    ['name' => 'خانه', 'url' => '/'],
    ['name' => 'محصولات', 'url' => '/products'],
    ['name' => 'BMW', 'url' => null], // آخری (بدون لینک)
  ]"
/>
```

**نمونهٔ خروجی**:
```
خانه > محصولات > BMW
```

---

### `social-publish.blade.php`
**نوع**: تنظیمات انتشار اجتماعی  
**مقصد**: انتشار در Instagram، Twitter، etc.  

**پارامترها**:
```php
<x-social-publish 
  :platforms="['instagram', 'twitter', 'facebook']"
  :item="$carListing"
/>
```

**فیلدهای تنظیم**:
- ✅ فعال/غیرفعال هر platform
- ✅ متن caption
- ✅ Hashtags
- ✅ Schedule انتشار
- ✅ Preview

---

### `staging-banner.blade.php`
**نوع**: بنر هشدار (Staging)  
**مقصد**: نمایش وقتی روی محیط test هستیم  

**خروجی**:
```
⚠️ این محیط Staging است - تغییرات کاربران واقعی را تحت تأثیر قرار می‌دهد
```

---

## 📧 Email Templates

### `emails/quote-request-received.blade.php`
**نوع**: ایمیل تأیید درخواست  
**استفاده**: وقتی کاربر درخواست نقل‌قول ثبت می‌کند  

**متغیرها**:
```php
$quoteRequest->name
$quoteRequest->email
$quoteRequest->car_label
$quoteRequest->totals()
$quoteRequest->breakdown()
```

**محتوا**:
- سلام و تشکر
- خلاصهٔ درخواست
- مبلغ کل
- شمارهٔ reference
- لینک مشاهده

---

### `emails/proforma-invoice.blade.php`
**نوع**: ایمیل فاکتور Proforma  
**استفاده**: هنگام ارسال فاکتور  

**متغیرها**:
```php
$invoice->number
$invoice->request
$invoice->breakdown_json
$invoice->totals_json
$downloadLink // لینک دانلود PDF
```

---

### `emails/lead-form-submitted.blade.php`
**نوع**: ایمیل تأیید فرم درخواست  
**استفاده**: فرم درخواست مشاور  

**متغیرها**:
```php
$leadForm->name
$leadForm->phone
$leadForm->email
$leadForm->message
```

---

## 📋 PDF Templates

### `pdf/proforma.blade.php`
**نوع**: PDF فاکتور (فارسی)  
**استفاده**: دانلود فاکتور  

**ویژگی‌ها**:
- ✅ RTL (راست به چپ)
- ✅ قیمت‌گذاری کامل
- ✅ اطلاعات مشتری
- ✅ شمارهٔ سریال
- ✅ تاریخ فارسی

**CSS**:
```css
direction: rtl;
font-family: 'XB Niloofar', Arial, sans-serif;
```

---

### `pdf/proforma-en.blade.php`
**نوع**: PDF فاکتور (انگلیسی)  
**استفاده**: دانلود فاکتور (English)  

---

## 💡 استفاده و نمونه‌ها

### نمونهٔ 1: صفحهٔ درخواست‌ها (Admin)
```blade
@extends('components.layouts.admin')

@section('content')
  <x-schema-breadcrumbs :items="[
    ['name' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['name' => 'درخواست‌ها', 'url' => null],
  ]" />

  <div class="grid grid-cols-4 gap-4 mb-6">
    <x-stat-card label="درخواست‌ها" value="{{ $totalRequests }}" trend="up" />
    <x-stat-card label="بسته‌شده" value="{{ $closedRequests }}" trend="up" />
    <x-stat-card label="در انتظار" value="{{ $pendingRequests }}" trend="down" />
    <x-stat-card label="آرشیو" value="{{ $archivedRequests }}" trend="flat" />
  </div>

  @if ($requests->isEmpty())
    <x-empty-state 
      icon="fa-inbox"
      title="هیچ درخواستی نیست"
      action-text="ایجاد درخواست جدید"
      action-url="{{ route('admin.requests.create') }}"
    />
  @else
    <x-card title="درخواست‌های اخیر">
      <table class="w-full">
        @foreach ($requests as $request)
          <tr>
            <td>{{ $request->name }}</td>
            <td><x-badge color="blue">{{ $request->stage->name }}</x-badge></td>
            <td>{{ $request->created_at->diffForHumans() }}</td>
          </tr>
        @endforeach
      </table>
    </x-card>
  @endif

  <x-toast-container position="top-right" />
@endsection
```

### نمونهٔ 2: صفحهٔ محاسب‌ه قیمت (Public)
```blade
@extends('components.layouts.public')

@section('content')
  <div class="container mx-auto py-12">
    <h1 class="text-4xl font-bold mb-8">محاسب‌ه قیمت خودرو</h1>
    
    <x-car-calculator 
      :categories="$categories"
      :makes="$makes"
      :prices="$prices"
    />

    <div id="result" class="mt-8 hidden">
      <x-card title="نتیجهٔ محاسب‌ه">
        <div id="breakdown"></div>
        <div id="total" class="text-2xl font-bold mt-4"></div>
      </x-card>
    </div>
  </div>

  <x-toast-container position="top-right" />

  <script>
    // بعد از محاسبه
    on('priceCalculated', (result) => {
      document.getElementById('result').classList.remove('hidden');
      document.getElementById('total').textContent = result.total;
    });
  </script>
@endsection
```

---

## 🎨 رنگ‌های Theme

### Light Mode (پیش‌فرض)
```css
--primary: #2563eb (آبی)
--secondary: #1e40af (آبی تیره)
--accent: #dc2626 (قرمز)
--success: #16a34a (سبز)
--warning: #ea580c (نارنجی)
--bg-light: #f8fafc (خاکستری روشن)
--text-light: #1e293b (متن تیره)
--border: #e2e8f0 (حاشیهٔ روشن)
```

### Dark Mode
```css
--bg-light: #0f172a (تیره)
--text-light: #f1f5f9 (متن روشن)
--border: #334155 (حاشیهٔ تیره)
```

---

## 📏 Typography

### Typefaces
- **Display**: System Font (SF Pro Display)
- **Body**: System Font (SF Pro Text)
- **Code**: Courier New / Menlo

### Scale
```css
.text-xs: 0.75rem
.text-sm: 0.875rem
.text-base: 1rem (پیش‌فرض)
.text-lg: 1.125rem
.text-xl: 1.25rem
.text-2xl: 1.5rem
.text-3xl: 1.875rem
.text-4xl: 2.25rem
```

---

## 🔄 ایجاد Component جدید

### مراحل:
1. **فایل درست کنید**: `resources/views/components/my-component.blade.php`

```blade
@props([
  'label' => 'Label',      // Props
  'value' => null,
  'required' => false,
])

<div class="component">
  @if ($required)
    <span class="text-red-500">*</span>
  @endif
  <label>{{ $label }}</label>
  <div>{{ $value }}</div>
  {{ $slot }}
</div>
```

2. **استفاده کنید**:
```blade
<x-my-component label="نام" value="محمد" required />
```

3. **استایل‌ دهید**:
```css
.component {
  /* CSS */
}
```

4. **Test کنید** (في یک view)

5. **این فایل را به‌روز کنید**

---

## ✅ Accessibility

تمام components شامل:
- ✅ Semantic HTML
- ✅ ARIA labels
- ✅ Keyboard navigation
- ✅ Focus states
- ✅ Color contrast

---

**نوشته**: این documentation برای شناخت Component Library بهتر است و باید برای components جدید به‌روز شود.

