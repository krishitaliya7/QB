<?php
include '../includes/session.php';
?>
<!DOCTYPE html>
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

    body {
      font-family: 'Inter', system-ui, sans-serif;
      background-color: var(--light);
      -webkit-font-smoothing: antialiased;
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

    @media (prefers-color-scheme: dark) {
      body {
        background-color: var(--dark);
        color: #e2e8f0;
      }
      .gradient-bg {
        background: linear-gradient(135deg, #3730a3 0%, #6b21a8 100%);
      }
      .bg-white {
        background-color: #1e293b;
      }
      .text-gray-800 {
        color: #e2e8f0;
      }
      .text-gray-600 {
        color: #94a3b8;
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
      <a href="Payments.php" class="text-lg hover:underline">Payments</a>
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

  <!-- Features Grid -->
  <section class="py-20 bg-white">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-4 font-poppins">Why QuantumBank?</h2>
      <p class="text-center text-base text-gray-600 max-w-2xl mx-auto mb-12 leading-relaxed">Cutting-edge banking technology for security, speed, and smart finance.</p>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-gray-50 rounded-xl p-6 card-hover" tabindex="0">
          <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4 transition-transform hover:scale-110">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
          </div>
          <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-2">Military-Grade Security</h3>
          <p class="text-base text-gray-600 leading-relaxed">256-bit encryption, biometric authentication, and real-time fraud monitoring keep your assets safe.</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-6 card-hover" tabindex="0">
          <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4 transition-transform hover:scale-110">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
          </div>
          <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-2">Lightning-Fast Transactions</h3>
          <p class="text-base text-gray-600 leading-relaxed">Instant transfers between accounts, external banks, and international currencies with no hidden fees.</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-6 card-hover" tabindex="0">
          <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4 transition-transform hover:scale-110">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
          </div>
          <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-2">AI Financial Insights</h3>
          <p class="text-base text-gray-600 leading-relaxed">Smart algorithms analyze spending patterns and provide personalized savings recommendations.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Interactive Dashboard Demo -->
  <section class="py-20 bg-gray-50">
    <div class="container mx-auto px-6">
      <div class="flex flex-col md:flex-row items-center gap-8">
        <div class="md:w-1/2 mb-10 md:mb-0">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4 font-poppins">Your Financial Command Center</h2>
          <p class="text-base text-gray-600 mb-6 leading-relaxed">Our interactive dashboard gives you complete visibility and control over your finances with real-time data visualization.</p>
          <ul class="space-y-3">
            <li class="flex items-start">
              <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-base">Personalized spending analytics</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-base">AI-powered cash flow predictions</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-base">Customizable financial goals</span>
            </li>
          </ul>
        </div>
        <div class="md:w-1/2 bg-white rounded-xl shadow-xl overflow-hidden">
          <div class="p-1 bg-gray-200 flex">
            <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
            <div class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></div>
            <div class="w-3 h-3 rounded-full bg-green-500"></div>
          </div>
          <img src="https://storage.googleapis.com/quantum-bank-assets/dashboard-preview.webp" alt="QuantumBank financial dashboard with spending analytics and account overview" class="w-full" loading="lazy">
        </div>
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
          Premium Banking Features
        </div>
        <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 font-poppins bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
          Experience Banking Like Never Before
        </h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
          Discover the complete suite of QuantumBank services designed to revolutionize your financial journey with cutting-edge technology and unparalleled convenience.
        </p>
      </div>

