<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

checkRole('faculty');

$faculty_id = $_SESSION['user_id'];

// Get faculty's courses for filter
$courses_query = "SELECT id, course_code, course_name FROM courses 
                  WHERE instructor_id = $faculty_id AND status = 'active' 
                  ORDER BY course_name";
$courses_result = mysqli_query($conn, $courses_query);

$selected_course = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Get student performance data
if ($selected_course > 0) {
    $performance_query = "SELECT 
        u.id, u.first_name, u.last_name, u.email,
        c.course_name, c.course_code,
        COUNT(DISTINCT s.id) as total_sessions,
        COUNT(a.id) as attended_sessions,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused_count,
        ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(DISTINCT s.id)) * 100, 1) as attendance_percentage,
        CASE 
            WHEN (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(DISTINCT s.id)) >= 0.90 THEN 'Excellent'
            WHEN (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(DISTINCT s.id)) >= 0.75 THEN 'Good'
            WHEN (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(DISTINCT s.id)) >= 0.60 THEN 'Fair'
            ELSE 'Poor'
        END as performance_level
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN sessions s ON s.course_id = c.id
        LEFT JOIN attendance a ON (a.student_id = u.id AND a.session_id = s.id)
        WHERE c.id = $selected_course AND c.instructor_id = $faculty_id AND e.status = 'active'
        GROUP BY u.id, c.id
        ORDER BY attendance_percentage DESC, u.last_name";
    $performance_result = mysqli_query($conn, $performance_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Performance</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
    <style>
        .performance-excellent { color: #28a745; font-weight: bold; }
        .performance-good { color: #17a2b8; font-weight: bold; }
        .performance-fair { color: #ffc107; font-weight: bold; }
        .performance-poor { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <h1>Student Performance & Participation</h1>
        <nav>
            <ul>
                <li><a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/FDashboard.html">Back to Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 1200px; margin: 0 auto;">
                <div class="card">
                    <h3>Select Course</h3>
                    <form method="GET" action="">
                        <div class="form-group">
                            <select name="course_id" onchange="this.form.submit()" style="width: 100%;">
                                <option value="0">-- Select a Course --</option>
                                <?php while($course = mysqli_fetch_assoc($courses_result)): ?>
                                    <option value="<?php echo $course['id']; ?>" 
                                            <?php echo ($selected_course == $course['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <?php if ($selected_course > 0 && isset($performance_result)): ?>
                    <?php if (mysqli_num_rows($performance_result) > 0): ?>
                    <div class="card mt-20">
                        <h3>Performance Analytics</h3>
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
                                    <th>Attendance %</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($student = mysqli_fetch_assoc($performance_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo $student['total_sessions']; ?></td>
                                    <td><?php echo $student['present_count']; ?></td>
                                    <td><?php echo $student['absent_count']; ?></td>
                                    <td><?php echo $student['late_count']; ?></td>
                                    <td><?php echo $student['excused_count']; ?></td>
                                    <td><?php echo $student['attendance_percentage']; ?>%</td>
                                    <td>
                                        <span class="performance-<?php echo strtolower($student['performance_level']); ?>">
                                            <?php echo $student['performance_level']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="card mt-20">
                        <h3>Performance Legend</h3>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                            <div>
                                <span class="performance-excellent">■ Excellent:</span> 90% and above
                            </div>
                            <div>
                                <span class="performance-good">■ Good:</span> 75% - 89%
                            </div>
                            <div>
                                <span class="performance-fair">■ Fair:</span> 60% - 74%
                            </div>
                            <div>
                                <span class="performance-poor">■ Poor:</span> Below 60%
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info mt-20">No students enrolled in this course yet.</div>
                    <?php endif; ?>
                <?php elseif ($selected_course > 0): ?>
                    <div class="alert alert-info mt-20">Please select a course to view performance data.</div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>