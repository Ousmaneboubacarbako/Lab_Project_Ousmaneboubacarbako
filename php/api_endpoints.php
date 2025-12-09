<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

switch($action) {
    
    // Get user info
    case 'get_user_info':
        $query = "SELECT id, first_name, last_name, email, role FROM users WHERE id = $user_id";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            echo json_encode(['success' => true, 'data' => mysqli_fetch_assoc($result)]);
        } else {
            echo json_encode(['success' => false, 'error' => 'User not found']);
        }
        break;
    
    // Get student courses
    case 'get_student_courses':
        if ($user_role != 'student') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            break;
        }
        
        $query = "SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as instructor_name
                  FROM enrollments e
                  JOIN courses c ON e.course_id = c.id
                  LEFT JOIN users u ON c.instructor_id = u.id
                  WHERE e.student_id = $user_id AND e.status = 'active'
                  ORDER BY c.course_name";
        $result = mysqli_query($conn, $query);
        
        $courses = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $courses[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $courses]);
        break;
    
    // Get faculty courses
    case 'get_faculty_courses':
        if ($user_role != 'faculty' && $user_role != 'fi') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            break;
        }
        
        $query = "SELECT c.*, 
                  (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count,
                  (SELECT COUNT(*) FROM sessions WHERE course_id = c.id) as session_count
                  FROM courses c
                  WHERE c.instructor_id = $user_id AND c.status = 'active'
                  ORDER BY c.course_name";
        $result = mysqli_query($conn, $query);
        
        $courses = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $courses[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $courses]);
        break;
    
    // Get upcoming sessions
    case 'get_upcoming_sessions':
        if ($user_role == 'student') {
            $query = "SELECT s.*, c.course_name, c.course_code
                     FROM sessions s
                     JOIN courses c ON s.course_id = c.id
                     JOIN enrollments e ON (e.course_id = c.id AND e.student_id = $user_id)
                     WHERE s.session_date >= CURDATE() AND e.status = 'active'
                     ORDER BY s.session_date, s.start_time LIMIT 10";
        } elseif ($user_role == 'faculty' || $user_role == 'fi') {
            $query = "SELECT s.*, c.course_name, c.course_code
                     FROM sessions s
                     JOIN courses c ON s.course_id = c.id
                     WHERE c.instructor_id = $user_id AND s.session_date >= CURDATE()
                     ORDER BY s.session_date, s.start_time LIMIT 10";
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid role']);
            break;
        }
        
        $result = mysqli_query($conn, $query);
        $sessions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $sessions[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $sessions]);
        break;
    
    // Get student attendance summary
    case 'get_student_attendance':
        if ($user_role != 'student') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            break;
        }
        
        $query = "SELECT 
                 COUNT(a.id) as total_records,
                 SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                 SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                 SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                 ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as attendance_rate
                 FROM attendance a
                 WHERE a.student_id = $user_id";
        
        $result = mysqli_query($conn, $query);
        $stats = mysqli_fetch_assoc($result);
        
        echo json_encode(['success' => true, 'data' => $stats]);
        break;
    
    // Get course attendance by student
    case 'get_course_attendance':
        if ($user_role != 'student') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            break;
        }
        
        $query = "SELECT c.course_name, c.course_code,
                 COUNT(a.id) as total_sessions,
                 SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                 SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                 SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                 ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as attendance_rate
                 FROM attendance a
                 JOIN sessions s ON a.session_id = s.id
                 JOIN courses c ON s.course_id = c.id
                 WHERE a.student_id = $user_id
                 GROUP BY c.id
                 ORDER BY c.course_name";
        
        $result = mysqli_query($conn, $query);
        $records = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $records[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $records]);
        break;
    
    // Get faculty attendance overview
    case 'get_faculty_attendance_overview':
        if ($user_role != 'faculty') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            break;
        }
        
        $query = "SELECT c.course_name,
                 COUNT(DISTINCT a.student_id) as total_students,
                 COUNT(a.id) as total_records,
                 SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                 ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as attendance_rate
                 FROM attendance a
                 JOIN sessions s ON a.session_id = s.id
                 JOIN courses c ON s.course_id = c.id
                 WHERE c.instructor_id = $user_id
                 GROUP BY c.id
                 ORDER BY c.course_name";
        
        $result = mysqli_query($conn, $query);
        $records = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $records[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $records]);
        break;
    
    // Get feedback for student
    case 'get_student_feedback':
        if ($user_role != 'student') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            break;
        }
        
        $query = "SELECT 
                 c.course_name, c.course_code,
                 s.session_title, s.session_date,
                 CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                 a.notes, a.marked_at
                 FROM attendance a
                 JOIN sessions s ON a.session_id = s.id
                 JOIN courses c ON s.course_id = c.id
                 LEFT JOIN users u ON c.instructor_id = u.id
                 WHERE a.student_id = $user_id 
                 AND a.notes IS NOT NULL 
                 AND a.notes != ''
                 ORDER BY s.session_date DESC
                 LIMIT 20";
        
        $result = mysqli_query($conn, $query);
        $feedback = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $feedback[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $feedback]);
        break;
    
    // Get available courses for enrollment
    case 'get_available_courses':
        if ($user_role != 'student') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            break;
        }
        
        $query = "SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                  (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count
                  FROM courses c
                  LEFT JOIN users u ON c.instructor_id = u.id
                  WHERE c.status = 'active' 
                  AND c.id NOT IN (SELECT course_id FROM enrollments WHERE student_id = $user_id)
                  ORDER BY c.course_name";
        
        $result = mysqli_query($conn, $query);
        $courses = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $courses[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $courses]);
        break;
    
    // Enroll in course
    case 'enroll_course':
        if ($user_role != 'student') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            break;
        }
        
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        
        // Check if already enrolled
        $check = "SELECT id FROM enrollments WHERE student_id = $user_id AND course_id = $course_id";
        $check_result = mysqli_query($conn, $check);
        
        if (mysqli_num_rows($check_result) > 0) {
            echo json_encode(['success' => false, 'error' => 'Already enrolled']);
            break;
        }
        
        // Enroll
        $query = "INSERT INTO enrollments (student_id, course_id, status, enrolled_at) 
                 VALUES ($user_id, $course_id, 'active', NOW())";
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true, 'message' => 'Enrolled successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Enrollment failed']);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
?>