<?php
// Session and auth helper functions
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie to last 30 days
    ini_set('session.cookie_lifetime', 60*60*24*30);
    // Set session garbage collection to 30 days
    ini_set('session.gc_maxlifetime', 60*60*24*30);
    // Disable automatic garbage collection to prevent premature session deletion
    ini_set('session.gc_probability', 0);
    session_start();
}

// Prevent redeclaration
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('getUserId')) {
    function getUserId() {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }
}

if (!function_exists('getUsername')) {
    function getUsername() {
        return isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('isEmailVerified')) {
    // Accepts a PDO instance and optional user id
    function isEmailVerified($pdo, $userId = null) {
        if ($userId === null) $userId = getUserId();
        if (!$userId) return false;
        // Allow calling without PDO by using global $pdo if available
        if ($pdo === null) {
            if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
                $pdo = $GLOBALS['pdo'];
            } else {
                return false; // cannot determine
            }
        }
        try {
            $stmt = $pdo->prepare('SELECT verified FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $r = $stmt->fetch();
            return $r && $r['verified'];
        } catch (Exception $e) {
            return false;
        }
    }
}

// Require login helper
if (!function_exists('requireLogin')) {
    function requireLogin() {
        // Prevent caching of sensitive pages
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
        if (!isLoggedIn()) {
            // Save requested URL and redirect to login
            if (!headers_sent()) {
                header('Location: login.php');
            }
            exit;
        }
    }
}

// CSRF helpers
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token) {
        if (empty($token) || empty($_SESSION['csrf_token'])) return false;
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Flash messaging helper
if (!function_exists('flash')) {
    function flash($key, $message = null) {
        if ($message === null) {
            if (isset($_SESSION['flash'][$key])) {
                $msg = $_SESSION['flash'][$key];
                unset($_SESSION['flash'][$key]);
                return $msg;
            }
            return null;
        }
        $_SESSION['flash'][$key] = $message;
    }
}

// Helper to pull and render all flash messages
if (!function_exists('renderFlashes')) {
    function renderFlashes() {
        if (!empty($_SESSION['flash']) && is_array($_SESSION['flash'])) {
            foreach ($_SESSION['flash'] as $k => $m) {
                echo "<div class=\"alert alert-info\">" . htmlspecialchars($m) . "</div>";
            }
            unset($_SESSION['flash']);
        }
    }
}

// Removed cache control headers to allow caching and prevent session loss on back button

?>
