<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('index.php');
}

// Role-based access control
if (is_employee() && !is_admin() && !is_manager()) {
    $allowed_report_types = ['attendance', 'inventory'];
} elseif (is_store()) {
    $allowed_report_types = ['purchase', 'inventory'];
} else {
    $allowed_report_types = ['purchase', 'attendance', 'purchase_invoice', 'inventory'];
}

function generate_purchase_report($date_from, $date_to) {
    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT po.*, s.name as supplier_name, u.full_name as created_by_name 
              FROM purchase_orders po 
              LEFT JOIN suppliers s ON po.supplier_id = s.id 
              LEFT JOIN users u ON po.created_by = u.id 
              WHERE 1=1";
    $params = [];
    if (!is_admin() && !is_manager()) {
        $query .= " AND po.order_date BETWEEN :date_from AND :date_to";
        $params[':date_from'] = $date_from;
        $params[':date_to'] = $date_to;
    }
    $query .= " ORDER BY po.order_date DESC, po.created_at DESC";
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) { $stmt->bindParam($key, $value); }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generate_attendance_report($date_from, $date_to, $employee_id = null) {
    $database = new Database();
    $db = $database->getConnection();
    $camera_employee_filter = '';
    $regular_employee_filter = '';
    if ($employee_id) {
        $camera_employee_filter = " AND ca.employee_id = :camera_employee_id";
        $regular_employee_filter = " AND a.employee_id = :regular_employee_id";
    }
    $query = "SELECT *
              FROM (
                  SELECT ca.id, ca.employee_id, ca.capture_date as date,
                      CASE WHEN LOWER(ca.notes) LIKE '%clock_out%' OR LOWER(ca.notes) LIKE '%clock out%' THEN NULL ELSE ca.capture_time END as check_in,
                      CASE WHEN LOWER(ca.notes) LIKE '%clock_out%' OR LOWER(ca.notes) LIKE '%clock out%' THEN ca.capture_time ELSE NULL END as check_out,
                      NULL as break_duration, NULL as total_hours,
                      CASE WHEN ca.verification_status = 'verified' THEN 'photo_verified'
                           WHEN ca.verification_status = 'rejected' THEN 'photo_rejected'
                           ELSE 'photo_sent' END as status,
                      ca.notes, ca.created_at, ca.photo_path, ca.verification_status,
                      'camera' as attendance_source,
                      e.first_name, e.last_name, e.employee_id as employee_code, e.department,
                      ca.capture_time as sort_time
                  FROM camera_attendance ca
                  LEFT JOIN employees e ON ca.employee_id = e.id
                  WHERE ca.capture_date BETWEEN :date_from_camera AND :date_to_camera
                  {$camera_employee_filter}
                  UNION ALL
                  SELECT a.id, a.employee_id, a.date, a.check_in, a.check_out,
                      a.break_duration, a.total_hours, a.status, a.notes, a.created_at,
                      NULL as photo_path, NULL as verification_status,
                      'regular' as attendance_source,
                      e.first_name, e.last_name, e.employee_id as employee_code, e.department,
                      COALESCE(a.check_out, a.check_in) as sort_time
                  FROM attendance a
                  LEFT JOIN employees e ON a.employee_id = e.id
                  WHERE a.date BETWEEN :date_from_regular AND :date_to_regular
                  {$regular_employee_filter}
                  AND NOT EXISTS (
                      SELECT 1 FROM camera_attendance ca2
                      WHERE ca2.employee_id = a.employee_id AND ca2.capture_date = a.date
                  )
              ) attendance_report
              ORDER BY date DESC, sort_time DESC, last_name ASC";
    $params = [':date_from_camera'=>$date_from,':date_to_camera'=>$date_to,':date_from_regular'=>$date_from,':date_to_regular'=>$date_to];
    if ($employee_id) { $params[':camera_employee_id'] = $employee_id; $params[':regular_employee_id'] = $employee_id; }
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) { $stmt->bindParam($key, $value); }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generate_purchase_invoice_report($date_from, $date_to) {
    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT pi.*, po.po_number, po.order_date, s.name as supplier_name, u.full_name as created_by_name
              FROM purchase_invoices pi
              LEFT JOIN purchase_orders po ON pi.po_id = po.id
              LEFT JOIN suppliers s ON po.supplier_id = s.id
              LEFT JOIN users u ON po.created_by = u.id
              WHERE pi.invoice_date BETWEEN :date_from AND :date_to
              ORDER BY pi.invoice_date DESC, pi.created_at DESC";
    $params = [':date_from'=>$date_from,':date_to'=>$date_to];
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) { $stmt->bindParam($key, $value); }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generate_inventory_report_data($date_from = null, $date_to = null) {
    return generate_inventory_report($date_from, $date_to);
}

