-- ==========================================================
-- ناوراکار — ارتقای دوم: فرم عمومی ثبت تماس فروش (بدون لاگین)
-- این اسکریپت را بعد از schema.sql / schema-update.sql اجرا کنید.
-- ==========================================================

ALTER TABLE quote_requests
  ADD COLUMN budget_range VARCHAR(100) NULL AFTER source,
  ADD COLUMN next_call_date DATE NULL AFTER follow_up_status,
  ADD COLUMN created_by INT NULL AFTER assigned_to,
  ADD FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL;
