<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

// Only students can access
if (!isLoggedIn()) {
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/Login.html");
    exit();
}

if ($_SESSION['role'] !== 'student') {
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/StuDashboard.html");
    exit();
}

$student_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle code submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['attendance_code'])) {
    $code = trim($_POST['attendance_code']);
    
    if (empty($code)) {
        $error = "Please enter an attendance code!";
    } else {
        // Find active session with this code
        $session_stmt = $conn->prepare("SELECT s.*, c.course_name, c.course_code, c.id as course_id
                         FROM sessions s
                         JOIN courses c ON s.course_id = c.id
                         WHERE s.attendance_code = ? 
                         AND s.allow_self_checkin = 1
                         AND s.code_expires_at > NOW()
                         AND s.status IN ('scheduled', 'ongoing')");
        $session_stmt->bind_param('s', $code);
        $session_stmt->execute();
        $session_result = $session_stmt->get_result();
        
        if ($session_result->num_rows == 0) {
            $error = "Invalid or expired attendance code!";
            $session_stmt->close();
        } else {
            $session = $session_result->fetch_assoc();
            $session_stmt->close();
            
            // Check if student is enrolled in the course
            $enroll_check = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ? AND status = 'active'");
            $enroll_check->bind_param('ii', $student_id, $session['course_id']);
            $enroll_check->execute();
            $enroll_result = $enroll_check->get_result();
            
            if ($enroll_result->num_rows == 0) {
                $error = "You are not enrolled in this course!";
                $enroll_check->close();
            } else {
                $enroll_check->close();
                
                // Check if already marked attendance
                $att_check = $conn->prepare("SELECT id, status FROM attendance WHERE session_id = ? AND student_id = ?");
                $att_check->bind_param('ii', $session['id'], $student_id);
                $att_check->execute();
                $att_result = $att_check->get_result();
                
                if ($att_result->num_rows > 0) {
                    $existing = $att_result->fetch_assoc();
                    $success = "You already marked your attendance for this session as: <strong>" . ucfirst($existing['status']) . "</strong>";
                    $att_check->close();
                } else {
                    $att_check->close();
                    
                    // Mark attendance as present
                    $mark_stmt = $conn->prepare("INSERT INTO attendance (session_id, student_id, status, marked_at, notes) 
                                  VALUES (?, ?, 'present', NOW(), 'Self check-in')");
                    $mark_stmt->bind_param('ii', $session['id'], $student_id);
                    
                    if ($mark_stmt->execute()) {
                        $success = "✓ Attendance marked successfully for <strong>" . htmlspecialchars($session['course_code']) . " - " . htmlspecialchars($session['course_name']) . "</strong><br>";
                        $success .= "Session: " . htmlspecialchars($session['session_name']) . "<br>";
                        $success .= "Date: " . date('F d, Y', strtotime($session['session_date']));
                        $mark_stmt->close();
                    } else {
                        $error = "Error marking attendance. Please try again.";
                        $mark_stmt->close();
                    }
                }
            }
        }
    }
}

// Get today's sessions for enrolled courses
$today = date('Y-m-d');
$today_sessions = $conn->prepare("SELECT s.*, c.course_name, c.course_code,
                    (SELECT status FROM attendance WHERE session_id = s.id AND student_id = ?) as my_status
                    FROM sessions s
                    JOIN courses c ON s.course_id = c.id
                    JOIN enrollments e ON (e.course_id = c.id AND e.student_id = ? AND e.status = 'active')
                    WHERE s.session_date = ?
                    AND s.allow_self_checkin = 1
                    AND s.code_expires_at > NOW()
                    ORDER BY s.start_time");
$today_sessions->bind_param('iis', $student_id, $student_id, $today);
$today_sessions->execute();
$sessions_result = $today_sessions->get_result();

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
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            font-size: 14px;
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
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .code-input {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 5px;
            padding: 15px;
            border: 2px solid #667eea;
        }
        .session-card {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-present {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <header>
        <h1>Mark Attendance</h1>
        <nav>
            <ul>
                <li><a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/StuDashboard.html">Back to Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 800px; margin: 0 auto;">
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="card">
                    <h3>Enter Attendance Code</h3>
                    <p style="color: #666; margin-bottom: 20px;">
                        Enter the 6-digit code provided by your instructor to mark your attendance.
                    </p>
                    
                    <form method="POST" action="">
                        <div class="input-group">
                            <input type="text" 
                                   name="attendance_code" 
                                   class="code-input"
                                   placeholder="000000"
                                   maxlength="6"
                                   pattern="[0-9]{6}"
                                   required
                                   autofocus>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <button type="submit" class="success" style="width: 100%; padding: 15px; font-size: 16px;">
                                ✓ Mark My Attendance
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Today's Sessions -->
                <div class="card mt-20">
                    <h3>Today's Sessions</h3>
                    
                    <?php if ($sessions_result->num_rows > 0): ?>
                        <?php while($session = $sessions_result->fetch_assoc()): ?>
                            <div class="session-card">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <h4 style="margin: 0 0 5px 0;">
                                            <?php echo htmlspecialchars($session['course_code']); ?> - 
                                            <?php echo htmlspecialchars($session['course_name']); ?>
                                        </h4>
                                        <p style="margin: 5px 0; color: #666;">
                                            <strong><?php echo htmlspecialchars($session['session_name']); ?></strong>
                                        </p>
                                        <p style="margin: 5px 0; color: #666;">
                                            ⏰ <?php echo date('g:i A', strtotime($session['start_time'])); ?> - 
                                            <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                                        </p>
                                        <?php if($session['location']): ?>
                                        <p style="margin: 5px 0; color: #666;">
                                            📍 <?php echo htmlspecialchars($session['location']); ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if($session['my_status']): ?>
                                            <span class="status-badge status-present">
                                                ✓ <?php echo ucfirst($session['my_status']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No sessions scheduled for today in your enrolled courses.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card mt-20" style="background: #f8f9fa;">
                    <h4 style="margin-top: 0;">📌 Instructions:</h4>
                    <ul style="color: #666; line-height: 1.8;">
                        <li>Ask your instructor for the 6-digit attendance code</li>
                        <li>Enter the code in the field above</li>
                        <li>Codes are valid for 15 minutes after the session ends</li>
                        <li>You can only mark attendance for courses you're enrolled in</li>
                        <li>Once marked, you cannot change your attendance status</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
