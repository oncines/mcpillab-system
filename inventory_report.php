<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('index.php');
}

// Get inventory data
$inventory_data = generate_inventory_report();
$inventory_summary = get_inventory_summary();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Inventory Report</title>
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
            margin-left: 280px;
        }
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
            margin: -25px -25px 25px -25px;
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
            color: #dc3545;
            font-weight: bold;
        }
        .stat-box {
            background: white;
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
            color: #667eea;
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
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
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
                            <a class="nav-link active" href="inventory.php">
                                <i class="fas fa-boxes"></i> Inventory Management
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
                        
                        <?php if (is_employee()): ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="inventory.php">
                                <i class="fas fa-boxes"></i> Inventory
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (is_admin() || is_manager()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="inventory_report.php">
                                <i class="fas fa-clipboard-list"></i> Inventory Report
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
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
                <div class="content-card no-print">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">Inventory Report</h2>
                            <p class="text-muted mb-0">Complete inventory status and suggested orders</p>
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

                <!-- Report Content -->
                <div class="content-card">
                    <div class="report-header">
                        <h2>INVENTORY FORM/SUGGESTED ORDER <?php echo date('Y'); ?></h2>
                        <p>Generated on <?php echo date('F d, Y'); ?></p>
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
                            </tbody>
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
                        <a href="inventory_form.php" class="btn btn-info">
                            <i class="fas fa-plus"></i> Add New Item
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'mcbot_widget.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html>
