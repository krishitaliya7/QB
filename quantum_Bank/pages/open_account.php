<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Open Account - QuantumBank</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }
    .gradient-bg {
      background: linear-gradient(135deg, #4f46e5 0%, #8b5cf6 100%);
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">
  
  <!-- HEADER -->
  <header class="gradient-bg text-white text-center py-16 shadow-lg">
    <h1 class="text-4xl md:text-5xl font-bold mb-2">Open Your QuantumBank Account</h1>
    <h2 class="text-lg md:text-xl opacity-90 mb-6">Experience next-generation digital banking</h2>
    <div class="flex justify-center gap-4">
      <button onclick="document.getElementById('accountForm').scrollIntoView({ behavior: 'smooth' });" 
        class="bg-white text-indigo-700 font-semibold px-6 py-2 rounded-lg shadow hover:bg-indigo-50 transition">
        Open Account
      </button>
      <button class="border border-white text-white font-semibold px-6 py-2 rounded-lg hover:bg-white hover:text-indigo-700 transition">
        Watch Demo
      </button>
    </div>
  </header>

  <!-- FEATURES SECTION -->
  <section class="max-w-6xl mx-auto text-center py-12 px-4">
    <h2 class="text-2xl md:text-3xl font-semibold mb-8 text-indigo-700">Why Choose QuantumBank?</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition">
        <img src="security-icon.png" alt="Security" class="w-20 mx-auto mb-4">
        <p class="font-medium text-gray-700">Military-grade Security</p>
      </div>
      <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition">
        <img src="speed-icon.png" alt="Speed" class="w-20 mx-auto mb-4">
        <p class="font-medium text-gray-700">Lightning-Fast Transactions</p>
      </div>
      <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition">
        <img src="smart-wallet-icon.png" alt="Smart Wallet" class="w-20 mx-auto mb-4">
        <p class="font-medium text-gray-700">Smart Financial Management</p>
      </div>
    </div>
  </section>

  <!-- FORM SECTION -->
  <section id="accountForm" class="max-w-lg mx-auto bg-white shadow-xl rounded-2xl p-8 mb-12">
    <h2 class="text-2xl font-semibold text-indigo-700 mb-6 text-center">Account Opening Form</h2>
    
    <form action="register.php" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return handleSubmit(event)">
      <div>
        <input type="text" name="fullName" id="fullName" placeholder="Full Name" required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" />
        <p class="text-red-500 text-sm hidden" id="fullNameError">Full Name is required.</p>
      </div>

      <div>
        <input type="email" name="email" id="email" placeholder="Email" required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" />
        <p class="text-red-500 text-sm hidden" id="emailError">Please enter a valid email address.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <input type="password" name="password" id="password" placeholder="Password" required 
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" />
          <p class="text-red-500 text-sm hidden" id="passwordError">Password must be at least 6 characters.</p>
        </div>
        <div>
          <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Confirm Password" required 
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" />
          <p class="text-red-500 text-sm hidden" id="confirmPasswordError">Passwords do not match.</p>
        </div>
      </div>

      <div>
        <input type="text" name="address" id="address" placeholder="Address" required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" />
        <p class="text-red-500 text-sm hidden" id="addressError">Address is required.</p>
      </div>

      <div>
        <input type="tel" name="phone" id="phone" placeholder="Phone (e.g., 123-456-7890)" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" />
        <p class="text-red-500 text-sm hidden" id="phoneError">Please enter a valid phone number.</p>
      </div>

      <div>
        <input type="password" name="pin" id="pin" placeholder="4-digit PIN" pattern="[0-9]{4}" maxlength="4" required
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" />
        <p class="text-red-500 text-sm hidden" id="pinError">PIN must be exactly 4 digits.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <input type="date" name="dob" id="dob" required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" />
          <p class="text-red-500 text-sm hidden" id="dobError">Date of Birth is required.</p>
        </div>
        <div>
          <select name="accountType" id="accountType" required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">Select Account Type</option>
            <option value="savings">Savings Account</option>
            <option value="checking">Checking Account</option>
            <option value="business">Business Account</option>
          </select>
          <p class="text-red-500 text-sm hidden" id="accountTypeError">Please select an account type.</p>
        </div>
      </div>

      <div>
        <select name="documentType" id="documentType" required
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
          <option value="">Select Document Type</option>
          <option value="aadhaar">Aadhaar</option>
          <option value="pan">PAN</option>
        </select>
        <p class="text-red-500 text-sm hidden" id="documentTypeError">Please select document type.</p>
      </div>

      <div>
        <input type="file" name="documentFile" id="documentFile" accept=".pdf,.jpg,.jpeg" required
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" />
        <p class="text-red-500 text-sm hidden" id="documentFileError">Please upload a valid document (PDF or JPG, max 5MB).</p>
      </div>

      <button type="submit" 
        class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700 transition">
        Submit Application
      </button>
    </form>
  </section>

  <!-- FOOTER -->
  <footer class="gradient-bg text-white text-center py-6">
    <p class="text-sm">&copy; 2025 QuantumBank. All rights reserved.</p>
  </footer>

  <!-- JS VALIDATION -->
 <script>
function handleSubmit(event) {
  event.preventDefault();

  const form = event.target;
  let isValid = true;

  const fullName = form.fullName.value.trim();
  const email = form.email.value.trim();
  const password = form.password.value;
  const confirmPassword = form.confirmPassword.value;
  const address = form.address.value.trim();
  const phone = form.phone.value.trim();
  const pin = form.pin.value;
  const dob = form.dob.value;
  const accountType = form.accountType.value;
  const documentType = form.documentType.value;
  const documentFile = form.documentFile.files[0];

  function showError(id, message) {
    const error = document.getElementById(id + 'Error');
    error.textContent = message;
    error.classList.remove('hidden');
    isValid = false;
  }

  function hideError(id) {
    document.getElementById(id + 'Error').classList.add('hidden');
  }

  if (!fullName) showError('fullName', 'Full Name is required.'); else hideError('fullName');
  if (!email.includes('@')) showError('email', 'Please enter a valid email.'); else hideError('email');
  if (password.length < 6) showError('password', 'Password must be at least 6 characters.'); else hideError('password');
  if (confirmPassword !== password) showError('confirmPassword', 'Passwords do not match.'); else hideError('confirmPassword');
  if (!address) showError('address', 'Address is required.'); else hideError('address');
  if (!phone.match(/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/)) showError('phone', 'Phone must match 123-456-7890.'); else hideError('phone');
  if (!pin.match(/^\d{4}$/)) showError('pin', 'PIN must be exactly 4 digits.'); else hideError('pin');
  if (!dob) showError('dob', 'Please enter your date of birth.'); else hideError('dob');
  if (!accountType) showError('accountType', 'Please select account type.'); else hideError('accountType');
  if (!documentType) showError('documentType', 'Please select document type.'); else hideError('documentType');
  if (!documentFile) showError('documentFile', 'Please upload a document.'); else if (!['application/pdf', 'image/jpeg', 'image/jpg'].includes(documentFile.type) || documentFile.size > 5 * 1024 * 1024) showError('documentFile', 'File must be PDF or JPG, max 5MB.'); else hideError('documentFile');

  if (isValid) {
    const formData = new FormData(form);
    fetch('register.php', {
      method: 'POST',
      body: formData
    })
    .then(async (response) => {
      const data = await response.json();
      alert(data.message || 'Account creation response received!');
      if (response.ok) form.reset();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred. Please try again.');
    });
  }
}
</script>


</body>
</html>
