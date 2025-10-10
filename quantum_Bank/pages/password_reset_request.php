<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/send_mail.php';
include '../includes/audit.php';

$page_css = 'login.css';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid CSRF token.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], $token, $expires]);
            $link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/password_reset.php?token=$token";
            include '../includes/email_helpers.php';
            $html = render_email_template(__DIR__ . '/../includes/email_templates/password_reset.php', ['username' => $user['username'], 'link' => $link]);
            $msg = "Password reset link: $link";
            send_mail($email, 'Password reset', $msg, '', $html);
            audit_log($pdo, 'password.reset.request', $user['id'], ['token_id' => $pdo->lastInsertId()]);
        }
        // Always show success message to avoid leaking which emails exist
        $success = 'If that email exists in our system, a password reset link has been sent.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuantumBank Password Reset</title>
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
        }

        .error-message {
            color: var(--error);
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }

        .success-message {
            color: var(--success);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .btn-primary {
            background-color: var(--primary);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .lock-icon {
            opacity: 0.1;
            position: absolute;
            top: 10%;
            right: 10%;
            width: 200px;
            height: 200px;
            z-index: -1;
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
            .text-gray-600 {
                color: #94a3b8;
            }
            .text-gray-800 {
                color: #e2e8f0;
            }
            .lock-icon {
                opacity: 0.2;
            }
        }

        @media (max-width: 768px) {
            .lock-icon {
                display: none;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <nav class="gradient-bg text-white shadow-lg sticky top-0 z-50" role="navigation" aria-label="Main navigation">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold tracking-tight font-inter">QuantumBank</a>
            <div class="hidden md:flex items-center space-x-8">
                <a href="Dashboard.html" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Dashboard">Dashboard</a>
                <div class="relative group">
                    <a href="#" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Accounts">Accounts</a>
                    <div class="absolute hidden group-hover:block bg-white text-gray-800 rounded-lg shadow-lg py-2 mt-2 z-10 min-w-[150px]">
                        <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-100">Checking</a>
                        <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-100">Savings</a>
                        <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-100">Business</a>
                    </div>
                </div>
                <a href="Payments.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Payments">Payments</a>
                <a href="Cards.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Cards">Cards</a>
                <a href="#" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Investments">Investments</a>
                <a href="#" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Calculators">Calculators</a>
                <a href="atmLocator.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="ATM Locator">ATM Locator</a>
                <a href="login.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Login">Login</a>
            </div>
            <button class="md:hidden text-white focus:outline-none" aria-label="Toggle Mobile Menu" id="mobileMenuBtn">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
        <div id="mobileMenu" class="hidden md:hidden bg-white text-gray-800 absolute top-0 left-0 w-full h-screen flex flex-col items-center justify-center space-y-6">
            <a href="Dashboard.html" class="text-lg font-medium hover:text-primary">Dashboard</a>
            <a href="#" class="text-lg font-medium hover:text-primary">Accounts</a>
            <a href="Payments.php" class="text-lg font-medium hover:text-primary">Payments</a>
            <a href="Cards.php" class="text-lg font-medium hover:text-primary">Cards</a>
            <a href="#" class="text-lg font-medium hover:text-primary">Investments</a>
            <a href="#" class="text-lg font-medium hover:text-primary">Calculators</a>
            <a href="atmLocator.php" class="text-lg font-medium hover:text-primary">ATM Locator</a>
            <a href="login.php" class="text-lg font-medium hover:text-primary">Login</a>
            <button id="closeMobileMenu" class="absolute top-4 right-4 text-2xl text-gray-800 focus:outline-none">&times;</button>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 relative">
        <div class="max-w-md mx-auto">
            <svg class="lock-icon hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 11c-1.104 0-2-.896-2-2s.896-2 2-2 2 .896 2 2-.896 2-2 2zm0 2c1.104 0 2 .896 2 2v3H10v-3c0-1.104.896-2 2-2zm0-10c-3.309 0-6 2.691-6 6v3c0 1.104-.896 2-2 2H4v6h16v-6h-2c-1.104 0-2-.896-2-2V9c0-3.309-2.691-6-6-6z"></path>
            </svg>
            <div class="card p-8">
                <h2 class="text-2xl font-semibold text-white-800 mb-6">Reset Your Password</h2>
                <p class="text-white-600 mb-6">Enter your email address to receive a password reset link.</p>
                <?php if (isset($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>
                <form method="POST" id="resetForm" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="you@example.com" required class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus" aria-describedby="emailError">
                        <p class="error-message" id="emailError">Please enter a valid email address.</p>
                    </div>
                    <div>
                        <button type="submit" class="w-full py-2.5 px-4 btn-primary text-white rounded-lg font-medium focus:ring-2 focus:ring-accent focus:ring-offset-2">Send Reset Link</button>
                    </div>
                </form>
                <div class="mt-6 text-center">
                    <p class="text-sm text-white-600">Back to <a href="login.php" class="text-primary font-medium hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded">Login</a></p>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        document.getElementById('resetForm').addEventListener('submit', function(event) {
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('emailError');

            if (!emailInput.value || !emailInput.value.includes('@')) {
                emailError.style.display = 'block';
                event.preventDefault();
            } else {
                emailError.style.display = 'none';
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