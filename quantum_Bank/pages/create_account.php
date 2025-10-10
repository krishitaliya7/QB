<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/utils_account.php';
requireLogin();

$page_css = 'index.css';

$user_id = getUserId();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = filter_input(INPUT_POST, 'account_type', FILTER_SANITIZE_STRING);
    $initial = filter_input(INPUT_POST, 'initial_deposit', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid CSRF token.';
    } elseif (!in_array($type, ['Savings','Checking','Business'])) {
        $error = 'Invalid account type.';
    } elseif ($initial < 0) {
        $error = 'Invalid initial deposit.';
    } else {
        try {
            $pdo->beginTransaction();
            $accNumber = generateAccountNumber($pdo);
            $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_type, account_number, balance) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $type, $accNumber, $initial ?: 0]);
            $accountId = $pdo->lastInsertId();
            if ($initial > 0) {
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $accountId, 'Initial deposit', $initial, 'Completed']);
            }
            $pdo->commit();
            $success = 'Account created successfully.';
            audit_log($pdo, 'account.create', $user_id, ['account_id' => $accountId, 'account_number' => $accNumber, 'type' => $type]);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to create account: ' . $e->getMessage();
        }
    }
}
include '../includes/header.php';
?>
<h2>Create a New Account</h2>
<?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <div class="mb-3">
        <label>Account Type</label>
        <select name="account_type" class="form-control">
            <option value="">--Choose--</option>
            <option value="Savings">Savings</option>
            <option value="Checking">Checking</option>
            <option value="Business">Business</option>
        </select>
    </div>
    <div class="mb-3">
        <label>Initial Deposit (USD)</label>
        <input type="number" name="initial_deposit" step="0.01" class="form-control">
    </div>
    <button class="btn btn-primary" type="submit">Open Account</button>
</form>
<?php include '../includes/footer.php'; ?>
