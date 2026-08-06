-- ==========================================================
-- ناوراکار — پایگاه داده محاسبه‌گر هزینه واردات خودرو (نسخه کامل با CRM)
-- کل این فایل را در یک دیتابیس خالی Import کنید.
-- ==========================================================

CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(255) NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'admin', -- 'admin' یا 'sales'
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- حساب مدیر پیش‌فرض — بلافاصله بعد از اولین ورود از admin/users.php رمز را تغییر دهید.
-- یوزرنیم: admin | رمز: Navrakar@2026
INSERT IGNORE INTO admin_users (id, username, password_hash, full_name, role) VALUES
(1, 'admin', '$2y$12$N6bTcV0IdYIHGR6B16vNI.b8NzTwgKwLtz/OIGHscmPQA9vS8rdRC', 'مدیر سیستم', 'admin');

CREATE TABLE IF NOT EXISTS calculation_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  car_label VARCHAR(255),
  category VARCHAR(100),
  real_price_aed DECIMAL(14,2) DEFAULT 0,
  customs_price_aed DECIMAL(14,2) DEFAULT 0,
  free_rate DECIMAL(14,2) DEFAULT 0,
  customs_rate DECIMAL(14,2) DEFAULT 0,
  sea_freight_aed DECIMAL(14,2) DEFAULT 0,
  permits_aed DECIMAL(14,2) DEFAULT 0,
  storage_toman DECIMAL(16,2) DEFAULT 0,
  sum_customs DECIMAL(18,2) DEFAULT 0,
  sum_plate DECIMAL(18,2) DEFAULT 0,
  total_no_profit DECIMAL(18,2) DEFAULT 0,
  service_profit DECIMAL(18,2) DEFAULT 0,
  total_with_profit DECIMAL(18,2) DEFAULT 0,
  country VARCHAR(100),
  city VARCHAR(100),
  ip_address VARCHAR(64),
  user_agent VARCHAR(255),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quote_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  name VARCHAR(255),
  phone VARCHAR(64),
  email VARCHAR(255),
  notes TEXT,
  car_label VARCHAR(255),
  category VARCHAR(100),
  breakdown_json TEXT,
  totals_json TEXT,
  total_with_profit DECIMAL(18,2) DEFAULT 0,
  email_sent TINYINT(1) DEFAULT 0,
  source VARCHAR(50) DEFAULT 'سایت',
  budget_range VARCHAR(100),
  country VARCHAR(100),
  city VARCHAR(100),
  assigned_to INT NULL,
  created_by INT NULL,
  temperature VARCHAR(10) DEFAULT 'warm',
  follow_up_status VARCHAR(50) DEFAULT 'باز',
  current_stage_id INT NULL,
  loss_reason VARCHAR(255) NULL,
  next_call_date DATE NULL,
  ip_address VARCHAR(64),
  INDEX idx_created_at (created_at),
  INDEX idx_assigned_to (assigned_to),
  INDEX idx_next_call_date (next_call_date),
  FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lead_activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  admin_user_id INT NULL,
  activity_type VARCHAR(50),
  note TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES quote_requests(id) ON DELETE CASCADE,
  FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vin_checks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  vin VARCHAR(20),
  make VARCHAR(100),
  model VARCHAR(100),
  model_year VARCHAR(10),
  plant_country VARCHAR(100),
  verdict VARCHAR(50),
  source VARCHAR(30),
  country VARCHAR(100),
  city VARCHAR(100),
  ip_address VARCHAR(64),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NULL,
  invoice_number VARCHAR(50) UNIQUE,
  customer_name VARCHAR(255),
  customer_phone VARCHAR(64),
  customer_address VARCHAR(500),
  customer_email VARCHAR(255),
  car_label VARCHAR(255),
  category VARCHAR(100),
  breakdown_json TEXT,
  total_amount DECIMAL(18,2) DEFAULT 0,
  discount_amount DECIMAL(18,2) DEFAULT 0,
  currency VARCHAR(10) DEFAULT 'toman',
  exchange_rate DECIMAL(14,2) NULL,
  valid_until DATE NULL,
  payment_terms VARCHAR(500) NULL,
  invoice_type VARCHAR(30) DEFAULT 'full',
  status VARCHAR(30) DEFAULT 'پیش‌نویس',
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES quote_requests(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- CRM فاز ۱: پایپ‌لاین کانبان + قالب‌های پیام + دما
-- ==========================================================

CREATE TABLE IF NOT EXISTS pipeline_stages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  sort_order INT NOT NULL DEFAULT 0,
  sla_hours INT DEFAULT 24,
  is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO pipeline_stages (id, name, slug, sort_order, sla_hours) VALUES
(1, 'سرنخ جدید', 'new-lead', 1, 24),
(2, 'لیست خودرو ارسال شد', 'car-list-sent', 2, 24),
(3, 'نیازسنجی', 'qualification', 3, 48),
(4, 'برآورد هزینه ارسال شد', 'cost-breakdown-sent', 4, 48),
(5, 'پیش‌نویس قرارداد ارسال شد', 'contract-draft-sent', 5, 72),
(6, 'مذاکره نهایی', 'final-negotiation', 6, 48),
(7, 'قرارداد امضا شد', 'contract-signed', 7, 168),
(8, 'در حال ترخیص / پرداخت', 'payment-customs', 8, 336),
(9, 'تحویل داده شد', 'delivered', 9, 0),
(10, 'از دست رفته', 'lost', 10, 0);

ALTER TABLE quote_requests
  ADD INDEX idx_current_stage (current_stage_id),
  ADD FOREIGN KEY (current_stage_id) REFERENCES pipeline_stages(id) ON DELETE SET NULL;

UPDATE quote_requests SET current_stage_id = 1 WHERE current_stage_id IS NULL;

CREATE TABLE IF NOT EXISTS message_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  category VARCHAR(50) NOT NULL DEFAULT 'custom',
  body TEXT NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO message_templates (id, title, category, body) VALUES
(1, 'پاسخ اولیه به سرنخ جدید', 'initial_response',
 'سلام {{customer_name}} عزیز، وقت بخیر 🌹\nممنون از تماستون با ناوراکار. من {{salesperson_name}} هستم و مشاور شما برای واردات خودرو.\nچند لحظه وقت میدید تا نیازتون رو دقیق‌تر بررسی کنم؟'),
(2, 'ارسال لیست خودرو و کانال‌های رسمی', 'car_list',
 'سلام {{customer_name}} عزیز\nلیست خودروهای مجاز طرح واردات ناوراکار و کانال‌های رسمی شرکت رو براتون ارسال می‌کنم: {{official_channels}}\nهر سوالی داشتید در خدمتم هستم.'),
(3, 'ارسال برآورد هزینه', 'cost_breakdown',
 'سلام {{customer_name}} عزیز\nبرآورد هزینه واردات {{car_model}} رو براتون آماده کردم.\nجمع کل تخمینی: {{total_price}} تومان\nاین یک برآورد اولیه است؛ برای قیمت قطعی در خدمتتون هستم.'),
(4, 'پیگیری مشتری داغ (Hot)', 'follow_up_hot',
 'سلام {{customer_name}} عزیز، وقت بخیر\nپیگیر درخواستتون برای {{car_model}} هستم. اگه سوال یا ابهامی مونده در خدمتتون هستم تا تصمیم نهایی رو راحت‌تر بگیرید.'),
(5, 'پیگیری مشتری معمولی (Warm)', 'follow_up_warm',
 'سلام {{customer_name}} عزیز\nخواستم حالتون رو بپرسم و ببینم روی گزینه {{car_model}} به جمع‌بندی رسیدید یا سوال دیگه‌ای هست؟'),
(6, 'پیگیری مشتری سرد (Cold)', 'follow_up_cold',
 'سلام {{customer_name}} عزیز\nهنوز پیشنهاد واردات {{car_model}} براتون معتبره. اگه علاقه‌مند بودید خوشحال میشم دوباره کمکتون کنم.');

CREATE TABLE IF NOT EXISTS loss_reasons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reason VARCHAR(255) NOT NULL,
  is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO loss_reasons (id, reason) VALUES
(1, 'قیمت مناسب نبود'),
(2, 'از رقیب خرید کرد'),
(3, 'منصرف شد / نیاز برطرف شد'),
(4, 'عدم پاسخگویی مشتری'),
(5, 'شرایط اقامت/مجوز مناسب نبود'),
(6, 'سایر');
