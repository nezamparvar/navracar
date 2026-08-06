-- ==========================================================
-- ناوراکار — ارتقای چهارم: CRM فاز ۱ (پایپ‌لاین کانبان + قالب پیام + دما)
-- طبق مشخصات فنی CRM ارسالی — این فایل هم برای نصب تازه هم برای ارتقای نصب قبلی کار می‌کند.
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

ALTER TABLE quote_requests
  ADD COLUMN temperature VARCHAR(10) DEFAULT 'warm' AFTER category,
  ADD COLUMN current_stage_id INT NULL AFTER follow_up_status,
  ADD COLUMN loss_reason VARCHAR(255) NULL AFTER current_stage_id,
  ADD INDEX idx_current_stage (current_stage_id),
  ADD FOREIGN KEY (current_stage_id) REFERENCES pipeline_stages(id) ON DELETE SET NULL;

-- نگاشت وضعیت‌های قبلی به مراحل جدید پایپ‌لاین (چیزی حذف نمی‌شود، follow_up_status دست‌نخورده می‌ماند)
UPDATE quote_requests SET current_stage_id = 1 WHERE follow_up_status = 'باز' OR follow_up_status IS NULL;
UPDATE quote_requests SET current_stage_id = 3 WHERE follow_up_status = 'در حال پیگیری';
UPDATE quote_requests SET current_stage_id = 9 WHERE follow_up_status = 'فروخته شد';
UPDATE quote_requests SET current_stage_id = 10 WHERE follow_up_status = 'بسته - ناموفق';

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
