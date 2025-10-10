-- Migration: add secure OTP columns and loan disbursement metadata
-- Assumptions:
--  - MySQL 8.0+ (ALTER TABLE ... ADD COLUMN IF NOT EXISTS supported)
--  - Existing `transfer_otps` may have `otp_code` (plaintext) which cannot be safely re-hashed in pure SQL.
-- This migration adds new columns (if missing). After running this SQL, run the accompanying PHP migration script
-- `scripts/migrate_transfer_otps.php` to re-hash existing plaintext OTPs into `otp_hash`.

ALTER TABLE `transfer_otps`
  ADD COLUMN IF NOT EXISTS `otp_hash` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `attempts` INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `max_attempts` INT NOT NULL DEFAULT 5,
  ADD COLUMN IF NOT EXISTS `expires_at` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `used` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE `loans`
  ADD COLUMN IF NOT EXISTS `disbursed` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `disbursed_at` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `disbursement_txn_id` BIGINT NULL;

-- index to help queries that search for unexpired OTPs
CREATE INDEX IF NOT EXISTS `idx_transfer_otps_expires_at` ON `transfer_otps` (`expires_at`);

-- Note: this SQL does not remove `otp_code` if present. Run the PHP migration script to securely hash
-- plaintext OTPs into `otp_hash` and optionally null out `otp_code` after verifying application behavior.
