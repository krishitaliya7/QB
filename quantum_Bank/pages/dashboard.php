<?php
include '../includes/db_connect.php';
include '../includes/session.php';
requireLogin();

$page_css = 'index.css';

$user_id = $_SESSION['user_id'];
// Check email verification
$verified = false;
try {
    $stmt = $pdo->prepare('SELECT verified FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $r = $stmt->fetch();
    $verified = $r && $r['verified'];
} catch (Exception $e) { }
$stmt = $pdo->prepare("SELECT * FROM accounts WHERE user_id = ?");
$stmt->execute([$user_id]);
$accounts = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT * FROM loans WHERE user_id = ?");
$stmt->execute([$user_id]);
$loans = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<!-- Dashboard Hero Section -->
<section class="gradient-bg text-white pt-16 pb-12">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold mb-4">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p class="text-xl text-blue-100">Your financial command center - manage accounts, track spending, and more.</p>
    </div>
</section>

<!-- Main Dashboard Content -->
<div class="container mx-auto px-4 py-8">

    <!-- Accounts Section -->
    <section class="mb-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Your Accounts</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($accounts as $account): ?>
                <div class="bg-white rounded-xl p-6 card-hover shadow-lg">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($account['account_type']); ?> Account</h3>
                            <p class="text-gray-500">**** **** **** <?php echo htmlspecialchars(substr($account['account_number'] ?? $account['id'], -4)); ?></p>
                        </div>
                        <div class="bg-blue-100 p-2 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 pt-4">
                        <p class="text-3xl font-bold text-gray-800">$<?php echo number_format($account['balance'], 2); ?></p>
                        <p class="text-gray-500">Available balance</p>
                        <div class="flex justify-between mt-4">
                            <button class="text-blue-600 hover:underline text-sm">Details</button>
                            <a href="payments.php" class="text-blue-600 hover:underline text-sm">Transfer</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Recent Transactions and Spending Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">

        <!-- Recent Transactions -->
        <section class="bg-white rounded-xl p-6 shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Recent Transactions</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-4 font-semibold text-gray-600">Date</th>
                            <th class="text-left py-2 px-4 font-semibold text-gray-600">Description</th>
                            <th class="text-left py-2 px-4 font-semibold text-gray-600">Amount</th>
                            <th class="text-left py-2 px-4 font-semibold text-gray-600">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr class="border-b border-gray-100">
                                <td class="py-3 px-4 text-sm text-gray-500"><?php echo htmlspecialchars(date('M d, Y', strtotime($transaction['created_at']))); ?></td>
                                <td class="py-3 px-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($transaction['description']); ?></div>
                                </td>
                                <td class="py-3 px-4 text-sm <?php echo $transaction['amount'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $transaction['amount'] >= 0 ? '+' : ''; ?>$<?php echo number_format(abs($transaction['amount']), 2); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Completed</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <a href="transactions.php" class="text-indigo-600 hover:underline text-sm">View all transactions →</a>
            </div>
        </section>

        <!-- Spending Analytics -->
        <section class="bg-white rounded-xl p-6 shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Spending Analytics</h3>
            <div class="h-64">
                <canvas id="spendingChart"></canvas>
            </div>
            <p class="text-sm text-gray-500 mt-4">Track your spending patterns across categories</p>
        </section>
    </div>

    <!-- Loan Applications -->
    <section class="mb-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Your Loan Applications</h2>
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-6 font-semibold text-gray-600">Loan Type</th>
                            <th class="text-left py-3 px-6 font-semibold text-gray-600">Amount</th>
                            <th class="text-left py-3 px-6 font-semibold text-gray-600">Interest Rate</th>
                            <th class="text-left py-3 px-6 font-semibold text-gray-600">Term</th>
                            <th class="text-left py-3 px-6 font-semibold text-gray-600">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr class="border-b border-gray-100">
                                <td class="py-4 px-6"><?php echo htmlspecialchars($loan['loan_type']); ?></td>
                                <td class="py-4 px-6">$<?php echo number_format($loan['amount'], 2); ?></td>
                                <td class="py-4 px-6"><?php echo number_format($loan['interest_rate'], 2); ?>%</td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($loan['term_months']); ?> months</td>
                                <td class="py-4 px-6">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs"><?php echo htmlspecialchars($loan['status']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Quick Actions -->
    <section>
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="payments.php" class="bg-blue-600 text-white p-4 rounded-lg hover:bg-blue-700 transition text-center">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
                Transfer Money
            </a>
            <a href="payments.php" class="bg-green-600 text-white p-4 rounded-lg hover:bg-green-700 transition text-center">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Pay Bills
            </a>
            <a href="cards.php" class="bg-blue-600 text-white p-4 rounded-lg hover:bg-blue-700 transition text-center">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                Manage Cards
            </a>
            <a href="loan.php" class="bg-orange-600 text-white p-4 rounded-lg hover:bg-orange-700 transition text-center">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Apply for Loan
            </a>
        </div>
    </section>
</div>

<script>
// Spending Chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('spendingChart').getContext('2d');
    const spendingData = {
        labels: ['Food', 'Transport', 'Entertainment', 'Utilities', 'Shopping', 'Other'],
        datasets: [{
            data: [450, 320, 180, 280, 150, 120],
            backgroundColor: [
                '#0b74de',
                '#055aa8',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#6b7280'
            ],
            hoverOffset: 4
        }]
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: spendingData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: true,
                    text: 'Monthly Spending by Category'
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
