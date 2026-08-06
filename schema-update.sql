-- ==========================================================
-- ناوراکار — ارتقای پایگاه داده قبلی به نسخه CRM
-- این اسکریپت را فقط روی دیتابیسی اجرا کنید که قبلاً schema.sql نسخه قبلی
-- را اجرا کرده بودید (یعنی جداول calculation_logs / quote_requests / admin_users
-- از قبل وجود دارند). اجرای این فایل داده‌های موجود را پاک نمی‌کند.
-- اگر خطای "Duplicate column" گرفتید یعنی آن ستون از قبل اضافه شده — بی‌خطر است، خط بعدی را اجرا کنید.
-- ==========================================================

ALTER TABLE admin_users
  ADD COLUMN full_name VARCHAR(255) NULL AFTER password_hash,
  ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'admin' AFTER full_name;

ALTER TABLE calculation_logs
  ADD COLUMN country VARCHAR(100) NULL AFTER total_with_profit,
  ADD COLUMN city VARCHAR(100) NULL AFTER country;

ALTER TABLE quote_requests
  ADD COLUMN source VARCHAR(50) DEFAULT 'سایت' AFTER email_sent,
  ADD COLUMN country VARCHAR(100) NULL AFTER source,
  ADD COLUMN city VARCHAR(100) NULL AFTER country,
  ADD COLUMN assigned_to INT NULL AFTER city,
  ADD COLUMN follow_up_status VARCHAR(50) DEFAULT 'باز' AFTER assigned_to,
  ADD INDEX idx_assigned_to (assigned_to),
  ADD FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL;

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
  car_label VARCHAR(255),
  category VARCHAR(100),
  breakdown_json TEXT,
  total_amount DECIMAL(18,2) DEFAULT 0,
  status VARCHAR(30) DEFAULT 'پیش‌نویس',
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES quote_requests(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- حساب مدیر فعلی شما به‌صورت پیش‌فرض role='admin' می‌شود (دسترسی کامل، بدون تغییر رفتار قبلی).
