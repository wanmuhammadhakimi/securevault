-- SecureVault: access requests replace open self-registration.
CREATE TABLE IF NOT EXISTS access_requests (
  request_id  INT AUTO_INCREMENT PRIMARY KEY,
  full_name   VARCHAR(100) NOT NULL,
  username    VARCHAR(50)  NOT NULL,
  email       VARCHAR(255) NOT NULL,
  note        VARCHAR(300) NULL COMMENT 'Department / reason given by the requester',
  status      ENUM('pending','created','dismissed') NOT NULL DEFAULT 'pending',
  ip_address  VARCHAR(45)  NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  handled_by  INT          NULL,
  handled_at  DATETIME     NULL,
  INDEX idx_status (status),
  INDEX idx_ip_time (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Keep the legacy biometric_enrolled flag consistent with the real template
-- (Settings and the login nudge still read this column).
UPDATE users SET biometric_enrolled = (fp_template IS NOT NULL);
