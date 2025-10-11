<?php
// register.php
// Handles new user registration requests from the frontend.

include '../includes/db_connect.php';

// Set content type to JSON for API responses
header('Content-Type: application/json');

// Start a session (optional for registration, but good practice for a web app)
session_start();

// --- Get Request Data ---
// Handle multipart/form-data from the frontend form
$data = $_POST;

// Extract data, providing default empty strings to avoid 'undefined index' warnings
$username = trim($data['fullName'] ?? ''); // Using fullName as username
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? ''; // Passwords should not be trimmed before hashing
$pin = $data['pin'] ?? '';
$address = trim($data['address'] ?? '');
$phone = trim($data['phone'] ?? '');
$dob = trim($data['dob'] ?? '');
$accountType = trim($data['accountType'] ?? '');
$documentType = trim($data['documentType'] ?? '');

// --- File Upload Handling ---
$documentPath = '';
if (isset($_FILES['documentFile']) && $_FILES['documentFile']['error'] == UPLOAD_ERR_OK) {
    $file = $_FILES['documentFile'];
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg'];
    if (!in_array($file['type'], $allowedTypes) || $file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid file. Must be PDF or JPG, max 5MB.']);
        $conn->close();
        exit();
    }
    $uploadDir = '../uploads/kyc/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueName = uniqid('kyc_', true) . '.' . $ext;
    $fullPath = $uploadDir . $uniqueName;
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to upload file.']);
        $conn->close();
        exit();
    }
    $documentPath = 'uploads/kyc/' . $uniqueName;
} else {
    http_response_code(400);
    echo json_encode(['message' => 'Document file is required.']);
    $conn->close();
    exit();
}

// --- Input Validation ---
if (empty($username) || empty($email) || empty($password) || empty($pin) || empty($address) || empty($phone) || empty($dob) || empty($accountType) || empty($documentType)) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'All fields are required.']);
    $conn->close();
    exit();
}

// Validate PIN
if (!preg_match('/^\d{4}$/', $pin)) {
    http_response_code(400);
    echo json_encode(['message' => 'PIN must be exactly 4 digits.']);
    $conn->close();
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Invalid email format.']);
    $conn->close();
    exit();
}


// Basic password length check (adjust as needed for stronger policies)
if (strlen($password) < 8) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Password must be at least 8 characters long.']);
    $conn->close();
    exit();
}

// --- Check if Email Already Exists ---
$stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt_check) {
    http_response_code(500);
    echo json_encode(['message' => 'Failed to prepare email check statement: ' . $conn->error]);
    $conn->close();
    exit();
}
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(['message' => 'This email is already registered. Please use a different email or log in.']);
    $stmt_check->close();
    $conn->close();
    exit();
}
$stmt_check->close();

// --- Hash Password and PIN ---
// Use password_hash() for secure, one-way hashing
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
if ($hashed_password === false) {
    http_response_code(500);
    echo json_encode(['message' => 'Failed to hash password.']);
    $conn->close();
    exit();
}
$hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
if ($hashed_pin === false) {
    http_response_code(500);
    echo json_encode(['message' => 'Failed to hash PIN.']);
    $conn->close();
    exit();
}

// --- Insert New User into Database ---
$stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, pin, kyc_document_type, kyc_document_path) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt_insert) {
    http_response_code(500);
    echo json_encode(['message' => 'Failed to prepare user insertion statement: ' . $conn->error]);
    $conn->close();
    exit();
}
$stmt_insert->bind_param("ssssss", $username, $email, $hashed_password, $hashed_pin, $documentType, $documentPath);

if ($stmt_insert->execute()) {
    $user_id = $conn->insert_id;
    // Generate account number
    $account_number = strval(mt_rand(1000000000, 9999999999));
    // Insert account
    $stmt_account = $conn->prepare("INSERT INTO accounts (user_id, account_type, account_number, balance) VALUES (?, ?, ?, 0.00)");
    $stmt_account->bind_param("iss", $user_id, $accountType, $account_number);
    if ($stmt_account->execute()) {
        http_response_code(201); // 201 Created - Indicates successful resource creation
        echo json_encode(['message' => 'Account created successfully! Your account number is ' . $account_number]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'User created but account creation failed.']);
    }
    $stmt_account->close();
} else {
    http_response_code(500); // Internal Server Error
    // Log the actual error for debugging, but send a generic message to the user
    error_log("Database error during registration: " . $stmt_insert->error);
    echo json_encode(['message' => 'Account creation failed. Please try again later.']);
}

// --- Close Statements and Database Connection ---
$stmt_insert->close();
$conn->close();
?>
