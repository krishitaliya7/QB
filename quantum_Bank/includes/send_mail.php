<?php
// send_mail will attempt to use PHPMailer (via Composer) if available and configured.
if (!function_exists('send_mail')) {
    function send_mail($to, $subject, $message, $headers = '', $html = null)
    {
        $configPath = __DIR__ . '/../../admin/config/smtp.php';
        $useSmtp = false;
        $smtpCfg = [];
        if (file_exists($configPath)) {
            $smtpCfg = include $configPath;
            if (!empty($smtpCfg['host'])) $useSmtp = true;
        }

        // Try PHPMailer if composer autoload exists and SMTP configured
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if ($useSmtp && file_exists($autoload)) {
            try {
                require_once $autoload;
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                // Configure transport security
                if (!empty($smtpCfg['smtp_secure'])) {
                    $mail->SMTPSecure = $smtpCfg['smtp_secure'];
                }
                $mail->isSMTP();
                $mail->Host = $smtpCfg['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtpCfg['username'];
                $mail->Password = $smtpCfg['password'];
                $mail->Port = $smtpCfg['port'] ?: 587;
                $mail->setFrom($smtpCfg['from_address'] ?? 'no-reply@localhost', $smtpCfg['from_name'] ?? 'QuantumBank');
                $mail->addAddress($to);
                $mail->Subject = $subject;
                if ($html) {
                    $mail->isHTML(true);
                    $mail->Body = $html;
                    $mail->AltBody = $message;
                } else {
                    $mail->Body = $message;
                }
                $mail->send();
                return true;
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                // log PHPMailer error and fallback
                $logDir = __DIR__ . '/../logs';
                if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                $file = $logDir . '/mail.log';
                $err = date('Y-m-d H:i:s') . " PHPMailer error: " . $e->getMessage() . "\n";
                file_put_contents($file, $err, FILE_APPEND);
            }
        }

        // Try native mail function
        if (function_exists('mail')) {
            // If an HTML body is provided, send multipart plain text fallback is not simple with mail(); use plain message
            @mail($to, $subject, $message, $headers);
            return true;
        }

        // Fallback: log to file
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $file = $logDir . '/mail.log';
        $entry = date('Y-m-d H:i:s') . "\nTo: $to\nSubject: $subject\nMessage:\n$message\n----\n";
        file_put_contents($file, $entry, FILE_APPEND);
        return true;
    }
}