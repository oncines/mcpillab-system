<?php
require_once 'config.php';

echo "<h2>Setting up Inventory Tables</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Read and execute the SQL file
    $sql = file_get_contents('inventory_tables.sql');
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $db->beginTransaction();
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "<p>Executing: " . substr($statement, 0, 50) . "...</p>";
            $db->exec($statement);
        }
    }
    
    $db->commit();
    echo "<h3 style='color: green;'>✓ Inventory tables created successfully!</h3>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>✗ Error: " . $e->getMessage() . "</h3>";
}
?>
