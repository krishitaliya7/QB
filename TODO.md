# OTP Feature Implementation for Login

## Database Schema Update
- [ ] Create SQL migration script for login_otps table
- [ ] Add login_otps table with fields: id, user_id, otp_hash, expires_at, attempts, max_attempts, used, created_at

## Modify Login Flow
- [ ] Update login.php to verify password first
- [ ] Generate OTP after successful password verification
- [ ] Store OTP in database and set session variables
- [ ] Send OTP via email
- [ ] Redirect to login_verify.php instead of dashboard

## Integrate OTP Verification
- [ ] Ensure login_verify.php handles OTP verification correctly
- [ ] Update session management for OTP flow
- [ ] Handle OTP expiration and attempt limits

## Email Template
- [ ] Create email template for login OTP delivery
- [ ] Include OTP code and expiration notice

## Settings Configuration
- [ ] Add OTP settings to admin/config/settings.php
- [ ] Configure OTP expiry time and max attempts

## Testing
- [ ] Test complete login flow with OTP
- [ ] Test OTP expiration
- [ ] Test invalid OTP attempts
- [ ] Test resend OTP functionality
