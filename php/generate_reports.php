<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

checkRole('admin');

$report_type = isset($_GET['type']) ? $_GET['type'] : 'attendance';
$course_filter = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
$date_from = isset($_POST['date_from']) ? $_POST['date_from'] : date('Y-m-01');
$date_to = isset($_POST['date_to']) ? $_POST['date_to'] : date('Y-m-d');

// Fetch courses for filter
$courses_query = "SELECT id, course_code, course_name FROM courses WHERE status = 'active' ORDER BY course_name";
$courses_result = mysqli_query($conn, $courses_query);

// Generate report based on type
$report_data = null;
$report_title = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    switch($report_type) {
        case 'attendance':
            $report_title = 'Attendance Report';
            
            if ($course_filter > 0) {
                $query = "SELECT 
                            c.course_code, c.course_name,
                            CONCAT(u.first_name, ' ', u.last_name) as student_name,
                            COUNT(a.id) as total_sessions,
                            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                            ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0)) * 100, 2) as attendance_percentage
                          FROM attendance a
                          JOIN sessions s ON a.session_id = s.id
                          JOIN courses c ON s.course_id = c.id
                          JOIN users u ON a.student_id = u.id
                          WHERE s.session_date BETWEEN ? AND ? AND s.course_id = ?
                          GROUP BY c.id, u.id
                          ORDER BY c.course_name, student_name";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ssi', $date_from, $date_to, $course_filter);
            } else {
                $query = "SELECT 
                            c.course_code, c.course_name,
                            CONCAT(u.first_name, ' ', u.last_name) as student_name,
                            COUNT(a.id) as total_sessions,
                            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                            ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0)) * 100, 2) as attendance_percentage
                          FROM attendance a
                          JOIN sessions s ON a.session_id = s.id
                          JOIN courses c ON s.course_id = c.id
                          JOIN users u ON a.student_id = u.id
                          WHERE s.session_date BETWEEN ? AND ?
                          GROUP BY c.id, u.id
                          ORDER BY c.course_name, student_name";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ss', $date_from, $date_to);
            }
            $stmt->execute();
            $report_data = $stmt->get_result();
            break;
            
        case 'performance':
            $report_title = 'Student Performance Report';
            
            if ($course_filter > 0) {
                $query = "SELECT 
                            c.course_code, c.course_name,
                            CONCAT(u.first_name, ' ', u.last_name) as student_name,
                            u.email,
                            (SELECT COUNT(*) FROM attendance a 
                             JOIN sessions s ON a.session_id = s.id 
                             WHERE a.student_id = u.id AND s.course_id = c.id AND a.status = 'present') as attended_sessions,
                            (SELECT COUNT(*) FROM sessions WHERE course_id = c.id) as total_sessions,
                            ROUND(((SELECT COUNT(*) FROM attendance a 
                                    JOIN sessions s ON a.session_id = s.id 
                                    WHERE a.student_id = u.id AND s.course_id = c.id AND a.status = 'present') / 
                                   NULLIF((SELECT COUNT(*) FROM sessions WHERE course_id = c.id), 0)) * 100, 2) as attendance_rate
                          FROM enrollments e
                          JOIN courses c ON e.course_id = c.id
                          JOIN users u ON e.student_id = u.id
                          WHERE e.status = 'active' AND e.course_id = ?
                          ORDER BY c.course_name, student_name";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $course_filter);
            } else {
                $query = "SELECT 
                            c.course_code, c.course_name,
                            CONCAT(u.first_name, ' ', u.last_name) as student_name,
                            u.email,
                            (SELECT COUNT(*) FROM attendance a 
                             JOIN sessions s ON a.session_id = s.id 
                             WHERE a.student_id = u.id AND s.course_id = c.id AND a.status = 'present') as attended_sessions,
                            (SELECT COUNT(*) FROM sessions WHERE course_id = c.id) as total_sessions,
                            ROUND(((SELECT COUNT(*) FROM attendance a 
                                    JOIN sessions s ON a.session_id = s.id 
                                    WHERE a.student_id = u.id AND s.course_id = c.id AND a.status = 'present') / 
                                   NULLIF((SELECT COUNT(*) FROM sessions WHERE course_id = c.id), 0)) * 100, 2) as attendance_rate
                          FROM enrollments e
                          JOIN courses c ON e.course_id = c.id
                          JOIN users u ON e.student_id = u.id
                          WHERE e.status = 'active'
                          ORDER BY c.course_name, student_name";
                $stmt = $conn->prepare($query);
            }
            $stmt->execute();
            $report_data = $stmt->get_result();
            break;
            
        case 'activity':
            $report_title = 'System Activity Log';
            $query = "SELECT 
                        DATE(created_at) as activity_date,
                        COUNT(*) as total_activities,
                        SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as student_registrations,
                        SUM(CASE WHEN role = 'faculty' THEN 1 ELSE 0 END) as faculty_registrations
                      FROM users
                      WHERE created_at BETWEEN ? AND ?
                      GROUP BY DATE(created_at)
                      ORDER BY activity_date DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $date_from, $date_to);
            $stmt->execute();
            $report_data = $stmt->get_result();
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
    <style>
        @media print {
            header nav, .no-print { display: none; }
            .content-box { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>
    <header>
        <h1>Generate System Reports</h1>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php">Back to Dashboard</a></li>
                <li><a href="?type=attendance" class="<?php echo $report_type == 'attendance' ? 'active' : ''; ?>">Attendance</a></li>
                <li><a href="?type=performance" class="<?php echo $report_type == 'performance' ? 'active' : ''; ?>">Performance</a></li>
                <li><a href="?type=activity" class="<?php echo $report_type == 'activity' ? 'active' : ''; ?>">Activity Log</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 1000px; margin: 0 auto;">
                <div class="card no-print">
                    <h3>Report Filters</h3>
                    <form method="POST" action="">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                            <div class="form-group">
                                <label for="course_id">Course (Optional)</label>
                                <select id="course_id" name="course_id">
                                    <option value="0">All Courses</option>
                                    <?php while($course = mysqli_fetch_assoc($courses_result)): ?>
                                        <option value="<?php echo $course['id']; ?>" 
                                                <?php echo ($course_filter == $course['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="date_from">From Date</label>
                                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>

                            <div class="form-group">
                                <label for="date_to">To Date</label>
                                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>

                            <div>
                                <button type="submit" class="success">Generate Report</button>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if ($report_data && mysqli_num_rows($report_data) > 0): ?>
                <div class="card mt-20">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3><?php echo $report_title; ?></h3>
                        <button onclick="window.print()" class="secondary no-print">Print Report</button>
                    </div>
                    
                    <p style="color: #666; margin-bottom: 15px;">
                        Report Period: <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
                    </p>

                    <?php if ($report_type == 'attendance'): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Student</th>
                                <th>Total Sessions</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                                <th>Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($report_data)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo $row['total_sessions']; ?></td>
                                <td><?php echo $row['present_count']; ?></td>
                                <td><?php echo $row['absent_count']; ?></td>
                                <td><?php echo $row['late_count']; ?></td>
                                <td><?php echo $row['attendance_percentage']; ?>%</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <?php elseif ($report_type == 'performance'): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Attended</th>
                                <th>Total Sessions</th>
                                <th>Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($report_data)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo $row['attended_sessions']; ?></td>
                                <td><?php echo $row['total_sessions']; ?></td>
                                <td><?php echo $row['attendance_rate']; ?>%</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <?php elseif ($report_type == 'activity'): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total Activities</th>
                                <th>Student Registrations</th>
                                <th>Faculty Registrations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($report_data)): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($row['activity_date'])); ?></td>
                                <td><?php echo $row['total_activities']; ?></td>
                                <td><?php echo $row['student_registrations']; ?></td>
                                <td><?php echo $row['faculty_registrations']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                <div class="alert alert-info mt-20">
                    No data found for the selected criteria.
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>