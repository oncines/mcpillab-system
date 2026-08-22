<?php
require_once 'config.php';

// Get attendance history for the current user
$user_id = $_SESSION['user_id'];
$attendance_records = get_attendance_history($user_id); // Assumes this function exists

// Get unread messages count
$unread_messages = get_unread_message_count($_SESSION['user_id']);

// Calculate stats
$total_days = count($attendance_records);
$total_hours = 0;
$on_time_count = 0;
$late_count = 0;
$absent_count = 0;
$check_in_times = [];
$check_out_times = [];

foreach ($attendance_records as $record) {
    if ($record['status'] === 'on_time') $on_time_count++;
    elseif ($record['status'] === 'late') $late_count++;
    elseif ($record['status'] === 'absent') $absent_count++;

    if (!empty($record['check_in']) && !empty($record['check_out'])) {
        $in = strtotime($record['check_in']);
        $out = strtotime($record['check_out']);
        $hours = ($out - $in) / 3600;
        $total_hours += $hours;
        $check_in_times[] = $in;
        $check_out_times[] = $out;
    }
}

$on_time_pct  = $total_days > 0 ? round(($on_time_count  / $total_days) * 100) : 0;
$late_pct     = $total_days > 0 ? round(($late_count      / $total_days) * 100) : 0;
$absent_pct   = $total_days > 0 ? round(($absent_count    / $total_days) * 100) : 0;

