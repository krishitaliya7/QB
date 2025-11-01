<?php
include '../includes/db_connect.php';
include '../includes/loan_approval.php';

// Simulate admin session
session_start();
$_SESSION['is_admin'] = true;

// Fetch pending loans older than 2 minutes
$stmt = $conn->prepare("SELECT l.id, l.user_id, l.loan_type, l.amount FROM loans l WHERE l.status = 'Pending' AND l.created_at < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
$stmt->execute();
$pending_loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "Pending loans older than 2 minutes: " . count($pending_loans) . PHP_EOL;

foreach ($pending_loans as $loan) {
    $eligibility = checkLoanEligibility($conn, $loan['user_id'], $loan['loan_type'], $loan['amount']);
    if ($eligibility['eligible']) {
        $result = autoApproveLoan($conn, $loan['id']);
        echo "Auto-approved loan ID " . $loan['id'] . ": " . ($result ? "Success" : "Failed") . PHP_EOL;
    } else {
        echo "Loan ID " . $loan['id'] . " not eligible: " . $eligibility['reason'] . PHP_EOL;
    }
}
?>
