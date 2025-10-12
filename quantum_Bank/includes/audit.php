<?php
function audit_log($conn, $action, $userId = null, $meta = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $metaJson = $meta ? json_encode($meta) : null;
        $stmt = $conn->prepare('INSERT INTO audit_logs (user_id, action, meta, ip) VALUES (?, ?, ?, ?)');
        $stmt->bind_param("isss", $userId, $action, $metaJson, $ip);
        $stmt->execute();
    } catch (Exception $e) {
        // ignore audit failures
    }
}
