<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

// Allow faculty and faculty intern
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

// Verify session belongs to faculty
$session_stmt = $conn->prepare("SELECT s.*, c.course_name, c.course_code 
                  FROM sessions s
                  JOIN courses c ON s.course_id = c.id
                  WHERE s.id = ? AND c.instructor_id = ?");
$session_stmt->bind_param('ii', $session_id, $faculty_id);
$session_stmt->execute();
$session_result = $session_stmt->get_result();

if ($session_result->num_rows == 0) {
    $session_stmt->close();
    $dashboard = ($_SESSION['role'] == 'fi') ? 'Dashboard.html' : 'FDashboard.html';
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/$dashboard");
    exit();
}

$session = $session_result->fetch_assoc();
$session_stmt->close();

// Handle code regeneration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['regenerate_code'])) {
    // Generate new unique code
    do {
        $new_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $check_code = $conn->prepare("SELECT id FROM sessions WHERE attendance_code = ? AND id != ?");
        $check_code->bind_param('si', $new_code, $session_id);
        $check_code->execute();
        $code_exists = $check_code->get_result()->num_rows > 0;
        $check_code->close();
    } while ($code_exists);
    
    // Update session with new code
    $update_stmt = $conn->prepare("UPDATE sessions SET attendance_code = ? WHERE id = ?");
    $update_stmt->bind_param('si', $new_code, $session_id);
    
    if ($update_stmt->execute()) {
        $success = "New attendance code generated: <strong>$new_code</strong>";
        $session['attendance_code'] = $new_code;
        $update_stmt->close();
    } else {
        $error = "Error generating new code.";
        $update_stmt->close();
    }
}

// Handle toggle self check-in
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_checkin'])) {
    $new_status = $session['allow_self_checkin'] ? 0 : 1;
    $toggle_stmt = $conn->prepare("UPDATE sessions SET allow_self_checkin = ? WHERE id = ?");
    $toggle_stmt->bind_param('ii', $new_status, $session_id);
    
    if ($toggle_stmt->execute()) {
        $success = "Self check-in " . ($new_status ? "enabled" : "disabled") . " successfully!";
        $session['allow_self_checkin'] = $new_status;
        $toggle_stmt->close();
    } else {
        $error = "Error updating settings.";
        $toggle_stmt->close();
    }
}

// Get attendance statistics
$stats_stmt = $conn->prepare("SELECT 
                COUNT(*) as total_marked,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN notes LIKE '%Self check-in%' THEN 1 ELSE 0 END) as self_checkin_count
                FROM attendance WHERE session_id = ?");
$stats_stmt->bind_param('i', $session_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// Get total enrolled students
$enrolled_stmt = $conn->prepare("SELECT COUNT(*) as total FROM enrollments 
                   WHERE course_id = ? AND status = 'active'");
$enrolled_stmt->bind_param('i', $session['course_id']);
$enrolled_stmt->execute();
$total_students = $enrolled_stmt->get_result()->fetch_assoc()['total'];
$enrolled_stmt->close();

$code_expired = strtotime($session['code_expires_at']) < time();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Session Code</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
    <style>
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .code-display {
            font-size: 48px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 10px;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .code-expired {
            background: #6c757d;
            opacity: 0.6;
        }
        .stat-box {
            display: inline-block;
            padding: 15px 25px;
            margin: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .toggle-switch {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Manage Session Attendance Code</h1>
        <nav>
            <ul>
                <li><a href="mark_attendance.php?session_id=<?php echo $session_id; ?>">Mark Attendance</a></li>
                <li><a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/<?php echo ($_SESSION['role'] == 'fi') ? 'Dashboard.html' : 'FDashboard.html'; ?>">Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 900px; margin: 0 auto;">
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="card">
                    <h3>Session Information</h3>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($session['course_code'] . ' - ' . $session['course_name']); ?></p>
                    <p><strong>Session:</strong> <?php echo htmlspecialchars($session['session_name']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('l, F d, Y', strtotime($session['session_date'])); ?></p>
                    <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></p>
                    <?php if($session['location']): ?>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($session['location']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="card mt-20">
                    <h3>Attendance Code</h3>
                    
                    <div class="code-display <?php echo $code_expired ? 'code-expired' : ''; ?>">
                        <?php echo htmlspecialchars($session['attendance_code']); ?>
                    </div>
                    
                    <?php if ($code_expired): ?>
                        <p style="text-align: center; color: #dc3545; font-weight: bold;">
                            ⚠️ This code has expired
                        </p>
                    <?php else: ?>
                        <p style="text-align: center; color: #666;">
                            Valid until: <?php echo date('M d, Y g:i A', strtotime($session['code_expires_at'])); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="regenerate_code" class="secondary">
                                🔄 Generate New Code
                            </button>
                        </form>
                        
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="toggle_checkin" class="<?php echo $session['allow_self_checkin'] ? 'danger' : 'success'; ?>">
                                <?php echo $session['allow_self_checkin'] ? '🔒 Disable' : '✓ Enable'; ?> Self Check-in
                            </button>
                        </form>
                    </div>
                    
                    <p style="margin-top: 15px; padding: 12px; background: <?php echo $session['allow_self_checkin'] ? '#d4edda' : '#f8d7da'; ?>; border-radius: 6px; text-align: center;">
                        Self Check-in is currently <strong><?php echo $session['allow_self_checkin'] ? 'ENABLED' : 'DISABLED'; ?></strong>
                    </p>
                </div>

                <div class="card mt-20">
                    <h3>Attendance Statistics</h3>
                    
                    <div style="text-align: center;">
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['total_marked']; ?> / <?php echo $total_students; ?></div>
                            <div class="stat-label">Marked</div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-number" style="color: #28a745;"><?php echo $stats['present_count']; ?></div>
                            <div class="stat-label">Present</div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-number" style="color: #dc3545;"><?php echo $stats['absent_count']; ?></div>
                            <div class="stat-label">Absent</div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-number" style="color: #17a2b8;"><?php echo $stats['self_checkin_count']; ?></div>
                            <div class="stat-label">Self Check-ins</div>
                        </div>
                    </div>
                </div>

                <div class="card mt-20" style="background: #f8f9fa;">
                    <h4 style="margin-top: 0;">📌 Instructions for Students:</h4>
                    <ol style="color: #666; line-height: 1.8;">
                        <li>Share the 6-digit code: <strong><?php echo htmlspecialchars($session['attendance_code']); ?></strong></li>
                        <li>Students can mark attendance at: <a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/php/student_checkin.php" target="_blank">Student Check-in Page</a></li>
                        <li>Code is valid until: <?php echo date('M d, g:i A', strtotime($session['code_expires_at'])); ?></li>
                        <li>Students must be enrolled in the course to use the code</li>
                    </ol>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
