<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/audit.php';
include '../includes/send_mail.php';
requireLogin();
if (!isAdmin()) {
    header('Location: ../pages/dashboard.php'); exit;
}

// Filters: status, user search, date range
$filters = [];
$where = [];
if (!empty($_GET['status'])) {
    $where[] = 'l.status = :status';
    $filters[':status'] = $_GET['status'];
}
if (!empty($_GET['q'])) {
    $where[] = '(u.username LIKE :q OR u.email LIKE :q)';
    $filters[':q'] = '%' . $_GET['q'] . '%';
}
if (!empty($_GET['from_date'])) {
    $where[] = 'l.created_at >= :from_date';
    $filters[':from_date'] = $_GET['from_date'] . ' 00:00:00';
}
if (!empty($_GET['to_date'])) {
    $where[] = 'l.created_at <= :to_date';
    $filters[':to_date'] = $_GET['to_date'] . ' 23:59:59';
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$sqlWhere = '';
if (!empty($where)) {
    $sqlWhere = 'WHERE ' . implode(' AND ', $where);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['loan_id'])) {
    $action = $_POST['action'];
    $loanId = (int)$_POST['loan_id'];
    if ($action === 'approve') {
        // Approve and disburse: credit user's first account or create one
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM loans WHERE id = ? FOR UPDATE');
            $stmt->execute([$loanId]);
            $loan = $stmt->fetch();
            if (!$loan) throw new Exception('Loan not found');
            if ($loan['status'] !== 'Pending') throw new Exception('Loan not pending');
            // Find or create a checking account for user
            $stmt = $pdo->prepare('SELECT id FROM accounts WHERE user_id = ? LIMIT 1');
            $stmt->execute([$loan['user_id']]);
            $acc = $stmt->fetch();
            if (!$acc) {
                $accNum = date('Y') . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare('INSERT INTO accounts (user_id, account_type, account_number, balance) VALUES (?, ?, ?, 0)');
                $stmt->execute([$loan['user_id'], 'Checking', $accNum]);
                $accountId = $pdo->lastInsertId();
            } else {
                $accountId = $acc['id'];
            }
            // Credit loan amount
            $stmt = $pdo->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ?');
            $stmt->execute([$loan['amount'], $accountId]);
            // Record transaction
            $stmt = $pdo->prepare('INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$loan['user_id'], $accountId, 'Loan disbursement for loan #' . $loanId, $loan['amount'], 'Completed']);
            $txnId = $pdo->lastInsertId();
            // Update loan status
            $stmt = $pdo->prepare('UPDATE loans SET status = ?, disbursed = 1, disbursed_at = NOW(), disbursement_txn_id = ? WHERE id = ?');
            $stmt->execute(['Approved', $txnId, $loanId]);
            $pdo->commit();
            audit_log($pdo, 'loan.approved', $_SESSION['user_id'], ['loan_id' => $loanId, 'disbursement_tx' => $txnId]);
            // Notify borrower
            try {
                $stmt = $pdo->prepare('SELECT email, username FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$loan['user_id']]);
                $user = $stmt->fetch();
                if ($user && !empty($user['email'])) {
                    $html = include __DIR__ . '/../includes/email_templates/loan_approved.php';
                    // If the template returns string, use it; otherwise build a simple body
                    if (!is_string($html) || empty($html)) {
                        $html = "<p>Hello " . htmlspecialchars($user['username']) . ",</p><p>Your loan request (#$loanId) for $" . number_format($loan['amount'],2) . " has been approved and disbursed to your account.</p>";
                    }
                    send_mail($user['email'], 'Loan approved', strip_tags($html), '', $html);
                }
            } catch (Exception $e) {
                // ignore notification failure
            }
            $msg = 'Loan approved and disbursed.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = 'Failed: ' . $e->getMessage();
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare('UPDATE loans SET status = ? WHERE id = ?');
        $stmt->execute(['Rejected', $loanId]);
        audit_log($pdo, 'loan.rejected', $_SESSION['user_id'], ['loan_id' => $loanId]);
        // Notify borrower
        try {
            $stmt = $pdo->prepare('SELECT email, username FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$_POST['user_id'] ?? 0]);
            $user = $stmt->fetch();
            if ($user && !empty($user['email'])) {
                $html = "<p>Hello " . htmlspecialchars($user['username']) . ",</p><p>Your loan request (#$loanId) has been rejected. Please contact support for details.</p>";
                send_mail($user['email'], 'Loan rejected', strip_tags($html), '', $html);
            }
        } catch (Exception $e) {}
        $msg = 'Loan rejected.';
    }
}

// Count total for pagination
$countSql = "SELECT COUNT(*) FROM loans l JOIN users u ON u.id = l.user_id $sqlWhere";
$stmt = $pdo->prepare($countSql);
$stmt->execute($filters);
$total = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT l.*, u.email, u.username FROM loans l JOIN users u ON u.id = l.user_id $sqlWhere ORDER BY l.created_at DESC LIMIT :limit OFFSET :offset");
foreach ($filters as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$loans = $stmt->fetchAll();

include '../includes/header.php';
?>
<h2>Loan Applications</h2>
<form method="GET" class="row g-2 mb-3">
    <div class="col-auto"><select name="status" class="form-select"><option value="">All statuses</option><option value="Pending">Pending</option><option value="Approved">Approved</option><option value="Rejected">Rejected</option></select></div>
    <div class="col-auto"><input name="q" class="form-control" placeholder="user or email"></div>
    <div class="col-auto"><input type="date" name="from_date" class="form-control"></div>
    <div class="col-auto"><input type="date" name="to_date" class="form-control"></div>
    <div class="col-auto"><button class="btn btn-secondary">Filter</button></div>
</form>
<?php if (!empty($msg)) echo "<div class='alert alert-info'>" . htmlspecialchars($msg) . "</div>"; ?>
<table class="table">
    <thead><tr><th>ID</th><th>User</th><th>Type</th><th>Amount</th><th>Interest</th><th>Term</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($loans as $l): ?>
        <tr>
            <td><?php echo $l['id']; ?></td>
            <td><?php echo htmlspecialchars($l['username'] . ' (' . $l['email'] . ')'); ?></td>
            <td><?php echo htmlspecialchars($l['loan_type']); ?></td>
            <td>$<?php echo number_format($l['amount'],2); ?></td>
            <td><?php echo number_format($l['interest_rate'],2); ?>%</td>
            <td><?php echo (int)$l['term_months']; ?></td>
            <td><?php echo htmlspecialchars($l['status']); ?></td>
            <td><?php echo htmlspecialchars($l['created_at']); ?></td>
            <td>
                <?php if ($l['status'] === 'Pending'): ?>
                    <form method="POST" style="display:inline-block" onsubmit="return confirmAction(event, 'approve', <?php echo $l['id']; ?>)">
                        <input type="hidden" name="loan_id" value="<?php echo $l['id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $l['user_id']; ?>">
                        <button name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                    </form>
                    <form method="POST" style="display:inline-block" onsubmit="return confirmAction(event, 'reject', <?php echo $l['id']; ?>)">
                        <input type="hidden" name="loan_id" value="<?php echo $l['id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $l['user_id']; ?>">
                        <button name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                    </form>
                <?php else: echo htmlspecialchars($l['status']); endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
$pages = (int)ceil($total / $perPage);
if ($pages > 1):
    echo '<nav><ul class="pagination">';
    for ($i = 1; $i <= $pages; $i++) {
        $active = $i === $page ? ' active' : '';
        $q = http_build_query(array_merge($_GET, ['page' => $i]));
        echo "<li class='page-item$active'><a class='page-link' href='?{$q}'>$i</a></li>";
    }
    echo '</ul></nav>';
endif;
?>

<script>
function confirmAction(e, action, id) {
    e.preventDefault();
    const ok = confirm('Are you sure you want to ' + action + ' loan #' + id + '?');
    if (!ok) return false;
    e.target.submit();
}
</script>

<?php include '../includes/footer.php'; ?>
