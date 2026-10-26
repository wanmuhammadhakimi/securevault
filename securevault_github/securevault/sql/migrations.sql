-- Schema changes added after the base schema (run once, in order).

-- Keep audit logs when a user account is deleted (FK -> ON DELETE SET NULL).
ALTER TABLE activity_logs   MODIFY user_id INT NULL;
ALTER TABLE intrusion_alerts MODIFY user_id INT NULL;
ALTER TABLE activity_logs
  ADD CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL;
ALTER TABLE intrusion_alerts
  ADD CONSTRAINT fk_intrusion_alerts_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL;

-- Bank-style cooling-off after an admin replaces a user's fingerprint.
ALTER TABLE users ADD COLUMN IF NOT EXISTS fp_cooldown_until DATETIME NULL;
