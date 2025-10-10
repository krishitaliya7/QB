<?php
// migrations script: migrate plaintext otp_code to otp_hash, and ensure loans disbursement columns exist.
// Usage: php scripts/migrate_transfer_otps.php

require_once __DIR__ . '/../includes/db_connect.php';

try {
    $pdo = get_pdo();
} catch (Exception $e) {
    echo "Failed to connect to DB: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo "Starting transfer_otps migration...\n";

$pdo->beginTransaction();
try {
    // Select rows that have otp_code non-null and otp_hash null
    $stmt = $pdo->prepare("SELECT id, otp_code FROM transfer_otps WHERE otp_hash IS NULL AND otp_code IS NOT NULL");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "No plaintext OTPs to migrate.\n";
    } else {
        $update = $pdo->prepare("UPDATE transfer_otps SET otp_hash = :otp_hash, otp_code = NULL WHERE id = :id");
        foreach ($rows as $r) {
            $id = $r['id'];
            $otp_code = $r['otp_code'];
            if ($otp_code === null || $otp_code === '') continue;
            $hash = password_hash($otp_code, PASSWORD_DEFAULT);
            $update->execute([':otp_hash' => $hash, ':id' => $id]);
            echo "Migrated OTP id={$id}\n";
        }
    }

    $pdo->commit();
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Migration failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Add a simple check for loans table disbursement columns
$cols = $pdo->query("SHOW COLUMNS FROM loans LIKE 'disbursed'")->fetch();
if ($cols) {
    echo "Loans table already has disbursed column.\n";
} else {
    echo "Please run the SQL migration to add disbursed columns (see sql_migrations).\n";
}

exit(0);
