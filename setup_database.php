<?php
// Database Setup Script for MCPIL Laboratory Management System

// Database Configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$db_name = 'mcpillab';

try {
    // Create database connection without specifying database
    $conn = new PDO("mysql:host=$host", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS $db_name");
    echo "Database '$db_name' created successfully!<br>";
    
    // Select the database
    $conn->exec("USE $db_name");
    
    // Read and execute the SQL file
    $sql_file = __DIR__ . '/database.sql';
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        
        // Split SQL statements by semicolon
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $conn->exec($statement);
            }
        }
        
        echo "Database tables and data imported successfully!<br>";
        echo "Setup completed! You can now access the system at: <a href='index.php'>index.php</a><br>";
        echo "<br>Default Admin Login:<br>";
        echo "Email: admin@mcpillab.com<br>";
        echo "Password: admin123<br>";
        echo "<br><a href='index.php'>Go to Login Page</a>";
        
    } else {
        echo "Error: database.sql file not found!";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
