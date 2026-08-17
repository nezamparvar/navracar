# 📋 Navracar - سایت و CRM خودروها | تمام امکانات و ساختار

**نسخه**: 1.0  
**تاریخ آخرین به‌روزرسانی**: 17 آگوست 2026  
**سیاق مشاهده**: برای هر تغییر، فایل این مستند را به‌روزرسانی کنید

---

## 🎯 فهرست

1. [معماری کلی](#معماری-کلی)
2. [صفحات و Routes](#صفحات-و-routes)
3. [Models و Database Schema](#models-و-database-schema)
4. [Controllers](#controllers)
5. [Views و Components](#views-و-components)
6. [Services](#services)
7. [نقشه و روابط](#نقشه-و-روابط)
8. [راهنمای کار](#راهنمای-کار)

---

## 📐 معماری کلی

### ساختار پروژه
```
navracar/
├── app/
│   ├── Models/              (17 Model)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/       (20 Admin Controller)
│   │   │   └── Public/      (10 Public Controller)
│   │   └── Middleware/
│   ├── Services/            (قیمت‌گذاری، وارداتی، و...)
│   └── Policies/            (کنترل دسترسی)
├── routes/
│   ├── admin.php            (صفحات مدیریتی)
│   ├── public.php           (صفحات عمومی)
│   ├── api.php              (API endpoints)
│   └── auth.php             (احراز هویت)
├── resources/
│   └── views/
│       ├── admin/           (58 View فایل)
│       ├── public/
│       ├── components/
│       ├── emails/
│       └── pdf/
├── database/
│   ├── migrations/          (مدل‌های پایگاه‌داده)
│   └── seeders/
└── storage/                 (تصاویر، فایل‌ها)
```

### سه بخش اصلی پروژه

#### 1️⃣ **بخش عمومی (Public)**
- صفحات عمومی برای بازدیدکنندگان
- Calculator و قیمت‌گذاری خودروها
- Blog و مقالات
- Lead Form و Quote Request
- نقشه سایت

#### 2️⃣ **بخش مدیریتی (Admin)**
- CRM برای مدیریت درخواست‌ها
- مدیریت خودروها و قیمت‌ها
- مدیریت کاربران و نقش‌ها
- تمپلیت‌های پیام
- فاکتورهای Proforma
- Kanban Board و Pipeline

#### 3️⃣ **بخش موبایل (Capacitor)**
- برنامه‌ی موبایل iOS/Android
- همزمان‌سازی با API

---

## 🛣️ صفحات و Routes

### 📌 Routes عمومی (`routes/public.php`)

| Route | Method | Controller | توضیح |
|-------|--------|-----------|--------|
| `/` | GET | `HomeController@index` | صفحه‌ی اصلی |
| `/calculator` | GET | `CalculatorController@index` | ابزار محاسب‌ه قیمت |
| `/vehicle-pricing/calculate` | POST | `VehiclePricingController` | API محاسبه قیمت |
| `/quote-requests` | POST | `QuoteController@store` | ثبت درخواست نقل‌قول |
| `/quote-requests/{id}/pdf` | GET | `QuoteController@downloadPdf` | دانلود PDF نقل‌قول |
| `/calculation-logs` | POST | `CalculationLogController@store` | ثبت لاگ محاسبات |
| `/vin-checks` | POST | `VinLogController@store` | بررسی VIN |
| `/lead-form` | GET\|POST | `LeadFormController` | فرم درخواست مشاور |
| `/blog` | GET | `BlogController@index` | لیست مقالات |
| `/blog/{slug}` | GET | `BlogController@show` | نمایش یک مقاله |
| `/car-prices` | GET | `CarPriceController@index` | لیست خودروها |
| `/car-prices/brand/{make}` | GET | `CarPriceController@brand` | خودروها براساس برند |
| `/car-prices/category/{id}` | GET | `CarPriceController@category` | خودروها براساس دسته |
| `/car-prices/price/{bracket}` | GET | `CarPriceController@price` | خودروها براساس قیمت |
| `/car-prices/{slug}` | GET | `CarPriceController@show` | جزئیات خودرو |
| `/sitemap.xml` | GET | `SitemapController@index` | نقشه سایت |
| `/app` | GET | View | صفحه‌ی دانلود اپ موبایل |

### 📌 Routes مدیریتی (`routes/admin.php`)

#### 🎯 صفحه‌ی داشبورد (Dashboard)
| Route | Method | Controller | نقش |
|-------|--------|-----------|------|
| `/admin` | GET | `DashboardController` | Sales + Content |
| `/admin/kanban` | GET | `KanbanController@index` | Sales + Content |
| `/admin/kanban/change-stage` | POST | `KanbanController@updateStage` | Sales |

#### 📧 بخش درخواست‌ها (Requests/Leads)
| Route | Method | Controller | نقش |
|-------|--------|-----------|------|
| `/admin/requests` | GET | `RequestController@index` | Sales |
| `/admin/requests/deleted` | GET | `RequestController@deletedIndex` | Admin |
| `/admin/requests/create` | GET | `RequestController@create` | Sales |
| `/admin/requests` | POST | `RequestController@store` | Sales |
| `/admin/requests/{id}` | GET | `RequestController@show` | Sales |
| `/admin/requests/{id}/assign` | POST | `RequestController@assign` | Sales |
| `/admin/requests/{id}/temperature` | POST | `RequestController@temperature` | Sales |
| `/admin/requests/{id}/status` | POST | `RequestController@status` | Sales |
| `/admin/requests/{id}/close` | POST | `RequestController@close` | Sales |
| `/admin/requests/{id}/archive` | POST | `RequestController@archive` | Sales |
| `/admin/requests/{id}/unarchive` | POST | `RequestController@unarchive` | Sales |
| `/admin/requests/{id}` | DELETE | `RequestController@destroy` | Admin |
| `/admin/requests/{id}/restore` | POST | `RequestController@restore` | Admin |
| `/admin/requests/{id}/force` | DELETE | `RequestController@forceDelete` | Admin |

#### 🚗 مدیریت خودروها (Car Listings)
| Route | Method | Controller | نقش |
|-------|--------|-----------|------|
| `/admin/car-listings` | GET | `CarListingController@index` | Content |
| `/admin/car-listings/create` | GET | `CarListingController@create` | Content |
| `/admin/car-listings` | POST | `CarListingController@store` | Content |
| `/admin/car-listings/store-manual` | POST | `CarListingController@storeManual` | Content |
| `/admin/car-listings/import` | GET | `CarListingController@showImport` | Content |
| `/admin/car-listings/import` | POST | `CarListingController@import` | Content |
| `/admin/car-listings/{id}/edit` | GET | `CarListingController@edit` | Content |
| `/admin/car-listings/{id}` | PUT | `CarListingController@update` | Content |
| `/admin/car-listings/{id}` | DELETE | `CarListingController@destroy` | Content |
| `/admin/car-listings/{id}/publish` | POST | `CarListingController@publish` | Content |
| `/admin/car-listings/{id}/unpublish` | POST | `CarListingController@unpublish` | Content |
| `/admin/car-listings/{id}/refetch` | POST | `CarListingController@refetch` | Content |
| `/admin/car-listings/{id}/images` | POST | `CarListingController@storeImage` | Content |
| `/admin/car-listings/{id}/images/{imgId}` | DELETE | `CarListingController@destroyImage` | Content |
| `/admin/car-listings/{id}/publish-social` | POST | `CarListingController@publishSocial` | Content |

#### 📝 مدیریت فاکتورها (Invoices)
| Route | Method | Controller | نقش |
|-------|--------|-----------|------|
| `/admin/invoices` | GET | `InvoiceController@index` | Sales |
| `/admin/invoices/create` | GET | `InvoiceController@create` | Sales |
| `/admin/invoices` | POST | `InvoiceController@store` | Sales |
| `/admin/invoices/{id}` | GET | `InvoiceController@show` | Sales |
| `/admin/invoices/{id}/pdf/{lang}` | GET | `InvoiceController@downloadPdf` | Sales |
| `/admin/invoices/{id}/status` | POST | `InvoiceController@updateStatus` | Sales |

#### 📰 مدیریت مقالات (Posts)
| Route | Method | Controller | نقش |
|-------|--------|-----------|------|
| `/admin/posts` | GET | `PostController@index` | Content |
| `/admin/posts/create` | GET | `PostController@create` | Content |
| `/admin/posts` | POST | `PostController@store` | Content |
| `/admin/posts/{id}/edit` | GET | `PostController@edit` | Content |
| `/admin/posts/{id}` | PUT | `PostController@update` | Content |
| `/admin/posts/{id}` | DELETE | `PostController@destroy` | Content |
| `/admin/posts/{id}/publish` | POST | `PostController@publish` | Content |
| `/admin/posts/{id}/unpublish` | POST | `PostController@unpublish` | Content |
| `/admin/posts/{id}/publish-social` | POST | `PostController@publishSocial` | Content |

#### 🏠 مدیریت اسلاید‌های صفحه‌ی اول (Home Slides)
| Route | Method | Controller | نقش |
|-------|--------|-----------|------|
| `/admin/home-slides` | GET | `HomeSlideController@index` | Content |
| `/admin/home-slides` | POST | `HomeSlideController@store` | Content |
| `/admin/home-slides/{id}` | PUT | `HomeSlideController@update` | Content |
| `/admin/home-slides/{id}/toggle` | POST | `HomeSlideController@toggle` | Content |
| `/admin/home-slides/{id}` | DELETE | `HomeSlideController@destroy` | Content |

#### 🍔 مدیریت منو (Menu Items)
| Route | Method | Controller | نقش |
|-------|--------|-----------|------|
| `/admin/menu-items` | GET | `MenuItemController@index` | Content |
| `/admin/menu-items` | POST | `MenuItemController@store` | Content |
| `/admin/menu-items/{id}/toggle` | POST | `MenuItemController@toggle` | Content |
| `/admin/menu-items/{id}` | DELETE | `MenuItemController@destroy` | Content |

#### 📋 مدیریت تمپلیت‌ها (Message Templates)
| Route | Method | Controller | نقش |
|-------|--------|-----------|------|
| `/admin/templates` | GET | `MessageTemplateController@index` | Admin |
| `/admin/templates` | POST | `MessageTemplateController@store` | Admin |
| `/admin/templates/{id}/toggle` | POST | `MessageTemplateController@toggle` | Admin |
| `/admin/templates/{id}` | DELETE | `MessageTemplateController@destroy` | Admin |
| `/admin/template-use` | POST | `TemplateUseController` | Sales |

#### 👥 مدیریت کاربران (Users)
| Route | Method | Controller | نقش |
|-------|--------|-----------|------|
| `/admin/users` | GET | `UserController@index` | Admin |
| `/admin/users` | POST | `UserController@store` | Admin |
| `/admin/users/{id}/role` | POST | `UserController@updateRole` | Admin |
| `/admin/users/{id}/reset-password` | POST | `UserController@resetPassword` | Admin |

#### ⚙️ تنظیمات و گزارش‌ها
| Route | Method | Controller | نقش |
|-------|--------|-----------|------|
| `/admin/export` | GET | `ExportController` | Admin |
| `/admin/calculations` | GET | `CalculationLogController@index` | Admin |
| `/admin/vin-checks` | GET | `VinCheckController@index` | Admin |
| `/admin/activity-log` | GET | `ActivityLogController@index` | Admin |
| `/admin/extension-pairing` | GET\|POST | `ExtensionPairingController` | Admin |
| `/admin/extension-pairing/{id}/revoke` | POST | `ExtensionPairingController@revoke` | Admin |
| `/admin/import-queue` | GET | `ImportQueueController@index` | Admin |
| `/admin/import-queue/{id}` | GET\|PUT | `ImportQueueController` | Admin |
| `/admin/import-queue/{id}/publish` | POST | `ImportQueueController@publish` | Admin |
| `/admin/import-queue/{id}/cancel` | POST | `ImportQueueController@cancel` | Admin |
| `/admin/imports/browser-capture` | POST | `BrowserCaptureController` | Admin |
| `/admin/settings` | GET\|POST | `SettingController` | Admin |

#### 🔀 مدیریت Pipeline
| Route | Method | Controller | نقش |
|-------|--------|-----------|------|
| `/admin/pipeline-stages` | POST | `KanbanController@storeStage` | Admin |
| `/admin/pipeline-stages/{id}/name` | PATCH | `KanbanController@updateStageName` | Admin |
| `/admin/pipeline-stages/{id}` | DELETE | `KanbanController@destroyStage` | Admin |

---

## 📊 Models و Database Schema

### 17 Model اصلی

#### 1. **AdminUser** (کاربران مدیریتی)
**جدول**: `admin_users`  
**فیلدها**: `id`, `email`, `password`, `name`, `role` (admin|sales|content), `created_at`, `updated_at`  
**روابط**:
- `QuoteRequest`: assigned leads و created leads
- `LeadActivity`: activities

#### 2. **QuoteRequest** (درخواست‌های نقل‌قول)
**جدول**: `quote_requests`  
**فیلدها**: `id`, `name`, `phone`, `email`, `notes`, `car_label`, `category`, `temperature` (hot|warm|cold), `breakdown_json`, `totals_json`, `total_with_profit`, `email_sent`, `source`, `budget_range`, `country`, `city`, `assigned_to` (FK), `created_by` (FK), `follow_up_status`, `current_stage_id` (FK), `loss_reason`, `next_call_date`, `ip_address`, `is_archived`, `created_at`, `deleted_at`  
**روابط**:
- `assignee()`: AdminUser (FK: assigned_to)
- `creator()`: AdminUser (FK: created_by)
- `stage()`: PipelineStage (FK: current_stage_id)
- `activities()`: LeadActivity (hasMany)
- `invoices()`: Invoice (hasMany)

#### 3. **CarListing** (فهرست خودروها)
**جدول**: `car_listings`  
**فیلدها**: `id`, `make`, `model`, `year`, `price`, `description`, `slug`, `is_published`, `mileage`, `customs_price`, `category`, `color`, `images_json`, `created_at`, `updated_at`  
**روابط**:
- `images()`: CarListingImage (hasMany)

#### 4. **CarListingImage** (تصاویر خودروها)
**جدول**: `car_listing_images`  
**فیلدها**: `id`, `car_listing_id` (FK), `path`, `order`, `created_at`  
**روابط**:
- `listing()`: CarListing (belongsTo)

#### 5. **Invoice** (فاکتورهای Proforma)
**جدول**: `invoices`  
**فیلدها**: `id`, `request_id` (FK), `number`, `breakdown_json`, `totals_json`, `status` (draft|sent|viewed), `created_at`, `updated_at`  
**روابط**:
- `request()`: QuoteRequest (belongsTo)

#### 6. **LeadActivity** (فعالیت‌های درخواست)
**جدول**: `lead_activities`  
**فیلدها**: `id`, `request_id` (FK), `type` (call|email|note|status_change), `description`, `created_by` (FK), `created_at`  
**روابط**:
- `request()`: QuoteRequest (belongsTo)
- `creator()`: AdminUser (belongsTo)

#### 7. **PipelineStage** (مراحل Pipeline)
**جدول**: `pipeline_stages`  
**فیلدها**: `id`, `name`, `order`, `color`, `created_at`, `updated_at`  
**روابط**:
- `leads()`: QuoteRequest (hasMany)

#### 8. **MessageTemplate** (تمپلیت‌های پیام)
**جدول**: `message_templates`  
**فیلدها**: `id`, `title`, `content`, `is_active`, `created_at`, `updated_at`  
**روابط**: -

#### 9. **Post** (مقالات Blog)
**جدول**: `posts`  
**فیلدها**: `id`, `title`, `content`, `slug`, `excerpt`, `featured_image`, `is_published`, `published_at`, `created_at`, `updated_at`  
**روابط**: -

#### 10. **HomeSlide** (اسلاید‌های صفحه‌ی اول)
**جدول**: `home_slides`  
**فیلدها**: `id`, `title`, `description`, `image`, `link`, `is_active`, `order`, `created_at`, `updated_at`  
**روابط**: -

#### 11. **MenuItem** (منو‌های سایت)
**جدول**: `menu_items`  
**فیلدها**: `id`, `label`, `url`, `icon`, `order`, `is_active`, `parent_id`, `created_at`, `updated_at`  
**روابط**: -

#### 12. **CalculationLog** (لاگ‌های محاسبات)
**جدول**: `calculation_logs`  
**فیلدها**: `id`, `car_label`, `category`, `breakdown_json`, `totals_json`, `ip_address`, `created_at`  
**روابط**: -

#### 13. **VinCheck** (بررسی‌های VIN)
**جدول**: `vin_checks`  
**فیلدها**: `id`, `vin`, `result_json`, `ip_address`, `created_at`  
**روابط**: -

#### 14. **LossReason** (دلایل از‌دست‌رفتن درخواست)
**جدول**: `loss_reasons`  
**فیلدها**: `id`, `reason`, `created_at`, `updated_at`  
**روابط**: -

#### 15. **ImportQueueItem** (صف وارداتی خودروها)
**جدول**: `import_queue`  
**فیلدها**: `id`, `data_json`, `status` (pending|processing|completed|failed), `source_platform`, `capture_method`, `customs_price`, `review_state`, `created_at`, `updated_at`  
**روابط**: -

#### 16. **BrowserExtensionPairing** (جفت‌شدگی مرورگر افزونه)
**جدول**: `browser_extension_pairings`  
**فیلدها**: `id`, `admin_user_id` (FK), `token`, `device_name`, `last_used_at`, `created_at`, `updated_at`  
**روابط**:
- `user()`: AdminUser (belongsTo)

#### 17. **Setting** (تنظیمات سایت)
**جدول**: `settings`  
**فیلدها**: `id`, `key`, `value`, `created_at`, `updated_at`  
**روابط**: -

---

## 🎮 Controllers

### Admin Controllers (20)

#### `DashboardController`
**مسیرها**: `/admin` (GET)  
**متدها**:
- `index()`: نمایش داشبورد با آمار

#### `RequestController` (CRM)
**مسیرها**: `/admin/requests/*`  
**متدها**:
- `index()`: لیست درخواست‌ها
- `deletedIndex()`: لیست حذف‌شده‌ها
- `create()`: فرم ایجاد
- `store()`: ذخیره
- `show()`: جزئیات
- `edit()`: فرم ویرایش
- `update()`: به‌روزرسانی
- `destroy()`: حذف نرم
- `forceDelete()`: حذف دائمی
- `restore()`: بازگردانی
- `assign()`: تخصیص به مدیر
- `temperature()`: تغییر دما (hot/warm/cold)
- `status()`: تغییر وضعیت
- `close()`: بستن درخواست
- `archive()`: آرشیو کردن
- `unarchive()`: خروج از آرشیو

#### `InvoiceController`
**مسیرها**: `/admin/invoices/*`  
**متدها**:
- `index()`: لیست فاکتورها
- `create()`: فرم ایجاد
- `store()`: ذخیره
- `show()`: نمایش جزئیات
- `downloadPdf()`: دانلود PDF
- `updateStatus()`: تغییر وضعیت

#### `KanbanController`
**مسیرها**: `/admin/kanban`  
**متدها**:
- `index()`: نمایش صفحه Kanban
- `updateStage()`: تغییر مرحله درخواست
- `storeStage()`: ایجاد مرحله جدید
- `updateStageName()`: تغییر نام مرحله
- `destroyStage()`: حذف مرحله

#### `CarListingController`
**مسیرها**: `/admin/car-listings/*`  
**متدها**:
- `index()`: لیست خودروها
- `create()`: فرم ایجاد
- `store()`: ذخیره
- `storeManual()`: ایجاد دستی
- `edit()`: فرم ویرایش
- `update()`: به‌روزرسانی
- `destroy()`: حذف
- `publish()`: انتشار
- `unpublish()`: لغو انتشار
- `refetch()`: بازگرفتن اطلاعات
- `storeImage()`: ذخیره تصویر
- `destroyImage()`: حذف تصویر
- `showImport()`: صفحه واردات
- `import()`: واردات
- `publishSocial()`: انتشار در شبکه‌های اجتماعی

#### `PostController`
**مسیرها**: `/admin/posts/*`  
**متدها**:
- `index()`: لیست مقالات
- `create()`: فرم ایجاد
- `store()`: ذخیره
- `edit()`: فرم ویرایش
- `update()`: به‌روزرسانی
- `destroy()`: حذف
- `publish()`: انتشار
- `unpublish()`: لغو انتشار
- `publishSocial()`: انتشار در شبکه‌های اجتماعی

#### `HomeSlideController`
**مسیرها**: `/admin/home-slides/*`  
**متدها**:
- `index()`: لیست اسلاید‌ها
- `store()`: ذخیره
- `update()`: به‌روزرسانی
- `toggle()`: فعال/غیرفعال
- `destroy()`: حذف

#### `MenuItemController`
**مسیرها**: `/admin/menu-items/*`  
**متدها**:
- `index()`: لیست منو
- `store()`: ذخیره
- `toggle()`: فعال/غیرفعال
- `destroy()`: حذف

#### `MessageTemplateController`
**مسیرها**: `/admin/templates/*`  
**متدها**:
- `index()`: لیست تمپلیت‌ها
- `store()`: ذخیره
- `toggle()`: فعال/غیرفعال
- `destroy()`: حذف

#### `TemplateUseController`
**مسیرها**: `/admin/template-use` (POST)  
**متدها**:
- `__invoke()`: استفاده از تمپلیت

#### `UserController`
**مسیرها**: `/admin/users/*`  
**متدها**:
- `index()`: لیست کاربران
- `store()`: ایجاد کاربر
- `updateRole()`: تغییر نقش
- `resetPassword()`: بازنشانی رمز

#### `CalculationLogController` (Admin)
**مسیرها**: `/admin/calculations` (GET)  
**متدها**:
- `index()`: لیست لاگ‌های محاسبات

#### `VinCheckController` (Admin)
**مسیرها**: `/admin/vin-checks` (GET)  
**متدها**:
- `index()`: لیست بررسی‌های VIN

#### `ActivityLogController`
**مسیرها**: `/admin/activity-log` (GET)  
**متدها**:
- `index()`: لیست فعالیت‌های سیستم

#### `ExtensionPairingController`
**مسیرها**: `/admin/extension-pairing/*`  
**متدها**:
- `index()`: لیست جفت‌شدگی‌ها
- `store()`: ایجاد جفت‌شدگی
- `revoke()`: لغو جفت‌شدگی

#### `ImportQueueController`
**مسیرها**: `/admin/import-queue/*`  
**متدها**:
- `index()`: لیست صف وارداتی
- `show()`: جزئیات یک مورد
- `update()`: به‌روزرسانی
- `publish()`: انتشار
- `cancel()`: لغو

#### `BrowserCaptureController`
**مسیرها**: `/admin/imports/browser-capture` (POST)  
**متدها**:
- `__invoke()`: دریافت داده از مرورگر

#### `SettingController`
**مسیرها**: `/admin/settings` (GET/POST)  
**متدها**:
- `edit()`: نمایش فرم تنظیمات
- `update()`: ذخیره تنظیمات

#### `ExportController`
**مسیرها**: `/admin/export` (GET)  
**متدها**:
- `__invoke()`: صادرات داده‌ها

### Public Controllers (10)

#### `HomeController`
**مسیرها**: `/` (GET)  
**متدها**:
- `index()`: صفحه‌ی اصلی

#### `CalculatorController`
**مسیرها**: `/calculator` (GET)  
**متدها**:
- `index()`: ابزار محاسب‌ه

#### `VehiclePricingController`
**مسیرها**: `/vehicle-pricing/calculate` (POST)  
**متدها**:
- `__invoke()`: محاسبه قیمت

#### `CalculationLogController` (Public)
**مسیرها**: `/calculation-logs` (POST)  
**متدها**:
- `store()`: ثبت لاگ محاسبه

#### `VinLogController`
**مسیرها**: `/vin-checks` (POST)  
**متدها**:
- `store()`: ثبت بررسی VIN

#### `QuoteController`
**مسیرها**: `/quote-requests*`  
**متدها**:
- `store()`: ثبت درخواست نقل‌قول
- `downloadPdf()`: دانلود PDF

#### `LeadFormController`
**مسیرها**: `/lead-form` (GET/POST)  
**متدها**:
- `create()`: نمایش فرم
- `store()`: ثبت درخواست

#### `BlogController`
**مسیرها**: `/blog*`  
**متدها**:
- `index()`: لیست مقالات
- `show()`: نمایش یک مقاله

#### `CarPriceController`
**مسیرها**: `/car-prices*`  
**متدها**:
- `index()`: لیست خودروها
- `show()`: جزئیات خودرو
- `brand()`: فیلتر براساس برند
- `category()`: فیلتر براساس دسته
- `price()`: فیلتر براساس قیمت
- `sitemap()`: نقشه‌ی سایت

#### `SitemapController`
**مسیرها**: `/sitemap.xml` (GET)  
**متدها**:
- `index()`: تولید نقشه‌ی سایت XML

---

## 👀 Views و Components

### 58 View فایل

#### Admin Views (/resources/views/admin/)
```
📁 admin/
├── dashboard.blade.php                    ✅ داشبورد
├── kanban.blade.php                       ✅ صفحه Kanban
├── 📁 requests/
│   ├── index.blade.php                   ✅ لیست درخواست‌ها
│   ├── deleted.blade.php                 ✅ درخواست‌های حذف‌شده
│   ├── create.blade.php                  ✅ فرم ایجاد
│   └── show.blade.php                    ✅ جزئیات درخواست
├── 📁 invoices/
│   ├── index.blade.php                   ✅ لیست فاکتورها
│   ├── create.blade.php                  ✅ فرم ایجاد
│   └── show.blade.php                    ✅ نمایش فاکتور
├── 📁 car-listings/
│   ├── index.blade.php                   ✅ لیست خودروها
│   ├── create.blade.php                  ✅ فرم ایجاد
│   ├── edit.blade.php                    ✅ فرم ویرایش
│   ├── import.blade.php                  ✅ صفحه واردات
│   └── _fields.blade.php                 ℹ️ قسمت‌های مشترک
├── 📁 posts/
│   ├── index.blade.php                   ✅ لیست مقالات
│   ├── create.blade.php                  ✅ فرم ایجاد
│   ├── edit.blade.php                    ✅ فرم ویرایش
│   └── _fields.blade.php                 ℹ️ قسمت‌های مشترک
├── 📁 home-slides/
│   └── index.blade.php                   ✅ مدیریت اسلاید‌ها
├── 📁 menu-items/
│   └── index.blade.php                   ✅ مدیریت منو
├── 📁 templates/
│   └── index.blade.php                   ✅ تمپلیت‌های پیام
├── 📁 users/
│   └── index.blade.php                   ✅ مدیریت کاربران
├── 📁 extension-pairing/
│   └── index.blade.php                   ✅ مدیریت جفت‌شدگی
├── 📁 import-queue/
│   ├── index.blade.php                   ✅ لیست صف وارداتی
│   └── show.blade.php                    ✅ جزئیات یک مورد
├── 📁 settings/
│   └── edit.blade.php                    ✅ تنظیمات سایت
├── 📁 calculations/
│   └── index.blade.php                   ✅ گزارش محاسبات
├── 📁 vin-checks/
│   └── index.blade.php                   ✅ گزارش بررسی‌های VIN
└── 📁 activity-log/
    └── index.blade.php                   ✅ فعالیت‌های سیستم
```

#### Public Views (/resources/views/public/)
```
📁 public/
├── home.blade.php                        ✅ صفحه‌ی اصلی
├── calculator.blade.php                  ✅ ابزار محاسب‌ه
├── mobile-app.blade.php                  ✅ دانلود اپ
├── lead-form.blade.php                   ✅ فرم درخواست مشاور
├── sitemap.blade.php                     ✅ نقشه سایت
├── 📁 blog/
│   ├── index.blade.php                   ✅ لیست مقالات
│   └── show.blade.php                    ✅ نمایش یک مقاله
├── 📁 car-prices/
│   ├── index.blade.php                   ✅ لیست خودروها
│   ├── show.blade.php                    ✅ جزئیات خودرو
│   └── sitemap.blade.php                 ✅ نقشه سایت خودروها
```

#### Components (/resources/views/components/)
```
📁 components/
├── layouts/
│   ├── admin.blade.php                   🏗️ Layout مدیریتی
│   └── public.blade.php                  🏗️ Layout عمومی
├── button.blade.php                      🎨 دکمه
├── card.blade.php                        🎨 کارت
├── badge.blade.php                       🎨 برچسب
├── toast-container.blade.php             🎨 اطلاع‌رسانی
├── empty-state.blade.php                 🎨 حالت خالی
├── stat-card.blade.php                   🎨 کارت آماری
├── car-calculator.blade.php              🎨 کامپوننت محاسب‌ه
├── schema-breadcrumbs.blade.php          🎨 Breadcrumbs
├── social-publish.blade.php              🎨 انتشار در شبکه‌های اجتماعی
├── icon.blade.php                        🎨 آیکون
└── staging-banner.blade.php              🎨 بنر Staging
```

#### Email Templates (/resources/views/emails/)
```
📁 emails/
├── quote-request-received.blade.php      📧 تأیید درخواست نقل‌قول
├── proforma-invoice.blade.php            📧 فاکتور Proforma
└── lead-form-submitted.blade.php         📧 تأیید فرم درخواست
```

#### PDF Templates (/resources/views/pdf/)
```
📁 pdf/
├── proforma.blade.php                    📄 PDF فاکتور (فارسی)
└── proforma-en.blade.php                 📄 PDF فاکتور (انگلیسی)
```

#### Auth Views (/resources/views/auth/)
```
📁 auth/
└── login.blade.php                       🔐 صفحه ورود
```

---

## 🔧 Services

### ورود اصلی (`app/Services/`)

#### 1. **VehiclePricing**
**قسمت**: `Services/VehiclePricing/`  
**وظیفه**: محاسبه قیمت خودروها  
**فایل‌های اصلی**:
- `VehiclePricingCatalog.php` - داده‌های کاتالوگ
- `VehiclePricingCalculator.php` - منطق محاسبه
- `VehiclePricingEngine.php` - موتور قیمت‌گذاری

#### 2. **Import Services**
**قسمت**: `Services/Import/`  
**وظیفه**: وارد کردن خودروها  
**فایل‌های اصلی**:
- `ImportService.php` - سرویس واردات
- `BrowserCaptureService.php` - دریافت از مرورگر

#### 3. **Quote & Invoice Services**
**وظیفه**: مدیریت نقل‌قول‌ها و فاکتورها  
**فایل‌های اصلی**:
- `QuoteService.php` - مدیریت نقل‌قول
- `InvoiceService.php` - مدیریت فاکتور

#### 4. **Social Media Services**
**وظیفه**: انتشار در شبکه‌های اجتماعی  
**فایل‌های اصلی**:
- `SocialPublishService.php` - انتشار

#### 5. **Email Services**
**وظیفه**: ارسال ایمیل‌ها  
**فایل‌های اصلی**:
- `EmailService.php` - ارسال ایمیل

---

## 📈 نقشه و روابط

### معماری کلی سیستم

```
┌─────────────────────────────────────────────────────────────┐
│                     NAVRACAR SYSTEM                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────┐                 ┌──────────────────┐   │
│  │   FRONTEND   │                 │  MOBILE APP      │   │
│  │   (Vue/JS)   │                 │  (Capacitor)     │   │
│  └──────┬───────┘                 └────────┬─────────┘   │
│         │                                  │              │
│         └──────────────┬───────────────────┘              │
│                        ↓                                   │
│  ┌────────────────────────────────────────────────────┐   │
│  │          LARAVEL BACKEND ROUTES                   │   │
│  ├────────────────────────────────────────────────────┤   │
│  │  • routes/public.php   (Public Pages)            │   │
│  │  • routes/admin.php    (Admin CRM)               │   │
│  │  • routes/api.php      (APIs)                    │   │
│  │  • routes/auth.php     (Authentication)          │   │
│  └────────────────────────────────────────────────────┘   │
│                        ↓                                   │
│  ┌────────────────────────────────────────────────────┐   │
│  │         HTTP CONTROLLERS (30 Controllers)         │   │
│  ├────────────────────────────────────────────────────┤   │
│  │  Admin/ ─────┬── RequestController (CRM)         │   │
│  │              ├── CarListingController            │   │
│  │              ├── InvoiceController               │   │
│  │              ├── PostController (Blog)           │   │
│  │              ├── KanbanController                │   │
│  │              ├── UserController                  │   │
│  │              └── 14 more...                      │   │
│  │                                                  │   │
│  │  Public/ ────┬── HomeController                  │   │
│  │              ├── CalculatorController            │   │
│  │              ├── CarPriceController              │   │
│  │              ├── QuoteController                 │   │
│  │              ├── BlogController                  │   │
│  │              └── 5 more...                       │   │
│  └────────────────────────────────────────────────────┘   │
│                        ↓                                   │
│  ┌────────────────────────────────────────────────────┐   │
│  │      BUSINESS LOGIC & SERVICES (app/Services)    │   │
│  ├────────────────────────────────────────────────────┤   │
│  │  • VehiclePricingEngine  (محاسبه قیمت)          │   │
│  │  • ImportService         (وارداتی)              │   │
│  │  • QuoteService          (نقل‌قول)             │   │
│  │  • InvoiceService        (فاکتور)               │   │
│  │  • SocialPublishService  (شبکه‌های اجتماعی)    │   │
│  │  • EmailService          (ایمیل)               │   │
│  └────────────────────────────────────────────────────┘   │
│                        ↓                                   │
│  ┌────────────────────────────────────────────────────┐   │
│  │    DATABASE MODELS (17 Models) & Eloquent ORM    │   │
│  ├────────────────────────────────────────────────────┤   │
│  │  ┌─ AdminUser             ┌─ CarListing          │   │
│  │  ├─ QuoteRequest ────┬───┤ ├─ CarListingImage    │   │
│  │  ├─ Invoice      ────┤   ├─ Post                 │   │
│  │  ├─ LeadActivity ────┤   ├─ HomeSlide            │   │
│  │  ├─ PipelineStage ───┤   ├─ MenuItem             │   │
│  │  ├─ MessageTemplate  │   ├─ Setting              │   │
│  │  ├─ CalculationLog   │   ├─ VinCheck             │   │
│  │  ├─ ImportQueueItem  │   ├─ LossReason           │   │
│  │  └─ BrowserExtPair ──┘   └─ (Relationships)      │   │
│  └────────────────────────────────────────────────────┘   │
│                        ↓                                   │
│  ┌────────────────────────────────────────────────────┐   │
│  │         DATABASE (PostgreSQL/MySQL)              │   │
│  ├────────────────────────────────────────────────────┤   │
│  │  • admin_users          • posts                   │   │
│  │  • quote_requests       • home_slides             │   │
│  │  • invoices             • menu_items              │   │
│  │  • lead_activities      • calculation_logs        │   │
│  │  • pipeline_stages      • vin_checks              │   │
│  │  • message_templates    • loss_reasons            │   │
│  │  • car_listings         • browser_extension...    │   │
│  │  • car_listing_images   • import_queue            │   │
│  │  • settings             • (+ Junction Tables)     │   │
│  └────────────────────────────────────────────────────┘   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### جریان داده برای "درخواست نقل‌قول" (Quote Request Flow)

```
┌────────────────────────────────────────────────┐
│  1. کاربر سایت را ملاقات می‌کند             │
│     (صفحه Calculator)                        │
└────────────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────────────┐
│  2. محاسبه قیمت                              │
│     POST /vehicle-pricing/calculate          │
│     → VehiclePricingController               │
│     → VehiclePricingEngine                   │
│     → CalculationLog Model                   │
└────────────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────────────┐
│  3. ثبت درخواست نقل‌قول                    │
│     POST /quote-requests                     │
│     → QuoteController@store                  │
│     → QuoteRequest Model                     │
│     → Email ارسال                            │
└────────────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────────────┐
│  4. مدیریتی درخواست را می‌بیند             │
│     Admin Dashboard                          │
│     → RequestController@index                │
│     → Kanban Board                           │
└────────────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────────────┐
│  5. مدیر درخواست را پردازش می‌کند          │
│     • تخصیص: assign                         │
│     • دما: temperature (Hot/Warm/Cold)      │
│     • فاز: change stage                      │
│     • فعالیت: add activity                   │
│     → LeadActivity Model                     │
└────────────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────────────┐
│  6. ایجاد فاکتور Proforma                    │
│     POST /admin/invoices                     │
│     → InvoiceController@store                │
│     → Invoice Model                          │
│     → QuoteRequest.invoices()                │
└────────────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────────────┐
│  7. دانلود PDF                                │
│     GET /admin/invoices/{id}/pdf             │
│     → InvoiceController@downloadPdf          │
│     → resources/views/pdf/proforma.blade.php │
└────────────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────────────┐
│  8. بستن درخواست                            │
│     POST /admin/requests/{id}/close          │
│     → RequestController@close                │
│     → PipelineStage = "Closed"               │
│     → LeadActivity: "Closed"                 │
└────────────────────────────────────────────────┘
```

### جریان داده برای "خودروها" (Car Listing Flow)

```
┌────────────────────────────────────────────────┐
│  1. ایجاد لیست خودرو (دستی)                │
│     POST /admin/car-listings/store-manual    │
│     → CarListingController@storeManual       │
│     → CarListing Model                       │
└────────────────────────────────────────────────┘
          ↓                           ↓
    ┌─────────────┐          ┌──────────────────┐
    │   یا        │          │  2. واردات       │
    └─────────────┘          │  از منابع خارجی  │
          ↓                  │  browser capture │
    ┌─────────────────────────────────────────┐
    │  POST /admin/car-listings/import      │
    │  → CarListingController@import        │
    │  → ImportQueueItem Model              │
    │  → BrowserCaptureService              │
    └─────────────────────────────────────────┘
                    ↓
    ┌─────────────────────────────────────────┐
    │  3. ویرایش جزئیات و تصاویر            │
    │  PUT /admin/car-listings/{id}         │
    │  POST /admin/car-listings/{id}/images │
    │  → CarListingController@update/storeImage
    │  → CarListing + CarListingImage       │
    └─────────────────────────────────────────┘
                    ↓
    ┌─────────────────────────────────────────┐
    │  4. انتشار (درج در سایت عمومی)       │
    │  POST /admin/car-listings/{id}/publish│
    │  → CarListing.is_published = true    │
    │  → نمایش در /car-prices             │
    └─────────────────────────────────────────┘
                    ↓
    ┌─────────────────────────────────────────┐
    │  5. انتشار در شبکه‌های اجتماعی       │
    │  POST /admin/car-listings/{id}/...   │
    │  publish-social                      │
    │  → SocialPublishService              │
    │  → Instagram / Twitter / etc         │
    └─────────────────────────────────────────┘
                    ↓
    ┌─────────────────────────────────────────┐
    │  6. مشاهده عمومی                      │
    │  GET /car-prices/{slug}              │
    │  → CarPriceController@show           │
    │  → resources/views/public/show.blade │
    └─────────────────────────────────────────┘
```

---

## 📝 راهنمای کار

### 🔄 **چگونگی اضافه‌کردن صفحه جدید؟**

**مثال**: اضافه‌کردن صفحه "مقایسه خودروها"

1. **Route اضافه کنید** (`routes/public.php` یا `routes/admin.php`):
   ```php
   Route::get('/compare', [CompareController::class, 'index'])->name('public.compare');
   Route::post('/compare', [CompareController::class, 'store'])->name('public.compare.store');
   ```

2. **Controller درست کنید** (`app/Http/Controllers/Public/CompareController.php`):
   ```php
   public function index() { ... }
   public function store() { ... }
   ```

3. **View درست کنید** (`resources/views/public/compare.blade.php`)

4. **اگر نیاز به Model هست**:
   - Model درست کنید: `php artisan make:model Compare`
   - Migration درست کنید: `php artisan make:migration create_compares_table`

5. **اگر نیاز به Service هست**:
   - Service درست کنید: `app/Services/CompareService.php`

6. **اگر نیاز به Email هست**:
   - Email درست کنید: `resources/views/emails/compare.blade.php`

7. **این مستند را به‌روز کنید**:
   - این فایل را نوشته‌اش دانلود کنید
   - صفحه جدید را به جدول Routes اضافه کنید
   - اگر Model جدید هست، به بخش Models اضافه کنید

### 🔍 **چگونه یک صفحه موجود را پیدا کنم؟**

**مثال**: می‌خواهم صفحه "مدیریت فاکتورها" را تغییر دهم

1. **نام صفحه را در جدول Routes پیدا کنید**:
   ```
   /admin/invoices → InvoiceController@index
   ```

2. **Controller را باز کنید**:
   ```
   app/Http/Controllers/Admin/InvoiceController.php
   ```

3. **View را باز کنید**:
   ```
   resources/views/admin/invoices/index.blade.php
   ```

4. **اگر نیاز به Database است**:
   ```
   app/Models/Invoice.php
   ```

5. **تغییرات خود را انجام دهید و این مستند را به‌روز کنید**

### 🛡️ **سطح دسترسی (Roles)**

سه سطح نقش وجود دارد:

1. **admin** (مدیر کامل):
   - تمام بخش‌های مدیریتی
   - مدیریت کاربران
   - تنظیمات سیستم

2. **sales** (کارشناس فروش):
   - مدیریت درخواست‌ها
   - مدیریت فاکتورها
   - Kanban Board
   - فقط درخواست‌های خود را می‌بیند

3. **content** (مدیر محتوا):
   - مدیریت خودروها
   - مدیریت مقالات
   - مدیریت منو
   - مدیریت اسلاید‌های صفحه‌ی اول

### ⏱️ **Rate Limiting**

برخی از Routes محدودیت ترافیک دارند:

```php
Route::post('/vehicle-pricing/calculate', ...) // 60 per minute
Route::post('/quote-requests', ...)             // 5 per minute
Route::post('/vin-checks', ...)                 // 30 per minute
Route::post('/calculation-logs', ...)           // 30 per minute
Route::post('/lead-form', ...)                  // 5 per minute
```

### 🔌 **APIs کلیدی**

#### محاسبه قیمت:
```php
POST /vehicle-pricing/calculate
Body: {
  category: "sedan",
  make: "BMW",
  model: "X5",
  year: 2020
}
Response: {
  breakdown: [...],
  totals: {...}
}
```

#### ثبت درخواست:
```php
POST /quote-requests
Body: {
  name, phone, email, car_label,
  category, breakdown_json, totals_json
}
```

#### بررسی VIN:
```php
POST /vin-checks
Body: { vin: "..." }
Response: { vin, result_json }
```

---

## 📋 چک‌لیست برای هر تغییر

- [ ] **توضیح مختصر**: تغییر چیست؟
- [ ] **Route**: آدرس جدید/تغییر‌یافته را مشخص کنید
- [ ] **Controller**: Controller درست شد یا تغییر کرد
- [ ] **Model**: Model جدید/تغییر درست شد
- [ ] **View**: View درست/تغییر شد
- [ ] **Database**: اگر schema تغییر کرد، migration نوشته شد
- [ ] **Service**: اگر logic پیچیده است، service درست شد
- [ ] **Test**: تست نوشته شد
- [ ] **مستند**: این فایل به‌روز شد
- [ ] **Git**: کامیت واضح با پیام مناسب

---

**نوشته**: این مستند برای هر تغییر به‌روز می‌شود تا همیشه مرجع دقیقی داشته باشیم.

