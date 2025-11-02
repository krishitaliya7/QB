<?php
// Email template for login OTP verification
$message = "Hello,\n\n";
$message .= "Your login verification code is: $otp\n\n";
$message .= "This code will expire in 5 minutes.\n\n";
$message .= "If you did not request this login, please ignore this email.\n\n";
$message .= "Best regards,\nQuantumBank Team";

$html_message = "<p>Hello,</p>";
$html_message .= "<p>Your login verification code is: <strong>$otp</strong></p>";
$html_message .= "<p>This code will expire in 5 minutes.</p>";
$html_message .= "<p>If you did not request this login, please ignore this email.</p>";
$html_message .= "<p>Best regards,<br>QuantumBank Team</p>";
?>
