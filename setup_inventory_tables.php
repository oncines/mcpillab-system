<?php
require_once 'config.php';

echo "<h2>Setting up Inventory Tables</h2>";

// Read the SQL file
$sql_file = 'inventory_tables.sql';
if (!file_exists($sql_file)) {
    die("SQL file not found: $sql_file");
}

$sql = file_get_contents($sql_file);

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<h3>Executing SQL statements...</h3>";
    
    foreach ($statements as $index => $statement) {
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                echo "<p style='color: green;'>✓ Statement " . ($index + 1) . " executed successfully</p>";
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>⚠ Statement " . ($index + 1) . " failed (may already exist): " . $e->getMessage() . "</p>";
                echo "<pre>$statement</pre>";
            }
        }
    }
    
    echo "<h3 style='color: green;'>✓ Inventory tables setup completed!</h3>";
    echo "<p><a href='reports.php'>Go to Reports</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
