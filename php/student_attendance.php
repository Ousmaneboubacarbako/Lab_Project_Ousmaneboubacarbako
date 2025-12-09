<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

checkRole('student');

$student_id = $_SESSION['user_id'];

// Get all courses the student is enrolled in
$courses_stmt = $conn->prepare("SELECT c.id, c.course_code, c.course_name, c.credits,
                                 CONCAT(u.first_name, ' ', u.last_name) as instructor_name
                                 FROM enrollments e
                                 JOIN courses c ON e.course_id = c.id
                                 LEFT JOIN users u ON c.instructor_id = u.id
                                 WHERE e.student_id = ? AND e.status = 'active'
                                 ORDER BY c.course_name");
$courses_stmt->bind_param('i', $student_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance Records</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
    <script src="../js/auth_check.js"></script>
    <style>
        .status-present { color: #28a745; font-weight: bold; }
        .status-absent { color: #dc3545; font-weight: bold; }
        .status-late { color: #ffc107; font-weight: bold; }
        .status-excused { color: #17a2b8; font-weight: bold; }
        .course-section {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <header>
        <h1>My Attendance Records</h1>
        <nav>
            <ul>
                <li><a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/StuDashboard.html">Back to Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 1200px; margin: 0 auto;">
                <?php if ($courses_result->num_rows == 0): ?>
                    <div class="alert alert-info">
                        <strong>No Enrollments</strong><br>
                        You are not currently enrolled in any courses.
                    </div>
                <?php else: ?>
                    <?php while($course = $courses_result->fetch_assoc()): 
                        $course_id = $course['id'];
                        
                        // Get attendance statistics for this course
                        $stats_stmt = $conn->prepare("SELECT 
                            COUNT(a.id) as total_sessions,
                            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                            SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused_count,
                            ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0)) * 100, 1) as attendance_rate
                            FROM attendance a
                            JOIN sessions s ON a.session_id = s.id
                            WHERE a.student_id = ? AND s.course_id = ?");
                        $stats_stmt->bind_param('ii', $student_id, $course_id);
                        $stats_stmt->execute();
                        $stats_result = $stats_stmt->get_result();
                        $stats = $stats_result->fetch_assoc();
                        $stats_stmt->close();
                        
                        // Get detailed attendance records
                        $attendance_stmt = $conn->prepare("SELECT s.session_title, s.session_date, s.start_time, s.location,
                                     a.status, a.notes, a.marked_at
                                     FROM attendance a
                                     JOIN sessions s ON a.session_id = s.id
                                     WHERE a.student_id = ? AND s.course_id = ?
                                     ORDER BY s.session_date DESC");
                        $attendance_stmt->bind_param('ii', $student_id, $course_id);
                        $attendance_stmt->execute();
                        $attendance_result = $attendance_stmt->get_result();
                    ?>
                    <div class="course-section">
                        <div class="card">
                            <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                            <p>
                                <strong>Course Code:</strong> <?php echo htmlspecialchars($course['course_code']); ?> | 
                                <strong>Credits:</strong> <?php echo $course['credits']; ?> |
                                <strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?>
                            </p>
                        </div>

                        <?php if ($stats['total_sessions'] > 0): ?>
                            <!-- Attendance Statistics -->
                            <div class="card mt-20">
                                <h3>Attendance Summary</h3>
                                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                    <div>
                                        <strong>Total Sessions:</strong><br>
                                        <?php echo $stats['total_sessions']; ?>
                                    </div>
                                    <div>
                                        <strong>Present:</strong><br>
                                        <span class="status-present"><?php echo $stats['present_count']; ?></span>
                                    </div>
                                    <div>
                                        <strong>Absent:</strong><br>
                                        <span class="status-absent"><?php echo $stats['absent_count']; ?></span>
                                    </div>
                                    <div>
                                        <strong>Late:</strong><br>
                                        <span class="status-late"><?php echo $stats['late_count']; ?></span>
                                    </div>
                                    <div>
                                        <strong>Attendance Rate:</strong><br>
                                        <span style="color: <?php echo ($stats['attendance_rate'] >= 75) ? 'green' : 'red'; ?>; font-weight: bold;">
                                            <?php echo $stats['attendance_rate'] ?? 0; ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Detailed Attendance Records -->
                            <div class="card mt-20">
                                <h3>Attendance History</h3>
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
                                        <?php while($record = $attendance_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['session_title']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($record['session_date'])); ?></td>
                                            <td><?php echo date('g:i A', strtotime($record['start_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($record['location'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="status-<?php echo $record['status']; ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['notes'] ?? '-'); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mt-20">
                                No attendance records available for this course yet.
                            </div>
                        <?php endif; ?>
                        <?php $attendance_stmt->close(); ?>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
                <?php $courses_stmt->close(); ?>
            </div>
        </section>
    </main>
</body>
</html>
