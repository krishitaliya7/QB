<!-- Features Grid -->
<section class="py-10 bg-gradient-to-b from-white to-gray-50">
  <div class="container mx-auto px-6">
    <div class="rounded-3xl shadow-xl border border-gray-200 bg-white p-8">
      
      <!-- Title -->
      <h2 class="text-4xl font-extrabold text-center text-gray-900 mb-4">
        Why Choose <span class="text-indigo-600">QuantumBank?</span>
      </h2>
      <p class="text-center text-gray-600 max-w-2xl mx-auto mb-8 text-lg">
        Cutting-edge banking technology for unmatched security, lightning speed, and smarter financial decisions.
      </p>

      <!-- Features Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-10 feature-grid">
        
        <div class="feature-card bg-gray-50 rounded-2xl shadow-sm p-6 text-center">
          <div class="icon-wrapper w-16 h-16 mx-auto bg-indigo-100 rounded-2xl flex items-center justify-center mb-5">
            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-3">Fraud Alert System</h3>
          <p class="text-gray-600 leading-relaxed">Stay safe with real-time fraud detection, instant alerts, and 24/7 monitoring.</p>
        </div>

        <div class="feature-card bg-gray-50 rounded-2xl shadow-sm p-6 text-center">
          <div class="icon-wrapper w-16 h-16 mx-auto bg-green-100 rounded-2xl flex items-center justify-center mb-5">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-3">Lightning-Fast Transactions</h3>
          <p class="text-gray-600 leading-relaxed">Instant transfers between accounts, banks, and across borders — with no hidden fees.</p>
        </div>

        <div class="feature-card bg-gray-50 rounded-2xl shadow-sm p-6 text-center">
          <div class="icon-wrapper w-16 h-16 mx-auto bg-purple-100 rounded-2xl flex items-center justify-center mb-5">
            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-3">AI Financial Insights</h3>
          <p class="text-gray-600 leading-relaxed">Get personalized financial advice with AI-driven insights and recommendations.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CSS -->
<style>
.feature-card {
  transition: all 0.5s ease;
  border: 1px solid transparent;
  box-shadow: 0 2px 10px rgba(0,0,0,0.03);
}
.feature-card.hovered, .feature-card:hover {
  /* Only highlight without moving */
  border-color: rgba(99, 102, 241, 0.3);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
  background-color: rgba(99,102,241,0.05);
}
.icon-wrapper {
  transition: all 0.5s ease;
}
.feature-card.hovered .icon-wrapper, .feature-card:hover .icon-wrapper {
  transform: scale(1.1);
  background-color: rgba(99, 102, 241, 0.15);
}
</style>

<!-- JS for Smooth Auto Highlight -->
<script>
const cards = document.querySelectorAll('.feature-card');
let index = 0;

setInterval(() => {
  // Remove previous highlight
  cards.forEach(c => c.classList.remove('hovered'));
  // Add highlight to current card
  cards[index].classList.add('hovered');
  // Move to next card
  index = (index + 1) % cards.length;
}, 3000); // 3 seconds per card
</script>

<!-- Interactive Dashboard Demo (upgraded UI) -->
<section class="py-16 bg-gray-50">
  <div class="container mx-auto px-4">
    <!-- Outer card -->
    <div class="rounded-3xl bg-white border border-gray-200 shadow-xl overflow-hidden p-6 md:p-8">
      <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
        
        <!-- Left: content (slightly inset on larger screens) -->
        <div class="md:w-1/2 pl-2 md:pl-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-4">Your Financial Command Center</h2>
          <p class="text-gray-600 mb-6">Our interactive dashboard gives you complete visibility and control over your finances with real-time data visualization.</p>

          <ul class="space-y-3 text-gray-700">
            <li class="flex items-start">
              <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>Personalized spending analytics</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>AI-powered cash flow predictions</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>Customizable financial goals</span>
            </li>
          </ul>
        </div>

        <!-- Right: image preview (responsive, fixed aspect ratio) -->
        <div class="md:w-1/2 w-full">
          <div class="img-frame rounded-lg overflow-hidden shadow-lg border border-gray-100">
            <!-- top window controls -->
            <div class="p-2 bg-gray-100 flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-red-500"></div>
              <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
            </div>

            <!-- Responsive aspect ratio wrapper -->
            <div class="relative" style="padding-top:56.25%;"> <!-- 16:9 aspect -->
              <img
              src="../Assets/images/Dashboard.jpg"
              alt="QuantumBank dashboard preview"
              loading="lazy"
              onerror="this.onerror=null;this.src='https://via.placeholder.com/900x506.png?text=Dashboard+Preview';"
              class="absolute inset-0 w-full h-full object-cover"
            />

              <!-- optional gradient overlay and play/CTA -->
              <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent pointer-events-none"></div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<!-- Small custom styles (put inside <style> or your CSS file) -->
