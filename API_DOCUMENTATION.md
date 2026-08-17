# 🔌 API Documentation | Navracar

**نسخه**: 1.0  
**Base URL**: `https://navracar.ir/api/` یا `https://navracar.ir/`  
**Authentication**: Token-based (for admin APIs)  
**تاریخ آخرین به‌روزرسانی**: 17 آگوست 2026

---

## 📑 فهرست

1. [API عمومی](#api-عمومی)
2. [API مدیریتی](#api-مدیریتی)
3. [Response Format](#response-format)
4. [Error Handling](#error-handling)
5. [Rate Limiting](#rate-limiting)

---

## 📌 API عمومی

### 1️⃣ محاسب‌ه قیمت خودرو

**Endpoint**: `POST /vehicle-pricing/calculate`  
**Rate Limit**: 60 requests/minute  
**Auth**: ❌ No  

**Request Body**:
```json
{
  "category": "sedan",           // [String] دسته‌بندی خودرو
  "make": "BMW",                 // [String] برند
  "model": "3 Series",           // [String] مدل
  "year": 2020,                  // [Integer] سال تولید
  "mileage": 50000,              // [Integer] کیلومتراژ
  "condition": "excellent|good", // [String] وضعیت
  "engine": "2.0",               // [String] حجم موتور
  "transmission": "automatic"    // [String] نوع گیربکس
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "base_price": 450000000,
    "breakdown": [
      {
        "key": "base_price",
        "label": "قیمت پایه",
        "amount": 450000000
      },
      {
        "key": "customs_tariff",
        "label": "تعرفهٔ گمرکی",
        "amount": 180000000
      },
      {
        "key": "vat",
        "label": "مالیات بر ارزش‌افزوده",
        "amount": 126000000
      },
      {
        "key": "service_fee",
        "label": "هزینهٔ خدمات",
        "amount": 50000000
      }
    ],
    "totals": {
      "subtotal": 630000000,
      "vat": 126000000,
      "total": 756000000
    },
    "pricing_snapshot": {
      "base_price": 450000000,
      "calculated_at": "2026-08-17T15:30:00Z",
      "valid_for_days": 7
    }
  }
}
```

**خطاهای احتمالی**:
- `422`: مقادیر نامعتبر
- `429`: تجاوز Rate Limit

---

### 2️⃣ ثبت درخواست نقل‌قول

**Endpoint**: `POST /quote-requests`  
**Rate Limit**: 5 requests/minute  
**Auth**: ❌ No  
**CORS**: ✅ Yes  

**Request Body**:
```json
{
  "name": "محمد احمدی",              // [String, required] نام
  "phone": "+989121234567",            // [String, required] تلفن
  "email": "user@example.com",         // [String, required] ایمیل
  "car_label": "BMW 3 Series 2020",    // [String] برچسب خودرو
  "category": "sedan",                 // [String] دسته
  "budget_range": "300-500M",          // [String] بودجه
  "breakdown_json": "[...]",           // [JSON String] Breakdown
  "totals_json": "{...}",              // [JSON String] مجموع‌ها
  "source": "calculator|website",      // [String] منبع
  "country": "IR",                     // [String] کشور
  "city": "Tehran"                     // [String] شهر
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "محمد احمدی",
    "email": "user@example.com",
    "status": "new",
    "created_at": "2026-08-17T15:30:00Z",
    "reference_number": "QR-2026-08-00123"
  },
  "message": "درخواست با موفقیت ثبت شد"
}
```

**Email ارسالی**:
- ایمیل تأیید برای کاربر
- ایمیل اطلاع‌رسانی برای مدیر

---

### 3️⃣ ثبت لاگ محاسبات

**Endpoint**: `POST /calculation-logs`  
**Rate Limit**: 30 requests/minute  
**Auth**: ❌ No  

**Request Body**:
```json
{
  "car_label": "BMW 3 Series 2020",
  "category": "sedan",
  "breakdown_json": "[...]",
  "totals_json": "{...}",
  "ip_address": "auto" // یا IP مشخص
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 456,
    "logged_at": "2026-08-17T15:30:00Z"
  }
}
```

---

### 4️⃣ بررسی VIN

**Endpoint**: `POST /vin-checks`  
**Rate Limit**: 30 requests/minute  
**Auth**: ❌ No  

**Request Body**:
```json
{
  "vin": "WBADT43452GK11111"  // [String] VIN خودرو
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 789,
    "vin": "WBADT43452GK11111",
    "result": {
      "valid": true,
      "make": "BMW",
      "model": "3 Series",
      "year": 2018,
      "body_type": "Sedan",
      "engine": "2.0 Turbocharged",
      "transmission": "Automatic"
    }
  }
}
```

---

### 5️⃣ دانلود PDF نقل‌قول

**Endpoint**: `GET /quote-requests/{id}/pdf`  
**Auth**: ❌ No (Signed URL)  
**Method**: GET  

**URL Example**:
```
GET /quote-requests/123/pdf?signature=xxx&expires=xxx
```

**Response**: 
- **Header**: `Content-Type: application/pdf`
- **Body**: Binary PDF file

---

### 6️⃣ لیست خودروها (با فیلتر)

**Endpoint**: `GET /car-prices`  
**Auth**: ❌ No  
**Method**: GET  

**Query Parameters**:
```
?brand=BMW              // فیلتر براساس برند
?category=sedan         // فیلتر براساس دسته
?price=300-500          // فیلتر براساس قیمت (میلیون)
?page=1                 // شمارهٔ صفحه
?per_page=20            // نتایج در هر صفحه
?sort=price|year|name   // ترتیب‌سازی
&direction=asc|desc     // جهت ترتیب‌سازی
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "slug": "bmw-3-series-2020",
      "make": "BMW",
      "model": "3 Series",
      "year": 2020,
      "price": 450000000,
      "customs_price": 180000000,
      "mileage": 50000,
      "color": "سفید",
      "category": "sedan",
      "image": "/images/cars/1.jpg",
      "is_published": true,
      "created_at": "2026-08-17T15:30:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total": 150,
    "per_page": 20,
    "total_pages": 8
  }
}
```

---

### 7️⃣ جزئیات خودروی منفرد

**Endpoint**: `GET /car-prices/{slug}`  
**Auth**: ❌ No  
**Method**: GET  

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "slug": "bmw-3-series-2020",
    "make": "BMW",
    "model": "3 Series",
    "year": 2020,
    "price": 450000000,
    "customs_price": 180000000,
    "description": "خودروی در حالت عالی...",
    "images": [
      "/images/cars/1-1.jpg",
      "/images/cars/1-2.jpg",
      "/images/cars/1-3.jpg"
    ],
    "specifications": {
      "engine": "2.0 Turbocharged",
      "transmission": "Automatic",
      "mileage": 50000,
      "color": "سفید",
      "fuel_type": "Petrol",
      "body_type": "Sedan"
    },
    "related_cars": [
      { "id": 2, "slug": "...", "price": "..." }
    ]
  }
}
```

---

## 🔐 API مدیریتی

### Bearer Token
تمام API مدیریتی نیاز به Token دارند:

```http
Authorization: Bearer {token}
```

**Token دریافت کنید**:
1. ثبت‌نام کنید
2. درخواست token برای Admin
3. `Bearer abc123xyz...` را استفاده کنید

---

### 1️⃣ لیست درخواست‌ها

**Endpoint**: `GET /admin/requests`  
**Auth**: ✅ Yes  
**Roles**: Sales, Admin  

**Query Parameters**:
```
?status=new|pending|closed
?stage_id=1
?assigned_to=5
?temperature=hot|warm|cold
?page=1
?per_page=20
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "محمد احمدی",
      "phone": "+989121234567",
      "email": "user@example.com",
      "car_label": "BMW 3 Series 2020",
      "category": "sedan",
      "temperature": "hot",
      "status": "pending",
      "stage": {
        "id": 2,
        "name": "در تماس"
      },
      "assigned_to": {
        "id": 5,
        "name": "علی رضایی"
      },
      "total": 756000000,
      "created_at": "2026-08-17T15:30:00Z",
      "updated_at": "2026-08-17T16:45:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total": 450,
    "per_page": 20
  }
}
```

---

### 2️⃣ جزئیات یک درخواست

**Endpoint**: `GET /admin/requests/{id}`  
**Auth**: ✅ Yes  
**Roles**: Sales (خودش)، Admin  

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "محمد احمدی",
    "phone": "+989121234567",
    "email": "user@example.com",
    "breakdown": [
      {
        "key": "base_price",
        "label": "قیمت پایه",
        "amount": 450000000
      }
    ],
    "invoices": [
      {
        "id": 1,
        "number": "INV-2026-0001",
        "status": "sent"
      }
    ],
    "activities": [
      {
        "id": 456,
        "type": "call",
        "description": "تماس با مشتری",
        "created_by": "علی رضایی",
        "created_at": "2026-08-17T15:30:00Z"
      }
    ]
  }
}
```

---

### 3️⃣ تغییر وضعیت درخواست

**Endpoint**: `POST /admin/requests/{id}/status`  
**Auth**: ✅ Yes  
**Roles**: Sales، Admin  

**Request Body**:
```json
{
  "status": "pending|closed|lost"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "وضعیت با موفقیت تغییر کرد"
}
```

---

### 4️⃣ تخصیص درخواست

**Endpoint**: `POST /admin/requests/{id}/assign`  
**Auth**: ✅ Yes  
**Roles**: Admin  

**Request Body**:
```json
{
  "assigned_to": 5  // User ID
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "درخواست با موفقیت تخصیص داده شد",
  "data": {
    "assigned_to": {
      "id": 5,
      "name": "علی رضایی"
    }
  }
}
```

---

### 5️⃣ ایجاد فاکتور Proforma

**Endpoint**: `POST /admin/invoices`  
**Auth**: ✅ Yes  
**Roles**: Sales، Admin  

**Request Body**:
```json
{
  "request_id": 123,
  "breakdown": [...],
  "totals": {...}
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "number": "INV-2026-0001",
    "request_id": 123,
    "status": "draft",
    "total": 756000000,
    "pdf_url": "/admin/invoices/1/pdf"
  }
}
```

---

### 6️⃣ لیست خودروها (Admin)

**Endpoint**: `GET /admin/car-listings`  
**Auth**: ✅ Yes  
**Roles**: Content Manager، Admin  

**Query Parameters**:
```
?is_published=true|false
?page=1
?per_page=20
?search=BMW
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "make": "BMW",
      "model": "3 Series",
      "year": 2020,
      "price": 450000000,
      "is_published": true,
      "created_at": "2026-08-17T15:30:00Z"
    }
  ]
}
```

---

### 7️⃣ ایجاد خودرو

**Endpoint**: `POST /admin/car-listings`  
**Auth**: ✅ Yes  
**Roles**: Content Manager، Admin  

**Request Body** (multipart/form-data):
```
make: "BMW"
model: "3 Series"
year: 2020
price: 450000000
customs_price: 180000000
description: "خودروی در حالت عالی"
color: "سفید"
images: [file1, file2, file3]
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 5,
    "slug": "bmw-3-series-2020-copy",
    "make": "BMW",
    "model": "3 Series",
    "price": 450000000
  }
}
```

---

### 8️⃣ بروز‌رسانی خودرو

**Endpoint**: `PUT /admin/car-listings/{id}`  
**Auth**: ✅ Yes  
**Roles**: Content Manager، Admin  

**Request Body**:
```json
{
  "make": "BMW",
  "model": "3 Series",
  "year": 2020,
  "price": 450000000
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "خودرو با موفقیت بروز‌رسانی شد"
}
```

---

### 9️⃣ حذف خودرو

**Endpoint**: `DELETE /admin/car-listings/{id}`  
**Auth**: ✅ Yes  
**Roles**: Admin  

**Response** (200 OK):
```json
{
  "success": true,
  "message": "خودرو با موفقیت حذف شد"
}
```

---

## 📦 Response Format

### موفق (2xx)
```json
{
  "success": true,
  "data": { ... },
  "message": "عملیات موفق"
}
```

### خطا (4xx, 5xx)
```json
{
  "success": false,
  "error": {
    "code": "INVALID_REQUEST",
    "message": "درخواست نامعتبر است",
    "details": {
      "field_name": ["خطا 1", "خطا 2"]
    }
  }
}
```

---

## ❌ Error Handling

### Status Codes

| Code | معنی | حل |
|------|------|-----|
| 200 | موفق | - |
| 201 | ایجاد شد | - |
| 400 | درخواست نامعتبر | بررسی parameters |
| 401 | عدم احراز هویت | Token را اضافه کنید |
| 403 | عدم دسترسی | نقش شما اجازه ندارد |
| 404 | پیدا نشد | ID یا URL درست نیست |
| 422 | مقادیر نامعتبر | فیلدها را چک کنید |
| 429 | تجاوز Rate Limit | صبر کنید و دوباره سعی کنید |
| 500 | خطای سرور | به پشتیبانی تماس بگیرید |

---

## ⏱️ Rate Limiting

**Headers**:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1692298200
```

**Limits**:
| Endpoint | Limit |
|----------|-------|
| `/vehicle-pricing/calculate` | 60/min |
| `/quote-requests` | 5/min |
| `/calculation-logs` | 30/min |
| `/vin-checks` | 30/min |
| Admin APIs | 100/min |

---

## 🔗 نمونهٔ استفاده

### JavaScript/Fetch
```javascript
// محاسبهٔ قیمت
const response = await fetch('/vehicle-pricing/calculate', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    category: 'sedan',
    make: 'BMW',
    model: '3 Series',
    year: 2020
  })
});

const result = await response.json();
console.log(result.data.totals);
```

### cURL
```bash
curl -X POST https://navracar.ir/vehicle-pricing/calculate \
  -H "Content-Type: application/json" \
  -d '{
    "category": "sedan",
    "make": "BMW",
    "model": "3 Series",
    "year": 2020
  }'
```

### PHP/Laravel
```php
$response = Http::post('/vehicle-pricing/calculate', [
    'category' => 'sedan',
    'make' => 'BMW',
    'model' => '3 Series',
    'year' => 2020,
]);

$data = $response->json();
```

---

**نوشته**: این API documentation برای تمام درخواست‌های API است و باید برای endpoints جدید به‌روز شود.

