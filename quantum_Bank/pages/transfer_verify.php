<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/audit.php';
include '../includes/send_mail.php';
requireLogin();

$userId = getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid CSRF token.';
    } elseif (empty($otp)) {
        $error = 'Please enter the OTP code.';
    } else {
        try {
            $settings = include __DIR__ . '/../admin/config/settings.php';
            $maxAttempts = $settings['otp_max_attempts'] ?? 5;
            $cooldown = $settings['otp_cooldown_seconds'] ?? 900;

            $conn->begin_transaction();
            // lock on user's most recent unused OTP within expiry window
            $stmt = $conn->prepare('SELECT * FROM transfer_otps WHERE user_id = ? AND used = 0 AND expires_at >= NOW() ORDER BY created_at DESC LIMIT 1 FOR UPDATE');
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if (!$row) throw new Exception('No pending transfer verification found or it has expired.');

            // enforce max attempts
            if ((int)$row['attempts'] >= (int)$row['max_attempts']) {
                throw new Exception('This OTP has been locked due to too many failed attempts. Please initiate the transfer again later.');
            }

            // verify against hashed otp
            if (!password_verify($otp, $row['otp_hash'])) {
                // increment attempts
                $stmt = $conn->prepare('UPDATE transfer_otps SET attempts = attempts + 1 WHERE id = ?');
                $stmt->bind_param("i", $row['id']);
                $stmt->execute();
                $conn->commit();
                throw new Exception('Invalid OTP code.');
            }

            // mark used
            $stmt = $conn->prepare('UPDATE transfer_otps SET used = 1 WHERE id = ?');
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();

            // perform transfer similar to transfer.php (locking accounts)
            $from = (int)$row['from_account'];
            $to = (int)$row['to_account'];
            $amount = (float)$row['amount'];
            // Lock accounts
            $stmt = $conn->prepare('SELECT id, user_id, balance FROM accounts WHERE id IN (?, ?) FOR UPDATE');
            $stmt->bind_param("ii", $from, $to);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $balances = [];
            $owners = [];
            foreach ($rows as $r) {
                $balances[$r['id']] = (float)$r['balance'];
                $owners[$r['id']] = (int)$r['user_id'];
            }
            if (!isset($balances[$from]) || !isset($balances[$to])) throw new Exception('Account not found.');
            if ($balances[$from] < $amount) throw new Exception('Insufficient funds.');
            // update balances
            $stmt = $conn->prepare('UPDATE accounts SET balance = balance - ? WHERE id = ?');
            $stmt->bind_param("di", $amount, $from);
            $stmt->execute();
            $stmt = $conn->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ?');
            $stmt->bind_param("di", $amount, $to);
            $stmt->execute();
            // record transactions
            $stmt = $conn->prepare('INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, "Completed")');
            $stmt->bind_param("iisd", $userId, $from, 'Transfer to account ' . $to, -$amount);
            $stmt->execute();
            $recipientUserId = $owners[$to];
            $stmt->bind_param("iisd", $recipientUserId, $to, 'Transfer from account ' . $from, $amount);
            $stmt->execute();
            $conn->commit();
            audit_log($conn, 'transfer.completed.otp', $userId, ['from' => $from, 'to' => $to, 'amount' => $amount, 'otp_id' => $row['id']]);
            $success = 'Transfer completed successfully.';
            // Add message to inbox
            add_message($userId, 'confirmation', "Your transfer of $" . number_format($amount, 2) . " from account $from to account $to has been completed successfully.");
            // notify recipient
            try {
                $stmt = $conn->prepare('SELECT email, username FROM users WHERE id = ? LIMIT 1');
                $stmt->bind_param("i", $recipientUserId);
                $stmt->execute();
                $result = $stmt->get_result();
                $r = $result->fetch_assoc();
                if ($r && !empty($r['email'])) {
                    $msg = "Hello {$r['username']},\n\nYou have received a transfer of $" . number_format($amount,2) . " to your account (ID: $to).";
                    send_mail($r['email'], 'Incoming transfer', $msg);
                }
            } catch (Exception $e) {}
        } catch (Exception $e) {
            try { $conn->rollback(); } catch (Exception $ex) {}
            $error = 'Failed to complete transfer: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>
<h2>Enter Transfer OTP</h2>
<?php if (!empty($error)) echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>"; ?>
<?php if (!empty($success)) echo "<div class='alert alert-success'>" . htmlspecialchars($success) . "</div>"; ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <div class="mb-3"><label>OTP Code</label><input name="otp" class="form-control"></div>
    <button class="btn btn-primary" type="submit">Verify & Complete Transfer</button>
</form>

<?php include '../includes/footer.php'; ?>