<style>
  /* subtle inner shadow and smoother border radius for the image frame */
  .img-frame { background: white; }
  .img-frame img { transition: transform .35s ease; }
  .img-frame:hover img { transform: scale(1.03); }
</style>

<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="flex flex-col lg:flex-row items-start lg:items-center gap-10">
            
            <div class="lg:w-1/3 p-8 bg-white border-l-4 border-blue-600 rounded-xl shadow-2xl hover:shadow-3xl transition-shadow duration-300 max-w-md self-stretch flex flex-col justify-center">
                
                <div class="mb-4 flex items-center">
                    <span class="text-sm font-semibold uppercase tracking-wider text-blue-600 bg-blue-100 px-3 py-1 rounded-full">
                        ✨ Premium Banking
                    </span>
                </div>

                <h2 class="text-5xl font-extrabold leading-tight text-gray-900">
                    Smart Account Features
                </h2>

                <p class="mt-4 text-xl text-gray-700">
                    Explore our banking products designed to simplify your finances and help you grow.
                </p>

            </div>

            <div class="lg:w-2/3 relative w-full overflow-hidden rounded-2xl shadow-xl">
                
                <div class="carousel-slide transition-all duration-700 ease-in-out opacity-100 relative">
                    <img src="../Assets/images/cashback.jpg"
                        alt="Cashback Offers"
                        class="w-full h-[350px] md:h-[450px] object-cover rounded-2xl">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent rounded-2xl"></div>
                    <div class="absolute bottom-4 md:bottom-6 left-4 md:left-6 text-white">
                        <h3 class="text-2xl md:text-3xl font-bold">Exclusive Cashback Offers</h3>
                        <p class="mt-1 md:mt-2 text-sm md:text-lg max-w-sm opacity-90">
                            Get rewarded for everyday spending with special cashback on shopping, travel, and dining.
                        </p>
                        <a href="#"
                            class="mt-2 md:mt-3 inline-block bg-blue-600 text-white px-4 md:px-6 py-1.5 md:py-2.5 rounded-full font-semibold shadow-md hover:bg-blue-700 transition">
                            Learn More
                        </a>
                    </div>
                </div>

                <div class="carousel-slide absolute inset-0 opacity-0 transition-all duration-700 ease-in-out">
                    <img src="../Assets/images/Loans.jpg"
                        alt="Loan Schemes"
                        class="w-full h-[350px] md:h-[450px] object-cover rounded-2xl">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent rounded-2xl"></div>
                    <div class="absolute bottom-4 md:bottom-6 left-4 md:left-6 text-white">
                        <h3 class="text-2xl md:text-3xl font-bold">Flexible Loan Options</h3>
                        <p class="mt-1 md:mt-2 text-sm md:text-lg max-w-sm opacity-90">
                            Access home, personal, and car loans with competitive interest rates and easy repayment plans.
                        </p>
                        <a href="#"
                            class="mt-2 md:mt-3 inline-block bg-blue-600 text-white px-4 md:px-6 py-1.5 md:py-2.5 rounded-full font-semibold shadow-md hover:bg-blue-700 transition">
                            Apply Now
                        </a>
                    </div>
                </div>

                <div class="carousel-slide absolute inset-0 opacity-0 transition-all duration-700 ease-in-out">
                    <img src="../Assets/images/Credit-rewards.jpg"
                        alt="Credit Card Rewards"
                        class="w-full h-[350px] md:h-[450px] object-cover rounded-2xl">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent rounded-2xl"></div>
                    <div class="absolute bottom-4 md:bottom-6 left-4 md:left-6 text-white">
                        <h3 class="text-2xl md:text-3xl font-bold">Credit Card Rewards</h3>
                        <p class="mt-1 md:mt-2 text-sm md:text-lg max-w-sm opacity-90">
                            Earn points on every transaction and redeem them for shopping, travel, and exclusive experiences.
                        </p>
                        <a href="#"
                            class="mt-2 md:mt-3 inline-block bg-blue-600 text-white px-4 md:px-6 py-1.5 md:py-2.5 rounded-full font-semibold shadow-md hover:bg-blue-700 transition">
                            Get Card
                        </a>
                    </div>
                </div>

                <div class="carousel-slide absolute inset-0 opacity-0 transition-all duration-700 ease-in-out">
                    <img src="../Assets/images/Insurance.jpg"
                        alt="Insurance & Benefits"
                        class="w-full h-[350px] md:h-[450px] object-cover rounded-2xl">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent rounded-2xl"></div>
                    <div class="absolute bottom-4 md:bottom-6 left-4 md:left-6 text-white">
                        <h3 class="text-2xl md:text-3xl font-bold">Insurance & Benefits</h3>
                        <p class="mt-1 md:mt-2 text-sm md:text-lg max-w-sm opacity-90">
                            Enjoy free accident insurance and access to premium services with your account.
                        </p>
                        <a href="#"
                            class="mt-2 md:mt-3 inline-block bg-blue-600 text-white px-4 md:px-6 py-1.5 md:py-2.5 rounded-full font-semibold shadow-md hover:bg-blue-700 transition">
                            Learn More
                        </a>
                    </div>
                </div>

                <div id="carousel-dots" class="flex justify-center mt-6 space-x-3 absolute bottom-4 left-1/2 -translate-x-1/2 z-10"></div>

            </div>
        </div>
    </div>
