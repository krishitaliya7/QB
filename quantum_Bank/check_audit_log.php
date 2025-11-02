<?php
require_once 'includes/db_connect.php';
require_once 'includes/audit.php';

// Check if audit log records login success
$stmt = $conn->prepare('SELECT * FROM audit_logs WHERE action = ? ORDER BY created_at DESC LIMIT 1');
$action = 'login.success';
$stmt->bind_param('s', $action);
$stmt->execute();
$result = $stmt->get_result();
$log = $result->fetch_assoc();

if ($log) {
    echo 'Audit log found: ' . json_encode($log) . PHP_EOL;
} else {
    echo 'No login.success audit log found.' . PHP_EOL;
}
?>
