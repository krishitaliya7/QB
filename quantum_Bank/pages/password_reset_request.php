<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/send_mail.php';
include '../includes/audit.php';

$page_css = 'login.css';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid CSRF token.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], $token, $expires]);
            $link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/password_reset.php?token=$token";
            include '../includes/email_helpers.php';
            $html = render_email_template(__DIR__ . '/../includes/email_templates/password_reset.php', ['username' => $user['username'], 'link' => $link]);
            $msg = "Password reset link: $link";
            send_mail($email, 'Password reset', $msg, '', $html);
            audit_log($pdo, 'password.reset.request', $user['id'], ['token_id' => $pdo->lastInsertId()]);
        }
        // Always show success message to avoid leaking which emails exist
        $success = 'If that email exists in our system, a password reset link has been sent.';
    }
}

include '../includes/header.php';
?>
<h2>Request Password Reset</h2>
<?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <button class="btn btn-primary" type="submit">Send Reset Link</button>
</form>

<?php include '../includes/footer.php'; ?>
