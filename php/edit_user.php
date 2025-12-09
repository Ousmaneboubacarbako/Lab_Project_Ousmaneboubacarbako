<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

checkRole('admin');

$error = '';
$success = '';
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch user data (using prepared statement)
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    header("Location: /Lab%203%20assignment%20(3)/Lab_assignment_4/html/admin_dashboard.html");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $status = trim($_POST['status']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($role)) {
        $error = "All fields except password are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        // Check if email exists for other users (using prepared statement)
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->bind_param('si', $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Email already exists!";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Update user (using prepared statement)
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    $error = "Password must be at least 8 characters long!";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET 
                                   first_name = ?, last_name = ?, email = ?, 
                                   role = ?, status = ?, password = ?
                                   WHERE id = ?");
                    $update_stmt->bind_param('ssssssi', $first_name, $last_name, $email, $role, $status, $hashed_password, $user_id);
                }
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET 
                               first_name = ?, last_name = ?, email = ?, 
                               role = ?, status = ?
                               WHERE id = ?");
                $update_stmt->bind_param('sssssi', $first_name, $last_name, $email, $role, $status, $user_id);
            }
            
            if (empty($error) && $update_stmt->execute()) {
                $success = "User updated successfully!";
                $update_stmt->close();
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } elseif (empty($error)) {
                $error = "Error updating user: " . $conn->error;
                $update_stmt->close();
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
    <title>Edit User</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
</head>
<body>
    <header>
        <h1>Edit User</h1>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php">Back to Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 600px; margin: 0 auto;">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="student" <?php echo ($user['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                            <option value="faculty" <?php echo ($user['role'] == 'faculty') ? 'selected' : ''; ?>>Faculty</option>
                            <option value="fi" <?php echo ($user['role'] == 'fi') ? 'selected' : ''; ?>>Faculty Intern</option>
                            <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="active" <?php echo ($user['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($user['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="pending" <?php echo ($user['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">New Password (leave blank to keep current)</label>
                        <input type="password" id="password" name="password">
                        <small>Minimum 8 characters</small>
                    </div>

                    <button type="submit" class="success">Update User</button>
                    <a href="admin_dashboard.php"><button type="button" class="secondary">Cancel</button></a>
                </form>
            </div>
        </section>
    </main>
</body>
</html>