function save_report($report_name, $report_type, $parameters, $generated_by) {
    $database = new Database();
    $db = $database->getConnection();
    $query = "INSERT INTO reports (report_name, report_type, generated_by, parameters) VALUES (:report_name, :report_type, :generated_by, :parameters)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':report_name', $report_name);
    $stmt->bindParam(':report_type', $report_type);
    $stmt->bindParam(':generated_by', $generated_by);
    $stmt->bindParam(':parameters', $parameters);
    return $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];
    $employee_id = $_POST['employee_id'] ?? null;
    if (!in_array($report_type, $allowed_report_types)) {
        $error_message = "You don't have permission to generate this type of report.";
    } else {
        $report_data = [];
        $report_name = '';
        switch ($report_type) {
            case 'purchase':
                $report_data = generate_purchase_report($date_from, $date_to);
                $report_name = (is_admin() || is_manager()) ? 'Purchase Order Report - All Purchase Orders' : 'Purchase Order Report - ' . format_date($date_from) . ' to ' . format_date($date_to);
                break;
            case 'attendance':
                $report_data = generate_attendance_report($date_from, $date_to, $employee_id);
                $report_name = 'Attendance Report - ' . format_date($date_from) . ' to ' . format_date($date_to);
                break;
            case 'purchase_invoice':
                $report_data = generate_purchase_invoice_report($date_from, $date_to);
                $report_name = 'Purchase Invoice Report - ' . format_date($date_from) . ' to ' . format_date($date_to);
                break;
            case 'inventory':
                $report_data = generate_inventory_report_data($date_from, $date_to);
                $report_name = 'Inventory Report - ' . format_date($date_from) . ' to ' . format_date($date_to);
                break;
        }
        $parameters = json_encode(['date_from'=>$date_from,'date_to'=>$date_to,'employee_id'=>$employee_id]);
        $saved_report_type = $report_type === 'purchase_invoice' ? 'financial' : $report_type;
        save_report($report_name, $saved_report_type, $parameters, $_SESSION['user_id']);
        $generated_report = ['type'=>$report_type,'name'=>$report_name,'data'=>$report_data];
    }
}

