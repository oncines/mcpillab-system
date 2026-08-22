<?php
require_once 'config.php';

if (!is_logged_in() || (!is_admin() && !is_manager() && !is_store())) {
    redirect('index.php');
}

// Get unread messages count
$unread_messages = get_unread_message_count($_SESSION['user_id']);

// Get all invoices
$database = new Database();
$db = $database->getConnection();
$po_filter = trim($_GET['po'] ?? '');

$query = "SELECT pi.*, po.po_number, po.created_by, s.name as supplier_name
          FROM purchase_invoices pi
          LEFT JOIN purchase_orders po ON pi.po_id = po.id
          LEFT JOIN suppliers s ON po.supplier_id = s.id
          WHERE 1=1";

if (is_store()) {
    $query .= " AND po.created_by = :user_id";
}

if ($po_filter !== '') {
    $query .= " AND po.po_number = :po_number";
}

$query .= " ORDER BY pi.created_at DESC";
$stmt = $db->prepare($query);
if (is_store()) {
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
}
if ($po_filter !== '') {
    $stmt->bindValue(':po_number', $po_filter);
}
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Invoice List</title>
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
            max-width: 1200px;
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
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-table th {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .invoice-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .invoice-table tbody tr:hover {
            background-color: #f8f9fa;
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
        .status-partially-paid {
            background-color: #ffc107;
            color: black;
        }
        .btn-view {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.875rem;
        }
        .btn-view:hover {
            background-color: #0056b3;
            color: white;
        }
        .btn-print {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.875rem;
            margin-left: 5px;
        }
        .btn-print:hover {
            background-color: #545b62;
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
    <link rel="stylesheet" href="sidebar-standard.css">
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
                        <?php if (is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
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
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (is_employee()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="inventory.php">
                                <i class="fas fa-boxes"></i> Inventory
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="attendance_camera.php">
                                <i class="fas fa-clock"></i> Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="attendance_history.php">
                                <i class="fas fa-history"></i> Attendance History
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
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (is_store()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="inventory.php">
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
                            <a class="nav-link active" href="invoice_list.php">
                                <i class="fas fa-list"></i> Invoice List
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
                        <h1>INVOICE LIST</h1>
                        <p class="text-muted">View and manage all your purchase invoices</p>
                        <?php if ($po_filter !== ''): ?>
                            <p>
                                Filtered by PO: <strong><?php echo htmlspecialchars($po_filter); ?></strong>
                                <a href="invoice_list.php" class="btn-view ms-2">Show All</a>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if (count($invoices) > 0): ?>
                        <div class="table-responsive">
                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th>Invoice Number</th>
                                        <th>Purchase Order</th>
                                        <th>Date Issued</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $invoice): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                            <td>
                                                <?php echo htmlspecialchars($invoice['po_number'] ?? '-'); ?>
                                                <?php if (!empty($invoice['supplier_name'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($invoice['supplier_name']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($invoice['invoice_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                                            <td><?php echo '₱' . number_format($invoice['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', $invoice['status'])); ?>">
                                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $invoice['status']))); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="invoice_view.php?id=<?php echo $invoice['id']; ?>" class="btn-view">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="invoice_view.php?id=<?php echo $invoice['id']; ?>&print=1" class="btn-print" target="_blank">
                                                    <i class="fas fa-print"></i> Print
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-invoice"></i>
                            <h3>No Invoices Found</h3>
                            <p>You haven't created any invoices yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <?php include 'mcbot_widget.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
