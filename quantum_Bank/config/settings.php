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
    // Optional Google reCAPTCHA keys. Leave blank to disable CAPTCHA checks.
    'recaptcha_site_key' => '',
    'recaptcha_secret' => '',
];
