<?php
function audit_log($pdo, $action, $userId = null, $meta = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, meta, ip) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $action, $meta ? json_encode($meta) : null, $ip]);
    } catch (Exception $e) {
        // ignore audit failures
    }
}
