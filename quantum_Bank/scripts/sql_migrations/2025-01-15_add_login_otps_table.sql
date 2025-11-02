-- Migration: Add login_otps table for login OTP verification
-- Date: 2025-01-15

USE quantum_bank;

CREATE TABLE IF NOT EXISTS login_otps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  otp_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts INT DEFAULT 0,
  max_attempts INT DEFAULT 5,
  used TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add index for performance
CREATE INDEX idx_login_otps_user_id ON login_otps(user_id);
CREATE INDEX idx_login_otps_expires_at ON login_otps(expires_at);
