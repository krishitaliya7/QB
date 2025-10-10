<?php
include '../includes/db_connect.php';
include '../includes/session.php';
requireLogin();

$page_css = 'index.css';
$user_id = getUserId();

$perPage = 20;
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM transactions WHERE user_id = ?');
$stmt->execute([$user_id]);
$total = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT t.*, a.account_number FROM transactions t LEFT JOIN accounts a ON a.id = t.account_id WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();

include '../includes/header.php';
?>
<h2>Your Transactions</h2>
<table class="table">
    <thead><tr><th>Date</th><th>Account</th><th>Description</th><th>Amount</th><th>Status</th></tr></thead>
    <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?php echo htmlspecialchars($t['created_at']); ?></td>
                <td><?php echo htmlspecialchars($t['account_number'] ?? $t['account_id']); ?></td>
                <td><?php echo htmlspecialchars($t['description']); ?></td>
                <td>$<?php echo number_format($t['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($t['status']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
$pages = ceil($total / $perPage);
for ($i = 1; $i <= $pages; $i++) {
    echo '<a class="btn btn-sm btn-outline-secondary me-1" href="?p=' . $i . '">' . $i . '</a>';
}
include '../includes/footer.php';
