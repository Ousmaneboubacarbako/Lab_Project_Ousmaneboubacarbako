<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Check</h2>";

require_once 'db_connection.php';

if ($conn) {
    echo "✓ Connected to database<br><br>";
    
    // Show all tables
    echo "<h3>Tables in Database:</h3>";
    $tables = mysqli_query($conn, "SHOW TABLES");
    $table_list = [];
    while ($row = mysqli_fetch_array($tables)) {
        $table_list[] = $row[0];
        echo "- " . $row[0] . "<br>";
    }
    
    // Check users table structure
    if (in_array('users', $table_list)) {
        echo "<br><h3>Users Table Structure:</h3>";
        $columns = mysqli_query($conn, "DESCRIBE users");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($col = mysqli_fetch_assoc($columns)) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if any users exist
        $count = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
        $user_count = mysqli_fetch_assoc($count)['count'];
        echo "<br><p>Total users in database: <strong>$user_count</strong></p>";
    } else {
        echo "<br><p style='color: red;'>❌ 'users' table does NOT exist!</p>";
        echo "<p>You need to create the users table. Here's the SQL:</p>";
        echo "<textarea rows='15' cols='80'>
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'faculty', 'student', 'fi') DEFAULT 'student',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
</textarea>";
    }
    
} else {
    echo "❌ Failed to connect to database";
}
?>
