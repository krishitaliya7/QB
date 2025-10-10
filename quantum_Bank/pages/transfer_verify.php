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
            $settings = include __DIR__ . '/../config/settings.php';
            $maxAttempts = $settings['otp_max_attempts'] ?? 5;
            $cooldown = $settings['otp_cooldown_seconds'] ?? 900;

            $pdo->beginTransaction();
            // lock on user's most recent unused OTP within expiry window
            $stmt = $pdo->prepare('SELECT * FROM transfer_otps WHERE user_id = ? AND used = 0 AND expires_at >= NOW() ORDER BY created_at DESC LIMIT 1 FOR UPDATE');
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if (!$row) throw new Exception('No pending transfer verification found or it has expired.');

            // enforce max attempts
            if ((int)$row['attempts'] >= (int)$row['max_attempts']) {
                throw new Exception('This OTP has been locked due to too many failed attempts. Please initiate the transfer again later.');
            }

            // verify against hashed otp
            if (!password_verify($otp, $row['otp_hash'])) {
                // increment attempts
                $stmt = $pdo->prepare('UPDATE transfer_otps SET attempts = attempts + 1 WHERE id = ?');
                $stmt->execute([$row['id']]);
                $pdo->commit();
                throw new Exception('Invalid OTP code.');
            }

            // mark used
            $stmt = $pdo->prepare('UPDATE transfer_otps SET used = 1 WHERE id = ?');
            $stmt->execute([$row['id']]);

            // perform transfer similar to transfer.php (locking accounts)
            $from = (int)$row['from_account'];
            $to = (int)$row['to_account'];
            $amount = (float)$row['amount'];
            // Lock accounts
            $stmt = $pdo->prepare('SELECT id, user_id, balance FROM accounts WHERE id IN (?, ?) FOR UPDATE');
            $stmt->execute([$from, $to]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $balances = [];
            $owners = [];
            foreach ($rows as $r) {
                $balances[$r['id']] = (float)$r['balance'];
                $owners[$r['id']] = (int)$r['user_id'];
            }
            if (!isset($balances[$from]) || !isset($balances[$to])) throw new Exception('Account not found.');
            if ($balances[$from] < $amount) throw new Exception('Insufficient funds.');
            // update balances
            $stmt = $pdo->prepare('UPDATE accounts SET balance = balance - ? WHERE id = ?');
            $stmt->execute([$amount, $from]);
            $stmt = $pdo->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ?');
            $stmt->execute([$amount, $to]);
            // record transactions
            $stmt = $pdo->prepare('INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, "Completed")');
            $stmt->execute([$userId, $from, 'Transfer to account ' . $to, -$amount]);
            $recipientUserId = $owners[$to];
            $stmt->execute([$recipientUserId, $to, 'Transfer from account ' . $from, $amount]);
            $pdo->commit();
            audit_log($pdo, 'transfer.completed.otp', $userId, ['from' => $from, 'to' => $to, 'amount' => $amount, 'otp_id' => $row['id']]);
            $success = 'Transfer completed successfully.';
            // notify recipient
            try {
                $stmt = $pdo->prepare('SELECT email, username FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$recipientUserId]);
                $r = $stmt->fetch();
                if ($r && !empty($r['email'])) {
                    $msg = "Hello {$r['username']},\n\nYou have received a transfer of $" . number_format($amount,2) . " to your account (ID: $to).";
                    send_mail($r['email'], 'Incoming transfer', $msg);
                }
            } catch (Exception $e) {}
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
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
