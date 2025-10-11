<?php include '../includes/db_connect.php'; include '../includes/session.php'; requireLogin(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ATM & Branch Locator - QuantumBank</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Custom Tailwind extensions or overrides */
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
    .map-placeholder {
      width: 100%;
      height: 300px; /* Reduced height for elegance */
      background-color: #e5e7eb; /* slate-200 */
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      color: #64748b; /* slate-500 */
      border-radius: 0.75rem;
      margin-bottom: 2rem;
    }
    /* Responsive adjustments */
    @media (min-width: 768px) {
      .container {
        max-width: 1200px;
      }
    }
  </style>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">

  <!-- Header Section -->
  <header class="gradient-bg text-white p-6 sticky top-0 z-50 shadow-md">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <a href="Bank.php.html" class="text-2xl md:text-3xl font-bold hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">QuantumBank</a>
      <nav class="hidden md:flex space-x-6 text-md font-medium">
        <a href="Bank.php.html" class="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Dashboard</a>
        <a href="#" class="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Accounts</a>
        <a href="Payments.html" class="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Payments</a>
        <a href="cards.php" class="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Cards</a>
        <a href="#" class="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Invest</a>
        <a href="calculators.html" class="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Calculators</a>
        <a href="atm_locator.php" class="hover:underline font-semibold focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">ATM Locator</a>
        <a href="about.html" class="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">About Us</a>
        <a href="contact.html" class="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Contact Us</a>
        <a href="login.php" class="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Login</a>
      </nav>
      <button id="mobileMenuBtn" class="md:hidden focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>
    <!-- Mobile Menu -->
    <div id="mobileMenu" class="hidden md:hidden bg-blue-700 text-white p-4 space-y-4">
      <a href="Bank.php.html" class="block hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Dashboard</a>
      <a href="#" class="block hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Accounts</a>
      <a href="Payments.html" class="block hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Payments</a>
      <a href="Cards.html" class="block hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Cards</a>
      <a href="#" class="block hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Invest</a>
      <a href="calculators.html" class="block hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Calculators</a>
      <a href="#" class="block hover:underline font-semibold focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">ATM Locator</a>
      <a href="about.html" class="block hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">About Us</a>
      <a href="contact.html" class="block hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Contact Us</a>
      <a href="Login.html" class="block hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Login</a>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container mx-auto px-4 py-8 md:py-12">
    <!-- Hero Section -->
    <section class="mb-8 md:mb-12 text-center">
      <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">ATM & Branch Locator</h1>
      <p class="text-gray-600 text-base md:text-lg">Find QuantumBank ATMs and branches near you with ease.</p>
    </section>

    <!-- Map Placeholder -->
    <section class="mb-8 md:mb-12">
      <div class="map-placeholder">Interactive Map (Google Maps API Placeholder)</div>
    </section>

    <!-- Search and Filter -->
    <section class="mb-8 md:mb-12">
      <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">Find a Location Near You</h2>
      <div class="bg-white rounded-xl p-6 shadow-md">
        <form id="locationSearchForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="searchLocation" class="block text-sm font-medium text-gray-700">Enter City or Zip Code</label>
            <input type="text" id="searchLocation" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., New York, 10001">
            <p id="searchError" class="text-red-500 text-xs mt-1 hidden">Please enter a city or zip code.</p>
          </div>
          <div>
            <h3 class="text-sm font-medium text-gray-700 mb-2">Filter by Type</h3>
            <label class="inline-flex items-center mr-4">
              <input type="checkbox" class="form-checkbox text-blue-600" value="branch" checked onchange="filterLocations()">
              <span class="ml-2 text-gray-700 text-sm">Branches</span>
            </label>
            <label class="inline-flex items-center">
              <input type="checkbox" class="form-checkbox text-blue-600" value="atm" checked onchange="filterLocations()">
              <span class="ml-2 text-gray-700 text-sm">ATMs</span>
            </label>
          </div>
          <div class="md:col-span-2">
            <button type="submit" class="w-full md:w-auto px-6 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 transition">Search Locations</button>
          </div>
        </form>
      </div>
    </section>

    <!-- Locations List -->
    <section>
      <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">Locations Found</h2>
      <div id="locationsList" class="space-y-4"></div>
      <p id="noLocationsFound" class="text-gray-600 text-sm mt-4 hidden">No locations found matching your criteria.</p>
    </section>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-800 text-white py-6">
    <div class="container mx-auto px-4 text-center">
      <p>&copy; 2025 QuantumBank. All rights reserved.</p>
    </div>
  </footer>

  <!-- Modal for Location Details -->
  <div id="locationModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-xl max-w-md w-full">
      <h3 class="text-xl font-bold text-gray-800 mb-4" id="modalTitle"></h3>
      <p class="text-gray-600 mb-2" id="modalAddress"></p>
      <p class="text-gray-600 mb-4" id="modalDetails"></p>
      <a id="modalDirections" href="#" target="_blank" class="text-blue-600 hover:underline text-sm mb-4 inline-block">Get Directions</a>
      <button id="closeModal" class="w-full bg-blue-600 text-white py-2 rounded-lg font-medium hover:bg-blue-700 transition">Close</button>
    </div>
  </div>

  <script>

    // Mock Data
    const allLocations = [$stmt = $pdo-prepare("SELECT * FROM locations");
$stmt-execute();
$locations = $stmt-fetchAll();

    // { id: 1, name: "Main Branch - Downtown", address: "123 Main St, Cityville, CA 90210", type: "branch", city: "Cityville", zip: "90210", hours: "Mon-Fri: 9 AM - 5 PM" },
   //   { id: 2, name: "ATM - Central Plaza", address: "456 Oak Ave, Cityville, CA 90210", type: "atm", city: "Cityville", zip: "90210", access: "24/7" },
     // { id: 3, name: "Northside Branch", address: "789 Pine Ln, Northtown, NY 10001", type: "branch", city: "Northtown", zip: "10001", hours: "Mon-Sat: 9 AM - 4 PM" },
     // { id: 4, name: "ATM - Airport Terminal", address: "100 Airport Blvd, Airville, TX 75001", type: "atm", city: "Airville", zip: "75001", access: "24/7" },
     // { id: 5, name: "Southside Branch", address: "101 River Rd, Southville, FL 33101", type: "branch", city: "Southville", zip: "33101", hours: "Mon-Fri: 10 AM - 6 PM" },

    ];

    // Render Locations
    function renderLocations(locationsToDisplay) {
      const locationsList = document.getElementById('locationsList');
      const noLocationsFound = document.getElementById('noLocationsFound');
      locationsList.innerHTML = '';

      if (locationsToDisplay.length === 0) {
        noLocationsFound.classList.remove('hidden');
        return;
      } else {
        noLocationsFound.classList.add('hidden');
      }

      locationsToDisplay.forEach(location => {
        const card = document.createElement('div');
        card.className = 'bg-white p-4 md:p-6 rounded-xl shadow-md card-hover cursor-pointer';
        card.innerHTML = `
          <div class="flex justify-between items-start">
            <div>
              <h3 class="text-lg font-semibold text-blue-600">${location.name} (${location.type.toUpperCase()})</h3>
              <p class="text-gray-600 text-sm">${location.address}</p>
              <p class="text-gray-600 text-sm">${location.type === 'branch' ? `Hours: ${location.hours}` : `Access: ${location.access}`}</p>
            </div>
            <button class="text-blue-600 hover:underline text-sm" onclick="showLocationModal(${location.id})">Details</button>
          </div>
        `;
        locationsList.appendChild(card);
      });
    }

    // Show Location Modal
    function showLocationModal(locationId) {
      const location = allLocations.find(loc => loc.id === locationId);
      if (!location) return;

      const modal = document.getElementById('locationModal');
      const modalTitle = document.getElementById('modalTitle');
      const modalAddress = document.getElementById('modalAddress');
      const modalDetails = document.getElementById('modalDetails');
      const modalDirections = document.getElementById('modalDirections');

      modalTitle.textContent = location.name;
      modalAddress.textContent = location.address;
      modalDetails.textContent = location.type === 'branch' ? `Hours: ${location.hours}` : `Access: ${location.access}`;
      modalDirections.href = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(location.address)}`;
      modal.classList.remove('hidden');
    }

    // Close Modal
    document.getElementById('closeModal').addEventListener('click', () => {
      document.getElementById('locationModal').classList.add('hidden');
    });

    // Search Locations
    function searchLocations(event) {
      event.preventDefault();
      const searchTerm = document.getElementById('searchLocation').value.toLowerCase();
      const searchError = document.getElementById('searchError');
      const filteredTypes = Array.from(document.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);

      searchError.classList.add('hidden');

      if (searchTerm.trim() === '') {
        searchError.classList.remove('hidden');
        return;
      }

      const results = allLocations.filter(location => {
        const matchesSearch = location.city.toLowerCase().includes(searchTerm) || location.zip.includes(searchTerm);
        const matchesType = filteredTypes.includes(location.type);
        return matchesSearch && matchesType;
      });
      renderLocations(results);
    }

    // Filter Locations
    function filterLocations() {
      searchLocations(new Event('submit')); // Trigger search with current input
    }

    // Form Submission
    document.getElementById('locationSearchForm').addEventListener('submit', searchLocations);

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
      renderLocations(allLocations);
      // Mobile menu toggle
      document.getElementById('mobileMenuBtn').addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.toggle('hidden');
      });
    });
  </script>

</body>
</html>