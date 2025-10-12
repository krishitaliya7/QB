# Fix Password Reset Page

## Issues Identified
- Missing `otp` column in `password_resets` table
- Duplicate and inconsistent code in `password_reset_request.php`
- OTP not generated or stored during reset request
- Schema mismatch between code and database

## Tasks
- [x] Create migration to add `otp` column to `password_resets` table
- [x] Update `password_reset_request.php` to generate 6-digit OTP, store in table, send via in-app message, and clean up duplicate code
- [x] Verify `password_reset.php` correctly verifies OTP
- [x] Test the complete password reset flow
