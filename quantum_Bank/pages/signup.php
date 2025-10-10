<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/audit.php';
include '../includes/send_mail.php';

$settings = include __DIR__ . '/../config/settings.php';
$recaptcha_secret = $settings['recaptcha_secret'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid CSRF token.';
    } elseif (empty($username) || empty($email) || empty($password)) {
        $error = 'Please complete all required fields.';
    } else {
        // Optional reCAPTCHA verification
        if (!empty($recaptcha_secret) && !empty($_POST['g-recaptcha-response'])) {
            $resp = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($recaptcha_secret) . '&response=' . urlencode($_POST['g-recaptcha-response']));
            $obj = json_decode($resp, true);
            if (empty($obj['success'])) {
                $error = 'CAPTCHA verification failed.';
            }
        }
    }

    if (empty($error)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
                $stmt->execute([$username, $email, $hash]);
                $uid = $pdo->lastInsertId();
                // create initial account optionally
                if (!empty($_POST['account_type'])) {
                    include __DIR__ . '/../includes/utils_account.php';
                    $accNum = generateAccountNumber($pdo);
                    $stmt = $pdo->prepare('INSERT INTO accounts (user_id, account_type, account_number, balance) VALUES (?, ?, ?, ?)');
                    $initial = (float)($_POST['initial_deposit'] ?? 0);
                    $stmt->execute([$uid, $_POST['account_type'], $accNum, $initial]);
                    $accId = $pdo->lastInsertId();
                    if ($initial > 0) {
                        $stmt = $pdo->prepare('INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute([$uid, $accId, 'Initial deposit', $initial, 'Completed']);
                    }
                }
                $pdo->commit();
                audit_log($pdo, 'user.signup', $uid, ['email' => $email]);
                // auto-login user
                $_SESSION['user_id'] = $uid;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'user';
                header('Location: dashboard.php'); exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Signup failed: ' . $e->getMessage();
            }
        }
    }
}

// Render form
include '../includes/header.php';
?>
<div class="container" style="max-width:900px">
    <div class="form-container">
        <h2>Create your QuantumBank account</h2>
        <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST" id="signupFormPage">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Full name</label>
                    <input id="username" name="username" type="text" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <div class="form-group">
                    <label for="passwordConfirm">Confirm password</label>
                    <input id="passwordConfirm" name="password_confirm" type="password" required>
                </div>
            </div>
            <div style="margin-top:12px;margin-bottom:6px">Optional: create an account now</div>
            <div class="form-row">
                <div class="form-group">
                    <label for="account_type">Account Type</label>
                    <select id="account_type" name="account_type">
                        <option value="">-- none --</option>
                        <option value="Savings">Savings</option>
                        <option value="Checking">Checking</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="initial_deposit">Initial Deposit</label>
                    <input id="initial_deposit" name="initial_deposit" type="number" step="0.01">
                </div>
            </div>
            <?php if (!empty($settings['recaptcha_site_key'])): ?>
                <div style="margin:12px 0"><div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($settings['recaptcha_site_key']); ?>"></div></div>
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            <?php endif; ?>
            <button class="submit-btn" type="submit">Create account</button>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
