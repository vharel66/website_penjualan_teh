<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'teh_shop');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
session_start();

// Base URL
define('BASE_URL', 'http://localhost/web_titis/');

// Nama Website
define('SITE_NAME', 'TEH TITIS');

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getCSRFToken() {
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
