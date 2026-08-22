<?php
require_once 'config.php';

echo "<h2>Complete Inventory Setup</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    // Read and execute inventory tables SQL
    $sql_file = __DIR__ . '/inventory_tables.sql';
    if (file_exists($sql_file)) {
        echo "<p>Setting up inventory tables...</p>";
        
        $sql = file_get_contents($sql_file);
        
        // Split and execute statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $db->exec($statement);
                    echo "<p style='color: green;'>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
                } catch (Exception $e) {
                    echo "<p style='color: orange;'>⚠ Skipped: " . substr($statement, 0, 50) . "...</p>";
                }
            }
        }
        
        echo "<p style='color: green;'>✓ Inventory tables setup completed</p>";
    } else {
        echo "<p style='color: red;'>✗ inventory_tables.sql not found</p>";
    }
    
    // Verify tables exist
    echo "<h3>Table Verification:</h3>";
    $tables = ['inventory_items', 'inventory_stock', 'inventory_transactions', 'suppliers'];
    foreach ($tables as $table) {
        try {
            $result = $db->query("SHOW TABLES LIKE '$table'");
            $exists = $result->rowCount() > 0;
            echo "<p style='color: " . ($exists ? 'green' : 'red') . ";'>" . ($exists ? '✓' : '✗') . " $table</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ $table - Error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test adding an inventory item
    echo "<h3>Test Inventory Addition:</h3>";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = 1;
    $_SESSION['full_name'] = 'Test User';
    $_SESSION['user_role'] = 'store';
    
    $result = add_inventory_item(
        'Test Item ' . date('H:i:s'),
        'TEST' . time(),
        'chemicals',
        'Test Description',
        'bottle',
        10.5,
        150.00,
        1,
        'Test Location',
        5
    );
    
    echo "<p style='color: " . ($result ? 'green' : 'red') . ";'>Test Result: " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";
    
    if ($result) {
        echo "<p style='color: green;'>✓ Inventory system is working correctly!</p>";
        echo "<p><a href='inventory_form.php'>Go to Inventory Form</a></p>";
    } else {
        echo "<p style='color: red;'>✗ Inventory addition failed. Check error logs.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>
