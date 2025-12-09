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
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$error = '';
$success = '';

// Verify course belongs to faculty (using prepared statement)
$course_stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$course_stmt->bind_param('ii', $course_id, $faculty_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();

if ($course_result->num_rows == 0) {
    $course_stmt->close();
    $dashboard = ($_SESSION['role'] == 'fi') ? 'Dashboard.html' : 'FDashboard.html';
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/$dashboard");
    exit();
}

$course = $course_result->fetch_assoc();
$course_stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $session_name = trim($_POST['session_name']);
    $session_date = trim($_POST['session_date']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $location = trim($_POST['location']);
    $allow_self_checkin = isset($_POST['allow_self_checkin']) ? 1 : 0;
    
    // Validation
    if (empty($session_name) || empty($session_date) || empty($start_time) || empty($end_time)) {
        $error = "Session name, date, and time are required!";
    } elseif (strtotime($end_time) <= strtotime($start_time)) {
        $error = "End time must be after start time!";
    } else {
        // Generate unique 6-digit attendance code
        do {
            $attendance_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $check_code = $conn->prepare("SELECT id FROM sessions WHERE attendance_code = ?");
            $check_code->bind_param('s', $attendance_code);
            $check_code->execute();
            $code_exists = $check_code->get_result()->num_rows > 0;
            $check_code->close();
        } while ($code_exists);
        
        // Calculate code expiration (15 minutes after session end)
        $code_expires_at = date('Y-m-d H:i:s', strtotime("$session_date $end_time") + (15 * 60));
        
        // Insert session (using prepared statement)
        $insert_stmt = $conn->prepare("INSERT INTO sessions (course_id, session_name, session_date, start_time, end_time, location, attendance_code, code_expires_at, allow_self_checkin, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())");
        $insert_stmt->bind_param('isssssssi', $course_id, $session_name, $session_date, $start_time, $end_time, $location, $attendance_code, $code_expires_at, $allow_self_checkin);
        
        if ($insert_stmt->execute()) {
            $success = "Session created successfully! Attendance Code: <strong>$attendance_code</strong>";
            $insert_stmt->close();
            $_POST = array();
        } else {
            $error = "Error creating session: " . $conn->error;
            $insert_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Session</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
</head>
<body>
    <header>
        <h1>Create New Session</h1>
        <nav>
            <ul>
                <li><a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/FDashboard.html">Back to Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 700px; margin: 0 auto;">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="card">
                    <h3>Course: <?php echo htmlspecialchars($course['course_name']); ?></h3>
                    <p style="color: #666;">Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
                </div>

                <form method="POST" action="" class="mt-20">
                    <div class="card">
                        <h3>Session Details</h3>
                        
                        <div class="form-group">
                            <label for="session_title">Session Title *</label>
                            <input type="text" id="session_title" name="session_title" 
                                   value="<?php echo isset($_POST['session_title']) ? htmlspecialchars($_POST['session_title']) : ''; ?>"
                                   placeholder="e.g., Introduction to HTML" required>
                        </div>

                        <div class="form-group">
                            <label for="session_date">Session Date *</label>
                            <input type="date" id="session_date" name="session_date" 
                                   value="<?php echo isset($_POST['session_date']) ? htmlspecialchars($_POST['session_date']) : date('Y-m-d'); ?>"
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="start_time">Start Time *</label>
                                <input type="time" id="start_time" name="start_time" 
                                       value="<?php echo isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : '09:00'; ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="end_time">End Time *</label>
                                <input type="time" id="end_time" name="end_time" 
                                       value="<?php echo isset($_POST['end_time']) ? htmlspecialchars($_POST['end_time']) : '11:00'; ?>"
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" 
                                   value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"
                                   placeholder="e.g., Room 101, Building A">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" 
                                      placeholder="Enter session description or topics to be covered"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <button type="submit" class="success">Create Session</button>
                        <a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/FDashboard.html"><button type="button" class="secondary">Cancel</button></a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>