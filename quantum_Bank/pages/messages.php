<?php
include '../includes/db_connect.php';
include '../includes/session.php';
requireLogin();

$user_id = getUserId(); // Assuming this function exists in session.php

// Spam detection function
function isSpam($message) {
    $spam_keywords = ['urgent', 'free', 'win', 'click here', 'password', 'account suspended', 'verify now', 'limited time', 'congratulations', 'winner', 'phishing', 'fraud', 'scam'];
    $lower_message = strtolower($message);
    foreach ($spam_keywords as $keyword) {
        if (strpos($lower_message, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// CSRF Token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle POST requests for actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        die(json_encode(['status' => 'error', 'message' => 'CSRF validation failed.']));
    }

    $action = $_POST['action'] ?? '';

    try {
        $conn->begin_transaction();

        if ($action === 'mark_read') {
            $message_id = $_POST['message_id'] ?? 0;
            $stmt = $conn->prepare("UPDATE messages SET read_status = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $message_id, $user_id);
            $stmt->execute();
        } elseif ($action === 'delete_message') {
            $message_id = $_POST['message_id'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $message_id, $user_id);
            $stmt->execute();
        } elseif ($action === 'report_spam') {
            $message_id = $_POST['message_id'] ?? 0;
            // Log spam report and delete the message
            $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $message_id, $user_id);
            $stmt->execute();
            // Log to audit
            include '../includes/audit.php';
            audit_log($conn, 'Reported spam message', $user_id, ['message_id' => $message_id]);
        }

        $conn->commit();

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

// Fetch messages
$stmt = $conn->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch unread count
$stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE user_id = ? AND read_status = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['COUNT(*)'];

// Function to generate message row HTML
function generateMessageRow($msg) {
    $is_spam = isSpam($msg['message']);
    $row_class = 'hover:bg-gray-50 transition-colors duration-200' . ($is_spam ? ' border-l-4 border-red-500 bg-red-50' : '');
    $html = '<tr class="' . $row_class . '" data-status="' . ($msg['read_status'] ? 'read' : 'unread') . '">';
    $html .= '<td class="px-6 py-4 text-sm text-gray-800 font-medium">' . htmlspecialchars($msg['type']) . '</td>';
    $html .= '<td class="px-6 py-4 text-sm text-gray-600">' . htmlspecialchars(substr($msg['message'], 0, 100)) . (strlen($msg['message']) > 100 ? '...' : '') . '</td>';
    $html .= '<td class="px-6 py-4 text-sm text-gray-500">' . htmlspecialchars($msg['created_at']) . '</td>';
    $html .= '<td class="px-6 py-4 text-sm">';
    $html .= '<span class="' . ($msg['read_status'] ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700') . ' px-2 py-1 rounded-full font-semibold mr-1">' . ($msg['read_status'] ? 'Read' : 'Unread') . '</span>';
    if ($is_spam) {
        $html .= '<span class="bg-red-100 text-red-700 px-2 py-1 rounded-full font-semibold">Potential Spam</span>';
    }
    $html .= '</td>';
    $html .= '<td class="px-6 py-4 text-sm">';
    if (!$msg['read_status']) {
        $html .= '<button onclick="markRead(' . $msg['id'] . ')" class="bg-green-500 text-white px-3 py-1 rounded-lg hover:bg-green-600 transition-colors duration-200 text-sm mr-2">Mark Read</button>';
    }
    $html .= '<button onclick="deleteMessage(' . $msg['id'] . ')" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 transition-colors duration-200 text-sm mr-2">Delete</button>';
    if ($is_spam) {
        $html .= '<button onclick="reportSpam(' . $msg['id'] . ')" class="bg-orange-500 text-white px-3 py-1 rounded-lg hover:bg-orange-600 transition-colors duration-200 text-sm">Report Spam</button>';
    } else {
        $html .= '<button onclick="reportSpam(' . $msg['id'] . ')" class="bg-gray-500 text-white px-3 py-1 rounded-lg hover:bg-gray-600 transition-colors duration-200 text-sm">Report as Spam</button>';
    }
    $html .= '</td></tr>';
    return $html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Inbox - Quantum Bank</title>
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
<body class="bg-gray-50 font-sans antialiased">
    <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">My Inbox (<?php echo $unread_count; ?> unread)</h1>
        <section>
            <div class="overflow-x-auto bg-white rounded-xl shadow-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                 
                    <tbody id="messages-body" class="divide-y divide-gray-200">
                        <?php if (empty($messages)): ?>
                            <tr><td colspan="5" class="px-6 py-4 text-center text-gray-600">No messages found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <?= generateMessageRow($msg) ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>



    <!-- JavaScript for Interactions and Real-time Updates -->
    <script>
        const csrfToken = '<?php echo $csrf_token; ?>';

        function postAction(action, data = {}) {
            const formData = new URLSearchParams();
            formData.append('csrf_token', csrfToken);
            formData.append('action', action);
            for (const key in data) {
                formData.append(key, data[key]);
            }

            return fetch('', {
                method: 'POST',
                body: formData
            }).then(response => response.json());
        }

        function markRead(id) {
            postAction('mark_read', { message_id: id })
                .then(data => {
                    if (data.status === 'success') {
                        updateMessages();
                        showToast('Message marked as read.', 'success');
                    } else {
                        showToast(data.message, 'error');
                    }
                });
        }

        function deleteMessage(id) {
            if (confirm('Are you sure you want to delete this message?')) {
                postAction('delete_message', { message_id: id })
                    .then(data => {
                        if (data.status === 'success') {
                            updateMessages();
                            showToast('Message deleted.', 'success');
                        } else {
                            showToast(data.message, 'error');
                        }
                    });
            }
        }

        function reportSpam(id) {
            if (confirm('Are you sure you want to report this message as spam? It will be deleted.')) {
                postAction('report_spam', { message_id: id })
                    .then(data => {
                        if (data.status === 'success') {
                            updateMessages();
                            showToast('Message reported as spam and deleted.', 'success');
                        } else {
                            showToast(data.message, 'error');
                        }
                    });
            }
        }

        function updateMessages() {
            fetch('?ajax=messages')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('messages-body').innerHTML = html;
                    // Update unread count
                    const unread = document.querySelectorAll('[data-status="unread"]').length;
                    document.querySelector('h1').textContent = `Inbox (${unread} unread)`;
                    // Check for spam alerts after update
                    const spamRows = document.querySelectorAll('.border-l-4.border-red-500');
                    if (spamRows.length > 0) {
                        showToast(`Potential spam detected in ${spamRows.length} message(s).`, 'warning');
                    }
                });
        }

        // Initial spam check on load
        document.addEventListener('DOMContentLoaded', function() {
            const spamRows = document.querySelectorAll('.border-l-4.border-red-500');
            if (spamRows.length > 0) {
                showToast(`Potential spam detected in ${spamRows.length} message(s). Consider reporting them.`, 'warning');
            }
        });

        function showToast(message, type) {
            const toast = document.createElement('div');
            let bgColor = 'bg-gray-500'; // default
            if (type === 'success') bgColor = 'bg-green-500';
            else if (type === 'error') bgColor = 'bg-red-500';
            else if (type === 'warning') bgColor = 'bg-yellow-500';
            toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white ${bgColor}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        setInterval(updateMessages, 10000); // Poll every 10 seconds
    </script>

    <?php
    // AJAX handler for messages
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'messages') {
        $stmt = $conn->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($messages)) {
            echo '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-600">No messages found.</td></tr>';
        } else {
            foreach ($messages as $msg) {
                $is_spam = isSpam($msg['message']);
                $row_class = 'hover:bg-gray-50 transition-colors duration-200' . ($is_spam ? ' border-l-4 border-red-500 bg-red-50' : '');
                echo '<tr class="' . $row_class . '" data-status="' . ($msg['read_status'] ? 'read' : 'unread') . '">';
                echo '<td class="px-6 py-4 text-sm text-gray-800 font-medium">' . htmlspecialchars($msg['type']) . '</td>';
                echo '<td class="px-6 py-4 text-sm text-gray-600">' . htmlspecialchars(substr($msg['message'], 0, 100)) . (strlen($msg['message']) > 100 ? '...' : '') . '</td>';
                echo '<td class="px-6 py-4 text-sm text-gray-500">' . htmlspecialchars($msg['created_at']) . '</td>';
                echo '<td class="px-6 py-4 text-sm">';
                echo '<span class="' . ($msg['read_status'] ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700') . ' px-2 py-1 rounded-full font-semibold mr-1">' . ($msg['read_status'] ? 'Read' : 'Unread') . '</span>';
                if ($is_spam) {
                    echo '<span class="bg-red-100 text-red-700 px-2 py-1 rounded-full font-semibold">Potential Spam</span>';
                }
                echo '</td>';
                echo '<td class="px-6 py-4 text-sm">';
                if (!$msg['read_status']) {
                    echo '<button onclick="markRead(' . $msg['id'] . ')" class="bg-green-500 text-white px-3 py-1 rounded-lg hover:bg-green-600 transition-colors duration-200 text-sm mr-2">Mark Read</button>';
                }
                echo '<button onclick="deleteMessage(' . $msg['id'] . ')" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 transition-colors duration-200 text-sm mr-2">Delete</button>';
                if ($is_spam) {
                    echo '<button onclick="reportSpam(' . $msg['id'] . ')" class="bg-orange-500 text-white px-3 py-1 rounded-lg hover:bg-orange-600 transition-colors duration-200 text-sm">Report Spam</button>';
                } else {
                    echo '<button onclick="reportSpam(' . $msg['id'] . ')" class="bg-gray-500 text-white px-3 py-1 rounded-lg hover:bg-gray-600 transition-colors duration-200 text-sm">Report as Spam</button>';
                }
                echo '</td></tr>';
            }
        }
        exit();
    }
    ?>

</body>
</html>
