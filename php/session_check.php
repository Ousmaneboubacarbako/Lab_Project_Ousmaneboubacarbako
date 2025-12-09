<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Function to check user role
function checkRole($required_role) {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to access this page.";
        header("Location: /Lab 3 assignment (3)/Lab 3 assignment/html/Login.html");
        exit();
    }
    
    // Allow admin to access everything
    if ($_SESSION['role'] === 'admin') {
        return true;
    }
    
    // Check if user has required role
    if ($_SESSION['role'] !== $required_role) {
        $_SESSION['error'] = "Access denied. You don't have permission to access this page.";
        
        // Redirect to appropriate dashboard based on their role
        switch($_SESSION['role']) {
            case 'faculty':
                header("Location: /Lab 3 assignment (3)/Lab 3 assignment/html/FDashboard.html");
                break;
            case 'student':
                header("Location: /Lab 3 assignment (3)/Lab 3 assignment/html/StuDashboard.html");
                break;
            case 'fi':
                header("Location: /Lab 3 assignment (3)/Lab 3 assignment/html/Dashboard.html");
                break;
            default:
                header("Location: /Lab 3 assignment (3)/Lab 3 assignment/html/Login.html");
        }
        exit();
    }
    
    return true;
}

// Function to get current user info
function getCurrentUser() {
    global $conn;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// Function to redirect based on role
function redirectToDashboard() {
    if (!isLoggedIn()) {
        header("Location: /Lab 3 assignment (3)/Lab 3 assignment/html/Login.html");
        exit();
    }
    
    switch($_SESSION['role']) {
        case 'admin':
            header("Location: /Lab 3 assignment (3)/Lab 3 assignment/html/admin_dashboard.html");
            break;
        case 'faculty':
            header("Location: /Lab 3 assignment (3)/Lab 3 assignment/html/FDashboard.html");
            break;
        case 'student':
            header("Location: /Lab 3 assignment (3)/Lab 3 assignment/html/StuDashboard.html");
            break;
        case 'fi':
            header("Location: /Lab 3 assignment (3)/Lab 3 assignment/html/Dashboard.html");
            break;
        default:
            header("Location: /Lab 3 assignment (3)/Lab 3 assignment/html/Login.html");
    }
    exit();
}

// Function to log activity
function logActivity($user_id, $action, $description = '') {
    global $conn;
    
    $user_id = intval($user_id);
    $action = mysqli_real_escape_string($conn, $action);
    $description = mysqli_real_escape_string($conn, $description);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $query = "INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
              VALUES ($user_id, '$action', '$description', '$ip_address', NOW())";
    
    mysqli_query($conn, $query);
}
?>