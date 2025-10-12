<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/send_mail.php';
requireLogin();

$user_id = getUserId();
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

// Handle Loan Application
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_loan') {
    $loan_type = filter_input(INPUT_POST, 'loan_type', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $term_months = filter_input(INPUT_POST, 'term_months', FILTER_SANITIZE_NUMBER_INT);
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } elseif (!$loan_type || !in_array($loan_type, ['Personal', 'Business', 'Home'])) {
        $error = "Invalid loan type.";
    } elseif ($amount <= 0 || $amount > 1000000) {
        $error = "Loan amount must be between $0.01 and $1,000,000.";
    } elseif ($term_months <= 0 || $term_months > 360) {
        $error = "Loan term must be between 1 and 360 months.";
    } else {
        try {
            $interest_rate = $loan_type === 'Personal' ? 5.50 : ($loan_type === 'Business' ? 6.50 : 4.50);
            $stmt = $conn->prepare("INSERT INTO loans (user_id, loan_type, amount, interest_rate, term_months, status, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
            $stmt->bind_param("isdii", $user_id, $loan_type, $amount, $interest_rate, $term_months);
            $stmt->execute();
            $loan_id = $conn->insert_id;

            // Send notification to user
            $stmt = $conn->prepare("SELECT email, username FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            if ($user && !empty($user['email'])) {
                $html = "<p>Hello " . htmlspecialchars($user['username']) . ",</p>" .
                        "<p>Your application for a $loan_type loan of $" . number_format($amount, 2) . " has been submitted successfully (ID: #$loan_id).</p>" .
                        "<p>Status: Pending. We will notify you once it is reviewed.</p>";
                send_mail($user['email'], 'Loan Application Submitted', strip_tags($html), '', $html);
            }
            // Add message to inbox
            add_message($user_id, 'confirmation', "Your application for a $loan_type loan of $" . number_format($amount, 2) . " has been submitted successfully (ID: #$loan_id). Status: Pending.");
            $success = "Loan application submitted successfully! You'll receive an email confirmation.";
        } catch (Exception $e) {
            $error = "Loan application failed: " . $e->getMessage();
        }
    }
}

// Handle Admin Actions (Approve, Reject, Delete)
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['approve', 'reject', 'delete'])) {
    $loan_id = (int)filter_input(INPUT_POST, 'loan_id', FILTER_SANITIZE_NUMBER_INT);
    $action = $_POST['action'];

    try {
        $conn->begin_transaction();
        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM loans WHERE id = ?");
            $stmt->bind_param("i", $loan_id);
            $stmt->execute();
        } else {
            $status = $action === 'approve' ? 'Approved' : 'Rejected';
            $stmt = $conn->prepare("UPDATE loans SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $status, $loan_id);
            $stmt->execute();

            // Notify user
            $stmt = $conn->prepare("SELECT u.email, u.username, l.user_id, l.loan_type, l.amount FROM loans l JOIN users u ON l.user_id = u.id WHERE l.id = ?");
            $stmt->bind_param("i", $loan_id);
            $stmt->execute();
            $loan = $stmt->get_result()->fetch_assoc();
            if ($loan && !empty($loan['email'])) {
                $html = "<p>Hello " . htmlspecialchars($loan['username']) . ",</p>" .
                        "<p>Your $loan[loan_type] loan application for $" . number_format($loan['amount'], 2) . " (ID: #$loan_id) has been $status.</p>";
                send_mail($loan['email'], "Loan Application $status", strip_tags($html), '', $html);
            }
            // Add message to inbox
            if ($loan) {
                add_message($loan['user_id'], 'confirmation', "Your $loan[loan_type] loan application for $" . number_format($loan['amount'], 2) . " (ID: #$loan_id) has been $status.");
            }
        }
        $conn->commit();
        $success = "Loan $action action completed successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to process loan action: " . $e->getMessage();
    }
}

