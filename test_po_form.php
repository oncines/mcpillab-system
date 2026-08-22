<?php
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'Test User';
$_SESSION['user_role'] = 'store';

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_po'])) {
    echo "<pre>";
    echo "POST data received:\n";
    print_r($_POST);
    echo "</pre>";
    
    $po_number = $_POST['po_number'];
    $supplier_id = $_POST['supplier_id'];
    $order_date = $_POST['order_date'];
    $expected_delivery_date = $_POST['expected_delivery_date'];
    $notes = $_POST['notes'];
    
    // Process items
    $items = [];
    if (isset($_POST['items'])) {
        foreach ($_POST['items']['item_name'] as $key => $item_name) {
            if (!empty($item_name)) {
                $items[] = [
                    'item_name' => $item_name,
                    'quantity' => $_POST['items']['quantity'][$key],
                    'unit_price' => $_POST['items']['unit_price'][$key]
                ];
            }
        }
    }
    
    echo "<pre>";
    echo "Items processed:\n";
    print_r($items);
    echo "</pre>";
    
    if (!empty($items)) {
        echo "<p>Calling create_purchase_order...</p>";
        $po_id = create_purchase_order($po_number, $supplier_id, $order_date, $expected_delivery_date, $items, $notes, $_SESSION['user_id']);
        
        if ($po_id) {
            $success_message = "Purchase Order created successfully! ID: $po_id";
        } else {
            $error_message = "Failed to create Purchase Order.";
        }
    } else {
        $error_message = "Please add at least one item.";
    }
}

// Get suppliers
$suppliers = get_suppliers();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test PO Form</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select, textarea { padding: 8px; width: 300px; }
        .item-row { background: #f0f0f0; padding: 10px; margin-bottom: 10px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Test Purchase Order Form</h1>
    
    <?php if ($error_message): ?>
        <div class="error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>PO Number:</label>
            <input type="text" name="po_number" value="TEST-<?php echo date('YmdHis'); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Supplier:</label>
            <select name="supplier_id" required>
                <option value="">Select Supplier</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo $supplier['id']; ?>"><?php echo $supplier['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Order Date:</label>
            <input type="date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Expected Delivery:</label>
            <input type="date" name="expected_delivery_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
        </div>
        
        <div class="form-group">
            <label>Items:</label>
            <div id="itemsContainer">
                <div class="item-row">
                    <input type="text" name="items[item_name][]" placeholder="Item Name" required>
                    <input type="number" name="items[quantity][]" placeholder="Quantity" min="1" required>
                    <input type="number" name="items[unit_price][]" placeholder="Unit Price" step="0.01" min="0" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Notes:</label>
            <textarea name="notes" rows="3"></textarea>
        </div>
        
        <button type="submit" name="create_po" class="btn">Create Purchase Order</button>
    </form>
    
    <hr>
    <p><a href="check_suppliers.php">Check Suppliers</a> | <a href="check_po_tables.php">Check PO Tables</a></p>
</body>
</html>
