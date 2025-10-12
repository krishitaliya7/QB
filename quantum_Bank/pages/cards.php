<?php
include '../includes/db_connect.php';
include '../includes/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// CSRF Token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        die("CSRF validation failed.");
    }

    $action = $_POST['action'] ?? '';

    try {
        $conn->begin_transaction();

        if ($action === 'apply_card') {
            $card_type = $_POST['card_type'] ?? '';
            if (!in_array($card_type, ['Debit', 'Credit'])) {
                throw new Exception("Invalid card type.");
            }
            // Generate random last four and expiry (for demo; in real, use proper generation)
            $last_four = rand(1000, 9999);
            $expiry_date = date('Y-m-d', strtotime('+3 years'));
            $stmt = $conn->prepare("INSERT INTO cards (user_id, card_type, card_last4, expiry_date, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("isss", $user_id, $card_type, $last_four, $expiry_date);
            $stmt->execute();
        } elseif ($action === 'activate' || $action === 'deactivate' || $action === 'block') {
            $card_id = $_POST['card_id'] ?? 0;
            $new_status = ($action === 'activate') ? 'active' : (($action === 'deactivate') ? 'inactive' : 'blocked');
            $stmt = $conn->prepare("UPDATE cards SET status = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $new_status, $card_id, $user_id);
            $stmt->execute();
        } elseif ($is_admin && ($action === 'approve' || $action === 'reject')) {
            $card_id = $_POST['card_id'] ?? 0;
            if ($action === 'approve') {
                $stmt = $conn->prepare("UPDATE cards SET status = 'active' WHERE id = ?");
                $stmt->bind_param("i", $card_id);
                $stmt->execute();
            } elseif ($action === 'reject') {
                $stmt = $conn->prepare("DELETE FROM cards WHERE id = ?");
                $stmt->bind_param("i", $card_id);
                $stmt->execute();
            }
        } elseif ($is_admin && $action === 'admin_update_status') {
            $card_id = $_POST['card_id'] ?? 0;
            $new_status = $_POST['new_status'] ?? '';
            if (!in_array($new_status, ['active', 'inactive', 'blocked'])) {
                throw new Exception("Invalid status.");
            }
            $stmt = $conn->prepare("UPDATE cards SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $card_id);
            $stmt->execute();
        }

        $conn->commit();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Fetch user's cards
$stmt = $conn->prepare("SELECT * FROM cards WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_cards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all cards if admin
$all_cards = [];
if ($is_admin) {
    $stmt = $conn->prepare("SELECT c.*, u.username FROM cards c JOIN users u ON c.user_id = u.user_id ORDER BY c.id DESC");
    $stmt->execute();
    $all_cards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Management - Quantum Bank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1D4ED8',
                        secondary: '#3B82F6',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8 text-primary">Card Management</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- User's Cards Section -->
        <section class="mb-12">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold">Your Cards</h2>
                <button onclick="document.getElementById('applyModal').showModal()" class="bg-primary text-white px-4 py-2 rounded hover:bg-secondary">Apply for New Card</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($user_cards as $card): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                        <div class="mb-4">
                            <h3 class="text-xl font-bold"><?= htmlspecialchars($card['card_type']) ?> Card</h3>
                            <p class="text-gray-600">**** **** **** <?= htmlspecialchars($card['card_last4']) ?></p>
                        </div>
                        <div class="mb-4">
                            <p>Expiry: <?= htmlspecialchars($card['expiry_date']) ?></p>
                            <p>Status: <span class="<?= getStatusColor($card['status']) ?>"><?= htmlspecialchars(ucfirst($card['status'])) ?></span></p>
                        </div>
                        <?php if ($card['status'] !== 'pending' && $card['status'] !== 'blocked'): ?>
                            <div class="flex space-x-2">
                                <?php if ($card['status'] === 'inactive'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                                        <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">Activate</button>
                                    </form>
                                <?php elseif ($card['status'] === 'active'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="action" value="deactivate">
                                        <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                                        <button type="submit" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">Deactivate</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="action" value="block">
                                    <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600" onclick="return confirm('Are you sure you want to block this card?')">Block</button>
                                </form>
                            </div>
                        <?php elseif ($card['status'] === 'pending'): ?>
                            <p class="text-yellow-600">Pending Approval</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($user_cards)): ?>
                    <p class="text-center text-gray-600 col-span-full">No cards found.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Admin Section -->
        <?php if ($is_admin): ?>
            <section>
                <h2 class="text-2xl font-semibold mb-4">Admin: All User Cards</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-300">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="px-4 py-2">User</th>
                                <th class="px-4 py-2">Type</th>
                                <th class="px-4 py-2">Last 4</th>
                                <th class="px-4 py-2">Expiry</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_cards as $card): ?>
                                <tr class="hover:bg-gray-100">
                                    <td class="px-4 py-2"><?= htmlspecialchars($card['username']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($card['card_type']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($card['card_last4']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($card['expiry_date']) ?></td>
                                    <td class="px-4 py-2"><span class="<?= getStatusColor($card['status']) ?>"><?= htmlspecialchars(ucfirst($card['status'])) ?></span></td>
                                    <td class="px-4 py-2">
                                        <?php if ($card['status'] === 'pending'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                                                <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">Approve</button>
                                            </form>
                                            <form method="POST" class="inline ml-2">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                                                <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="action" value="admin_update_status">
                                                <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                                                <select name="new_status" class="border px-2 py-1">
                                                    <option value="active" <?= $card['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="inactive" <?= $card['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                    <option value="blocked" <?= $card['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                                                </select>
                                                <button type="submit" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 ml-2">Update</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($all_cards)): ?>
                                <tr><td colspan="6" class="text-center py-4">No cards found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <!-- Apply New Card Modal -->
    <dialog id="applyModal" class="p-0 rounded-lg shadow-xl">
        <div class="bg-white p-6">
            <h3 class="text-xl font-bold mb-4">Apply for New Card</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="apply_card">
                <label class="block mb-2">Card Type:</label>
                <select name="card_type" class="w-full border px-3 py-2 mb-4 rounded">
                    <option value="Debit">Debit</option>
                    <option value="Credit">Credit</option>
                </select>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('applyModal').close()" class="bg-gray-300 px-4 py-2 rounded">Cancel</button>
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded hover:bg-secondary">Apply</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- JavaScript for Real-time Updates (Polling every 10 seconds) -->
    <script>
        function updateUserCards() {
            fetch('<?= $_SERVER['PHP_SELF'] ?>?ajax=user_cards', { method: 'GET' })
                .then(response => response.text())
                .then(html => {
                    document.querySelector('.grid').innerHTML = html;
                });
        }

        <?php if ($is_admin): ?>
        function updateAllCards() {
            fetch('<?= $_SERVER['PHP_SELF'] ?>?ajax=all_cards', { method: 'GET' })
                .then(response => response.text())
                .then(html => {
                    document.querySelector('table tbody').innerHTML = html;
                });
        }
        <?php endif; ?>

        setInterval(() => {
            updateUserCards();
            <?php if ($is_admin): ?>updateAllCards();<?php endif; ?>
        }, 10000); // Poll every 10 seconds
    </script>

    <?php
    // AJAX handlers
    if (isset($_GET['ajax'])) {
        if ($_GET['ajax'] === 'user_cards') {
            $stmt = $conn->prepare("SELECT * FROM cards WHERE user_id = ? ORDER BY id DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_cards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach ($user_cards as $card) {
                echo '<div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">';
                echo '<div class="mb-4"><h3 class="text-xl font-bold">' . htmlspecialchars($card['card_type']) . ' Card</h3>';
                echo '<p class="text-gray-600">**** **** **** ' . htmlspecialchars($card['card_last4']) . '</p></div>';
                echo '<div class="mb-4"><p>Expiry: ' . htmlspecialchars($card['expiry_date']) . '</p>';
                echo '<p>Status: <span class="' . getStatusColor($card['status']) . '">' . htmlspecialchars(ucfirst($card['status'])) . '</span></p></div>';
                if ($card['status'] !== 'pending' && $card['status'] !== 'blocked') {
                    echo '<div class="flex space-x-2">';
                    if ($card['status'] === 'inactive') {
                        echo '<form method="POST"><input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
                        echo '<input type="hidden" name="action" value="activate"><input type="hidden" name="card_id" value="' . $card['id'] . '">';
                        echo '<button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">Activate</button></form>';
                    } elseif ($card['status'] === 'active') {
                        echo '<form method="POST"><input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
                        echo '<input type="hidden" name="action" value="deactivate"><input type="hidden" name="card_id" value="' . $card['id'] . '">';
                        echo '<button type="submit" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">Deactivate</button></form>';
                    }
                    echo '<form method="POST"><input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
                    echo '<input type="hidden" name="action" value="block"><input type="hidden" name="card_id" value="' . $card['id'] . '">';
                    echo '<button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600" onclick="return confirm(\'Are you sure?\')">Block</button></form>';
                    echo '</div>';
                } elseif ($card['status'] === 'pending') {
                    echo '<p class="text-yellow-600">Pending Approval</p>';
                }
                echo '</div>';
            }
            if (empty($user_cards)) {
                echo '<p class="text-center text-gray-600 col-span-full">No cards found.</p>';
            }
            exit();
        } elseif ($is_admin && $_GET['ajax'] === 'all_cards') {
            $stmt = $conn->prepare("SELECT c.*, u.username FROM cards c JOIN users u ON c.user_id = u.user_id ORDER BY c.id DESC");
            $stmt->execute();
            $all_cards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach ($all_cards as $card) {
                echo '<tr class="hover:bg-gray-100">';
                echo '<td class="px-4 py-2">' . htmlspecialchars($card['username']) . '</td>';
                echo '<td class="px-4 py-2">' . htmlspecialchars($card['card_type']) . '</td>';
                echo '<td class="px-4 py-2">' . htmlspecialchars($card['card_last4']) . '</td>';
                echo '<td class="px-4 py-2">' . htmlspecialchars($card['expiry_date']) . '</td>';
                echo '<td class="px-4 py-2"><span class="' . getStatusColor($card['status']) . '">' . htmlspecialchars(ucfirst($card['status'])) . '</span></td>';
                echo '<td class="px-4 py-2">';
                if ($card['status'] === 'pending') {
                    echo '<form method="POST" class="inline"><input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
                    echo '<input type="hidden" name="action" value="approve"><input type="hidden" name="card_id" value="' . $card['id'] . '">';
                    echo '<button type="submit" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">Approve</button></form>';
                    echo '<form method="POST" class="inline ml-2"><input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
                    echo '<input type="hidden" name="action" value="reject"><input type="hidden" name="card_id" value="' . $card['id'] . '">';
                    echo '<button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Reject</button></form>';
                } else {
                    echo '<form method="POST" class="inline"><input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
                    echo '<input type="hidden" name="action" value="admin_update_status"><input type="hidden" name="card_id" value="' . $card['id'] . '">';
                    echo '<select name="new_status" class="border px-2 py-1">';
                    echo '<option value="active" ' . ($card['status'] === 'active' ? 'selected' : '') . '>Active</option>';
                    echo '<option value="inactive" ' . ($card['status'] === 'inactive' ? 'selected' : '') . '>Inactive</option>';
                    echo '<option value="blocked" ' . ($card['status'] === 'blocked' ? 'selected' : '') . '>Blocked</option>';
                    echo '</select>';
                    echo '<button type="submit" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 ml-2">Update</button></form>';
                }
                echo '</td></tr>';
            }
            if (empty($all_cards)) {
                echo '<tr><td colspan="6" class="text-center py-4">No cards found.</td></tr>';
            }
            exit();
        }
    }
    ?>

    <?php
    function getStatusColor($status) {
        switch ($status) {
            case 'active': return 'text-green-600 font-bold';
            case 'inactive': return 'text-yellow-600 font-bold';
            case 'blocked': return 'text-red-600 font-bold';
            case 'pending': return 'text-orange-600 font-bold';
            default: return 'text-gray-600';
        }
    }
    ?>
</body>
</html>