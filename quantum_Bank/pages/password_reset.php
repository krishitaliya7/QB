<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/audit.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid CSRF token.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Verify token and OTP
        $stmt = $conn->prepare('SELECT user_id, otp, expires_at FROM password_resets WHERE token = ?');
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $reset = $result->fetch_assoc();

        if ($reset && strtotime($reset['expires_at']) > time() && $reset['otp'] == $otp) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_id = $reset['user_id'];

            // Update password
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->bind_param("si", $hashed_password, $user_id);
            if ($stmt->execute()) {
                // Delete the token
                $stmt = $conn->prepare('DELETE FROM password_resets WHERE token = ?');
                $stmt->bind_param("s", $token);
                $stmt->execute();

                audit_log($conn, 'password.reset.success', $user_id, ['token' => $token]);
                $success = 'Your password has been reset successfully. You can now log in.';
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
        } else {
            $error = 'Invalid or expired reset OTP.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - QuantumBank</title>
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
                <a href="dashboard.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Dashboard">Dashboard</a>
                <a href="payments.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Payments">Payments</a>
                <a href="cards.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Cards">Cards</a>
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
            <a href="payments.php" class="text-lg font-medium hover:text-primary">Payments</a>
            <a href="cards.php" class="text-lg font-medium hover:text-primary">Cards</a>
            <a href="login.php" class="text-lg font-medium hover:text-primary">Login</a>
            <button id="closeMobileMenu" class="absolute top-4 right-4 text-2xl text-gray-800 focus:outline-none">&times;</button>
        </div>
        <!-- Messages Box -->
        <div class="absolute top-16 right-4 bg-white text-gray-800 rounded-lg shadow-lg p-4 max-w-sm z-50 hidden" id="messagesBox">
            <h3 class="font-semibold mb-2">Messages</h3>
            <div id="messagesContent">
                <!-- Messages will be loaded here -->
            </div>
            <button class="mt-2 text-sm text-primary hover:underline" onclick="closeMessages()">Close</button>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 relative">
        <div class="max-w-md mx-auto">
            <svg class="lock-icon hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 11c-1.104 0-2-.896-2-2s.896-2 2-2 2 .896 2 2-.896 2-2 2zm0 2c1.104 0 2 .896 2 2v3H10v-3c0-1.104.896-2 2-2zm0-10c-3.309 0-6 2.691-6 6v3c0 1.104-.896 2-2 2H4v6h16v-6h-2c-1.104 0-2-.896-2-2V9c0-3.309-2.691-6-6-6z"></path>
            </svg>
            <div class="card p-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Reset Your Password</h2>
                <p class="text-gray-600 mb-6">Enter the OTP from your messages (valid for 2 minutes) and your new password.</p>
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg flex items-center mb-4">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg flex items-center mb-4">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><?php echo $success; ?> <a href="login.php" class="text-primary font-medium hover:underline">Log in now</a>.</span>
                    </div>
                <?php endif; ?>
                <?php if (!$success): ?>
                    <form method="POST" id="resetForm" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <div>
                            <label for="otp" class="block text-sm font-medium text-gray-700">OTP from Messages</label>
                            <input type="text" id="otp" name="otp" placeholder="Enter 6-digit OTP" required maxlength="6" class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus">
                            <p class="error-message" id="otpError">Please enter the 6-digit OTP.</p>
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" id="password" name="password" required minlength="8" class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus">
                            <p class="error-message" id="passwordError">Password must be at least 8 characters.</p>
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus">
                            <p class="error-message" id="confirmError">Passwords do not match.</p>
                        </div>
                        <div>
                            <button type="submit" class="w-full py-2.5 px-4 btn-primary text-white rounded-lg font-medium focus:ring-2 focus:ring-accent focus:ring-offset-2">Reset Password</button>
                        </div>
                    </form>
                <?php endif; ?>
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">Back to <a href="login.php" class="text-primary font-medium hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded">Login</a></p>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        document.getElementById('resetForm')?.addEventListener('submit', function(event) {
            const otp = document.getElementById('otp').value;
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const otpError = document.getElementById('otpError');
            const passwordError = document.getElementById('passwordError');
            const confirmError = document.getElementById('confirmError');

            if (otp.length !== 6 || !/^\d{6}$/.test(otp)) {
                otpError.style.display = 'block';
                event.preventDefault();
            } else {
                otpError.style.display = 'none';
            }

            if (password.length < 8) {
                passwordError.style.display = 'block';
                event.preventDefault();
            } else {
                passwordError.style.display = 'none';
            }

            if (password !== confirm) {
                confirmError.style.display = 'block';
                event.preventDefault();
            } else {
                confirmError.style.display = 'none';
            }
        });

        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        document.getElementById('closeMobileMenu')?.addEventListener('click', () => {
            document.getElementById('mobileMenu').classList.add('hidden');
        });

        // Load messages for OTP display
        function loadMessages() {
            fetch('messages.php?ajax=1')
                .then(response => response.json())
                .then(data => {
                    const messagesContent = document.getElementById('messagesContent');
                    messagesContent.innerHTML = '';
                    data.messages.forEach(message => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = 'mb-2 p-2 bg-gray-100 rounded';
                        messageDiv.innerHTML = `<p class="text-sm">${message.message}</p><small class="text-gray-500">${message.created_at}</small>`;
                        messagesContent.appendChild(messageDiv);
                    });
                })
                .catch(error => console.error('Error loading messages:', error));
        }

        function showMessages() {
            loadMessages();
            document.getElementById('messagesBox').classList.remove('hidden');
        }

        function closeMessages() {
            document.getElementById('messagesBox').classList.add('hidden');
        }

        // Add a button to show messages
        document.addEventListener('DOMContentLoaded', function() {
            const nav = document.querySelector('nav');
            const messagesBtn = document.createElement('button');
            messagesBtn.textContent = 'Messages';
            messagesBtn.className = 'text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded';
            messagesBtn.onclick = showMessages;
            nav.querySelector('.container').appendChild(messagesBtn);
        });
    </script>
</body>
</html>
