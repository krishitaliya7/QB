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
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            // Generate OTP for login verification
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $settings = include __DIR__ . '/../admin/config/settings.php';
            $expires_at = date('Y-m-d H:i:s', time() + ($settings['login_otp_expiry_seconds'] ?? 300)); // 5 minutes default
            $max_attempts = $settings['login_otp_max_attempts'] ?? 3;

            // Store OTP in database
            $stmt_otp = $conn->prepare("INSERT INTO login_otps (user_id, otp_hash, expires_at, max_attempts) VALUES (?, ?, ?, ?)");
            // Types: i = user_id (int), s = otp_hash (string), s = expires_at (string/datetime), i = max_attempts (int)
            $stmt_otp->bind_param("issi", $user['id'], $otp_hash, $expires_at, $max_attempts);
            $stmt_otp->execute();
            $otp_id = $conn->insert_id;
            $stmt_otp->close();

            // Create internal message to user's inbox with the OTP
            $msg_type = 'login.otp';
            $inbox_message = "Your login verification code is: $otp. It will expire by $expires_at.";
            if ($stmt_msg = $conn->prepare("INSERT INTO messages (user_id, type, message, read_status, created_at) VALUES (?, ?, ?, 0, NOW())")) {
                $stmt_msg->bind_param("iss", $user['id'], $msg_type, $inbox_message);
                $stmt_msg->execute();
                $stmt_msg->close();
            }

            // Set session for OTP verification
            $_SESSION['login_user_id'] = $user['id'];
            $_SESSION['login_email'] = $email;
            $_SESSION['login_otp_id'] = $otp_id;
            $_SESSION['login_otp_plain'] = $otp; // Store plain OTP for on-screen display

            // Send OTP via email
            $subject = "QuantumBank Login Verification Code";
            // Prepare email bodies using template
            $otp_value_for_template = $otp; // keep OTP available under a clear name
            // Ensure template has access to $otp
            $otp = $otp_value_for_template;
            include '../includes/email_templates/login_otp.php';
            // login_otp.php sets $message and $html_message
            send_mail($email, $subject, $message, '', $html_message);

            audit_log($conn, 'login.otp.sent', $user['id'], ['email' => $email]);

            header('Location: login_verify.php');
            exit;
        } else {
            $error = "Invalid email or password.";
            audit_log($conn, 'login.failed', null, ['email' => $email, 'reason' => 'invalid_credentials']);
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
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, verified) VALUES (?, ?, ?, 1)");
                $stmt->bind_param("sss", $username, $email, $password);
                $stmt->execute();
                $newUserId = $conn->insert_id;

                $success = "Registration successful! You can now log in.";

                audit_log($conn, 'user.register', $newUserId, ['email' => $email]);
            } catch (Exception $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $reset_email = filter_input(INPUT_POST, 'reset_email', FILTER_SANITIZE_EMAIL);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $csrf_token = $_POST['csrf_token'];

    if (!verifyCsrfToken($csrf_token)) {
        $reset_error = "Invalid CSRF token.";
    } elseif (strlen($new_password) < 8) {
        $reset_error = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $reset_error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $reset_email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user['id']);
            if ($stmt->execute()) {
                audit_log($conn, 'password.reset.direct', $user['id'], ['email' => $reset_email]);
                $reset_success = "Password has been reset successfully.";
            } else {
                $reset_error = "Failed to reset password. Please try again.";
            }
        } else {
            $reset_error = "No account found with that email address.";
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
                <?php if (isset($reset_error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><?php echo $reset_error; ?></span>
                    </div>
                <?php endif; ?>
                <?php if (isset($reset_success)): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><?php echo $reset_success; ?></span>
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
                        <a href="#" class="text-sm text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded" onclick="showResetForm()">Forgot Password?</a>
                        <a href="pin_reset_request.php" class="text-sm text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded ml-4">Forgot PIN?</a>
                    </div>
                    <div>
                       <button type="submit" name="login" class="w-full py-2.5 px-4 bg-white text-black rounded-lg font-medium focus:ring-2 focus:ring-accent focus:ring-offset-2">Sign In</button>

                    </div>
                </form>

                <!-- Direct Password Reset Form -->
                <div id="resetForm" class="hidden mt-6 space-y-6">
                    <h3 class="text-lg font-semibold text-white-800">Reset Password</h3>
                    <form method="POST" id="passwordResetForm" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <div>
                            <label for="reset_email" class="block text-sm font-medium text-white-700">Email Address</label>
                            <input type="email" id="reset_email" name="reset_email" placeholder="you@example.com" required class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus">
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-white-700">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required minlength="8" class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-white-700">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus">
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" name="reset_password" class="flex-1 py-2.5 px-4 btn-primary text-white rounded-lg font-medium focus:ring-2 focus:ring-accent focus:ring-offset-2">Reset Password</button>
                            <button type="button" onclick="hideResetForm()" class="flex-1 py-2.5 px-4 bg-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-400">Cancel</button>
                        </div>
                    </form>
                </div>

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

        document.getElementById('passwordResetForm')?.addEventListener('submit', function(event) {
            const emailInput = document.getElementById('reset_email');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');

            let isValid = true;

            if (!emailInput.value || !emailInput.value.includes('@')) {
                alert('Please enter a valid email address.');
                isValid = false;
            }

            if (newPassword.value.length < 8) {
                alert('Password must be at least 8 characters long.');
                isValid = false;
            }

            if (newPassword.value !== confirmPassword.value) {
                alert('Passwords do not match.');
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
            }
        });

        function showResetForm() {
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('resetForm').classList.remove('hidden');
        }

        function hideResetForm() {
            document.getElementById('resetForm').classList.add('hidden');
            document.getElementById('loginForm').classList.remove('hidden');
        }

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
