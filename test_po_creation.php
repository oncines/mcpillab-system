<?php
require_once 'config.php';

// Simulate a logged-in store user
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'store';
$_SESSION['full_name'] = 'Test Store User';

echo "<h2>Purchase Order Creation Test</h2>";

// Test the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_po'])) {
    echo "<h3>Form Submitted Successfully!</h3>";
    echo "<pre>";
    echo "POST Data:\n";
    print_r($_POST);
    echo "</pre>";
    
    // The actual processing happens in purchase_order.php
    // This is just to test the form
    exit;
}

// Get suppliers
$suppliers = get_suppliers();
$po_number = generate_po_number();

?>
<!DOCTYPE html>
<html>
<head>
    <title>PO Creation Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h4>Test Purchase Order Creation</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>PO Number</label>
                            <input type="text" class="form-control" name="po_number" value="<?php echo $po_number; ?>" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Supplier *</label>
                            <select class="form-select" name="supplier_id" required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>"><?php echo $supplier['name']; ?> (<?php echo $supplier['supplier_code']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Order Date</label>
                            <input type="date" class="form-control" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label>Items *</label>
                        <div id="itemsContainer">
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="items[item_name][]" placeholder="Item Name" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" name="items[quantity][]" placeholder="Qty" min="1" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" name="items[unit_price][]" placeholder="Unit Price" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" name="create_po" class="btn btn-primary">Create Purchase Order</button>
                    <a href="purchase_order.php" class="btn btn-secondary">Back to PO Page</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
