<?php
function generateAccountNumber($conn) {
    // Simple 12-digit account number: YYYY + 8 random digits
    for ($i = 0; $i < 5; $i++) {
        $candidate = date('Y') . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare('SELECT id FROM accounts WHERE account_number = ? LIMIT 1');
        $stmt->bind_param("s", $candidate);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) return $candidate;
    }
    // fallback
    return uniqid();
}
