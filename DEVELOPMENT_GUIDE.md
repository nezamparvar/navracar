# 👨‍💻 Development Guide | Navracar

**نسخه**: 1.0  
**تاریخ آخرین به‌روزرسانی**: 17 آگوست 2026  
**مقصد**: راهنمای کاری برای developers

---

## 📑 فهرست

1. [Setup و شروع](#setup-و-شروع)
2. [Folder Structure](#folder-structure)
3. [Coding Standards](#coding-standards)
4. [Git Workflow](#git-workflow)
5. [Testing](#testing)
6. [Performance](#performance)
7. [Troubleshooting](#troubleshooting)

---

## 🚀 Setup و شروع

### پیش‌نیاز‌ها
```bash
# System Requirements
• PHP 8.1+
• Composer
• Node.js 16+
• npm / yarn
• PostgreSQL 13+ یا MySQL 8+
• Redis (برای cache/queue)
```

### 1. Clone Repository
```bash
git clone https://github.com/nezamparvar/navracar.git
cd navracar
```

### 2. Install Dependencies
```bash
# PHP dependencies
composer install

# JavaScript dependencies
npm install

# یا yarn
yarn install
```

### 3. Environment Setup
```bash
# کپی .env
cp .env.example .env

# تنظیمات پایگاه‌داده
# ویرایش .env و تنظیم:
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=navracar
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 4. Generate Key
```bash
php artisan key:generate
```

### 5. Database Setup
```bash
# ایجاد جداول
php artisan migrate

# اضافه کردن داده‌های نمونه (optional)
php artisan db:seed
```

### 6. Build Assets
```bash
# Development
npm run dev

# Production
npm run build

# Watch (توسعه)
npm run watch
```

### 7. Start Server
```bash
# Laravel Development Server
php artisan serve

# یا استفاده از Homestead/Docker

# دسترسی
http://localhost:8000
```

---

## 📁 Folder Structure

```
navracar/
├── app/
│   ├── Console/                 # Artisan Commands
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin Controllers (20+)
│   │   │   └── Public/         # Public Controllers (10+)
│   │   ├── Middleware/         # Custom Middleware
│   │   └── Requests/           # Form Requests / Validation
│   ├── Models/                  # Eloquent Models (17)
│   ├── Policies/                # Authorization Policies
│   ├── Services/                # Business Logic Services
│   │   ├── VehiclePricing/     # قیمت‌گذاری
│   │   ├── Capture/            # واردات خودروها
│   │   ├── Import/             # Import Services
│   │   └── ...
│   ├── Mail/                    # Mailable Classes
│   └── Providers/               # Service Providers
│
├── routes/
│   ├── web.php                  # Web routes (empty)
│   ├── api.php                  # API routes (empty)
│   ├── admin.php                # Admin routes (160+ lines)
│   ├── public.php               # Public routes (41 routes)
│   ├── auth.php                 # Auth routes
│   └── console.php              # Console commands
│
├── resources/
│   ├── views/
│   │   ├── components/          # Blade Components (12+)
│   │   │   └── layouts/         # Layouts (admin, public)
│   │   ├── admin/               # Admin Views (20+)
│   │   ├── public/              # Public Views (10+)
│   │   ├── auth/                # Auth Views
│   │   ├── emails/              # Email Templates
│   │   └── pdf/                 # PDF Templates
│   ├── js/
│   │   ├── app.js               # Bootstrap
│   │   └── components/          # Vue/React Components
│   ├── css/
│   │   ├── app.css              # Main styles
│   │   └── tailwind.css         # Tailwind config
│   └── lang/
│       └── fa/                  # Persian translations
│
├── database/
│   ├── migrations/              # Database migrations (27+)
│   ├── seeders/                 # Database seeders
│   └── factories/               # Model factories
│
├── tests/
│   ├── Feature/                 # Feature tests
│   ├── Unit/                    # Unit tests
│   └── TestCase.php             # Base test class
│
├── storage/
│   ├── app/                     # Application storage
│   │   ├── public/              # Public files
│   │   └── uploads/             # User uploads
│   ├── logs/                    # Log files
│   └── framework/               # Framework cache
│
├── config/
│   ├── app.php                  # App config
│   ├── database.php             # Database config
│   ├── queue.php                # Queue config
│   ├── mail.php                 # Mail config
│   ├── auth.php                 # Auth config
│   └── navaracar.php            # Custom config
│
└── public/
    ├── index.php                # Entry point
    ├── css/                     # Compiled CSS
    ├── js/                      # Compiled JS
    └── images/                  # Static images
```

---

## 🎯 Coding Standards

### PHP Code Style
```php
// ✅ Proper
class QuoteRequestController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,closed,lost',
        ]);

        return view('admin.requests.index', [
            'requests' => QuoteRequest::query()
                ->where('deleted_at', null)
                ->get(),
        ]);
    }
}

// ❌ Avoid
class QuoteRequestController {
    function index($req) {
        $status = $req->get('status');
        $requests = DB::table('quote_requests')->where('status', $status)->get();
        return view('admin.requests.index', compact('requests'));
    }
}
```

### Naming Conventions
```php
// Controllers
class QuoteRequestController      // ✅
class RequestController           // ✅
class Request_Handler             // ❌

// Models
class QuoteRequest                // ✅
class quote_request               // ❌

// Methods
public function updateStatus()    // ✅
public function update_status()   // ❌
public function updatestatus()    // ❌

// Variables
$totalAmount                      // ✅
$total_amount                     // ❌
$t                               // ❌

// Constants
const ADMIN_ROLE = 'admin'        // ✅
const adminRole = 'admin'         // ❌
```

### Blade Templates
```blade
{{-- ✅ Proper --}}
<x-card title="درخواست‌ها" class="mb-6">
  @foreach ($requests as $request)
    <tr>
      <td>{{ $request->name }}</td>
      <td><x-badge color="blue">{{ $request->stage->name }}</x-badge></td>
    </tr>
  @endforeach
</x-card>

{{-- ❌ Avoid --}}
<div>
  @foreach ($requests as $r)
    <tr><td>{{ $r->name }}</td><td>{{ $r->stage }}</td></tr>
  @endforeach
</div>
```

### Comments
```php
// ✅ Meaningful
public function calculatePrice($category, $make)
{
    // ضریب قیمت براساس برند و دسته‌بندی
    $coefficient = $this->getCoefficient($category, $make);
    return $basePrice * $coefficient;
}

// ❌ Obvious
// این متد قیمت را محاسبه می‌کند
public function calculatePrice($category, $make)
{
    // متغیر coefficient
    $coefficient = $this->getCoefficient($category, $make);
    // برگردان قیمت
    return $basePrice * $coefficient;
}
```

---

## 🌳 Git Workflow

### Branch Naming
```bash
# Features
git checkout -b feature/add-export-functionality

# Bug fixes
git checkout -b fix/quote-calculation-error

# Refactoring
git checkout -b refactor/simplify-pricing-logic

# Documentation
git checkout -b docs/api-documentation
```

### Commit Messages
```bash
# ✅ Good
git commit -m "🎯 افزودن تابع محاسبهٔ قیمت جدید

- محاسبهٔ تعرفهٔ گمرکی دقیق‌تر
- اضافه کردن ضریب برای هر دسته
- تست‌های واحد برای verify کردن

fixes #123"

# ❌ Bad
git commit -m "update"
git commit -m "fix bugs"
git commit -m "asdfghjk"
```

### Pull Request Process
```bash
1. ایجاد branch
   git checkout -b feature/my-feature

2. کار کردن روی تغییرات
   git add .
   git commit -m "..."

3. Push به remote
   git push -u origin feature/my-feature

4. ایجاد PR در GitHub

5. Code Review

6. Merge (بعد از approval)

7. حذف branch
   git branch -d feature/my-feature
```

---

## ✅ Testing

### Running Tests
```bash
# تمام تست‌ها
php artisan test

# فقط Feature tests
php artisan test --filter Feature

# فقط Unit tests
php artisan test --filter Unit

# با Coverage
php artisan test --coverage

# Watch mode
php artisan test --watch
```

### Writing Tests
```php
// ✅ Feature Test
namespace Tests\Feature;

class QuoteRequestTest extends TestCase
{
    public function test_can_create_quote_request()
    {
        $response = $this->post('/quote-requests', [
            'name' => 'محمد احمدی',
            'email' => 'test@example.com',
            'phone' => '+989121234567',
            'car_label' => 'BMW 3 Series 2020',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('quote_requests', [
            'email' => 'test@example.com',
        ]);
    }
}

// ✅ Unit Test
namespace Tests\Unit;

class PricingCalculatorTest extends TestCase
{
    public function test_calculate_with_customs_tariff()
    {
        $calculator = new VehiclePricingCalculator();
        
        $result = $calculator->calculate(
            category: 'sedan',
            make: 'BMW',
            basePrice: 450_000_000
        );

        $this->assertEquals(756_000_000, $result['total']);
    }
}
```

---

## ⚡ Performance

### Optimization Tips

#### 1. **Database Queries**
```php
// ❌ N+1 Problem
$requests = QuoteRequest::all();
foreach ($requests as $request) {
    echo $request->assignee->name; // هر بار query
}

// ✅ Eager Loading
$requests = QuoteRequest::with('assignee')->get();
foreach ($requests as $request) {
    echo $request->assignee->name; // از memory
}
```

#### 2. **Caching**
```php
// ✅ Cache Results
$categories = Cache::remember('pricing_categories', 3600, function () {
    return VehiclePricingCatalog::CATEGORIES;
});

// ✅ Cache Invalidation
Cache::forget('pricing_categories');
```

#### 3. **Indexes**
```sql
-- ✅ درست
CREATE INDEX idx_quote_requests_email ON quote_requests(email);
CREATE INDEX idx_car_listings_is_published ON car_listings(is_published);

-- ❌ غیرضروری
CREATE INDEX idx_everything ON quote_requests(id, name, email, phone);
```

#### 4. **API Throttling**
```php
// در routes
Route::post('/vehicle-pricing/calculate', ...)
    ->middleware('throttle:60,1');  // 60 per minute
```

---

## 🐛 Troubleshooting

### Common Issues

#### 1. **Migration Errors**
```bash
# مشکل: Migration locked
# حل:
php artisan migrate:reset --env=local
php artisan migrate

# یا
php artisan migrate:rollback
php artisan migrate
```

#### 2. **Permission Denied**
```bash
# مشکل: storage files
# حل:
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 3. **Composer Issues**
```bash
# مشکل: conflicts
# حل:
composer install --no-interaction --prefer-dist

# یا clear cache
composer clear-cache
composer install
```

#### 4. **Database Connection**
```bash
# مشکل: Can't connect
# چک کنید:
1. .env فایل تنظیمات
2. Database آپ است؟
3. PostgreSQL/MySQL service running?

# حل:
php artisan tinker
>>> DB::connection()->getPdo();
```

#### 5. **Front-end Build Issues**
```bash
# مشکل: npm errors
# حل:
rm -rf node_modules package-lock.json
npm install
npm run build
```

---

## 📋 Before Committing

### Checklist
- [ ] تمام تست‌ها pass می‌کنند
- [ ] کد لینت است (`npm run lint`)
- [ ] Database migrations نوشته شد (اگر schema تغییر کرد)
- [ ] SITE_DOCUMENTATION.md به‌روز شد
- [ ] کامنت‌های غیرضروری حذف شدند
- [ ] Console.log/dd/dump حذف شدند
- [ ] Environment variables تنظیم شدند
- [ ] Security checks انجام شدند

---

## 🔐 Security Checklist

```php
// ✅ CSRF Protection
<form method="POST">
    @csrf
    ...
</form>

// ✅ SQL Injection Prevention
DB::table('table')->where('column', $value)->get();

// ✅ XSS Prevention
{{ $userInput }}  // Escaped by default
{!! $htmlContent !!}  // Only if safe

// ✅ File Upload Security
$request->file('image')
    ->storeAs('public/images', 'filename.jpg')
    ->validate(['mimes:jpg,png', 'max:5000']);

// ✅ Rate Limiting
Route::post('/api/..')->middleware('throttle:10,1');
```

---

## 📚 Resources

### Documentation Links
- [Laravel Docs](https://laravel.com/docs)
- [Eloquent ORM](https://laravel.com/docs/eloquent)
- [Blade Templates](https://laravel.com/docs/blade)
- [Testing](https://laravel.com/docs/testing)

### Internal Documentation
- `SITE_DOCUMENTATION.md` - تمام صفحات و routes
- `COMPONENTS_DOCUMENTATION.md` - UI Components
- `API_DOCUMENTATION.md` - API Endpoints
- `DATABASE_SCHEMA.md` - Database Tables

---

**نوشته**: این guide باید برای workflow changes به‌روز شود.

