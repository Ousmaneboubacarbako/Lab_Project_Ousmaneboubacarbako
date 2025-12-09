<?php
session_start();

// Destroy all session data
$_SESSION = array();

// Delete session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Return JSON response for AJAX calls
if (isset($_POST['logout']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    exit();
}

// Redirect to login page for direct access
header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/Login.html");
exit();
?>