</section>

<script>
    let currentIndex = 0;
    const slides = document.querySelectorAll(".carousel-slide");
    const dotsContainer = document.getElementById("carousel-dots");

    // Create dots
    slides.forEach((_, i) => {
        const dot = document.createElement("button");
        // Initial dot styling
        dot.className = `w-3 h-3 rounded-full ${i === 0 ? "bg-gray-800" : "bg-gray-400"} transition-transform`;
        dot.setAttribute("aria-label", `Go to slide ${i+1}`);
        dot.onclick = () => goToSlide(i);
        dotsContainer.appendChild(dot);
    });

    function showSlide(index) {
        slides.forEach((slide, i) => {
            // Reset all slides to hidden (absolute positioning and opacity 0)
            slide.classList.remove('opacity-100', 'relative');
            slide.classList.add('opacity-0', 'absolute');
            
            if (i === index) {
                // Set current slide to visible (relative positioning and opacity 100)
                slide.classList.remove('opacity-0', 'absolute');
                slide.classList.add('opacity-100', 'relative');
            }
        });
        
        // Update dot styling
        document.querySelectorAll("#carousel-dots button").forEach((dot, i) => {
            dot.className = `w-3 h-3 rounded-full ${i === index ? "bg-gray-800 scale-110" : "bg-gray-400 scale-100"} transition-transform`;
        });
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % slides.length;
        showSlide(currentIndex);
    }

    function goToSlide(index) {
        currentIndex = index;
        showSlide(index);
    }

    // Initialize the carousel
    showSlide(0);
    // Auto-advance every 2 seconds (2000ms)
    setInterval(nextSlide, 2000);
