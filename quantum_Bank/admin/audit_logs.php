<?php
include '../includes/db_connect.php';
include '../includes/session.php';
requireLogin();
// Require admin role
if (!isAdmin()) {
    header('Location: ../pages/dashboard.php');
    exit;
}
// Very simple admin access: in a real app restrict by role
$page_css = 'index.css';

$q = trim($_GET['q'] ?? '');
$export = isset($_GET['export']);

$params = [];
$sql = 'SELECT a.*, u.email as user_email FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id';
if ($q !== '') {
    $sql .= ' WHERE a.action LIKE ? OR u.email LIKE ?';
    $params[] = "%$q%"; $params[] = "%$q%";
}
$sql .= ' ORDER BY a.created_at DESC LIMIT 1000';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

if ($export) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_logs.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','user_id','user_email','action','meta','ip','created_at']);
    foreach ($rows as $r) fputcsv($out, [$r['id'],$r['user_id'],$r['user_email'],$r['action'],$r['meta'],$r['ip'],$r['created_at']]);
    exit;
}

include '../includes/header.php';
?>
<h2>Audit Logs</h2>
<form method="GET" class="mb-3">
    <input type="text" name="q" class="form-control d-inline-block w-50" placeholder="search action or email" value="<?php echo htmlspecialchars($q); ?>">
    <button class="btn btn-primary mt-2" type="submit">Filter</button>
    <a class="btn btn-outline-secondary mt-2" href="?export=1">Export CSV</a>
</form>
<table class="table table-sm">
    <thead><tr><th>Date</th><th>User</th><th>Action</th><th>Meta</th><th>IP</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
            <td><?php echo htmlspecialchars($r['user_email'] ?? $r['user_id']); ?></td>
            <td><?php echo htmlspecialchars($r['action']); ?></td>
            <td><?php echo htmlspecialchars($r['meta']); ?></td>
            <td><?php echo htmlspecialchars($r['ip']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>
