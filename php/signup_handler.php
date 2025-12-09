<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = mysqli_real_escape_string($conn, $_POST['firstName']);
    $last_name = mysqli_real_escape_string($conn, $_POST['lastName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirmPassword'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required!']);
        exit();
    }
    
    if (!preg_match("/^[a-zA-Z\s\-\']+$/", $first_name)) {
        echo json_encode(['success' => false, 'message' => 'First name can only contain letters, spaces, hyphens, and apostrophes!']);
        exit();
    }
    
    if (!preg_match("/^[a-zA-Z\s\-\']+$/", $last_name)) {
        echo json_encode(['success' => false, 'message' => 'Last name can only contain letters, spaces, hyphens, and apostrophes!']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format!']);
        exit();
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match!']);
        exit();
    }
    
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long!']);
        exit();
    }
    
    // Check if email already exists (using prepared statement)
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param('s', $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists! Please use a different email.']);
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user (using prepared statement)
    $insert_stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
    $insert_stmt->bind_param('sssss', $first_name, $last_name, $email, $hashed_password, $role);
    
    if ($insert_stmt->execute()) {
        $insert_stmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! Redirecting to login...',
            'redirect' => '/Lab%203%20assignment%20(3)/Lab_assignment_4/html/Login.html'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating account. Please try again.']);
        $insert_stmt->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>