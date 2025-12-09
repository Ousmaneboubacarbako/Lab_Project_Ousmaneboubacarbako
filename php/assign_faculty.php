<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

checkRole('admin');

$error = '';
$success = '';

// Fetch courses
$courses_query = "SELECT c.id, c.course_name, c.course_code, 
                  CONCAT(u.first_name, ' ', u.last_name) as current_instructor
                  FROM courses c
                  LEFT JOIN users u ON c.instructor_id = u.id
                  WHERE c.status = 'active'
                  ORDER BY c.course_name";
$courses_result = mysqli_query($conn, $courses_query);

// Fetch faculty members (including Faculty Interns)
$faculty_query = "SELECT id, first_name, last_name, email, role FROM users 
                  WHERE role IN ('faculty', 'fi') AND status = 'active' 
                  ORDER BY first_name";
$faculty_result = mysqli_query($conn, $faculty_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = intval($_POST['course_id']);
    $faculty_id = intval($_POST['faculty_id']);
    
    // Validation
    if ($course_id == 0 || $faculty_id == 0) {
        $error = "Please select both course and faculty member!";
    } else {
        // Update course with new instructor
        $update_query = "UPDATE courses SET instructor_id = $faculty_id WHERE id = $course_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success = "Faculty assigned successfully!";
            
            // Refresh courses data
            $courses_result = mysqli_query($conn, $courses_query);
        } else {
            $error = "Error assigning faculty: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Faculty to Courses</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
</head>
<body>
    <header>
        <h1>Assign Faculty to Courses</h1>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php">Back to Dashboard</a></li>
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
                    <h3>Assign New Instructor</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="course_id">Select Course *</label>
                            <select id="course_id" name="course_id" required>
                                <option value="">Choose a course</option>
                                <?php 
                                mysqli_data_seek($courses_result, 0);
                                while($course = mysqli_fetch_assoc($courses_result)): 
                                ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        <?php if($course['current_instructor']): ?>
                                            (Current: <?php echo htmlspecialchars($course['current_instructor']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="faculty_id">Select Faculty Member *</label>
                            <select id="faculty_id" name="faculty_id" required>
                                <option value="">Choose a faculty member</option>
                                <?php while($faculty = mysqli_fetch_assoc($faculty_result)): ?>
                                    <option value="<?php echo $faculty['id']; ?>">
                                        <?php echo htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']); ?>
                                        (<?php echo htmlspecialchars($faculty['email']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <button type="submit" class="success">Assign Faculty</button>
                    </form>
                </div>

                <div class="card mt-20">
                    <h3>Current Course Assignments</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Current Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($courses_result, 0);
                            while($course = mysqli_fetch_assoc($courses_result)): 
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td>
                                    <?php 
                                    echo $course['current_instructor'] 
                                        ? htmlspecialchars($course['current_instructor']) 
                                        : '<span style="color: #999;">Not Assigned</span>'; 
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</body>
</html>