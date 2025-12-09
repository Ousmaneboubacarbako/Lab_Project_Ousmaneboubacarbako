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

// Get all sessions for this course (using prepared statement)
$sessions_stmt = $conn->prepare("SELECT s.*, 
                   COUNT(a.id) as marked_count,
                   (SELECT COUNT(*) FROM enrollments WHERE course_id = s.course_id AND status = 'active') as total_students
                   FROM sessions s
                   LEFT JOIN attendance a ON s.id = a.session_id
                   WHERE s.course_id = ?
                   GROUP BY s.id
                   ORDER BY s.session_date DESC, s.start_time DESC");
$sessions_stmt->bind_param('i', $course_id);
$sessions_stmt->execute();
$sessions_result = $sessions_stmt->get_result();

// Get attendance summary by student (using prepared statement)
$summary_stmt = $conn->prepare("SELECT u.id, u.first_name, u.last_name, u.email,
                  COUNT(a.id) as total_sessions,
                  SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                  SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                  SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                  SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused_count,
                  ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0)) * 100, 1) as attendance_rate
                  FROM enrollments e
                  JOIN users u ON e.student_id = u.id
                  LEFT JOIN attendance a ON (a.student_id = u.id AND a.session_id IN 
                      (SELECT id FROM sessions WHERE course_id = ?))
                  WHERE e.course_id = ? AND e.status = 'active'
                  GROUP BY u.id
                  ORDER BY u.last_name, u.first_name");
$summary_stmt->bind_param('ii', $course_id, $course_id);
$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
</head>
<body>
    <header>
        <h1>Course Attendance Records</h1>
        <nav>
            <ul>
                <li><a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/<?php echo ($_SESSION['role'] == 'fi') ? 'Dashboard.html' : 'FDashboard.html'; ?>">Back to Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 1200px; margin: 0 auto;">
                <div class="card">
                    <h3>Course Information</h3>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></p>
                    <p><strong>Semester:</strong> <?php echo htmlspecialchars($course['semester'] . ' ' . $course['academic_year']); ?></p>
                </div>

                <!-- Student Attendance Summary -->
                <div class="card mt-20">
                    <h3>Student Attendance Summary</h3>
                    <?php if (mysqli_num_rows($summary_result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Total Sessions</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                                <th>Excused</th>
                                <th>Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($student = $summary_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo $student['total_sessions']; ?></td>
                                <td><?php echo $student['present_count']; ?></td>
                                <td><?php echo $student['absent_count']; ?></td>
                                <td><?php echo $student['late_count']; ?></td>
                                <td><?php echo $student['excused_count']; ?></td>
                                <td>
                                    <span style="color: <?php echo ($student['attendance_rate'] >= 75) ? 'green' : 'red'; ?>;">
                                        <?php echo $student['attendance_rate'] ?? 0; ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="alert alert-info">No students enrolled yet.</div>
                    <?php endif; ?>
                </div>

                <!-- Session List -->
                <div class="card mt-20">
                    <h3>Session Records</h3>
                    <?php if ($sessions_result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Session Title</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Marked</th>
                                <th>Total Students</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($session = $sessions_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($session['session_name'] ?? 'Class Session'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($session['session_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($session['start_time'])); ?></td>
                                <td><?php echo htmlspecialchars($session['location']); ?></td>
                                <td><?php echo $session['marked_count']; ?></td>
                                <td><?php echo $session['total_students']; ?></td>
                                <td>
                                    <a href="mark_attendance.php?session_id=<?php echo $session['id']; ?>">
                                        <button style="padding: 6px 12px;">
                                            <?php echo ($session['marked_count'] > 0) ? 'Edit' : 'Mark'; ?>
                                        </button>
                                    </a>
                                    <a href="manage_session_codes.php?session_id=<?php echo $session['id']; ?>">
                                        <button class="secondary" style="padding: 6px 12px;">View Code</button>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="alert alert-info">No sessions created yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>