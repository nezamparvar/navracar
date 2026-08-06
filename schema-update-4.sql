-- ==========================================================
-- ناوراکار — ارتقای پنجم: جزئیات بیشتر پیش‌فاکتور (ایمیل، واحد پول، اعتبار، شرایط پرداخت، نوع پیش‌فاکتور)
-- اگر schema-update-3.sql را قبلاً اجرا نکرده‌اید، اول آن را اجرا کنید (ستون discount_amount لازم است).
-- ==========================================================

ALTER TABLE invoices
  ADD COLUMN customer_email VARCHAR(255) NULL AFTER customer_phone,
  ADD COLUMN currency VARCHAR(10) DEFAULT 'toman' AFTER discount_amount,
  ADD COLUMN exchange_rate DECIMAL(14,2) NULL AFTER currency,
  ADD COLUMN valid_until DATE NULL AFTER exchange_rate,
  ADD COLUMN payment_terms VARCHAR(500) NULL AFTER valid_until,
  ADD COLUMN invoice_type VARCHAR(30) DEFAULT 'full' AFTER payment_terms;
