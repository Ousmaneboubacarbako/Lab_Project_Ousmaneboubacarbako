<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

checkRole('student');

$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Verify student is enrolled in this course (using prepared statement)
$enrollment_stmt = $conn->prepare("SELECT e.*, c.course_name, c.course_code, c.credits
                     FROM enrollments e
                     JOIN courses c ON e.course_id = c.id
                     WHERE e.student_id = ? AND e.course_id = ?");
$enrollment_stmt->bind_param('ii', $student_id, $course_id);
$enrollment_stmt->execute();
$enrollment_result = $enrollment_stmt->get_result();

if ($enrollment_result->num_rows == 0) {
    $enrollment_stmt->close();
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/StuDashboard.html");
    exit();
}

$course = $enrollment_result->fetch_assoc();
$enrollment_stmt->close();

// Get attendance records for this course
$attendance_query = "SELECT s.session_title, s.session_date, s.start_time, s.location,
                     a.status, a.notes, a.marked_at
                     FROM attendance a
                     JOIN sessions s ON a.session_id = s.id
                     WHERE a.student_id = $student_id AND s.course_id = $course_id
                     ORDER BY s.session_date DESC";
$attendance_result = mysqli_query($conn, $attendance_query);

// Get attendance statistics
$stats_query = "SELECT 
                COUNT(a.id) as total_sessions,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused_count,
                ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as attendance_rate
                FROM attendance a
                JOIN sessions s ON a.session_id = s.id
                WHERE a.student_id = $student_id AND s.course_id = $course_id";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades & Attendance</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
    <style>
        .status-present { color: #28a745; font-weight: bold; }
        .status-absent { color: #dc3545; font-weight: bold; }
        .status-late { color: #ffc107; font-weight: bold; }
        .status-excused { color: #17a2b8; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <h1>Course Grades & Attendance</h1>
        <nav>
            <ul>
                <li><a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/StuDashboard.html">Back to Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 1000px; margin: 0 auto;">
                <div class="card">
                    <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                    <p>
                        <strong>Course Code:</strong> <?php echo htmlspecialchars($course['course_code']); ?> | 
                        <strong>Credits:</strong> <?php echo $course['credits']; ?>
                    </p>
                </div>

                <!-- Attendance Statistics -->
                <div class="card mt-20">
                    <h3>Attendance Summary</h3>
                    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <div>
                            <strong>Total Sessions:</strong><br>
                            <?php echo $stats['total_sessions'] ?? 0; ?>
                        </div>
                        <div>
                            <strong>Present:</strong><br>
                            <span class="status-present"><?php echo $stats['present_count'] ?? 0; ?></span>
                        </div>
                        <div>
                            <strong>Absent:</strong><br>
                            <span class="status-absent"><?php echo $stats['absent_count'] ?? 0; ?></span>
                        </div>
                        <div>
                            <strong>Late:</strong><br>
                            <span class="status-late"><?php echo $stats['late_count'] ?? 0; ?></span>
                        </div>
                        <div>
                            <strong>Attendance Rate:</strong><br>
                            <span style="color: <?php echo (($stats['attendance_rate'] ?? 0) >= 75) ? 'green' : 'red'; ?>; font-weight: bold;">
                                <?php echo $stats['attendance_rate'] ?? 0; ?>%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Detailed Attendance Records -->
                <div class="card mt-20">
                    <h3>Attendance History</h3>
                    <?php if (mysqli_num_rows($attendance_result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Session Title</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($record = mysqli_fetch_assoc($attendance_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['session_title']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($record['session_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($record['start_time'])); ?></td>
                                <td><?php echo htmlspecialchars($record['location']); ?></td>
                                <td>
                                    <span class="status-<?php echo $record['status']; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($record['notes'] ?? ''); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="alert alert-info">No attendance records available yet.</div>
                    <?php endif; ?>
                </div>

                <!-- Performance Analysis -->
                <?php if ($stats['total_sessions'] > 0): ?>
                <div class="card mt-20">
                    <h3>Performance Analysis</h3>
                    <div style="padding: 15px;">
                        <?php 
                        $rate = $stats['attendance_rate'];
                        if ($rate >= 90):
                        ?>
                        <div class="alert alert-success">
                            <strong>Excellent Performance!</strong> Your attendance rate is outstanding. Keep up the great work!
                        </div>
                        <?php elseif ($rate >= 75): ?>
                        <div class="alert alert-info">
                            <strong>Good Performance!</strong> You're meeting the attendance requirements. Try to maintain this level.
                        </div>
                        <?php elseif ($rate >= 60): ?>
                        <div class="alert alert-warning">
                            <strong>Needs Improvement.</strong> Your attendance is below the recommended level. Please try to attend more sessions.
                        </div>
                        <?php else: ?>
                        <div class="alert alert-error">
                            <strong>Critical!</strong> Your attendance is significantly below requirements. Please speak with your instructor.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>