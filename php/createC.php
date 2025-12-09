<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

// Allow both admin and faculty intern to create courses
if (!isLoggedIn()) {
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/Login.html");
    exit();
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'fi') {
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/" . 
           ($_SESSION['role'] === 'faculty' ? 'FDashboard' : 'StuDashboard') . ".html");
    exit();
}

$error = '';
$success = '';

// Get faculty list for assignment
$faculty_stmt = $conn->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users 
                                 WHERE role IN ('faculty', 'fi') AND status = 'active' 
                                 ORDER BY first_name, last_name");
$faculty_stmt->execute();
$faculty_result = $faculty_stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = trim($_POST['course_name']);
    $course_code = trim($_POST['course_code']);
    $credits = intval($_POST['credits']);
    $instructor_id = intval($_POST['instructor_id']);
    $description = trim($_POST['description']);
    
    // Validation
    if (empty($course_name) || empty($course_code) || empty($instructor_id)) {
        $error = "Course name, code, and instructor are required!";
    } elseif ($credits < 1 || $credits > 10) {
        $error = "Credits must be between 1 and 10!";
    } else {
        // Check if course code already exists
        $check_stmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
        $check_stmt->bind_param('s', $course_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Course code already exists!";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            // Insert course
            $insert_stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, credits, instructor_id, description, status, created_at) 
                                          VALUES (?, ?, ?, ?, ?, 'active', NOW())");
            $insert_stmt->bind_param('ssiis', $course_code, $course_name, $credits, $instructor_id, $description);
            
            if ($insert_stmt->execute()) {
                $success = "Course created successfully!";
                // Clear form
                $_POST = array();
            } else {
                $error = "Error creating course: " . $conn->error;
            }
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
    <title>Create Course</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
    <script src="../js/auth_check.js"></script>
</head>
<body>
    <header>
        <h1>Create New Course</h1>
        <nav>
            <ul>
                <li><a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/admin_dashboard.html">Back to Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 800px; margin: 0 auto;">
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="card">
                        <h3>Course Information</h3>
                        
                        <div class="input-group">
                            <label for="course_code">Course Code *</label>
                            <input type="text" 
                                   id="course_code" 
                                   name="course_code" 
                                   placeholder="e.g., CS101" 
                                   value="<?php echo isset($_POST['course_code']) ? htmlspecialchars($_POST['course_code']) : ''; ?>"
                                   required>
                        </div>

                        <div class="input-group">
                            <label for="course_name">Course Name *</label>
                            <input type="text" 
                                   id="course_name" 
                                   name="course_name" 
                                   placeholder="e.g., Introduction to Programming" 
                                   value="<?php echo isset($_POST['course_name']) ? htmlspecialchars($_POST['course_name']) : ''; ?>"
                                   required>
                        </div>

                        <div class="input-group">
                            <label for="credits">Credits *</label>
                            <input type="number" 
                                   id="credits" 
                                   name="credits" 
                                   min="1" 
                                   max="10" 
                                   value="<?php echo isset($_POST['credits']) ? htmlspecialchars($_POST['credits']) : '3'; ?>"
                                   required>
                        </div>

                        <div class="input-group">
                            <label for="instructor_id">Assign Instructor *</label>
                            <select id="instructor_id" name="instructor_id" required>
                                <option value="">Select Instructor</option>
                                <?php 
                                $faculty_result->data_seek(0);
                                while($faculty = $faculty_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $faculty['id']; ?>"
                                            <?php echo (isset($_POST['instructor_id']) && $_POST['instructor_id'] == $faculty['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($faculty['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label for="description">Description</label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="4" 
                                      placeholder="Course description, objectives, prerequisites..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="submit" class="success">Create Course</button>
                            <a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/admin_dashboard.html">
                                <button type="button" class="secondary">Cancel</button>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <style>
        .input-group {
            margin-bottom: 20px;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .input-group input,
        .input-group select,
        .input-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
    </style>
</body>
</html>
