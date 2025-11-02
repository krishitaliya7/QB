<?php
// Include session and database connection
include '../includes/session.php';
include '../includes/db_connect.php';
include '../includes/audit.php';
include '../includes/send_mail.php';
requireLogin();

// Function to mask account number
function maskAccount($account) {
    if (strlen($account) <= 4) return $account;
    return str_repeat('x', strlen($account) - 4) . substr($account, -4);
}

// Set page CSS (optional, overridden by Tailwind)
$page_css = 'index.css';
$user_id = getUserId();

// Initialize variables for error and success messages
$error = '';
$success = '';
$errors = [];


// Fetch user's accounts for the 'from' dropdown

$stmt = $conn->prepare("SELECT id, account_type, balance, account_number FROM accounts WHERE user_id = ?");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from = (int)filter_input(INPUT_POST, 'from_account_id', FILTER_SANITIZE_NUMBER_INT);
    $to_account_number = filter_input(INPUT_POST, 'to_account', FILTER_SANITIZE_NUMBER_INT);
    $amount = (float)filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

    $csrf = $_POST['csrf_token'] ?? '';
    $pin = filter_input(INPUT_POST, 'pin', FILTER_SANITIZE_STRING);


    // Server-side validation
    if (!verifyCsrfToken($csrf)) {
        $errors['csrf'] = 'Invalid CSRF token.';
    }
    if ($amount <= 0) {
        $errors['amount'] = 'Amount must be positive.';
    }

    if (empty($to_account_number)) {
        $errors['to_account'] = 'Recipient account number is required.';
    }

    // Check if from account belongs to user
    $userAccountIds = array_column($accounts, 'id');
    if (!in_array($from, $userAccountIds, true)) {
        $errors['from_account'] = 'Invalid source account.';
    } else {
        // Find recipient account by account number
        $stmt = $conn->prepare("SELECT id, user_id FROM accounts WHERE account_number = ? LIMIT 1");
        $stmt->bind_param("s", $to_account_number);
        $stmt->execute();
        $to_result = $stmt->get_result()->fetch_assoc();
        if (!$to_result) {
            $errors['to_account'] = 'Recipient account not found.';
        } else {
            $to = (int)$to_result['id'];
            $recipient_user_id = (int)$to_result['user_id'];
            if ($to == $from) {
                $errors['to_account'] = 'Cannot transfer to the same account.';
            } else {
                // Check balance
                $stmt = $conn->prepare("SELECT balance FROM accounts WHERE id = ?");
                $stmt->bind_param("i", $from);
                $stmt->execute();
                $balance = (float)$stmt->get_result()->fetch_assoc()['balance'];
                if ($balance < $amount) {
                    $errors['amount'] = 'Insufficient funds.';
                } else {
                    // Use PIN for all transfers
                    if (empty($pin)) {
                        $errors['pin'] = 'PIN is required.';
                    } elseif (!preg_match('/^\d{4}$/', $pin)) {
                        $errors['pin'] = 'PIN must be exactly 4 digits.';
                    } else {
                        // Verify PIN against user's PIN
                        $stmt = $conn->prepare("SELECT pin FROM users WHERE id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $user_pin_hash = $stmt->get_result()->fetch_assoc()['pin'];
                        if (!password_verify($pin, $user_pin_hash)) {
                            $errors['pin'] = 'Invalid PIN.';
                        }
                    }

                    if (empty($errors)) {
                        // Perform transfer directly
                        try {
                            $conn->begin_transaction();

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

                            // Update balances
                            $stmt = $conn->prepare('UPDATE accounts SET balance = balance - ? WHERE id = ?');
                            $stmt->bind_param("di", $amount, $from);
                            $stmt->execute();
                            $stmt = $conn->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ?');
                            $stmt->bind_param("di", $amount, $to);
                            $stmt->execute();

                            $recipientUserId = $owners[$to];

                            // Fetch usernames for descriptions
                            $stmt = $conn->prepare('SELECT username FROM users WHERE id = ?');
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $sender_username = $stmt->get_result()->fetch_assoc()['username'];
                            $stmt->bind_param("i", $recipientUserId);
                            $stmt->execute();
                            $recipient_username = $stmt->get_result()->fetch_assoc()['username'];

                            // Record transactions
                            $stmt = $conn->prepare('INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, ?)');
                            $desc1 = 'Transfer to ' . $recipient_username;
                            $neg_amount = -$amount;
                            $status1 = 'Completed';
                            $stmt->bind_param("iisds", $user_id, $from, $desc1, $neg_amount, $status1);
                            $stmt->execute();
                            $desc2 = 'Transfer from ' . $sender_username;
                            $pos_amount = $amount;
                            $status2 = 'Completed';
                            $stmt->bind_param("iisds", $recipientUserId, $to, $desc2, $pos_amount, $status2);
                            $stmt->execute();

                            $conn->commit();
                            audit_log($conn, 'transfer.completed', $user_id, ['from' => $from, 'to' => $to, 'amount' => $amount]);
                            $success = 'Transfer completed successfully.';

                            // Add message to inbox
                            add_message($user_id, 'confirmation', "Your transfer of $" . number_format($amount, 2) . " from account $from to account $to has been completed successfully.");

                            // Notify recipient
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
                            $errors['general'] = 'Failed to complete transfer: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money Transfer - QuantumBank</title>
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--primary) 0%, #60a5fa 100%);
        }

        .card {
            background: white;
            border-radius: 1rem;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .input-focus {
            transition: all 0.3s ease;
            color: black;
        }

        .input-focus:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .error-message {
            color: var(--error);
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }

        .btn-primary {
            background-color: var(--primary);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1;
        }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--text);
            transition: background-color 0.3s ease;
        }

        .step.active .step-number {
            background-color: var(--primary);
            color: white;
        }

        .step-connector {
            position: absolute;
            top: 16px;
            left: 25%;
            width: 50%;
            height: 2px;
            background-color: #e5e7eb;
            transition: background-color 0.3s ease;
        }

        .step-connector.active {
            background-color: var(--primary);
        }


        @media (max-width: 768px) {
            .card {
                width: 100%;
            }
            .step-indicator {
                flex-direction: column;
            }
            .step-connector {
                display: none;
            }
        }

        /* Success animations */
        .animate-check {
            animation: checkmark 0.6s ease-in-out;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.7;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .confetti {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }

        .confetti-piece {
            position: absolute;
            width: 10px;
            height: 10px;
            background: currentColor;
            top: -10px;
            animation: confetti-fall 3s linear infinite;
            border-radius: 50%;
        }

        @keyframes confetti-fall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
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
                <a href="#" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Calculators">Calculators</a>
                <a href="atmLocator.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="ATM Locator">ATM Locator</a>
                <a href="logout.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Logout">Logout</a>
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
            <a href="#" class="text-lg font-medium hover:text-primary">Calculators</a>
            <a href="atmLocator.php" class="text-lg font-medium hover:text-primary">ATM Locator</a>
            <a href="logout.php" class="text-lg font-medium hover:text-primary">Logout</a>
            <button id="closeMobileMenu" class="absolute top-4 right-4 text-2xl text-gray-800 focus:outline-none">&times;</button>
        </div>
    </nav>

    <main class="px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto">
            <div class="card p-8">
                <h2 class="text-2xl font-semibold text-white-800 mb-6">Money Transfer</h2>
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg flex items-center mb-6 relative overflow-hidden">
                        <svg class="w-5 h-5 mr-2 animate-check" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><?php echo $success; ?></span>
                        <div class="confetti">
                            <div class="confetti-piece" style="left: 10%; animation-delay: 0s; background: #ff6b6b;"></div>
                            <div class="confetti-piece" style="left: 20%; animation-delay: 0.5s; background: #4ecdc4;"></div>
                            <div class="confetti-piece" style="left: 30%; animation-delay: 1s; background: #45b7d1;"></div>
                            <div class="confetti-piece" style="left: 40%; animation-delay: 1.5s; background: #f9ca24;"></div>
                            <div class="confetti-piece" style="left: 50%; animation-delay: 2s; background: #f0932b;"></div>
                            <div class="confetti-piece" style="left: 60%; animation-delay: 2.5s; background: #eb4d4b;"></div>
                            <div class="confetti-piece" style="left: 70%; animation-delay: 3s; background: #6c5ce7;"></div>
                            <div class="confetti-piece" style="left: 80%; animation-delay: 3.5s; background: #a29bfe;"></div>
                            <div class="confetti-piece" style="left: 90%; animation-delay: 4s; background: #fd79a8;"></div>
                        </div>
                    </div>

                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn-primary w-full py-2.5 px-4 text-white rounded-lg font-medium text-center block focus:ring-2 focus:ring-accent focus:ring-offset-2">
                        Make Another Transfer
                    </a>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg flex items-center mb-6">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <ul class="list-disc list-inside">
                                <?php foreach ($errors as $key => $msg): ?>
                                    <li><?php echo htmlspecialchars($msg); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form id="transferForm" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="transfer">
                            <div>
                                <label for="from_account" class="block text-sm font-medium text-gray-700">From Account</label>
                                <select id="from_account" name="from_account_id" required class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus">
                                    <?php if (!empty($accounts)): ?>
                                        <?php foreach ($accounts as $account): ?>
                                            <option value="<?php echo $account['id']; ?>" data-balance="<?php echo $account['balance']; ?>" <?php echo $account === $accounts[0] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($account['account_number'] . ' (' . $account['account_type'] . ') - Balance: $' . number_format($account['balance'], 2)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">No accounts available</option>
                                    <?php endif; ?>
                                </select>
                                <p class="error-message" id="from_account_error"></p>
                            </div>
                            <div>
                                <label for="to_account" class="block text-sm font-medium text-gray-700">To Account Number</label>
                                <input type="text" id="to_account" name="to_account" placeholder="Enter account number" required class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus">
                                <p class="error-message" id="to_account_error"></p>
                            </div>
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                                <input type="number" id="amount" name="amount" min="0.01" step="0.01" placeholder="0.00" required class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus">
                                <p class="error-message" id="amount_error"></p>
                            </div>
                            <div>
                                <label for="pin" class="block text-sm font-medium text-gray-700">PIN</label>
                                <input type="password" id="pin" name="pin" placeholder="Enter 4-digit PIN" maxlength="4" required class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus">
                                <p class="error-message" id="pin_error"></p>
                            </div>
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                                <input type="text" id="description" name="description" placeholder="Enter description" class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus">
                            </div>
                            <button type="submit" class="btn-primary w-full py-2.5 px-4 text-white rounded-lg font-medium focus:ring-2 focus:ring-accent focus:ring-offset-2">
                                Transfer Funds
                            </button>
                        </form>
                    </div>

                    <div id="alertContainer" class="mt-4"></div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Real-time validation on blur
        document.getElementById('to_account').addEventListener('blur', () => {
            const field = document.getElementById('to_account');
            const error = document.getElementById('to_account_error');
            if (!field.value.trim()) {
                field.classList.add('border-red-500');
                if (error) error.style.display = 'block';
            } else {
                field.classList.remove('border-red-500');
                if (error) error.style.display = 'none';
            }
        });

        document.getElementById('amount').addEventListener('blur', () => {
            const amount = parseFloat(document.getElementById('amount').value);
            const selectedOption = document.getElementById('from_account').selectedOptions[0];
            const balance = parseFloat(selectedOption.dataset.balance);
            const field = document.getElementById('amount');
            const error = document.getElementById('amount_error');
            if (isNaN(amount) || amount <= 0 || amount > balance) {
                field.classList.add('border-red-500');
                if (error) error.style.display = 'block';
            } else {
                field.classList.remove('border-red-500');
                if (error) error.style.display = 'none';
            }
        });

        document.getElementById('mobileMenuBtn').addEventListener('click', () => {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        document.getElementById('closeMobileMenu').addEventListener('click', () => {
            document.getElementById('mobileMenu').classList.add('hidden');
        });

    </script>
</body>
</html>
