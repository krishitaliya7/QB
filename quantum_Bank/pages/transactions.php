<?php
include '../includes/db_connect.php';
include '../includes/session.php';
include '../includes/send_mail.php';
requireLogin();





$user_id = getUserId();

// Fetch user's accounts (for 'from' dropdown and balances)
$stmt = $conn->prepare("SELECT id, account_type, account_number, balance FROM accounts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$accounts = $result->fetch_all(MYSQLI_ASSOC);

// Fetch transaction history
$stmt = $conn->prepare("SELECT id, created_at, description, amount, status FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);

// Handle transfer POST
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'transfer') {
    $from = (int)filter_input(INPUT_POST, 'from_account', FILTER_SANITIZE_NUMBER_INT);
    $to = (int)filter_input(INPUT_POST, 'to_account', FILTER_SANITIZE_NUMBER_INT);
    $to_external = filter_input(INPUT_POST, 'to_external', FILTER_SANITIZE_STRING);
    $amount = (float)filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid CSRF token.';
    } elseif ($amount <= 0) {
        $error = 'Amount must be positive.';
    } else {
        // Ensure 'from' account belongs to user
        $userAccountIds = array_column($accounts, 'id');
        if (!in_array($from, $userAccountIds, true)) {
            $error = 'Invalid source account selection.';
        } else {
            // Determine destination account id
            $destinationAccountId = null;
            if (!empty($to_external)) {
                $stmt = $conn->prepare('SELECT id FROM accounts WHERE account_number = ? LIMIT 1');
                $stmt->bind_param("s", $to_external);
                $stmt->execute();
                $result = $stmt->get_result();
                $found = $result->fetch_assoc();
                if ($found) $destinationAccountId = (int)$found['id'];
            }
            if ($destinationAccountId === null && $to > 0) $destinationAccountId = $to;
            if ($destinationAccountId === null) {
                $error = 'Invalid destination account.';
            } elseif ($destinationAccountId === $from) {
                $error = 'Cannot transfer to the same account.';
            } else {
                // OTP required for all transfers
                if (true) {
                    try {
                            $settings = include __DIR__ . '/../admin/config/settings.php';
                        $expirySeconds = $settings['otp_expiry_seconds'] ?? 900;
                        $maxAttempts = $settings['otp_max_attempts'] ?? 5;

                        // Rate limit
                        $rlWindow = date('Y-m-d H:i:s', time() - 30*60);
                        $stmt = $conn->prepare('SELECT COUNT(*) as count FROM transfer_otps WHERE user_id = ? AND created_at >= ?');
                        $stmt->bind_param("is", $user_id, $rlWindow);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $recent = (int)$row['count'];
                        if ($recent >= 3) {
                            throw new Exception('Too many OTP requests in a short period. Try again later.');
                        }

                        $otp = strval(random_int(100000, 999999));
                        $hash = password_hash($otp, PASSWORD_DEFAULT);
                        $expires = date('Y-m-d H:i:s', time() + $expirySeconds);
                        $stmt = $conn->prepare('INSERT INTO transfer_otps (user_id, from_account, to_account, amount, otp_hash, max_attempts, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
                        $stmt->bind_param("iiidisi", $user_id, $from, $destinationAccountId, $amount, $hash, $maxAttempts, $expires);
                        $stmt->execute();
                        $otpId = $conn->insert_id;

                        // Send OTP email
                        $stmt = $conn->prepare('SELECT email, username FROM users WHERE id = ? LIMIT 1');
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $u = $result->fetch_assoc();
                        if ($u && !empty($u['email'])) {
                            $html = "<p>Hello " . htmlspecialchars($u['username']) . ",</p>" .
                                    "<p>You've initiated a transfer of $" . number_format($amount,2) . " from account #$from to account #$destinationAccountId.</p>" .
                                    "<p>Please verify this transfer by entering the OTP code on the verification page. The code expires in " . intval($expirySeconds/60) . " minutes.</p>" .
                                    "<p>Your OTP code: <strong>" . htmlspecialchars($otp) . "</strong></p>";
                            send_mail($u['email'], 'Verify your transfer', strip_tags($html), '', $html);
                        }
                        // Audit log
                        audit_log($conn, 'transfer.otp.created', $user_id, ['otp_id' => $otpId, 'from' => $from, 'to' => $destinationAccountId, 'amount' => $amount]);
                        $success = 'Transfer requires verification. An OTP has been sent to your email. <a href="transfer_verify.php">Enter OTP to complete transfer</a>';
                    } catch (Exception $e) {
                        $error = 'Failed to initiate secured transfer: ' . $e->getMessage();
                    }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Transactions - QuantumBank</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- For potential charts if added -->
  <style>
    /* Custom Tailwind extensions */
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
    @media (min-width: 768px) {
      .dashboard-layout {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
      }
    }
  </style>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">

  <!-- Header -->
  <header class="gradient-bg text-white p-6">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <h1 class="text-2xl md:text-3xl font-bold">QuantumBank</h1>
      <nav class="hidden md:flex space-x-6">
        <a href="dashboard.php" class="hover:underline">Dashboard</a>
        <a href="accounts.php" class="hover:underline">Accounts</a>
        <a href="#" class="hover:underline font-semibold">Transactions</a>
        <a href="loans.php" class="hover:underline">Loans</a>
        <a href="settings.php" class="hover:underline">Settings</a>
      </nav>
      <button id="mobileMenuBtn" class="md:hidden text-white focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>
    </div>
    <div id="mobileMenu" class="hidden md:hidden bg-blue-700 p-4 text-white">
      <nav class="flex flex-col space-y-2">
        <a href="dashboard.php" class="hover:underline">Dashboard</a>
        <a href="accounts.php" class="hover:underline">Accounts</a>
        <a href="#" class="hover:underline font-semibold">Transactions</a>
        <a href="loans.php" class="hover:underline">Loans</a>
        <a href="settings.php" class="hover:underline">Settings</a>
      </nav>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container mx-auto px-4 py-8 md:py-12">
    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6">Transactions</h2>
    
    <?php if ($error): ?>
      <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="dashboard-layout grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- Transfer Form Section -->
      <section class="bg-white p-6 rounded-xl shadow-md card-hover">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Make a Transfer</h3>
        <form id="transferForm" method="POST">
          <input type="hidden" name="action" value="transfer">
          <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
          <div class="mb-4">
            <label for="from_account" class="block text-sm font-medium text-gray-700">From Account</label>
            <select id="from_account" name="from_account" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
              <?php foreach ($accounts as $a): ?>
                <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['account_type']) . ' (' . htmlspecialchars($a['account_number'] ?? $a['id']) . ') - $' . number_format($a['balance'], 2); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-4">
            <label for="to_account" class="block text-sm font-medium text-gray-700">To Account (Internal)</label>
            <select id="to_account" name="to_account" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              <option value="">Select internal account (optional)</option>
              <?php foreach ($accounts as $a): ?>
                <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['account_type']) . ' (' . htmlspecialchars($a['account_number'] ?? $a['id']) . ') - $' . number_format($a['balance'], 2); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-4">
            <label for="to_external" class="block text-sm font-medium text-gray-700">Or External Account Number</label>
            <input type="text" id="to_external" name="to_external" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Enter account number">
          </div>
          <div class="mb-4">
            <label for="amount" class="block text-sm font-medium text-gray-700">Amount ($)</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
          </div>
          <button type="button" id="confirmTransferBtn" class="w-full bg-blue-600 text-white py-2 rounded-lg font-medium hover:bg-blue-700 transition">Send Transfer</button>
        </form>
      </section>

      <!-- Transaction History Section -->
      <section class="bg-white p-6 rounded-xl shadow-md card-hover md:col-span-1">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Transaction History</h3>
        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <div>
            <label for="searchDesc" class="block text-sm font-medium text-gray-700">Search Description</label>
            <input type="text" id="searchDesc" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Search...">
          </div>
          <div>
            <label for="dateFrom" class="block text-sm font-medium text-gray-700">From Date</label>
            <input type="date" id="dateFrom" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
          </div>
          <div>
            <label for="dateTo" class="block text-sm font-medium text-gray-700">To Date</label>
            <input type="date" id="dateTo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
          </div>
          <div>
            <label for="amountMin" class="block text-sm font-medium text-gray-700">Min Amount</label>
            <input type="number" id="amountMin" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Min">
          </div>
          <div>
            <label for="amountMax" class="block text-sm font-medium text-gray-700">Max Amount</label>
            <input type="number" id="amountMax" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Max">
          </div>
          <div>
            <label for="typeFilter" class="block text-sm font-medium text-gray-700">Type</label>
            <select id="typeFilter" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              <option value="all">All</option>
              <option value="debit">Debit</option>
              <option value="credit">Credit</option>
            </select>
          </div>
        </div>
        <button id="applyFilters" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">Apply Filters</button>
        <!-- Transaction Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody id="transactionTableBody" class="bg-white divide-y divide-gray-200">
              <?php foreach ($transactions as $tx): ?>
                <tr data-date="<?php echo htmlspecialchars(date('Y-m-d', strtotime($tx['created_at']))); ?>" data-amount="<?php echo htmlspecialchars($tx['amount']); ?>" data-type="<?php echo $tx['amount'] < 0 ? 'debit' : 'credit'; ?>" data-desc="<?php echo htmlspecialchars(strtolower($tx['description'])); ?>">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($tx['created_at']))); ?></td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($tx['description']); ?></td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $tx['amount'] < 0 ? 'text-red-600' : 'text-green-600'; ?>">$<?php echo number_format(abs($tx['amount']), 2); ?> <?php echo $tx['amount'] < 0 ? '(Debit)' : '(Credit)'; ?></td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($tx['status']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </main>

  <!-- Confirmation Modal -->
  <div id="confirmModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-xl max-w-md w-full">
      <h3 class="text-xl font-bold mb-4">Confirm Transfer</h3>
      <p id="confirmDetails" class="mb-4 text-gray-600"></p>
      <div class="flex justify-end space-x-2">
        <button id="cancelConfirm" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Cancel</button>
        <button id="submitTransfer" class="px-4 py-2 bg-blue-600 text-white rounded-md">Confirm</button>
      </div>
    </div>
  </div>

  <script>
    // Mobile Menu
    document.getElementById('mobileMenuBtn').addEventListener('click', () => {
      document.getElementById('mobileMenu').classList.toggle('hidden');
    });

    // Transfer Confirmation Modal
    const transferForm = document.getElementById('transferForm');
    const confirmModal = document.getElementById('confirmModal');
    const confirmDetails = document.getElementById('confirmDetails');
    const cancelConfirm = document.getElementById('cancelConfirm');
    const submitTransfer = document.getElementById('submitTransfer');

    document.getElementById('confirmTransferBtn').addEventListener('click', () => {
      const from = document.getElementById('from_account').options[document.getElementById('from_account').selectedIndex].text;
      const to = document.getElementById('to_account').value ? document.getElementById('to_account').options[document.getElementById('to_account').selectedIndex].text : document.getElementById('to_external').value;
      const amount = document.getElementById('amount').value;
      confirmDetails.innerHTML = `Transfer <strong>$${amount}</strong> from <strong>${from}</strong> to <strong>${to}</strong>?`;
      confirmModal.classList.remove('hidden');
    });

    cancelConfirm.addEventListener('click', () => {
      confirmModal.classList.add('hidden');
    });

    submitTransfer.addEventListener('click', () => {
      transferForm.submit();
    });

    // Transaction Filters
    document.getElementById('applyFilters').addEventListener('click', () => {
      const searchDesc = document.getElementById('searchDesc').value.toLowerCase();
      const dateFrom = document.getElementById('dateFrom').value;
      const dateTo = document.getElementById('dateTo').value;
      const amountMin = parseFloat(document.getElementById('amountMin').value) || -Infinity;
      const amountMax = parseFloat(document.getElementById('amountMax').value) || Infinity;
      const typeFilter = document.getElementById('typeFilter').value;

      const rows = document.querySelectorAll('#transactionTableBody tr');
      rows.forEach(row => {
        const date = row.dataset.date;
        const amount = parseFloat(row.dataset.amount);
        const type = row.dataset.type;
        const desc = row.dataset.desc;

        let show = true;
        if (searchDesc && !desc.includes(searchDesc)) show = false;
        if (dateFrom && date < dateFrom) show = false;
        if (dateTo && date > dateTo) show = false;
        if (amount < amountMin || amount > amountMax) show = false;
        if (typeFilter !== 'all' && type !== typeFilter) show = false;

        row.style.display = show ? '' : 'none';
      });
    });
  </script>

</body>
</html>