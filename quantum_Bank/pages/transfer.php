<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/send_mail.php';
requireLogin();

$page_css = 'index.css';
$user_id = getUserId();

// Fetch user's accounts (for 'from' dropdown)
$stmt = $pdo->prepare("SELECT id, account_type, balance FROM accounts WHERE user_id = ?");
$stmt->execute([$user_id]);
$accounts = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from = (int)filter_input(INPUT_POST, 'from_account', FILTER_SANITIZE_NUMBER_INT);
    $to = (int)filter_input(INPUT_POST, 'to_account', FILTER_SANITIZE_NUMBER_INT);
    $to_external = filter_input(INPUT_POST, 'to_external', FILTER_SANITIZE_NUMBER_INT);
    $amount = (float)filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid CSRF token.';
    } elseif ($amount <= 0) {
        $error = 'Amount must be positive.';
    } else {
        // Ensure 'from' account belongs to user
        $userAccountIds = array_column($accounts, 'id');
        if (!in_array($from, $userAccountIds, true)) {
            $error = 'Invalid source account selection.';
        } else {
            // Determine destination account id. If user provided an external account id, try lookup by account_number first
            $destinationAccountId = null;
            if (!empty($to_external)) {
                // try to find account by account_number
                $stmt = $pdo->prepare('SELECT id FROM accounts WHERE account_number = ? LIMIT 1');
                $stmt->execute([(string)$to_external]);
                $found = $stmt->fetch();
                if ($found) $destinationAccountId = (int)$found['id'];
            }
            if ($destinationAccountId === null) $destinationAccountId = (int)$to;
            if ($destinationAccountId === $from) {
                $error = 'Cannot transfer to the same account.';
            } else {
                // If amount is above threshold, create OTP and require verification
                $highValueThreshold = 500.00;
                if ($amount >= $highValueThreshold) {
                    // create OTP row and email to user, but store only a hash
                    try {
                        // load settings
                        $settings = include __DIR__ . '/../config/settings.php';
                        $expirySeconds = $settings['otp_expiry_seconds'] ?? 900;
                        $maxAttempts = $settings['otp_max_attempts'] ?? 5;

                        // simple rate-limit: max 3 creations in past 30 minutes
                        $rlWindow = date('Y-m-d H:i:s', time() - 30*60);
                        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transfer_otps WHERE user_id = ? AND created_at >= ?');
                        $stmt->execute([$user_id, $rlWindow]);
                        $recent = (int)$stmt->fetchColumn();
                        if ($recent >= 3) {
                            throw new Exception('Too many OTP requests in a short period. Try again later.');
                        }

                        $otp = strval(random_int(100000, 999999));
                        $hash = password_hash($otp, PASSWORD_DEFAULT);
                        $expires = date('Y-m-d H:i:s', time() + $expirySeconds);
                        $stmt = $pdo->prepare('INSERT INTO transfer_otps (user_id, from_account, to_account, amount, otp_hash, max_attempts, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$user_id, $from, $destinationAccountId, $amount, $hash, $maxAttempts, $expires]);
                        $otpId = $pdo->lastInsertId();
                        // send user an email with OTP and instructions
                        $stmt = $pdo->prepare('SELECT email, username FROM users WHERE id = ? LIMIT 1');
                        $stmt->execute([$user_id]);
                        $u = $stmt->fetch();
                        if ($u && !empty($u['email'])) {
                            $html = "<p>Hello " . htmlspecialchars($u['username']) . ",</p>" .
                                "<p>You've initiated a transfer of $" . number_format($amount,2) . " from account #$from to account #$destinationAccountId.</p>" .
                                "<p>Please verify this transfer by entering the OTP code on the verification page. The code expires in " . intval($expirySeconds/60) . " minutes.</p>" .
                                "<p>Your OTP code: <strong>" . htmlspecialchars($otp) . "</strong></p>";
                            send_mail($u['email'], 'Verify your transfer', strip_tags($html), '', $html);
                        }
                        audit_log($pdo, 'transfer.otp.created', $user_id, ['otp_id' => $otpId, 'from' => $from, 'to' => $destinationAccountId, 'amount' => $amount]);
                        $success = 'High-value transfer requires verification. An OTP has been sent to your email. <a href="transfer_verify.php">Enter OTP to complete transfer</a>';
                    } catch (Exception $e) {
                        $error = 'Failed to initiate secured transfer: ' . $e->getMessage();
                    }
                } else {
                    try {
                        $pdo->beginTransaction();
                    // Lock involved accounts
                    $stmt = $pdo->prepare("SELECT id, user_id, balance FROM accounts WHERE id IN (?, ?) FOR UPDATE");
                    $stmt->execute([$from, $destinationAccountId]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $balances = [];
                    $owners = [];
                    foreach ($rows as $r) {
                        $id = (int)$r['id'];
                        $balances[$id] = (float)$r['balance'];
                        $owners[$id] = (int)$r['user_id'];
                    }
                    if (!isset($balances[$from]) || !isset($balances[$destinationAccountId])) {
                        throw new Exception('One of the accounts was not found.');
                    }
                    if ($balances[$from] < $amount) {
                        throw new Exception('Insufficient funds in source account.');
                    }
                    // Perform balance updates
                    $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
                    $stmt->execute([$amount, $from]);
                    $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
                    $stmt->execute([$amount, $destinationAccountId]);
                    // Record transactions: debit for sender, credit for recipient (use respective user_ids)
                    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, 'Completed')");
                    $senderDesc = "Transfer to account $destinationAccountId";
                    $recipientDesc = "Transfer from account $from";
                    $stmt->execute([$user_id, $from, $senderDesc, -$amount]);
                    $recipientUserId = $owners[$destinationAccountId];
                    $stmt->execute([$recipientUserId, $destinationAccountId, $recipientDesc, $amount]);
                    $pdo->commit();
                    // Notify recipient if email available
                    audit_log($pdo, 'transfer.completed', $user_id, ['from' => $from, 'to' => $destinationAccountId, 'amount' => $amount]);
                    try {
                        $stmt = $pdo->prepare('SELECT email, username FROM users WHERE id = ? LIMIT 1');
                        $stmt->execute([$recipientUserId]);
                        $r = $stmt->fetch();
                        if ($r && !empty($r['email'])) {
                            $msg = "Hello {$r['username']},\n\nYou have received a transfer of $" . number_format($amount,2) . " to your account (ID: $destinationAccountId) from account ID $from.\n\nIf you did not expect this, contact support.";
                            send_mail($r['email'], 'Incoming transfer', $msg);
                        }
                    } catch (Exception $e) {
                        // ignore notification failures
                    }
                    $success = 'Transfer completed.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Transfer failed: ' . $e->getMessage();
                }
            }
        }
    }
}

include '../includes/header.php';
?>
<h2>Transfer Between Accounts</h2>
<?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <div class="mb-3">
        <label>From Account</label>
        <select name="from_account" class="form-control">
            <?php foreach ($accounts as $a): ?>
                <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['account_type']) . ' - ' . htmlspecialchars($a['account_number'] ?? $a['id']) . ' - $' . number_format($a['balance'],2); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label>To Account (choose one or enter an external account ID)</label>
        <select name="to_account" class="form-control mb-2">
            <?php foreach ($accounts as $a): ?>
                <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['account_type']) . ' - ' . htmlspecialchars($a['account_number'] ?? $a['id']) . ' - $' . number_format($a['balance'],2); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="to_external" class="form-control" placeholder="Or enter recipient account ID (numeric)">
    </div>
    <div class="mb-3">
        <label>Amount</label>
        <input type="number" name="amount" step="0.01" class="form-control">
    </div>
    <button class="btn btn-primary" type="submit">Send</button>
</form>
<?php include '../includes/footer.php'; ?>

