<?php
include '../includes/db_connect.php';
include '../includes/session.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loan_type = filter_input(INPUT_POST, 'loan_type', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $term_months = filter_input(INPUT_POST, 'term_months', FILTER_SANITIZE_NUMBER_INT);
    $csrf_token = $_POST['csrf_token'];
    $user_id = $_SESSION['user_id'];

    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } elseif ($amount <= 0 || $term_months <= 0) {
        $error = "Invalid loan amount or term.";
    } else {
        try {
            $interest_rate = $loan_type === 'Personal' ? 5.50 : ($loan_type === 'Business' ? 6.50 : 4.50);
            $stmt = $pdo->prepare("INSERT INTO loans (user_id, loan_type, amount, interest_rate, term_months) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $loan_type, $amount, $interest_rate, $term_months]);
            $success = "Loan application submitted successfully!";
        } catch (PDOException $e) {
            $error = "Loan application failed: " . $e->getMessage();
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<h2>Apply for a Loan</h2>
<?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
<form id="loanForm" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <div class="mb-3">
        <label for="loanType" class="form-label">Loan Type</label>
        <select class="form-select" id="loanType" name="loan_type" required>
            <option value="">--Please choose an option--</option>
            <option value="Personal">Personal Loan</option>
            <option value="Business">Business Loan</option>
            <option value="Home">Home Loan</option>
        </select>
        <div id="loanTypeError" class="text-danger" style="display:none;">Please select a loan type.</div>
    </div>
    <div class="mb-3">
        <label for="loanAmount" class="form-label">Loan Amount (USD)</label>
        <input type="number" class="form-control" id="loanAmount" name="amount" step="0.01" required>
        <div id="loanAmountError" class="text-danger" style="display:none;">Please enter a valid amount greater than 0.</div>
    </div>
    <div class="mb-3">
        <label for="loanTerm" class="form-label">Term (Months)</label>
        <input type="number" class="form-control" id="loanTerm" name="term_months" required>
        <div id="loanTermError" class="text-danger" style="display:none;">Please enter a valid term greater than 0.</div>
    </div>
    <button type="submit" class="btn btn-primary">Submit Application</button>
</form>
<?php include '../includes/footer.php'; ?>