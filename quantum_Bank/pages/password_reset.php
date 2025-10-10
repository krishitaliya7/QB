<?php
include '../includes/db_connect.php';
include '../includes/session.php';

$page_css = 'login.css';

$token = $_GET['token'] ?? null;
if (!$token) {
    header('Location: login.php');
    exit;
}

// Validate token
$stmt = $pdo->prepare('SELECT pr.id, pr.user_id, pr.expires_at, pr.used, u.email FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE pr.token = ? LIMIT 1');
$stmt->execute([$token]);
$row = $stmt->fetch();
if (!$row || $row['used'] || strtotime($row['expires_at']) < time()) {
    $error = 'Invalid or expired token.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid CSRF token.';
    } else {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$password, $row['user_id']]);
            $stmt = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
            $stmt->execute([$row['id']]);
            $pdo->commit();
            $success = 'Password reset. You may now log in.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to reset password.';
        }
    }
}

include '../includes/header.php';
?>
<h2>Reset Password</h2>
<?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
<?php if (empty($success) && empty($error)): ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <div class="mb-3">
        <label>New Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary" type="submit">Set Password</button>
</form>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
