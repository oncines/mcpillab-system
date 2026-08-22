<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$can_manage_inventory = is_store() || is_employee();
$unread_messages = get_unread_message_count($_SESSION['user_id']);

// Handle form submission for adding inventory items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inventory']) && $can_manage_inventory) {
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
    
    $success = add_inventory_item($item_name, $item_code, $category, $description, $unit, $quantity, $unit_price, $supplier_name, $location, $min_stock_level);
    
    if ($success) {
        $success_message = "Inventory item added successfully!";
    } else {
        $error_message = "Failed to add inventory item. Please try again.";
    }
}

// Get inventory data based on user role
if (is_admin()) {
    // Admin can see all inventory items
    $inventory_data = generate_inventory_report();
    $inventory_summary = get_inventory_summary();
} elseif (is_store()) {
    // Store can see their own inventory items
    $inventory_data = get_store_inventory_report($_SESSION['user_id']);
    $inventory_summary = get_store_inventory_summary($_SESSION['user_id']);
} elseif (is_employee()) {
    // Employee can view inventory reports
    $inventory_data = generate_inventory_report();
    $inventory_summary = get_inventory_summary();
} else {
    // Manager or other roles can view inventory
    $inventory_data = generate_inventory_report();
    $inventory_summary = get_inventory_summary();
}

