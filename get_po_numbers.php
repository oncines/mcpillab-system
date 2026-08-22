<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get PO numbers based on user role
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'manager') {
        // Admins and managers can see all PO numbers
        $query = "SELECT po.id, po.po_number, po.order_date, po.total_amount, po.status, 
                         s.name as supplier_name 
                  FROM purchase_orders po 
                  LEFT JOIN suppliers s ON po.supplier_id = s.id 
                  ORDER BY po.order_date DESC 
                  LIMIT 50";
        
        $stmt = $db->prepare($query);
    } else {
        // Store user can only see their own PO numbers
        $query = "SELECT po.id, po.po_number, po.order_date, po.total_amount, po.status, 
                         s.name as supplier_name 
                  FROM purchase_orders po 
                  LEFT JOIN suppliers s ON po.supplier_id = s.id 
                  WHERE po.created_by = :user_id 
                  ORDER BY po.order_date DESC 
                  LIMIT 50";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
    }
    
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