</script>

  <!-- Security Section -->
  <section class="py-16 bg-gray-900 text-white">
    <div class="container mx-auto px-4">
      <div class="flex flex-col md:flex-row items-center">
        <div class="md:w-1/2 mb-10 md:mb-0">
          <img 
            src="../Assets/images/encryption.jpg" 
            alt="QuantumBank security shield with encryption and protection layers icon" 
            class="w-full h-full object-cover rounded-2xl shadow-lg"
          >
        </div>

        <div class="md:w-1/2 md:pl-12">
          <h2 class="text-3xl font-bold mb-6" style="color: white !important;">Quantum-Secure Banking</h2>

          <p class="text-gray-300 mb-8">Your security is our top priority. We employ multiple layers of protection to safeguard your assets and personal information.</p>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 class="font-bold text-lg mb-2 flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm-1 17.9c-4-.5-7.2-3.3-7.8-7.2L7 13v-2c0-.6.4-1 1-1s1 .4 1 1v3c0 .6-.4 1-1 1H5c-.6 0-1-.4-1-1s.4-1 1-1h1l.2-1.4C5.9 7.7 8.6 5 12 5c3.6 0 6.5 2.9 6.5 6.5 0 1.2-.3 2.4-1 3.4l1.4 1.4c.9-1.3 1.6-2.9 1.6-4.8C20 8.2 16.4 4.5 12 4.5c-3.7 0-6.7 3-6.7 6.7H5l3 3 3-3h-1.2C9.8 8.5 12.6 6 16 6c3.3 0 6 2.7 6 6s-2.7 6-6 6c-1.1 0-2.1-.3-3-.8l-1.5 1.5c1.3.8 2.8 1.3 4.5 1.3 4.2 0 7.9-2.6 9.3-6.4.3-.9.5-1.8.5-2.8 0-.6-.1-1.2-.2-1.8-.5 3.9-3.6 7-7.6 7.6L11 19.9z"></path>
                </svg>
                Biometric Authentication
              </h3>
              <p class="text-gray-400">Fingerprint, face ID, and voice recognition for secure access.</p>
            </div>
            
            <div>
              <h3 class="font-bold text-lg mb-2 flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm0 16c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7zm1-11h-2v3H8v2h3v3h2v-3h3v-2h-3V8z"></path>
                </svg>
                Real-Time Alerts
              </h3>
              <p class="text-gray-400">Instant notifications for all account activity.</p>
            </div>
            
            <div>
              <h3 class="font-bold text-lg mb-2 flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"></path>
                </svg>
                FDIC Insurance
              </h3>
              <p class="text-gray-400">All deposits insured up to $250,000.</p>
            </div>
            
            <div>
              <h3 class="font-bold text-lg mb-2 flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 15c1.66 0 3-1.34 3-3V6c0-1.66-1.34-3-3-3S9 4.34 9 6v6c0 1.66 1.34 3 3 3zm5.91-3c-.49 0-.9.36-.98.85C16.52 15.22 14.47 17 12 17s-4.52-1.78-4.93-4.15c-.08-.49-.49-.85-.98-.85-.61 0-1.09.54-1 1.14.49 3 2.89 5.35 5.91 5.78V20c0 .55.45 1 1 1s1-.45 1-1v-2.08c3.02-.43 5.42-2.78 5.91-5.78.1-.6-.39-1.14-1-1.14z"></path>
                </svg>
                End-to-End Encryption
              </h3>
              <p class="text-gray-400">Your data is protected in transit and at rest.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

<style>
    /* Custom CSS for the ultimate premium card effect */
    .premium-card-v2 {
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); /* Smoother, professional transition */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06); /* Initial soft shadow */
        border-radius: 1rem;
        border-left: 5px solid transparent; /* Prepared for the structural accent */
    }
    .premium-card-v2:hover {
        transform: translateY(-8px); /* Clearer lift */
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2); /* Deep, floating shadow */
        border-left-color: #4c51bf; /* Vibrant brand accent color on hover */
    }
    /* Quote Icon Styling for emphasis */
    .quote-mark {
        color: #4c51bf;
        opacity: 0.1;
        font-size: 5rem; /* Larger and more prominent */
        position: absolute;
        top: -1.5rem;
        left: 0.5rem;
        font-family: serif;
        line-height: 1;
        z-index: 0;
    }
</style>

<style>
    /* Custom CSS to define the premium UI for the testimonial cards */
    .card-hover {
        /* Base Styles: Pure white background, subtle shadow */
        background-color: #ffffff !important; /* Force pure white for maximum contrast */
        padding: 1.75rem !important; /* Slightly increased padding for breathing room */
        border-radius: 1rem !important; /* Modern rounded corners */
        box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.08); /* Initial soft shadow */
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); /* Professional, smooth transition */
        border: 1px solid transparent; /* Prepare for hover border */
        position: relative;
    }
    .card-hover:hover {
        /* Hover Effect: Lifts the card and adds a structural accent */
        transform: translateY(-8px); /* Clearer lift, making it feel 'floating' */
        box-shadow: 0 25px 40px -10px rgba(0, 0, 0, 0.15); /* Deep, floating shadow */
        border-color: #4c51bf; /* Subtle blue outline on hover (Primary Brand Color) */
    }
    /* Style the main headline for premium feel */
    .container h2 {
        color: #1a237e !important; /* Deep Indigo for authority */
        font-size: 2.5rem !important;
        font-weight: 800 !important;
        margin-bottom: 3rem !important;
        letter-spacing: -0.025em;
    }
    /* Style the testimonial quote text */
    .card-hover p:not(.text-sm) {
        color: #1f2937 !important; /* Darker text for readability */
        font-style: italic;
        line-height: 1.6;
    }
    /* Style the rating stars to the high-contrast gold */
    .card-hover .text-yellow-400 {
        color: #facc15 !important; /* Vibrant Gold/Yellow accent */
    }
