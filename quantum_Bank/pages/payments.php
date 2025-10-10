<?php
include '../includes/db_connect.php';
include '../includes/session.php';
requireLogin();

$page_css = 'payments.css';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardholder_name = filter_input(INPUT_POST, 'cardholder_name', FILTER_SANITIZE_STRING);
    $card_number = filter_input(INPUT_POST, 'card_number', FILTER_SANITIZE_STRING);
    $expiry_date = filter_input(INPUT_POST, 'expiry_date', FILTER_SANITIZE_STRING);
    $cvv = filter_input(INPUT_POST, 'cvv', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $csrf_token = $_POST['csrf_token'];
    $user_id = $_SESSION['user_id'];

    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } elseif ($amount <= 0) {
        $error = "Invalid amount.";
    } else {
        try {
            // Simulate card validation
            if (strlen($card_number) === 16 && preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry_date) && strlen($cvv) >= 3) {
                $stmt = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ? LIMIT 1");
                $stmt->execute([$user_id]);
                $account = $stmt->fetch();
                if ($account) {
                    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $account['id'], "Mock Payment to $cardholder_name", $amount, 'Completed']);
                    $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
                    $stmt->execute([$amount, $account['id']]);
                    $success = "Payment of $$amount processed successfully!";
                } else {
                    $error = "No account found.";
                }
            } else {
                $error = "Invalid card details.";
            }
        } catch (PDOException $e) {
            $error = "Payment failed: " . $e->getMessage();
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<h2>Secure Payment Gateway</h2>
<?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
<form id="paymentForm" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <div class="mb-3">
        <label for="cardholderName" class="form-label">Cardholder Name</label>
        <input type="text" class="form-control" id="cardholderName" name="cardholder_name" required>
        <div id="nameError" class="text-danger" style="display:none;">Please enter a valid name.</div>
    </div>
    <div class="mb-3">
        <label for="cardNumber" class="form-label">Card Number</label>
        <input type="text" class="form-control" id="cardNumber" name="card_number" required>
        <div id="cardError" class="text-danger" style="display:none;">Enter a valid 16-digit card number.</div>
    </div>
    <div class="mb-3">
        <label for="expiryDate" class="form-label">Expiry Date (MM/YY)</label>
        <input type="text" class="form-control" id="expiryDate" name="expiry_date" required>
        <div id="expiryError" class="text-danger" style="display:none;">Enter a valid expiry date.</div>
    </div>
    <div class="mb-3">
        <label for="cvv" class="form-label">CVV</label>
        <input type="text" class="form-control" id="cvv" name="cvv" required>
        <div id="cvvError" class="text-danger" style="display:none;">Enter a valid 3 or 4 digit CVV.</div>
    </div>
    <div class="mb-3">
        <label for="amount" class="form-label">Amount (USD)</label>
        <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
        <div id="amountError" class="text-danger" style="display:none;">Please enter a valid amount greater than 0.</div>
    </div>
    <button type="submit" class="btn btn-primary">Pay Now</button>
</form>
<?php include '../includes/footer.php'; ?>