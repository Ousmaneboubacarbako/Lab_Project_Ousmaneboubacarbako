<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connection.php';

echo "<h2>Database Table Creation</h2>";

// Check if users table exists
$check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
$table_exists = mysqli_num_rows($check) > 0;

if (!$table_exists) {
    echo "<p style='color: red;'>❌ Users table does NOT exist. Creating it now...</p>";
    
    $create_table = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'faculty', 'student', 'fi') DEFAULT 'student',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_table)) {
        echo "<p style='color: green;'>✓ Users table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ Users table already exists</p>";
}

// Show table structure
echo "<h3>Table Structure:</h3>";
$structure = mysqli_query($conn, "DESCRIBE users");
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #667eea; color: white;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($col = mysqli_fetch_assoc($structure)) {
    echo "<tr>";
    echo "<td><strong>" . $col['Field'] . "</strong></td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . $col['Key'] . "</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Show existing users
$count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$count = mysqli_fetch_assoc($count_query)['count'];
echo "<h3>Existing Users: $count</h3>";

if ($count > 0) {
    $users = mysqli_query($conn, "SELECT id, first_name, last_name, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #667eea; color: white;'><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th></tr>";
    while ($user = mysqli_fetch_assoc($users)) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['first_name'] . " " . $user['last_name'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . $user['status'] . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<p><a href='test_direct_signup.php'>Test Direct Signup</a> | <a href='test_auth.html'>Test Auth Page</a></p>";
?>
