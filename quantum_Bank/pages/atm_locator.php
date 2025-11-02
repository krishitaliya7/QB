<?php include '../includes/db_connect.php'; include '../includes/session.php'; requireLogin(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ATM & Branch Locator - QuantumBank</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Google Maps API - Replace YOUR_API_KEY with your actual Google Maps API key -->
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places&callback=initMap"></script>
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
    #map {
      width: 100%;
      height: 500px;
      border-radius: 0.75rem;
      margin-bottom: 2rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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

    <!-- Google Map -->
    <section class="mb-8 md:mb-12">
      <div id="map"></div>
      <div id="mapError" class="hidden bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg mt-4">
        <p class="font-semibold">⚠️ Map Loading Issue</p>
        <p class="text-sm mt-1">Please ensure you have added your Google Maps API key in the page source. The map requires a valid API key to function.</p>
      </div>
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
    // Google Maps variables
    let map;
    let geocoder;
    let markers = [];
    let infoWindow;

    // Fetch locations from PHP
    const allLocations = <?php
    $stmt = $conn->prepare("SELECT * FROM locations");
    $stmt->execute();
    $result = $stmt->get_result();
    $locations = [];
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
    echo json_encode($locations);
    ?>;

    // Initialize Google Map
    function initMap() {
      try {
        // Default center (can be set to user's location or a default city)
        const defaultCenter = { lat: 40.7128, lng: -74.0060 }; // New York City coordinates

      // Initialize map
      map = new google.maps.Map(document.getElementById('map'), {
        zoom: 12,
        center: defaultCenter,
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true,
        styles: [
          {
            featureType: 'poi',
            elementType: 'labels',
            stylers: [{ visibility: 'on' }]
          }
        ]
      });

      geocoder = new google.maps.Geocoder();
      infoWindow = new google.maps.InfoWindow();

      // Try to get user's current location
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            const userLocation = {
              lat: position.coords.latitude,
              lng: position.coords.longitude
            };
            map.setCenter(userLocation);
            map.setZoom(13);
          },
          () => {
            console.log('Geolocation permission denied or unavailable');
          }
        );
      }

      // Load all locations on map
      loadLocationsOnMap(allLocations);
      } catch (error) {
        console.error('Error initializing map:', error);
        document.getElementById('mapError').classList.remove('hidden');
      }
    }

    // Error handler if Google Maps API fails to load
    window.gm_authFailure = function() {
      document.getElementById('mapError').classList.remove('hidden');
      document.getElementById('mapError').innerHTML = `
        <p class="font-semibold">⚠️ Google Maps API Error</p>
        <p class="text-sm mt-1">Invalid or missing API key. Please check your Google Maps API key configuration.</p>
      `;
    };

    // Add markers to map
    function loadLocationsOnMap(locations) {
      // Clear existing markers
      clearMarkers();

      if (locations.length === 0) {
        return;
      }

      const bounds = new google.maps.LatLngBounds();

      locations.forEach(location => {
        // Geocode address to get coordinates
        geocoder.geocode({ address: location.address }, (results, status) => {
          if (status === 'OK' && results[0]) {
            const position = results[0].geometry.location;
            
            // Choose icon based on type
            const icon = location.type === 'branch' 
              ? {
                  path: google.maps.SymbolPath.CIRCLE,
                  fillColor: '#3b82f6',
                  fillOpacity: 1,
                  strokeColor: '#ffffff',
                  strokeWeight: 2,
                  scale: 10
                }
              : {
                  path: google.maps.SymbolPath.CIRCLE,
                  fillColor: '#10b981',
                  fillOpacity: 1,
                  strokeColor: '#ffffff',
                  strokeWeight: 2,
                  scale: 8
                };

            // Create marker
            const marker = new google.maps.Marker({
              position: position,
              map: map,
              title: location.name,
              icon: icon,
              animation: google.maps.Animation.DROP
            });

            // Create info window content
            const infoContent = `
              <div class="p-2">
                <h3 class="font-bold text-lg text-blue-600 mb-1">${location.name}</h3>
                <p class="text-sm text-gray-700 mb-2">${location.type.toUpperCase()}</p>
                <p class="text-sm text-gray-600 mb-2">${location.address}</p>
                ${location.type === 'branch' 
                  ? `<p class="text-sm text-gray-600 mb-2">Hours: ${location.hours || 'N/A'}</p>`
                  : `<p class="text-sm text-gray-600 mb-2">Access: ${location.access || '24/7'}</p>`
                }
                <a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(location.address)}" 
                   target="_blank" 
                   class="text-blue-600 hover:underline text-sm">
                  Get Directions
                </a>
              </div>
            `;

            // Add click listener to marker
            marker.addListener('click', () => {
              infoWindow.setContent(infoContent);
              infoWindow.open(map, marker);
              showLocationModal(location.id);
            });

            markers.push(marker);
            bounds.extend(position);

            // Fit map to show all markers
            if (markers.length === locations.length) {
              map.fitBounds(bounds);
              // If only one marker, zoom in more
              if (markers.length === 1) {
                map.setZoom(15);
              }
            }
          } else {
            console.error('Geocode was not successful for: ' + location.address);
          }
        });
      });
    }

    // Clear all markers
    function clearMarkers() {
      markers.forEach(marker => {
        marker.setMap(null);
      });
      markers = [];
      if (infoWindow) {
        infoWindow.close();
      }
    }

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
        const matchesSearch = location.city.toLowerCase().includes(searchTerm) || location.zip.includes(searchTerm) || location.address.toLowerCase().includes(searchTerm);
        const matchesType = filteredTypes.includes(location.type);
        return matchesSearch && matchesType;
      });
      renderLocations(results);
      
      // Update map with filtered results
      if (typeof map !== 'undefined' && map) {
        loadLocationsOnMap(results);
      } else {
        // If map not loaded yet, try geocoding the search term and centering map
        if (searchTerm.trim() !== '') {
          if (typeof geocoder !== 'undefined' && geocoder) {
            geocoder.geocode({ address: searchTerm }, (results, status) => {
              if (status === 'OK' && results[0] && typeof map !== 'undefined' && map) {
                map.setCenter(results[0].geometry.location);
                map.setZoom(13);
              }
            });
          }
        }
      }
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