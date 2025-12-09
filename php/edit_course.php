<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

checkRole('admin');

$error = '';
$success = '';
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch course data (using prepared statement)
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->bind_param('i', $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/admin_dashboard.html");
    exit();
}

$course = $result->fetch_assoc();
$stmt->close();

// Fetch faculty members for dropdown (including FI)
$faculty_query = "SELECT id, first_name, last_name FROM users 
                  WHERE role IN ('faculty', 'fi') AND status = 'active' 
                  ORDER BY first_name";
$faculty_result = mysqli_query($conn, $faculty_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $course_code = mysqli_real_escape_string($conn, $_POST['course_code']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $instructor_id = intval($_POST['instructor_id']);
    $credits = intval($_POST['credits']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validation
    if (empty($course_name) || empty($course_code) || empty($semester) || empty($academic_year)) {
        $error = "Course name, code, semester, and academic year are required!";
    } else {
        // Check if course code exists for other courses
        $check_query = "SELECT id FROM courses WHERE course_code = '$course_code' AND id != $course_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Course code already exists!";
        } else {
            // Update course
            $instructor_value = ($instructor_id > 0) ? $instructor_id : 'NULL';
            $update_query = "UPDATE courses SET 
                           course_name = '$course_name',
                           course_code = '$course_code',
                           description = '$description',
                           instructor_id = $instructor_value,
                           credits = $credits,
                           semester = '$semester',
                           academic_year = '$academic_year',
                           status = '$status'
                           WHERE id = $course_id";
            
            if (mysqli_query($conn, $update_query)) {
                $success = "Course updated successfully!";
                // Refresh course data
                $result = mysqli_query($conn, "SELECT * FROM courses WHERE id = $course_id");
                $course = mysqli_fetch_assoc($result);
            } else {
                $error = "Error updating course: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
</head>
<body>
    <header>
        <h1>Edit Course</h1>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php">Back to Dashboard</a></li>
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

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="course_name">Course Name *</label>
                        <input type="text" id="course_name" name="course_name" 
                               value="<?php echo htmlspecialchars($course['course_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="course_code">Course Code *</label>
                        <input type="text" id="course_code" name="course_code" 
                               value="<?php echo htmlspecialchars($course['course_code']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Course Description</label>
                        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($course['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="instructor_id">Instructor</label>
                        <select id="instructor_id" name="instructor_id">
                            <option value="">Select Instructor (Optional)</option>
                            <?php while($faculty = mysqli_fetch_assoc($faculty_result)): ?>
                                <option value="<?php echo $faculty['id']; ?>" 
                                        <?php echo ($course['instructor_id'] == $faculty['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="credits">Credits</label>
                        <input type="number" id="credits" name="credits" min="1" max="10"
                               value="<?php echo htmlspecialchars($course['credits']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="semester">Semester *</label>
                        <select id="semester" name="semester" required>
                            <option value="Fall" <?php echo ($course['semester'] == 'Fall') ? 'selected' : ''; ?>>Fall</option>
                            <option value="Spring" <?php echo ($course['semester'] == 'Spring') ? 'selected' : ''; ?>>Spring</option>
                            <option value="Summer" <?php echo ($course['semester'] == 'Summer') ? 'selected' : ''; ?>>Summer</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="academic_year">Academic Year *</label>
                        <input type="text" id="academic_year" name="academic_year" 
                               value="<?php echo htmlspecialchars($course['academic_year']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="active" <?php echo ($course['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($course['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="archived" <?php echo ($course['status'] == 'archived') ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>

                    <button type="submit" class="success">Update Course</button>
                    <a href="admin_dashboard.php"><button type="button" class="secondary">Cancel</button></a>
                </form>
            </div>
        </section>
    </main>
</body>
</html>