-- Migration: Add otp column to password_resets table for secure password reset flow
-- This adds the missing otp column that the password reset pages expect

ALTER TABLE `password_resets`
  ADD COLUMN IF NOT EXISTS `otp` VARCHAR(6) NULL;

-- Index for faster lookups by token
CREATE INDEX IF NOT EXISTS `idx_password_resets_token` ON `password_resets` (`token`);

-- Index for faster lookups by user_id and expires_at
CREATE INDEX IF NOT EXISTS `idx_password_resets_user_expires` ON `password_resets` (`user_id`, `expires_at`);
