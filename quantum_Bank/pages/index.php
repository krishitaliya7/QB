<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QuantumBank | Next-Gen Banking Solutions</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <script defer src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --primary: #4f46e5;
      --primary-dark: #4338ca;
      --secondary: #10b981;
      --accent: #f59e0b;
      --dark: #1e293b;
      --light: #f1f5f9;
      --text: #64748b;
      --error: #ef4444;
    }

    .dark {
      --primary: #6366f1;
      --primary-dark: #4f46e5;
      --secondary: #34d399;
      --accent: #fbbf24;
      --dark: #0f172a;
      --light: #1e293b;
      --text: #94a3b8;
      --error: #f87171;
    }

    body {
      font-family: 'Inter', system-ui, sans-serif;
      background-color: var(--light);
      color: var(--text);
      -webkit-font-smoothing: antialiased;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .dark body {
      background-color: var(--dark);
      color: var(--text);
    }

    .gradient-bg {
      background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%);
    }

    .card-hover {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .card-hover:hover {
      transform: translateY(-4px) scale(1.02);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
      border-color: var(--accent);
    }

    .animate-pulse {
      animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      max-width: 1200px;
      margin: 0 auto;
    }

    .dashboard-layout {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
    }

    @media (min-width: 768px) {
      .dashboard-layout {
        grid-template-columns: 2fr 1fr;
      }
    }


  </style>
</head>
<body class="min-h-screen">
  <!-- Navigation -->
  <nav class="gradient-bg text-white shadow-sm sticky top-0 z-50" role="navigation" aria-label="Main navigation">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
      <a href="index.php" class="text-xl font-bold tracking-tight text-white font-poppins">QuantumBank</a>
      <div class="hidden md:flex items-center space-x-6">
        <a href="dashboard.php" class="hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Dashboard">Dashboard</a>
        <div class="relative group">
          <a href="#" class="hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Accounts">Accounts</a>
          <div class="absolute hidden group-hover:block bg-white text-gray-800 rounded-lg shadow-lg py-2 z-10">
            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Checking</a>
            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Savings</a>
            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Business</a>
          </div>
        </div>
        <a href="payments.php" class="hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Payments">Payments</a>
        <a href="Cards.php" class="hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Cards">Cards</a>
        <a href="#" class="hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Investments">Investments</a>
        <a href="CalC.html" class="hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Calculators">Calculators</a>
        <a href="atm_locator.php" class="hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="ATM Locator">ATM Locator</a>
        <a href="login.php" class="hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Login">Login</a>
        <div id="userNav" class="hidden flex items-center space-x-2">
          <span class="text-white">Welcome, <span id="userName"></span>!</span>
          <button id="logoutBtn" class="px-4 py-2 bg-error text-white rounded-lg font-medium hover:bg-opacity-90 transition focus:ring-2 focus:ring-error focus:ring-offset-2">Logout</button>
        </div>
      </div>
      <button class="md:hidden text-white" aria-label="Toggle Mobile Menu" id="mobileMenuBtn">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>
    </div>
    <div id="mobileMenu" class="hidden md:hidden bg-white text-gray-800 absolute top-0 left-0 w-full h-screen flex flex-col items-center justify-center space-y-4">
      <a href="Dashboard.html" class="text-lg hover:underline">Dashboard</a>
      <a href="#" class="text-lg hover:underline">Accounts</a>
      <a href="payments.php" class="text-lg hover:underline">Payments</a>
      <a href="cards.php" class="text-lg hover:underline">Cards</a>
      <a href="#" class="text-lg hover:underline">Investments</a>
      <a href="#" class="text-lg hover:underline">Calculators</a>
      <a href="atmLocator.php" class="text-lg hover:underline">ATM Locator</a>
      <a href="login.php" class="text-lg hover:underline">Login</a>
      <button id="closeMobileMenu" class="absolute top-4 right-4 text-gray-800 text-2xl">&times;</button>
    </div>
  </nav>

  <!-- Hero Section -->
  <section id="heroSection" class="gradient-bg text-white pt-20 pb-24" role="main" aria-label="Hero section">
    <div class="container mx-auto px-6 flex flex-col md:flex-row items-center gap-8">
      <div class="md:w-1/2 mb-10 md:mb-0 text-center md:text-left">
        <h1 class="text-5xl md:text-6xl font-bold leading-tight mb-4 font-poppins">Banking Evolved for the Digital Age</h1>
        <p class="text-base md:text-lg leading-relaxed mb-8 text-blue-100">Smart financial solutions powered by AI with military-grade security</p>
        <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4 justify-center md:justify-start">
        <a href="open_account.php" class="px-6 py-3 border-2 border-white text-white rounded-lg font-medium hover:bg-white hover:text-accent transition transform hover:scale-105 focus:ring-2 focus:ring-accent focus:ring-offset-2">Open Account</a>
          <a href="login.php" class="px-6 py-3 border-2 border-white text-white rounded-lg font-medium hover:bg-white hover:text-accent transition transform hover:scale-105 focus:ring-2 focus:ring-accent focus:ring-offset-2">Login to Account</a>
        </div>
      </div>
      <div class="md:w-1/2 flex justify-center p-4">
        <img src="Big Shoes - Hero.png" alt="QuantumBank digital banking illustration with futuristic interface" class="w-3/4 md:w-1/2 animate-pulse" loading="lazy">
      </div>
    </div>
    
  </section>

  <!-- QuantumBank Features Showcase -->
  <section class="py-20 bg-gradient-to-br from-indigo-50 via-white to-purple-50 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/5 to-purple-500/5"></div>
    <div class="container mx-auto px-6 relative">
      <div class="text-center mb-16">
        <div class="inline-flex items-center bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-4 py-2 rounded-full text-sm font-medium mb-4 shadow-lg">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
          </svg>
          Premium Banking Features
        </div>
        <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 font-poppins bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
          Experience Banking Like Never Before
        </h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
          Discover the complete suite of QuantumBank services designed to revolutionize your financial journey with cutting-edge technology and unparalleled convenience.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        <!-- Premium Accounts -->
        <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 group">
          <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Premium Accounts</h3>
          <p class="text-gray-600 text-base mb-6 leading-relaxed">High-yield savings accounts with competitive APY rates and unlimited free transfers.</p>
          <div class="flex items-center justify-between">
            <span class="text-3xl font-bold text-indigo-600">4.25% APY</span>
            <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm font-medium">Popular</span>
          </div>
        </div>

        <!-- Instant Transfers -->
        <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 group">
          <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Instant Transfers</h3>
          <p class="text-gray-600 text-base mb-6 leading-relaxed">Lightning-fast money transfers between accounts, domestically and internationally.</p>
          <div class="flex items-center justify-between">
            <span class="text-3xl font-bold text-green-600">Real-time</span>
            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Instant</span>
          </div>
        </div>

        <!-- Loan Services -->
        <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 group">
          <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Smart Loans</h3>
          <p class="text-gray-600 text-base mb-6 leading-relaxed">AI-powered loan approval with competitive rates and flexible repayment options.</p>
          <div class="flex items-center justify-between">
            <span class="text-3xl font-bold text-blue-600">Auto-Approval</span>
            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">Smart</span>
          </div>
        </div>

        <!-- Credit Cards -->
        <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 group">
          <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Premium Cards</h3>
          <p class="text-gray-600 text-base mb-6 leading-relaxed">Reward-rich credit cards with cashback, travel benefits, and zero foreign transaction fees.</p>
          <div class="flex items-center justify-between">
            <span class="text-3xl font-bold text-purple-600">5% Cashback</span>
            <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium">Rewards</span>
          </div>
        </div>

        <!-- Investment Tools -->
        <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 group">
          <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Investment Platform</h3>
          <p class="text-gray-600 text-base mb-6 leading-relaxed">Commission-free trading with advanced analytics and portfolio management tools.</p>
          <div class="flex items-center justify-between">
            <span class="text-3xl font-bold text-orange-600">$0 Fees</span>
            <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-medium">Free</span>
          </div>
        </div>

        <!-- Security Features -->
        <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 group">
          <div class="w-16 h-16 bg-gradient-to-br from-gray-700 to-gray-900 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Bank-Grade Security</h3>
          <p class="text-gray-600 text-base mb-6 leading-relaxed">Military-grade encryption, biometric authentication, and 24/7 fraud monitoring.</p>
          <div class="flex items-center justify-between">
            <span class="text-3xl font-bold text-gray-700">256-bit</span>
            <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">Secure</span>
          </div>
        </div>
      </div>

      <div class="text-center">
        <div class="flex flex-col sm:flex-row justify-center gap-4 mb-8">
          <a href="open_account.php" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Open Your Account
          </a>
          <a href="features.php" class="inline-flex items-center px-8 py-4 border-2 border-indigo-600 text-indigo-600 font-semibold rounded-xl hover:bg-indigo-600 hover:text-white transition-all duration-300 transform hover:scale-105">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Explore All Features
          </a>
        </div>
        <p class="text-gray-500 text-sm">Join over 100,000 satisfied customers banking with QuantumBank</p>
      </div>
    </div>
  </section>

  <!-- Accounts Showcase -->
  <section class="py-20 bg-white">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-4 font-poppins">Smart Account Features</h2>
      <p class="text-center text-base text-gray-600 max-w-2xl mx-auto mb-12 leading-relaxed">Banking products designed to grow with your ambitions.</p>
      <div class="dashboard-grid">
        <!-- Premium Account -->
        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl text-white p-6 card-hover" tabindex="0">
          <div class="flex justify-between items-start mb-6">
            <div>
              <h3 class="text-xl md:text-2xl font-bold">Quantum Premium</h3>
              <p class="text-indigo-100 text-base">Ultimate Banking</p>
            </div>
            <div class="bg-white bg-opacity-20 p-2 rounded-lg transition-transform hover:scale-110">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
              </svg>
            </div>
          </div>
          <div class="mb-6">
            <p class="text-3xl font-bold">4.25% APY</p>
            <p class="text-indigo-100 text-base">High-yield savings</p>
          </div>
          <ul class="space-y-2 mb-6">
            <li class="flex items-start">
              <svg class="h-5 w-5 text-green-300 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-base">Unlimited free transfers</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-green-300 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-base">Priority customer support</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-green-300 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-base">Global ATM fee reimbursement</span>
            </li>
          </ul>
          <button class="w-full px-6 py-3 bg-white text-indigo-600 rounded-lg font-medium hover:bg-indigo-50 transition transform hover:scale-105 focus:ring-2 focus:ring-accent focus:ring-offset-2" onclick="sendNotification('Welcome to Quantum Premium!')">Get Started</button>
        </div>
        <!-- Business Account -->
        <div class="bg-gradient-to-br from-blue-600 to-cyan-500 rounded-2xl text-white p-6 card-hover" tabindex="0">
          <div class="flex justify-between items-start mb-6">
            <div>
              <h3 class="text-xl md:text-2xl font-bold">Business Suite</h3>
              <p class="text-blue-100 text-base">For Entrepreneurs</p>
            </div>
            <div class="bg-white bg-opacity-20 p-2 rounded-lg transition-transform hover:scale-110">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
            </div>
          </div>
          <div class="mb-6">
            <p class="text-3xl font-bold">3.75% APY</p>
            <p class="text-blue-100 text-base">Business banking</p>
          </div>
          <ul class="space-y-2 mb-6">
            <li class="flex items-start">
              <svg class="h-5 w-5 text-cyan-300 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-base">Multi-user access controls</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-cyan-300 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-base">Expense management tools</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-cyan-300 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-base">Integrated payroll services</span>
            </li>
          </ul>
          <button class="w-full px-6 py-3 bg-white text-blue-600 rounded-lg font-medium hover:bg-blue-50 transition transform hover:scale-105 focus:ring-2 focus:ring-accent focus:ring-offset-2">Get Started</button>
        </div>
        <!-- Mobile App CTA -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl text-white p-6 card-hover relative overflow-hidden" tabindex="0">
          <div class="relative z-10">
            <h3 class="text-xl md:text-2xl font-bold mb-2">Banking On The Go</h3>
            <p class="text-base text-gray-300 mb-6 leading-relaxed">Download our award-winning mobile app for iOS and Android.</p>
            <div class="flex space-x-3">
              <button class="px-4 py-2 bg-white text-gray-900 rounded-lg flex items-center hover:bg-opacity-90 transition transform hover:scale-105 focus:ring-2 focus:ring-accent focus:ring-offset-2">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.667-1.48 3.676-2.948 1.156-1.68 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.364-2-.156-3.675 1.091-4.61 1.091zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701"></path>
                </svg>
                App Store
              </button>
              <button class="px-4 py-2 bg-white text-gray-900 rounded-lg flex items-center hover:bg-opacity-90 transition transform hover:scale-105 focus:ring-2 focus:ring-accent focus:ring-offset-2">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M3 18.6V5.4c0-1.2 1-2.2 2.2-2.2h13.6c1.2 0 2.2 1 2.2 2.2v13.1c0 1.2-1 2.2-2.2 2.2H5.2c-1.2.1-2.2-.9-2.2-2.1zm6.9-8.9l-1.7 2.2v2.7h5v-2.3h1.6l2.2-2.8h-4.9V6.1h-1.7v3.6H9.9z"></path>
                </svg>
                Play Store
              </button>
            </div>
          </div>
          <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/7b8e8a03-3282-4643-a728-2a27b1d73860.webp" alt="QuantumBank mobile app screenshot showing account overview" class="absolute bottom-0 right-0 h-40 opacity-20" loading="lazy">
        </div>
      </div>
    </div>
  </section>

  <!-- Security Section -->
  <section class="py-20 bg-gray-900 text-white">
    <div class="container mx-auto px-6">
      <div class="flex flex-col md:flex-row items-center gap-8">
        <div class="md:w-1/2 mb-10 md:mb-0">
          <img src="2.jpg" alt="QuantumBank security shield with encryption and protection layers icon" class="w-full max-w-md mx-auto" loading="lazy">
        </div>
        <div class="md:w-1/2 md:pl-12">
          <h2 class="text-3xl md:text-4xl font-bold mb-6 font-poppins">Quantum-Secure Banking</h2>
          <p class="text-base text-gray-300 mb-8 leading-relaxed">Your security is our top priority. We employ multiple layers of protection to safeguard your assets and personal information.</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 class="font-bold text-lg mb-2 flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm-1 17.9c-4-.5-7.2-3.3-7.8-7.2L7 13v-2c0-.6.4-1 1-1s1 .4 1 1v3c0 .6-.4 1-1 1H5c-.6 0-1-.4-1-1s.4-1 1-1h1l.2-1.4C5.9 7.7 8.6 5 12 5c3.6 0 6.5 2.9 6.5 6.5 0 1.2-.3 2.4-1 3.4l1.4 1.4c.9-1.3 1.6-2.9 1.6-4.8C20 8.2 16.4 4.5 12 4.5c-3.7 0-6.7 3-6.7 6.7H5l3 3 3-3h-1.2C9.8 8.5 12.6 6 16 6c3.3 0 6 2.7 6 6s-2.7 6-6 6c-1.1 0-2.1-.3-3-.8l-1.5 1.5c1.3.8 2.8 1.3 4.5 1.3 4.2 0 7.9-2.6 9.3-6.4.3-.9.5-1.8.5-2.8 0-.6-.1-1.2-.2-1.8-.5 3.9-3.6 7-7.6 7.6L11 19.9z"></path>
                </svg>
                Biometric Authentication
              </h3>
              <p class="text-base text-gray-400 leading-relaxed">Fingerprint, face ID, and voice recognition for secure access.</p>
            </div>
            <div>
              <h3 class="font-bold text-lg mb-2 flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm0 16c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7zm1-11h-2v3H8v2h3v3h2v-3h3v-2h-3V8z"></path>
                </svg>
                Real-Time Alerts
              </h3>
              <p class="text-base text-gray-400 leading-relaxed">Instant notifications for all account activity.</p>
            </div>
            <div>
              <h3 class="font-bold text-lg mb-2 flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"></path>
                </svg>
                FDIC Insurance
              </h3>
              <p class="text-base text-gray-400 leading-relaxed">All deposits insured up to $250,000.</p>
            </div>
            <div>
              <h3 class="font-bold text-lg mb-2 flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 15c1.66 0 3-1.34 3-3V6c0-1.66-1.34-3-3-3S9 4.34 9 6v6c0 1.66 1.34 3 3 3zm5.91-3c-.49 0-.9.36-.98.85C16.52 15.22 14.47 17 12 17s-4.52-1.78-4.93-4.15c-.08-.49-.49-.85-.98-.85-.61 0-1.09.54-1 1.14.49 3 2.89 5.35 5.91 5.78V20c0 .55.45 1 1 1s1-.45 1-1v-2.08c3.02-.43 5.42-2.78 5.91-5.78.1-.6-.39-1.14-1-1.14z"></path>
                </svg>
                End-to-End Encryption
              </h3>
              <p class="text-base text-gray-400 leading-relaxed">Your data is protected in transit and at rest.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials -->
  <section class="py-20 bg-white">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-12 font-poppins">Trusted by Thousands</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-gray-50 p-6 rounded-xl card-hover" tabindex="0">
          <div class="flex items-center mb-4">
            <img src="3.jpg" alt="Sarah Johnson, Marketing Director at TechCorp" class="w-12 h-12 rounded-full mr-4" loading="lazy">
            <div>
              <h4 class="font-bold text-base">Itachi Uchiha</h4>
              <p class="text-sm text-gray-600">Marketing Director</p>
            </div>
          </div>
          <p class="text-base text-gray-700 mb-4 leading-relaxed">"QuantumBank's platform has revolutionized our financial operations with its intuitive interface and powerful tools."</p>
          <div class="flex text-yellow-400">
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
          </div>
        </div>
        <div class="bg-gray-50 p-6 rounded-xl card-hover" tabindex="0">
          <div class="flex items-center mb-4">
            <img src="4.jpg" alt="Madara Uchiha, Freelance Designer" class="w-12 h-12 rounded-full mr-4" loading="lazy">
            <div>
              <h4 class="font-bold text-base">Madara Uchiha</h4>
              <p class="text-sm text-gray-600">Freelance Designer</p>
            </div>
          </div>
          <p class="text-base text-gray-700 mb-4 leading-relaxed">"The AI insights helped me identify spending patterns I never noticed. Saved 15% on unnecessary expenses last month!"</p>
          <div class="flex text-yellow-400">
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
          </div>
        </div>
        <div class="bg-gray-50 p-6 rounded-xl card-hover" tabindex="0">
          <div class="flex items-center mb-4">
            <img src="1.jpg" alt="David Rodriguez, Founder at StartupXYZ" class="w-12 h-12 rounded-full mr-4" loading="lazy">
            <div>
              <h4 class="font-bold text-base">Rovonovo Zoro</h4>
              <p class="text-sm text-gray-600">Startup Founder</p>
            </div>
          </div>
          <p class="text-base text-gray-700 mb-4 leading-relaxed">"QuantumBank's cash flow forecasting has been invaluable for our startup growth, giving us the financial insights we need to scale smartly."</p>
          <div class="flex text-yellow-400">
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Final CTA -->
  <section class="gradient-bg text-white py-20">
    <div class="container mx-auto px-6 text-center">
      <h2 class="text-3xl md:text-4xl font-bold mb-6 font-poppins">Ready for Smarter Banking?</h2>
      <p class="text-base md:text-lg text-blue-100 max-w-2xl mx-auto mb-8 leading-relaxed">Join thousands of customers experiencing the future of finance today.</p>
      <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-6">
        <a href="open_account.php" class="px-6 py-3 border-2 border-white text-white rounded-lg font-medium hover:bg-white hover:text-accent transition transform hover:scale-105 focus:ring-2 focus:ring-accent focus:ring-offset-2">Open Account</a>
        <a href="contact.html" class="px-6 py-3 border-2 border-white text-white rounded-lg font-medium hover:bg-white hover:text-accent transition transform hover:scale-105 focus:ring-2 focus:ring-accent focus:ring-offset-2">Contact Us</a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-dark py-12">
    <div class="container mx-auto px-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <div>
          <div class="flex items-center space-x-3 mb-4">
            
            <span class="text-xl font-bold text-white font-poppins">QuantumBank</span>
          </div>
          <p class="text-base text-black-400 leading-relaxed">Reimagining banking for the digital age with cutting-edge technology and unparalleled customer service.</p>
        </div>
        <div>
          <h3 class="text-lg font-semibold mb-4 text-white">Products</h3>
          <ul class="space-y-2">
            <li><a href="#" class="text-base text-gray-400 hover:text-black transition">Personal Banking</a></li>
            <li><a href="#" class="text-base text-gray-400 hover:text-black transition">Business Banking</a></li>
            <li><a href="loan.php" class="text-base text-gray-400 hover:text-black transition">Loans</a></li>
            <li><a href="#" class="text-base text-gray-400 hover:text-black transition">Credit Cards</a></li>
            <li><a href="#" class="text-base text-gray-400 hover:text-black transition">Investments</a></li>
          </ul>
        </div>
        <div>
          <h3 class="text-lg font-semibold mb-4 text-white">Company</h3>
          <ul class="space-y-2">
            <li><a href="about.html" class="text-base text-gray-400 hover:text-white transition">About Us</a></li>
            <li><a href="#" class="text-base text-gray-400 hover:text-black transition">Careers</a></li>
            <li><a href="#" class="text-base text-gray-400 hover:text-black transition">Press</a></li>
            <li><a href="#" class="text-base text-gray-400 hover:text-black transition">Blog</a></li>
            <li><a href="#" class="text-base text-gray-400 hover:text-black transition">Partners</a></li>
          </ul>
        </div>
        <div>
          <h3 class="text-lg font-semibold mb-4 text-white">Support</h3>
          <ul class="space-y-2">
            <li><a href="contact.html" class="text-base text-black-400 hover:text-black transition">Help Center</a></li>
            <li><a href="contact.html" class="text-base text-gray-400 hover:text-white transition">Contact Us</a></li>
            <li><a href="#" class="text-base text-gray-400 hover:text-white transition">Security</a></li>
            <li><a href="#" class="text-base text-gray-400 hover:text-white transition">Privacy Policy</a></li>
            <li><a href="#" class="text-base text-gray-400 hover:text-white transition">Terms of Service</a></li>
          </ul>
        </div>
      </div>
      <div class="pt-8 border-t border-gray-800 flex flex-col md:flex-row justify-between items-center">
        <p class="text-base text-gray-400 text-center md:text-left">© 2023 QuantumBank. All rights reserved.</p>
        <button id="darkModeToggle" class="text-gray-400 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded" aria-label="Toggle Dark Mode">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
          </svg>
        </button>
      </div>
    </div>
  </footer>

  <!-- Mobile Navigation -->
  <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white shadow-md z-50">
    <div class="flex justify-around items-center py-3">
      <a href="index.php" class="flex flex-col items-center text-primary p-4" aria-label="Home Navigation">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
        </svg>
        <span class="text-xs mt-1">Home</span>
      </a>
      <a href="payments.php" class="flex flex-col items-center text-gray-800 p-4" aria-label="Transfer Navigation">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
        </svg>
        <span class="text-xs mt-1">Transfer</span>
      </a>
      <a href="Cards.html" class="flex flex-col items-center text-gray-500 p-4" aria-label="Cards Navigation">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
        </svg>
        <span class="text-xs mt-1">Cards</span>
      </a>
      <a href="#" class="flex flex-col items-center text-gray-500 p-4" aria-label="Account Navigation">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
        <span class="text-xs mt-1">Account</span>
      </a>
    </div>
  </div>

  <!-- Transfer Modal -->
  <div id="transferModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50" aria-live="polite">
    <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full relative">
      <button id="closeTransferModal" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 text-xl focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2" aria-label="Close Transfer Modal">&times;</button>
      <h3 class="text-2xl font-bold text-gray-800 mb-6 font-poppins">Make a Transfer</h3>
      <form id="transferForm" class="space-y-4">
        <div>
          <label for="fromAccount" class="block text-sm font-medium text-gray-700">From Account</label>
          <select id="fromAccount" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent sm:text-sm" aria-describedby="fromAccount-error">
            <!-- Options populated by JS -->
          </select>
          <p id="fromAccount-error" class="hidden text-error text-sm mt-1">Please select a valid account.</p>
        </div>
        <div>
          <label for="toAccount" class="block text-sm font-medium text-gray-700">To Account</label>
          <select id="toAccount" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent sm:text-sm" aria-describedby="toAccount-error">
            <!-- Options populated by JS -->
          </select>
          <p id="toAccount-error" class="hidden text-error text-sm mt-1">Cannot transfer to the same account.</p>
        </div>
        <div>
          <label for="transferAmount" class="block text-sm font-medium text-gray-700">Amount</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
            <input type="number" id="transferAmount" min="0.01" step="0.01" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 pl-8 pr-3 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent sm:text-sm" aria-describedby="amount-error">
          </div>
          <p id="amount-error" class="hidden text-error text-sm mt-1">Please enter a valid amount.</p>
        </div>
        <div>
          <label for="transferDescription" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
          <textarea id="transferDescription" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent sm:text-sm"></textarea>
        </div>
        <div class="flex justify-end space-x-3">
          <button type="button" id="cancelTransfer" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-accent focus:ring-offset-2">Cancel</button>
          <button type="submit" id="confirmTransfer" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-primary-dark transition focus:ring-2 focus:ring-accent focus:ring-offset-2 relative">
            <span id="confirmText" class="inline">Confirm Transfer</span>
            <svg id="loadingSpinner" class="hidden absolute w-5 h-5 text-white animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    // Mock user data
    const bankData = {
      currentUser: {
        name: "Alex Johnson",
        accounts: [
          { id: "acc1", type: "Premium Checking", number: "•••• 5678", balance: 8450.23, currency: "USD" },
          { id: "acc2", type: "High-Yield Savings", number: "•••• 9012", balance: 32500.00, currency: "USD" },
          { id: "acc3", type: "Business Checking", number: "•••• 3456", balance: 15200.50, currency: "USD" }
        ],
        transactions: [
          { id: "tx1", date: "2023-06-15", description: "Grocery Store", amount: -87.34, category: "Food" },
          { id: "tx2", date: "2023-06-14", description: "Salary Deposit", amount: 4200.00, category: "Income" },
          { id: "tx3", date: "2023-06-12", description: "Electric Bill", amount: -145.67, category: "Utilities" },
          { id: "tx4", date: "2023-06-11", description: "Online Shopping", amount: -230.10, category: "Shopping" },
          { id: "tx5", date: "2023-06-10", description: "Restaurant", amount: -55.00, category: "Food" }
        ],
        spendingCategories: {
          "Food": 142.34,
          "Utilities": 145.67,
          "Shopping": 230.10,
          "Transport": 80.00,
          "Entertainment": 120.00
        }
      }
    };

    function sendNotification(message) {
      alert(message); // Replace with a proper notification system in production
    }


    function populateDashboard() {
      const accountsContainer = document.getElementById('accountsContainer');
      if (accountsContainer) {
        accountsContainer.innerHTML = bankData.currentUser.accounts.map(account => `
          <div class="bg-white rounded-xl p-6 card-hover" tabindex="0">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h3 class="text-lg font-bold text-gray-800">${account.type}</h3>
                <p class="text-gray-500 text-sm">${account.number}</p>
              </div>
              <div class="bg-gray-100 p-2 rounded-lg transition-transform hover:scale-110">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
            </div>
            <div class="border-t border-gray-200 pt-4">
              <p class="text-3xl font-bold text-gray-800">$${account.balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
              <p class="text-gray-500 text-sm">Available balance</p>
              <div class="flex justify-between mt-4">
                <button class="text-indigo-600 hover:underline text-sm focus:ring-2 focus:ring-accent focus:ring-offset-2">Details</button>
                <button class="text-indigo-600 hover:underline text-sm focus:ring-2 focus:ring-accent focus:ring-offset-2">Transfer</button>
              </div>
            </div>
          </div>
        `).join('');
      }

      const transactionsTable = document.getElementById('transactionsTable');
      if (transactionsTable) {
        transactionsTable.innerHTML = bankData.currentUser.transactions.map(tx => `
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${tx.date}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">${tx.description}</div>
              <div class="text-xs text-gray-500">${tx.category}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm ${tx.amount >= 0 ? 'text-green-600' : 'text-error'}">
              ${tx.amount >= 0 ? '+' : ''}${tx.amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Completed</span>
            </td>
          </tr>
        `).join('');
      }

      renderSpendingChart();
    }

    let spendingChartInstance = null;

    function renderSpendingChart() {
      const ctx = document.getElementById('spendingChart')?.getContext('2d');
      if (!ctx) return;
      const categories = Object.keys(bankData.currentUser.spendingCategories);
      const amounts = Object.values(bankData.currentUser.spendingCategories);

      if (spendingChartInstance) {
        spendingChartInstance.destroy();
      }

      spendingChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: categories,
          datasets: [{
            data: amounts,
            backgroundColor: ['#7c3aed', '#4f46e5', '#10b981', '#f59e0b', '#ef4444'],
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'top' },
            title: { display: true, text: 'Spending by Category' }
          }
        }
      });
    }

    // Mobile menu toggle
    document.getElementById('mobileMenuBtn').addEventListener('click', () => {
      document.getElementById('mobileMenu').classList.toggle('hidden');
    });
    document.getElementById('closeMobileMenu').addEventListener('click', () => {
      document.getElementById('mobileMenu').classList.add('hidden');
    });

    // Transfer modal functionality
    const transferBtn = document.getElementById('transferBtn');
    const transferModal = document.getElementById('transferModal');
    const closeTransferModal = document.getElementById('closeTransferModal');
    const cancelTransfer = document.getElementById('cancelTransfer');
    const confirmTransfer = document.getElementById('confirmTransfer');
    const confirmText = document.getElementById('confirmText');
    const loadingSpinner = document.getElementById('loadingSpinner');

    if (transferBtn) {
      transferBtn.addEventListener('click', () => {
        transferModal.classList.remove('hidden');
        const fromAccountSelect = document.getElementById('fromAccount');
        const toAccountSelect = document.getElementById('toAccount');
        fromAccountSelect.innerHTML = bankData.currentUser.accounts.map(account => `
          <option value="${account.id}">${account.type} (${account.number})</option>
        `).join('');
        toAccountSelect.innerHTML = bankData.currentUser.accounts.map(account => `
          <option value="${account.id}">${account.type} (${account.number})</option>
        `).join('');
      });
    }

    closeTransferModal.addEventListener('click', () => {
      transferModal.classList.add('hidden');
    });

    cancelTransfer.addEventListener('click', () => {
      transferModal.classList.add('hidden');
    });

    document.getElementById('transferForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const fromAccount = document.getElementById('fromAccount').value;
      const toAccount = document.getElementById('toAccount').value;
      const amount = parseFloat(document.getElementById('transferAmount').value);
      const description = document.getElementById('transferDescription').value;

      if (fromAccount === toAccount) {
        document.getElementById('toAccount-error').classList.remove('hidden');
        return;
      } else {
        document.getElementById('toAccount-error').classList.add('hidden');
      }

      if (isNaN(amount) || amount <= 0) {
        document.getElementById('amount-error').classList.remove('hidden');
        return;
      } else {
        document.getElementById('amount-error').classList.add('hidden');
      }

      confirmText.classList.add('hidden');
      loadingSpinner.classList.remove('hidden');
      setTimeout(() => {
        console.log(`Transferring $${amount} from ${fromAccount} to ${toAccount}. Description: ${description}`);
        sendNotification(`Transfer of $${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} processed successfully! (Backend integration needed)`);
        const fromAcc = bankData.currentUser.accounts.find(acc => acc.id === fromAccount);
        const toAcc = bankData.currentUser.accounts.find(acc => acc.id === toAccount);
        if (fromAcc && toAcc) {
          fromAcc.balance -= amount;
          toAcc.balance += amount;
          bankData.currentUser.transactions.unshift({
            id: `tx${Date.now()}`,
            date: new Date().toISOString().slice(0, 10),
            description: `Transfer to ${toAcc.type}`,
            amount: -amount,
            category: "Transfer"
          });
          bankData.currentUser.transactions.unshift({
            id: `tx${Date.now() + 1}`,
            date: new Date().toISOString().slice(0, 10),
            description: `Transfer from ${fromAcc.type}`,
            amount: amount,
            category: "Transfer"
          });
          populateDashboard();
        }
        confirmText.classList.remove('hidden');
        loadingSpinner.classList.add('hidden');
        transferModal.classList.add('hidden');
        e.target.reset();
      }, 1000);
    });

    // Logout handler
    document.getElementById('logoutBtn').addEventListener('click', function() {
      window.location.href = 'logout.php';
    });

    // Check session on page load
    document.addEventListener('DOMContentLoaded', () => {
      fetch('../includes/check_session.php')
        .then(response => response.json())
        .then(data => {
          if (data.logged_in) {
            document.getElementById('userNav').classList.remove('hidden');
            document.getElementById('userName').textContent = data.username;
            // Hide login link if needed
            const loginLink = document.querySelector('a[href="login.php"]');
            if (loginLink) {
              loginLink.style.display = 'none';
            }
          } else {
            document.getElementById('userNav').classList.add('hidden');
            const loginLink = document.querySelector('a[href="login.php"]');
            if (loginLink) {
              loginLink.style.display = 'inline';
            }
          }
        })
        .catch(error => {
          console.error('Error checking session:', error);
          // Default to logged out
          document.getElementById('userNav').classList.add('hidden');
        });

      // Dark mode toggle
      const darkModeToggle = document.getElementById('darkModeToggle');
      const html = document.documentElement;

      // Load saved theme
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'dark') {
        html.classList.add('dark');
      }

      darkModeToggle.addEventListener('click', () => {
        html.classList.toggle('dark');
        const isDark = html.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
      });
    });
  </script>
</body>
</html>