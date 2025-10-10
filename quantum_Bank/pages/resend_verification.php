<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/send_mail.php';
include '../includes/audit.php';
requireLogin();

$page_css = 'login.css';
$user_id = getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid CSRF token.';
    } else {
        // Create a new verification token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 86400);
        $stmt = $pdo->prepare('INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$user_id, $token, $expires]);
        $link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/verify_email.php?token=$token";
        // send
        $stmt = $pdo->prepare('SELECT email, username FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$user_id]);
        $r = $stmt->fetch();
        if ($r) {
            include '../includes/email_helpers.php';
            $html = render_email_template(__DIR__ . '/../includes/email_templates/verify_email.php', ['username' => $r['username'], 'link' => $link]);
            $text = "Verify: $link";
            send_mail($r['email'], 'Verify your email', $text, '', $html);
        }
    audit_log($pdo, 'email.verification.sent', $user_id, ['token' => $token]);
        $success = 'Verification email sent. Check your inbox.';
    }
}

include '../includes/header.php';
?>
<h2>Resend Verification</h2>
<?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <p>Click to resend a verification email to your registered address.</p>
    <button class="btn btn-primary" type="submit">Resend</button>
</form>

<?php include '../includes/footer.php'; ?>
