<?php
require_once 'config.php';

// Admin-only page
if (!is_admin()) {
    header('Location: dashboard.php');
    exit;
}

// Get employee ID from URL
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$employee_id) {
    header('Location: employee_profile.php');
    exit;
}

// Get employee details
$employee = get_employee_by_id($employee_id);

if (!$employee) {
    header('Location: employee_profile.php');
    exit;
}

// Get employee profile data if available
$employee_profile = null;
try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("
        SELECT 
            ep.*,
            u.full_name,
            u.email,
            u.role
        FROM employee_profiles ep
        LEFT JOIN users u ON ep.user_id = u.id
        WHERE ep.user_id = :user_id OR ep.employee_id = :employee_id
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $employee['user_id'] ?? 0, ':employee_id' => $employee['employee_id'] ?? '']);
    $employee_profile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Profile table might not exist or employee might not have profile
}

// Get education records
$education = [];
try {
    $database = new Database();
    $db = $database->getConnection();
    if ($db->query("SHOW TABLES LIKE 'employee_education'")->rowCount() > 0) {
        $eduStmt = $db->prepare("
            SELECT degree, school, field, gpa, year_start, year_end
            FROM employee_education
            WHERE user_id = :user_id
            ORDER BY year_start DESC
        ");
        $eduStmt->execute([':user_id' => $employee['user_id'] ?? 0]);
        $education = $eduStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Education table might not exist
}

// Get family records
$family = [];
try {
    $database = new Database();
    $db = $database->getConnection();
    if ($db->query("SHOW TABLES LIKE 'employee_family'")->rowCount() > 0) {
        $famStmt = $db->prepare("
            SELECT family_type AS type, person_name AS name
            FROM employee_family
            WHERE user_id = :user_id
            ORDER BY id ASC
        ");
        $famStmt->execute([':user_id' => $employee['user_id'] ?? 0]);
        $family = $famStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Family table might not exist
}

// Get unread messages count
$unread_messages = get_unread_message_count($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Employee Detail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --bg-color: #f9fafb;
        }
        body {
            background-color: var(--bg-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: #0d1578;
            min-height: 100vh;
            color: white;
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 9999;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 10px;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
            position: relative;
            z-index: 10000;
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
            pointer-events: none;
            z-index: 11;
        }
        .main-content {
            padding: 0;
            margin-left: var(--sidebar-width);
            width: calc(100vw - var(--sidebar-width));
            max-width: calc(100vw - var(--sidebar-width));
            min-width: 0;
            overflow-x: hidden;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background: white;
            padding: 20px 30px;
            margin-bottom: 0;
            border-radius: 0;
            border-bottom: 1px solid var(--border-color);
        }
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }
        .breadcrumb-item.active {
            color: var(--text-secondary);
        }
        
        /* Page Header */
        .page-header {
            background: white;
            padding: 30px;
            border-bottom: 1px solid var(--border-color);
        }
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }
        
        /* Tabs Container */
        .tabs-container {
            background: white;
            border-bottom: 2px solid var(--border-color);
            padding: 0 30px;
        }
        .nav-tabs {
            border: none;
            gap: 8px;
        }
        .nav-tabs .nav-link {
            border: none;
            color: var(--text-secondary);
            font-weight: 500;
            padding: 16px 20px;
            border-radius: 0;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            background: transparent;
        }
        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
            border-bottom-color: rgba(102, 126, 234, 0.3);
        }
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background: white;
            border-bottom-color: var(--primary-color);
        }
        
        /* Content Area */
        .content-area {
            padding: 30px;
        }
        
        /* Info Cards */
        .info-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
            overflow: hidden;
        }
        .info-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
        }
        .info-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        .edit-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .edit-btn:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        .info-card-body {
            padding: 24px;
        }
        
        /* Basic Info Layout */
        .basic-info-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .basic-info-left {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .basic-info-right {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .basic-info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .info-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .info-value {
            font-size: 1rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        /* Address Section */
        .address-item {
            margin-bottom: 20px;
        }
        .address-item:last-child {
            margin-bottom: 0;
        }
        
        /* Emergency Contact Grid */
        .emergency-contact-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .emergency-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        /* Education Section */
        .education-item {
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .education-item:last-child {
            margin-bottom: 0;
        }
        .education-degree {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }
        .education-school {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        .education-details {
            font-size: 0.9rem;
            color: #374151;
            margin-bottom: 5px;
        }
        .education-details span {
            margin: 0 5px;
        }
        .education-details span:first-child {
            margin-left: 0;
        }
        .education-year {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        /* Family Table */
        .family-table {
            width: 100%;
            border-collapse: collapse;
        }
        .family-table th {
            text-align: left;
            padding: 12px;
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
            border-bottom: 2px solid var(--border-color);
        }
        .family-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            color: #374151;
        }
        .family-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #d1d5db;
        }
        .empty-state p {
            font-size: 1.1rem;
        }
        
        /* Mobile Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                width: min(280px, 86vw);
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
            }
            body.sidebar-open .sidebar {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            .basic-info-layout {
                grid-template-columns: 1fr;
            }
            .emergency-contact-grid {
                grid-template-columns: 1fr;
            }
            .nav-tabs {
                padding: 0 10px;
            }
            .nav-tabs .nav-link {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            .content-area {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="appSidebar" class="sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="logo.png" alt="McPIL Logo" class="sidebar-logo" style="width: 80px; height: 80px; border-radius: 50%;">
                        <h4 class="mt-2">McPIL</h4>
                        <small>PHARMACEUTICAL LABORATORY</small>
                    </div>
                    
                    <ul class="nav flex-column">
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
                            <a class="nav-link active" href="employee_profile.php">
                                <i class="fas fa-users"></i> Employees
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
                        
                        <li class="nav-item mt-4">
                            <a class="nav-link text-danger" href="logout.php" style="background: rgba(220, 53, 69, 0.1); border-radius: 10px;">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="employee_profile.php">Employee</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Employee Detail</li>
                    </ol>
                </nav>
                
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">Employee Detail</h1>
                    <p class="page-subtitle">View and manage employee information</p>
                </div>
                
                <!-- Tabs -->
                <div class="tabs-container">
                    <ul class="nav nav-tabs" id="employeeTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="personal-info-tab" data-bs-toggle="tab" data-bs-target="#personalInfo" type="button" role="tab" aria-controls="personalInfo" aria-selected="true">Personal info</button>
                        </li>
                       
                    </ul>
                </div>
                
                <!-- Tab Content -->
                <div class="tab-content content-area" id="employeeTabContent">
                    <!-- Personal Info Tab -->
                    <div class="tab-pane fade show active" id="personalInfo" role="tabpanel" aria-labelledby="personal-info-tab">
                        <?php
                        $initials = strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1));
                        $profile_data = $employee_profile ?: $employee;
                        ?>
                        
                        <!-- Basic Information Card -->
                        <div class="info-card">
                            <div class="info-card-header">
                                <h5 class="info-card-title">Basic information</h5>
                                <button class="edit-btn" onclick="editSection('basic-info')"><i class="fas fa-pencil-alt"></i></button>
                            </div>
                            <div class="info-card-body">
                                <div class="basic-info-layout">
                                    <div class="basic-info-left">
                                        <div class="profile-photo">
                                            <?php echo $initials; ?>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">Name</div>
                                            <div class="info-value"><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">ID Number</div>
                                            <div class="info-value"><?php echo $employee['employee_id'] ?? 'N/A'; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">Gender</div>
                                            <div class="info-value"><?php echo $profile_data['gender'] ?? 'N/A'; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                                            <div class="info-value"><?php echo $employee['email']; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label"><i class="fas fa-phone"></i> Phone Number</div>
                                            <div class="info-value"><?php echo $profile_data['phone'] ?? $employee['phone'] ?? 'N/A'; ?></div>
                                        </div>
                                    </div>
                                    <div class="basic-info-right">
                                        <div class="basic-info-item">
                                            <div class="info-label">Place of birth</div>
                                            <div class="info-value"><?php echo $profile_data['place_of_birth'] ?? 'N/A'; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">Birth date</div>
                                            <div class="info-value"><?php echo ($profile_data['birth_date'] ?? '') ? date('M d, Y', strtotime($profile_data['birth_date'])) : 'N/A'; ?></div>
                                        <div class="basic-info-item">
                                            <div class="info-label">Marital Status</div>
                                            <div class="info-value"><?php echo $profile_data['marital_status'] ?? 'N/A'; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">Religion</div>
                                            <div class="info-value"><?php echo $profile_data['religion'] ?? 'N/A'; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Card -->
                        <div class="info-card">
                            <div class="info-card-header">
                                <h5 class="info-card-title">Address</h5>
                                <button class="edit-btn" onclick="editSection('address')"><i class="fas fa-pencil-alt"></i></button>
                            </div>
                        
                        </div>

                        <!-- Emergency Contact Card -->
                        <div class="info-card">
                            <div class="info-card-header">
                                <h5 class="info-card-title">Emergency contact</h5>
                                <button class="edit-btn" onclick="editSection('emergency')"><i class="fas fa-pencil-alt"></i></button>
                            </div>
                            <div class="info-card-body">
                                <div class="emergency-contact-grid">
                                    <div class="emergency-item">
                                        <div class="info-label">Name</div>
                                        <div class="info-value"><?php echo $profile_data['ec_name'] ?? 'N/A'; ?></div>
                                    </div>
                                    <div class="emergency-item">
                                        <div class="info-label">Relationship</div>
                                        <div class="info-value"><?php echo $profile_data['ec_relationship'] ?? 'N/A'; ?></div>
                                    </div>
                                    <div class="emergency-item">
                                        <div class="info-label">Phone number</div>
                                        <div class="info-value"><?php echo $profile_data['ec_phone'] ?? 'N/A'; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Education Card -->
                        <div class="info-card">
                            <div class="info-card-header">
                                <h5 class="info-card-title">Education</h5>
                                <button class="edit-btn" onclick="editSection('education')"><i class="fas fa-pencil-alt"></i></button>
                            </div>
                            <div class="info-card-body">
                                <?php if (!empty($education)): ?>
                                    <?php foreach ($education as $edu): ?>
                                    <div class="education-item">
                                        <div class="education-degree"><?php echo $edu['degree']; ?></div>
                                        <div class="education-school"><?php echo $edu['school']; ?></div>
                                        <div class="education-details">
                                            <span><?php echo $edu['field']; ?></span>
                                            <span>•</span>
                                            <span>GPA (<?php echo $edu['gpa']; ?>)</span>
                                        </div>
                                        <div class="education-year"><?php echo $edu['year_start']; ?> - <?php echo $edu['year_end']; ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-graduation-cap"></i>
                                        <p>No education records found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Family Card -->
                        <div class="info-card">
                            <div class="info-card-header">
                                <h5 class="info-card-title">Family</h5>
                                <button class="edit-btn" onclick="editSection('family')"><i class="fas fa-pencil-alt"></i></button>
                            </div>
                            <div class="info-card-body">
                                <?php if (!empty($family)): ?>
                                    <table class="family-table">
                                        <thead>
                                            <tr>
                                                <th>Family type</th>
                                                <th>Person name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($family as $fam): ?>
                                            <tr>
                                                <td><?php echo $fam['type']; ?></td>
                                                <td><?php echo $fam['name']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <p>No family records found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Details Tab -->
                    <div class="tab-pane fade" id="employeeDetails" role="tabpanel" aria-labelledby="employee-details-tab">
                        <div class="info-card">
                            <div class="info-card-header">
                                <h5 class="info-card-title">Employee details</h5>
                                <button class="edit-btn" onclick="editSection('employee-details')"><i class="fas fa-pencil-alt"></i></button>
                            </div>
                            <div class="info-card-body">
                                <div class="basic-info-layout">
                                    <div class="basic-info-left">
                                        <div class="basic-info-item">
                                            <div class="info-label">Department</div>
                                            <div class="info-value"><?php echo $employee['department'] ?? 'N/A'; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">Position</div>
                                            <div class="info-value"><?php echo $employee['position'] ?? 'N/A'; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">Date Hired</div>
                                            <div class="info-value"><?php echo $employee['hire_date'] ? date('M d, Y', strtotime($employee['hire_date'])) : 'N/A'; ?></div>
                                        </div>
                                    </div>
                                    <div class="basic-info-right">
                                        <div class="basic-info-item">
                                            <div class="info-label">Status</div>
                                            <div class="info-value">
                                                <?php 
                                                $status = $employee['status'] ?? 'active';
                                                $statusClass = $status == 'active' ? 'status-active' : 'status-inactive';
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($status); ?></span>
                                            </div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">Role</div>
                                            <div class="info-value"><?php echo ucfirst($employee['role'] ?? 'employee'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payroll Details Tab -->
                    <div class="tab-pane fade" id="payrollDetails" role="tabpanel" aria-labelledby="payroll-details-tab">
                        <div class="info-card">
                            <div class="info-card-header">
                                <h5 class="info-card-title">Payroll details</h5>
                                <button class="edit-btn" onclick="editSection('payroll-details')"><i class="fas fa-pencil-alt"></i></button>
                            </div>
                            <div class="info-card-body">
                                <div class="basic-info-layout">
                                    <div class="basic-info-left">
                                        <div class="basic-info-item">
                                            <div class="info-label">Basic Salary</div>
                                            <div class="info-value"><?php echo $profile_data['basic_salary'] ? '₱' . number_format($profile_data['basic_salary'], 2) : 'N/A'; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">Bank Name</div>
                                            <div class="info-value"><?php echo $profile_data['bank_name'] ?? 'N/A'; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">Bank Account</div>
                                            <div class="info-value"><?php echo $profile_data['bank_account'] ?? 'N/A'; ?></div>
                                        </div>
                                    </div>
                                    <div class="basic-info-right">
                                        <div class="basic-info-item">
                                            <div class="info-label">Tax ID</div>
                                            <div class="info-value"><?php echo $profile_data['tax_id'] ?? 'N/A'; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">SSS Number</div>
                                            <div class="info-value"><?php echo $profile_data['sss_number'] ?? 'N/A'; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">PhilHealth</div>
                                            <div class="info-value"><?php echo $profile_data['philhealth'] ?? 'N/A'; ?></div>
                                        </div>
                                        <div class="basic-info-item">
                                            <div class="info-label">Pag-IBIG</div>
                                            <div class="info-value"><?php echo $profile_data['pagibig'] ?? 'N/A'; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Tab -->
                    <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                        <div class="info-card">
                            <div class="info-card-header">
                                <h5 class="info-card-title">Documents</h5>
                                <button class="edit-btn" onclick="editSection('documents')"><i class="fas fa-pencil-alt"></i></button>
                            </div>
                            <div class="info-card-body">
                                <div class="empty-state">
                                    <i class="fas fa-file-alt"></i>
                                    <p>No documents uploaded yet</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payroll History Tab -->
                    <div class="tab-pane fade" id="payrollHistory" role="tabpanel" aria-labelledby="payroll-history-tab">
                        <div class="info-card">
                            <div class="info-card-header">
                                <h5 class="info-card-title">Payroll history</h5>
                            </div>
                            <div class="info-card-body">
                                <div class="empty-state">
                                    <i class="fas fa-history"></i>
                                    <p>No payroll history available</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Medical History Tab -->
                    <div class="tab-pane fade" id="medicalHistory" role="tabpanel" aria-labelledby="medical-history-tab">
                        <div class="info-card">
                            <div class="info-card-header">
                                <h5 class="info-card-title">Medical history</h5>
                                <button class="edit-btn" onclick="editSection('medical-history')"><i class="fas fa-pencil-alt"></i></button>
                            </div>
                            <div class="info-card-body">
                                <div class="empty-state">
                                    <i class="fas fa-notes-medical"></i>
                                    <p>No medical records available</p>
                                </div>
                            </div>
                        </div>
                    </div>

                 

                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'mcbot_widget.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" name="section" id="editSection">
                        <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                        
                        <!-- Basic Info Form -->
                        <div id="basic-info-form" class="edit-form" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" value="<?php echo $employee['first_name']; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" value="<?php echo $employee['last_name']; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ($profile_data['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($profile_data['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($profile_data['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo $profile_data['phone'] ?? $employee['phone'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Place of Birth</label>
                                    <input type="text" class="form-control" name="place_of_birth" value="<?php echo $profile_data['place_of_birth'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Birth Date</label>
                                    <input type="date" class="form-control" name="birth_date" value="<?php echo $profile_data['birth_date'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Marital Status</label>
                                    <select class="form-select" name="marital_status">
                                        <option value="">Select Status</option>
                                        <option value="Single" <?php echo ($profile_data['marital_status'] ?? '') == 'Single' ? 'selected' : ''; ?>>Single</option>
                                        <option value="Married" <?php echo ($profile_data['marital_status'] ?? '') == 'Married' ? 'selected' : ''; ?>>Married</option>
                                        <option value="Divorced" <?php echo ($profile_data['marital_status'] ?? '') == 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                        <option value="Widowed" <?php echo ($profile_data['marital_status'] ?? '') == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Religion</label>
                                    <input type="text" class="form-control" name="religion" value="<?php echo $profile_data['religion'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Address Form -->
                        <div id="address-form" class="edit-form" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Citizen ID Address</label>
                                <textarea class="form-control" name="citizen_address" rows="3"><?php echo $profile_data['citizen_address'] ?? ''; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Residential Address</label>
                                <textarea class="form-control" name="residential_address" rows="3"><?php echo $profile_data['residential_address'] ?? ''; ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Emergency Contact Form -->
                        <div id="emergency-form" class="edit-form" style="display: none;">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Emergency Contact Name</label>
                                    <input type="text" class="form-control" name="ec_name" value="<?php echo $profile_data['ec_name'] ?? ''; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Relationship</label>
                                    <input type="text" class="form-control" name="ec_relationship" value="<?php echo $profile_data['ec_relationship'] ?? ''; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" name="ec_phone" value="<?php echo $profile_data['ec_phone'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Education Form -->
                        <div id="education-form" class="edit-form" style="display: none;">
                            <div id="education-entries">
                                <?php if (!empty($education)): ?>
                                    <?php foreach ($education as $index => $edu): ?>
                                    <div class="education-entry mb-3 p-3 border rounded">
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">Degree</label>
                                                <input type="text" class="form-control" name="education[<?php echo $index; ?>][degree]" value="<?php echo $edu['degree']; ?>">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">School</label>
                                                <input type="text" class="form-control" name="education[<?php echo $index; ?>][school]" value="<?php echo $edu['school']; ?>">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">Field</label>
                                                <input type="text" class="form-control" name="education[<?php echo $index; ?>][field]" value="<?php echo $edu['field']; ?>">
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <label class="form-label">GPA</label>
                                                <input type="text" class="form-control" name="education[<?php echo $index; ?>][gpa]" value="<?php echo $edu['gpa']; ?>">
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label">Year Start</label>
                                                <input type="text" class="form-control" name="education[<?php echo $index; ?>][year_start]" value="<?php echo $edu['year_start']; ?>">
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label">Year End</label>
                                                <input type="text" class="form-control" name="education[<?php echo $index; ?>][year_end]" value="<?php echo $edu['year_end']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addEducationEntry()">
                                <i class="fas fa-plus"></i> Add Education
                            </button>
                        </div>
                        
                        <!-- Family Form -->
                        <div id="family-form" class="edit-form" style="display: none;">
                            <div id="family-entries">
                                <?php if (!empty($family)): ?>
                                    <?php foreach ($family as $index => $fam): ?>
                                    <div class="family-entry mb-3 p-3 border rounded">
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">Family Type</label>
                                                <input type="text" class="form-control" name="family[<?php echo $index; ?>][type]" value="<?php echo $fam['type']; ?>">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">Person Name</label>
                                                <input type="text" class="form-control" name="family[<?php echo $index; ?>][name]" value="<?php echo $fam['name']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addFamilyEntry()">
                                <i class="fas fa-plus"></i> Add Family Member
                            </button>
                        </div>
                        
                        <!-- Employee Details Form -->
                        <div id="employee-details-form" class="edit-form" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Department</label>
                                    <input type="text" class="form-control" name="department" value="<?php echo $employee['department'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Position</label>
                                    <input type="text" class="form-control" name="position" value="<?php echo $employee['position'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date Hired</label>
                                    <input type="date" class="form-control" name="hire_date" value="<?php echo $employee['hire_date'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active" <?php echo ($employee['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($employee['status'] ?? 'active') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payroll Details Form -->
                        <div id="payroll-details-form" class="edit-form" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Basic Salary</label>
                                    <input type="number" class="form-control" name="basic_salary" value="<?php echo $profile_data['basic_salary'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bank Name</label>
                                    <input type="text" class="form-control" name="bank_name" value="<?php echo $profile_data['bank_name'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bank Account</label>
                                    <input type="text" class="form-control" name="bank_account" value="<?php echo $profile_data['bank_account'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tax ID</label>
                                    <input type="text" class="form-control" name="tax_id" value="<?php echo $profile_data['tax_id'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SSS Number</label>
                                    <input type="text" class="form-control" name="sss_number" value="<?php echo $profile_data['sss_number'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">PhilHealth</label>
                                    <input type="text" class="form-control" name="philhealth" value="<?php echo $profile_data['philhealth'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Pag-IBIG</label>
                                    <input type="text" class="form-control" name="pagibig" value="<?php echo $profile_data['pagibig'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveSection()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let editModal;
        
        document.addEventListener('DOMContentLoaded', function() {
            editModal = new bootstrap.Modal(document.getElementById('editModal'));
        });
        
        function editSection(section) {
            // Hide all forms
            document.querySelectorAll('.edit-form').forEach(form => {
                form.style.display = 'none';
            });
            
            // Show the selected form
            const formId = section + '-form';
            const form = document.getElementById(formId);
            if (form) {
                form.style.display = 'block';
            }
            
            // Set the section name
            document.getElementById('editSection').value = section;
            
            // Update modal title
            const titles = {
                'basic-info': 'Edit Basic Information',
                'address': 'Edit Address',
                'emergency': 'Edit Emergency Contact',
                'education': 'Edit Education',
                'family': 'Edit Family',
                'employee-details': 'Edit Employee Details',
                'payroll-details': 'Edit Payroll Details'
            };
            document.getElementById('editModalLabel').textContent = titles[section] || 'Edit Section';
            
            // Show the modal
            editModal.show();
        }
        
        function addEducationEntry() {
            const container = document.getElementById('education-entries');
            const index = container.children.length;
            const entryHtml = `
                <div class="education-entry mb-3 p-3 border rounded">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Degree</label>
                            <input type="text" class="form-control" name="education[${index}][degree]">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">School</label>
                            <input type="text" class="form-control" name="education[${index}][school]">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Field</label>
                            <input type="text" class="form-control" name="education[${index}][field]">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">GPA</label>
                            <input type="text" class="form-control" name="education[${index}][gpa]">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Year Start</label>
                            <input type="text" class="form-control" name="education[${index}][year_start]">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Year End</label>
                            <input type="text" class="form-control" name="education[${index}][year_end]">
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', entryHtml);
        }
        
        function addFamilyEntry() {
            const container = document.getElementById('family-entries');
            const index = container.children.length;
            const entryHtml = `
                <div class="family-entry mb-3 p-3 border rounded">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Family Type</label>
                            <input type="text" class="form-control" name="family[${index}][type]">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Person Name</label>
                            <input type="text" class="form-control" name="family[${index}][name]">
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', entryHtml);
        }
        
        function saveSection() {
            const form = document.getElementById('editForm');
            const formData = new FormData(form);
            const section = formData.get('section');
            
            // Send AJAX request to save the data
            fetch('update_employee_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Changes saved successfully!');
                    editModal.hide();
                    // Reload the page to show updated data
                    location.reload();
                } else {
                    alert('Error saving changes: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving changes. Please try again.');
            });
        }
    </script>
</body>
</html>
