<?php
require_once 'config.php';

// Create a test purchase order for testing purposes
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // First check if supplier exists, if not create one
    $supplier_check = "SELECT id FROM suppliers LIMIT 1";
    $supplier_stmt = $db->prepare($supplier_check);
    $supplier_stmt->execute();
    
    if ($supplier_stmt->rowCount() === 0) {
        // Create a test supplier
        $supplier_insert = "INSERT INTO suppliers (name, contact_person, email, phone, address) 
                           VALUES ('Test Supplier', 'John Doe', 'test@supplier.com', '123-456-7890', '123 Test Street')";
        $db->exec($supplier_insert);
        $supplier_id = $db->lastInsertId();
    } else {
        $supplier = $supplier_stmt->fetch(PDO::FETCH_ASSOC);
        $supplier_id = $supplier['id'];
    }
    
    // Create a test purchase order
    $po_insert = "INSERT INTO purchase_orders (po_number, supplier_id, store_name, order_date, expected_delivery_date, total_amount, status, created_by) 
                 VALUES ('TEST-001', :supplier_id, 'Main Store', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 1000.00, 'Approved', 1)";
    
    $stmt = $db->prepare($po_insert);
    $stmt->bindParam(':supplier_id', $supplier_id);
    $stmt->execute();
    
    $po_id = $db->lastInsertId();
    
    echo "Test Purchase Order created successfully!\n";
    echo "PO ID: " . $po_id . "\n";
    echo "PO Number: TEST-001\n";
    
    // Create some test items for the PO
    $items_insert = "INSERT INTO purchase_order_items (po_id, item_name, description, quantity, unit_price, total_price) 
                    VALUES (:po_id, 'Test Item 1', 'Description for test item 1', 5, 100.00, 500.00),
                           (:po_id, 'Test Item 2', 'Description for test item 2', 10, 50.00, 500.00)";
    
    $items_stmt = $db->prepare($items_insert);
    $items_stmt->bindParam(':po_id', $po_id);
    $items_stmt->execute();
    
    echo "Test items added to PO!\n";
    
} catch (Exception $e) {
    echo "Error creating test PO: " . $e->getMessage() . "\n";
}
?>
