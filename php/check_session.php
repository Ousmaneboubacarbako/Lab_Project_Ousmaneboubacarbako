<?php
session_start();

header('Content-Type: application/json');

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['role']);

if ($isLoggedIn) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role']
        ],
        'dashboard' => getDashboardUrl($_SESSION['role'])
    ]);
} else {
    echo json_encode([
        'authenticated' => false,
        'redirect' => '/Lab 3 assignment (3)/Lab 3 assignment/html/Login.html'
    ]);
}

function getDashboardUrl($role) {
    switch($role) {
        case 'admin':
            return '/Lab 3 assignment (3)/Lab 3 assignment/html/admin_dashboard.html';
        case 'faculty':
            return '/Lab 3 assignment (3)/Lab 3 assignment/html/FDashboard.html';
        case 'student':
            return '/Lab 3 assignment (3)/Lab 3 assignment/html/StuDashboard.html';
        case 'fi':
            return '/Lab 3 assignment (3)/Lab 3 assignment/html/Dashboard.html';
        default:
            return '/Lab 3 assignment (3)/Lab 3 assignment/html/Login.html';
    }
}
?>
