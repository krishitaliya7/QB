<?php
// Minimal loan approved template that returns HTML content when included
$user = $user ?? null; // caller may set $user
$loan = $loan ?? null;
$loanId = $loan['id'] ?? '';
$amount = isset($loan['amount']) ? number_format($loan['amount'],2) : '';
ob_start();
?>
<p>Hello <?php echo htmlspecialchars($user['username'] ?? ''); ?>,</p>
<p>Your loan request (ID: <?php echo htmlspecialchars($loanId); ?>) for $<?php echo $amount; ?> has been approved and disbursed to your account.</p>
<p>Thank you for using Quantum Bank.</p>
<?php
return ob_get_clean();
?>
