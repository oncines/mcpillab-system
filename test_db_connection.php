<?php
// Test database connection without using the config.php
echo "<h2>Direct Database Connection Test</h2>";

$host = 'localhost';
$dbname = 'mcpillab';
$username = 'root';
$password = '';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Connected to MySQL server</p>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $db_exists = $stmt->fetch();
    
    if ($db_exists) {
        echo "<p style='color: green;'>✓ Database '$dbname' exists</p>";
        
        // Connect to the specific database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Tables in database:</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        
        // Check suppliers table
        if (in_array('suppliers', $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM suppliers");
            $count = $stmt->fetchColumn();
            echo "<p style='color: green;'>✓ Suppliers table exists with $count records</p>";
        } else {
            echo "<p style='color: red;'>✗ Suppliers table missing</p>";
        }
        
        // Check purchase_orders table
        if (in_array('purchase_orders', $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM purchase_orders");
            $count = $stmt->fetchColumn();
            echo "<p style='color: green;'>✓ Purchase_orders table exists with $count records</p>";
            
            // Show structure
            $stmt = $pdo->query("DESCRIBE purchase_orders");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h4>Purchase Orders table structure:</h4>";
            echo "<table border='1'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
            foreach ($columns as $column) {
                echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Key']}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>✗ Purchase_orders table missing</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Database '$dbname' does not exist</p>";
        echo "<p>Creating database...</p>";
        $pdo->exec("CREATE DATABASE $dbname");
        echo "<p style='color: green;'>✓ Database created</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Connection failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Testing with config.php:</h3>";

try {
    require_once 'config.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Config.php connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Config.php connection failed: " . $e->getMessage() . "</p>";
}
?>
