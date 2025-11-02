<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/audit.php';
include '../includes/send_mail.php';
requireLogin();

$userId = getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid CSRF token.';
    } elseif (empty($otp)) {
        $error = 'Please enter the OTP code.';
    } else {
        try {
            $settings = include __DIR__ . '/../admin/config/settings.php';
            $maxAttempts = $settings['otp_max_attempts'] ?? 5;
            $cooldown = $settings['otp_cooldown_seconds'] ?? 900;

            $conn->begin_transaction();
            // lock on user's most recent unused OTP within expiry window
            $stmt = $conn->prepare('SELECT * FROM transfer_otps WHERE user_id = ? AND used = 0 AND expires_at >= NOW() ORDER BY created_at DESC LIMIT 1 FOR UPDATE');
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if (!$row) throw new Exception('No pending transfer verification found or it has expired.');

            // enforce max attempts
            if ((int)$row['attempts'] >= (int)$row['max_attempts']) {
                throw new Exception('This OTP has been locked due to too many failed attempts. Please initiate the transfer again later.');
            }

            // verify against hashed otp
            if (!password_verify($otp, $row['otp_hash'])) {
                // increment attempts
                $stmt = $conn->prepare('UPDATE transfer_otps SET attempts = attempts + 1 WHERE id = ?');
                $stmt->bind_param("i", $row['id']);
                $stmt->execute();
                $conn->commit();
                throw new Exception('Invalid OTP code.');
            }

            // mark used
            $stmt = $conn->prepare('UPDATE transfer_otps SET used = 1 WHERE id = ?');
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();

            // perform transfer similar to transfer.php (locking accounts)
            $from = (int)$row['from_account'];
            $to = (int)$row['to_account'];
            $amount = (float)$row['amount'];
            // Lock accounts
            $stmt = $conn->prepare('SELECT id, user_id, balance FROM accounts WHERE id IN (?, ?) FOR UPDATE');
            $stmt->bind_param("ii", $from, $to);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $balances = [];
            $owners = [];
            foreach ($rows as $r) {
                $balances[$r['id']] = (float)$r['balance'];
                $owners[$r['id']] = (int)$r['user_id'];
            }
            if (!isset($balances[$from]) || !isset($balances[$to])) throw new Exception('Account not found.');
            if ($balances[$from] < $amount) throw new Exception('Insufficient funds.');
            // update balances
            $stmt = $conn->prepare('UPDATE accounts SET balance = balance - ? WHERE id = ?');
            $stmt->bind_param("di", $amount, $from);
            $stmt->execute();
            $stmt = $conn->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ?');
            $stmt->bind_param("di", $amount, $to);
            $stmt->execute();
            // record transactions
            $stmt = $conn->prepare('INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, "Completed")');
            $stmt->bind_param("iisd", $userId, $from, 'Transfer to account ' . $to, -$amount);
            $stmt->execute();
            $recipientUserId = $owners[$to];
            $stmt->bind_param("iisd", $recipientUserId, $to, 'Transfer from account ' . $from, $amount);
            $stmt->execute();
            $conn->commit();
            audit_log($conn, 'transfer.completed.otp', $userId, ['from' => $from, 'to' => $to, 'amount' => $amount, 'otp_id' => $row['id']]);
            $success = 'Transfer completed successfully.';
            // Add message to inbox
            add_message($userId, 'confirmation', "Your transfer of $" . number_format($amount, 2) . " from account $from to account $to has been completed successfully.");
            // notify recipient
            try {
                $stmt = $conn->prepare('SELECT email, username FROM users WHERE id = ? LIMIT 1');
                $stmt->bind_param("i", $recipientUserId);
                $stmt->execute();
                $result = $stmt->get_result();
                $r = $result->fetch_assoc();
                if ($r && !empty($r['email'])) {
                    $msg = "Hello {$r['username']},\n\nYou have received a transfer of $" . number_format($amount,2) . " to your account (ID: $to).";
                    send_mail($r['email'], 'Incoming transfer', $msg);
                }
            } catch (Exception $e) {}
        } catch (Exception $e) {
            try { $conn->rollback(); } catch (Exception $ex) {}
            $error = 'Failed to complete transfer: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Transfer - QuantumBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #1e40af;
            --secondary: #10b981;
            --accent: #f59e0b;
            --dark: #1e293b;
            --light: #f8fafc;
            --text: #475569;
            --error: #ef4444;
            --success: #22c55e;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--light);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--primary) 0%, #60a5fa 100%);
        }

        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .input-focus {
            transition: all 0.3s ease;
        }

        .input-focus:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            outline: none;
        }

        .btn-primary {
            background-color: var(--primary);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .otp-input {
            letter-spacing: 0.5em;
            font-size: 1.5rem;
            text-align: center;
            font-weight: 600;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background-color: var(--dark);
                color: #e2e8f0;
            }
            .gradient-bg {
                background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            }
            .card {
                background-color: #2d3748;
            }
            .text-gray-600 { color: #94a3b8; }
            .text-gray-800 { color: #e2e8f0; }
            .input-focus {
                background-color: #1e293b;
                border-color: #475569;
                color: #e2e8f0;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="gradient-bg text-white shadow-lg sticky top-0 z-50" role="navigation" aria-label="Main navigation">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold tracking-tight font-inter">QuantumBank</a>
            <div class="hidden md:flex items-center space-x-8">
                <a href="dashboard.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Dashboard">Dashboard</a>
                <div class="relative group">
                    <a href="#" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Accounts">Accounts</a>
                    <div class="absolute hidden group-hover:block bg-white text-gray-800 rounded-lg shadow-lg py-2 mt-2 z-10 min-w-[150px]">
                        <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-100">Checking</a>
                        <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-100">Savings</a>
                        <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-100">Business</a>
                    </div>
                </div>
                <a href="payments.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Payments">Payments</a>
                <a href="cards.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Cards">Cards</a>
                <a href="#" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Investments">Investments</a>
                <a href="Calc.html" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Calculators">Calculators</a>
                <a href="atm_locator.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="ATM Locator">ATM Locator</a>
                <a href="login.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Login">Login</a>
            </div>
            <button class="md:hidden text-white focus:outline-none" aria-label="Toggle Mobile Menu" id="mobileMenuBtn">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
        <div id="mobileMenu" class="hidden md:hidden bg-white text-gray-800 absolute top-0 left-0 w-full h-screen flex flex-col items-center justify-center space-y-6">
            <a href="dashboard.php" class="text-lg font-medium hover:text-primary">Dashboard</a>
            <a href="#" class="text-lg font-medium hover:text-primary">Accounts</a>
            <a href="payments.php" class="text-lg font-medium hover:text-primary">Payments</a>
            <a href="cards.php" class="text-lg font-medium hover:text-primary">Cards</a>
            <a href="#" class="text-lg font-medium hover:text-primary">Investments</a>
            <a href="CalC.html" class="text-lg font-medium hover:text-primary">Calculators</a>
            <a href="atm_locator.php" class="text-lg font-medium hover:text-primary">ATM Locator</a>
            <a href="login.php" class="text-lg font-medium hover:text-primary">Login</a>
            <button id="closeMobileMenu" class="absolute top-4 right-4 text-2xl text-gray-800 focus:outline-none">&times;</button>
        </div>
    </nav>

    <!-- OTP Modal -->
    <div id="otpModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-gray-800">Verify Your Transfer</h2>
                    <p class="text-sm text-gray-600 mt-2">Enter the 6-digit OTP sent to your email</p>
                </div>

                <!-- Transfer Summary -->
                <?php
                $stmt = $conn->prepare("
                    SELECT t.from_account, t.to_account, t.amount, a1.account_number AS from_num, a2.account_number AS to_num
                    FROM transfer_otps t
                    JOIN accounts a1 ON t.from_account = a1.id
                    JOIN accounts a2 ON t.to_account = a2.id
                    WHERE t.user_id = ? AND t.used = 0 AND t.expires_at >= NOW()
                    ORDER BY t.created_at DESC LIMIT 1
                ");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                ?>
                <?php if ($res): ?>
                <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg mb-6">
                    <p class="font-semibold text-sm mb-2">Transfer Summary</p>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div><span class="font-medium">From:</span></div>
                        <div class="font-mono">****<?php echo substr($res['from_num'], -4); ?></div>
                        <div><span class="font-medium">To:</span></div>
                        <div class="font-mono">****<?php echo substr($res['to_num'], -4); ?></div>
                        <div><span class="font-medium">Amount:</span></div>
                        <div class="font-bold">$<?php echo number_format($res['amount'], 2); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Messages -->
                <?php if (isset($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="otpForm" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                    <div>
                        <label for="otp" class="block text-sm font-medium text-gray-700">6-Digit OTP</label>
                        <input
                            type="text"
                            id="otp"
                            name="otp"
                            inputmode="numeric"
                            maxlength="6"
                            placeholder="000000"
                            required
                            class="otp-input mt-1 block w-full border border-gray-300 rounded-lg py-3 px-4 text-center input-focus text-lg tracking-widest"
                            autocomplete="one-time-code"
                        >
                        <p class="text-xs text-gray-500 mt-2 text-center">Enter the code from your email</p>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 btn-primary text-white rounded-lg font-medium flex items-center justify-center space-x-2 focus:ring-2 focus:ring-accent focus:ring-offset-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Verify & Complete Transfer</span>
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-600">
                        Didn't receive the code?
                        <a href="transfer.php" class="text-primary font-medium hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded">Try Again</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content (Hidden when modal is shown) -->
    <main class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 hidden" id="mainContent">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-800">Transfer Verification</h1>
            <p class="text-lg text-gray-600 mt-4">Please complete the OTP verification in the popup above.</p>
        </div>
    </main>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Scripts -->
    <script>
        // Auto-focus OTP input
        document.getElementById('otp')?.focus();

        // Format OTP input (only numbers, max 6)
        document.getElementById('otp')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });

        // Mobile menu
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });
        document.getElementById('closeMobileMenu')?.addEventListener('click', () => {
            document.getElementById('mobileMenu').classList.add('hidden');
        });
    </script>
</body>
</html>