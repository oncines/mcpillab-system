<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

echo "<h2>Session Info:</h2>";
echo "<pre>";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set') . "\n";
echo "User Role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Not set') . "\n";
echo "</pre>";

echo "<h2>Database Test:</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test basic connection
    echo "Database connection: SUCCESS\n";
    
    // Count purchase orders
    $count_query = "SELECT COUNT(*) as total FROM purchase_orders";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $count = $count_stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total purchase orders: " . $count['total'] . "\n";
    
    // Show some sample POs
    if ($count['total'] > 0) {
        $sample_query = "SELECT id, po_number, supplier_id, created_by FROM purchase_orders LIMIT 5";
        $sample_stmt = $db->prepare($sample_query);
        $sample_stmt->execute();
        $samples = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nSample POs:\n";
        echo "<pre>";
        print_r($samples);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "<h2>get_po_numbers.php Test:</h2>";

// Temporarily set session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Assume admin user exists
    $_SESSION['user_role'] = 'admin';
}

include 'get_po_numbers.php';
?>
