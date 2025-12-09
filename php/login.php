<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    // Validation
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required!']);
        exit();
    }
    
    // Check user credentials (using prepared statement)
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Remember me functionality
            if ($remember) {
                setcookie('user_email', $email, time() + (86400 * 30), "/");
            }
            
            // Determine redirect URL
            $redirect = '';
            switch($user['role']) {
                case 'admin':
                    $redirect = '/Lab%203%20assignment%20(3)/Lab_assignment_4/html/admin_dashboard.html';
                    break;
                case 'faculty':
                    $redirect = '/Lab%203%20assignment%20(3)/Lab_assignment_4/html/FDashboard.html';
                    break;
                case 'student':
                    $redirect = '/Lab%203%20assignment%20(3)/Lab_assignment_4/html/StuDashboard.html';
                    break;
                case 'fi':
                    $redirect = '/Lab%203%20assignment%20(3)/Lab_assignment_4/html/Dashboard.html';
                    break;
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Login successful!',
                'redirect' => $redirect,
                'user' => [
                    'name' => $user['first_name'] . ' ' . $user['last_name'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password!']);
        }
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Invalid email or inactive account!']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>