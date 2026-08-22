<?php
require_once 'config.php';

if (!is_logged_in() || (!is_admin() && !is_manager() && !is_store())) {
    redirect('index.php');
}

// Get unread messages count
$unread_messages = get_unread_message_count($_SESSION['user_id']);

// Get invoice ID
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($invoice_id === 0) {
    header('Location: invoice_list.php');
    exit;
}

// Get invoice details
$database = new Database();
$db = $database->getConnection();

$query = "SELECT pi.*, po.po_number, po.created_by, s.name as supplier_name
          FROM purchase_invoices pi
          LEFT JOIN purchase_orders po ON pi.po_id = po.id
          LEFT JOIN suppliers s ON po.supplier_id = s.id
          WHERE pi.id = :id";
if (is_store()) {
    $query .= " AND po.created_by = :user_id";
}
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $invoice_id, PDO::PARAM_INT);
if (is_store()) {
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
}
$stmt->execute();
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    header('Location: invoice_list.php');
    exit;
}

// Parse invoice notes
$invoice_data = json_decode($invoice['notes'], true);
$seller = $invoice_data['seller'] ?? [];
$buyer = $invoice_data['buyer'] ?? [];
$items = $invoice_data['items'] ?? [];
$tax_rate = $invoice_data['tax_rate'] ?? 0;
$discount = $invoice_data['discount'] ?? 0;