function get_saved_reports($limit = 10) {
    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT r.*, u.full_name as generated_by_name FROM reports r LEFT JOIN users u ON r.generated_by = u.id ORDER BY r.generated_at DESC LIMIT :limit";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$saved_reports = get_saved_reports();
$all_employees = get_employees(100, 0);
$unread_notifications = (is_admin() || is_manager()) ? get_unread_attendance_notifications(10) : [];
$unread_messages = get_unread_message_count($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy:       #0a1045;
            --navy-2:     #0f1860;
            --navy-3:     #1a2580;
            --navy-light: #e8ebf8;
            --navy-pale:  #f2f4fc;
            --white:      #ffffff;
            --bg:         #f0f2f9;
            --surface:    #f7f8fd;
            --surface-2:  #eceef7;
            --border:     #e4e7f2;
            --border-2:   #cdd1e8;
            --text-1:     #0d1030;
            --text-2:     #3a4066;
            --text-3:     #7b809e;
            --text-4:     #b0b4cc;
            --green:      #0d7a48;
            --green-bg:   #e6f4ee;
            --amber:      #875200;
            --amber-bg:   #fff4de;
            --blue:       #1645b6;
            --blue-bg:    #e8eeff;
            --red:        #d4241a;
            --red-tint:   #fdecea;

            --sidebar-w:    220px;
            --sb-bg:        #0d1b3e;
            --sb-active-bg: rgba(255,255,255,0.11);
            --sb-hover-bg:  rgba(255,255,255,0.06);
            --sb-label:     rgba(255,255,255,0.32);
            --sb-text:      rgba(255,255,255,0.70);
            --sb-text-act:  #ffffff;
            --sb-border:    rgba(255,255,255,0.07);
            --topbar-h: 64px;
            --radius:   14px;
            --radius-sm: 9px;
            --font:     'Sora', sans-serif;
            --mono:     'DM Mono', monospace;
            --shadow-xs: 0 1px 2px rgba(10,16,69,0.05);
            --shadow-sm: 0 2px 8px rgba(10,16,69,0.07),0 1px 3px rgba(10,16,69,0.04);
            --shadow:    0 6px 24px rgba(10,16,69,0.09),0 2px 8px rgba(10,16,69,0.04);
        }

        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: var(--font); background: var(--bg); color: var(--text-1); font-size: 13px; line-height: 1.55; -webkit-font-smoothing: antialiased; margin: 0; }

        /* ── SIDEBAR ── */
        .sidebar { position: fixed; top: 0; left: 0; width: var(--sidebar-w); height: 100vh; background: var(--sb-bg); display: flex; flex-direction: column; z-index: 9999; overflow-y: auto; overflow-x: hidden; scrollbar-width: none; }
        .sidebar::-webkit-scrollbar { display: none; }
        .sb-logo { display: flex; align-items: center; gap: 11px; padding: 18px 16px 16px; border-bottom: 1px solid var(--sb-border); flex-shrink: 0; }
        .sb-logo-ring { width: 40px; height: 40px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.28); overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #1a2a5e; }
        .sb-logo-ring img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .sb-brand-name { font-size: 13px; font-weight: 800; color: #fff; letter-spacing: .06em; text-transform: uppercase; line-height: 1.15; }
        .sb-brand-sub { font-size: 8.5px; color: rgba(255,255,255,0.38); letter-spacing: .10em; text-transform: uppercase; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
        .sb-nav { flex: 1; padding: 6px 10px 4px; }
        .sb-section { font-size: 9.5px; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; color: var(--sb-label); padding: 14px 8px 5px; }
        .sb-item { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 9px; color: var(--sb-text); text-decoration: none; font-size: 13px; font-weight: 500; transition: background .13s, color .13s; margin-bottom: 2px; line-height: 1.2; cursor: pointer; position: relative; }
        .sb-item:hover { background: var(--sb-hover-bg); color: var(--sb-text-act); text-decoration: none; }
        .sb-item.active { background: var(--sb-active-bg); color: var(--sb-text-act); font-weight: 600; }
        .sb-item i { font-size: 18px; flex-shrink: 0; line-height: 1; width: 22px; text-align: center; }
        .sb-item .sb-badge { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: #e5534b; color: #fff; font-size: 9px; font-weight: 700; min-width: 17px; height: 17px; border-radius: 50%; display: flex; align-items: center; justify-content: center; padding: 0 3px; }
        .sb-item.sb-logout { color: rgba(239,68,68,0.75); }
        .sb-item.sb-logout i { color: rgba(239,68,68,0.85); }
        .sb-item.sb-logout:hover { background: rgba(239,68,68,0.10); color: #ef4444; }
        .sb-item.sb-logout:hover i { color: #ef4444; }
        .sb-footer { flex-shrink: 0; padding: 4px 10px 18px; border-top: 1px solid var(--sb-border); }

        /* Mobile sidebar */
        .mobile-sb-toggle { display: none; align-items: center; justify-content: center; width: 36px; height: 36px; border: none; border-radius: var(--radius-sm); background: var(--surface); color: var(--text-2); cursor: pointer; border: 1px solid var(--border); flex: 0 0 auto; }
        .mobile-sb-backdrop { display: none; }
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); transition: transform .3s ease; box-shadow: 0 12px 28px rgba(0,0,0,.25); }
            body.sb-open .sidebar { transform: translateX(0); }
            .main-wrap { margin-left: 0 !important; }
            .mobile-sb-toggle { display: inline-flex; }
            .mobile-sb-backdrop { display: block; position: fixed; inset: 0; background: rgba(9,15,85,.45); opacity: 0; pointer-events: none; transition: opacity .3s ease; z-index: 9998; }
            body.sb-open .mobile-sb-backdrop { opacity: 1; pointer-events: auto; }
        }

        /* ── LAYOUT ── */
        .main-wrap { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; }

        /* ── TOPBAR ── */
        .topbar { height: var(--topbar-h); background: var(--white); border-bottom: 1px solid var(--border); padding: 0 32px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow-xs); }
        .topbar-left { display: flex; align-items: center; gap: 14px; }
        .topbar-breadcrumb { display: flex; flex-direction: column; gap: 1px; }
        .topbar-title { font-size: 16px; font-weight: 700; color: var(--text-1); letter-spacing: -.025em; line-height: 1.2; }
        .topbar-sub { font-size: 11px; color: var(--text-4); font-weight: 400; letter-spacing: .02em; }
        .topbar-divider { width: 1px; height: 28px; background: var(--border); margin: 0 4px; }
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .tb-icon-btn { width: 36px; height: 36px; border-radius: var(--radius-sm); background: var(--surface); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--text-3); cursor: pointer; font-size: 13px; transition: all .15s; position: relative; }
        .tb-icon-btn:hover { background: var(--surface-2); color: var(--text-1); border-color: var(--border-2); }
        .tb-notif-dot { position: absolute; top: 8px; right: 8px; width: 6px; height: 6px; border-radius: 50%; background: var(--red); border: 1.5px solid var(--white); }
        .user-chip { display: flex; align-items: center; gap: 9px; padding: 4px 12px 4px 4px; border: 1px solid var(--border); border-radius: 40px; cursor: pointer; background: var(--white); transition: all .15s; }
        .user-chip:hover { border-color: var(--border-2); box-shadow: var(--shadow-xs); }
        .u-avatar { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, var(--navy-3), var(--navy)); color: var(--white); font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .u-name { font-size: 12px; font-weight: 700; color: var(--text-1); line-height: 1.2; }
        .u-role { font-size: 10px; color: var(--text-4); font-weight: 400; }

        /* ── PAGE BODY ── */
        .page-body { padding: 28px 32px; flex: 1; }
        .page-eyebrow { font-size: 10.5px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--blue); margin-bottom: 4px; }
        .page-heading { font-size: 22px; font-weight: 800; color: var(--text-1); letter-spacing: -.03em; line-height: 1; }
        .page-sub { font-size: 12px; color: var(--text-3); margin-top: 5px; }

        /* ── CONTENT CARDS ── */
        .content-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow-sm); margin-bottom: 20px; }
        .content-card h5 { font-size: 14px; font-weight: 700; color: var(--text-1); margin-bottom: 18px; }

        /* ── FORM CONTROLS ── */
        .form-label { font-size: 12px; font-weight: 600; color: var(--text-2); margin-bottom: 5px; }
        .form-control, .form-select { font-size: 13px; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 9px 12px; color: var(--text-1); background: var(--surface); font-family: var(--font); transition: border-color .15s, box-shadow .15s; }
        .form-control:focus, .form-select:focus { border-color: var(--navy-3); box-shadow: 0 0 0 3px rgba(26,37,128,.08); outline: none; }

        /* ── BUTTONS ── */
        .btn-primary { background: var(--navy); border: none; border-radius: var(--radius-sm); padding: 10px 20px; font-weight: 700; font-family: var(--font); font-size: 13px; transition: all .15s; }
        .btn-primary:hover { background: var(--navy-2); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(10,16,69,.25); }
        .btn-outline-secondary { border-radius: var(--radius-sm); font-family: var(--font); font-size: 13px; font-weight: 600; }

        /* ── STAT BOXES ── */
        .stat-box { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 18px; text-align: center; transition: all .22s; }
        .stat-box:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
        .stat-number { font-size: 22px; font-weight: 800; color: var(--navy); letter-spacing: -.02em; font-family: var(--mono); }
        .stat-label { font-size: 11px; color: var(--text-3); text-transform: uppercase; letter-spacing: .08em; font-weight: 700; margin-top: 4px; }

        /* ── TABLE ── */
        .table th { font-size: 10.5px; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: .1em; border-top: none; border-bottom: 2px solid var(--border); padding: 11px 12px; }
        .table td { font-size: 12.5px; color: var(--text-1); padding: 13px 12px; vertical-align: middle; }
        .table-hover tbody tr:hover td { background: var(--navy-pale); }

        /* ── BADGES ── */
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }

        /* ── REPORT HEADER ── */
        .report-header { background: linear-gradient(135deg, var(--navy) 0%, var(--navy-3) 100%); color: white; padding: 24px; border-radius: var(--radius-sm); margin-bottom: 24px; text-align: center; }
        .report-header h4 { font-size: 17px; font-weight: 800; margin: 0 0 4px; }
        .report-header p { font-size: 12px; opacity: .7; margin: 0; }

        /* ── ATTENDANCE PHOTO ── */
        .attendance-photo-thumb { width: 56px; height: 42px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border); box-shadow: 0 2px 6px rgba(0,0,0,.1); }

        /* Alerts */
        .alert { border-radius: var(--radius-sm); font-size: 12.5px; margin-bottom: 18px; border: none; font-weight: 500; }
        .alert-danger { background: var(--red-tint); color: var(--red); }

        @media (max-width: 991.98px) {
            .page-body { padding: 20px 16px; }
            .topbar { padding: 0 16px; }
        }
        @media (max-width: 575.98px) {
            .page-body { padding: 14px 12px; }
            .content-card { padding: 16px; }
        }
    </style>
    <link rel="stylesheet" href="sidebar-standard.css">
