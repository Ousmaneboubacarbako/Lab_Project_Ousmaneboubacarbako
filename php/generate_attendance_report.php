<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

checkRole('faculty');

$faculty_id = $_SESSION['user_id'];
$course_name = isset($_GET['course_name']) ? $_GET['course_name'] : '';

// Get course details (using prepared statement)
$course_stmt = $conn->prepare("SELECT * FROM courses 
                 WHERE course_name = ? AND instructor_id = ?");
$course_stmt->bind_param('si', $course_name, $faculty_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();

if ($course_result->num_rows == 0) {
    $course_stmt->close();
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/FDashboard.html");
    exit();
}

$course = $course_result->fetch_assoc();
$course_stmt->close();
$course_id = $course['id'];

// Date filter
$date_from = isset($_POST['date_from']) ? $_POST['date_from'] : date('Y-m-01');
$date_to = isset($_POST['date_to']) ? $_POST['date_to'] : date('Y-m-d');

// Get detailed attendance data (using prepared statement)
$attendance_stmt = $conn->prepare("SELECT 
    u.first_name, u.last_name, u.email,
    s.session_name, s.session_date, s.start_time,
    a.status, a.notes, a.marked_at
    FROM attendance a
    JOIN sessions s ON a.session_id = s.id
    JOIN users u ON a.student_id = u.id
    WHERE s.course_id = ?
    AND s.session_date BETWEEN ? AND ?
    ORDER BY s.session_date DESC, u.last_name, u.first_name");
$attendance_stmt->bind_param('iss', $course_id, $date_from, $date_to);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

// Get summary statistics (using prepared statement)
$stats_stmt = $conn->prepare("SELECT 
    COUNT(DISTINCT a.student_id) as total_students,
    COUNT(a.id) as total_records,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
    SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused_count,
    ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0)) * 100, 1) as overall_attendance_rate
    FROM attendance a
    JOIN sessions s ON a.session_id = s.id
    WHERE s.course_id = ?
    AND s.session_date BETWEEN ? AND ?");
$stats_stmt->bind_param('iss', $course_id, $date_from, $date_to);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
    <style>
        @media print {
            header nav, .no-print { display: none; }
            .content-box { box-shadow: none; border: none; }
        }
        .status-present { color: #28a745; font-weight: bold; }
        .status-absent { color: #dc3545; font-weight: bold; }
        .status-late { color: #ffc107; font-weight: bold; }
        .status-excused { color: #17a2b8; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <h1>Detailed Attendance Report</h1>
        <nav>
            <ul>
                <li><a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/FDashboard.html">Back to Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 1200px; margin: 0 auto;">
                <div class="card no-print">
                    <h3>Report Filters</h3>
                    <form method="POST" action="">
                        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                            <div class="form-group">
                                <label for="date_from">From Date</label>
                                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="form-group">
                                <label for="date_to">To Date</label>
                                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div>
                                <button type="submit" class="success">Update Report</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card mt-20">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h3><?php echo htmlspecialchars($course['course_name']); ?> - Attendance Report</h3>
                            <p style="color: #666;">
                                <?php echo htmlspecialchars($course['course_code']); ?> | 
                                Period: <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
                            </p>
                        </div>
                        <button onclick="window.print()" class="secondary no-print">Print Report</button>
                    </div>

                    <!-- Summary Statistics -->
                    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 25px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <div>
                            <strong>Total Students:</strong><br>
                            <?php echo $stats['total_students'] ?? 0; ?>
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
                            <strong>Overall Rate:</strong><br>
                            <?php echo $stats['overall_attendance_rate'] ?? 0; ?>%
                        </div>
                    </div>

                    <!-- Detailed Records -->
                    <?php if ($attendance_result->num_rows > 0): ?>
                    <h4>Detailed Attendance Records</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Session</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($record = $attendance_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['email']); ?></td>
                                <td><?php echo htmlspecialchars($record['session_name'] ?? 'Class Session'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($record['session_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($record['start_time'])); ?></td>
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
                    <div class="alert alert-info">No attendance records found for the selected period.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>