// Get suppliers for inventory managers
$suppliers = $can_manage_inventory ? get_suppliers() : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo is_employee() ? 'Inventory' : 'Inventory Management'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="public/css/design-system.css" rel="stylesheet">
    <style>
        .main-content {
            padding: 20px;
            margin-left: var(--sidebar-w);
            min-width: 0;
        }
        .notification-badge {
            background: var(--red);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 8px;
        }
        .content-card {
            background: var(--white);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        .inventory-table {
            font-size: 0.9rem;
            border-collapse: collapse;
        }
        .inventory-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-align: center;
            border: 1px solid #dee2e6;
            padding: 8px;
            font-size: 0.85rem;
        }
        .inventory-table td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
            vertical-align: middle;
        }
        .inventory-table .product-name {
            text-align: left;
            font-weight: 500;
            min-width: 200px;
        }
        .inventory-table .numeric {
            text-align: right;
        }
        .inventory-table .center {
            text-align: center;
        }
        .highlight-red {
            color: var(--red);
            font-weight: bold;
        }
        .stat-box {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--navy);
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
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: bold;
        }
        .btn-primary {
            background: var(--navy);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(13, 21, 120, 0.3);
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid var(--border);
            padding: 12px 15px;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--navy);
            box-shadow: 0 0 0 0.2rem rgba(13, 21, 120, 0.25);
        }
        .section-title {
            color: var(--navy);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--navy);
        }
        .role-badge {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            color: var(--white);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .header-layout {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .header-copy {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 0;
        }
        .mobile-sidebar-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 12px;
            background: var(--navy);
            color: var(--white);
            flex: 0 0 auto;
        }
        .mobile-sidebar-backdrop {
            display: none;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                width: min(var(--sidebar-w), 86vw);
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
                z-index: 9999;
            }
            body.sidebar-open .sidebar {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            .mobile-sidebar-toggle {
                display: inline-flex;
            }
            .mobile-sidebar-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(9, 15, 85, 0.45);
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.3s ease;
                z-index: 9998;
            }
            body.sidebar-open .mobile-sidebar-backdrop {
                opacity: 1;
                pointer-events: auto;
            }
            .header-layout {
                align-items: flex-start;
                flex-wrap: wrap;
            }
            .user-info {
                width: 100%;
            }
            .content-card {
                padding: 18px;
            }
        }
        @media (min-width: 992px) and (max-width: 1366px) {
            .main-content {
                padding: 16px;
            }
            .content-card {
                padding: 18px;
            }
            .content-card h2,
            .content-card h5,
            .section-title {
                font-size: 1.35rem;
            }
            .content-card p,
            .form-label,
            .btn,
            .role-badge {
                font-size: 0.9rem;
            }
            .stat-number {
                font-size: 1.7rem;
            }
            .inventory-table {
                font-size: 0.82rem;
            }
            .inventory-table .product-name {
                min-width: 170px;
            }
        }
        @media (max-width: 575.98px) {
            .main-content {
                padding: 12px;
            }
            .content-card {
                padding: 14px;
                border-radius: 12px;
            }
            .content-card h2,
            .content-card h5,
            .section-title {
                font-size: 1.1rem;
            }
            .content-card p,
            .text-muted,
            .form-label,
            .btn,
            .role-badge {
                font-size: 0.82rem;
            }
            .user-avatar {
                width: 32px;
                height: 32px;
                font-size: 0.8rem;
            }
            .form-control, .form-select {
                padding: 10px 12px;
            }
            .inventory-table {
                font-size: 0.76rem;
            }
            .inventory-table .product-name {
                min-width: 140px;
            }
            .stat-number {
                font-size: 1.5rem;
            }
        }
        @media print {
            .sidebar, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
            }
            .content-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="appSidebar" class="sidebar">
                <div class="sidebar-brand">
                    <div class="sidebar-logo-ring">
                        <img src="logo.png" alt="McPIL Logo">
                    </div>
                    <div class="sidebar-brand-name">McPIL</div>
                    <div class="sidebar-brand-sub">Pharmaceutical Laboratory</div>
                </div>

                <div class="sidebar-nav">
                    <div class="nav-section-label">Navigation</div>

                    <a class="sidebar-link" href="<?php echo is_employee() ? 'employee_home.php' : 'dashboard.php'; ?>">
                        <span class="icon"><i class="fas fa-tachometer-alt"></i></span>
                        <?php echo is_employee() ? 'Home' : 'Dashboard'; ?>
                    </a>

                    <?php if (is_store()): ?>
                    <a class="sidebar-link active" href="inventory.php">
                        <span class="icon"><i class="fas fa-boxes"></i></span> Inventory Management
                    </a>
                    <a class="sidebar-link" href="purchase_order.php">
                        <span class="icon"><i class="fas fa-shopping-cart"></i></span> Purchase Order
                    </a>
                    <a class="sidebar-link" href="purchase_invoice.php">
                        <span class="icon"><i class="fas fa-file-invoice"></i></span> Purchase Invoice
                    </a>
                    <a class="sidebar-link" href="invoice_list.php">
                        <span class="icon"><i class="fas fa-list"></i></span> Invoice List
                    </a>
                    <a class="sidebar-link logout" href="chat_interface.php">
                        <span class="icon"><i class="fas fa-comments"></i></span> Messages
                    </a>
                    <?php endif; ?>

                    <?php if (is_employee()): ?>
                    <a class="sidebar-link active" href="inventory.php">
                        <span class="icon"><i class="fas fa-boxes"></i></span> Inventory
                    </a>
                    <a class="sidebar-link" href="attendance_camera.php">
                        <span class="icon"><i class="fas fa-clock"></i></span> Attendance
                    </a>
                    <a class="sidebar-link" href="attendance_history.php">
                        <span class="icon"><i class="fas fa-history"></i></span> Attendance History
                    </a>
                    <a class="sidebar-link" href="reports.php">
                        <span class="icon"><i class="fas fa-chart-bar"></i></span> Reports
                    </a>
                    <a class="sidebar-link logout" href="chat_interface.php">
                        <span class="icon"><i class="fas fa-comments"></i></span> Messages
                    </a>
                    <?php endif; ?>

                    <?php if (is_manager()): ?>
                    <a class="sidebar-link active" href="inventory.php">
                        <span class="icon"><i class="fas fa-clipboard-list"></i></span> Inventory Report
                    </a>
                    <a class="sidebar-link" href="reports.php">
                        <span class="icon"><i class="fas fa-chart-bar"></i></span> Reports
                    </a>
                    <?php endif; ?>
                </div>

                <div class="sidebar-footer">
                    <a class="sidebar-link logout" href="logout.php">
                        <span class="icon"><i class="fas fa-sign-out-alt"></i></span> Logout
                    </a>
                </div>
            </nav>
            <div class="mobile-sidebar-backdrop" id="mobileSidebarBackdrop"></div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Header -->
                <div class="content-card no-print">
                    <div class="header-layout">
                        <div class="header-copy">
                            <button type="button" class="mobile-sidebar-toggle" id="sidebarToggle" aria-label="Open navigation">
                                <i class="fas fa-bars"></i>
                            </button>
                            <div>
                            <h2 class="mb-0">
                                <?php if ($can_manage_inventory): ?>
                                    <?php echo is_employee() ? 'Inventory' : 'Inventory Management'; ?>
                                <?php else: ?>
                                    Inventory Report
                                <?php endif; ?>
                            </h2>
                            <p class="text-muted mb-0">
                                <?php 
                                if (is_admin()) {
                                    echo "View all submitted inventory items";
                                } elseif ($can_manage_inventory) {
                                    echo "Manage your inventory items";
                                } else {
                                    echo is_employee() ? "Inventory and reporting" : "Inventory management and reporting";
                                }
                                ?>
                            </p>
                            <span class="role-badge">
                                <i class="fas fa-user"></i> 
                                <?php echo ucfirst($_SESSION['user_role']); ?> Role
                            </span>
                            </div>
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

                <!-- Add Inventory Form -->
                <?php if ($can_manage_inventory): ?>
                <div class="content-card no-print">
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
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
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
                <?php endif; ?>

                <!-- Inventory Report -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="report-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin: -25px -25px 20px -25px;">
                            <h3 class="mb-1">
                                <?php if (is_admin()): ?>
                                    ALL INVENTORY REPORTS
                                <?php else: ?>
                                    INVENTORY REPORT
                                <?php endif; ?>
                            </h3>
                            <p class="mb-0">Generated on <?php echo date('F d, Y'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Summary Statistics -->
                    <div class="row mb-4 no-print">
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-number"><?php echo $inventory_summary['total_items']; ?></div>
                                <div>Total Items</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-number"><?php echo $inventory_summary['total_quantity']; ?></div>
                                <div>Total Quantity</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-number"><?php echo format_currency($inventory_summary['total_value']); ?></div>
                                <div>Total Value</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-number"><?php echo $inventory_summary['items_to_order']; ?></div>
                                <div>Items to Order</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Inventory Table -->
                    <div class="table-responsive">
                        <table class="table inventory-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="product-name">NAME OF PRODUCTS</th>
                                    <th rowspan="2">BARCODE</th>
                                    <th rowspan="2">SIZE</th>
                                    <th rowspan="2">UNIT</th>
                                    <th rowspan="2">UNITPRICE</th>
                                    <th rowspan="2">CONTENT</th>
                                    <th colspan="4">BEGINNING</th>
                                    <th colspan="4">ENDING</th>
                                    <th rowspan="2">ON HAND</th>
                                    <th rowspan="2">TOTAL AMOUNT</th>
                                    <th rowspan="2">SUGGESTED ORDER</th>
                                </tr>
                                <tr>
                                    <th>BODEGA</th>
                                    <th>SHELVES</th>
                                    <th>DELIVERY</th>
                                    <th>TOTAL</th>
                                    <th>BODEGA</th>
                                    <th>SHELVES</th>
                                    <th>DELIVERY</th>
                                    <th>TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inventory_data)): ?>
                                    <?php foreach ($inventory_data as $item): ?>
                                    <tr>
                                        <td class="product-name"><?php echo $item['item_name']; ?></td>
                                        <td class="center"><?php echo $item['barcode'] ?? '-'; ?></td>
                                        <td class="center"><?php echo $item['size'] ?? '-'; ?></td>
                                        <td class="center"><?php echo $item['unit']; ?></td>
                                        <td class="numeric"><?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td class="center"><?php echo $item['content'] ?? 1; ?></td>
                                        <td class="numeric"><?php echo $item['bodega_stock']; ?></td>
                                        <td class="numeric"><?php echo $item['shelves_stock']; ?></td>
                                        <td class="numeric"><?php echo $item['delivery_stock']; ?></td>
                                        <td class="numeric"><?php echo $item['total_stock']; ?></td>
                                        <td class="numeric"><?php echo $item['bodega_stock']; ?></td>
                                        <td class="numeric"><?php echo $item['shelves_stock']; ?></td>
                                        <td class="numeric"><?php echo $item['delivery_stock']; ?></td>
                                        <td class="numeric"><?php echo $item['total_stock']; ?></td>
                                        <td class="numeric"><?php echo $item['total_stock']; ?></td>
                                        <td class="numeric"><?php echo number_format($item['total_amount'], 2); ?></td>
                                        <td class="numeric <?php echo $item['suggested_order'] > 0 ? 'highlight-red' : ''; ?>">
                                            <?php echo $item['suggested_order']; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="18" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>No inventory items found.</p>
                                            <?php if ($can_manage_inventory): ?>
                                                <a href="inventory.php" class="btn btn-primary">Add Your First Item</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($inventory_data)): ?>
                            <tfoot>
                                <tr style="background-color: #f8f9fa; font-weight: bold;">
                                    <td colspan="6">TOTALS</td>
                                    <td class="numeric"><?php echo array_sum(array_column($inventory_data, 'bodega_stock')); ?></td>
                                    <td class="numeric"><?php echo array_sum(array_column($inventory_data, 'shelves_stock')); ?></td>
                                    <td class="numeric"><?php echo array_sum(array_column($inventory_data, 'delivery_stock')); ?></td>
                                    <td class="numeric"><?php echo array_sum(array_column($inventory_data, 'total_stock')); ?></td>
                                    <td class="numeric"><?php echo array_sum(array_column($inventory_data, 'bodega_stock')); ?></td>
                                    <td class="numeric"><?php echo array_sum(array_column($inventory_data, 'shelves_stock')); ?></td>
                                    <td class="numeric"><?php echo array_sum(array_column($inventory_data, 'delivery_stock')); ?></td>
                                    <td class="numeric"><?php echo array_sum(array_column($inventory_data, 'total_stock')); ?></td>
                                    <td class="numeric"><?php echo array_sum(array_column($inventory_data, 'total_stock')); ?></td>
                                    <td class="numeric"><?php echo number_format(array_sum(array_column($inventory_data, 'total_amount')), 2); ?></td>
                                    <td class="numeric highlight-red"><?php echo array_sum(array_column($inventory_data, 'suggested_order')); ?></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-4 text-center no-print">
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <button class="btn btn-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                        <?php if ($can_manage_inventory): ?>
                            <button class="btn btn-info" onclick="scrollToForm()">
                                <i class="fas fa-plus"></i> Add New Item
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'mcbot_widget.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const body = document.body;
            const toggle = document.getElementById('sidebarToggle');
            const backdrop = document.getElementById('mobileSidebarBackdrop');

            function closeSidebarOnDesktop() {
                if (window.innerWidth >= 992) {
                    body.classList.remove('sidebar-open');
                }
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    body.classList.toggle('sidebar-open');
                });
            }

            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    body.classList.remove('sidebar-open');
                });
            }

            window.addEventListener('resize', closeSidebarOnDesktop);
            closeSidebarOnDesktop();
        })();

        function exportToExcel() {
            let table = document.querySelector('.inventory-table');
            let rows = table.querySelectorAll('tr');
            let csv = [];
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    // Clean the text for CSV
                    let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").replace(/"/g, '""');
                    if (text.includes(',')) {
                        text = '"' + text + '"';
                    }
                    row.push(text);
                }
                csv.push(row.join(','));
            }
            
            let csvContent = csv.join('\n');
            let blob = new Blob([csvContent], { type: 'text/csv' });
            let url = window.URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.setAttribute('hidden', '');
            a.setAttribute('href', url);
            a.setAttribute('download', 'inventory_report_<?php echo date('Y-m-d'); ?>.csv');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
        
        function scrollToForm() {
            window.scrollTo({
                top: document.querySelector('.content-card').offsetTop,
                behavior: 'smooth'
            });
        }
    </script>
</body>
</html>
