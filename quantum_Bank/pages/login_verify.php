<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/audit.php';

$page_css = 'login.css';

// Check if user is in login verification process
if (!isset($_SESSION['login_user_id']) || !isset($_SESSION['login_email']) || !isset($_SESSION['login_otp_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['login_user_id'];
$email = $_SESSION['login_email'];
$otp_id = $_SESSION['login_otp_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'];
    $csrf_token = $_POST['csrf_token'];

    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        // Get OTP from database
        $stmt = $conn->prepare("SELECT otp_hash, expires_at, attempts, max_attempts FROM login_otps WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $otp_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $otp_record = $result->fetch_assoc();

        if ($otp_record) {
            $current_time = date('Y-m-d H:i:s');

            if ($current_time > $otp_record['expires_at']) {
                $error = "OTP has expired. Please try logging in again.";
                // Clean up expired OTP
                $stmt_cleanup = $conn->prepare("DELETE FROM login_otps WHERE id = ?");
                $stmt_cleanup->bind_param("i", $otp_id);
                $stmt_cleanup->execute();
                $stmt_cleanup->close();
                // Clear session
                unset($_SESSION['login_user_id'], $_SESSION['login_email'], $_SESSION['login_otp_id']);
            } elseif ($otp_record['attempts'] >= $otp_record['max_attempts']) {
                $error = "Too many failed attempts. Please try logging in again.";
                // Clean up OTP
                $stmt_cleanup = $conn->prepare("DELETE FROM login_otps WHERE id = ?");
                $stmt_cleanup->bind_param("i", $otp_id);
                $stmt_cleanup->execute();
                $stmt_cleanup->close();
                // Clear session
                unset($_SESSION['login_user_id'], $_SESSION['login_email'], $_SESSION['login_otp_id']);
            } elseif (password_verify($entered_otp, $otp_record['otp_hash'])) {
                // Successful verification
                $stmt_user = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
                $stmt_user->bind_param("i", $user_id);
                $stmt_user->execute();
                $user_result = $stmt_user->get_result();
                $user = $user_result->fetch_assoc();

                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                session_regenerate_id(true);

                // Clean up OTP
                $stmt_cleanup = $conn->prepare("DELETE FROM login_otps WHERE id = ?");
                $stmt_cleanup->bind_param("i", $otp_id);
                $stmt_cleanup->execute();
                $stmt_cleanup->close();

                // Clear temporary session data
                unset($_SESSION['login_user_id'], $_SESSION['login_email'], $_SESSION['login_otp_id']);

                audit_log($conn, 'login.success', $user_id, ['email' => $email]);

                $success = "Login successful! Redirecting to dashboard...";
            } else {
                // Failed attempt
                $new_attempts = $otp_record['attempts'] + 1;
                $stmt_update = $conn->prepare("UPDATE login_otps SET attempts = ? WHERE id = ?");
                $stmt_update->bind_param("ii", $new_attempts, $otp_id);
                $stmt_update->execute();
                $stmt_update->close();

                audit_log($conn, 'login.otp.failed', $user_id, ['email' => $email, 'attempts' => $new_attempts]);

                if ($new_attempts >= $otp_record['max_attempts']) {
                    $error = "Too many failed attempts. Please try logging in again.";
                    // Clean up OTP
                    $stmt_cleanup = $conn->prepare("DELETE FROM login_otps WHERE id = ?");
                    $stmt_cleanup->bind_param("i", $otp_id);
                    $stmt_cleanup->execute();
                    $stmt_cleanup->close();
                    // Clear session
                    unset($_SESSION['login_user_id'], $_SESSION['login_email'], $_SESSION['login_otp_id']);
                } else {
                    $error = "Invalid OTP. " . ($otp_record['max_attempts'] - $new_attempts) . " attempts remaining.";
                }
            }
        } else {
            $error = "OTP verification failed. Please try logging in again.";
            // Clear session
            unset($_SESSION['login_user_id'], $_SESSION['login_email'], $_SESSION['login_otp_id']);
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])) {
    $csrf_token = $_POST['csrf_token'];

    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        // Clean up old OTP
        $stmt_cleanup = $conn->prepare("DELETE FROM login_otps WHERE id = ?");
        $stmt_cleanup->bind_param("i", $otp_id);
        $stmt_cleanup->execute();
        $stmt_cleanup->close();

        // Generate new OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
        $expires_at = date('Y-m-d H:i:s', time() + 300); // 5 minutes expiry

        // Store new OTP
        $stmt_otp = $conn->prepare("INSERT INTO login_otps (user_id, otp_hash, expires_at) VALUES (?, ?, ?)");
        $stmt_otp->bind_param("iss", $user_id, $otp_hash, $expires_at);
        $stmt_otp->execute();
        $new_otp_id = $conn->insert_id;
        $stmt_otp->close();

        // Send new OTP via email
        include '../includes/send_mail.php';
        $subject = "QuantumBank Login Verification Code (Resent)";
        $message = "Your new login verification code is: $otp\n\nThis code will expire in 5 minutes.";
        $html_message = "<p>Your new login verification code is: <strong>$otp</strong></p><p>This code will expire in 5 minutes.</p>";
        send_mail($email, $subject, $message, '', $html_message);

        // Update session
        $_SESSION['login_otp_id'] = $new_otp_id;

        audit_log($conn, 'login.otp.resent', $user_id, ['email' => $email]);

        $success = "A new verification code has been sent to your email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuantumBank Login Verification</title>
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
                <h1 class="text-4xl sm:text-5xl font-bold text-gray-800">Verify Your Login</h1>
                <p class="text-lg text-gray-600 leading-relaxed">We've sent a 6-digit verification code to <strong><?php echo htmlspecialchars($email); ?></strong>. Please enter it below to complete your login.</p>
                <?php if (isset($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div id="successPopup" class="fixed top-4 right-4 bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg flex items-center shadow-lg z-50 max-w-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><?php echo $success; ?></span>
                        <button id="closePopup" class="ml-4 text-green-600 hover:text-green-800 focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
                <div class="hidden md:block">
                    <p class="text-sm text-gray-500">Didn't receive the code? Check your spam folder or <button type="submit" form="resendForm" class="text-primary hover:underline">resend the code</button>.</p>
                </div>
            </div>

            <div class="card p-8">
                <h2 class="text-2xl font-semibold text-white-800 mb-6">Enter Verification Code</h2>
                <form method="POST" id="otpForm" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div>
                        <label for="otp" class="block text-sm font-medium text-white-700">6-Digit Code</label>
                        <input type="text" id="otp" name="otp" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm text-center text-2xl tracking-widest input-focus" aria-describedby="otpError">
                        <p class="error-message" id="otpError">Please enter a valid 6-digit code.</p>
                    </div>
                    <div>
                        <button type="submit" name="verify_otp" class="w-full py-2.5 px-4 bg-black text-white rounded-lg font-medium focus:ring-2 focus:ring-accent focus:ring-offset-2">Verify & Sign In</button>
                    </div>
                </form>

                <form method="POST" id="resendForm" class="hidden">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="resend_otp">
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-white-600">Wrong email? <a href="login.php" class="text-primary font-medium hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded">Go back to login</a></p>
                    <p class="text-sm text-white-600 mt-2">Didn't receive the code? <button type="submit" form="resendForm" class="text-primary hover:underline">Resend code</button></p>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        document.getElementById('otpForm').addEventListener('submit', function(event) {
            const otpInput = document.getElementById('otp');
            const otpError = document.getElementById('otpError');

            let isValid = true;

            if (!otpInput.value || !/^\d{6}$/.test(otpInput.value)) {
                otpError.style.display = 'block';
                isValid = false;
            } else {
                otpError.style.display = 'none';
            }

            if (!isValid) {
                event.preventDefault();
            }
        });

        // Auto-focus and auto-submit on 6 digits
        document.getElementById('otp').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, ''); // Only allow digits
            if (this.value.length === 6) {
                // Optional: auto-submit after 6 digits
                // document.getElementById('otpForm').submit();
            }
        });

        document.getElementById('mobileMenuBtn').addEventListener('click', () => {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        document.getElementById('closeMobileMenu').addEventListener('click', () => {
            document.getElementById('mobileMenu').classList.add('hidden');
        });

        document.getElementById('closePopup')?.addEventListener('click', function() {
            document.getElementById('successPopup').style.display = 'none';
        });

        // Auto-hide success popup after 10 seconds
        setTimeout(() => {
            const popup = document.getElementById('successPopup');
            if (popup) popup.style.display = 'none';
        }, 10000);

        // Redirect to dashboard after successful login
        if (document.getElementById('successPopup')) {
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 2000); // Redirect after 2 seconds to show the success message briefly
        }
    </script>
</body>
</html>
