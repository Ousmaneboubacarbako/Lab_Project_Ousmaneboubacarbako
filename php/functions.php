<?php
// General utility functions for the Attendance Management System

// Validate email format
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate name (letters, spaces, hyphens, apostrophes only)
function validateName($name) {
    return preg_match("/^[a-zA-Z\s\-\']+$/", $name);
}

// Validate password strength
function validatePassword($password) {
    // At least 8 characters
    if (strlen($password) < 8) {
        return false;
    }
    return true;
}

// Format date for display
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Format time for display
function formatTime($time, $format = 'h:i A') {
    return date($format, strtotime($time));
}

// Calculate attendance percentage
function calculateAttendanceRate($present, $total) {
    if ($total == 0) {
        return 0;
    }
    return round(($present / $total) * 100, 1);
}

// Get attendance status badge color
function getStatusBadgeClass($status) {
    switch(strtolower($status)) {
        case 'present':
            return 'badge-success';
        case 'absent':
            return 'badge-danger';
        case 'late':
            return 'badge-warning';
        case 'excused':
            return 'badge-info';
        default:
            return 'badge-secondary';
    }
}

// Generate random password
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// Send email notification (basic implementation)
function sendEmail($to, $subject, $message) {
    // In a production environment, use a proper email library like PHPMailer
    $headers = "From: noreply@attendance-system.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Upload file helper
function uploadFile($file, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'], $max_size = 5242880) {
    $target_dir = "../uploads/";
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Validate file type
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    // Validate file size
    if ($file["size"] > $max_size) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename, 'path' => $target_file];
    } else {
        return ['success' => false, 'error' => 'Upload failed'];
    }
}

// Paginate results
function paginate($total_items, $items_per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'items_per_page' => $items_per_page,
        'offset' => $offset
    ];
}

// Export data to CSV
function exportToCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        // Add headers
        fputcsv($output, array_keys($data[0]));
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit();
}

// Get academic year
function getCurrentAcademicYear() {
    $current_month = date('n');
    $current_year = date('Y');
    
    if ($current_month >= 8) {
        return $current_year . '-' . ($current_year + 1);
    } else {
        return ($current_year - 1) . '-' . $current_year;
    }
}

// Calculate grade based on attendance
function calculateGrade($attendance_rate) {
    if ($attendance_rate >= 90) {
        return 'A';
    } elseif ($attendance_rate >= 80) {
        return 'B';
    } elseif ($attendance_rate >= 70) {
        return 'C';
    } elseif ($attendance_rate >= 60) {
        return 'D';
    } else {
        return 'F';
    }
}

// Display flash messages
function displayFlashMessage() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['warning'])) {
        echo '<div class="alert alert-warning">' . htmlspecialchars($_SESSION['warning']) . '</div>';
        unset($_SESSION['warning']);
    }
    
    if (isset($_SESSION['info'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['info']) . '</div>';
        unset($_SESSION['info']);
    }
}

// Time ago function
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    $periods = [
        'year' => 31536000,
        'month' => 2592000,
        'week' => 604800,
        'day' => 86400,
        'hour' => 3600,
        'minute' => 60,
        'second' => 1
    ];
    
    foreach ($periods as $period => $value) {
        if ($difference >= $value) {
            $time = floor($difference / $value);
            return $time . ' ' . $period . ($time > 1 ? 's' : '') . ' ago';
        }
    }
    
    return 'just now';
}
?>