<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>QuantumBank Dashboard</title>

<?php
include '../includes/session.php';
include '../includes/db_connect.php';

requireLogin();

$user_id = getUserId();
$username = getUsername();

// Fetch user's accounts
$stmt = $conn->prepare("SELECT account_type, account_number, balance FROM accounts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch recent transactions
$stmt = $conn->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as date_formatted, description, amount, status FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

    
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chart.js for spending analytics chart -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Custom Tailwind extensions or overrides */
    body {
      font-family: 'Inter', sans-serif;
      scroll-behavior: smooth;
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
    /* Responsive adjustments */
    @media (min-width: 768px) {
      .dashboard-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
      }
    }
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }
    /* Refined chart styling */
    #spendingChart {
      max-height: 200px; /* Smaller chart size */
    }
  </style>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">

  <!-- Header Section -->
  <header class="gradient-bg text-white p-6">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <h1 class="text-2xl md:text-3xl font-bold">QuantumBank</h1>
      <nav class="hidden md:flex space-x-6">
        <a href="#" class="hover:underline">Dashboard</a>
        <a href="#" class="hover:underline">Accounts</a>
        <a href="cards.php" class="hover:underline">Cards</a>
        <a href="#" class="hover:underline">Transactions</a>
        <a href="#" class="hover:underline">Loans</a>
        <a href="#" class="hover:underline">Settings</a>
        <a href="logout.php" class="hover:underline">Logout</a>
      </nav>
      <button class="md:hidden text-white focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container mx-auto px-4 py-8 md:py-12">
    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6 md:mb-8">
      Welcome back, <?php echo htmlspecialchars($username); ?>!
    </h2>
    
    <div class="mb-8 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200">
      <p class="text-sm text-gray-600 mb-2">Available Balance</p>
      <p class="text-3xl font-bold text-gray-800" id="currentBalance">$8,450.23</p>
      <p class="text-xs text-gray-500">Updated: October 10, 2025</p>
    </div>
    
    <div class="dashboard-layout grid grid-cols-1 gap-6 md:gap-8">
      <!-- Main Content Area -->
      <div class="space-y-8">
        <!-- Accounts Overview -->
        <section>
          <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">Your Accounts</h3>
          <div id="accountsContainer" class="dashboard-grid"></div>
        </section>

        <!-- Recent Transactions -->
        <section id="recent-transactions">
          <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">Recent Transactions</h3>
          <div class="bg-white shadow-md rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th scope="col" class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th scope="col" class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  </tr>
                </thead>
                <tbody id="transactionsTable" class="bg-white divide-y divide-gray-200"></tbody>
              </table>
            </div>
            <div class="p-4 text-right">
              <a href="#" class="text-blue-600 hover:underline text-sm">View all transactions →</a>
            </div>
          </div>
        </section>

        <!-- Loan Applications -->
        <section>
          <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">Your Loan Applications</h3>
          <div class="bg-white shadow-md rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Type</th>
                    <th scope="col" class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th scope="col" class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Interest Rate</th>
                    <th scope="col" class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Term</th>
                    <th scope="col" class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  </tr>
                </thead>
                <tbody id="loansTable" class="bg-white divide-y divide-gray-200"></tbody>
              </table>
            </div>
          </div>
        </section>
      </div>

      <!-- Sidebar Area -->
      <div class="space-y-8">
        <!-- Spending Analytics Chart -->
        <section class="bg-white p-4 md:p-6 rounded-xl shadow-md">
          <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-3">Spending Analytics</h3>
          <canvas id="spendingChart" class="w-full"></canvas>
          <p class="text-xs text-gray-500 mt-3 text-center">Monthly spending by category</p>
        </section>

        <!-- Quick Actions -->
        <section class="bg-white p-4 md:p-6 rounded-xl shadow-md">
          <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-4">Quick Actions</h3>
          <div class="grid grid-cols-1 gap-4">
            <a href="transfer.php" class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 transition flex items-center justify-center inline-block text-center">
              <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
              </svg>
              Make a Transfer
            </a>
            <button class="w-full bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700 transition flex items-center justify-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
              </svg>
              Pay Bills
            </button>
            <button onclick="window.location.href='cards.php'" class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 transition flex items-center justify-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
              </svg>
              Manage Cards
            </button>
            <button class="w-full bg-orange-600 text-white py-3 rounded-lg font-medium hover:bg-orange-700 transition flex items-center justify-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Apply for Loan
            </button>
          </div>
        </section>
      </div>
    </div>
  </main>



  <script>
    // Data from PHP
    const accounts = <?php echo json_encode($accounts); ?>;

    const transactions = <?php echo json_encode($recent_transactions); ?>;

    const loans = [
      { type: 'Personal Loan', amount: 10000, interest: 5.5, term: 36, status: 'Approved' },
      { type: 'Home Loan', amount: 200000, interest: 3.8, term: 360, status: 'Pending' },
      { type: 'Auto Loan', amount: 25000, interest: 4.2, term: 60, status: 'Approved' }
    ];

    // Calculate total balance
    const totalBalance = accounts.reduce((sum, account) => sum + account.balance, 0);

    // Populate Total Balance
    document.getElementById('currentBalance').textContent = `$${totalBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

    // Populate Accounts
    const accountsContainer = document.getElementById('accountsContainer');
    accounts.forEach(account => {
      const accountCard = document.createElement('div');
      accountCard.className = 'bg-white rounded-xl p-6 shadow card-hover';
      accountCard.innerHTML = `
        <div class="flex justify-between items-start mb-4">
          <div>
            <h4 class="text-lg font-semibold text-gray-800">${account.account_type}</h4>
            <p class="text-gray-500 mb-2">${account.account_number}</p>
          </div>
          <div class="bg-blue-100 p-2 rounded-lg">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
        </div>
        <p class="text-3xl font-bold text-gray-900">$${parseFloat(account.balance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
        <p class="text-gray-500">Available balance</p>
        <div class="flex justify-between mt-4">
          <button class="text-blue-600 hover:underline text-sm">Details</button>
          <button class="text-blue-600 hover:underline text-sm">Transfer</button>
        </div>
      `;
      accountsContainer.appendChild(accountCard);
    });



    // Populate Transactions
    const transactionsTable = document.getElementById('transactionsTable');
    transactions.forEach(tx => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">${tx.date_formatted}</td>
        <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-900">${tx.description}</td>
        <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm ${parseFloat(tx.amount) >= 0 ? 'text-green-600' : 'text-red-600'}">
          ${parseFloat(tx.amount) >= 0 ? '+' : ''}$${Math.abs(parseFloat(tx.amount)).toFixed(2)}
        </td>
        <td class="px-4 md:px-6 py-4 whitespace-nowrap">
          <span class="inline-block px-2 py-1 text-xs font-medium rounded-full ${(tx.status === 'Completed' || tx.status === 'Successful') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">${tx.status}</span>
        </td>
      `;
      transactionsTable.appendChild(tr);
    });

    // Populate Loans
    const loansTable = document.getElementById('loansTable');
    loans.forEach(loan => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-900">${loan.type}</td>
        <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-900">$${loan.amount.toLocaleString()}</td>
        <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-900">${loan.interest}%</td>
        <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-900">${loan.term} months</td>
        <td class="px-4 md:px-6 py-4 whitespace-nowrap">
          <span class="inline-block px-2 py-1 text-xs font-medium rounded-full ${loan.status === 'Approved' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}">${loan.status}</span>
        </td>
      `;
      loansTable.appendChild(tr);
    });

    // Spending Analytics Chart
    const ctx = document.getElementById('spendingChart').getContext('2d');
    const spendingData = {
      labels: ['Food', 'Utilities', 'Shopping', 'Transport', 'Entertainment', 'Other'],
      datasets: [{
        label: 'Spending',
        data: [450, 280, 230, 180, 150, 120],
        backgroundColor: [
          '#3b82f6', // blue-500
          '#1d4ed8', // blue-700
          '#10b981', // green-500
          '#f59e0b', // amber-500
          '#ef4444', // red-500
          '#6b7280'  // gray-500
        ],
        borderWidth: 0, // Remove borders for cleaner look
        hoverOffset: 8
      }]
    };
    new Chart(ctx, {
      type: 'doughnut',
      data: spendingData,
      options: {
        responsive: true,
        maintainAspectRatio: false, // Allow custom height
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              font: {
                size: 12, // Smaller, elegant font
                family: 'Inter',
                weight: '500'
              },
              padding: 15,
              usePointStyle: true, // Circular legend markers
              pointStyle: 'circle'
            }
          },
          title: {
            display: false // Remove title for minimalism
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleFont: { family: 'Inter', size: 12 },
            bodyFont: { family: 'Inter', size: 12 },
            padding: 8
          }
        },
        cutout: '65%', // Thinner doughnut for elegance
        animation: {
          animateScale: true,
          animateRotate: true
        }
      }
    });


  </script>

</body>
</html>