// Handle print request
if (isset($_GET['print']) && $_GET['print'] == '1') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #4CAF50; }
            .nav-arrows { margin-bottom: 20px; text-align: center; }
            .nav-arrows a { 
                display: inline-block; 
                margin: 0 10px; 
                padding: 8px 16px; 
                background-color: #4CAF50; 
                color: white; 
                text-decoration: none; 
                border-radius: 4px; 
                font-size: 14px;
            }
            .nav-arrows a:hover { background-color: #45a049; }
            .section { margin-bottom: 20px; }
            .section-title { font-weight: bold; font-size: 1.2rem; margin-bottom: 10px; border-bottom: 2px solid #4CAF50; padding-bottom: 5px; }
            .row { display: flex; margin-bottom: 10px; }
            .col { flex: 1; padding: 0 10px; }
            .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .table th { background-color: #4CAF50; color: white; }
            .text-right { text-align: right; }
            .total-row { font-weight: bold; }
            @media print { .no-print { display: none; } .nav-arrows { display: none; } }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>INVOICE</h1>
            <p>Invoice Number: <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
        </div>

        <div class="nav-arrows">
            <a href="invoice_list.php">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="javascript:history.back()">
                <i class="fas fa-arrow-left"></i> Previous
            </a>
            <a href="javascript:window.close()">
                <i class="fas fa-times"></i> Close
            </a>
        </div>

        <div class="section">
            <div class="row">
                <div class="col">
                    <strong>Date Issued:</strong> <?php echo date('M d, Y', strtotime($invoice['invoice_date'])); ?><br>
                    <strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?><br>
                    <strong>Status:</strong> <?php echo htmlspecialchars($invoice['status']); ?>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">SELLER (FROM)</div>
            <div class="row">
                <div class="col">
                    <strong><?php echo htmlspecialchars($seller['company'] ?? ''); ?></strong><br>
                    <?php echo htmlspecialchars($seller['address'] ?? ''); ?><br>
                    Email: <?php echo htmlspecialchars($seller['email'] ?? ''); ?><br>
                    Phone: <?php echo htmlspecialchars($seller['phone'] ?? ''); ?>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">BUYER (BILL TO)</div>
            <div class="row">
                <div class="col">
                    <strong><?php echo htmlspecialchars($buyer['name'] ?? ''); ?></strong><br>
                    <?php echo htmlspecialchars($buyer['company'] ?? ''); ?><br>
                    <?php echo htmlspecialchars($buyer['address'] ?? ''); ?><br>
                    Email: <?php echo htmlspecialchars($buyer['email'] ?? ''); ?>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">ITEMS / SERVICES</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>DESCRIPTION</th>
                        <th class="text-right">QTY</th>
                        <th class="text-right">UNIT PRICE</th>
                        <th class="text-right">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                        <td class="text-right"><?php echo $item['qty']; ?></td>
                        <td class="text-right">₱<?php echo number_format($item['price'], 2); ?></td>
                        <td class="text-right">₱<?php echo number_format($item['total'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right">Subtotal:</td>
                        <td class="text-right">₱<?php echo number_format($invoice['amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right">VAT/Tax (<?php echo $tax_rate; ?>%):</td>
                        <td class="text-right">₱<?php echo number_format($invoice['tax_amount'], 2); ?></td>
                    </tr>
                    <?php if ($discount > 0): ?>
                    <tr>
                        <td colspan="3" class="text-right">Discount:</td>
                        <td class="text-right">-₱<?php echo number_format($discount, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                        <td class="text-right"><strong>₱<?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Invoice View</title>
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
            margin-left: 280px;
            padding: 20px;
        }
        .invoice-container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .invoice-header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #4CAF50, #45a049);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #4CAF50;
            color: #4CAF50;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #555;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .btn-back {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin-right: 10px;
        }
        .btn-back:hover {
            background-color: #545b62;
            color: white;
        }
        .btn-print {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }
        .btn-print:hover {
            background-color: #0056b3;
            color: white;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: bold;
        }
        .status-unpaid {
            background-color: #dc3545;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: #6b4200;
        }
        .status-approved {
            background-color: #28a745;
            color: white;
        }
        .status-rejected {
            background-color: #dc3545;
            color: white;
        }
        .status-paid {
            background-color: #28a745;
            color: white;
        }
        .status-partial {
            background-color: #ffc107;
            color: black;
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
                        <?php if (is_employee() || is_store()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="inventory.php">
                                <i class="fas fa-boxes"></i> <?php echo is_employee() ? 'Inventory' : 'Inventory Management'; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (is_admin()): ?>
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
                        <li class="nav-item">
                            <a class="nav-link" href="employee_profile.php">
                                <i class="fas fa-users"></i> Employee Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="attendance.php">
                                <i class="fas fa-clock"></i> Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="delivery_tracking.php">
                                <i class="fas fa-truck"></i> Delivery Tracking
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="delivery_history.php">
                                <i class="fas fa-history"></i> Delivery History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="chat_interface.php">
                                <i class="fas fa-comments"></i> Messages
                                <?php if ($unread_messages > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_messages; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (is_store()): ?>
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
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showPONumbers(); return false;">
                                <i class="fas fa-hashtag"></i> PO Numbers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="chat_interface.php">
                                <i class="fas fa-comments"></i> Messages
                                <?php if ($unread_messages > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_messages; ?></span>
                                <?php endif; ?>
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
                <div class="invoice-container">
                    <div class="invoice-header">
                        <h1>INVOICE #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h1>
                        <div class="mb-3">
                            <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', $invoice['status'])); ?>">
                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $invoice['status']))); ?>
                            </span>
                        </div>
                        <div>
                            <a href="invoice_list.php" class="btn-back">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <a href="invoice_view.php?id=<?php echo $invoice['id']; ?>&print=1" class="btn-print" target="_blank">
                                <i class="fas fa-print"></i> Print Invoice
                            </a>
                        </div>
                    </div>

                    <!-- INVOICE DETAILS -->
                    <div class="section">
                        <div class="section-title">INVOICE DETAILS</div>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Date Issued:</strong> <?php echo date('M d, Y', strtotime($invoice['invoice_date'])); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?>
                            </div>
                        </div>
                    </div>

                    <!-- SELLER (FROM) -->
                    <div class="section">
                        <div class="section-title">SELLER (FROM)</div>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Company / Name:</strong> <?php echo htmlspecialchars($seller['company'] ?? ''); ?><br>
                                <strong>Email:</strong> <?php echo htmlspecialchars($seller['email'] ?? ''); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Address:</strong> <?php echo htmlspecialchars($seller['address'] ?? ''); ?><br>
                                <strong>Phone:</strong> <?php echo htmlspecialchars($seller['phone'] ?? ''); ?>
                            </div>
                        </div>
                    </div>

                    <!-- BUYER (BILL TO) -->
                    <div class="section">
                        <div class="section-title">BUYER (BILL TO)</div>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Full Name:</strong> <?php echo htmlspecialchars($buyer['name'] ?? ''); ?><br>
                                <strong>Company:</strong> <?php echo htmlspecialchars($buyer['company'] ?? ''); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Address:</strong> <?php echo htmlspecialchars($buyer['address'] ?? ''); ?><br>
                                <strong>Email:</strong> <?php echo htmlspecialchars($buyer['email'] ?? ''); ?>
                            </div>
                        </div>
                    </div>

                    <!-- ITEMS / SERVICES -->
                    <div class="section">
                        <div class="section-title">ITEMS / SERVICES</div>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>DESCRIPTION</th>
                                    <th class="text-right">QTY</th>
                                    <th class="text-right">UNIT PRICE</th>
                                    <th class="text-right">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td class="text-right"><?php echo $item['qty']; ?></td>
                                    <td class="text-right">₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="text-right">₱<?php echo number_format($item['total'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-right">Subtotal:</td>
                                    <td class="text-right">₱<?php echo number_format($invoice['amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-right">VAT/Tax (<?php echo $tax_rate; ?>%):</td>
                                    <td class="text-right">₱<?php echo number_format($invoice['tax_amount'], 2); ?></td>
                                </tr>
                                <?php if ($discount > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-right">Discount:</td>
                                    <td class="text-right">-₱<?php echo number_format($discount, 2); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr class="total-row">
                                    <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                                    <td class="text-right"><strong>₱<?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'mcbot_widget.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
