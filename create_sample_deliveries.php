<?php
require_once 'config.php';

echo "<h2>Creating Sample Delivery Data</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get some sample data
    $query = "SELECT id, name FROM suppliers LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $query = "SELECT id, po_number FROM purchase_orders LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $purchase_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($suppliers) || empty($purchase_orders)) {
        echo "<div class='alert alert-warning'>⚠️ Please create some suppliers and purchase orders first.</div>";
        exit;
    }
    
    // Get first admin user as creator
    $query = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
    $created_by = $admin_user ? $admin_user['id'] : 1;
    
    // Sample deliveries with different statuses
    $sample_deliveries = [
        [
            'status' => 'pending',
            'delivery_date' => date('Y-m-d'),
            'expected_date' => date('Y-m-d', strtotime('+3 days')),
            'tracking_number' => 'TRK123456',
            'carrier' => 'FedEx',
            'notes' => 'Pending approval'
        ],
        [
            'status' => 'approved',
            'delivery_date' => date('Y-m-d', strtotime('-1 day')),
            'expected_date' => date('Y-m-d', strtotime('+2 days')),
            'tracking_number' => 'TRK789012',
            'carrier' => 'DHL',
            'notes' => 'Approved for shipment'
        ],
        [
            'status' => 'in_transit',
            'delivery_date' => date('Y-m-d', strtotime('-2 days')),
            'expected_date' => date('Y-m-d', strtotime('+1 day')),
            'tracking_number' => 'TRK345678',
            'carrier' => 'UPS',
            'notes' => 'Currently in transit'
        ],
        [
            'status' => 'delivered',
            'delivery_date' => date('Y-m-d', strtotime('-3 days')),
            'expected_date' => date('Y-m-d', strtotime('-2 days')),
            'tracking_number' => 'TRK901234',
            'carrier' => 'LBC',
            'notes' => 'Successfully delivered'
        ],
        [
            'status' => 'cancelled',
            'delivery_date' => date('Y-m-d', strtotime('-4 days')),
            'expected_date' => date('Y-m-d', strtotime('-1 day')),
            'tracking_number' => 'TRK567890',
            'carrier' => 'J&T',
            'notes' => 'Delivery cancelled'
        ]
    ];
    
    foreach ($sample_deliveries as $index => $delivery_data) {
        $delivery_number = generate_delivery_number();
        $supplier = $suppliers[$index % count($suppliers)];
        $po = $purchase_orders[$index % count($purchase_orders)];
        
        $query = "INSERT INTO deliveries (delivery_number, po_id, supplier_id, delivery_date, expected_date, status, tracking_number, carrier, notes, created_by) 
                  VALUES (:delivery_number, :po_id, :supplier_id, :delivery_date, :expected_date, :status, :tracking_number, :carrier, :notes, :created_by)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':delivery_number', $delivery_number);
        $stmt->bindParam(':po_id', $po['id']);
        $stmt->bindParam(':supplier_id', $supplier['id']);
        $stmt->bindParam(':delivery_date', $delivery_data['delivery_date']);
        $stmt->bindParam(':expected_date', $delivery_data['expected_date']);
        $stmt->bindParam(':status', $delivery_data['status']);
        $stmt->bindParam(':tracking_number', $delivery_data['tracking_number']);
        $stmt->bindParam(':carrier', $delivery_data['carrier']);
        $stmt->bindParam(':notes', $delivery_data['notes']);
        $stmt->bindParam(':created_by', $created_by);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>✅ Created {$delivery_data['status']} delivery: {$delivery_number}</div>";
        }
    }
    
    echo "<br><a href='delivery_tracking.php' class='btn btn-primary'>View Delivery Tracking</a>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
}
?>
