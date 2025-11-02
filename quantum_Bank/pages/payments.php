<?php
include '../includes/db_connect.php';
include '../includes/session.php';
requireLogin();

$page_css = 'payments.css';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardholder_name = filter_input(INPUT_POST, 'cardholder_name', FILTER_SANITIZE_STRING);
    $card_number = filter_input(INPUT_POST, 'card_number', FILTER_SANITIZE_STRING);
    $expiry_date = filter_input(INPUT_POST, 'expiry_date', FILTER_SANITIZE_STRING);
    $cvv = filter_input(INPUT_POST, 'cvv', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $csrf_token = $_POST['csrf_token'];
    $user_id = $_SESSION['user_id'];

    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } elseif ($amount <= 0) {
        $error = "Invalid amount.";
    } else {
        try {
            // Simulate card validation
            if (strlen(str_replace([' ', '-'], '', $card_number)) === 16 && preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry_date) && strlen($cvv) >= 3) {
                $stmt = $conn->prepare("SELECT id FROM accounts WHERE user_id = ? LIMIT 1");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $account = $result->fetch_assoc();
                if ($account) {
                    $stmt = $conn->prepare("INSERT INTO transactions (user_id, account_id, description, amount, status) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisds", $user_id, $account['id'], "Payment to $cardholder_name", $amount, 'Completed');
                    $stmt->execute();
                    $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
                    $stmt->bind_param("di", $amount, $account['id']);
                    $stmt->execute();
                    $success = "Payment of $$amount processed successfully!";
                } else {
                    $error = "No account found.";
                }
            } else {
                $error = "Invalid card details.";
            }
        } catch (Exception $e) {
            $error = "Payment failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuantumBank Payment Gateway</title>
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

        .modal {
            display: none;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal.show {
            display: flex;
        }

        .credit-card-icon {
            width: 40px;
            height: 28px;
            background: linear-gradient(45deg, var(--primary), #60a5fa);
            border-radius: 6px;
            position: relative;
        }

        .credit-card-icon::before, .credit-card-icon::after {
            content: "";
            position: absolute;
            border-radius: 50%;
        }

        .credit-card-icon::before {
            width: 20px;
            height: 20px;
            background: var(--accent);
            left: 8px;
            top: 4px;
        }

        .credit-card-icon::after {
            width: 20px;
            height: 20px;
            background: #93c5fd;
            left: 20px;
            top: 8px;
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

        @media (max-width: 768px) {
            .card {
                width: 100%;
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
                <a href="atm_locator.php" class="text-sm font-medium hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="ATM Locator">ATM Locator</a>
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
            <a href="atm_locator.php" class="text-lg font-medium hover:text-primary">ATM Locator</a>
            <a href="logout.php" class="text-lg font-medium hover:text-primary">Logout</a>
            <button id="closeMobileMenu" class="absolute top-4 right-4 text-2xl text-gray-800 focus:outline-none">&times;</button>
        </div>
    </nav>

    <main class="px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto relative">
            <div class="card p-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Secure Payment Gateway</h2>
                <p class="text-gray-600 mb-6">Make your payment with confidence — protected by military-grade encryption.</p>
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
                <form id="paymentForm" method="POST" class="space-y-6" autocomplete="on" aria-describedby="form-instructions">
                    <p id="form-instructions" class="sr-only">
                        Payment form. Required fields: Cardholder Name, Card Number, Expiry Date, CVV, Amount. Submit button disabled until valid.
                    </p>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div>
                        <label for="cardholderName" class="block text-sm font-medium text-gray-700">Cardholder Name</label>
                        <input
                            type="text"
                            id="cardholderName"
                            name="cardholder_name"
                            class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus"
                            maxlength="50"
                            autocomplete="cc-name"
                            placeholder="Full Name as on Card"
                            required
                            pattern="^[a-zA-Z\s\-']+$"
                            title="Enter the full name as printed on your card"
                            aria-describedby="errorName"
                        >
                        <p class="error-message" id="errorName">Please enter a valid name (letters, spaces, hyphens only).</p>
                    </div>
                    <div>
                        <label for="cardNumber" class="block text-sm font-medium text-gray-700">Card Number</label>
                        <div class="flex items-center space-x-3">
                            <input
                                type="text"
                                id="cardNumber"
                                name="card_number"
                                inputmode="numeric"
                                class="mt-1 block flex-grow border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus tracking-widest"
                                maxlength="19"
                                autocomplete="cc-number"
                                placeholder="xxxx xxxx xxxx xxxx"
                                required
                                pattern="^(\d{4}[\s-]?){3}\d{4}$"
                                aria-describedby="errorNumber"
                            >
                            <div class="credit-card-icon" aria-hidden="true"></div>
                        </div>
                        <p class="error-message" id="errorNumber">Enter a valid 16-digit card number.</p>
                    </div>
                    <div class="flex space-x-4">
                        <div class="flex-1">
                            <label for="expiryDate" class="block text-sm font-medium text-gray-700">Expiry Date (MM/YY)</label>
                            <input
                                type="text"
                                id="expiryDate"
                                name="expiry_date"
                                inputmode="numeric"
                                class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus text-center"
                                placeholder="MM/YY"
                                maxlength="5"
                                autocomplete="cc-exp"
                                required
                                pattern="^(0[1-9]|1[0-2])\/?([0-9]{2})$"
                                title="Expiry date in MM/YY format"
                                aria-describedby="errorExpiry"
                            >
                            <p class="error-message" id="errorExpiry">Enter a valid expiry date (MM/YY) not in the past.</p>
                        </div>
                        <div class="flex-1">
                            <label for="cvv" class="block text-sm font-medium text-gray-700">CVV</label>
                            <input
                                type="password"
                                id="cvv"
                                name="cvv"
                                inputmode="numeric"
                                class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus text-center tracking-widest"
                                maxlength="4"
                                autocomplete="cc-csc"
                                placeholder="***"
                                required
                                pattern="^\d{3,4}$"
                                title="3 or 4 digit security code"
                                aria-describedby="errorCVV"
                            >
                            <p class="error-message" id="errorCVV">Enter a valid 3 or 4 digit CVV.</p>
                        </div>
                    </div>
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount (USD)</label>
                        <input
                            type="number"
                            id="amount"
                            name="amount"
                            min="1"
                            step="0.01"
                            class="mt-1 block w-full border border-gray-300 rounded-lg py-2.5 px-4 text-sm input-focus text-right"
                            placeholder="0.00"
                            required
                            title="Enter amount to pay in USD"
                            aria-describedby="errorAmount"
                        >
                        <p class="error-message" id="errorAmount">Please enter a valid amount greater than 0.</p>
                    </div>
                    <button
                        type="submit"
                        id="submitBtn"
                        class="btn-primary w-full py-2.5 px-4 text-white rounded-lg font-medium focus:ring-2 focus:ring-accent focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled
                        aria-disabled="true"
                    >
                        Pay Now
                    </button>
                </form>
                <div id="paymentStatus" role="alert" aria-live="polite" class="mt-6 text-center text-sm font-medium"></div>
            </div>
        </div>
    </main>

    <div id="confirmModal" class="modal fixed inset-0 flex items-center justify-center z-50" role="dialog" aria-labelledby="modalTitle" aria-modal="true">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-sm w-full">
            <h3 id="modalTitle" class="text-lg font-semibold text-gray-800 dark:text-gray-200">Confirm Payment</h3>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Are you sure you want to process a payment of <span id="modalAmount"></span>?</p>
            <div class="mt-6 flex justify-end space-x-4">
                <button id="cancelBtn" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-accent rounded">Cancel</button>
                <button id="confirmBtn" class="px-4 py-2 btn-primary text-white rounded font-medium focus:ring-2 focus:ring-accent focus:ring-offset-2">Confirm</button>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        (() => {
            'use strict';

            const form = document.getElementById('paymentForm');
            const submitBtn = document.getElementById('submitBtn');
            const statusMsg = document.getElementById('paymentStatus');
            const modal = document.getElementById('confirmModal');
            const modalAmount = document.getElementById('modalAmount');
            const confirmBtn = document.getElementById('confirmBtn');
            const cancelBtn = document.getElementById('cancelBtn');

            // Input fields
            const cardholderName = form.cardholder_name;
            const cardNumber = form.card_number;
            const expiry = form.expiry_date;
            const cvv = form.cvv;
            const amount = form.amount;

            // Error message elements
            const errors = {
                name: document.getElementById('errorName'),
                number: document.getElementById('errorNumber'),
                expiry: document.getElementById('errorExpiry'),
                cvv: document.getElementById('errorCVV'),
                amount: document.getElementById('errorAmount')
            };

            // Validation helpers
            function validateName(value) {
                return /^[a-zA-Z\s\-']+$/.test(value.trim());
            }

            function validateCardNumber(value) {
                const num = value.replace(/[\s-]/g, '');
                if (!/^\d{16}$/.test(num)) return false;
                let sum = 0;
                for (let i = 0; i < 16; i++) {
                    let intVal = parseInt(num.charAt(15 - i), 10);
                    if (i % 2 === 1) {
                        intVal *= 2;
                        if (intVal > 9) intVal -= 9;
                    }
                    sum += intVal;
                }
                return (sum % 10) === 0;
            }

            function validateExpiry(value) {
                if (!/^(0[1-9]|1[0-2])\/?([0-9]{2})$/.test(value)) return false;
                const parts = value.split('/');
                const month = parseInt(parts[0], 10);
                const year = 2000 + parseInt(parts[1], 10);
                const now = new Date();
                const currentMonth = now.getMonth() + 1;
                const currentYear = now.getFullYear();
                if (year < currentYear) return false;
                if (year === currentYear && month < currentMonth) return false;
                return true;
            }

            function validateCVV(value) {
                return /^\d{3,4}$/.test(value);
            }

            function validateAmount(value) {
                const num = parseFloat(value);
                return !isNaN(num) && num > 0;
            }

            function toggleError(field, show) {
                if (show) {
                    errors[field].style.display = 'block';
                    errors[field].setAttribute('aria-hidden', 'false');
                    form[field === 'name' ? 'cardholder_name' : field === 'number' ? 'card_number' : field].setAttribute('aria-invalid', 'true');
                } else {
                    errors[field].style.display = 'none';
                    errors[field].setAttribute('aria-hidden', 'true');
                    form[field === 'name' ? 'cardholder_name' : field === 'number' ? 'card_number' : field].setAttribute('aria-invalid', 'false');
                }
            }

            function validateForm() {
                const nameValid = validateName(cardholderName.value);
                const numberValid = validateCardNumber(cardNumber.value);
                const expiryValid = validateExpiry(expiry.value);
                const cvvValid = validateCVV(cvv.value);
                const amountValid = validateAmount(amount.value);

                toggleError('name', !nameValid);
                toggleError('number', !numberValid);
                toggleError('expiry', !expiryValid);
                toggleError('cvv', !cvvValid);
                toggleError('amount', !amountValid);

                return nameValid && numberValid && expiryValid && cvvValid && amountValid;
            }

            function formatCardNumber(value) {
                return value
                    .replace(/\D/g, '')
                    .replace(/(.{4})/g, '$1 ')
                    .trim();
            }

            function formatExpiry(value) {
                let input = value.replace(/\D/g, '').substring(0, 4);
                if (input.length >= 3) {
                    return input.substring(0, 2) + '/' + input.substring(2, 4);
                } else if (input.length >= 2) {
                    return input.substring(0, 2);
                }
                return input;
            }

            // Event listeners for formatting and validation
            cardNumber.addEventListener('input', (e) => {
                e.target.value = formatCardNumber(e.target.value);
                validateForm();
                toggleSubmit();
            });

            expiry.addEventListener('input', (e) => {
                e.target.value = formatExpiry(e.target.value);
                validateForm();
                toggleSubmit();
            });

            [cardholderName, cvv, amount].forEach((input) => {
                input.addEventListener('input', () => {
                    validateForm();
                    toggleSubmit();
                });
            });

            function toggleSubmit() {
                if (validateForm()) {
                    submitBtn.disabled = false;
                    submitBtn.setAttribute('aria-disabled', 'false');
                } else {
                    submitBtn.disabled = true;
                    submitBtn.setAttribute('aria-disabled', 'true');
                }
            }

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                if (validateForm()) {
                    modalAmount.textContent = `$${parseFloat(amount.value).toFixed(2)}`;
                    modal.classList.add('show');
                }
            });

            confirmBtn.addEventListener('click', () => {
                modal.classList.remove('show');
                submitBtn.textContent = 'Processing...';
                submitBtn.disabled = true;
                form.submit();
            });

            cancelBtn.addEventListener('click', () => {
                modal.classList.remove('show');
            });

            document.getElementById('mobileMenuBtn').addEventListener('click', () => {
                document.getElementById('mobileMenu').classList.toggle('hidden');
            });

            document.getElementById('closeMobileMenu').addEventListener('click', () => {
                document.getElementById('mobileMenu').classList.add('hidden');
            });
        })();
    </script>
</body>
</html>