-- ==========================================================
-- ناوراکار — ارتقای سوم: بهبود سرعت (ایندکس تماس بعدی)
-- ==========================================================

ALTER TABLE quote_requests
  ADD INDEX idx_next_call_date (next_call_date);

ALTER TABLE invoices
  ADD COLUMN discount_amount DECIMAL(18,2) DEFAULT 0 AFTER total_amount;
