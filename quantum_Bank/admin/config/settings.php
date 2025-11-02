<?php
// Application settings (change as needed)
return [
    // threshold for requiring OTP on transfers (in dollars)
    'high_value_threshold' => 500.00,
    // OTP expiry in seconds
    'otp_expiry_seconds' => 15 * 60,
    // Max OTP verification attempts
    'otp_max_attempts' => 5,
    // Cooldown seconds after max attempts (optional lockout)
    'otp_cooldown_seconds' => 15 * 60,
    // Rate limiting for OTP requests
    'otp_rate_limit_count' => 10, // Number of OTP requests allowed
    'otp_rate_limit_window_minutes' => 60, // Time window in minutes
    // Optional Google reCAPTCHA keys. Leave blank to disable CAPTCHA checks.
    'recaptcha_site_key' => '',
    'recaptcha_secret' => '',
];