</style>

<section style="background-color: #f0f2f7; padding-top: 6rem; padding-bottom: 6rem;">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Trusted by Thousands</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-gray-50 p-6 rounded-xl card-hover">
                <div class="flex items-center mb-4">
                    <img src="../Assets/images/marketing-director.jpg" alt="Sarah Johnson, Marketing Director at TechCorp" class="w-12 h-12 rounded-full mr-4" style="object-fit: cover;">
                    <div>
                        <h4 class="font-bold">Sarah Johnson</h4>
                        <p class="text-sm text-gray-600">Marketing Director</p>
                    </div>
                </div>
                <p class="text-gray-700 mb-4">"QuantumBank's platform has revolutionized our financial operations with its intuitive interface and powerful tools."</p>
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
            
            <div class="bg-gray-50 p-6 rounded-xl card-hover">
                <div class="flex items-center mb-4">
                    <img src="../Assets/images/designer.jpg" alt="Michael Chen, Freelance Designer" class="w-12 h-12 rounded-full mr-4" style="object-fit: cover;">
                    <div>
                        <h4 class="font-bold">Michael Chen</h4>
                        <p class="text-sm text-gray-600">Freelance Designer</p>
                    </div>
                </div>
                <p class="text-gray-700 mb-4">"The AI insights helped me identify spending patterns I never noticed. Saved 15% on unnecessary expenses last month!"</p>
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
            
            <div class="bg-gray-50 p-6 rounded-xl card-hover">
                <div class="flex items-center mb-4">
                    <img src="../Assets/images/startup-founder.jpg" alt="David Rodriguez, Founder at StartupXYZ" class="w-12 h-12 rounded-full mr-4" style="object-fit: cover;">
                    <div>
                        <h4 class="font-bold">David Rodriguez</h4>
                        <p class="text-sm text-gray-600">Startup Founder</p>
                    </div>
                </div>
                <p class="text-gray-700 mb-4">"QuantumBank's cash flow forecasting has been invaluable for our startup growth, giving us the financial insights we need to scale smartly."</p>
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

<section style="background-image: linear-gradient(to right, #4c51bf, #7f3399); color: white; padding-top: 6rem; padding-bottom: 6rem; position: relative; overflow: hidden;">
    
    <div style="position: absolute; top: 10%; left: 5%; width: 100px; height: 100px; background-color: rgba(255, 255, 255, 0.08); border-radius: 50%;"></div>
    <div style="position: absolute; bottom: 10%; right: 5%; width: 150px; height: 150px; background-color: rgba(255, 255, 255, 0.08); border-radius: 50%;"></div>
    
    <div style="max-width: 1024px; margin-left: auto; margin-right: auto; padding-left: 1rem; padding-right: 1rem; text-align: center; position: relative; z-index: 10;">
        
        <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; letter-spacing: -0.025em;" class="md:text-5xl">
            Your Financial Future Starts Now
        </h2>
        
        <p style="font-size: 1.25rem; color: #e0f2f7; max-width: 42rem; margin-left: auto; margin-right: auto; margin-bottom: 3rem;">
            Join thousands of customers experiencing the security, simplicity, and future of finance today.
        </p>
        
        <div style="display: flex; flex-direction: column; justify-content: center; gap: 1rem;" class="sm:flex-row sm:space-y-0 sm:space-x-6">
            
            <a href="OpenAccount.html"
   style="background-color: rgba(255, 255, 255, 0.95); color: #4c51bf; padding: 1.25rem 3rem; border-radius: 9999px; font-weight: 800; font-size: 1.25rem; border: 2px solid rgba(255, 255, 255, 0.7); box-shadow: 0 15px 30px -8px rgba(76, 81, 191, 0.5);"
   class="transition-all duration-300 ease-in-out transform hover:-translate-y-1 hover:bg-white hover:scale-105 focus:outline-none focus:ring-4 focus:ring-white focus:ring-opacity-75">
    Open Account Instantly →
</a>
            <a href="contact.html"
               style="border: 2px solid white; color: white; background-color: transparent; padding: 1rem 2.5rem; border-radius: 9999px; font-weight: 600; font-size: 1.125rem;"
               class="transition-all duration-300 ease-in-out transform hover:bg-white hover:text-purple-800 hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-white focus:ring-opacity-50">
                Speak to an Advisor
            </a>
        </div>
        
    </div>
</section>