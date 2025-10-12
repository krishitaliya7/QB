<?php
// CLI test script: simulate a high-value transfer that requires OTP verification
// Usage:
//   php scripts/test_transfer_flow.php [--dry-run]
// By default the script will commit the transfer. Use --dry-run to roll back at the end.

chdir(__DIR__ . '/..'); // ensure script runs from project root

require_once __DIR__ . '/../includes/db_connect.php'; // provides $conn
require_once __DIR__ . '/../includes/utils_account.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/send_mail.php';

$dryRun = in_array('--dry-run', $argv, true);

echo "Starting transfer OTP flow test (dry-run=" . ($dryRun ? 'yes' : 'no') . ")\n";

$settings = include __DIR__ . '/../config/settings.php';
$threshold = $settings['high_value_threshold'] ?? 500.00;
$otpExpiry = $settings['otp_expiry_seconds'] ?? 900;
$maxAttempts = $settings['otp_max_attempts'] ?? 5;

function createUser($conn, $username, $email, $passwordPlain = 'Password123') {
    $hash = password_hash($passwordPlain, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (username, email, password, verified) VALUES (?, ?, ?, 1)');
    $stmt->bind_param("sss", $username, $email, $hash);
    $stmt->execute();
    return (int)$conn->insert_id;
}

function createAccount($conn, $userId, $type = 'Checking', $balance = 0.0) {
    $accNum = generateAccountNumber($conn);
    $stmt = $conn->prepare('INSERT INTO accounts (user_id, account_type, account_number, balance) VALUES (?, ?, ?, ?)');
    $stmt->bind_param("issd", $userId, $type, $accNum, $balance);
    $stmt->execute();
    return (int)$conn->insert_id;
}

try {
    // 1) Create sender and recipient
    $senderEmail = 'test_sender_' . time() . '@local.test';
    $recipientEmail = 'test_recipient_' . time() . '@local.test';
    echo "Creating users: $senderEmail, $recipientEmail\n";
    $conn->begin_transaction();
    $senderId = createUser($conn, 'test_sender', $senderEmail);
    $recipientId = createUser($conn, 'test_recipient', $recipientEmail);
    // create accounts
    $senderAcct = createAccount($conn, $senderId, 'Checking', 1000.00);
    $recipientAcct = createAccount($conn, $recipientId, 'Savings', 100.00);
    $conn->commit();
    echo "Created accounts: sender_id={$senderId} acct={$senderAcct}, recipient_id={$recipientId} acct={$recipientAcct}\n";

    // 2) Initiate a high-value transfer (above threshold) and create OTP
    $amount = max($threshold + 100, 600.00);
    echo "Initiating transfer of $" . number_format($amount,2) . " from account {$senderAcct} to {$recipientAcct}\n";

    // generate OTP, hash it, insert transfer_otps
    $plainOtp = strval(random_int(100000, 999999));
    $otpHash = password_hash($plainOtp, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', time() + $otpExpiry);
    $stmt = $conn->prepare('INSERT INTO transfer_otps (user_id, from_account, to_account, amount, otp_hash, max_attempts, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param("iiidsss", $senderId, $senderAcct, $recipientAcct, $amount, $otpHash, $maxAttempts, $expiresAt);
    $stmt->execute();
    $otpId = (int)$conn->insert_id;
    echo "Created OTP id={$otpId}, expires_at={$expiresAt}\n";

    // send email (this will be logged if SMTP not configured)
    $message = "Your OTP for transfer of $" . number_format($amount,2) . " is: {$plainOtp}";
    send_mail($senderEmail, 'Test transfer OTP', $message);
    audit_log($conn, 'test.transfer.otp.created', $senderId, ['otp_id' => $otpId, 'from' => $senderAcct, 'to' => $recipientAcct, 'amount' => $amount]);

    echo "Plain OTP (for testing): {$plainOtp}\n";

    // 3) Simulate verification step
    echo "Verifying OTP and completing transfer...\n";

    // begin transaction for verification + transfer
    $conn->begin_transaction();
    // lock the otp row
    $stmt = $conn->prepare('SELECT * FROM transfer_otps WHERE id = ? FOR UPDATE');
    $stmt->bind_param("i", $otpId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if (!$row) throw new Exception('OTP row not found');
    if ((int)$row['used'] === 1) throw new Exception('OTP already used');
    if (new DateTime($row['expires_at']) < new DateTime()) throw new Exception('OTP expired');

    if (!password_verify($plainOtp, $row['otp_hash'])) {
        // increment attempts
        $stmt = $conn->prepare('UPDATE transfer_otps SET attempts = attempts + 1 WHERE id = ?');
        $stmt->bind_param("i", $otpId);
        $stmt->execute();
        $conn->commit();
        throw new Exception('OTP verification failed (unexpected)');
    }

    // mark used
    $stmt = $conn->prepare('UPDATE transfer_otps SET used = 1 WHERE id = ?');
    $stmt->bind_param("i", $otpId);
    $stmt->execute();

    // lock accounts
    $stmt = $conn->prepare('SELECT id, user_id, balance FROM accounts WHERE id IN (?, ?) FOR UPDATE');
    $stmt->bind_param("ii", $senderAcct, $recipientAcct);
    $stmt->execute();
    $result = $stmt->get_result();
    $acctRows = $result->fetch_all(MYSQLI_ASSOC);
    $balances = [];
    $owners = [];
    foreach ($acctRows as $r) {
        $balances[(int)$r['id']] = (float)$r['balance'];
        $owners[(int)$r['id']] = (int)$r['user_id'];
    }
    if (!isset($balances[$senderAcct]) || !isset($balances[$recipientAcct])) throw new Exception('One of the accounts was not found');
    if ($balances[$senderAcct] < $amount) throw new Exception('Insufficient funds in sender account');

    // update balances
    $stmt = $conn->prepare('UPDATE accounts SET balance = balance - ? WHERE id = ?');
    $stmt->bind_param("di", $amount, $senderAcct);
    $stmt->execute();
    $stmt = $conn->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ?');
    $stmt->bind_param("di", $amount, $recipientAcct);
    $stmt->execute();

    // record transactions
    $stmt = $conn->prepare('INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, "Completed")');
    $description1 = 'Test transfer to account ' . $recipientAcct;
    $stmt->bind_param("iisd", $senderId, $senderAcct, $description1, -$amount);
    $stmt->execute();
    $description2 = 'Test transfer from account ' . $senderAcct;
    $stmt->bind_param("iisd", $owners[$recipientAcct], $recipientAcct, $description2, $amount);
    $stmt->execute();

    if ($dryRun) {
        $conn->rollback();
        echo "Dry-run: rolled back transaction. No balances changed.\n";
    } else {
        $conn->commit();
        echo "Success: transfer completed and committed.\n";
        // show balances
        $stmt = $conn->prepare('SELECT id, balance FROM accounts WHERE id IN (?, ?)');
        $stmt->bind_param("ii", $senderAcct, $recipientAcct);
        $stmt->execute();
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_ASSOC);
        foreach ($r as $row) echo "Account {$row['id']} balance: {$row['balance']}\n";
    }

    audit_log($conn, 'test.transfer.completed', $senderId, ['otp_id' => $otpId, 'from' => $senderAcct, 'to' => $recipientAcct, 'amount' => $amount]);

} catch (Exception $e) {
    try { $conn->rollback(); } catch (Exception $ex) {}
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Test finished.\n";
