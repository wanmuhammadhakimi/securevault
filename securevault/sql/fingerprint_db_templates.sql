-- SecureVault: store fingerprint templates in the database (AES-256-GCM)
-- Run once on the `secure_file_storage` database (phpMyAdmin → SQL tab).

ALTER TABLE users
  ADD COLUMN fp_template     VARBINARY(1024) NULL COMMENT 'AS608 template (768 bytes), AES-256-GCM encrypted',
  ADD COLUMN fp_template_iv  VARBINARY(12)   NULL,
  ADD COLUMN fp_template_tag VARBINARY(16)   NULL,
  ADD COLUMN fp_enrolled_at  DATETIME        NULL;

-- Old slot-based enrollments are no longer used (those templates lived on the
-- sensor, not in the DB). Each user must be enrolled again with the new flow;
-- until then fingerprint_verify_start.php reports "No fingerprint enrolled".
UPDATE users SET fingerprint_slot_id = NULL;

-- Optional, once everything works: the slot column is no longer used.
-- ALTER TABLE users DROP COLUMN fingerprint_slot_id;
