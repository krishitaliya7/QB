<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/audit.php';

$page_css = 'login.css';

$token = $_GET['token'] ?? null;
if (!$token) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, user_id, expires_at, verified FROM email_verifications WHERE token = ? LIMIT 1');
$stmt->execute([$token]);
$row = $stmt->fetch();
if (!$row) {
    $error = 'Invalid verification token.';
} elseif ($row['verified'] || strtotime($row['expires_at']) < time()) {
    $error = 'Token expired or already verified.';
} else {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE users SET verified = 1 WHERE id = ?');
        $stmt->execute([$row['user_id']]);
        $stmt = $pdo->prepare('UPDATE email_verifications SET verified = 1 WHERE id = ?');
        $stmt->execute([$row['id']]);
        $pdo->commit();
        $success = 'Email verified. You can now log in.';
        audit_log($pdo, 'email.verified', $row['user_id'], ['verification_id' => $row['id']]);
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Verification failed.';
    }
}

include '../includes/header.php';
?>
<h2>Email Verification</h2>
<?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

<?php include '../includes/footer.php'; ?>