$avg_check_in  = !empty($check_in_times)  ? date('g:i A', (int)(array_sum($check_in_times)  / count($check_in_times)))  : 'N/A';
$avg_check_out = !empty($check_out_times) ? date('g:i A', (int)(array_sum($check_out_times) / count($check_out_times))) : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Attendance History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="public/css/design-system.css" rel="stylesheet">
    <style>
        /* =====================
           SIDEBAR (same as dashboard)
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

            /* Page palette */
            --page-bg: #f0f2f7;
            --card-bg: #ffffff;
            --border-col: #e4e8f0;
            --text-primary: #1a1d2e;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --accent-blue: #2563eb;
            --accent-green: #16a34a;
            --accent-amber: #d97706;
            --accent-red: #dc2626;
            --accent-purple: #7c3aed;
            --on-time-color: #2563eb;
            --late-color: #f59e0b;
            --absent-color: #ef4444;
            --holiday-color: #8b5cf6;
        }

        /* ---- Sidebar shell ---- */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--sb-bg);
            display: flex;
            flex-direction: column;
            z-index: 9999;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
        }
        .sidebar::-webkit-scrollbar { display: none; }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 16px 18px;
            border-bottom: 1px solid var(--sb-border);
            margin-bottom: 10px;
        }
        .sidebar-logo-ring {
            width: 40px; height: 40px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            border: 2px solid rgba(255,255,255,0.30);
            display: flex; align-items: center; justify-content: center;
        }
        .sidebar-logo-ring img { width:100%; height:100%; object-fit:cover; display:block; }
        .sidebar-brand-text { display:flex; flex-direction:column; justify-content:center; min-width:0; gap:2px; }
        .sidebar-brand-name { font-size:0.92rem; font-weight:800; color:#fff; letter-spacing:0.06em; line-height:1.1; text-transform:uppercase; margin:0; }
        .sidebar-brand-sub  { font-size:0.55rem; color:rgba(255,255,255,0.45); letter-spacing:0.10em; text-transform:uppercase; line-height:1.2; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin:0; }

        .sidebar-nav { flex:1; padding:0 10px; }
        .nav-section-label { font-size:0.62rem; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--sb-label); padding:14px 8px 6px; }

        .sidebar-link {
            display:flex; align-items:center; gap:10px;
            padding:9px 10px;
            border-radius:var(--sb-radius);
            color:var(--sb-text);
            text-decoration:none;
            font-size:0.84rem; font-weight:500;
            transition:background .15s, color .15s;
            position:relative;
            margin-bottom:2px;
        }
        .sidebar-link:hover  { background:var(--sb-hover); color:var(--sb-text-active); }
        .sidebar-link.active { background:var(--sb-active); color:var(--sb-text-active); }
        .sidebar-link .icon {
            width:30px; height:30px; border-radius:7px;
            background:var(--sb-icon-bg);
            display:flex; align-items:center; justify-content:center;
            font-size:0.8rem; flex-shrink:0;
            transition:background .15s;
        }
        .sidebar-link.active .icon { background:var(--sb-icon-active); color:#fff; }
        .sidebar-link .link-label { flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .sidebar-link .badge-dot { width:18px; height:18px; border-radius:50%; background:#e5534b; color:#fff; font-size:0.6rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }

        .sidebar-footer { padding:10px 10px 20px; border-top:1px solid var(--sb-border); margin-top:6px; }
        .sidebar-link.logout .icon { background:rgba(229,83,75,0.15); color:#e5534b; }
        .sidebar-link.logout      { color:rgba(229,83,75,0.85); }
        .sidebar-link.logout:hover{ background:rgba(229,83,75,0.10); color:#e5534b; }

        /* =====================
           MAIN LAYOUT
           ===================== */
        body { background: var(--page-bg); font-family: 'DM Sans', sans-serif; font-size: 14px; margin: 0; }
        .main-content { margin-left: var(--sidebar-w); padding: 28px 28px 40px; min-height: 100vh; }

        /* =====================
           TOP BAR
           ===================== */
        .page-topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 24px;
        }
        .page-title-group {}
        .page-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 4px;
            letter-spacing: -0.02em;
        }
        .page-subtitle {
            font-size: 0.82rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .page-subtitle i { color: var(--accent-blue); font-size: 0.75rem; }

        /* Legend dots */
        .legend {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .dot {
            width: 9px; height: 9px;
            border-radius: 50%;
        }
        .dot-blue   { background: var(--on-time-color); }
        .dot-amber  { background: var(--late-color); }
        .dot-red    { background: var(--absent-color); }

        /* =====================
           PROFILE SUMMARY CARD
           ===================== */
        .summary-card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.07), 0 4px 12px rgba(0,0,0,0.04);
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .summary-avatar {
            width: 52px; height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d1b3e 0%, #2f69ff 100%);
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .summary-identity { flex: 1; min-width: 0; }
        .summary-name   { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0 0 2px; }
        .summary-role   { font-size: 0.8rem; color: var(--text-secondary); margin: 0 0 4px; }
        .summary-email  { font-size: 0.78rem; color: var(--text-muted); }
        .summary-actions { display: flex; gap: 10px; align-items: center; }
        .action-btn {
            width: 38px; height: 38px;
            border-radius: 50%;
            border: 1.5px solid var(--border-col);
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        .action-btn:hover { border-color: var(--accent-blue); color: var(--accent-blue); background: #eff6ff; }

        .summary-divider { width: 1px; height: 60px; background: var(--border-col); margin: 0 8px; }

        .stat-pill {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 80px;
        }
        .stat-pill-val {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.1;
            letter-spacing: -0.03em;
        }
        .stat-pill-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
            text-align: center;
            margin-top: 3px;
        }

        /* =====================
           PERIOD HEADING
           ===================== */
        .period-heading {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-secondary);
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 8px 0 14px;
            border-bottom: 2px solid var(--border-col);
            margin-bottom: 16px;
        }

        /* =====================
           ATTENDANCE GRID
           ===================== */
        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 32px;
        }

        .att-card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1.5px solid var(--border-col);
            padding: 16px 18px 14px;
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative;
            overflow: hidden;
        }
        .att-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.09);
            transform: translateY(-1px);
        }
        .att-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-radius: 12px 12px 0 0;
        }
        .att-card.on-time::before  { background: var(--on-time-color); }
        .att-card.late::before     { background: var(--late-color); }
        .att-card.absent::before   { background: var(--absent-color); }
        .att-card.holiday::before  { background: var(--holiday-color); }

        .att-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .att-date {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .status-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .status-badge::before { content: '●'; font-size: 0.5rem; }
        .status-badge.on-time { background: #dbeafe; color: var(--on-time-color); }
        .status-badge.late    { background: #fef3c7; color: #92400e; }
        .status-badge.absent  { background: #fee2e2; color: #991b1b; }
        .status-badge.holiday { background: #ede9fe; color: #5b21b6; }

        .att-times {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0;
            margin-bottom: 12px;
        }
        .att-time-col {}
        .att-time-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 3px;
        }
        .att-time-val {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'DM Mono', monospace;
            letter-spacing: -0.02em;
        }
        .att-time-val.empty { color: var(--text-muted); font-weight: 400; font-size: 0.9rem; }

        .att-notes {
            font-size: 0.76rem;
            color: var(--text-secondary);
            display: flex;
            gap: 6px;
        }
        .att-notes-label {
            color: var(--text-muted);
            font-weight: 600;
            flex-shrink: 0;
        }
        .att-notes-text {
            font-style: italic;
            color: #6366f1;
        }
        .att-notes-text.empty { color: var(--text-muted); font-style: normal; }

        /* Absent state */
        .att-card.absent .att-time-val { color: var(--text-muted); font-weight: 400; }
        .att-card.absent .att-times    { opacity: 0.5; }

        /* =====================
           MOBILE TOGGLE
           ===================== */
        .mobile-sidebar-toggle {
            display: none;
            align-items: center; justify-content: center;
            width: 40px; height: 40px;
            border: none; border-radius: 10px;
            background: var(--sb-bg); color: #fff;
            flex: 0 0 auto; cursor: pointer;
        }
        .mobile-sidebar-backdrop { display: none; }

        @media (max-width: 991.98px) {
            .sidebar { width: min(var(--sidebar-w), 86vw); transform: translateX(-100%); transition: transform .3s ease; box-shadow: 0 12px 28px rgba(0,0,0,.25); }
            body.sidebar-open .sidebar { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px 16px 32px; }
            .mobile-sidebar-toggle { display: inline-flex; }
            .mobile-sidebar-backdrop { display: block; position: fixed; inset: 0; background: rgba(9,15,85,.45); opacity: 0; pointer-events: none; transition: opacity .3s; z-index: 9998; }
            body.sidebar-open .mobile-sidebar-backdrop { opacity: 1; pointer-events: auto; }
            .attendance-grid { grid-template-columns: repeat(2, 1fr); }
            .summary-divider { display: none; }
        }
        @media (max-width: 575.98px) {
            .attendance-grid { grid-template-columns: 1fr; }
            .page-title { font-size: 1.3rem; }
            .att-times { grid-template-columns: 1fr 1fr 1fr; }
            .att-time-val { font-size: 0.9rem; }
            .summary-card { padding: 16px; gap: 14px; }
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">

        <!-- =====================
             SIDEBAR
             ===================== -->
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

                <a class="sidebar-link" href="<?php echo is_employee() ? 'employee_home.php' : 'dashboard.php'; ?>">
                    <span class="icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span class="link-label"><?php echo is_employee() ? 'Home' : 'Dashboard'; ?></span>
                </a>

                <?php if (is_admin()): ?>
                <a class="sidebar-link" href="purchase_order.php">
                    <span class="icon"><i class="fas fa-shopping-cart"></i></span>
                    <span class="link-label">Purchase Order</span>
                </a>
                <a class="sidebar-link" href="purchase_invoice.php">
                    <span class="icon"><i class="fas fa-file-invoice"></i></span>
                    <span class="link-label">Purchase Invoice</span>
                </a>
                <a class="sidebar-link" href="employee_profile.php">
                    <span class="icon"><i class="fas fa-users"></i></span>
                    <span class="link-label">Employee Profile</span>
                </a>
                <a class="sidebar-link" href="attendance.php">
                    <span class="icon"><i class="fas fa-clock"></i></span>
                    <span class="link-label">Attendance</span>
                </a>
                <?php endif; ?>

                <?php if (is_employee() || is_store()): ?>
                <a class="sidebar-link" href="inventory.php">
                    <span class="icon"><i class="fas fa-boxes"></i></span>
                    <span class="link-label"><?php echo is_employee() ? 'Inventory' : 'Inventory Management'; ?></span>
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
                <?php endif; ?>

                <?php if (is_employee()): ?>
                <a class="sidebar-link" href="attendance_camera.php">
                    <span class="icon"><i class="fas fa-clock"></i></span>
                    <span class="link-label">Attendance</span>
                </a>
                <a class="sidebar-link active" href="attendance_history.php">
                    <span class="icon"><i class="fas fa-history"></i></span>
                    <span class="link-label">Attendance History</span>
                </a>
                <?php endif; ?>

               
                <?php if (!is_employee()): ?>
                    <a class="sidebar-link" href="delivery_history.php">
                        <span class="icon"><i class="fas fa-history"></i></span>
                        <span class="link-label">Delivery History</span>
                    </a>
                <?php endif; ?>

                <div class="nav-section-label">Tools</div>
                <a class="sidebar-link" href="reports.php">
                    <span class="icon"><i class="fas fa-chart-bar"></i></span>
                    <span class="link-label">Reports</span>
                </a>
                <a class="sidebar-link" href="chat_interface.php">
                    <span class="icon"><i class="fas fa-comments"></i></span>
                    <span class="link-label">Chat</span>
                    <?php if ($unread_messages > 0): ?>
                        <span class="badge-dot"><?php echo $unread_messages; ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="sidebar-footer">
                <div class="nav-section-label" style="padding-top:6px;">Account</div>
                <a class="sidebar-link" href="settings.php">
                    <span class="icon"><i class="fas fa-cog"></i></span>
                    <span class="link-label">Settings</span>
                </a>
                <a class="sidebar-link logout" href="logout.php">
                    <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span class="link-label">Logout</span>
                </a>
            </div>
        </nav>
        <div class="mobile-sidebar-backdrop" id="mobileSidebarBackdrop"></div>

        <!-- =====================
             MAIN CONTENT
             ===================== -->
        <main class="main-content">

            <!-- Mobile toggle + Page title -->
            <div class="page-topbar">
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="mobile-sidebar-toggle" id="sidebarToggle" aria-label="Open navigation">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title-group">
                        <h1 class="page-title">Attendance History</h1>
                        <div class="page-subtitle">
                            <i class="fas fa-calendar-alt"></i>
                            Today <?php echo date('D, M j, Y'); ?>
                        </div>
                        <div class="legend">
                            <div class="legend-item"><div class="dot dot-blue"></div> On time <?php echo $on_time_pct; ?>%</div>
                            <div class="legend-item"><div class="dot dot-amber"></div> Late <?php echo $late_pct; ?>%</div>
                            <div class="legend-item"><div class="dot dot-red"></div> Absent <?php echo $absent_pct; ?>%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile summary bar -->
            <div class="summary-card">
                <div class="summary-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?></div>
                <div class="summary-identity">
                    <div class="summary-name"><?php echo $_SESSION['full_name']; ?></div>
                    <div class="summary-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
                    <div class="summary-email"><?php echo $_SESSION['email'] ?? ''; ?></div>
                </div>
                <div class="summary-actions">
                    <button class="action-btn" title="Call"><i class="fas fa-phone"></i></button>
                    <button class="action-btn" title="Message"><i class="fas fa-comment-dots"></i></button>
                </div>

                <div class="summary-divider"></div>

                <div class="stat-pill">
                    <div class="stat-pill-val"><?php echo $total_days; ?> days</div>
                    <div class="stat-pill-label">Total Attendance</div>
                </div>
                <div class="stat-pill">
                    <div class="stat-pill-val"><?php echo round($total_hours); ?> hours</div>
                    <div class="stat-pill-label">Total hours</div>
                </div>
                <div class="stat-pill">
                    <div class="stat-pill-val"><?php echo $avg_check_in; ?></div>
                    <div class="stat-pill-label">Avg check in</div>
                </div>
                <div class="stat-pill">
                    <div class="stat-pill-val"><?php echo $avg_check_out; ?></div>
                    <div class="stat-pill-label">Avg check out</div>
                </div>
            </div>

            <?php
            // Group records by month/period
            $grouped = [];
            foreach ($attendance_records as $record) {
                $period_key = date('M j', strtotime($record['date']));
                // Group by bi-weekly or monthly; here we group by month
                $month_key = date('F Y', strtotime($record['date']));
                $grouped[$month_key][] = $record;
            }

            if (empty($grouped)):
            ?>
                <div style="text-align:center; padding:60px 20px; color:var(--text-muted);">
                    <i class="fas fa-calendar-times" style="font-size:2.5rem; margin-bottom:12px; display:block; opacity:0.4;"></i>
                    No attendance records found.
                </div>
            <?php else: foreach ($grouped as $period => $records): ?>

            <div class="period-heading"><?php echo $period; ?></div>
            <div class="attendance-grid">
                <?php foreach ($records as $rec):
                    $status       = $rec['status'] ?? 'on_time';
                    $check_in     = !empty($rec['check_in'])  ? date('g:i A', strtotime($rec['check_in']))  : null;
                    $check_out    = !empty($rec['check_out']) ? date('g:i A', strtotime($rec['check_out'])) : null;
                    $total_hrs    = ($check_in && $check_out)
                                   ? round((strtotime($rec['check_out']) - strtotime($rec['check_in'])) / 3600) . 'hr'
                                   : '0hr';
                    $notes        = $rec['notes'] ?? '';
                    $date_label   = date('D, M j, Y', strtotime($rec['date']));

                    $badge_label  = match($status) {
                        'on_time' => 'On time',
                        'late'    => 'Late',
                        'absent'  => 'Absent',
                        'holiday' => 'Holiday',
                        default   => ucfirst($status),
                    };
                ?>
                <div class="att-card <?php echo $status; ?>">
                    <div class="att-card-top">
                        <div class="att-date"><?php echo $date_label; ?></div>
                        <div class="status-badge <?php echo $status; ?>"><?php echo $badge_label; ?></div>
                    </div>

                    <div class="att-times">
                        <div class="att-time-col">
                            <div class="att-time-label">Check In</div>
                            <div class="att-time-val <?php echo $check_in ? '' : 'empty'; ?>">
                                <?php echo $check_in ?? '—'; ?>
                            </div>
                        </div>
                        <div class="att-time-col">
                            <div class="att-time-label">Check Out</div>
                            <div class="att-time-val <?php echo $check_out ? '' : 'empty'; ?>">
                                <?php echo $check_out ?? '—'; ?>
                            </div>
                        </div>
                        <div class="att-time-col">
                            <div class="att-time-label">Total</div>
                            <div class="att-time-val"><?php echo $total_hrs; ?></div>
                        </div>
                    </div>

                    <div class="att-notes">
                        <span class="att-notes-label">Notes:</span>
                        <span class="att-notes-text <?php echo $notes ? '' : 'empty'; ?>">
                            <?php echo $notes ?: 'No notes'; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php endforeach; endif; ?>

        </main>
    </div>
</div>

<?php include 'mcbot_widget.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const body     = document.body;
    const toggle   = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('mobileSidebarBackdrop');

    function closeSidebarOnDesktop() {
        if (window.innerWidth >= 992) body.classList.remove('sidebar-open');
    }
    if (toggle)   toggle.addEventListener('click',   () => body.classList.toggle('sidebar-open'));
    if (backdrop) backdrop.addEventListener('click', () => body.classList.remove('sidebar-open'));
    window.addEventListener('resize', closeSidebarOnDesktop);
    closeSidebarOnDesktop();
})();
</script>
</body>
</html>
