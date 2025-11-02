<?php
// Test low-value transfer using PIN
require_once 'includes/db_connect.php';
require_once 'includes/utils_account.php';
require_once 'includes/audit.php';
require_once 'includes/send_mail.php';

$settings = include 'admin/config/settings.php';
$threshold = $settings['high_value_threshold'] ?? 500.00;

echo "Testing low-value transfer (PIN-based) below threshold $" . number_format($threshold, 2) . "\n";

// Create test users and accounts
$conn->begin_transaction();

$username1 = 'sender_pin';
$email1 = 'sender_pin_' . time() . '@test.com';
$password1 = password_hash('password123', PASSWORD_DEFAULT);
$pin1 = password_hash('1234', PASSWORD_DEFAULT);

$stmt = $conn->prepare('INSERT INTO users (username, email, password, pin, verified) VALUES (?, ?, ?, ?, ?)');
$verified1 = 1;
$stmt->bind_param('sssss', $username1, $email1, $password1, $pin1, $verified1);
$stmt->execute();
$senderId = $conn->insert_id;

$username2 = 'recipient_pin';
$email2 = 'recipient_pin_' . time() . '@test.com';
$password2 = password_hash('password123', PASSWORD_DEFAULT);

$stmt = $conn->prepare('INSERT INTO users (username, email, password, verified) VALUES (?, ?, ?, ?)');
$verified2 = 1;
$stmt->bind_param('sssi', $username2, $email2, $password2, $verified2);
$stmt->execute();
$recipientId = $conn->insert_id;

// Create accounts
$senderAcctNum = generateAccountNumber($conn);
$stmt = $conn->prepare('INSERT INTO accounts (user_id, account_type, account_number, balance) VALUES (?, ?, ?, ?)');
$type1 = 'Checking';
$bal1 = 1000.00;
$stmt->bind_param('issd', $senderId, $type1, $senderAcctNum, $bal1);
$stmt->execute();
$senderAcctId = $conn->insert_id;

$recipientAcctNum = generateAccountNumber($conn);
$stmt = $conn->prepare('INSERT INTO accounts (user_id, account_type, account_number, balance) VALUES (?, ?, ?, ?)');
$type2 = 'Savings';
$bal2 = 100.00;
$stmt->bind_param('issd', $recipientId, $type2, $recipientAcctNum, $bal2);
$stmt->execute();
$recipientAcctId = $conn->insert_id;

$conn->commit();

echo "Created sender ID: $senderId, Account: $senderAcctNum, Balance: 1000.00\n";
echo "Created recipient ID: $recipientId, Account: $recipientAcctNum, Balance: 100.00\n";

// Simulate low-value transfer
$amount = 100.00; // Below threshold
echo "Initiating transfer of $" . number_format($amount, 2) . " from $senderAcctNum to $recipientAcctNum using PIN\n";

try {
    $conn->begin_transaction();

    // Check balance
    $stmt = $conn->prepare('SELECT balance FROM accounts WHERE id = ?');
    $stmt->bind_param('i', $senderAcctId);
    $stmt->execute();
    $balance = $stmt->get_result()->fetch_assoc()['balance'];
    if ($balance < $amount) throw new Exception('Insufficient funds');

    // Update balances
    $stmt = $conn->prepare('UPDATE accounts SET balance = balance - ? WHERE id = ?');
    $stmt->bind_param('di', $amount, $senderAcctId);
    $stmt->execute();

    $stmt = $conn->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ?');
    $stmt->bind_param('di', $amount, $recipientAcctId);
    $stmt->execute();

    // Record transactions
    $stmt = $conn->prepare('INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, ?)');
    $desc1 = 'Transfer to account ' . $recipientAcctNum;
    $neg_amount = -$amount;
    $status1 = 'Completed';
    $stmt->bind_param('iisds', $senderId, $senderAcctId, $desc1, $neg_amount, $status1);
    $stmt->execute();

    $stmt = $conn->prepare('INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, ?)');
    $desc2 = 'Transfer from account ' . $senderAcctNum;
    $pos_amount = $amount;
    $status2 = 'Completed';
    $stmt->bind_param('iisds', $recipientId, $recipientAcctId, $desc2, $pos_amount, $status2);
    $stmt->execute();

    $conn->commit();
    echo "Transfer completed successfully.\n";

    // Check final balances
    $stmt = $conn->prepare('SELECT balance FROM accounts WHERE id = ?');
    $stmt->bind_param('i', $senderAcctId);
    $stmt->execute();
    $finalSender = $stmt->get_result()->fetch_assoc()['balance'];

    $stmt = $conn->prepare('SELECT balance FROM accounts WHERE id = ?');
    $stmt->bind_param('i', $recipientAcctId);
    $stmt->execute();
    $finalRecipient = $stmt->get_result()->fetch_assoc()['balance'];

    echo "Final balances: Sender $finalSender, Recipient $finalRecipient\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "Test finished.\n";
?>
