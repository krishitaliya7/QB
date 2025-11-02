<?php include __DIR__ . '/session.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuantumBank</title>
    <!-- Tailwind CDN for utility-first styling (used by several pages) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js for dashboard charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/QB/css/index.css">
    <link rel="stylesheet" href="/QB/css/style.css">
    <!-- Bootstrap JS for dropdowns -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markRead(messageId) {
            fetch('pages/mark_read.php?message_id=' + messageId, { method: 'GET' })
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        // Optionally update the UI without reload
                        location.reload();
                    }
                });
        }

        // Handle resend verification form submission
        document.addEventListener('DOMContentLoaded', function() {
            const resendForm = document.getElementById('resendForm');
            const resendBtn = document.getElementById('resendBtn');
            if (resendForm && resendBtn) {
                resendForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    resendBtn.disabled = true;
                    resendBtn.textContent = 'Sending...';

                    const formData = new FormData(resendForm);
                    fetch(resendForm.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.includes('Verification email sent')) {
                            resendBtn.textContent = 'Sent!';
                            setTimeout(() => {
                                resendBtn.disabled = false;
                                resendBtn.textContent = 'Resend';
                            }, 3000);
                        } else {
                            resendBtn.disabled = false;
                            resendBtn.textContent = 'Resend';
                            alert('Failed to send verification email. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        resendBtn.disabled = false;
                        resendBtn.textContent = 'Resend';
                        alert('An error occurred. Please try again.');
                    });
                });
            }
        });
    </script>
    <style>
        /* Small page-specific overrides to ensure pixel matching */
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php" style="cursor: pointer;">Quantum Banking</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="open_account.php">Accounts</a></li>
                    <li class="nav-item"><a class="nav-link" href="payments.php">Payments</a></li>
                    <li class="nav-item"><a class="nav-link" href="cards.php">Cards</a></li>
                    <li class="nav-item"><a class="nav-link" href="loan.php">Loans</a></li>
                    <li class="nav-item"><a class="nav-link" href="atm_locator.php">ATM Locator</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="messages.php">Messages <span class="badge bg-danger" id="unreadCount"><?php echo get_unread_messages_count(getUserId()); ?></span></a></li>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Hello, <?php echo getUsername(); ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <?php if (function_exists('renderFlashes')) renderFlashes(); ?>
        <?php if (isLoggedIn()):
            // check verification quickly
            $verified = false;
            try {
                if (isset($conn)) {
                    $user_id = getUserId();
                    $stmt = $conn->prepare('SELECT verified FROM users WHERE id = ? LIMIT 1');
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $r = $stmt->get_result()->fetch_assoc();
                    $verified = $r && $r['verified'];
                }
            } catch (Exception $e) { }
            if (!$verified): ?>
                <div class="alert alert-warning d-flex justify-content-between align-items-center">
                    <div>Your email is not verified. Please verify to unlock all features.</div>
                    <form method="POST" action="pages/resend_verification.php" id="resendForm" class="m-0">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="ajax" value="1">
                        <button type="submit" class="btn btn-sm btn-outline-primary" id="resendBtn">Resend</button>
                    </form>
                </div>
        <?php endif; endif; ?>


