<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('index.php');
}

$chat_partner_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'manager') {
    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT id, full_name, role FROM users WHERE id != :current_user ORDER BY full_name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':current_user', $_SESSION['user_id']);
    $stmt->execute();
    $chat_partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $chat_partners = get_admin_users();
}

if (!$chat_partner_id && !empty($chat_partners)) {
    $chat_partner_id = $chat_partners[0]['id'];
}

$messages = [];
if ($chat_partner_id) {
    $messages = get_chat_messages($_SESSION['user_id'], $chat_partner_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_chat_message'])) {
    $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING));
    if (!empty($message) && $chat_partner_id) {
        send_message($_SESSION['user_id'], $chat_partner_id, $message);
        header("Location: chat_interface.php?user_id=$chat_partner_id");
        exit;
    }
}

$current_partner = null;
if ($chat_partner_id) {
    foreach ($chat_partners as $partner) {
        if ($partner['id'] == $chat_partner_id) {
            $current_partner = $partner;
            break;
        }
    }
}

$unread_messages = get_unread_message_count($_SESSION['user_id']);

function initials($name) {
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    return strtoupper(substr($name, 0, 2));
}

$avatar_colors = ['#4f6ef7','#e0534a','#f5a623','#27ae60','#8e44ad','#16a085','#d35400','#2980b9'];
function avatar_color($id) {
    global $avatar_colors;
    return $avatar_colors[$id % count($avatar_colors)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> – Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy:       #0a1045;
            --navy-2:     #0f1860;
            --navy-3:     #1a2580;
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
            --blue:       #1645b6;
            --blue-bg:    #e8eeff;
            --red:        #d4241a;

            --sb-bg:        #0d1b3e;
            --sb-active-bg: rgba(255,255,255,0.11);
            --sb-hover-bg:  rgba(255,255,255,0.06);
            --sb-label:     rgba(255,255,255,0.32);
            --sb-text:      rgba(255,255,255,0.70);
            --sb-text-act:  #ffffff;
            --sb-border:    rgba(255,255,255,0.07);
            --sidebar-w:    220px;
            --radius:       14px;
            --radius-sm:    9px;
            --font:         'Sora', sans-serif;
            --mono:         'DM Mono', monospace;
            --shadow-xs:    0 1px 2px rgba(10,16,69,0.05);
            --shadow-sm:    0 2px 8px rgba(10,16,69,0.07),0 1px 3px rgba(10,16,69,0.04);
            --shadow:       0 6px 24px rgba(10,16,69,0.09),0 2px 8px rgba(10,16,69,0.04);

            /* Messages UI */
            --msg-blue:      #4f6ef7;
            --msg-blue-lt:   #eef0fe;
            --msg-green:     #22c55e;
            --msg-border:    #e8eaed;
            --msg-sub:       #6b7280;
            --msg-faint:     #9ca3af;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; font-family: var(--font); background: var(--bg); color: var(--text-1); font-size: 13px; line-height: 1.55; -webkit-font-smoothing: antialiased; }

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

        .mobile-sb-toggle { display: none; align-items: center; justify-content: center; width: 36px; height: 36px; border: none; border-radius: var(--radius-sm); background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.7); cursor: pointer; flex: 0 0 auto; position: absolute; top: 14px; left: 14px; z-index: 1000; }
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
        .main-wrap {
            margin-left: var(--sidebar-w);
            width: calc(100vw - var(--sidebar-w));
            height: 100vh;
            overflow: hidden;
        }

        /* ── MESSAGES APP ── */
        .app-body {
            display: grid;
            grid-template-columns: 280px 1fr 290px;
            height: 100vh;
        }

        /* LEFT: conversation list */
        .msg-list-pane {
            background: var(--white);
            border-right: 1px solid var(--msg-border);
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .msg-list-header {
            padding: 18px 16px 12px;
            border-bottom: 1px solid var(--msg-border);
        }

        .msg-list-title {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -.025em;
            color: var(--text-1);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filter-btn {
            font-size: 11px;
            font-weight: 700;
            color: var(--blue);
            background: var(--blue-bg);
            border: none;
            padding: 4px 10px;
            border-radius: 20px;
            cursor: pointer;
            font-family: var(--font);
        }

        .msg-search { position: relative; }
        .msg-search input {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 12px 8px 32px;
            font-size: 12.5px;
            font-family: var(--font);
            color: var(--text-1);
            outline: none;
            transition: border .15s;
        }
        .msg-search input:focus { border-color: var(--navy-3); }
        .msg-search input::placeholder { color: var(--text-4); }
        .msg-search i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-4); font-size: 11px; }

        .msg-conversations { flex: 1; overflow-y: auto; padding: 4px 0; }

        .msg-conv-item {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            gap: 10px;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: background .12s;
            border-left: 3px solid transparent;
        }
        .msg-conv-item:hover { background: var(--bg); text-decoration: none; }
        .msg-conv-item.active { background: var(--blue-bg); border-left-color: var(--blue); }

        .conv-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: #fff;
            flex-shrink: 0; position: relative;
        }
        .online-dot { position: absolute; bottom: 1px; right: 1px; width: 9px; height: 9px; border-radius: 50%; background: var(--msg-green); border: 2px solid var(--white); }

        .conv-body { flex: 1; min-width: 0; }
        .conv-top { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 2px; }
        .conv-name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-1); }
        .conv-time { font-size: 10.5px; color: var(--text-4); white-space: nowrap; margin-left: 4px; font-family: var(--mono); }
        .conv-preview { font-size: 12px; color: var(--text-3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 4px; }
        .conv-badge { background: var(--blue); color: #fff; font-size: 9px; font-weight: 700; padding: 1px 5px; border-radius: 10px; flex-shrink: 0; }

        .msg-list-footer {
            padding: 10px 14px;
            border-top: 1px solid var(--msg-border);
            font-size: 12px;
            color: var(--text-4);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .msg-list-footer select { border: 1px solid var(--border); border-radius: 6px; padding: 2px 6px; font-size: 12px; color: var(--text-2); background: var(--white); outline: none; font-family: var(--font); }

        /* CENTER: thread */
        .msg-thread-pane {
            display: flex;
            flex-direction: column;
            background: var(--bg);
            height: 100vh;
        }

        .thread-header {
            background: var(--white);
            border-bottom: 1px solid var(--msg-border);
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-shrink: 0;
            box-shadow: var(--shadow-xs);
        }
        .thread-header-left { display: flex; align-items: center; gap: 10px; }
        .thread-header-name { font-size: 14px; font-weight: 700; color: var(--text-1); }
        .thread-header-role { font-size: 11px; color: var(--text-4); }
        .thread-header-actions { display: flex; gap: 6px; }

        .icon-btn {
            width: 32px; height: 32px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--text-3);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all .15s;
            font-size: 13px;
        }
        .icon-btn:hover { background: var(--bg); color: var(--blue); border-color: var(--border-2); }

        .thread-messages {
            flex: 1;
            overflow-y: auto;
            padding: 18px 22px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .date-divider { text-align: center; margin: 14px 0 8px; }
        .date-divider span { background: var(--border); color: var(--text-4); font-size: 11px; font-weight: 600; padding: 3px 12px; border-radius: 20px; }

        .msg-row { display: flex; align-items: flex-end; gap: 6px; max-width: 70%; }
        .msg-row.sent { align-self: flex-end; flex-direction: row-reverse; }
        .msg-row.received { align-self: flex-start; }

        .msg-sender-label { font-size: 11px; font-weight: 600; color: var(--text-4); margin-bottom: 3px; }

        .msg-bubble {
            padding: 9px 13px;
            border-radius: 16px;
            font-size: 13px;
            line-height: 1.5;
            word-break: break-word;
        }
        .msg-row.received .msg-bubble {
            background: var(--white);
            color: var(--text-1);
            border-bottom-left-radius: 4px;
            box-shadow: var(--shadow-sm);
        }
        .msg-row.sent .msg-bubble {
            background: var(--navy);
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .msg-meta { font-size: 10px; margin-top: 4px; color: var(--text-4); display: flex; align-items: center; gap: 3px; font-family: var(--mono); }
        .msg-row.sent .msg-meta { justify-content: flex-end; color: rgba(255,255,255,.5); }

        .thread-input {
            background: var(--white);
            border-top: 1px solid var(--msg-border);
            padding: 12px 18px;
            flex-shrink: 0;
            box-shadow: 0 -2px 8px rgba(10,16,69,.04);
        }
        .thread-input-row { display: flex; align-items: center; gap: 8px; }
        .thread-input-field {
            flex: 1;
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 9px 16px;
            font-size: 13px;
            font-family: var(--font);
            outline: none;
            color: var(--text-1);
            background: var(--bg);
            transition: border .15s;
        }
        .thread-input-field:focus { border-color: var(--navy-3); background: var(--white); }
        .thread-input-field::placeholder { color: var(--text-4); }
        .send-btn {
            width: 38px; height: 38px;
            border-radius: 50%;
            border: none;
            background: var(--navy);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all .2s;
            flex-shrink: 0;
        }
        .send-btn:hover { background: var(--navy-2); transform: scale(1.05); }

        .empty-thread { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-4); gap: 10px; }
        .empty-thread i { font-size: 38px; }
        .empty-thread p { font-size: 14px; font-weight: 500; }

        /* RIGHT: contact details */
        .msg-detail-pane {
            background: var(--white);
            border-left: 1px solid var(--msg-border);
            overflow-y: auto;
            padding: 22px 18px;
            height: 100vh;
        }
        .detail-section-title { font-size: 14px; font-weight: 700; color: var(--text-1); margin-bottom: 14px; }
        .detail-contact-header { display: flex; align-items: center; gap: 11px; margin-bottom: 18px; }
        .detail-contact-name { font-size: 14px; font-weight: 700; color: var(--text-1); }
        .detail-contact-id { font-size: 11px; color: var(--text-4); margin-top: 1px; font-family: var(--mono); }
        .detail-more-btn { margin-left: auto; width: 26px; height: 26px; border-radius: 50%; border: 1px solid var(--border); background: var(--white); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-3); font-size: 12px; }
        .detail-field { display: flex; align-items: flex-start; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--border); gap: 8px; }
        .detail-field:last-child { border-bottom: none; }
        .detail-field-label { font-size: 10px; font-weight: 700; color: var(--text-4); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 2px; }
        .detail-field-value { font-size: 12.5px; color: var(--text-1); line-height: 1.5; }
        .detail-field-action { color: var(--blue); font-size: 17px; cursor: pointer; flex-shrink: 0; margin-top: 2px; }
        .detail-show-more { font-size: 12px; color: var(--blue); cursor: pointer; margin-top: 6px; font-weight: 600; }
        .detail-divider { height: 1px; background: var(--border); margin: 18px 0; }
        .call-history-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .call-entry { border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 9px 11px; margin-bottom: 7px; background: var(--bg); }
        .call-entry.highlighted { background: var(--blue-bg); border-color: #c5d6f7; }
        .call-entry-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 3px; }
        .call-name { font-size: 12.5px; font-weight: 600; color: var(--text-1); }
        .call-date { font-size: 10px; color: var(--text-4); font-family: var(--mono); }
        .call-row { display: flex; align-items: center; justify-content: space-between; }
        .call-number { font-size: 11px; color: var(--text-3); display: flex; align-items: center; gap: 4px; font-family: var(--mono); }
        .call-number i { color: var(--text-4); font-size: 10px; }
        .call-tag { font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 10px; background: var(--border); color: var(--text-3); }
        .call-link { font-size: 11px; color: var(--blue); cursor: pointer; margin-top: 4px; font-weight: 600; }
        .call-viewed { display: flex; align-items: center; gap: 4px; font-size: 11px; color: var(--green); margin-top: 4px; }

        /* Scrollbars */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 10px; }

        @media (max-width: 1100px) { .app-body { grid-template-columns: 260px 1fr; } .msg-detail-pane { display: none; } }
        @media (max-width: 768px) { .app-body { grid-template-columns: 1fr; } .msg-list-pane { display: none; } }
        @media (max-width: 991.98px) { .main-wrap { margin-left: 0; width: 100vw; } }
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
        <a class="sb-item" href="reports.php"><i class="ti ti-chart-bar"></i>Reports</a>
        <a class="sb-item active" href="chat_interface.php">
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
<div class="app-body">

    <!-- ── LEFT: Conversations ── -->
    <div class="msg-list-pane">
        <button type="button" class="mobile-sb-toggle" id="sidebarToggle" aria-label="Open navigation">
            <i class="fas fa-bars"></i>
        </button>
        <div class="msg-list-header">
            <div class="msg-list-title">
                Messages
                <button class="filter-btn">Filters</button>
            </div>
            <div class="msg-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search conversations…" id="convSearch">
            </div>
        </div>

        <div class="msg-conversations" id="convList">
            <?php foreach ($chat_partners as $i => $partner):
                $isActive    = ($chat_partner_id == $partner['id']);
                $color       = avatar_color($partner['id']);
                $ini         = initials($partner['full_name']);
                $preview_msgs = get_chat_messages($_SESSION['user_id'], $partner['id']);
                $last_msg     = !empty($preview_msgs) ? end($preview_msgs) : null;
                $preview_text = $last_msg ? htmlspecialchars(substr($last_msg['message'], 0, 42)) : 'No messages yet';
                $preview_time = $last_msg ? date('g:i A', strtotime($last_msg['created_at'])) : '';
            ?>
            <a href="chat_interface.php?user_id=<?php echo $partner['id']; ?>"
               class="msg-conv-item <?php echo $isActive ? 'active' : ''; ?>">
                <div class="conv-avatar" style="background:<?php echo $color; ?>">
                    <?php echo $ini; ?>
                    <?php if ($i < 3): ?><span class="online-dot"></span><?php endif; ?>
                </div>
                <div class="conv-body">
                    <div class="conv-top">
                        <span class="conv-name"><?php echo htmlspecialchars($partner['full_name']); ?></span>
                        <span class="conv-time"><?php echo $preview_time; ?></span>
                    </div>
                    <div class="conv-preview">
                        <?php echo $preview_text; ?>
                        <?php if ($i < 2): ?><span class="conv-badge">●</span><?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="msg-list-footer">
            <div style="display:flex;align-items:center;gap:6px;">
                <select><option>10</option><option>25</option><option>50</option></select>
                <span>of <?php echo count($chat_partners); ?> messages</span>
            </div>
            <button class="icon-btn" style="width:26px;height:26px;font-size:10px;">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- ── CENTER: Thread ── -->
    <div class="msg-thread-pane">
        <?php if ($current_partner): ?>
            <div class="thread-header">
                <div class="thread-header-left">
                    <div class="conv-avatar" style="background:<?php echo avatar_color($current_partner['id']); ?>;width:36px;height:36px;font-size:12px;">
                        <?php echo initials($current_partner['full_name']); ?>
                    </div>
                    <div>
                        <div class="thread-header-name"><?php echo htmlspecialchars($current_partner['full_name']); ?></div>
                        <div class="thread-header-role"><?php echo ucfirst($current_partner['role']); ?></div>
                    </div>
                </div>
                <div class="thread-header-actions">
                    <button class="icon-btn" title="Attach image"><i class="ti ti-photo" style="font-size:16px"></i></button>
                    <button class="icon-btn" title="Phone"><i class="ti ti-phone" style="font-size:16px"></i></button>
                </div>
            </div>

            <div class="thread-messages" id="threadMessages">
                <?php
                $current_date = null;
                foreach ($messages as $msg):
                    $msg_date = date('Y-m-d', strtotime($msg['created_at']));
                    $is_sent  = $msg['sender_id'] == $_SESSION['user_id'];
                    if ($current_date !== $msg_date):
                        $current_date = $msg_date;
                        if ($msg_date === date('Y-m-d')) $label = 'Today';
                        elseif ($msg_date === date('Y-m-d', strtotime('-1 day'))) $label = 'Yesterday, ' . date('M j', strtotime($msg['created_at']));
                        else $label = date('l, M j', strtotime($msg['created_at']));
                ?>
                    <div class="date-divider"><span><?php echo $label; ?></span></div>
                <?php endif; ?>
                <div class="msg-row <?php echo $is_sent ? 'sent' : 'received'; ?>">
                    <div>
                        <div class="msg-sender-label" style="<?php echo $is_sent ? 'text-align:right;' : ''; ?>">
                            <?php echo $is_sent ? 'You' : htmlspecialchars($current_partner['full_name']); ?>
                        </div>
                        <div class="msg-bubble">
                            <?php if ($msg['message_type'] === 'document'): ?>
                                <div style="display:flex;align-items:center;gap:8px;padding:2px 0;">
                                    <i class="ti ti-file-word" style="font-size:22px;opacity:.8;"></i>
                                    <div>
                                        <div style="font-size:13px;font-weight:600;">Design_project_2021.docx</div>
                                        <div style="font-size:11px;opacity:.7;">5.5 MB</div>
                                    </div>
                                </div>
                            <?php elseif ($msg['message_type'] === 'voice'): ?>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <button style="width:32px;height:32px;border-radius:50%;border:none;background:rgba(255,255,255,.2);color:inherit;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                                        <i class="ti ti-player-play" style="font-size:12px;"></i>
                                    </button>
                                    <div style="flex:1;height:3px;background:rgba(255,255,255,.3);border-radius:4px;"></div>
                                    <span style="font-size:11px;opacity:.7;font-family:var(--mono)">02:18</span>
                                </div>
                            <?php else: ?>
                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            <?php endif; ?>
                        </div>
                        <div class="msg-meta">
                            <?php echo date('g:i A', strtotime($msg['created_at'])); ?>
                            <?php if ($is_sent): ?><i class="ti ti-checks" style="font-size:11px;"></i><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="thread-input">
                <form method="POST" action="" class="thread-input-row">
                    <button type="button" class="icon-btn" title="Attach"><i class="ti ti-paperclip" style="font-size:16px"></i></button>
                    <input type="text" name="message" class="thread-input-field" placeholder="Type a message…" autocomplete="off">
                    <button type="button" class="icon-btn" title="Emoji"><i class="ti ti-mood-smile" style="font-size:16px"></i></button>
                    <button type="submit" name="send_chat_message" class="send-btn">
                        <i class="ti ti-send" style="font-size:15px;"></i>
                    </button>
                </form>
            </div>

        <?php else: ?>
            <div class="empty-thread">
                <i class="ti ti-message-dots" style="font-size:42px;color:var(--text-4)"></i>
                <p>Select a conversation to start messaging</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── RIGHT: Contact details ── -->
    <?php if ($current_partner): ?>
    <div class="msg-detail-pane">
        <div class="detail-section-title">Contact details</div>

        <div class="detail-contact-header">
            <div class="conv-avatar" style="background:<?php echo avatar_color($current_partner['id']); ?>;width:46px;height:46px;font-size:15px;">
                <?php echo initials($current_partner['full_name']); ?>
            </div>
            <div>
                <div class="detail-contact-name"><?php echo htmlspecialchars($current_partner['full_name']); ?></div>
                <div class="detail-contact-id"><?php echo str_pad($current_partner['id'], 11, '1', STR_PAD_LEFT); ?></div>
            </div>
            <button class="detail-more-btn"><i class="ti ti-dots-vertical" style="font-size:14px"></i></button>
        </div>

        <div class="detail-field">
            <div>
                <div class="detail-field-label">Main</div>
                <div class="detail-field-value">(484) 250-2031</div>
            </div>
            <i class="ti ti-phone detail-field-action" style="font-size:18px"></i>
        </div>
        <div class="detail-field">
            <div>
                <div class="detail-field-label">Email</div>
                <div class="detail-field-value">info@info.com</div>
            </div>
        </div>
        <div class="detail-field">
            <div>
                <div class="detail-field-label">Address</div>
                <div class="detail-field-value">789 Willow Creek Drive, Unit 2B,<br>Phoenixville, AZ</div>
            </div>
        </div>
        <div class="detail-show-more">Show more</div>

        <div class="detail-divider"></div>

        <div class="call-history-header">
            <div class="detail-section-title" style="margin:0;">Call history <span style="color:var(--blue);font-size:13px;">3</span></div>
            <span style="font-size:12px;color:var(--blue);cursor:pointer;font-weight:600;">Filters</span>
        </div>

        <?php
        $calls = [
            ['highlighted' => true,  'viewed' => false],
            ['highlighted' => false, 'viewed' => false],
            ['highlighted' => false, 'viewed' => true],
            ['highlighted' => false, 'viewed' => false],
        ];
        foreach ($calls as $call): ?>
        <div class="call-entry <?php echo $call['highlighted'] ? 'highlighted' : ''; ?>">
            <div class="call-entry-top">
                <span class="call-name"><?php echo htmlspecialchars($current_partner['full_name']); ?></span>
                <span class="call-date">30/1/2026  05:03 PM</span>
            </div>
            <div class="call-row">
                <div class="call-number"><i class="ti ti-arrow-left" style="font-size:10px"></i> +17244157196</div>
                <span class="call-tag">Default</span>
            </div>
            <?php if ($call['highlighted']): ?>
                <div class="call-link">Open call details</div>
            <?php endif; ?>
            <?php if ($call['viewed']): ?>
                <div class="call-viewed"><i class="fas fa-circle" style="font-size:7px;"></i> Viewed by SAS</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div><!-- /.app-body -->
</div><!-- /.main-wrap -->

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

// Auto-scroll thread
(function() {
    const el = document.getElementById('threadMessages');
    if (el) el.scrollTop = el.scrollHeight;
})();

// Live search filter
document.getElementById('convSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.msg-conv-item').forEach(item => {
        const name = item.querySelector('.conv-name')?.textContent.toLowerCase() || '';
        item.style.display = name.includes(q) ? '' : 'none';
    });
});

// Auto-refresh every 10s
setInterval(() => {
    if (document.visibilityState === 'visible' && <?php echo $chat_partner_id ? 'true' : 'false'; ?>) {
        // fetch new messages via AJAX here
    }
}, 10000);
</script>
</body>
</html>
