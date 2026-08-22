<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mcpillab');
define('DB_USER', 'root');
define('DB_PASS', '');

echo "<h1>Create Missing PO Tables</h1>";

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Database connected successfully</p>";
    
    // Read and execute SQL file
    $sql = file_get_contents('create_missing_tables.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "<p>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<p>✗ Error: " . $e->getMessage() . "</p>";
                } else {
                    echo "<p>⚠ Table already exists: " . substr($statement, 0, 50) . "...</p>";
                }
            }
        }
    }
    
    echo "<h2>Verification - Checking Tables:</h2>";
    
    // Check all required tables
    $tables = ['purchase_orders', 'purchase_order_items', 'purchase_order_messages'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p>✓ Table '$table': $count records</p>";
        } catch (Exception $e) {
            echo "<p>✗ Table '$table': Error - " . $e->getMessage() . "</p>";
        }
    }
    
    // Add sample data if tables are empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM purchase_orders");
    if ($stmt->fetchColumn() == 0) {
        echo "<h2>Adding Sample Data:</h2>";
        
        // Get first supplier
        $stmt = $pdo->query("SELECT id FROM suppliers LIMIT 1");
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get first user
        $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($supplier && $user) {
            // Insert sample PO
            $stmt = $pdo->prepare("
                INSERT INTO purchase_orders (po_number, supplier_id, order_date, expected_delivery_date, status, total_amount, notes, created_by) 
                VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Pending', 1500.00, 'Sample purchase order', ?)
            ");
            $stmt->execute(['PO-2024-001', $supplier['id'], $user['id']]);
            $po_id = $pdo->lastInsertId();
            
            // Insert sample items
            $stmt = $pdo->prepare("
                INSERT INTO purchase_order_items (po_id, item_name, quantity, unit_price) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$po_id, 'Sample Item 1', 10, 100.00]);
            $stmt->execute([$po_id, 'Sample Item 2', 5, 100.00]);
            
            echo "<p>✓ Sample PO created successfully</p>";
        }
    }
    
    echo "<h2>✅ All missing tables have been created!</h2>";
    echo "<p><a href='purchase_order.php'>Go to Purchase Orders</a></p>";
    
} catch (Exception $e) {
    echo "<h1>❌ Error:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
