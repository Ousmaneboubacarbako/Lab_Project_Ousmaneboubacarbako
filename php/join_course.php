<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

checkRole('student');

$student_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get available courses (not already enrolled) - using prepared statement
$courses_stmt = $conn->prepare("SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                  (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count
                  FROM courses c
                  LEFT JOIN users u ON c.instructor_id = u.id
                  WHERE c.status = 'active' 
                  AND c.id NOT IN (SELECT course_id FROM enrollments WHERE student_id = ?)
                  ORDER BY c.course_name");
$courses_stmt->bind_param('i', $student_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['course_id'])) {
    $course_id = intval($_POST['course_id']);
    
    // Check if already enrolled (using prepared statement)
    $check_stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
    $check_stmt->bind_param('ii', $student_id, $course_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "You are already enrolled in this course!";
        $check_stmt->close();
    } else {
        $check_stmt->close();
        // Enroll student (using prepared statement)
        $enroll_stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id, status, enrolled_at) 
                        VALUES (?, ?, 'active', NOW())");
        $enroll_stmt->bind_param('ii', $student_id, $course_id);
        
        if ($enroll_stmt->execute()) {
            $success = "Successfully enrolled as an observer!";
            $enroll_stmt->close();
            // Refresh available courses
            $courses_stmt->execute();
            $courses_result = $courses_stmt->get_result();
        } else {
            $error = "Error enrolling in course: " . $conn->error;
            $enroll_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Course as Observer</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
</head>
<body>
    <header>
        <h1>Join Course as Observer</h1>
        <nav>
            <ul>
                <li><a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/StuDashboard.html">Back to Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 900px; margin: 0 auto;">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <h3>Available Courses</h3>
                    <p style="color: #666; margin-bottom: 20px;">
                        Browse and join courses as an observer. You'll be able to view course materials and track your attendance.
                    </p>

                    <?php if (mysqli_num_rows($courses_result) > 0): ?>
                        <?php while($course = mysqli_fetch_assoc($courses_result)): ?>
                        <div class="card" style="margin-bottom: 15px; background: #f8f9fa;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 10px 0;"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                                    <p style="margin: 5px 0; color: #666;">
                                        <strong>Code:</strong> <?php echo htmlspecialchars($course['course_code']); ?> | 
                                        <strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name'] ?? 'TBA'); ?>
                                    </p>
                                    <p style="margin: 5px 0; color: #666;">
                                        <strong>Credits:</strong> <?php echo $course['credits']; ?> | 
                                        <strong>Semester:</strong> <?php echo htmlspecialchars($course['semester'] . ' ' . $course['academic_year']); ?> | 
                                        <strong>Students:</strong> <?php echo $course['student_count']; ?>
                                    </p>
                                    <?php if (!empty($course['description'])): ?>
                                    <p style="margin: 10px 0 0 0; font-size: 14px; color: #555;">
                                        <?php echo htmlspecialchars($course['description']); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-left: 20px;">
                                    <form method="POST" action="" style="margin: 0;">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" class="success" onclick="return confirm('Are you sure you want to join this course as an observer?')">
                                            Join Course
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No courses available to join at this time. You may already be enrolled in all active courses.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>