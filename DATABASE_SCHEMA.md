# 🗄️ Database Schema Documentation | Navracar

**نسخه**: 1.0  
**نوع Database**: PostgreSQL / MySQL  
**تاریخ آخرین به‌روزرسانی**: 17 آگوست 2026  
**مکان**: `database/migrations/`

---

## 📑 فهرست

1. [نمای کلی](#نمای-کلی)
2. [جداول اصلی](#جداول-اصلی)
3. [Foreign Keys و Relationships](#foreign-keys-و-relationships)
4. [Indexes](#indexes)
5. [نمونهٔ Queries](#نمونهٔ-queries)

---

## 🎯 نمای کلی

### آمار
```
• 20 جدول
• 150+ ستون
• 15+ Foreign Keys
• 30+ Indexes
• Soft Deletes: Enabled
```

### ارتباطات
```
┌─ AdminUser ─────────┐
│                     │
├── assigned_to ──┐   ├── created_by ──┐
│                 │   │                │
└─ QuoteRequest ──┤   ├─ LeadActivity │
   │              │   │               │
   ├─ Invoice     │   └─ (Self)       │
   ├─ LeadActivity│
   └─ PipelineStage
     │
     └─ CarListing
        └─ CarListingImage
```

---

## 📋 جداول اصلی

### 1. `admin_users`
**نوع**: کاربران مدیریتی  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `name` | string(255) | ✗ | - | نام کاربر |
| `email` | string(255) | ✗ | - | ایمیل (Unique) |
| `password` | string(255) | ✗ | - | رمز عبور (hashed) |
| `role` | enum | ✗ | 'sales' | نقش: admin \| sales \| content |
| `is_active` | boolean | ✗ | true | فعال/غیرفعال |
| `created_at` | timestamp | ✓ | NULL | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |

**Indexes**:
```sql
CREATE UNIQUE INDEX idx_admin_users_email ON admin_users(email);
CREATE INDEX idx_admin_users_role ON admin_users(role);
CREATE INDEX idx_admin_users_active ON admin_users(is_active);
```

**نمونهٔ ایجاد**:
```sql
INSERT INTO admin_users (name, email, password, role)
VALUES ('محمد احمدی', 'mohammad@navracar.ir', bcrypt('password123'), 'admin');
```

---

### 2. `quote_requests`
**نوع**: درخواست‌های نقل‌قول  
**Primary Key**: `id` (bigint)  
**Soft Deletes**: ✅ Yes (`deleted_at`)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `name` | string(255) | ✗ | - | نام مشتری |
| `phone` | string(20) | ✗ | - | شمارهٔ تلفن |
| `email` | string(255) | ✗ | - | ایمیل |
| `car_label` | string(255) | ✓ | NULL | برچسب خودرو |
| `category` | string(100) | ✓ | NULL | دسته‌بندی |
| `temperature` | enum | ✗ | 'warm' | hot \| warm \| cold |
| `breakdown_json` | json | ✓ | NULL | تفکیک هزینه‌ها |
| `totals_json` | json | ✓ | NULL | مجموع‌ها و فیلدهای محاسبه |
| `total_with_profit` | bigint | ✓ | NULL | کل با سود |
| `email_sent` | boolean | ✗ | false | ایمیل ارسال شد |
| `source` | string(100) | ✓ | NULL | منبع (calculator, website) |
| `budget_range` | string(100) | ✓ | NULL | بودجه |
| `country` | string(50) | ✗ | 'IR' | کشور |
| `city` | string(100) | ✓ | NULL | شهر |
| `assigned_to` | bigint | ✓ | NULL | FK → admin_users |
| `created_by` | bigint | ✓ | NULL | FK → admin_users |
| `follow_up_status` | string(100) | ✓ | NULL | وضعیت پیگیری |
| `current_stage_id` | bigint | ✓ | NULL | FK → pipeline_stages |
| `loss_reason` | string(255) | ✓ | NULL | دلیل از‌دست‌رفتن |
| `next_call_date` | date | ✓ | NULL | تاریخ تماس بعدی |
| `ip_address` | string(45) | ✓ | NULL | آدرس IP متقاضی |
| `is_archived` | boolean | ✗ | false | آرشیو شده |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |
| `deleted_at` | timestamp | ✓ | NULL | تاریخ حذف نرم |

**Indexes**:
```sql
CREATE INDEX idx_quote_requests_email ON quote_requests(email);
CREATE INDEX idx_quote_requests_assigned_to ON quote_requests(assigned_to);
CREATE INDEX idx_quote_requests_stage ON quote_requests(current_stage_id);
CREATE INDEX idx_quote_requests_created_at ON quote_requests(created_at);
CREATE INDEX idx_quote_requests_temperature ON quote_requests(temperature);
```

**JSON Structure** (breakdown_json):
```json
[
  {
    "key": "base_price",
    "label": "قیمت پایه",
    "amount": 450000000
  },
  {
    "key": "customs_tariff",
    "label": "تعرفهٔ گمرکی",
    "amount": 180000000
  }
]
```

**JSON Structure** (totals_json):
```json
{
  "display": {
    "subtotal": 630000000,
    "vat": 126000000,
    "total": 756000000
  },
  "pricing_snapshot": {
    "base_price": 450000000,
    "calculated_at": "2026-08-17T15:30:00Z"
  }
}
```

---

### 3. `invoices`
**نوع**: فاکتورهای Proforma  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `request_id` | bigint | ✗ | - | FK → quote_requests |
| `number` | string(100) | ✗ | - | شمارهٔ فاکتور |
| `breakdown_json` | json | ✓ | NULL | تفکیک هزینه‌ها |
| `totals_json` | json | ✓ | NULL | مجموع‌ها |
| `status` | enum | ✗ | 'draft' | draft \| sent \| viewed |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |

**Indexes**:
```sql
CREATE UNIQUE INDEX idx_invoices_number ON invoices(number);
CREATE INDEX idx_invoices_request_id ON invoices(request_id);
CREATE INDEX idx_invoices_status ON invoices(status);
```

---

### 4. `car_listings`
**نوع**: فهرست خودروها  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `make` | string(100) | ✗ | - | برند (BMW, Toyota) |
| `model` | string(100) | ✗ | - | مدل |
| `year` | integer | ✗ | - | سال تولید |
| `price` | bigint | ✗ | - | قیمت |
| `customs_price` | bigint | ✓ | NULL | قیمت گمرکی |
| `description` | text | ✓ | NULL | توضیح |
| `slug` | string(255) | ✗ | - | URL slug (Unique) |
| `is_published` | boolean | ✗ | false | منتشر شده |
| `mileage` | integer | ✓ | NULL | کیلومتراژ |
| `category` | string(100) | ✓ | NULL | دسته‌بندی |
| `color` | string(50) | ✓ | NULL | رنگ |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |

**Indexes**:
```sql
CREATE UNIQUE INDEX idx_car_listings_slug ON car_listings(slug);
CREATE INDEX idx_car_listings_is_published ON car_listings(is_published);
CREATE INDEX idx_car_listings_make ON car_listings(make);
CREATE INDEX idx_car_listings_year ON car_listings(year);
CREATE INDEX idx_car_listings_price ON car_listings(price);
```

---

### 5. `car_listing_images`
**نوع**: تصاویر خودروها  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `car_listing_id` | bigint | ✗ | - | FK → car_listings |
| `path` | string(500) | ✗ | - | مسیر فایل |
| `order` | integer | ✗ | 0 | ترتیب نمایش |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |

**Indexes**:
```sql
CREATE INDEX idx_car_listing_images_car_id ON car_listing_images(car_listing_id);
CREATE INDEX idx_car_listing_images_order ON car_listing_images(order);
```

---

### 6. `lead_activities`
**نوع**: فعالیت‌های درخواست  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `request_id` | bigint | ✗ | - | FK → quote_requests |
| `type` | enum | ✗ | - | call \| email \| note \| status_change |
| `description` | text | ✓ | NULL | متن فعالیت |
| `created_by` | bigint | ✓ | NULL | FK → admin_users |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |

**Indexes**:
```sql
CREATE INDEX idx_lead_activities_request_id ON lead_activities(request_id);
CREATE INDEX idx_lead_activities_type ON lead_activities(type);
CREATE INDEX idx_lead_activities_created_at ON lead_activities(created_at);
```

---

### 7. `pipeline_stages`
**نوع**: مراحل Pipeline CRM  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `name` | string(100) | ✗ | - | نام مرحله |
| `order` | integer | ✗ | 0 | ترتیب نمایش |
| `color` | string(7) | ✓ | '#3b82f6' | رنگ Hex |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |

**Indexes**:
```sql
CREATE INDEX idx_pipeline_stages_order ON pipeline_stages(order);
```

**نمونهٔ داده‌ها**:
```sql
INSERT INTO pipeline_stages (name, order, color) VALUES
('جدید', 1, '#3b82f6'),
('در تماس', 2, '#10b981'),
('ارائه شده', 3, '#f59e0b'),
('پیش‌فاکتور', 4, '#8b5cf6'),
('تکمیل شده', 5, '#6b7280'),
('از‌دست رفته', 6, '#ef4444');
```

---

### 8. `posts`
**نوع**: مقالات Blog  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `title` | string(255) | ✗ | - | عنوان |
| `slug` | string(255) | ✗ | - | URL slug (Unique) |
| `content` | longtext | ✗ | - | محتوای مقاله |
| `excerpt` | text | ✓ | NULL | خلاصهٔ کوتاه |
| `featured_image` | string(500) | ✓ | NULL | تصویر شاخص |
| `is_published` | boolean | ✗ | false | منتشر شده |
| `published_at` | timestamp | ✓ | NULL | تاریخ انتشار |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |

**Indexes**:
```sql
CREATE UNIQUE INDEX idx_posts_slug ON posts(slug);
CREATE INDEX idx_posts_is_published ON posts(is_published);
CREATE INDEX idx_posts_published_at ON posts(published_at);
```

---

### 9. `home_slides`
**نوع**: اسلاید‌های صفحهٔ اول  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `title` | string(255) | ✓ | NULL | عنوان |
| `description` | text | ✓ | NULL | توضیح |
| `image` | string(500) | ✗ | - | مسیر تصویر |
| `link` | string(500) | ✓ | NULL | لینک |
| `is_active` | boolean | ✗ | true | فعال |
| `order` | integer | ✗ | 0 | ترتیب نمایش |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |

---

### 10. `menu_items`
**نوع**: منوی ناویگیشن  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `label` | string(255) | ✗ | - | برچسب منو |
| `url` | string(500) | ✗ | - | مسیر URL |
| `icon` | string(100) | ✓ | NULL | کلاس آیکون |
| `order` | integer | ✗ | 0 | ترتیب نمایش |
| `is_active` | boolean | ✗ | true | فعال |
| `parent_id` | bigint | ✓ | NULL | FK → menu_items (زیرمنو) |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |

---

### 11. `message_templates`
**نوع**: تمپلیت‌های پیام  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `title` | string(255) | ✗ | - | عنوان تمپلیت |
| `content` | text | ✗ | - | محتوا |
| `is_active` | boolean | ✗ | true | فعال |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |

---

### 12. `calculation_logs`
**نوع**: لاگ‌های محاسبات  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `car_label` | string(255) | ✓ | NULL | برچسب خودرو |
| `category` | string(100) | ✓ | NULL | دسته‌بندی |
| `breakdown_json` | json | ✓ | NULL | تفکیک هزینه‌ها |
| `totals_json` | json | ✓ | NULL | مجموع‌ها |
| `ip_address` | string(45) | ✓ | NULL | آدرس IP |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |

**Indexes**:
```sql
CREATE INDEX idx_calculation_logs_created_at ON calculation_logs(created_at);
CREATE INDEX idx_calculation_logs_ip ON calculation_logs(ip_address);
```

---

### 13. `vin_checks`
**نوع**: بررسی‌های VIN  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `vin` | string(17) | ✗ | - | کدِ VIN |
| `result_json` | json | ✓ | NULL | نتیجهٔ بررسی |
| `ip_address` | string(45) | ✓ | NULL | آدرس IP |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |

---

### 14. `loss_reasons`
**نوع**: دلایل از‌دست‌رفتن درخواست  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `reason` | string(255) | ✗ | - | دلیل |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |

---

### 15. `import_queue`
**نوع**: صف وارداتی خودروها  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `data_json` | json | ✗ | - | داده‌های خودرو |
| `status` | enum | ✗ | 'pending' | pending \| processing \| completed \| failed |
| `source_platform` | string(100) | ✓ | NULL | منبع (Dubizzle, DubiCars, etc) |
| `capture_method` | string(100) | ✓ | NULL | روش (browser_capture, api, manual) |
| `customs_price` | bigint | ✓ | NULL | قیمت گمرکی |
| `review_state` | string(100) | ✓ | NULL | وضعیت بررسی |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |

---

### 16. `browser_extension_pairings`
**نوع**: جفت‌شدگی افزونهٔ مرورگر  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `admin_user_id` | bigint | ✗ | - | FK → admin_users |
| `token` | string(255) | ✗ | - | Token احراز (Unique) |
| `device_name` | string(255) | ✓ | NULL | نام دستگاه |
| `last_used_at` | timestamp | ✓ | NULL | آخرین استفاده |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |

---

### 17. `settings`
**نوع**: تنظیمات سایت  
**Primary Key**: `id` (bigint)  

| ستون | نوع | nullable | پیش‌فرض | توضیح |
|------|------|----------|---------|--------|
| `id` | bigint | ✗ | - | شناسهٔ منحصر |
| `key` | string(255) | ✗ | - | کلید تنظیم (Unique) |
| `value` | longtext | ✓ | NULL | مقدار |
| `created_at` | timestamp | ✗ | NOW() | تاریخ ایجاد |
| `updated_at` | timestamp | ✓ | NULL | تاریخ بروز‌رسانی |

**نمونهٔ داده‌ها**:
```sql
INSERT INTO settings (key, value) VALUES
('app_name', 'Navracar'),
('app_phone', '+989121234567'),
('app_email', 'info@navracar.ir'),
('customs_coefficient', '0.4'),
('vat_percentage', '9'),
('base_service_fee', '50000000');
```

---

## 🔗 Foreign Keys و Relationships

### AdminUser Relations
```sql
ALTER TABLE quote_requests 
ADD FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL;

ALTER TABLE quote_requests 
ADD FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL;

ALTER TABLE lead_activities 
ADD FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL;

ALTER TABLE browser_extension_pairings 
ADD FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE;
```

### QuoteRequest Relations
```sql
ALTER TABLE invoices 
ADD FOREIGN KEY (request_id) REFERENCES quote_requests(id) ON DELETE CASCADE;

ALTER TABLE lead_activities 
ADD FOREIGN KEY (request_id) REFERENCES quote_requests(id) ON DELETE CASCADE;

ALTER TABLE quote_requests 
ADD FOREIGN KEY (current_stage_id) REFERENCES pipeline_stages(id) ON DELETE SET NULL;
```

### CarListing Relations
```sql
ALTER TABLE car_listing_images 
ADD FOREIGN KEY (car_listing_id) REFERENCES car_listings(id) ON DELETE CASCADE;
```

---

## 🗂️ Indexes

### Indexes on Foreign Keys
تمام Foreign Keys خودکار Indexed هستند

### Composite Indexes
```sql
-- برای جستجوی سریع
CREATE INDEX idx_quote_requests_search 
ON quote_requests(assigned_to, current_stage_id, created_at);

-- برای فیلتر کردن
CREATE INDEX idx_car_listings_filter 
ON car_listings(make, year, is_published);
```

### Full Text Search (اختیاری)
```sql
-- برای جستجوی متنی
ALTER TABLE posts 
ADD FULLTEXT INDEX idx_posts_search (title, content);

ALTER TABLE car_listings 
ADD FULLTEXT INDEX idx_car_listings_search (description);
```

---

## 📊 نمونهٔ Queries

### 1. درخواست‌های Hot (فعال)
```sql
SELECT qr.*, 
  au.name as assigned_name,
  ps.name as stage_name
FROM quote_requests qr
LEFT JOIN admin_users au ON qr.assigned_to = au.id
LEFT JOIN pipeline_stages ps ON qr.current_stage_id = ps.id
WHERE qr.temperature = 'hot'
  AND qr.deleted_at IS NULL
  AND qr.is_archived = false
ORDER BY qr.created_at DESC;
```

### 2. درآمد فروش (ماه جاری)
```sql
SELECT SUM(total_with_profit) as revenue,
  COUNT(*) as total_requests,
  COUNT(DISTINCT assigned_to) as team_members
FROM quote_requests
WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
  AND current_stage_id != (SELECT id FROM pipeline_stages WHERE name = 'lost');
```

### 3. خودروهای منتشر شده
```sql
SELECT cl.*,
  COUNT(cli.id) as image_count
FROM car_listings cl
LEFT JOIN car_listing_images cli ON cl.id = cli.car_listing_id
WHERE cl.is_published = true
GROUP BY cl.id
ORDER BY cl.created_at DESC;
```

### 4. فعالیت‌های درخواست
```sql
SELECT la.*,
  qr.name as request_name,
  au.name as created_by_name
FROM lead_activities la
LEFT JOIN quote_requests qr ON la.request_id = qr.id
LEFT JOIN admin_users au ON la.created_by = au.id
WHERE la.request_id = ?
ORDER BY la.created_at DESC;
```

---

## 🛡️ Security & Best Practices

### Prepared Statements
```php
// ✅ درست
$results = DB::table('quote_requests')
  ->where('email', $email)
  ->get();

// ❌ غلط - SQL Injection خطر
$query = "SELECT * FROM quote_requests WHERE email = '$email'";
```

### Soft Deletes
```php
// نمایش تمام
$all = QuoteRequest::withTrashed()->get();

// فقط حذف‌شده‌ها
$deleted = QuoteRequest::onlyTrashed()->get();

// بازگردانی
$restored = QuoteRequest::onlyTrashed()->restore();
```

### Data Validation
```php
$validated = $request->validate([
  'email' => 'required|email|max:255',
  'phone' => 'required|regex:/^[+0-9]{10,}$/',
  'price' => 'required|integer|min:0',
]);
```

---

**نوشته**: این schema documentation باید برای migrations جدید به‌روز شود.

