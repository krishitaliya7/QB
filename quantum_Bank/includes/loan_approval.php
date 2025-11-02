<?php
function checkLoanEligibility($conn, $user_id, $loan_type, $amount) {
    // Fetch user balance and banking score
    $stmt = $conn->prepare("SELECT a.balance, u.banking_score FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.user_id = ? AND a.account_type = 'Savings' LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();

    if (!$user_data) {
        return ['eligible' => false, 'reason' => 'No savings account found'];
    }

    $balance = $user_data['balance'];
    $score = $user_data['banking_score'];

    // Criteria based on loan type
    if ($loan_type === 'Personal') {
        if ($balance < 0) {
            return ['eligible' => false, 'reason' => 'Insufficient balance for Personal loan'];
        }
        if ($score < 5.0) {
            return ['eligible' => false, 'reason' => 'Banking score too low for Personal loan'];
        }
    } elseif ($loan_type === 'Business') {
        if ($balance < 100000) {
            return ['eligible' => false, 'reason' => 'Insufficient balance for Business loan'];
        }
        if ($score < 5.0) {
            return ['eligible' => false, 'reason' => 'Banking score too low for Business loan'];
        }
    } elseif ($loan_type === 'Home') {
        if ($balance < 0) {
            return ['eligible' => false, 'reason' => 'Insufficient balance for Home loan'];
        }
        if ($score < 5.0) {
            return ['eligible' => false, 'reason' => 'Banking score too low for Home loan'];
        }
    }

    return ['eligible' => true];
}

function autoApproveLoan($conn, $loan_id) {
    $conn->begin_transaction();
    try {
        // Update loan status to Approved
        $stmt = $conn->prepare("UPDATE loans SET status = 'Approved' WHERE id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();

        // Get loan details
        $stmt = $conn->prepare("SELECT user_id, loan_type, amount FROM loans WHERE id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();

        // Disburse loan amount to user's account
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE user_id = ? AND account_type = 'Savings' LIMIT 1");
        $stmt->bind_param("di", $loan['amount'], $loan['user_id']);
        $stmt->execute();

        // Record disbursement
        $stmt = $conn->prepare("UPDATE loans SET disbursed = 1, disbursed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();

        // Log transaction
        $description = "Loan disbursement: " . $loan['loan_type'] . " - $" . number_format($loan['amount'], 2);
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, account_id, amount, description, status) VALUES (?, (SELECT id FROM accounts WHERE user_id = ? AND account_type = 'Savings' LIMIT 1), ?, ?, 'Completed')");
        $stmt->bind_param("iids", $loan['user_id'], $loan['user_id'], $loan['amount'], $description);
        $stmt->execute();
        $txn_id = $conn->insert_id;

        // Update disbursement_txn_id
        $stmt = $conn->prepare("UPDATE loans SET disbursement_txn_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $txn_id, $loan_id);
        $stmt->execute();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Auto approve loan failed: " . $e->getMessage());
        return false;
    }
}
?>
