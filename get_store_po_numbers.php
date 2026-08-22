<?php
require_once 'config.php';

// Check if user is logged in and is a store user
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'store') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get store user's PO numbers
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT po.id, po.po_number, po.order_date, po.total_amount, po.status, 
                     s.name as supplier_name 
              FROM purchase_orders po 
              LEFT JOIN suppliers s ON po.supplier_id = s.id 
              WHERE po.created_by = :user_id 
              ORDER BY po.order_date DESC 
              LIMIT 50";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    $po_numbers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data for JSON response
    foreach ($po_numbers as &$po) {
        $po['order_date'] = format_date($po['order_date']);
        $po['total_amount'] = format_currency($po['total_amount']);
    }
    
    echo json_encode(['po_numbers' => $po_numbers]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch PO numbers: ' . $e->getMessage()]);
}
?>
