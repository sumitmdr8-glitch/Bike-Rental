<?php
// Connect to MySQL
$conn = mysqli_connect("localhost", "root", "", "bikerentalbt");

// Check connection
if(!$conn){
    die("MySQL Connection failed: " . mysqli_connect_error());
}

// Optional: Set charset for proper character handling
mysqli_set_charset($conn, "utf8mb4");
session_start();

// CSRF Protection
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
