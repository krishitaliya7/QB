<?php
include '../includes/db_connect.php';

// Insert a test pending loan older than 2 minutes
$user_id = 1; // Assume admin user ID 1
$loan_type = 'Personal';
$amount = 5000.00;
$interest_rate = 5.50;
$term_months = 12;

// Insert loan with created_at 3 minutes ago
$stmt = $conn->prepare("INSERT INTO loans (user_id, loan_type, amount, interest_rate, term_months, status, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', DATE_SUB(NOW(), INTERVAL 3 MINUTE))");
$stmt->bind_param("isdii", $user_id, $loan_type, $amount, $interest_rate, $term_months);
$stmt->execute();

echo "Test pending loan inserted with ID: " . $conn->insert_id . "\n";
?>
