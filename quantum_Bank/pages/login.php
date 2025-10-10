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
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            if (isset($user['verified']) && !$user['verified']) {
                $error = 'Please verify your email before logging in. Check your inbox.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = "Invalid email or password.";
        }
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
        // Validate email uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already registered. Please log in or use a different email.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $password]);
                $newUserId = $pdo->lastInsertId();
                // Create email verification token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours
                $stmt = $pdo->prepare('INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)');
                $stmt->execute([$newUserId, $token, $expires]);
                $link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/verify_email.php?token=$token";
                include '../includes/email_helpers.php';
                $html = render_email_template(__DIR__ . '/../includes/email_templates/verify_email.php', ['username' => $username, 'link' => $link]);
                $msg = "Please verify your email: $link";
                send_mail($email, 'Verify your QuantumBank email', $msg, '', $html);
                $success = "Registration successful! Please check your email to verify your account.";
                // Audit
                audit_log($pdo, 'user.register', $newUserId, ['email' => $email]);
            } catch (PDOException $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="max-w-6xl mx-auto min-h-[72vh] flex items-center px-4">
    <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
        <div class="space-y-6">
            <h1 class="text-4xl font-extrabold text-qbblue">Welcome back</h1>
            <p class="text-gray-600">Sign in to access your QuantumBank dashboard and manage your accounts securely.</p>
            <?php if (isset($error)) echo "<div class='bg-red-50 border border-red-200 text-red-800 p-3 rounded'>$error</div>"; ?>
            <?php if (isset($success)) echo "<div class='bg-green-50 border border-green-200 text-green-800 p-3 rounded'>$success</div>"; ?>
            <div class="hidden md:block">
                <img src="../pages/img1.png" alt="Illustration" class="max-w-sm opacity-90">
            </div>
        </div>

        <div class="bg-white shadow-lg rounded-lg p-8">
            <h2 class="text-2xl font-semibold mb-4">Account Login</h2>
            <form method="POST" id="loginForm" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required class="mt-1 block w-full border-gray-200 rounded-md shadow-sm focus:ring-qbblue focus:border-qbblue">
                </div>
                <div class="relative">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required class="mt-1 block w-full border-gray-200 rounded-md shadow-sm pr-20 focus:ring-qbblue focus:border-qbblue">
                    <button type="button" class="absolute right-2 top-8 text-sm text-gray-500" id="togglePassword">Show</button>
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="checkbox" id="remember" name="remember" class="mr-2"> Remember me
                    </label>
                    <a href="password_reset_request.php" class="text-sm text-qbblue hover:underline">Forgot Password?</a>
                </div>
                <div>
                    <button type="submit" name="login" class="w-full py-2 px-4 bg-qbblue text-white rounded-md hover:bg-[#044e99]">Login</button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">New here? <a href="signup.php" class="text-qbblue font-medium hover:underline">Create an account</a></p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>