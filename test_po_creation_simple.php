<?php
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'Test Store User';
$_SESSION['user_role'] = 'store';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_po'])) {
    $po_number = sanitize_input($_POST['po_number']);
    $supplier_id = (int)$_POST['supplier_id'];
    $order_date = $_POST['order_date'];
    $expected_delivery_date = !empty($_POST['expected_delivery_date']) ? $_POST['expected_delivery_date'] : null;
    $notes = sanitize_input($_POST['notes']);
    
    // Process items
    $items = [];
    if (isset($_POST['items']) && isset($_POST['items']['item_name'])) {
        foreach ($_POST['items']['item_name'] as $key => $item_name) {
            if (!empty(trim($item_name))) {
                $quantity = isset($_POST['items']['quantity'][$key]) ? (float)$_POST['items']['quantity'][$key] : 0;
                $unit_price = isset($_POST['items']['unit_price'][$key]) ? (float)$_POST['items']['unit_price'][$key] : 0;
                
                if ($quantity > 0 && $unit_price > 0) {
                    $items[] = [
                        'item_name' => sanitize_input($item_name),
                        'quantity' => $quantity,
                        'unit_price' => $unit_price
                    ];
                }
            }
        }
    }
    
    if (!empty($items)) {
        $po_id = create_purchase_order($po_number, $supplier_id, $order_date, $expected_delivery_date, $items, $notes, $_SESSION['user_id']);
        
        if ($po_id) {
            $success_message = "Purchase Order created successfully! PO Number: $po_number, ID: $po_id";
        } else {
            $error_message = "Failed to create Purchase Order. Please check the data and try again.";
        }
    } else {
        $error_message = "Please add at least one valid item with quantity and unit price.";
    }
}

// Get suppliers
$suppliers = get_suppliers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test PO Creation - Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .item-row {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 class="mb-4">Test Purchase Order Creation</h2>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="po_number" class="form-label">PO Number *</label>
                    <input type="text" class="form-control" id="po_number" name="po_number" value="<?php echo generate_po_number(); ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="supplier_id" class="form-label">Supplier *</label>
                    <select class="form-select" id="supplier_id" name="supplier_id" required>
                        <option value="">Select Supplier</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>"><?php echo $supplier['name']; ?> (<?php echo $supplier['supplier_code']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label for="order_date" class="form-label">Order Date *</label>
                    <input type="date" class="form-control" id="order_date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label for="expected_delivery_date" class="form-label">Expected Delivery</label>
                    <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Items *</label>
                <div id="itemsContainer">
                    <div class="item-row">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <input type="text" class="form-control" name="items[item_name][]" placeholder="Item Name" required>
                            </div>
                            <div class="col-md-3 mb-2">
                                <input type="number" class="form-control" name="items[quantity][]" placeholder="Qty" min="1" step="0.01" required>
                            </div>
                            <div class="col-md-3 mb-2">
                                <input type="number" class="form-control" name="items[unit_price][]" placeholder="Unit Price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-2 mb-2">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addItem()">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
            </div>
            
            <button type="submit" name="create_po" class="btn btn-primary">
                <i class="fas fa-save"></i> Create Purchase Order
            </button>
        </form>
        
        <hr class="my-4">
        
        <div class="text-center">
            <a href="purchase_order.php" class="btn btn-outline-primary">Go to Purchase Order System</a>
            <a href="debug_po_creation.php" class="btn btn-outline-info">Debug PO Creation</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addItem() {
            const container = document.getElementById('itemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'item-row';
            newItem.innerHTML = `
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <input type="text" class="form-control" name="items[item_name][]" placeholder="Item Name" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="number" class="form-control" name="items[quantity][]" placeholder="Qty" min="1" step="0.01" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="number" class="form-control" name="items[unit_price][]" placeholder="Unit Price" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newItem);
        }
        
        function removeItem(button) {
            const itemRow = button.closest('.item-row');
            const container = document.getElementById('itemsContainer');
            if (container.children.length > 1) {
                itemRow.remove();
            }
        }
    </script>
</body>
</html>
