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

$stmt = $conn->prepare('SELECT id, user_id, expires_at, verified FROM email_verifications WHERE token = ? LIMIT 1');
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
if (!$row) {
    $error = 'Invalid verification token.';
} elseif ($row['verified'] || strtotime($row['expires_at']) < time()) {
    $error = 'Token expired or already verified.';
} else {
    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare('UPDATE users SET verified = 1 WHERE id = ?');
        $stmt->bind_param("i", $row['user_id']);
        $stmt->execute();
        $stmt = $conn->prepare('UPDATE email_verifications SET verified = 1 WHERE id = ?');
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
        $conn->commit();
        $success = 'Email verified. You can now log in.';
        audit_log($conn, 'email.verified', $row['user_id'], ['verification_id' => $row['id']]);
    } catch (Exception $e) {
        $conn->rollback();
        $error = 'Verification failed.';
    }
}

include '../includes/header.php';
?>
<h2>Email Verification</h2>
<?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

<?php include '../includes/footer.php'; ?>