</head>
<body>

<!-- ═══════ SIDEBAR ═══════ -->
<nav id="appSidebar" class="sidebar">
    <div class="sb-logo">
        <div class="sb-logo-ring">
            <img src="logo.png" alt="McPIL" onerror="this.style.display='none'">
        </div>
        <div>
            <div class="sb-brand-name">McPIL</div>
            <div class="sb-brand-sub">Pharmaceutical Lab...</div>
        </div>
    </div>

    <div class="sb-nav">
        <div class="sb-section">Main</div>
        <a class="sb-item" href="<?php echo is_employee() ? 'employee_home.php' : 'dashboard.php'; ?>"><i class="ti ti-layout-dashboard"></i><?php echo is_employee() ? 'Home' : 'Dashboard'; ?></a>

        <?php if (is_admin()): ?>
        <a class="sb-item" href="purchase_order.php"><i class="ti ti-shopping-cart"></i>Purchase Order</a>
        <a class="sb-item" href="purchase_invoice.php"><i class="ti ti-file-invoice"></i>Purchase Invoice</a>
        <a class="sb-item" href="employee_profile.php"><i class="ti ti-users"></i>Employee Profile</a>
        <a class="sb-item" href="attendance.php"><i class="ti ti-calendar-check"></i>Attendance</a>
        <?php endif; ?>

        <?php if (is_manager()): ?>
        <a class="sb-item" href="purchase_order.php"><i class="ti ti-shopping-cart"></i>Purchase Order</a>
        <a class="sb-item" href="purchase_invoice.php"><i class="ti ti-file-invoice"></i>Purchase Invoice</a>
        <a class="sb-item" href="attendance.php"><i class="ti ti-calendar-check"></i>Attendance</a>
        <?php endif; ?>

        <?php if (is_store()): ?>
        <a class="sb-item" href="inventory.php"><i class="ti ti-box"></i>Inventory Management</a>
        <a class="sb-item" href="purchase_order.php"><i class="ti ti-shopping-cart"></i>Purchase Order</a>
        <a class="sb-item" href="purchase_invoice.php"><i class="ti ti-file-invoice"></i>Purchase Invoice</a>
        <a class="sb-item" href="invoice_list.php"><i class="ti ti-list"></i>Invoice List</a>
        <?php endif; ?>

        <?php if (is_employee()): ?>
        <a class="sb-item" href="inventory.php"><i class="ti ti-box"></i>Inventory</a>
        <a class="sb-item" href="attendance_camera.php"><i class="ti ti-camera"></i>Attendance</a>
        <a class="sb-item" href="attendance_history.php"><i class="ti ti-history"></i>Attendance History</a>
        <?php endif; ?>

        <?php if (!is_store()): ?>
        <div class="sb-section">Logistics</div>
        <a class="sb-item" href="delivery_tracking.php"><i class="ti ti-truck-delivery"></i>Delivery Tracking</a>
        <?php if (!is_employee()): ?>
        <a class="sb-item" href="delivery_history.php"><i class="ti ti-history"></i>Delivery History</a>
        <?php endif; ?>
        <?php endif; ?>

        <div class="sb-section">Tools</div>
        <a class="sb-item active" href="reports.php"><i class="ti ti-chart-bar"></i>Reports</a>
        <a class="sb-item" href="chat_interface.php">
            <i class="ti ti-message-2"></i>Messages
            <?php if ($unread_messages > 0): ?>
                <span class="sb-badge"><?php echo $unread_messages; ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="sb-footer">
        <div class="sb-section" style="padding-top:8px">Account</div>
        <a class="sb-item" href="settings.php"><i class="ti ti-settings"></i>Settings</a>
        <a class="sb-item sb-logout" href="logout.php"><i class="ti ti-logout"></i>Logout</a>
    </div>
