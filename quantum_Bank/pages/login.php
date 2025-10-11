<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/send_mail.php';
include '../includes/audit.php';

$page_css = 'login.css';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $csrf_token = $_POST['csrf_token'];

    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            // Temporarily skip email verification for testing
            // if (isset($user['verified']) && !$user['verified']) {
            //     $error = 'Please verify your email before logging in. Check your inbox.';
            // } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                session_regenerate_id(true); // Regenerate session ID for security
                header('Location: dashboard.php');
                exit;
            // }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $csrf_token = $_POST['csrf_token'];

    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = "Email already registered. Please log in or use a different email.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $email, $password);
                $stmt->execute();
                $newUserId = $conn->insert_id;

                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 86400);
                $stmt = $conn->prepare('INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)');
                $stmt->bind_param("iss", $newUserId, $token, $expires);
                $stmt->execute();
                $link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/verify_email.php?token=$token";
                include '../includes/email_helpers.php';
                $html = render_email_template(__DIR__ . '/../includes/email_templates/verify_email.php', ['username' => $username, 'link' => $link]);
                $msg = "Please verify your email: $link";
                send_mail($email, 'Verify your QuantumBank email', $msg, '', $html);
                $success = "Registration successful! Please check your email to verify your account.";

                audit_log($conn, 'user.register', $newUserId, ['email' => $email]);
            } catch (Exception $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuantumBank Login</title>
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
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
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

    <main class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div class="space-y-8">
                <h1 class="text-4xl sm:text-5xl font-bold text-gray-800">Welcome to QuantumBank</h1>
                <p class="text-lg text-gray-600 leading-relaxed">Access your dashboard to manage your accounts, make payments, and explore investment options securely.</p>
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
                <div class="hidden md:block">
                   
                </div>
            </div>

            <div class="card p-8">
                <h2 class="text-2xl font-semibold text-white-800 mb-6">Sign In</h2>
                <form method="POST" id="loginForm" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div>
                        <label for="email" class="block text-sm font-medium text-white-700">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="you@example.com" required class="mt-1 block w-full border border-white-300 rounded-lg py-2.5 px-4 text-sm input-focus" aria-describedby="emailError">
                        <p class="error-message" id="emailError">Please enter a valid email address.</p>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-white-700">Password</label>
                        <div class="relative">
                            <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus pr-16" aria-describedby="passwordError">
                            <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-sm text-gray-500 hover:text-primary" id="togglePassword">Show</button>
                        </div>
                        <p class="error-message" id="passwordError">Password cannot be empty.</p>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="checkbox" id="remember" name="remember" class="h-4 w-4 rounded border-white-300 text-primary focus:ring-primary"> 
                            <span class="ml-2 text-white">Remember me</span>
                        </label>
                        <a href="password_reset_request.php" class="text-sm text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded">Forgot Password?</a>
                    </div>
                    <div>
                       <button type="submit" name="login" class="w-full py-2.5 px-4 bg-white text-black rounded-lg font-medium focus:ring-2 focus:ring-accent focus:ring-offset-2">Sign In</button>

                    </div>
                </form>
                <div class="mt-6 text-center">
                    <p class="text-sm text-white-600">Don't have an account? <a href="open_account.php" class="text-primary font-medium hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded">Create one now</a></p>

                </div>

            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('loginPassword');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');

            let isValid = true;

            if (!emailInput.value || !emailInput.value.includes('@')) {
                emailError.style.display = 'block';
                isValid = false;
            } else {
                emailError.style.display = 'none';
            }

            if (!passwordInput.value) {
                passwordError.style.display = 'block';
                isValid = false;
            } else {
                passwordError.style.display = 'none';
            }

            if (!isValid) {
                event.preventDefault();
            }
        });

        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('loginPassword');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? 'Show' : 'Hide';
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