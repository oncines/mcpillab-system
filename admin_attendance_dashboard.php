<?php
require_once 'config.php';

// Check if user is logged in and is admin/manager
if (!is_logged_in() || !is_admin() && !is_manager()) {
    redirect('index.php');
}

// Handle approval actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_attendance'])) {
        $camera_attendance_id = $_POST['camera_attendance_id'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        if (approve_camera_attendance($camera_attendance_id, $admin_notes)) {
            $success_message = "Attendance approved successfully!";
        } else {
            $error_message = "Failed to approve attendance.";
        }
    }
    
    if (isset($_POST['reject_attendance'])) {
        $camera_attendance_id = $_POST['camera_attendance_id'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        if (reject_camera_attendance($camera_attendance_id, $admin_notes)) {
            $success_message = "Attendance rejected successfully!";
        } else {
            $error_message = "Failed to reject attendance.";
        }
    }
    
    if (isset($_POST['mark_read'])) {
        $notification_id = $_POST['notification_id'];
        mark_notification_read($notification_id);
        header('Location: admin_attendance_dashboard.php');
        exit();
    }
    
    if (isset($_POST['mark_all_read'])) {
        $unread_notifications = get_unread_attendance_notifications(100);
        foreach ($unread_notifications as $notification) {
            mark_notification_read($notification['id']);
        }
        $success_message = "All notifications marked as read.";
    }
}

