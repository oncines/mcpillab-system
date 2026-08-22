<?php
require_once 'config.php';

// Function to create employee
function create_employee($employee_id, $first_name, $last_name, $email, $phone, $department, $position, $hire_date, $salary) {
    $database = new Database();
    $db = $database->getConnection();
    $query = "INSERT INTO employees (employee_id, first_name, last_name, email, phone, department, position, hire_date, salary) 
              VALUES (:employee_id, :first_name, :last_name, :email, :phone, :department, :position, :hire_date, :salary)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':department', $department);
    $stmt->bindParam(':position', $position);
    $stmt->bindParam(':hire_date', $hire_date);
    $stmt->bindParam(':salary', $salary);
    return $stmt->execute();
}

function update_employee($id, $first_name, $last_name, $email, $phone, $department, $position, $salary, $status) {
    $database = new Database();
    $db = $database->getConnection();
    $query = "UPDATE employees SET first_name=:first_name, last_name=:last_name, email=:email, phone=:phone, department=:department, position=:position, salary=:salary, status=:status WHERE id=:id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':department', $department);
    $stmt->bindParam(':position', $position);
    $stmt->bindParam(':salary', $salary);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_employee'])) {
    $employee_id = generate_employee_id();
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $department = sanitize_input($_POST['department']);
    $position = sanitize_input($_POST['position']);
    $hire_date = $_POST['hire_date'];
    $salary = $_POST['salary'];
    if (create_employee($employee_id, $first_name, $last_name, $email, $phone, $department, $position, $hire_date, $salary)) {
        $success_message = "Employee created successfully! Employee ID: " . $employee_id;
    } else {
        $error_message = "Failed to create employee. Email might already exist.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_employee'])) {
    $id = $_POST['employee_id'];
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $department = sanitize_input($_POST['department']);
    $position = sanitize_input($_POST['position']);
    $salary = $_POST['salary'];
    $status = $_POST['status'];
    if (update_employee($id, $first_name, $last_name, $email, $phone, $department, $position, $salary, $status)) {
        $success_message = "Employee updated successfully!";
    } else {
        $error_message = "Failed to update employee.";
    }
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';
$status_filter = $_GET['status'] ?? '';
$employees = get_employees_with_filters($limit, $offset, $search, $department_filter, $status_filter);
$total_employees = get_employees_count($search, $department_filter, $status_filter);
$total_pages = ceil($total_employees / $limit);
$employee_stats = get_employee_statistics();
$editing_employee = null;
if (isset($_GET['edit'])) {
    $editing_employee = get_employee_by_id($_GET['edit']);
}
$unread_messages = get_unread_message_count($_SESSION['user_id']);

$active_count = $employee_stats['active'] ?? 28;
$inactive_count = $employee_stats['inactive'] ?? 4;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Employees</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="public/css/design-system.css" rel="stylesheet">
    <style>
        /* =====================
           CSS VARIABLES
           ===================== */
        :root {
            --sidebar-w: 220px;
            --sb-bg: #0d1b3e;
            --sb-active: rgba(255,255,255,0.10);
            --sb-hover: rgba(255,255,255,0.06);
            --sb-label: rgba(255,255,255,0.38);
            --sb-icon-bg: rgba(255,255,255,0.08);
            --sb-icon-active: #2f69ff;
            --sb-text: rgba(255,255,255,0.78);
            --sb-text-active: #ffffff;
            --sb-border: rgba(255,255,255,0.07);
            --sb-radius: 10px;

            /* Page colors */
            --page-bg: #f5f6fa;
            --card-bg: #ffffff;
            --border-col: #e8eaf0;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --navy: #0d1b3e;
            --navy-mid: #1e3a8a;
            --accent: #2f69ff;

            /* Status colors */
            --active-bg: #dcfce7;
            --active-text: #166534;
            --inactive-bg: #fce8e8;
            --inactive-text: #991b1b;
            --invited-bg: #fef3c7;
            --invited-text: #92400e;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--page-bg); color: var(--text-primary); }

        /* =====================
           SIDEBAR
           ===================== */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: var(--sidebar-w); height: 100vh;
            background: var(--sb-bg);
            display: flex; flex-direction: column;
            z-index: 9999; overflow-y: auto; overflow-x: hidden;
            scrollbar-width: none;
        }
        .sidebar::-webkit-scrollbar { display: none; }

        .sidebar-brand {
            display: flex; align-items: center; gap: 12px;
            padding: 20px 16px 18px;
            border-bottom: 1px solid var(--sb-border);
            margin-bottom: 10px;
        }
        .sidebar-logo-ring {
            width: 40px; height: 40px; border-radius: 50%;
            overflow: hidden; flex-shrink: 0;
            border: 2px solid rgba(255,255,255,0.30);
            display: flex; align-items: center; justify-content: center;
        }
        .sidebar-logo-ring img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .sidebar-brand-text { display: flex; flex-direction: column; justify-content: center; min-width: 0; gap: 2px; }
        .sidebar-brand-name { font-size: 0.92rem; font-weight: 800; color: #fff; letter-spacing: 0.06em; line-height: 1.1; text-transform: uppercase; }
        .sidebar-brand-sub { font-size: 0.55rem; color: rgba(255,255,255,0.45); letter-spacing: 0.10em; text-transform: uppercase; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .sidebar-nav { flex: 1; padding: 0 10px; }
        .nav-section-label { font-size: 0.62rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: var(--sb-label); padding: 14px 8px 6px; }

        .sidebar-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 10px; border-radius: var(--sb-radius);
            color: var(--sb-text); text-decoration: none;
            font-size: 0.84rem; font-weight: 500;
            transition: background 0.15s, color 0.15s;
            position: relative; margin-bottom: 2px;
        }
        .sidebar-link:hover { background: var(--sb-hover); color: var(--sb-text-active); }
        .sidebar-link.active { background: var(--sb-active); color: var(--sb-text-active); }
        .sidebar-link .icon {
            width: 30px; height: 30px; border-radius: 7px;
            background: var(--sb-icon-bg);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; flex-shrink: 0; transition: background 0.15s;
        }
        .sidebar-link.active .icon { background: var(--sb-icon-active); color: #fff; }
        .sidebar-link .link-label { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-link .badge-dot {
            width: 18px; height: 18px; border-radius: 50%;
            background: #e5534b; color: #fff; font-size: 0.6rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .sidebar-footer { padding: 10px 10px 20px; border-top: 1px solid var(--sb-border); margin-top: 6px; }
        .sidebar-link.logout .icon { background: rgba(229,83,75,0.15); color: #e5534b; }
        .sidebar-link.logout { color: rgba(229,83,75,0.85); }
        .sidebar-link.logout:hover { background: rgba(229,83,75,0.10); color: #e5534b; }

        .mobile-sidebar-toggle {
            display: none; align-items: center; justify-content: center;
            width: 40px; height: 40px; border: none; border-radius: 10px;
            background: var(--navy); color: #fff; cursor: pointer; flex-shrink: 0;
        }
        .mobile-sidebar-backdrop { display: none; }

        @media (max-width: 991.98px) {
            .sidebar { width: min(var(--sidebar-w), 86vw); transform: translateX(-100%); transition: transform 0.3s ease; }
            body.sidebar-open .sidebar { transform: translateX(0); }
            .main-content { margin-left: 0 !important; }
            .mobile-sidebar-toggle { display: inline-flex; }
            .mobile-sidebar-backdrop {
                display: block; position: fixed; inset: 0;
                background: rgba(9,15,85,0.45); opacity: 0; pointer-events: none;
                transition: opacity 0.3s ease; z-index: 9998;
            }
            body.sidebar-open .mobile-sidebar-backdrop { opacity: 1; pointer-events: auto; }
        }

        /* =====================
           MAIN LAYOUT
           ===================== */
        .main-content { margin-left: var(--sidebar-w); min-height: 100vh; padding: 0; }

        /* =====================
           TOP HEADER BAR
           ===================== */
        .top-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-col);
            padding: 16px 28px;
            display: flex; align-items: center;
            justify-content: space-between; gap: 16px;
            position: sticky; top: 0; z-index: 100;
        }
        .top-header-left { display: flex; align-items: center; gap: 14px; }
        .page-title { font-size: 1.35rem; font-weight: 700; color: var(--text-primary); line-height: 1.2; }
        .page-subtitle { font-size: 0.75rem; color: var(--text-muted); margin-top: 1px; }

        .top-header-right { display: flex; align-items: center; gap: 14px; }
        .user-avatar-sm {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--navy) 0%, #2f69ff 100%);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 0.75rem; flex-shrink: 0;
        }
        .user-name-sm { font-size: 0.85rem; font-weight: 600; color: var(--text-primary); }
        .user-role-sm { font-size: 0.72rem; color: var(--text-muted); }

        /* =====================
           PAGE BODY
           ===================== */
        .page-body { padding: 24px 28px; }

        /* =====================
           FILTER / TOOLBAR
           ===================== */
        .toolbar {
            background: var(--card-bg);
            border: 1px solid var(--border-col);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 22px;
        }
        .toolbar-main { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }

        .search-wrap { position: relative; flex: 1; min-width: 200px; max-width: 300px; }
        .search-wrap .fa-search { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.8rem; pointer-events: none; }
        .search-input {
            width: 100%; padding: 9px 12px 9px 34px;
            border: 1px solid var(--border-col); border-radius: 8px;
            font-size: 0.85rem; font-family: 'DM Sans', sans-serif;
            color: var(--text-primary); background: var(--page-bg);
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(47,105,255,0.10); }
        .search-input::placeholder { color: var(--text-muted); }

        .toolbar-actions { display: flex; gap: 8px; align-items: center; }
        .btn-outline-sm {
            display: flex; align-items: center; gap: 6px;
            padding: 9px 14px; border: 1px solid var(--border-col);
            border-radius: 8px; background: var(--card-bg);
            font-size: 0.83rem; font-family: 'DM Sans', sans-serif;
            color: var(--text-secondary); cursor: pointer; transition: all 0.2s; white-space: nowrap;
        }
        .btn-outline-sm:hover { border-color: var(--text-secondary); }

        .btn-add {
            display: flex; align-items: center; gap: 7px;
            padding: 9px 18px; border: none; border-radius: 8px;
            background: var(--navy); color: #fff;
            font-size: 0.85rem; font-weight: 600; font-family: 'DM Sans', sans-serif;
            cursor: pointer; transition: all 0.2s; white-space: nowrap;
        }
        .btn-add:hover { background: #1a2d5a; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(13,27,62,0.25); }

        /* =====================
           EMPLOYEE GRID
           ===================== */
        .employee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px;
        }

        /* =====================
           EMPLOYEE CARD
           ===================== */
        .emp-card {
            background: var(--card-bg);
            border: 1px solid var(--border-col);
            border-radius: 16px;
            padding: 20px;
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative;
        }
        .emp-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.09); transform: translateY(-2px); }

        .emp-card-top {
            display: flex; justify-content: space-between;
            align-items: flex-start; margin-bottom: 16px;
        }
        .status-badge {
            font-size: 0.7rem; font-weight: 700; text-transform: capitalize;
            padding: 4px 10px; border-radius: 20px; letter-spacing: 0.01em;
        }
        .status-badge.active { background: var(--active-bg); color: var(--active-text); }
        .status-badge.inactive { background: var(--inactive-bg); color: var(--inactive-text); }
        .status-badge.invited { background: var(--invited-bg); color: var(--invited-text); }
        .status-badge.on-leave { background: #e0f2fe; color: #075985; }

        .emp-menu-btn {
            background: none; border: none; cursor: pointer;
            color: var(--text-muted); padding: 2px 6px; border-radius: 6px;
            font-size: 0.9rem; transition: background 0.15s;
        }
        .emp-menu-btn:hover { background: var(--page-bg); color: var(--text-primary); }

        /* Avatar circle */
        .emp-avatar-wrap { display: flex; justify-content: center; margin-bottom: 12px; }
        .emp-avatar {
            width: 72px; height: 72px; border-radius: 50%;
            background: linear-gradient(135deg, #c7d2fe 0%, #818cf8 100%);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.5rem; color: #3730a3;
            border: 3px solid var(--card-bg); box-shadow: 0 0 0 2px var(--border-col);
            overflow: hidden; flex-shrink: 0;
        }
        .emp-avatar img { width: 100%; height: 100%; object-fit: cover; }

        /* Avatar color variants */
        .emp-avatar.av-blue { background: linear-gradient(135deg, #bfdbfe 0%, #60a5fa 100%); color: #1e40af; }
        .emp-avatar.av-green { background: linear-gradient(135deg, #bbf7d0 0%, #4ade80 100%); color: #166534; }
        .emp-avatar.av-purple { background: linear-gradient(135deg, #e9d5ff 0%, #c084fc 100%); color: #6b21a8; }
        .emp-avatar.av-orange { background: linear-gradient(135deg, #fed7aa 0%, #fb923c 100%); color: #9a3412; }
        .emp-avatar.av-pink { background: linear-gradient(135deg, #fce7f3 0%, #f472b6 100%); color: #9d174d; }
        .emp-avatar.av-teal { background: linear-gradient(135deg, #ccfbf1 0%, #2dd4bf 100%); color: #134e4a; }

        .emp-name { font-size: 1rem; font-weight: 700; color: var(--text-primary); text-align: center; margin-bottom: 2px; }
        .emp-position { font-size: 0.8rem; color: var(--text-muted); text-align: center; margin-bottom: 14px; }

        /* Info rows */
        .emp-info-block { border-top: 1px solid var(--border-col); padding-top: 14px; margin-bottom: 14px; }
        .emp-info-row { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 6px; font-size: 0.8rem; color: var(--text-secondary); }
        .emp-info-row:last-child { margin-bottom: 0; }
        .emp-info-icon { width: 20px; height: 20px; border-radius: 5px; background: #f0f4ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .emp-info-icon i { font-size: 0.65rem; color: var(--accent); }
        .emp-info-val { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-primary); font-size: 0.8rem; }
        .emp-info-label { font-size: 0.72rem; color: var(--text-muted); }

        /* Tags row */
        .emp-tags { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 14px; }
        .emp-tag {
            font-size: 0.7rem; font-weight: 500; padding: 3px 9px;
            border-radius: 12px; border: 1px solid var(--border-col);
            color: var(--text-secondary); background: var(--page-bg);
            white-space: nowrap;
        }
        .emp-tag.tag-dept { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
        .emp-tag.tag-type { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }

        /* Meta row */
        .emp-meta { display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); padding-top: 12px; border-top: 1px solid var(--border-col); }
        .emp-meta-date { display: flex; align-items: center; gap: 4px; }

        .view-details-link {
            font-size: 0.8rem; font-weight: 600; color: var(--accent);
            text-decoration: none; display: flex; align-items: center; gap: 4px;
            transition: gap 0.2s;
        }
        .view-details-link:hover { gap: 7px; }

        /* Employee ID badge */
        .emp-id-tag {
            display: inline-flex; align-items: center; gap: 4px;
            font-family: 'DM Mono', monospace; font-size: 0.72rem;
            color: var(--text-muted); background: var(--page-bg);
            border: 1px solid var(--border-col); border-radius: 6px;
            padding: 2px 7px; margin-bottom: 10px;
        }

        /* Alerts */
        .alert { border-radius: 10px; font-size: 0.88rem; margin-bottom: 16px; }

        /* =====================
           EMPTY STATE
           ===================== */
        .empty-state { text-align: center; padding: 60px 20px; grid-column: 1/-1; }
        .empty-state .empty-icon { font-size: 2.5rem; color: var(--border-col); margin-bottom: 16px; }
        .empty-state h4 { font-size: 1rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; }
        .empty-state p { font-size: 0.85rem; color: var(--text-muted); }

        /* =====================
           DROPDOWN TWEAKS
           ===================== */
        .dropdown-menu { border-radius: 10px; border: 1px solid var(--border-col); box-shadow: 0 8px 24px rgba(0,0,0,0.10); font-size: 0.85rem; }
        .dropdown-item { padding: 8px 14px; border-radius: 7px; margin: 2px 4px; }
        .dropdown-item:hover { background: var(--page-bg); }

        /* =====================
           MODAL TWEAKS
           ===================== */
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 24px 60px rgba(0,0,0,0.15); }
        .modal-header { border-bottom: 1px solid var(--border-col); padding: 18px 22px; }
        .modal-footer { border-top: 1px solid var(--border-col); padding: 14px 22px; }

        /* =====================
           RESPONSIVE
           ===================== */
        @media (max-width: 767px) {
            .page-body { padding: 16px; }
            .top-header { padding: 14px 16px; }
            .employee-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .page-title { font-size: 1.1rem; }
        }
        @media (max-width: 500px) {
            .employee-grid { grid-template-columns: 1fr; }
            .toolbar-main { flex-direction: column; align-items: stretch; }
            .search-wrap { max-width: 100%; }
        }
    </style>
</head>
<body>

    <!-- ===================== SIDEBAR ===================== -->
    <nav id="appSidebar" class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-logo-ring">
                <img src="logo.png" alt="McPIL Logo">
            </div>
            <div class="sidebar-brand-text">
                <div class="sidebar-brand-name">McPIL</div>
                <div class="sidebar-brand-sub">Pharmaceutical Laboratory</div>
            </div>
        </div>

        <div class="sidebar-nav">
            <div class="nav-section-label">Main</div>

            <a class="sidebar-link" href="dashboard.php">
                <span class="icon"><i class="fas fa-tachometer-alt"></i></span>
                <span class="link-label"><?php echo is_employee() ? 'Home' : 'Dashboard'; ?></span>
            </a>

            <?php if (is_employee() || is_store()): ?>
            <a class="sidebar-link" href="inventory.php">
                <span class="icon"><i class="fas fa-boxes"></i></span>
                <span class="link-label"><?php echo is_employee() ? 'Inventory' : 'Inventory Management'; ?></span>
            </a>
            <?php endif; ?>

            <?php if (is_admin()): ?>
            <a class="sidebar-link" href="purchase_order.php">
                <span class="icon"><i class="fas fa-shopping-cart"></i></span>
                <span class="link-label">Purchase Order</span>
            </a>
            <a class="sidebar-link" href="purchase_invoice.php">
                <span class="icon"><i class="fas fa-file-invoice"></i></span>
                <span class="link-label">Purchase Invoice</span>
            </a>
            <a class="sidebar-link active" href="employee_profile.php">
                <span class="icon"><i class="fas fa-users"></i></span>
                <span class="link-label">Employee Profile</span>
            </a>
            <a class="sidebar-link" href="attendance.php">
                <span class="icon"><i class="fas fa-clock"></i></span>
                <span class="link-label">Attendance</span>
            </a>
            <?php endif; ?>

            <?php if (is_employee()): ?>
            <a class="sidebar-link" href="attendance_camera.php">
                <span class="icon"><i class="fas fa-clock"></i></span>
                <span class="link-label">Attendance</span>
            </a>
            <a class="sidebar-link" href="attendance_history.php">
                <span class="icon"><i class="fas fa-history"></i></span>
                <span class="link-label">Attendance History</span>
            </a>
            <?php endif; ?>

            <?php if (is_store()): ?>
            <a class="sidebar-link" href="purchase_order.php">
                <span class="icon"><i class="fas fa-shopping-cart"></i></span>
                <span class="link-label">Purchase Order</span>
            </a>
            <a class="sidebar-link" href="purchase_invoice.php">
                <span class="icon"><i class="fas fa-file-invoice"></i></span>
                <span class="link-label">Purchase Invoice</span>
            </a>
            <a class="sidebar-link" href="invoice_list.php">
                <span class="icon"><i class="fas fa-list"></i></span>
                <span class="link-label">Invoice List</span>
            </a>
            <?php endif; ?>

            <div class="nav-section-label">Logistics</div>

            <?php if (is_admin() || is_manager()): ?>
            <a class="sidebar-link" href="delivery_tracking.php">
                <span class="icon"><i class="fas fa-truck"></i></span>
                <span class="link-label">Delivery Tracking</span>
            </a>
            <a class="sidebar-link" href="delivery_history.php">
                <span class="icon"><i class="fas fa-history"></i></span>
                <span class="link-label">Delivery History</span>
            </a>
            <?php endif; ?>

            <div class="nav-section-label">Tools</div>

            <?php if (!is_store()): ?>
            <a class="sidebar-link" href="reports.php">
                <span class="icon"><i class="fas fa-chart-bar"></i></span>
                <span class="link-label">Reports</span>
            </a>
            <?php endif; ?>

            <a class="sidebar-link" href="chat_interface.php">
                <span class="icon"><i class="fas fa-comments"></i></span>
                <span class="link-label">Messages</span>
                <?php if ($unread_messages > 0): ?>
                    <span class="badge-dot"><?php echo $unread_messages; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="sidebar-footer">
            <a class="sidebar-link logout" href="logout.php">
                <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                <span class="link-label">Logout</span>
            </a>
        </div>
    </nav>
    <div class="mobile-sidebar-backdrop" id="mobileSidebarBackdrop"></div>

    <!-- ===================== MAIN CONTENT ===================== -->
    <div class="main-content">

        <!-- Top Header -->
        <div class="top-header">
            <div class="top-header-left">
                <button class="mobile-sidebar-toggle" id="sidebarToggle" aria-label="Open navigation">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <div class="page-title">Employee</div>
                    <div class="page-subtitle">Manage your team members</div>
                </div>
            </div>
            <div class="top-header-right">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="user-avatar-sm"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?></div>
                    <div>
                        <div class="user-name-sm"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                        <div class="user-role-sm"><?php echo ucfirst($_SESSION['user_role']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Body -->
        <div class="page-body">

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Toolbar -->
            <div class="toolbar">
                <div class="toolbar-main">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="employeeSearch" class="search-input" placeholder="Search employees...">
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <button class="btn-outline-sm"><i class="fas fa-file-import" style="font-size:0.75rem;"></i> Import</button>
                        <button class="btn-outline-sm"><i class="fas fa-file-export" style="font-size:0.75rem;"></i> Export</button>
                        <button class="btn-add" onclick="showAddEmployeeModal()">
                            <i class="fas fa-plus" style="font-size:0.75rem;"></i> Add Employee
                        </button>
                    </div>
                </div>
            </div>

            <!-- Employee Grid -->
            <div class="employee-grid" id="employeeCardsGrid">
                <?php
                $avatar_classes = ['av-blue','av-green','av-purple','av-orange','av-pink','av-teal'];
                $idx = 0;
                foreach ($employees as $employee):
                    $status = $employee['status'] ?? 'active';
                    $initials = strtoupper(substr($employee['first_name'],0,1) . substr($employee['last_name'],0,1));
                    $av_class = $avatar_classes[$idx % count($avatar_classes)];
                    $idx++;
                    $hire_formatted = !empty($employee['hire_date']) ? date('j M Y', strtotime($employee['hire_date'])) : 'N/A';
                ?>
                <div class="emp-card" data-employee-id="<?php echo $employee['id']; ?>"
                     data-name="<?php echo strtolower($employee['first_name'].' '.$employee['last_name']); ?>"
                     data-position="<?php echo strtolower($employee['position'] ?? ''); ?>"
                     data-email="<?php echo strtolower($employee['email']); ?>"
                     data-status="<?php echo strtolower($status); ?>"
                     data-department="<?php echo strtolower($employee['department'] ?? ''); ?>">

                    <div class="emp-card-top">
                        <span class="status-badge <?php echo $status === 'active' ? 'active' : ($status === 'inactive' ? 'inactive' : 'invited'); ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                        <div class="dropdown">
                            <button class="emp-menu-btn" type="button"
                                id="empMenu<?php echo $employee['id']; ?>"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="empMenu<?php echo $employee['id']; ?>">
                                <li><a class="dropdown-item" href="admin_employee_detail.php?id=<?php echo $employee['id']; ?>"><i class="fas fa-eye me-2 text-muted" style="font-size:0.8rem;"></i>View Details</a></li>
                                <li>
                                    <a class="dropdown-item <?php echo $status !== 'active' ? 'disabled' : ''; ?>"
                                        href="<?php echo $status === 'active' ? 'employee_profile.php?edit='.$employee['id'] : '#'; ?>">
                                        <i class="fas fa-pen me-2 text-muted" style="font-size:0.8rem;"></i>Edit
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="delete_employee.php?id=<?php echo $employee['id']; ?>"><i class="fas fa-trash me-2" style="font-size:0.8rem;"></i>Delete</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="emp-avatar-wrap">
                        <div class="emp-avatar <?php echo $av_class; ?>"><?php echo $initials; ?></div>
                    </div>

                    <div class="emp-name"><?php echo htmlspecialchars($employee['first_name'].' '.$employee['last_name']); ?></div>
                    <div class="emp-position"><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></div>

                    <?php if (!empty($employee['employee_id'])): ?>
                    <div style="text-align:center;margin-bottom:12px;">
                        <span class="emp-id-tag"><i class="fas fa-hashtag" style="font-size:0.6rem;"></i> <?php echo htmlspecialchars($employee['employee_id']); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="emp-tags">
                        <?php if (!empty($employee['department'])): ?>
                        <span class="emp-tag tag-dept"><?php echo htmlspecialchars($employee['department']); ?></span>
                        <?php endif; ?>
                        <span class="emp-tag tag-type">Full-time</span>
                    </div>

                    <div class="emp-info-block">
                        <div class="emp-info-row">
                            <div class="emp-info-icon"><i class="fas fa-envelope"></i></div>
                            <div>
                                <div class="emp-info-val"><?php echo htmlspecialchars($employee['email']); ?></div>
                            </div>
                        </div>
                        <div class="emp-info-row">
                            <div class="emp-info-icon"><i class="fas fa-phone"></i></div>
                            <div>
                                <div class="emp-info-val"><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="emp-meta">
                        <span class="emp-meta-date">
                            <i class="fas fa-calendar-plus" style="font-size:0.65rem;"></i>
                            Joined <?php echo $hire_formatted; ?>
                        </span>
                        <a href="#" onclick="viewEmployeeDetails(<?php echo $employee['id']; ?>); return false;" class="view-details-link">
                            View details <i class="fas fa-arrow-right" style="font-size:0.65rem;"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($employees)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-users"></i></div>
                    <h4>No employees found</h4>
                    <p>Try adjusting your search or filters, or add a new employee.</p>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /page-body -->
    </div><!-- /main-content -->

    <!-- Employee Details Modal -->
    <div class="modal fade" id="employeeDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Employee Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="employeeDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'mcbot_widget.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar
        (function(){
            const body = document.body;
            const toggle = document.getElementById('sidebarToggle');
            const backdrop = document.getElementById('mobileSidebarBackdrop');
            if (toggle) toggle.addEventListener('click', () => body.classList.toggle('sidebar-open'));
            if (backdrop) backdrop.addEventListener('click', () => body.classList.remove('sidebar-open'));
            window.addEventListener('resize', () => { if(window.innerWidth >= 992) body.classList.remove('sidebar-open'); });
        })();

        // Live search + filter
        function filterCards() {
            const term = document.getElementById('employeeSearch').value.toLowerCase();
            document.querySelectorAll('.emp-card').forEach(card => {
                const name = card.dataset.name || '';
                const pos = card.dataset.position || '';
                const email = card.dataset.email || '';
                const match = !term || name.includes(term) || pos.includes(term) || email.includes(term);
                card.style.display = match ? '' : 'none';
            });
        }
        document.getElementById('employeeSearch')?.addEventListener('input', filterCards);

        function showAddEmployeeModal() {
            alert('Add Employee modal – implement your form here.');
        }

        let empModal;
        document.addEventListener('DOMContentLoaded', () => {
            empModal = new bootstrap.Modal(document.getElementById('employeeDetailsModal'));
        });

        function viewEmployeeDetails(id) {
            document.getElementById('employeeDetailsContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>`;
            empModal.show();

            fetch('get_employee_details.php?id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('employeeDetailsContent').innerHTML =
                            `<div class="alert alert-danger">Failed to load: ${data.message}</div>`;
                        return;
                    }
                    const e = data.employee, p = data.profile || {}, edu = data.education || [], fam = data.family || [];
                    const init = ((e.first_name||'')[0]||'') + ((e.last_name||'')[0]||'');
                    let html = `
                    <div style="display:flex;align-items:center;gap:16px;padding-bottom:18px;border-bottom:1px solid #e8eaf0;margin-bottom:20px;">
                        <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#c7d2fe,#818cf8);display:flex;align-items:center;justify-content:center;color:#3730a3;font-weight:700;font-size:1.8rem;flex-shrink:0;">${init.toUpperCase()}</div>
                        <div>
                            <div style="font-size:1.2rem;font-weight:700;color:#111827;">${e.first_name} ${e.last_name}</div>
                            <div style="font-size:0.85rem;color:#6b7280;margin-top:2px;">${e.position||'N/A'} · ${e.department||'N/A'}</div>
                            <span style="font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:20px;background:${e.status==='active'?'#dcfce7':'#fce8e8'};color:${e.status==='active'?'#166534':'#991b1b'};display:inline-block;margin-top:6px;">${(e.status||'N/A').charAt(0).toUpperCase()+(e.status||'').slice(1)}</span>
                        </div>
                    </div>`;

                    const section = (icon, title, rows) => `
                    <div style="margin-bottom:20px;">
                        <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                            <i class="${icon}" style="font-size:0.75rem;"></i>${title}
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 16px;">${rows}</div>
                    </div>`;
                    const row = (label, val) => `
                        <div>
                            <div style="font-size:0.7rem;color:#9ca3af;margin-bottom:1px;">${label}</div>
                            <div style="font-size:0.85rem;color:#111827;font-weight:500;">${val||'N/A'}</div>
                        </div>`;

                    html += section('fas fa-user', 'Personal Information',
                        row('Employee ID', e.employee_id) +
                        row('Gender', p.gender) +
                        row('Birth Date', p.birth_date ? new Date(p.birth_date).toLocaleDateString() : null) +
                        row('Place of Birth', p.place_of_birth) +
                        row('Marital Status', p.marital_status) +
                        row('Religion', p.religion)
                    );
                    html += section('fas fa-briefcase', 'Employment',
                        row('Department', e.department) +
                        row('Position', e.position) +
                        row('Role', e.role) +
                        row('Date Hired', e.hire_date ? new Date(e.hire_date).toLocaleDateString() : null) +
                        row('Email', e.email) +
                        row('Phone', e.phone || p.phone) +
                        row('Basic Salary', p.basic_salary ? '₱' + parseFloat(p.basic_salary).toLocaleString() : null)
                    );
                    html += section('fas fa-phone-alt', 'Emergency Contact',
                        row('Name', p.ec_name) +
                        row('Relationship', p.ec_relationship) +
                        row('Phone', p.ec_phone)
                    );

                    if (edu.length) {
                        html += `<div style="margin-bottom:20px;"><div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:10px;display:flex;align-items:center;gap:6px;"><i class="fas fa-graduation-cap" style="font-size:0.75rem;"></i>Education</div>${edu.map(e=>`<div style="background:#f9fafb;border-radius:10px;padding:12px 14px;margin-bottom:8px;"><div style="font-weight:600;font-size:0.88rem;">${e.degree||'N/A'}</div><div style="font-size:0.82rem;color:#6b7280;">${e.school||'N/A'} · ${e.field||''}</div><div style="font-size:0.75rem;color:#9ca3af;margin-top:2px;">${e.year_start||''} – ${e.year_end||''} · GPA: ${e.gpa||'N/A'}</div></div>`).join('')}</div>`;
                    }
                    if (fam.length) {
                        html += `<div style="margin-bottom:8px;"><div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:10px;display:flex;align-items:center;gap:6px;"><i class="fas fa-users" style="font-size:0.75rem;"></i>Family</div><table class="table table-sm" style="font-size:0.85rem;"><thead><tr><th>Relationship</th><th>Name</th></tr></thead><tbody>${fam.map(f=>`<tr><td>${f.type||'N/A'}</td><td>${f.name||'N/A'}</td></tr>`).join('')}</tbody></table></div>`;
                    }

                    document.getElementById('employeeDetailsContent').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('employeeDetailsContent').innerHTML =
                        `<div class="alert alert-danger">Error loading employee details. Please try again.</div>`;
                });
        }
    </script>
</body>
</html>
