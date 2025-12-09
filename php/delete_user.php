<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

checkRole('admin');

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id > 0) {
    // Check if user exists (using prepared statement)
    $check_stmt = $conn->prepare("SELECT id, role FROM users WHERE id = ?");
    $check_stmt->bind_param('i', $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $check_stmt->close();
        
        // Prevent deleting yourself
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error'] = "You cannot delete your own account!";
        } else {
            // Delete user (using prepared statement)
            $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $delete_stmt->bind_param('i', $user_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['success'] = "User deleted successfully!";
                
                // Also delete related records
                if ($user['role'] == 'student') {
                    $del_enroll = $conn->prepare("DELETE FROM enrollments WHERE student_id = ?");
                    $del_enroll->bind_param('i', $user_id);
                    $del_enroll->execute();
                    $del_enroll->close();
                }
            } else {
                $_SESSION['error'] = "Error deleting user!";
            }
            $delete_stmt->close();
                    mysqli_query($conn, "DELETE FROM enrollments WHERE student_id = $user_id");
                    mysqli_query($conn, "DELETE FROM attendance WHERE student_id = $user_id");
                }
                
                // Update courses if faculty
                if ($user['role'] == 'faculty') {
                    mysqli_query($conn, "UPDATE courses SET instructor_id = NULL WHERE instructor_id = $user_id");
                }
            } else {
                $_SESSION['error'] = "Error deleting user: " . mysqli_error($conn);
            }
        }
    } else {
        $_SESSION['error'] = "User not found!";
    }
} else {
    $_SESSION['error'] = "Invalid user ID!";
}

header("Location: admin_dashboard.php");
exit();
?>