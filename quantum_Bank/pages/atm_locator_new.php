<?php include '../includes/header.php'; ?>

<!-- ATM Locator Hero Section -->
<section class="gradient-bg text-white pt-16 pb-12">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold mb-4">ATM & Branch Locator</h1>
        <p class="text-xl text-blue-100">Find QuantumBank ATMs and branches near you with our interactive locator.</p>
    </div>
</section>

<!-- Main Locator Content -->
<div class="container mx-auto px-4 py-8">

    <!-- Search Section -->
    <section class="mb-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl p-6 shadow-lg">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Find Locations Near You</h2>

                <form id="locationSearchForm" class="mb-6">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">Enter City, State, or Zip Code</label>
                            <input type="text" id="location" name="location" placeholder="e.g., New York, NY or 10001"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition font-medium">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                Search
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Filters -->
                <div class="flex flex-wrap gap-4 mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="branches" name="branches" checked class="mr-2">
                        <label for="branches" class="text-sm font-medium text-gray-700">Branches</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="atms" name="atms" checked class="mr-2">
                        <label for="atms" class="text-sm font-medium text-gray-700">ATMs</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="open24" name="open24" class="mr-2">
                        <label for="open24" class="text-sm font-medium text-gray-700">24/7 Access</label>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map and Results -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Map Section -->
        <section class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="h-96 bg-gray-100 relative">
                    <!-- Placeholder for Google Maps -->
                    <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
                        <div class="text-center">
                            <svg class="w-16 h-16 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                            <p class="text-gray-600 font-medium">Interactive Map</p>
                            <p class="text-sm text-gray-500">Google Maps integration coming soon</p>
                        </div>
                    </div>
                    <!-- Sample location markers (static for now) -->
                    <div class="absolute top-1/4 left-1/3 w-4 h-4 bg-blue-600 rounded-full border-2 border-white shadow-lg"></div>
                    <div class="absolute top-1/2 right-1/4 w-4 h-4 bg-green-600 rounded-full border-2 border-white shadow-lg"></div>
                    <div class="absolute bottom-1/3 left-1/2 w-4 h-4 bg-purple-600 rounded-full border-2 border-white shadow-lg"></div>
                </div>
            </div>
        </section>

        <!-- Results Section -->
        <section>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Nearby Locations</h3>

                <!-- Sample Results -->
                <div id="locationResults" class="space-y-4">
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <h4 class="font-semibold text-gray-800">Main Street Branch</h4>
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">Branch</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2">123 Main Street, Downtown</p>
                        <div class="flex items-center text-sm text-gray-500 mb-2">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Open until 5:00 PM
                        </div>
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            0.3 miles away
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <h4 class="font-semibold text-gray-800">Central ATM</h4>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">ATM</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2">456 Central Plaza, Midtown</p>
                        <div class="flex items-center text-sm text-gray-500 mb-2">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Available 24/7
                        </div>
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            0.7 miles away
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <h4 class="font-semibold text-gray-800">Express ATM</h4>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">ATM</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2">789 Express Lane, Uptown</p>
                        <div class="flex items-center text-sm text-gray-500 mb-2">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Available 24/7
                        </div>
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            1.2 miles away
                        </div>
                    </div>
                </div>

                <!-- No Results State -->
                <div id="noResults" class="hidden text-center py-8">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <p class="text-gray-500">No locations found matching your criteria.</p>
                    <p class="text-sm text-gray-400 mt-2">Try adjusting your search or filters.</p>
                </div>
            </div>
        </section>
    </div>

    <!-- Features Section -->
    <section class="mt-16">
        <h2 class="text-3xl font-bold text-gray-800 mb-12 text-center">Banking Made Easy</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white rounded-xl p-6 shadow-lg text-center card-hover">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-4">Cash Withdrawals</h3>
                <p class="text-gray-600">Quick and secure cash withdrawals from any QuantumBank ATM.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-lg text-center card-hover">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-4">Deposits</h3>
                <p class="text-gray-600">Deposit checks and cash at our branches or through ATMs.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-lg text-center card-hover">
                <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-4">24/7 Support</h3>
                <p class="text-gray-600">Get help anytime with our round-the-clock customer support.</p>
            </div>
        </div>
    </section>
</div>

<script>
// Location search functionality (placeholder)
document.getElementById('locationSearchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const location = document.getElementById('location').value;
    // In a real implementation, this would call a geocoding API
    console.log('Searching for locations near:', location);
    // For now, just show the existing results
});
</script>

<?php include '../includes/footer.php'; ?>
