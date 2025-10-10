<?php
// CLI test script: simulate a high-value transfer that requires OTP verification
// Usage:
//   php scripts/test_transfer_flow.php [--dry-run]
// By default the script will commit the transfer. Use --dry-run to roll back at the end.

chdir(__DIR__ . '/..'); // ensure script runs from project root

require_once __DIR__ . '/../includes/db_connect.php'; // provides $pdo
require_once __DIR__ . '/../includes/utils_account.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/send_mail.php';

$dryRun = in_array('--dry-run', $argv, true);

echo "Starting transfer OTP flow test (dry-run=" . ($dryRun ? 'yes' : 'no') . ")\n";

$settings = include __DIR__ . '/../config/settings.php';
$threshold = $settings['high_value_threshold'] ?? 500.00;
$otpExpiry = $settings['otp_expiry_seconds'] ?? 900;
$maxAttempts = $settings['otp_max_attempts'] ?? 5;

function createUser(PDO $pdo, $username, $email, $passwordPlain = 'Password123') {
    $hash = password_hash($passwordPlain, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password, verified) VALUES (?, ?, ?, 1)');
    $stmt->execute([$username, $email, $hash]);
    return (int)$pdo->lastInsertId();
}

function createAccount(PDO $pdo, $userId, $type = 'Checking', $balance = 0.0) {
    $accNum = generateAccountNumber($pdo);
    $stmt = $pdo->prepare('INSERT INTO accounts (user_id, account_type, account_number, balance) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $type, $accNum, $balance]);
    return (int)$pdo->lastInsertId();
}

try {
    // 1) Create sender and recipient
    $senderEmail = 'test_sender_' . time() . '@local.test';
    $recipientEmail = 'test_recipient_' . time() . '@local.test';
    echo "Creating users: $senderEmail, $recipientEmail\n";
    $pdo->beginTransaction();
    $senderId = createUser($pdo, 'test_sender', $senderEmail);
    $recipientId = createUser($pdo, 'test_recipient', $recipientEmail);
    // create accounts
    $senderAcct = createAccount($pdo, $senderId, 'Checking', 1000.00);
    $recipientAcct = createAccount($pdo, $recipientId, 'Savings', 100.00);
    $pdo->commit();
    echo "Created accounts: sender_id={$senderId} acct={$senderAcct}, recipient_id={$recipientId} acct={$recipientAcct}\n";

    // 2) Initiate a high-value transfer (above threshold) and create OTP
    $amount = max($threshold + 100, 600.00);
    echo "Initiating transfer of $" . number_format($amount,2) . " from account {$senderAcct} to {$recipientAcct}\n";

    // generate OTP, hash it, insert transfer_otps
    $plainOtp = strval(random_int(100000, 999999));
    $otpHash = password_hash($plainOtp, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', time() + $otpExpiry);
    $stmt = $pdo->prepare('INSERT INTO transfer_otps (user_id, from_account, to_account, amount, otp_hash, max_attempts, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$senderId, $senderAcct, $recipientAcct, $amount, $otpHash, $maxAttempts, $expiresAt]);
    $otpId = (int)$pdo->lastInsertId();
    echo "Created OTP id={$otpId}, expires_at={$expiresAt}\n";

    // send email (this will be logged if SMTP not configured)
    $message = "Your OTP for transfer of $" . number_format($amount,2) . " is: {$plainOtp}";
    send_mail($senderEmail, 'Test transfer OTP', $message);
    audit_log($pdo, 'test.transfer.otp.created', $senderId, ['otp_id' => $otpId, 'from' => $senderAcct, 'to' => $recipientAcct, 'amount' => $amount]);

    echo "Plain OTP (for testing): {$plainOtp}\n";

    // 3) Simulate verification step
    echo "Verifying OTP and completing transfer...\n";

    // begin transaction for verification + transfer
    $pdo->beginTransaction();
    // lock the otp row
    $stmt = $pdo->prepare('SELECT * FROM transfer_otps WHERE id = ? FOR UPDATE');
    $stmt->execute([$otpId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new Exception('OTP row not found');
    if ((int)$row['used'] === 1) throw new Exception('OTP already used');
    if (new DateTime($row['expires_at']) < new DateTime()) throw new Exception('OTP expired');

    if (!password_verify($plainOtp, $row['otp_hash'])) {
        // increment attempts
        $stmt = $pdo->prepare('UPDATE transfer_otps SET attempts = attempts + 1 WHERE id = ?');
        $stmt->execute([$otpId]);
        $pdo->commit();
        throw new Exception('OTP verification failed (unexpected)');
    }

    // mark used
    $stmt = $pdo->prepare('UPDATE transfer_otps SET used = 1 WHERE id = ?');
    $stmt->execute([$otpId]);

    // lock accounts
    $stmt = $pdo->prepare('SELECT id, user_id, balance FROM accounts WHERE id IN (?, ?) FOR UPDATE');
    $stmt->execute([$senderAcct, $recipientAcct]);
    $acctRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $balances = [];
    $owners = [];
    foreach ($acctRows as $r) {
        $balances[(int)$r['id']] = (float)$r['balance'];
        $owners[(int)$r['id']] = (int)$r['user_id'];
    }
    if (!isset($balances[$senderAcct]) || !isset($balances[$recipientAcct])) throw new Exception('One of the accounts was not found');
    if ($balances[$senderAcct] < $amount) throw new Exception('Insufficient funds in sender account');

    // update balances
    $stmt = $pdo->prepare('UPDATE accounts SET balance = balance - ? WHERE id = ?');
    $stmt->execute([$amount, $senderAcct]);
    $stmt = $pdo->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ?');
    $stmt->execute([$amount, $recipientAcct]);

    // record transactions
    $stmt = $pdo->prepare('INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, "Completed")');
    $stmt->execute([$senderId, $senderAcct, 'Test transfer to account ' . $recipientAcct, -$amount]);
    $stmt->execute([$owners[$recipientAcct], $recipientAcct, 'Test transfer from account ' . $senderAcct, $amount]);

    if ($dryRun) {
        $pdo->rollBack();
        echo "Dry-run: rolled back transaction. No balances changed.\n";
    } else {
        $pdo->commit();
        echo "Success: transfer completed and committed.\n";
        // show balances
        $stmt = $pdo->prepare('SELECT id, balance FROM accounts WHERE id IN (?, ?)');
        $stmt->execute([$senderAcct, $recipientAcct]);
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($r as $row) echo "Account {$row['id']} balance: {$row['balance']}\n";
    }

    audit_log($pdo, 'test.transfer.completed', $senderId, ['otp_id' => $otpId, 'from' => $senderAcct, 'to' => $recipientAcct, 'amount' => $amount]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Test finished.\n";
