<?php
// Database configuration
define('DB_HOST', 'sql105.infinityfree.com');
define('DB_USER', 'if0_40641254');
define('DB_PASS', 'Ousou2004');
define('DB_NAME', 'if0_40641254_attendancedb');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4 for proper character support
mysqli_set_charset($conn, "utf8mb4");

// Function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Function to execute queries safely
function execute_query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    if (!$result) {
        error_log("Query Error: " . mysqli_error($conn));
        return false;
    }
    return $result;
}
?>