// Get data
$pending_attendance = get_pending_camera_attendance(50);
$unread_notifications = get_unread_attendance_notifications(20);
$unread_messages = get_unread_message_count($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Attendance Dashboard</title>
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
            pointer-events: auto !important;
            cursor: pointer !important;
            text-decoration: none;
            display: block;
            position: relative;
            z-index: 101;
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
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
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
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            opacity: 0.8;
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
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        .status-unread {
            background: #fff3cd;
            color: #856404;
        }
        .status-read {
            background: #d4edda;
            color: #155724;
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
        .btn-approve {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        .tab-content {
            padding-top: 20px;
        }
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            border: none;
            background: #f8f9fa;
            color: #495057;
            font-weight: 500;
            padding: 12px 24px;
            margin-right: 5px;
        }
        .nav-tabs .nav-link.active {
            background: white;
            color: #667eea;
            border-bottom: 3px solid #667eea;
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
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <?php if (is_employee() || is_store()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="inventory.php">
                                <i class="fas fa-boxes"></i> Inventory Management
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
                            <h2 class="mb-0">Attendance Dashboard</h2>
                            <p class="text-muted mb-0">Manage photo notifications and attendance approval in one place</p>
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
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <h3 class="mb-1"><?php echo count($pending_attendance); ?></h3>
                            <p class="text-muted mb-0">Pending Approval</p>
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

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="attendanceTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                            <i class="fas fa-bell"></i> Photo Notifications
                            <?php if (count($unread_notifications) > 0): ?>
                                <span class="badge bg-danger ms-1"><?php echo count($unread_notifications); ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="approval-tab" data-bs-toggle="tab" data-bs-target="#approval" type="button" role="tab">
                            <i class="fas fa-user-check"></i> Attendance Approval
                            <?php if (count($pending_attendance) > 0): ?>
                                <span class="badge bg-warning ms-1"><?php echo count($pending_attendance); ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="attendanceTabsContent">
                    <!-- Notifications Tab -->
                    <div class="tab-pane fade show active" id="notifications" role="tabpanel">
                        <?php if (!empty($unread_notifications)): ?>
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4><i class="fas fa-bell"></i> Recent Photo Submissions</h4>
                                <form method="POST" style="display: inline;">
                                    <button type="submit" name="mark_all_read" class="btn btn-outline-secondary">
                                        <i class="fas fa-check-double"></i> Mark All Read
                                    </button>
                                </form>
                            </div>
                            
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
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="camera_attendance_id" value="<?php echo $notification['camera_attendance_id']; ?>">
                                                    <button type="submit" name="approve_attendance" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Quick Approve
                                                    </button>
                                                </form>
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
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Approval Tab -->
                    <div class="tab-pane fade" id="approval" role="tabpanel">
                        <div class="attendance-table-container">
                            <div class="attendance-header">
                                <h2 class="attendance-title">Pending Camera Attendance</h2>
                                <div class="search-filter-sort-container">
                                    <div class="input-group search-input me-3">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" placeholder="Search employee" id="searchInput">
                                    </div>
                                    <div class="dropdown me-3">
                                        <button class="btn filter-btn dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                                            <li><a class="dropdown-item" href="#">All Status</a></li>
                                            <li><a class="dropdown-item" href="#">Pending</a></li>
                                            <li><a class="dropdown-item" href="#">Approved</a></li>
                                            <li><a class="dropdown-item" href="#">Rejected</a></li>
                                        </ul>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn sort-dropdown dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            Sort by: Date (Newest)
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                                            <li><a class="dropdown-item" href="#">Date (Newest)</a></li>
                                            <li><a class="dropdown-item" href="#">Date (Oldest)</a></li>
                                            <li><a class="dropdown-item" href="#">Name (A-Z)</a></li>
                                            <li><a class="dropdown-item" href="#">Status</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($pending_attendance)): ?>
                                <div class="table-responsive">
                                    <table class="table attendance-table">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Date & Time</th>
                                                <th>Location</th>
                                                <th>Photo</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_attendance as $attendance): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="employee-avatar me-3">
                                                            <?php echo strtoupper(substr($attendance['first_name'], 0, 1) . substr($attendance['last_name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <div class="employee-name"><?php echo $attendance['first_name'] . ' ' . $attendance['last_name']; ?></div>
                                                            <div class="employee-title"><?php echo $attendance['position'] ?? 'Employee'; ?></div>
                                                            <small class="text-muted"><?php echo $attendance['emp_id']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="check-time">
                                                        <i class="fas fa-calendar"></i> <?php echo format_date($attendance['capture_date']); ?><br>
                                                        <i class="fas fa-clock"></i> <?php echo $attendance['capture_time']; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="check-time">
                                                        <i class="fas fa-map-marker-alt"></i> <?php echo $attendance['location_address'] ?: 'Location not available'; ?><br>
                                                        <small class="text-muted">Azimuth: <?php echo $attendance['azimuth'] ?: 'N/A'; ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <img src="<?php echo htmlspecialchars($attendance['photo_path']); ?>" alt="Attendance Photo" class="photo-preview" onclick="window.open('<?php echo htmlspecialchars($attendance['photo_path']); ?>', '_blank')" onerror="this.src='public/images/no-photo.svg'; this.alt='Photo not found';">
                                                </td>
                                                <td>
                                                    <span class="status-badge status-pending">Pending</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="camera_attendance_id" value="<?php echo $attendance['id']; ?>">
                                                            <button type="submit" name="approve_attendance" class="btn btn-sm btn-success">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                        </form>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="camera_attendance_id" value="<?php echo $attendance['id']; ?>">
                                                            <button type="submit" name="reject_attendance" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-camera fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No pending attendance to review</h5>
                                    <p class="text-muted">All camera attendance submissions have been processed.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);

        // Ensure sidebar links are clickable
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
            sidebarLinks.forEach(link => {
                link.style.pointerEvents = 'auto';
                link.style.cursor = 'pointer';
                
                link.addEventListener('click', function(e) {
                    if (this.href) {
                        window.location.href = this.href;
                    }
                });
            });
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('.attendance-table tbody tr');
            
            rows.forEach(row => {
                const employeeName = row.querySelector('.employee-name').textContent.toLowerCase();
                const employeeId = row.querySelector('.text-muted').textContent.toLowerCase();
                
                if (employeeName.includes(searchValue) || employeeId.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
