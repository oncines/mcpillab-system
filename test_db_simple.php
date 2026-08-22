<?php
// Simple database test without session
define('DB_HOST', 'localhost');
define('DB_NAME', 'mcpillab');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Database Connection: ✓ Success</h1>";
    
    // Check tables
    $tables = ['purchase_orders', 'purchase_order_items', 'purchase_order_messages', 'suppliers', 'users'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<p>✓ Table '$table': $count records</p>";
    }
    
    // Test a sample PO query
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("
            SELECT po.*, u.full_name as created_by_name, u.role as created_by_role, s.name as supplier_name 
            FROM purchase_orders po 
            LEFT JOIN users u ON po.created_by = u.id 
            LEFT JOIN suppliers s ON po.supplier_id = s.id 
            WHERE po.id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $po = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($po) {
            echo "<h2>✓ PO Found:</h2>";
            echo "<pre>";
            print_r($po);
            echo "</pre>";
            
            // Get items
            $stmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
            $stmt->execute([$_GET['id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h2>Items:</h2>";
            echo "<pre>";
            print_r($items);
            echo "</pre>";
        } else {
            echo "<h2>✗ PO Not Found</h2>";
        }
    }
    
} catch (Exception $e) {
    echo "<h1>Database Error:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
