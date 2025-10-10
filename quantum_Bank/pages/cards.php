<?php
include '../includes/db_connect.php';
include '../includes/session.php';
requireLogin();

$page_css = 'cards.css';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_type = filter_input(INPUT_POST, 'card_type', FILTER_SANITIZE_STRING);
    $delivery_address = filter_input(INPUT_POST, 'delivery_address', FILTER_SANITIZE_STRING);
    $csrf_token = $_POST['csrf_token'];
    $user_id = $_SESSION['user_id'];

    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        try {
            // Generate a pseudo token (in real system use a PCI-compliant vault)
            $token = bin2hex(random_bytes(24));
            $card_number = '4000' . rand(1000000000, 9999999999);
            $last4 = substr($card_number, -4);
            $expiry_date = date('m/y', strtotime('+2 years'));
            // Store only last4 and a token reference; do NOT store CVV
            $stmt = $pdo->prepare("INSERT INTO cards (user_id, card_type, card_last4, card_token, expiry_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $card_type, $last4, $token, $expiry_date]);
            $success = "Card request submitted successfully! We'll notify you when it's shipped.";
            // Audit and notify
            include '../includes/send_mail.php';
            include '../includes/audit.php';
            audit_log($pdo, 'card.request', $user_id, ['card_type' => $card_type, 'last4' => $last4]);
            $stmt = $pdo->prepare('SELECT email, username FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$user_id]);
            $r = $stmt->fetch();
            if ($r) send_mail($r['email'], 'Card request received', "Your card ending in $last4 has been requested. We'll notify you when it's shipped.");
        } catch (PDOException $e) {
            $error = "Card request failed: " . $e->getMessage();
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<h2>Banking Cards Options</h2>
<div class="row">
    <div class="col-md-4"><div class="card"><div class="card-body"><h5>Secure Card</h5><p>Military-grade security.</p></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><h5>Instant Card</h5><p>Instant approval.</p></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><h5>Rewards Card</h5><p>Earn rewards on transactions.</p></div></div></div>
</div>
<h3>Request New Card</h3>
<?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
<form id="cardRequestForm" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <div class="mb-3">
        <label for="cardType" class="form-label">Select Card Type</label>
        <select class="form-select" id="cardType" name="card_type" required>
            <option value="">--Please choose an option--</option>
            <option value="Secure">Secure Card</option>
            <option value="Instant">Instant Card</option>
            <option value="Rewards">Rewards Card</option>
            <option value="Virtual">Virtual Card</option>
        </select>
        <div id="cardTypeError" class="text-danger" style="display:none;">Please select a card type.</div>
    </div>
    <div class="mb-3">
        <label for="deliveryAddress" class="form-label">Delivery Address</label>
        <input type="text" class="form-control" id="deliveryAddress" name="delivery_address" required>
        <div id="addressError" class="text-danger" style="display:none;">Delivery address is required.</div>
    </div>
    <button type="submit" class="btn btn-primary">Submit Request</button>
</form>
<h3>Your Existing Cards</h3>
<?php
$stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cards = $stmt->fetchAll();
if ($cards) {
    echo '<ul class="list-group">';
    foreach ($cards as $card) {
        echo "<li class='list-group-item'>" . htmlspecialchars($card['card_type']) . " - **** **** **** " . htmlspecialchars($card['card_last4']) . " (" . htmlspecialchars($card['status']) . ")</li>";
    }
    echo '</ul>';
} else {
    echo '<p>No cards found. Request a new one!</p>';
}
?>
<?php include '../includes/footer.php'; ?>