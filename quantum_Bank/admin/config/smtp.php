<?php
// Copy this file to config/smtp.php and fill in the values for real SMTP delivery.
return [
    'host' => '', // e.g. smtp.gmail.com
    'port' => 587,
    'username' => '',
    'password' => '',
    'smtp_secure' => 'tls', // tls or ssl
    'from_address' => 'no-reply@quantumbank.test',
    'from_name' => 'QuantumBank'
];
