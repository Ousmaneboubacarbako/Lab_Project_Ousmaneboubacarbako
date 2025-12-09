<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

// Allow faculty and faculty intern to mark attendance
if (!isLoggedIn()) {
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/Login.html");
    exit();
}

if ($_SESSION['role'] !== 'faculty' && $_SESSION['role'] !== 'fi' && $_SESSION['role'] !== 'admin') {
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/StuDashboard.html");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
$success = '';
$error = '';

// Verify session belongs to faculty (using prepared statement)
$session_stmt = $conn->prepare("SELECT s.*, c.course_name, c.course_code 
                  FROM sessions s
                  JOIN courses c ON s.course_id = c.id
                  WHERE s.id = ? AND c.instructor_id = ?");
$session_stmt->bind_param('ii', $session_id, $faculty_id);
$session_stmt->execute();
$session_result = $session_stmt->get_result();

if ($session_result->num_rows == 0) {
    $session_stmt->close();
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/FDashboard.html");
    exit();
}

$session = $session_result->fetch_assoc();
$session_stmt->close();

// Get enrolled students (using prepared statement)
$students_stmt = $conn->prepare("SELECT u.id, u.first_name, u.last_name, u.email,
                   a.status as attendance_status, a.id as attendance_id
                   FROM enrollments e
                   JOIN users u ON e.student_id = u.id
                   LEFT JOIN attendance a ON (a.student_id = u.id AND a.session_id = ?)
                   WHERE e.course_id = ? AND e.status = 'active'
                   ORDER BY u.last_name, u.first_name");
$students_stmt->bind_param('ii', $session_id, $session['course_id']);
$students_stmt->execute();
$students_result = $students_stmt->get_result();

// Process attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $attendance_data = $_POST['attendance'];
    $notes = $_POST['notes'];
    
    foreach ($attendance_data as $student_id => $status) {
        $student_id = intval($student_id);
        $note = isset($notes[$student_id]) ? trim($notes[$student_id]) : '';
        
        // Check if attendance record exists (using prepared statement)
        $check_stmt = $conn->prepare("SELECT id FROM attendance WHERE session_id = ? AND student_id = ?");
        $check_stmt->bind_param('ii', $session_id, $student_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing record
            $att_row = $check_result->fetch_assoc();
            $att_id = $att_row['id'];
            $check_stmt->close();
            
            $update_stmt = $conn->prepare("UPDATE attendance SET status = ?, notes = ?, marked_at = NOW() WHERE id = ?");
            $update_stmt->bind_param('ssi', $status, $note, $att_id);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            $check_stmt->close();
            // Insert new record
            $insert_stmt = $conn->prepare("INSERT INTO attendance (session_id, student_id, status, notes, marked_at) 
                           VALUES (?, ?, ?, ?, NOW())");
            $insert_stmt->bind_param('iiss', $session_id, $student_id, $status, $note);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
    }
    
    $success = "Attendance marked successfully!";
    
    // Refresh student data
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
    <script src="../js/auth_check.js"></script>
    <style>
        .attendance-radio { margin: 0 5px; }
        .alert { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <header>
        <h1>Mark Attendance</h1>
        <nav>
            <ul>
                <li><a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/<?php echo ($_SESSION['role'] == 'fi') ? 'Dashboard.html' : 'FDashboard.html'; ?>">Back to Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 1000px; margin: 0 auto;">
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <h3>Session Details</h3>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($session['course_code'] . ' - ' . $session['course_name']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('l, F d, Y', strtotime($session['session_date'])); ?></p>
                    <?php if($session['start_time'] && $session['end_time']): ?>
                    <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($session['start_time'])); ?> - <?php echo date('h:i A', strtotime($session['end_time'])); ?></p>
                    <?php endif; ?>
                    <?php if($session['location']): ?>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($session['location']); ?></p>
                    <?php endif; ?>
                </div>

                <form method="POST" action="">
                    <div class="card mt-20">
                        <h3>Student Attendance</h3>
                        
                        <?php if ($students_result->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Excused</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($student = $students_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td>
                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                               value="present" class="attendance-radio"
                                               <?php echo ($student['attendance_status'] == 'present') ? 'checked' : ''; ?>>
                                    </td>
                                    <td>
                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                               value="absent" class="attendance-radio"
                                               <?php echo ($student['attendance_status'] == 'absent' || !$student['attendance_status']) ? 'checked' : ''; ?>>
                                    </td>
                                    <td>
                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                               value="late" class="attendance-radio"
                                               <?php echo ($student['attendance_status'] == 'late') ? 'checked' : ''; ?>>
                                    </td>
                                    <td>
                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                               value="excused" class="attendance-radio"
                                               <?php echo ($student['attendance_status'] == 'excused') ? 'checked' : ''; ?>>
                                    </td>
                                    <td>
                                        <input type="text" name="notes[<?php echo $student['id']; ?>]" 
                                               style="width: 150px;" placeholder="Optional notes">
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 20px;">
                            <button type="submit" class="success">Save Attendance</button>
                            <a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/<?php echo ($_SESSION['role'] == 'fi') ? 'Dashboard.html' : 'FDashboard.html'; ?>"><button type="button" class="secondary">Cancel</button></a>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;">No students enrolled in this course.</div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>