<?php
// Redirect to the new combined dashboard
header('Location: admin_attendance_dashboard.php');
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Attendance Notifications</title>
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
            text-decoration: none;
            display: block;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        .notification-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .main-content {
            padding: 20px;
            margin-left: 280px;
        }
        .notification-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        .notification-card.high-priority {
            border-left-color: #ff4757;
        }
        .notification-card.medium-priority {
            border-left-color: #ffa502;
        }
        .notification-card.low-priority {
            border-left-color: #2ed573;
        }
        .notification-photo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #667eea;
        }
        .notification-content {
            flex: 1;
        }
        .notification-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .priority-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .priority-high {
            background: #ffe4e6;
            color: #ff4757;
        }
        .priority-medium {
            background: #fff3cd;
            color: #ffa502;
        }
        .priority-low {
            background: #d4edda;
            color: #2ed573;
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
        .stats-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            opacity: 0.8;
        }
        
        /* Attendance.php styles */
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        .attendance-table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .attendance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 20px;
        }
        .attendance-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .search-filter-sort-container {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-input {
            flex: 1;
            min-width: 250px;
            max-width: 400px;
        }
        .search-input .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            color: #6c757d;
        }
        .search-input .form-control {
            border-left: none;
            border: 2px solid #e9ecef;
            border-radius: 0 10px 10px 0;
        }
        .search-input .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .filter-btn {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            color: #495057;
            transition: all 0.3s;
        }
        .filter-btn:hover {
            background: #e9ecef;
            color: #495057;
        }
        .sort-dropdown {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 10px 15px;
            font-weight: 500;
            color: #495057;
        }
        .attendance-table {
            border-radius: 10px;
            overflow: hidden;
        }
        .attendance-table thead {
            background: #f8f9fa;
        }
        .attendance-table th {
            border: none;
            padding: 15px;
            font-weight: 600;
            color: #495057;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        .attendance-table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        .attendance-table tbody tr:hover {
            background: #f8f9fa;
        }
        .employee-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.875rem;
        }
        .employee-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 2px;
        }
        .employee-title {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .check-time {
            font-size: 0.875rem;
            color: #495057;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-unread {
            background: #fff3cd;
            color: #856404;
        }
        .status-read {
            background: #d4edda;
            color: #155724;
        }
        .priority-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .priority-high {
            background: #f8d7da;
            color: #721c24;
        }
        .priority-medium {
            background: #fff3cd;
            color: #856404;
        }
        .priority-low {
            background: #d1ecf1;
            color: #0c5460;
        }
        .photo-preview {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .photo-preview:hover {
            transform: scale(1.05);
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
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
                                <i class="fas fa-tachometer-alt"></i> Home
                            </a>
                        </li>
                        <?php if (is_employee() || is_store()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="inventory.php">
                                <i class="fas fa-boxes"></i> Inventory
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
                        
                        <?php if (is_employee()): ?>
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
                <!-- Header -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">Attendance Notifications</h2>
                            <p class="text-muted mb-0">Real-time employee attendance photo submissions</p>
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

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <h3 class="mb-1"><?php echo count($unread_notifications); ?></h3>
                            <p class="text-muted mb-0">Unread Notifications</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <h3 class="mb-1">
                                <?php 
                                $today_count = array_filter($unread_notifications, function($n) {
                                    return date('Y-m-d', strtotime($n['created_at'])) == date('Y-m-d');
                                });
                                echo count($today_count);
                                ?>
                            </h3>
                            <p class="text-muted mb-0">Today's Submissions</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3 class="mb-1">24h</h3>
                            <p class="text-muted mb-0">Time Window</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3 class="mb-1">
                                <?php 
                                $high_priority = array_filter($unread_notifications, function($n) {
                                    return $n['priority'] === 'high';
                                });
                                echo count($high_priority);
                                ?>
                            </h3>
                            <p class="text-muted mb-0">High Priority</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <?php if (!empty($unread_notifications)): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="fas fa-bell"></i> Recent Photo Submissions</h4>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-outline-secondary">
                            <i class="fas fa-check-double"></i> Mark All Read
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Notifications List -->
                <?php if (!empty($unread_notifications)): ?>
                    <?php foreach ($unread_notifications as $notification): ?>
                        <div class="notification-card <?php echo $notification['priority']; ?>-priority">
                            <div class="d-flex align-items-start gap-3">
                                <?php if ($notification['photo_path'] && file_exists($notification['photo_path'])): ?>
                                    <img src="<?php echo $notification['photo_path']; ?>" alt="Attendance Photo" class="notification-photo">
                                <?php else: ?>
                                    <div class="notification-photo d-flex align-items-center justify-content-center bg-light">
                                        <i class="fas fa-camera fa-2x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="notification-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1">
                                                <?php echo $notification['first_name'] . ' ' . $notification['last_name']; ?>
                                                <small class="text-muted">(<?php echo $notification['emp_id']; ?>)</small>
                                            </h5>
                                            <span class="priority-badge priority-<?php echo $notification['priority']; ?>">
                                                <?php echo $notification['priority']; ?> priority
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <div class="notification-time">
                                                <i class="fas fa-clock"></i> 
                                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p class="mb-2"><?php echo $notification['message']; ?></p>
                                    
                                    <?php if ($notification['capture_time']): ?>
                                        <p class="mb-1">
                                            <i class="fas fa-clock"></i> 
                                            Capture Time: <?php echo $notification['capture_time']; ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex gap-2 mt-3">
                                        <a href="admin_attendance_approval.php#notification-<?php echo $notification['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_read" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-check"></i> Mark Read
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h4>All Caught Up!</h4>
                        <p>No new attendance photo submissions to review.</p>
                        <a href="admin_attendance_approval.php" class="btn btn-primary">
                            <i class="fas fa-user-check"></i> Review Attendance
                        </a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