// Fetch user's loans
$stmt = $conn->prepare("SELECT id, loan_type, amount, interest_rate, term_months, status, created_at FROM loans WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all loans for admin
$all_loans = [];
if ($is_admin) {
    $stmt = $conn->prepare("SELECT l.id, l.loan_type, l.amount, l.interest_rate, l.term_months, l.status, l.created_at, u.username
                           FROM loans l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC");
    $stmt->execute();
    $all_loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Loans - QuantumBank</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
    .gradient-bg {
      background: linear-gradient(135deg, #1e3a8a, #3b82f6);
    }
    .card-hover {
      transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    .progress-bar {
      transition: width 0.3s ease-in-out;
    }
    @media (min-width: 768px) {
      .container {
        max-width: 1200px;
      }
    }
  </style>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">

  <!-- Header -->
  <header class="gradient-bg text-white p-6 sticky top-0 z-50 shadow-md">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <h1 class="text-2xl md:text-3xl font-bold">QuantumBank</h1>
      <nav class="hidden md:flex space-x-6 text-md font-medium">
        <a href="dashboard.php" class="hover:underline">Dashboard</a>
        <a href="accounts.php" class="hover:underline">Accounts</a>
        <a href="transfer.php" class="hover:underline">Transactions</a>
        <a href="#" class="hover:underline font-semibold">Loans</a>
        <a href="settings.php" class="hover:underline">Settings</a>
        <?php if ($is_admin): ?>
          <a href="#admin-section" class="hover:underline">Admin Panel</a>
        <?php endif; ?>
      </nav>
      <button id="mobileMenuBtn" class="md:hidden focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>
    </div>
    <div id="mobileMenu" class="hidden md:hidden bg-blue-700 text-white p-4">
      <nav class="flex flex-col space-y-4">
        <a href="dashboard.php" class="hover:underline">Dashboard</a>
        <a href="accounts.php" class="hover:underline">Accounts</a>
        <a href="transfer.php" class="hover:underline">Transactions</a>
        <a href="#" class="hover:underline font-semibold">Loans</a>
        <a href="settings.php" class="hover:underline">Settings</a>
        <?php if ($is_admin): ?>
          <a href="#admin-section" class="hover:underline">Admin Panel</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container mx-auto px-4 py-8 md:py-12">
    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6 text-center">Loan Management</h2>

    <!-- Alerts -->
    <?php if ($error): ?>
      <div class="mb-6 p-4 bg-red-100 text-red-800 rounded-md"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="mb-6 p-4 bg-green-100 text-green-800 rounded-md"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="flex flex-wrap border-b border-gray-200 mb-6">
      <button id="tab-apply" class="tab-button active px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600 bg-blue-50 rounded-t-md">Apply for Loan</button>
      <button id="tab-myloans" class="tab-button px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">My Loans</button>
      <?php if ($is_admin): ?>
        <button id="tab-admin" class="tab-button px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Admin Panel</button>
      <?php endif; ?>
    </div>

    <!-- Loan Application and EMI Calculator -->
    <div id="tab-apply-content" class="tab-content active">
      <section class="bg-white p-6 rounded-xl shadow-md card-hover">
      <h3 class="text-xl font-bold text-gray-800 mb-4">Apply for a Loan & Calculate EMI</h3>
      <form id="loanForm" method="POST">
        <input type="hidden" name="action" value="apply_loan">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="loan_type" class="block text-sm font-medium text-gray-700">Loan Type</label>
            <select id="loan_type" name="loan_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
              <option value="">Select Loan Type</option>
              <option value="Personal">Personal Loan (5.5%)</option>
              <option value="Business">Business Loan (6.5%)</option>
              <option value="Home">Home Loan (4.5%)</option>
            </select>
            <p id="loanTypeError" class="text-red-600 text-sm hidden">Please select a loan type.</p>
          </div>
          <div>
            <label for="amount" class="block text-sm font-medium text-gray-700">Loan Amount ($)</label>
            <input type="number" id="amount" name="amount" step="0.01" min="1000" max="1000000" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            <input type="range" id="amountSlider" min="1000" max="1000000" value="10000" class="w-full mt-2">
            <p id="amountError" class="text-red-600 text-sm hidden">Amount must be between $1,000 and $1,000,000.</p>
          </div>
          <div>
            <label for="term_months" class="block text-sm font-medium text-gray-700">Term (Months)</label>
            <input type="number" id="term_months" name="term_months" min="1" max="360" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            <input type="range" id="termSlider" min="1" max="360" value="12" class="w-full mt-2">
            <p id="termError" class="text-red-600 text-sm hidden">Term must be between 1 and 360 months.</p>
          </div>
        </div>
        <div class="mt-4">
          <button type="button" id="calculateEmiBtn" class="w-full md:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">Calculate EMI</button>
          <button type="button" id="submitLoanBtn" class="w-full md:w-auto px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition mt-2 md:mt-0 md:ml-2">Submit Application</button>
        </div>
      </form>
      <div id="emiResult" class="mt-4 hidden">
        <h4 class="text-lg font-semibold text-gray-800">EMI Details</h4>
        <p>Monthly EMI: <span id="emiValue" class="font-bold"></span></p>
        <p>Total Interest: <span id="totalInterest" class="font-bold"></span></p>
        <p>Total Payment: <span id="totalPayment" class="font-bold"></span></p>
        <div class="mt-4">
          <h5 class="text-sm font-medium text-gray-700">Repayment Schedule</h5>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">EMI</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Principal</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Interest</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                </tr>
              </thead>
              <tbody id="scheduleTable" class="bg-white divide-y divide-gray-200"></tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
    </div>

    <!-- User's Loan History -->
    <div id="tab-myloans-content" class="tab-content hidden">
      <section class="bg-white p-6 rounded-xl shadow-md card-hover">
      <h3 class="text-xl font-bold text-gray-800 mb-4">Your Loan History</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applied</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($user_loans as $loan): ?>
              <tr>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($loan['id']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($loan['loan_type']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900">$<?php echo number_format($loan['amount'], 2); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo number_format($loan['interest_rate'], 2); ?>%</td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($loan['term_months']); ?> months</td>
                <td class="px-4 py-3 text-sm">
                  <span class="inline-block px-2 py-1 text-xs font-medium rounded-full <?php echo $loan['status'] === 'Approved' ? 'bg-green-100 text-green-800' : ($loan['status'] === 'Rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                    <?php echo htmlspecialchars($loan['status']); ?>
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500"><?php echo htmlspecialchars(date('Y-m-d', strtotime($loan['created_at']))); ?></td>
                <td class="px-4 py-3 text-sm">
                  <button onclick="showLoanDetails(<?php echo $loan['id']; ?>, '<?php echo htmlspecialchars($loan['loan_type']); ?>', <?php echo $loan['amount']; ?>, <?php echo $loan['interest_rate']; ?>, <?php echo $loan['term_months']; ?>)" class="text-blue-600 hover:underline">Details</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    </div>

    <!-- Admin Panel -->
    <?php if ($is_admin): ?>
      <div id="tab-admin-content" class="tab-content hidden">
        <section id="admin-section" class="bg-white p-6 rounded-xl shadow-md card-hover">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Admin: Manage Loan Applications</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applied</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($all_loans as $loan): ?>
                <tr>
                  <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($loan['id']); ?></td>
                  <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($loan['username']); ?></td>
                  <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($loan['loan_type']); ?></td>
                  <td class="px-4 py-3 text-sm text-gray-900">$<?php echo number_format($loan['amount'], 2); ?></td>
                  <td class="px-4 py-3 text-sm text-gray-900"><?php echo number_format($loan['interest_rate'], 2); ?>%</td>
                  <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($loan['term_months']); ?> months</td>
                  <td class="px-4 py-3 text-sm">
                    <span class="inline-block px-2 py-1 text-xs font-medium rounded-full <?php echo $loan['status'] === 'Approved' ? 'bg-green-100 text-green-800' : ($loan['status'] === 'Rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                      <?php echo htmlspecialchars($loan['status']); ?>
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500"><?php echo htmlspecialchars(date('Y-m-d', strtotime($loan['created_at']))); ?></td>
                  <td class="px-4 py-3 text-sm">
                    <?php if ($loan['status'] === 'Pending'): ?>
                      <form method="POST" class="inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <button type="submit" class="text-green-600 hover:underline mr-2">Approve</button>
                      </form>
                      <form method="POST" class="inline">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <button type="submit" class="text-red-600 hover:underline mr-2">Reject</button>
                      </form>
                    <?php endif; ?>
                    <form method="POST" class="inline">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                      <button type="button" onclick="confirmDelete(<?php echo $loan['id']; ?>)" class="text-gray-600 hover:underline">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
      </div>
    <?php endif; ?>
  </main>

  <!-- Loan Details Modal -->
  <div id="loanDetailsModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-xl max-w-2xl w-full">
      <h3 class="text-xl font-bold text-gray-800 mb-4">Loan Details</h3>
      <div id="loanDetailsContent" class="mb-4"></div>
      <div class="flex justify-end">
        <button id="closeDetailsModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Close</button>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteConfirmModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-xl max-w-md w-full">
      <h3 class="text-xl font-bold text-gray-800 mb-4">Confirm Deletion</h3>
      <p class="mb-4 text-gray-600">Are you sure you want to delete this loan application? This action cannot be undone.</p>
      <div class="flex justify-end space-x-2">
        <button id="cancelDelete" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Cancel</button>
        <form id="deleteForm" method="POST">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="loan_id" id="deleteLoanId">
          <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
          <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md">Delete</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Tab Switching
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
      button.addEventListener('click', () => {
        // Remove active class from all buttons
        tabButtons.forEach(btn => {
          btn.classList.remove('active', 'text-blue-600', 'border-b-2', 'border-blue-600', 'bg-blue-50');
          btn.classList.add('text-gray-500', 'hover:text-gray-700');
        });
        // Add active class to clicked button
        button.classList.add('active', 'text-blue-600', 'border-b-2', 'border-blue-600', 'bg-blue-50');
        button.classList.remove('text-gray-500', 'hover:text-gray-700');

        // Hide all tab contents
        tabContents.forEach(content => content.classList.add('hidden'));

        // Show the corresponding tab content
        const tabId = button.id.replace('tab-', 'tab-') + '-content';
        document.getElementById(tabId).classList.remove('hidden');
      });
    });

    // Mobile Menu Toggle
    document.getElementById('mobileMenuBtn').addEventListener('click', () => {
      document.getElementById('mobileMenu').classList.toggle('hidden');
    });

    // Form Validation and EMI Calculation
    const loanForm = document.getElementById('loanForm');
    const loanType = document.getElementById('loan_type');
    const amountInput = document.getElementById('amount');
    const amountSlider = document.getElementById('amountSlider');
    const termInput = document.getElementById('term_months');
    const termSlider = document.getElementById('termSlider');
    const emiResult = document.getElementById('emiResult');
    const scheduleTable = document.getElementById('scheduleTable');

    // Sync sliders with inputs
    amountInput.addEventListener('input', () => {
      if (amountInput.value >= 1000 && amountInput.value <= 1000000) amountSlider.value = amountInput.value;
    });
    amountSlider.addEventListener('input', () => amountInput.value = amountSlider.value);
    termInput.addEventListener('input', () => {
      if (termInput.value >= 1 && termInput.value <= 360) termSlider.value = termInput.value;
    });
    termSlider.addEventListener('input', () => termInput.value = termSlider.value);

    // EMI Calculation
    document.getElementById('calculateEmiBtn').addEventListener('click', () => {
      const loanType = document.getElementById('loan_type').value;
      const amount = parseFloat(document.getElementById('amount').value);
      const term = parseInt(document.getElementById('term_months').value);
      let valid = true;

      // Validation
      document.getElementById('loanTypeError').classList.add('hidden');
      document.getElementById('amountError').classList.add('hidden');
      document.getElementById('termError').classList.add('hidden');

      if (!loanType) {
        document.getElementById('loanTypeError').classList.remove('hidden');
        valid = false;
      }
      if (!amount || amount < 1000 || amount > 1000000) {
        document.getElementById('amountError').classList.remove('hidden');
        valid = false;
      }
      if (!term || term < 1 || term > 360) {
        document.getElementById('termError').classList.remove('hidden');
        valid = false;
      }

      if (!valid) return;

      const interestRate = loanType === 'Personal' ? 5.5 / 100 / 12 : (loanType === 'Business' ? 6.5 / 100 / 12 : 4.5 / 100 / 12);
      const emi = (amount * interestRate * Math.pow(1 + interestRate, term)) / (Math.pow(1 + interestRate, term) - 1);
      const totalPayment = emi * term;
      const totalInterest = totalPayment - amount;

      document.getElementById('emiValue').textContent = `$${emi.toFixed(2)}`;
      document.getElementById('totalInterest').textContent = `$${totalInterest.toFixed(2)}`;
      document.getElementById('totalPayment').textContent = `$${totalPayment.toFixed(2)}`;
      emiResult.classList.remove('hidden');

      // Repayment Schedule
      scheduleTable.innerHTML = '';
      let balance = amount;
      for (let i = 1; i <= term && balance > 0.01; i++) {
        const interest = balance * interestRate;
        const principal = emi - interest;
        balance -= principal;
        if (balance < 0) balance = 0;
        const row = document.createElement('tr');
        row.innerHTML = `
          <td class="px-4 py-2 text-sm text-gray-900">${i}</td>
          <td class="px-4 py-2 text-sm text-gray-900">$${emi.toFixed(2)}</td>
          <td class="px-4 py-2 text-sm text-gray-900">$${principal.toFixed(2)}</td>
          <td class="px-4 py-2 text-sm text-gray-900">$${interest.toFixed(2)}</td>
          <td class="px-4 py-2 text-sm text-gray-900">$${balance.toFixed(2)}</td>
        `;
        scheduleTable.appendChild(row);
      }
    });

    // Submit Loan Application
    document.getElementById('submitLoanBtn').addEventListener('click', () => {
      let valid = true;
      document.getElementById('loanTypeError').classList.add('hidden');
      document.getElementById('amountError').classList.add('hidden');
      document.getElementById('termError').classList.add('hidden');

      if (!loanType.value) {
        document.getElementById('loanTypeError').classList.remove('hidden');
        valid = false;
      }
      if (!amountInput.value || amountInput.value < 1000 || amountInput.value > 1000000) {
        document.getElementById('amountError').classList.remove('hidden');
        valid = false;
      }
      if (!termInput.value || termInput.value < 1 || termInput.value > 360) {
        document.getElementById('termError').classList.remove('hidden');
        valid = false;
      }
      if (valid) loanForm.submit();
    });

    // Loan Details Modal
    function showLoanDetails(id, type, amount, rate, term) {
      const monthlyRate = rate / 100 / 12;
      const emi = (amount * monthlyRate * Math.pow(1 + monthlyRate, term)) / (Math.pow(1 + monthlyRate, term) - 1);
      const totalPayment = emi * term;
      const totalInterest = totalPayment - amount;
      let scheduleHtml = '<h5 class="text-sm font-medium text-gray-700 mt-4">Repayment Schedule</h5>' +
                        '<table class="min-w-full divide-y divide-gray-200">' +
                        '<thead class="bg-gray-50">' +
                        '<tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Month</th>' +
                        '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">EMI</th>' +
                        '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Principal</th>' +
                        '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Interest</th>' +
                        '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Balance</th></tr>' +
                        '</thead><tbody class="bg-white divide-y divide-gray-200">';
      let balance = amount;
      for (let i = 1; i <= term && balance > 0.01; i++) {
        const interest = balance * monthlyRate;
        const principal = emi - interest;
        balance -= principal;
        if (balance < 0) balance = 0;
        scheduleHtml += `<tr>
          <td class="px-4 py-2 text-sm text-gray-900">${i}</td>
          <td class="px-4 py-2 text-sm text-gray-900">$${emi.toFixed(2)}</td>
          <td class="px-4 py-2 text-sm text-gray-900">$${principal.toFixed(2)}</td>
          <td class="px-4 py-2 text-sm text-gray-900">$${interest.toFixed(2)}</td>
          <td class="px-4 py-2 text-sm text-gray-900">$${balance.toFixed(2)}</td>
        </tr>`;
      }
      scheduleHtml += '</tbody></table>';

      document.getElementById('loanDetailsContent').innerHTML = `
        <p><strong>Loan ID:</strong> ${id}</p>
        <p><strong>Type:</strong> ${type}</p>
        <p><strong>Amount:</strong> $${amount.toFixed(2)}</p>
        <p><strong>Interest Rate:</strong> ${rate.toFixed(2)}%</p>
        <p><strong>Term:</strong> ${term} months</p>
        <p><strong>Monthly EMI:</strong> $${emi.toFixed(2)}</p>
        <p><strong>Total Interest:</strong> $${totalInterest.toFixed(2)}</p>
        <p><strong>Total Payment:</strong> $${totalPayment.toFixed(2)}</p>
        ${scheduleHtml}
      `;
      document.getElementById('loanDetailsModal').classList.remove('hidden');
    }

    document.getElementById('closeDetailsModal').addEventListener('click', () => {
      document.getElementById('loanDetailsModal').classList.add('hidden');
    });

    // Delete Confirmation
    function confirmDelete(loanId) {
      document.getElementById('deleteLoanId').value = loanId;
      document.getElementById('deleteConfirmModal').classList.remove('hidden');
    }

    document.getElementById('cancelDelete').addEventListener('click', () => {
      document.getElementById('deleteConfirmModal').classList.add('hidden');
    });
  </script>

</body>
</html>