</nav>

<div class="mobile-sb-backdrop" id="mobileSbBackdrop"></div>

<!-- ═══════ MAIN ═══════ -->
<div class="main-wrap">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="mobile-sb-toggle" id="sidebarToggle" aria-label="Open navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-divider"></div>
            <div class="topbar-breadcrumb">
                <div class="topbar-title">Reports & Analytics</div>
                <div class="topbar-sub">Business Intelligence · Generate & export reports</div>
            </div>
        </div>
        <div class="topbar-right">
            <div class="tb-icon-btn">
                <i class="fas fa-bell"></i>
                <?php if (!empty($unread_notifications)): ?>
                <span class="tb-notif-dot"></span>
                <?php endif; ?>
            </div>
            <div class="user-chip">
                <div class="u-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?></div>
                <div>
                    <div class="u-name"><?php echo $_SESSION['full_name']; ?></div>
                    <div class="u-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
                </div>
            </div>
        </div>
    </header>

    <div class="page-body">

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Page Header -->
        <div style="margin-bottom:24px">
            <div class="page-eyebrow">Analytics</div>
            <div class="page-heading">Reports
                <span style="font-size:16px;font-weight:400;color:var(--text-4);margin-left:8px"><?php echo count($saved_reports); ?> saved</span>
            </div>
            <div class="page-sub">Generate comprehensive reports for purchase orders, attendance, invoices, and inventory</div>
        </div>

        <!-- Report Generation Form -->
        <div class="content-card">
            <h5><i class="ti ti-file-analytics me-2" style="color:var(--blue)"></i>Generate New Report</h5>
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="report_type" class="form-label">Report Type *</label>
                        <select class="form-select" id="report_type" name="report_type" required onchange="toggleFilters()">
                            <option value="">Select Report Type</option>
                            <?php if (in_array('purchase', $allowed_report_types)): ?>
                            <option value="purchase">Purchase Order Report</option>
                            <?php endif; ?>
                            <?php if (in_array('attendance', $allowed_report_types)): ?>
                            <option value="attendance">Attendance Report</option>
                            <?php endif; ?>
                            <?php if (in_array('purchase_invoice', $allowed_report_types)): ?>
                            <option value="purchase_invoice">Purchase Invoice Report</option>
                            <?php endif; ?>
                            <?php if (in_array('inventory', $allowed_report_types)): ?>
                            <option value="inventory">Inventory Report</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="date_from" class="form-label">From Date *</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo date('Y-m-01'); ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="date_to" class="form-label">To Date *</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" id="employee_filter_label" style="display:none">Filter by Employee</label>
                        <select class="form-select" id="employee_filter" name="employee_id" style="display:none; margin-top:0">
                            <option value="">All Employees</option>
                            <?php foreach ($all_employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>"><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="generate_report" class="btn btn-primary">
                    <i class="ti ti-chart-bar me-1"></i> Generate Report
                </button>
            </form>
        </div>

        <!-- Generated Report Preview -->
        <?php if (isset($generated_report)): ?>
        <div class="content-card">
            <div class="report-header">
                <h4><?php echo $generated_report['name']; ?></h4>
                <p>Generated on <?php echo date('M d, Y H:i'); ?></p>
            </div>

            <?php if ($generated_report['type'] == 'purchase'): ?>
                <h5 style="font-size:13px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px">Purchase Order Summary</h5>
                <div class="row mb-4">
                    <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo count($generated_report['data']); ?></div><div class="stat-label">Total Orders</div></div></div>
                    <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo format_currency(array_sum(array_column($generated_report['data'], 'total_amount'))); ?></div><div class="stat-label">Total Value</div></div></div>
                    <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo count(array_filter($generated_report['data'], fn($d) => strtolower($d['status']) == 'approved')); ?></div><div class="stat-label">Approved</div></div></div>
                    <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo count(array_filter($generated_report['data'], fn($d) => strtolower($d['status']) == 'pending')); ?></div><div class="stat-label">Pending</div></div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>PO Number</th><th>Supplier</th><th>Order Date</th><th>Total Amount</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($generated_report['data'] as $order): ?>
                            <tr>
                                <td style="font-family:var(--mono);font-weight:600;color:var(--navy)"><?php echo $order['po_number']; ?></td>
                                <td><?php echo $order['supplier_name']; ?></td>
                                <td style="font-family:var(--mono);font-size:12px"><?php echo format_date($order['order_date']); ?></td>
                                <td style="font-family:var(--mono);font-weight:600"><?php echo format_currency($order['total_amount']); ?></td>
                                <td><span class="badge bg-<?php echo strtolower($order['status']) == 'approved' ? 'success' : (strtolower($order['status']) == 'archived' ? 'secondary' : 'warning'); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($generated_report['type'] == 'attendance'): ?>
                <?php
                    $attendance_data = $generated_report['data'];
                    $photo_sent_count = count(array_filter($attendance_data, fn($d) => ($d['attendance_source'] ?? '') == 'camera'));
                    $photo_verified_count = count(array_filter($attendance_data, fn($d) => ($d['status'] ?? '') == 'photo_verified'));
                    $photo_rejected_count = count(array_filter($attendance_data, fn($d) => ($d['status'] ?? '') == 'photo_rejected'));
                ?>
                <h5 style="font-size:13px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px">Attendance Summary</h5>
                <div class="row mb-4">
                    <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo count($attendance_data); ?></div><div class="stat-label">Total Records</div></div></div>
                    <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo $photo_sent_count; ?></div><div class="stat-label">Photo Sent</div></div></div>
                    <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo $photo_verified_count; ?></div><div class="stat-label">Verified</div></div></div>
                    <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo $photo_rejected_count; ?></div><div class="stat-label">Rejected</div></div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Employee</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Total Hours</th><th>Photo</th></tr></thead>
                        <tbody>
                            <?php foreach ($generated_report['data'] as $record): ?>
                            <?php
                                $status_meta = [
                                    'present' => ['class'=>'success','text'=>'Present'],
                                    'late' => ['class'=>'warning','text'=>'Late'],
                                    'absent' => ['class'=>'danger','text'=>'Absent'],
                                    'half_day' => ['class'=>'secondary','text'=>'Half Day'],
                                    'photo_sent' => ['class'=>'primary','text'=>'Photo Sent'],
                                    'photo_verified' => ['class'=>'success','text'=>'Photo Verified'],
                                    'photo_rejected' => ['class'=>'danger','text'=>'Photo Rejected']
                                ][$record['status']] ?? ['class'=>'secondary','text'=>ucfirst(str_replace('_',' ',$record['status']))];
                            ?>
                            <tr>
                                <td style="font-weight:600"><?php echo $record['first_name'] . ' ' . $record['last_name']; ?></td>
                                <td style="font-family:var(--mono);font-size:12px"><?php echo format_date($record['date']); ?></td>
                                <td style="font-family:var(--mono);font-size:12px"><?php echo $record['check_in'] ?? '—'; ?></td>
                                <td style="font-family:var(--mono);font-size:12px"><?php echo $record['check_out'] ?? '—'; ?></td>
                                <td style="font-family:var(--mono);font-size:12px"><?php echo $record['total_hours'] ?? '—'; ?></td>
                                <td>
                                    <?php if (!empty($record['photo_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($record['photo_path']); ?>" target="_blank">
                                            <img src="<?php echo htmlspecialchars($record['photo_path']); ?>" alt="<?php echo $status_meta['text']; ?>" class="attendance-photo-thumb" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block'">
                                            <span class="badge bg-<?php echo $status_meta['class']; ?>" style="display:none"><?php echo $status_meta['text']; ?></span>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-<?php echo $status_meta['class']; ?>"><?php echo $status_meta['text']; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($generated_report['type'] == 'purchase_invoice'): ?>
                <?php
                    $invoice_data = $generated_report['data'];
                    $approved_invoice_count = count(array_filter($invoice_data, fn($d) => in_array(strtolower($d['status']), ['approved','paid'])));
                    $pending_invoice_count = count(array_filter($invoice_data, fn($d) => in_array(strtolower($d['status']), ['pending','unpaid','partially_paid'])));
                ?>
                <h5 style="font-size:13px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px">Purchase Invoice Summary</h5>
                <div class="row mb-4">
                    <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo count($invoice_data); ?></div><div class="stat-label">Total Invoices</div></div></div>
                    <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo format_currency(array_sum(array_column($invoice_data, 'total_amount'))); ?></div><div class="stat-label">Total Value</div></div></div>
                    <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo $approved_invoice_count; ?></div><div class="stat-label">Approved</div></div></div>
                    <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo $pending_invoice_count; ?></div><div class="stat-label">Pending</div></div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Invoice Number</th><th>PO Number</th><th>Supplier</th><th>Invoice Date</th><th>Total Amount</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($invoice_data as $invoice): ?>
                            <?php $badge_class = in_array(strtolower($invoice['status']), ['approved','paid']) ? 'success' : (strtolower($invoice['status']) === 'rejected' ? 'danger' : 'warning'); ?>
                            <tr>
                                <td style="font-family:var(--mono);font-weight:700;color:var(--navy)"><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                <td style="font-family:var(--mono);font-size:12px;color:var(--text-3)"><?php echo htmlspecialchars($invoice['po_number'] ?? '—'); ?></td>
                                <td style="font-weight:600"><?php echo htmlspecialchars($invoice['supplier_name'] ?? '—'); ?></td>
                                <td style="font-family:var(--mono);font-size:12px"><?php echo format_date($invoice['invoice_date']); ?></td>
                                <td style="font-family:var(--mono);font-weight:600"><?php echo format_currency($invoice['total_amount']); ?></td>
                                <td><span class="badge bg-<?php echo $badge_class; ?>"><?php echo htmlspecialchars(ucwords(str_replace('_',' ',$invoice['status']))); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($generated_report['type'] == 'inventory'): ?>
                <?php $inventory_summary = get_inventory_summary(); ?>
                <h5 style="font-size:13px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px">Inventory Summary</h5>
                <?php if ($inventory_summary['total_items'] == 0): ?>
                    <div class="alert" style="background:var(--amber-bg);color:var(--amber)">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        The inventory tables have not been set up yet.
                        <a href="create_inventory_tables.php" class="btn btn-primary btn-sm ms-2">Setup Inventory Tables</a>
                    </div>
                <?php else: ?>
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo $inventory_summary['total_items']; ?></div><div class="stat-label">Total Items</div></div></div>
                        <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo $inventory_summary['total_quantity']; ?></div><div class="stat-label">Total Quantity</div></div></div>
                        <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo format_currency($inventory_summary['total_value']); ?></div><div class="stat-label">Total Value</div></div></div>
                        <div class="col-md-3 mb-3"><div class="stat-box"><div class="stat-number"><?php echo $inventory_summary['items_to_order']; ?></div><div class="stat-label">Items to Order</div></div></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2">NAME OF PRODUCTS</th><th rowspan="2">BARCODE</th><th rowspan="2">SIZE</th><th rowspan="2">UNIT</th>
                                    <th rowspan="2">UNIT PRICE</th><th rowspan="2">CONTENT</th>
                                    <th colspan="4" style="text-align:center">BEGINNING</th><th colspan="4" style="text-align:center">ENDING</th>
                                    <th rowspan="2">ON HAND</th><th rowspan="2">TOTAL AMOUNT</th><th rowspan="2">SUGGESTED ORDER</th>
                                </tr>
                                <tr><th>BODEGA</th><th>SHELVES</th><th>DELIVERY</th><th>TOTAL</th><th>BODEGA</th><th>SHELVES</th><th>DELIVERY</th><th>TOTAL</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($generated_report['data'] as $item): ?>
                                <tr>
                                    <td style="font-weight:600"><?php echo $item['item_name']; ?></td>
                                    <td style="font-family:var(--mono);font-size:11px"><?php echo $item['barcode'] ?? '—'; ?></td>
                                    <td><?php echo $item['size'] ?? '—'; ?></td>
                                    <td><?php echo $item['unit']; ?></td>
                                    <td style="font-family:var(--mono)"><?php echo format_currency($item['unit_price']); ?></td>
                                    <td><?php echo $item['content'] ?? 1; ?></td>
                                    <td><?php echo $item['bodega_stock']; ?></td><td><?php echo $item['shelves_stock']; ?></td>
                                    <td><?php echo $item['delivery_stock']; ?></td><td><?php echo $item['total_stock']; ?></td>
                                    <td><?php echo $item['bodega_stock']; ?></td><td><?php echo $item['shelves_stock']; ?></td>
                                    <td><?php echo $item['delivery_stock']; ?></td><td><?php echo $item['total_stock']; ?></td>
                                    <td><?php echo $item['total_stock']; ?></td>
                                    <td style="font-family:var(--mono);font-weight:600"><?php echo format_currency($item['total_amount']); ?></td>
                                    <td class="<?php echo $item['suggested_order'] > 0 ? 'text-danger fw-bold' : ''; ?>"><?php echo $item['suggested_order']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="6">TOTALS</th>
                                    <td><?php echo array_sum(array_column($generated_report['data'],'bodega_stock')); ?></td>
                                    <td><?php echo array_sum(array_column($generated_report['data'],'shelves_stock')); ?></td>
                                    <td><?php echo array_sum(array_column($generated_report['data'],'delivery_stock')); ?></td>
                                    <td><?php echo array_sum(array_column($generated_report['data'],'total_stock')); ?></td>
                                    <td><?php echo array_sum(array_column($generated_report['data'],'bodega_stock')); ?></td>
                                    <td><?php echo array_sum(array_column($generated_report['data'],'shelves_stock')); ?></td>
                                    <td><?php echo array_sum(array_column($generated_report['data'],'delivery_stock')); ?></td>
                                    <td><?php echo array_sum(array_column($generated_report['data'],'total_stock')); ?></td>
                                    <td><?php echo array_sum(array_column($generated_report['data'],'total_stock')); ?></td>
                                    <td style="font-family:var(--mono);font-weight:700"><?php echo format_currency(array_sum(array_column($generated_report['data'],'total_amount'))); ?></td>
                                    <td><?php echo array_sum(array_column($generated_report['data'],'suggested_order')); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="mt-4 d-flex gap-2 justify-content-center">
                <button class="btn btn-primary" onclick="exportReport()"><i class="ti ti-download me-1"></i> Export Report</button>
                <button class="btn btn-outline-secondary" onclick="window.print()"><i class="ti ti-printer me-1"></i> Print Report</button>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /page-body -->
</div><!-- /main-wrap -->

<?php include 'mcbot_widget.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const body = document.body;
    const toggle = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('mobileSbBackdrop');
    function closeSidebarOnDesktop() {
        if (window.innerWidth >= 992) body.classList.remove('sb-open');
    }
    if (toggle) toggle.addEventListener('click', () => body.classList.toggle('sb-open'));
    if (backdrop) backdrop.addEventListener('click', () => body.classList.remove('sb-open'));
    window.addEventListener('resize', closeSidebarOnDesktop);
    closeSidebarOnDesktop();
})();

function toggleFilters() {
    const reportType = document.getElementById('report_type').value;
    const employeeFilter = document.getElementById('employee_filter');
    const employeeLabel = document.getElementById('employee_filter_label');
    employeeFilter.style.display = 'none';
    employeeLabel.style.display = 'none';
    if (reportType === 'attendance') {
        employeeFilter.style.display = 'block';
        employeeLabel.style.display = 'block';
    }
}

function exportReport() {
    alert('Export functionality would generate and download the report in Excel/PDF format.');
}
</script>
</body>
</html>
