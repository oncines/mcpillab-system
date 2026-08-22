<?php
require_once 'config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inventory'])) {
    error_log("Form submission received");
    
    $item_name = sanitize_input($_POST['item_name']);
    $item_code = sanitize_input($_POST['item_code']);
    $category = sanitize_input($_POST['category']);
    $description = sanitize_input($_POST['description']);
    $unit = sanitize_input($_POST['unit']);
    $quantity = floatval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $supplier_name = isset($_POST['supplier']) ? sanitize_input($_POST['supplier']) : '';
    $location = sanitize_input($_POST['location']);
    $min_stock_level = intval($_POST['min_stock_level']);
    
    error_log("Form data processed: item_name=$item_nam e, item_code=$item_code, category=$category, unit=$unit, quantity=$quantity, unit_price=$unit_price, supplier_name=$supplier_name, location=$location, min_stock_level=$min_stock_level");
    
    // Validate required fields
    if (empty($item_name) || empty($item_code) || empty($category) || empty($unit) || $quantity <= 0 || $unit_price <= 0 || empty($location) || $min_stock_level < 0 || empty($supplier_name)) {
        $error_message = "Please fill in all required fields with valid values.";
        error_log("Validation failed: missing or invalid required fields");
    } else {
        $success = add_inventory_item($item_name, $item_code, $category, $description, $unit, $quantity, $unit_price, $supplier_name, $location, $min_stock_level);
        
        if ($success) {
            $success_message = "Inventory item added successfully!";
            error_log("Inventory item added successfully");
        } else {
            $error_message = "Failed to add inventory item. Please check error logs for details.";
            error_log("Inventory item addition failed");
        }
    }
}

// Get suppliers for dropdown
$suppliers = get_suppliers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Inventory Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: #0d1578;
            min-height: 100vh;
            color: white;
            width: 280px;
            position: fixed;
            z-index: 100;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        .main-content {
            padding: 20px;
        }
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .section-title {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="logo.png" alt="McPIL Logo" class="sidebar-logo" style="width: 80px; height: 80px; border-radius: 50%;">
                        <h4 class="mt-2">McPIL</h4>
                        <small>Pharmaceutical Laboratory</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> <?php echo is_employee() ? 'Home' : 'Dashboard'; ?>
                            </a>
                        </li>
                        
                        <?php if (is_store()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="inventory.php">
                                <i class="fas fa-boxes"></i> <?php echo is_employee() ? 'Inventory' : 'Inventory Management'; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="purchase_order.php">
                                <i class="fas fa-shopping-cart"></i> Purchase Order
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="purchase_invoice.php">
                                <i class="fas fa-file-invoice"></i> Purchase Invoice
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="invoice_list.php">
                                <i class="fas fa-list"></i> Invoice List
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item mt-4">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Header -->
                <div class="header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">Inventory Form</h2>
                            <p class="text-muted mb-0">Add new inventory items to the system</p>
                        </div>
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?php echo $_SESSION['full_name']; ?></div>
                                <small class="text-muted"><?php echo ucfirst($_SESSION['user_role']); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Inventory Form -->
                <div class="form-card">
                    <h5 class="section-title">
                        <i class="fas fa-plus-circle"></i> Add New Inventory Item
                    </h5>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="item_name" class="form-label">
                                    <i class="fas fa-tag"></i> Item Name
                                </label>
                                <input type="text" class="form-control" id="item_name" name="item_name" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="item_code" class="form-label">
                                    <i class="fas fa-barcode"></i> Item Code
                                </label>
                                <input type="text" class="form-control" id="item_code" name="item_code" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">
                                    <i class="fas fa-list"></i> Category
                                </label>
                                <input type="text" class="form-control" id="category" name="category" placeholder="Enter category" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="unit" class="form-label">
                                    <i class="fas fa-weight"></i> Unit
                                </label>
                                <input type="text" class="form-control" id="unit" name="unit" placeholder="Enter unit" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left"></i> Description
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="quantity" class="form-label">
                                    <i class="fas fa-cubes"></i> Quantity
                                </label>
                                <input type="number" step="0.01" class="form-control" id="quantity" name="quantity" required>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="unit_price" class="form-label">
                                    <i class="fas fa-dollar-sign"></i> Unit Price
                                </label>
                                <input type="number" step="0.01" class="form-control" id="unit_price" name="unit_price" required>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="min_stock_level" class="form-label">
                                    <i class="fas fa-exclamation-triangle"></i> Min Stock Level
                                </label>
                                <input type="number" class="form-control" id="min_stock_level" name="min_stock_level" required>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="supplier" class="form-label">
                                    <i class="fas fa-truck"></i> Supplier
                                </label>
                                <input type="text" class="form-control" id="supplier" name="supplier" placeholder="Enter supplier name" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="location" class="form-label">
                                <i class="fas fa-map-marker-alt"></i> Storage Location
                            </label>
                            <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Shelf A-1, Room 201" required>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="save_inventory" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Item
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <?php include 'mcbot_widget.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
