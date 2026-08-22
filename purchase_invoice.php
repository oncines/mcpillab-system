<?php
require_once 'config.php';
require_roles(['admin', 'manager', 'store']);

$unread_messages = get_unread_message_count($_SESSION['user_id']);

// ── Sample invoice data ──
$admin_invoices = [
    ['id'=>1,'invoice_number'=>'INV-2026-5135','po_number'=>'PO-2026-9501','supplier_name'=>'MediSupply Co.','invoice_date'=>'2026-05-14','due_date'=>'2026-05-28','total_amount'=>515.02,'status'=>'paid'],
    ['id'=>2,'invoice_number'=>'INV-2026-9059','po_number'=>'371892371','supplier_name'=>'BioTech Solutions','invoice_date'=>'2026-05-03','due_date'=>'2026-05-17','total_amount'=>100.72,'status'=>'unpaid'],
    ['id'=>3,'invoice_number'=>'INV-2026-8741','po_number'=>'371892371','supplier_name'=>'BioTech Solutions','invoice_date'=>'2026-05-03','due_date'=>'2026-05-17','total_amount'=>4222.40,'status'=>'unpaid'],
    ['id'=>4,'invoice_number'=>'INV-2026-3302','po_number'=>'PO-2026-1204','supplier_name'=>'PharmaCorp Inc.','invoice_date'=>'2026-04-28','due_date'=>'2026-05-12','total_amount'=>8750.00,'status'=>'approved'],
    ['id'=>5,'invoice_number'=>'INV-2026-1190','po_number'=>'PO-2026-0887','supplier_name'=>'LabGear Direct','invoice_date'=>'2026-04-15','due_date'=>'2026-04-29','total_amount'=>1233.60,'status'=>'pending'],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>McPIL – Purchase Invoice</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
:root {
  --navy:       #0a1045;
  --navy-2:     #0f1860;
  --navy-3:     #1a2580;
  --navy-light: #e8ebf8;
  --navy-pale:  #f2f4fc;
  --red:        #d4241a;
  --red-2:      #b01d14;
  --red-tint:   #fdecea;
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
  --purple:     #5b3fc4;
  --purple-bg:  #eeebfa;

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
  --radius-xs: 6px;
  --font:     'Sora', sans-serif;
  --mono:     'DM Mono', monospace;
  --shadow-xs: 0 1px 2px rgba(10,16,69,0.05);
  --shadow-sm: 0 2px 8px rgba(10,16,69,0.07),0 1px 3px rgba(10,16,69,0.04);
  --shadow:    0 6px 24px rgba(10,16,69,0.09),0 2px 8px rgba(10,16,69,0.04);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font);background:var(--bg);color:var(--text-1);font-size:13px;line-height:1.55;-webkit-font-smoothing:antialiased}

@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulse-dot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.5)}}
.fade-up  {animation:fadeUp .45s cubic-bezier(.22,1,.36,1) both}
.fade-up-1{animation-delay:.04s}.fade-up-2{animation-delay:.09s}.fade-up-3{animation-delay:.14s}

/* ── SIDEBAR ── */
.sidebar{position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:var(--sb-bg);display:flex;flex-direction:column;z-index:9999;overflow-y:auto;overflow-x:hidden;scrollbar-width:none}
.sidebar::-webkit-scrollbar{display:none}
.sb-logo{display:flex;align-items:center;gap:11px;padding:18px 16px 16px;border-bottom:1px solid var(--sb-border);flex-shrink:0}
.sb-logo-ring{width:40px;height:40px;border-radius:50%;border:2px solid rgba(255,255,255,0.28);overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#1a2a5e}
.sb-logo-ring img{width:100%;height:100%;object-fit:cover;display:block}
.sb-brand-name{font-size:13px;font-weight:800;color:#fff;letter-spacing:.06em;text-transform:uppercase;line-height:1.15}
.sb-brand-sub{font-size:8.5px;color:rgba(255,255,255,0.38);letter-spacing:.10em;text-transform:uppercase;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px}
.sb-nav{flex:1;padding:6px 10px 4px}
.sb-section{font-size:9.5px;font-weight:700;letter-spacing:.13em;text-transform:uppercase;color:var(--sb-label);padding:14px 8px 5px}
.sb-item{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;color:var(--sb-text);text-decoration:none;font-size:13px;font-weight:500;transition:background .13s,color .13s;margin-bottom:2px;line-height:1.2;cursor:pointer;position:relative}
.sb-item:hover{background:var(--sb-hover-bg);color:var(--sb-text-act);text-decoration:none}
.sb-item.active{background:var(--sb-active-bg);color:var(--sb-text-act);font-weight:600}
.sb-item i{font-size:18px;flex-shrink:0;line-height:1;width:22px;text-align:center}
.sb-item .sb-badge{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:#e5534b;color:#fff;font-size:9px;font-weight:700;min-width:17px;height:17px;border-radius:50%;display:flex;align-items:center;justify-content:center;padding:0 3px}
.sb-item.sb-logout{color:rgba(239,68,68,0.75)}
.sb-item.sb-logout i{color:rgba(239,68,68,0.85)}
.sb-item.sb-logout:hover{background:rgba(239,68,68,0.10);color:#ef4444}
.sb-item.sb-logout:hover i{color:#ef4444}
.sb-footer{flex-shrink:0;padding:4px 10px 18px;border-top:1px solid var(--sb-border)}

.mobile-sb-toggle{display:none;align-items:center;justify-content:center;width:36px;height:36px;border:none;border-radius:var(--radius-sm);background:var(--surface);color:var(--text-2);cursor:pointer;border:1px solid var(--border);flex:0 0 auto}
.mobile-sb-backdrop{display:none}
@media(max-width:991.98px){
  .sidebar{transform:translateX(-100%);transition:transform .3s ease;box-shadow:0 12px 28px rgba(0,0,0,.25)}
  body.sb-open .sidebar{transform:translateX(0)}
  .main-wrap{margin-left:0!important}
  .mobile-sb-toggle{display:inline-flex}
  .mobile-sb-backdrop{display:block;position:fixed;inset:0;background:rgba(9,15,85,.45);opacity:0;pointer-events:none;transition:opacity .3s ease;z-index:9998}
  body.sb-open .mobile-sb-backdrop{opacity:1;pointer-events:auto}
}

/* ── LAYOUT ── */
.main-wrap{margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column}

/* ── TOPBAR ── */
.topbar{height:var(--topbar-h);background:var(--white);border-bottom:1px solid var(--border);padding:0 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:var(--shadow-xs)}
.topbar-left{display:flex;align-items:center;gap:14px}
.topbar-breadcrumb{display:flex;flex-direction:column;gap:1px}
.topbar-title{font-size:16px;font-weight:700;color:var(--text-1);letter-spacing:-.025em;line-height:1.2}
.topbar-sub{font-size:11px;color:var(--text-4);font-weight:400;letter-spacing:.02em}
.topbar-divider{width:1px;height:28px;background:var(--border);margin:0 4px}
.topbar-right{display:flex;align-items:center;gap:10px}
.tb-icon-btn{width:36px;height:36px;border-radius:var(--radius-sm);background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-3);cursor:pointer;font-size:13px;transition:all .15s;position:relative}
.tb-icon-btn:hover{background:var(--surface-2);color:var(--text-1);border-color:var(--border-2)}
.tb-notif-dot{position:absolute;top:8px;right:8px;width:6px;height:6px;border-radius:50%;background:var(--red);border:1.5px solid var(--white);animation:pulse-dot 2.5s infinite}
.user-chip{display:flex;align-items:center;gap:9px;padding:4px 12px 4px 4px;border:1px solid var(--border);border-radius:40px;cursor:pointer;background:var(--white);transition:all .15s}
.user-chip:hover{border-color:var(--border-2);box-shadow:var(--shadow-xs)}
.u-avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--navy-3),var(--navy));color:var(--white);font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center}
.u-name{font-size:12px;font-weight:700;color:var(--text-1);line-height:1.2}
.u-role{font-size:10px;color:var(--text-4);font-weight:400}
.btn-topbar{display:inline-flex;align-items:center;gap:7px;background:var(--navy);color:var(--white);border:none;border-radius:var(--radius-sm);padding:9px 18px;font-size:12.5px;font-weight:700;cursor:pointer;font-family:var(--font);letter-spacing:.01em;transition:all .15s}
.btn-topbar:hover{background:var(--navy-2);transform:translateY(-1px);box-shadow:0 6px 20px rgba(10,16,69,.25)}
.btn-topbar-outline{background:var(--surface);color:var(--text-2);border:1px solid var(--border);box-shadow:none}
.btn-topbar-outline:hover{background:var(--surface-2);transform:none;box-shadow:none}

/* ── PAGE BODY ── */
.page-body{padding:28px 32px;flex:1}
.page-header{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:24px}
.page-eyebrow{font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--blue);margin-bottom:4px}
.page-heading{font-size:22px;font-weight:800;color:var(--text-1);letter-spacing:-.03em;line-height:1}
.page-sub{font-size:12px;color:var(--text-3);margin-top:5px}

/* ── KPI CARDS ── */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.kpi-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;position:relative;overflow:hidden;transition:all .22s ease}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:var(--radius) var(--radius) 0 0}
.kpi-card.k-blue::before{background:var(--blue)}
.kpi-card.k-green::before{background:var(--green)}
.kpi-card.k-amber::before{background:#d97706}
.kpi-card.k-red::before{background:var(--red)}
.kpi-card:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.kpi-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:12px}
.kpi-icon.ic-blue{background:var(--blue-bg);color:var(--blue)}
.kpi-icon.ic-green{background:var(--green-bg);color:var(--green)}
.kpi-icon.ic-amber{background:var(--amber-bg);color:var(--amber)}
.kpi-icon.ic-red{background:var(--red-tint);color:var(--red)}
.kpi-val{font-size:26px;font-weight:800;color:var(--text-1);letter-spacing:-.03em;line-height:1;font-variant-numeric:tabular-nums}
.kpi-label{font-size:11px;color:var(--text-3);text-transform:uppercase;letter-spacing:.08em;font-weight:700;margin-top:4px}
.kpi-sub{font-size:11px;color:var(--text-4);margin-top:2px}

/* ── TABLE CARD ── */
.tbl-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);overflow:hidden}
.tbl-toolbar{display:flex;align-items:center;gap:10px;padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface);flex-wrap:wrap;position:relative}
.tbl-toolbar-title{font-size:14px;font-weight:700;color:var(--text-1)}
.search-field{display:flex;align-items:center;gap:8px;background:var(--white);border:1px solid var(--border);border-radius:var(--radius-sm);padding:8px 13px;min-width:200px;transition:border-color .15s,box-shadow .15s}
.search-field:focus-within{border-color:var(--navy-3);box-shadow:0 0 0 3px rgba(26,37,128,.08)}
.search-field i{color:var(--text-4);font-size:11px}
.search-field input{border:none;background:transparent;font-size:12.5px;color:var(--text-1);outline:none;font-family:var(--font);width:150px}
.search-field input::placeholder{color:var(--text-4)}
.filter-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 13px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--white);color:var(--text-2);font-size:12.5px;cursor:pointer;font-family:var(--font);white-space:nowrap;transition:all .15s}
.filter-btn:hover{background:var(--surface-2);border-color:var(--border-2)}
.ml-auto{margin-left:auto}
.icon-btn{width:35px;height:35px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--text-3);cursor:pointer;font-size:12px;transition:all .15s}
.icon-btn:hover{background:var(--surface-2);color:var(--text-1);border-color:var(--border-2)}
.btn-approve-all{display:inline-flex;align-items:center;gap:7px;background:#0d7a48;color:#fff;border:none;border-radius:var(--radius-sm);padding:9px 16px;font-size:12.5px;font-weight:700;cursor:pointer;font-family:var(--font);transition:all .15s}
.btn-approve-all:hover{background:#0a5e37;transform:translateY(-1px);box-shadow:0 4px 14px rgba(13,122,72,.25)}

/* TABLE */
.tbl-scroll{overflow-x:auto}
table.mtbl{width:100%;border-collapse:collapse;min-width:820px}
table.mtbl thead{background:var(--surface);border-bottom:2px solid var(--border)}
table.mtbl thead th{padding:11px 16px;font-size:10.5px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.1em;text-align:left;white-space:nowrap}
table.mtbl thead th:first-child{padding-left:24px}
table.mtbl tbody tr{border-bottom:1px solid var(--border);transition:background .12s}
table.mtbl tbody tr:last-child{border-bottom:none}
table.mtbl tbody tr:hover td{background:var(--navy-pale);cursor:pointer}
table.mtbl td{padding:14px 16px;vertical-align:middle;font-size:12.5px;color:var(--text-1)}
table.mtbl td:first-child{padding-left:24px}

/* Invoice number cell */
.inv-num{font-weight:700;color:var(--navy);font-family:var(--mono);font-size:12px}
.po-num{font-size:11px;color:var(--text-3);font-family:var(--mono);margin-top:2px}
.supplier-name{font-weight:600;color:var(--text-1)}
.supplier-date{font-size:11px;color:var(--text-4);margin-top:2px}
.amount-val{font-weight:700;color:var(--text-1);font-family:var(--mono)}

/* Status badges */
.inv-status{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.inv-status::before{content:'';width:6px;height:6px;border-radius:50%;flex-shrink:0}
.s-paid     {background:var(--green-bg);color:var(--green)}
.s-paid::before{background:var(--green)}
.s-unpaid   {background:var(--amber-bg);color:var(--amber)}
.s-unpaid::before{background:#d97706}
.s-pending  {background:var(--blue-bg);color:var(--blue)}
.s-pending::before{background:var(--blue)}
.s-approved {background:var(--green-bg);color:var(--green)}
.s-approved::before{background:var(--green)}
.s-rejected {background:var(--red-tint);color:var(--red)}
.s-rejected::before{background:var(--red)}
.s-partially_paid{background:var(--purple-bg);color:var(--purple)}
.s-partially_paid::before{background:var(--purple)}

/* Action buttons */
.action-wrap{display:flex;align-items:center;gap:5px}
.act-btn{width:30px;height:30px;border-radius:6px;border:1px solid var(--border);background:var(--white);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;transition:all .13s;text-decoration:none}
.act-btn:hover{transform:translateY(-1px);box-shadow:0 3px 10px rgba(10,16,69,.1)}
.act-btn.v{color:var(--blue);border-color:#c5d6f7}
.act-btn.v:hover{background:var(--blue-bg)}
.act-btn.e{color:var(--amber);border-color:#f5d89c}
.act-btn.e:hover{background:var(--amber-bg)}
.act-btn.p{color:var(--text-3);border-color:var(--border)}
.act-btn.p:hover{background:var(--surface-2)}
.act-btn.ok{color:var(--green);border-color:#a7d9bc}
.act-btn.ok:hover{background:var(--green-bg)}
.act-btn.rej{color:var(--red);border-color:#f5b4b0}
.act-btn.rej:hover{background:var(--red-tint)}

/* Table footer */
.tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:14px 24px;border-top:1px solid var(--border);background:var(--surface)}
.tbl-count{font-size:12px;color:var(--text-3)}
.tbl-count strong{color:var(--text-1);font-weight:700}
.pg-btn{width:30px;height:30px;border-radius:6px;border:1px solid var(--border);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--text-3);cursor:pointer;font-size:10px;transition:all .15s}
.pg-btn:hover{background:var(--surface-2)}
.pg-active{background:var(--navy);color:#fff;border-color:var(--navy)}

/* Modals */
.modal-header{background:var(--navy);color:#fff;border-bottom:none;padding:18px 24px}
.modal-title{font-size:15px;font-weight:700;color:#fff;letter-spacing:-.01em}
.modal-header .btn-close{filter:invert(1);opacity:.7}
.modal-body{padding:24px}
.modal-footer{border-top:1px solid var(--border);padding:14px 24px}
.form-label{font-size:12px;font-weight:600;color:var(--text-2);margin-bottom:5px}
.form-control,.form-select{font-size:13px;border:1px solid var(--border);border-radius:var(--radius-sm);padding:9px 12px;color:var(--text-1);background:var(--surface);font-family:var(--font)}
.form-control:focus,.form-select:focus{border-color:var(--navy-3);box-shadow:0 0 0 3px rgba(26,37,128,.08)}

/* Alerts */
.alert{border-radius:var(--radius-sm);font-size:12.5px;margin-bottom:18px;border:none;font-weight:500}
.alert-success{background:var(--green-bg);color:var(--green)}
.alert-danger{background:var(--red-tint);color:var(--red)}

/* Preset date buttons */
.preset-btn{font-size:11px;padding:4px 10px;border-radius:5px;border:1px solid var(--border);background:var(--surface);color:var(--text-2);cursor:pointer;font-family:var(--font);transition:all .13s}
.preset-btn:hover,.preset-btn.active{background:var(--navy);color:#fff;border-color:var(--navy)}

/* ── PRINT ── */
@media print {
  body * { visibility: hidden !important; }
  #printableInvoice,
  #printableInvoice * { visibility: visible !important; }
  #printableInvoice {
    position: fixed !important;
    top: 0 !important; left: 0 !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 32px 40px !important;
    box-shadow: none !important;
    background: #fff !important;
    font-family: 'Sora', sans-serif !important;
  }
  @page { margin: 0; size: A4; }
}

  .kpi-grid{grid-template-columns:repeat(2,1fr)}
  .page-body{padding:20px 16px}
  .topbar{padding:0 16px}
}
@media(max-width:480px){
  .kpi-grid{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>

<!-- ═══════ SIDEBAR ═══════ -->
<nav id="appSidebar" class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-ring">
      <img src="logo.png" alt="McPIL"
           onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
      <span style="display:none;font-size:11px;font-weight:800;color:#fff">McP</span>
    </div>
    <div>
      <div class="sb-brand-name">McPIL</div>
      <div class="sb-brand-sub">Pharmaceutical Lab...</div>
    </div>
  </div>

  <div class="sb-nav">
    <div class="sb-section">Main</div>
    <a class="sb-item" href="<?php echo is_employee() ? 'employee_home.php' : 'dashboard.php'; ?>"><i class="ti ti-layout-dashboard"></i><?php echo is_employee() ? 'Home' : 'Dashboard'; ?></a>
    <a class="sb-item" href="purchase_order.php"><i class="ti ti-shopping-cart"></i>Purchase Order</a>
    <a class="sb-item active" href="purchase_invoice.php"><i class="ti ti-file-invoice"></i>Purchase Invoice</a>
    <a class="sb-item" href="employee_profile.php"><i class="ti ti-users"></i>Employee Profile</a>
    <a class="sb-item" href="attendance.php"><i class="ti ti-calendar-check"></i>Attendance</a>

    <div class="sb-section">Logistics</div>
    <a class="sb-item" href="delivery_tracking.php"><i class="ti ti-truck-delivery"></i>Delivery Tracking</a>
    <a class="sb-item" href="delivery_history.php"><i class="ti ti-history"></i>Delivery History</a>

    <div class="sb-section">Tools</div>
    <a class="sb-item" href="reports.php"><i class="ti ti-chart-bar"></i>Reports</a>
    <a class="sb-item" href="chat_interface.php">
      <i class="ti ti-message-2"></i>Chat
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
        <div class="topbar-title">Purchase Invoice</div>
        <div class="topbar-sub">Finance · Invoice approval & management</div>
      </div>
    </div>
    <div class="topbar-right">
      <div class="tb-icon-btn"><i class="fas fa-magnifying-glass"></i></div>
      <div class="tb-icon-btn">
        <i class="fas fa-bell"></i>
        <span class="tb-notif-dot"></span>
      </div>
      <div class="user-chip">
        <div class="u-avatar">AD</div>
        <div>
          <div class="u-name">Admin</div>
          <div class="u-role">Administrator</div>
        </div>
      </div>
      <form method="POST" style="margin:0" onsubmit="return confirm('Approve all pending invoices?')">
        <input type="hidden" name="approve_all_invoices" value="1">
        <button type="submit" class="btn-approve-all">
          <i class="ti ti-check" style="font-size:15px"></i>
          Approve All Pending
        </button>
      </form>
    </div>
  </header>

  <div class="page-body">

    <!-- Alerts -->
    <?php if (!empty($success_message)): ?>
      <div class="alert alert-success fade-up"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
      <div class="alert alert-danger fade-up"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header fade-up">
      <div>
        <div class="page-eyebrow">Finance</div>
        <div class="page-heading">
          Purchase Invoice Approval
          <span style="font-size:16px;font-weight:400;color:var(--text-4);margin-left:8px"><?php echo count($admin_invoices); ?> invoices</span>
        </div>
        <div class="page-sub">Review, approve, or reject purchase invoices submitted by store staff</div>
      </div>
      <div style="font-size:11px;color:var(--text-4);font-family:var(--mono)">
        <?php echo date('l, d F Y'); ?>
      </div>
    </div>

    <!-- Table Card -->
    <div class="tbl-card fade-up fade-up-2">
      <div class="tbl-toolbar">
        <div class="tbl-toolbar-title">All Invoices</div>
        <div class="search-field">
          <i class="fas fa-magnifying-glass"></i>
          <input type="text" id="searchInput" placeholder="Search invoice, supplier…" oninput="filterTable()">
        </div>
        <!-- Date Range -->
        <div style="display:flex;align-items:center;gap:6px;background:var(--white);border:1px solid var(--border);border-radius:var(--radius-sm);padding:7px 12px;font-size:12.5px;color:var(--text-2);cursor:pointer" id="dateRangeBtn" onclick="toggleDatePicker()">
          <i class="ti ti-calendar" style="font-size:14px;color:var(--text-3)"></i>
          <span id="dateRangeLabel">Date Range</span>
          <i class="fas fa-chevron-down" style="font-size:9px;color:var(--text-4);margin-left:2px" id="dateChevron"></i>
        </div>
        <!-- Date picker dropdown -->
        <div id="datePickerDrop" style="display:none;position:absolute;top:100%;left:0;z-index:200;background:var(--white);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);padding:16px 18px;min-width:300px;margin-top:4px">
          <div style="font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.1em;margin-bottom:12px">Filter by Date Issued</div>
          <div style="display:flex;gap:10px;align-items:center;margin-bottom:14px">
            <div style="flex:1">
              <label style="font-size:11px;color:var(--text-3);font-weight:600;display:block;margin-bottom:4px">From</label>
              <input type="date" id="dateFrom" class="form-control" style="font-size:12px;padding:7px 10px" oninput="filterTable()">
            </div>
            <div style="color:var(--text-4);margin-top:16px">—</div>
            <div style="flex:1">
              <label style="font-size:11px;color:var(--text-3);font-weight:600;display:block;margin-bottom:4px">To</label>
              <input type="date" id="dateTo" class="form-control" style="font-size:12px;padding:7px 10px" oninput="filterTable()">
            </div>
          </div>
          <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px">
            <button onclick="setPreset('today')"      class="preset-btn">Today</button>
            <button onclick="setPreset('week')"       class="preset-btn">This Week</button>
            <button onclick="setPreset('month')"      class="preset-btn">This Month</button>
            <button onclick="setPreset('last30')"     class="preset-btn">Last 30 Days</button>
            <button onclick="setPreset('quarter')"    class="preset-btn">This Quarter</button>
          </div>
          <div style="display:flex;justify-content:space-between;gap:8px;border-top:1px solid var(--border);padding-top:12px">
            <button onclick="clearDateFilter()" style="font-size:12px;color:var(--text-3);background:none;border:none;cursor:pointer;font-family:var(--font);padding:0">Clear</button>
            <button onclick="toggleDatePicker()" style="background:var(--navy);color:#fff;border:none;border-radius:var(--radius-sm);padding:7px 18px;font-size:12px;font-weight:700;cursor:pointer;font-family:var(--font)">Apply</button>
          </div>
        </div>
        <div class="ml-auto" style="display:flex;gap:6px">
          <div class="icon-btn" title="Export CSV"><i class="fas fa-download"></i></div>
          <div class="icon-btn" title="Refresh" onclick="clearAllFilters()"><i class="fas fa-rotate-right"></i></div>
        </div>
      </div>

      <div class="tbl-scroll">
        <table class="mtbl" id="invoiceTable">
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Supplier</th>
              <th>Date Issued</th>
              <th>Due Date</th>
              <th>Total Amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($admin_invoices as $inv):
              $sc = strtolower(str_replace([' ','-'], '_', $inv['status']));
              $due = strtotime($inv['due_date']) < time() && !in_array($inv['status'], ['paid','approved']) ? 'style="color:var(--red)"' : '';
            ?>
            <tr data-date="<?php echo htmlspecialchars($inv['invoice_date']); ?>">
              <td>
                <div class="po-num"><?php echo htmlspecialchars($inv['po_number'] ?? '—'); ?></div>
              </td>
              <td>
                <div class="supplier-name"><?php echo htmlspecialchars($inv['supplier_name'] ?? '—'); ?></div>
              </td>
              <td><span style="font-family:var(--mono);font-size:12px"><?php echo format_date($inv['invoice_date']); ?></span></td>
              <td><span <?php echo $due; ?> style="font-family:var(--mono);font-size:12px"><?php echo format_date($inv['due_date']); ?></span></td>
              <td><span class="amount-val"><?php echo format_currency($inv['total_amount']); ?></span></td>
              <td>
                <span class="inv-status s-<?php echo $sc; ?>">
                  <?php echo ucwords(str_replace('_',' ',$inv['status'])); ?>
                </span>
              </td>
              <td>
                <div class="action-wrap">
                  <a class="act-btn v" href="invoice_view.php?id=<?php echo $inv['id']; ?>" title="View"><i class="fas fa-eye"></i></a>
                  <button class="act-btn e" title="Edit" onclick="openEditModal(<?php echo (int)$inv['id']; ?>,'<?php echo htmlspecialchars($inv['invoice_number'],ENT_QUOTES); ?>','<?php echo $inv['invoice_date']; ?>','<?php echo $inv['due_date']; ?>','<?php echo $inv['status']; ?>')"><i class="fas fa-pen"></i></button>
                  <button class="act-btn p" title="Print" onclick="openPrintPreview(<?php echo htmlspecialchars(json_encode($inv), ENT_QUOTES); ?>)"><i class="fas fa-print"></i></button>
                  <?php if (!in_array($inv['status'], ['approved','rejected'])): ?>
                  <form method="POST" style="display:inline;margin:0">
                    <input type="hidden" name="update_invoice_status" value="1">
                    <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                    <input type="hidden" name="new_status" value="approved">
                    <button type="submit" class="act-btn ok" title="Approve"><i class="fas fa-check"></i></button>
                  </form>
                  <form method="POST" style="display:inline;margin:0">
                    <input type="hidden" name="update_invoice_status" value="1">
                    <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                    <input type="hidden" name="new_status" value="rejected">
                    <button type="submit" class="act-btn rej" title="Reject"><i class="fas fa-times"></i></button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="tbl-footer">
        <div class="tbl-count">
          Showing <strong id="visibleCount"><?php echo count($admin_invoices); ?></strong> of <strong><?php echo count($admin_invoices); ?></strong> invoices
        </div>
        <div style="display:flex;gap:6px;align-items:center">
          <button class="pg-btn" disabled><i class="fas fa-chevron-left" style="font-size:9px"></i></button>
          <button class="pg-btn pg-active">1</button>
          <button class="pg-btn"><i class="fas fa-chevron-right" style="font-size:9px"></i></button>
        </div>
      </div>
    </div>

  </div><!-- /page-body -->
</div><!-- /main-wrap -->

<!-- ═══════ PRINT PREVIEW MODAL ═══════ -->
<div class="modal fade" id="printModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:720px">
    <div class="modal-content" style="border:none;border-radius:var(--radius);overflow:hidden">
      <div class="modal-header" style="padding:14px 20px;background:var(--surface);border-bottom:1px solid var(--border)">
        <h5 class="modal-title" style="color:var(--text-1);font-size:14px;font-weight:700"><i class="fas fa-print me-2" style="color:var(--text-3)"></i>Print Preview</h5>
        <div style="display:flex;gap:8px;align-items:center">
          <button onclick="triggerPrint()" style="background:var(--navy);color:#fff;border:none;border-radius:var(--radius-sm);padding:8px 18px;font-family:var(--font);font-size:12.5px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px">
            <i class="fas fa-print"></i> Print
          </button>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <div class="modal-body" style="padding:0;background:#d0d0d0;max-height:80vh;overflow-y:auto">
        <!-- The printable invoice document -->
        <div id="printableInvoice" style="background:#fff;margin:24px auto;width:640px;padding:48px 52px;box-shadow:0 4px 24px rgba(0,0,0,.15);font-family:'Sora',sans-serif;color:#0d1030;position:relative;overflow:hidden">

          <!-- Header -->
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:36px">
            <div>
              <div style="font-size:28px;font-weight:800;color:#0a1045;letter-spacing:-.03em">McPIL</div>
              <div style="font-size:11px;color:#7b809e;text-transform:uppercase;letter-spacing:.12em;margin-top:2px">Pharmaceutical Laboratory</div>
              <div style="font-size:11.5px;color:#3a4066;margin-top:10px;line-height:1.6">
                123 Pharma Street, Davao City<br>
                Philippines 8000<br>
                +63 82 000 0000 · admin@mcpil.com
              </div>
            </div>
            <div style="text-align:right">
              <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#7b809e;margin-bottom:4px">Invoice</div>
              <div id="pi-number" style="font-size:20px;font-weight:800;color:#0a1045;font-family:'DM Mono',monospace"></div>
              <div style="margin-top:10px;line-height:1.8">
                <div style="font-size:11px;color:#7b809e">Date Issued: <strong id="pi-date" style="color:#0d1030"></strong></div>
                <div style="font-size:11px;color:#7b809e">Due Date: <strong id="pi-due" style="color:#0d1030"></strong></div>
                <div style="font-size:11px;color:#7b809e;margin-top:4px">Status: <span id="pi-status-badge" style="display:inline-block;padding:2px 10px;border-radius:5px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em"></span></div>
              </div>
            </div>
          </div>

          <!-- Divider -->
          <div style="height:2px;background:#0a1045;margin-bottom:28px"></div>

          <!-- Bill To / From -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;margin-bottom:32px">
            <div>
              <div style="font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#7b809e;margin-bottom:8px">Bill From (Seller)</div>
              <div id="pi-seller" style="font-size:12px;color:#3a4066;line-height:1.7"></div>
            </div>
            <div>
              <div style="font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#7b809e;margin-bottom:8px">Bill To (Buyer)</div>
              <div id="pi-buyer" style="font-size:12px;color:#3a4066;line-height:1.7"></div>
            </div>
          </div>

          <!-- PO Reference -->
          <div style="background:#f0f2f9;border-radius:8px;padding:12px 16px;margin-bottom:28px;display:flex;gap:32px">
            <div>
              <div style="font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#7b809e;margin-bottom:2px">Purchase Order</div>
              <div id="pi-po" style="font-size:13px;font-weight:700;color:#0a1045;font-family:'DM Mono',monospace"></div>
            </div>
            <div>
              <div style="font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#7b809e;margin-bottom:2px">Supplier</div>
              <div id="pi-supplier" style="font-size:13px;font-weight:700;color:#0a1045"></div>
            </div>
          </div>

          <!-- Items Table -->
          <table style="width:100%;border-collapse:collapse;margin-bottom:24px">
            <thead>
              <tr style="background:#0a1045">
                <th style="padding:10px 14px;font-size:10.5px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.08em;text-align:left;border-radius:6px 0 0 0">Description</th>
                <th style="padding:10px 14px;font-size:10.5px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.08em;text-align:center">Qty</th>
                <th style="padding:10px 14px;font-size:10.5px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.08em;text-align:right">Unit Price</th>
                <th style="padding:10px 14px;font-size:10.5px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.08em;text-align:right;border-radius:0 6px 0 0">Total</th>
              </tr>
            </thead>
            <tbody id="pi-items">
              <!-- injected by JS -->
            </tbody>
          </table>

          <!-- Totals -->
          <div style="display:flex;justify-content:flex-end;margin-bottom:36px">
            <div style="min-width:240px">
              <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #e4e7f2;font-size:12.5px">
                <span style="color:#7b809e">Subtotal</span>
                <span id="pi-subtotal" style="font-weight:600;font-family:'DM Mono',monospace"></span>
              </div>
              <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #e4e7f2;font-size:12.5px">
                <span style="color:#7b809e">VAT / Tax</span>
                <span id="pi-tax" style="font-weight:600;font-family:'DM Mono',monospace"></span>
              </div>
              <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #e4e7f2;font-size:12.5px">
                <span style="color:#7b809e">Discount</span>
                <span id="pi-discount" style="font-weight:600;font-family:'DM Mono',monospace"></span>
              </div>
              <div style="display:flex;justify-content:space-between;padding:10px 14px;background:#0a1045;border-radius:8px;margin-top:8px">
                <span style="font-size:13px;font-weight:700;color:#fff">TOTAL DUE</span>
                <span id="pi-total" style="font-size:15px;font-weight:800;color:#fff;font-family:'DM Mono',monospace"></span>
              </div>
            </div>
          </div>

          <!-- Footer / Notes -->
          <div style="border-top:1px solid #e4e7f2;padding-top:20px;display:flex;justify-content:space-between;align-items:flex-end">
            <div>
              <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#7b809e;margin-bottom:4px">Payment Notes</div>
              <div style="font-size:11.5px;color:#3a4066;line-height:1.6;max-width:300px">
                Please make payment by the due date. Late payments may incur additional charges. Thank you for your business.
              </div>
            </div>
            <div style="text-align:right">
              <div style="width:160px;border-top:1.5px solid #0a1045;padding-top:8px;margin-top:40px">
                <div style="font-size:10.5px;font-weight:700;color:#7b809e;text-transform:uppercase;letter-spacing:.1em">Authorized Signature</div>
              </div>
            </div>
          </div>

          <!-- Watermark for paid -->
          <div id="pi-watermark" style="display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);font-size:72px;font-weight:800;opacity:.06;color:#0d7a48;pointer-events:none;white-space:nowrap;letter-spacing:.1em">PAID</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Invoice Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content" style="border:none;border-radius:var(--radius);overflow:hidden">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-pen me-2" style="font-size:13px;opacity:.7"></i>Edit Purchase Invoice</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="edit_invoice" value="1">
        <input type="hidden" name="invoice_id" id="editId">
        <div class="mb-3">
          <label class="form-label">Invoice Number</label>
          <input type="text" class="form-control" name="invoice_number" id="editNumber" required>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label">Date Issued</label>
            <input type="date" class="form-control" name="invoice_date" id="editDate" required>
          </div>
          <div class="col-6">
            <label class="form-label">Due Date</label>
            <input type="date" class="form-control" name="due_date" id="editDue" required>
          </div>
        </div>
        <div class="mb-1">
          <label class="form-label">Status</label>
          <select class="form-select" name="status" id="editStatus" required>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="unpaid">Unpaid</option>
            <option value="paid">Paid</option>
            <option value="partially_paid">Partially Paid</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm" style="border:1px solid var(--border);color:var(--text-2);background:var(--surface);border-radius:var(--radius-sm);padding:8px 18px;font-family:var(--font);font-size:13px" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" style="background:var(--navy);color:#fff;border:none;border-radius:var(--radius-sm);padding:8px 20px;font-family:var(--font);font-size:13px;font-weight:700;cursor:pointer">
          <i class="fas fa-save me-1"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Sample notes data keyed by invoice id (replace with real DB data) ──
const invoiceNotes = {
  1: { seller:{company:'MediSupply Co.',email:'billing@medisupply.com',address:'12 Pharma Ave, Cebu City',phone:'+63 32 000 1111'}, buyer:{name:'McPIL Admin',company:'McPIL Pharmaceutical Laboratory',address:'123 Pharma Street, Davao City',email:'admin@mcpil.com'}, items:[{description:'Paracetamol 500mg x 100',qty:10,price:30.00,total:300.00},{description:'Ibuprofen 200mg x 50',qty:5,price:43.00,total:215.02}], tax_rate:12, discount:0 },
  2: { seller:{company:'BioTech Solutions',email:'orders@biotech.com',address:'55 Science Park, BGC, Taguig',phone:'+63 2 8000 2222'}, buyer:{name:'McPIL Store',company:'McPIL Pharmaceutical Laboratory',address:'123 Pharma Street, Davao City',email:'store@mcpil.com'}, items:[{description:'Lab Gloves (Box of 100)',qty:2,price:44.97,total:89.94}], tax_rate:12, discount:0 },
  3: { seller:{company:'BioTech Solutions',email:'orders@biotech.com',address:'55 Science Park, BGC, Taguig',phone:'+63 2 8000 2222'}, buyer:{name:'McPIL Store',company:'McPIL Pharmaceutical Laboratory',address:'123 Pharma Street, Davao City',email:'store@mcpil.com'}, items:[{description:'Petri Dishes x 50',qty:20,price:100.00,total:2000.00},{description:'Microscope Slides x 100',qty:15,price:80.00,total:1200.00},{description:'Culture Media 500ml',qty:8,price:127.80,total:1022.40}], tax_rate:12, discount:0 },
  4: { seller:{company:'PharmaCorp Inc.',email:'sales@pharmacorp.com',address:'88 Industrial Blvd, Quezon City',phone:'+63 2 8000 3333'}, buyer:{name:'McPIL Admin',company:'McPIL Pharmaceutical Laboratory',address:'123 Pharma Street, Davao City',email:'admin@mcpil.com'}, items:[{description:'Amoxicillin 500mg x 200',qty:50,price:105.00,total:5250.00},{description:'Vitamin C 1000mg x 100',qty:30,price:100.00,total:3000.00},{description:'Shipping & Handling',qty:1,price:500.00,total:500.00}], tax_rate:12, discount:0 },
  5: { seller:{company:'LabGear Direct',email:'info@labgear.com',address:'29 Equipment St, Pasig City',phone:'+63 2 8000 4444'}, buyer:{name:'McPIL Store',company:'McPIL Pharmaceutical Laboratory',address:'123 Pharma Street, Davao City',email:'store@mcpil.com'}, items:[{description:'Safety Goggles',qty:10,price:65.50,total:655.00},{description:'Lab Coat (Large) x 5',qty:5,price:115.72,total:578.60}], tax_rate:12, discount:0 },
};

const statusColors = {
  paid:           {bg:'#e6f4ee',color:'#0d7a48'},
  unpaid:         {bg:'#fff4de',color:'#875200'},
  pending:        {bg:'#e8eeff',color:'#1645b6'},
  approved:       {bg:'#e6f4ee',color:'#0d7a48'},
  rejected:       {bg:'#fdecea',color:'#d4241a'},
  partially_paid: {bg:'#eeebfa',color:'#5b3fc4'},
};

function fmt(n) { return '₱'+Number(n).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}); }

function openPrintPreview(inv) {
  const notes = invoiceNotes[inv.id] || {};
  const seller = notes.seller || {};
  const buyer  = notes.buyer  || {};
  const items  = notes.items  || [{description:'(No items recorded)',qty:1,price:inv.total_amount,total:inv.total_amount}];
  const taxRate   = notes.tax_rate || 0;
  const discount  = notes.discount || 0;
  const subtotal  = items.reduce((s,i)=>s+Number(i.total),0);
  const taxAmt    = subtotal * (taxRate/100);
  const total     = subtotal + taxAmt - discount;

  document.getElementById('pi-number').textContent = inv.invoice_number;
  document.getElementById('pi-date').textContent   = new Date(inv.invoice_date).toLocaleDateString('en-PH',{year:'numeric',month:'long',day:'numeric'});
  document.getElementById('pi-due').textContent    = new Date(inv.due_date).toLocaleDateString('en-PH',{year:'numeric',month:'long',day:'numeric'});
  document.getElementById('pi-po').textContent       = inv.po_number || '—';
  document.getElementById('pi-supplier').textContent = inv.supplier_name || '—';
  document.getElementById('pi-subtotal').textContent = fmt(subtotal);
  document.getElementById('pi-tax').textContent      = fmt(taxAmt)+` (${taxRate}%)`;
  document.getElementById('pi-discount').textContent = discount ? '-'+fmt(discount) : '—';
  document.getElementById('pi-total').textContent    = fmt(total);

  const sc = inv.status.toLowerCase().replace(/ /g,'_');
  const col = statusColors[sc] || {bg:'#f0f2f9',color:'#3a4066'};
  const badge = document.getElementById('pi-status-badge');
  badge.textContent = inv.status.replace(/_/g,' ').toUpperCase();
  badge.style.background = col.bg;
  badge.style.color = col.color;

  document.getElementById('pi-seller').innerHTML =
    `<strong>${seller.company||'—'}</strong><br>${seller.email||''}<br>${seller.address||''}<br>${seller.phone||''}`;
  document.getElementById('pi-buyer').innerHTML =
    `<strong>${buyer.name||'—'}</strong>${buyer.company?'<br>'+buyer.company:''}<br>${buyer.email||''}<br>${buyer.address||''}`;

  let rowsHtml = '';
  items.forEach((item,i) => {
    const bg = i%2===0?'#fff':'#f7f8fd';
    rowsHtml += `<tr style="background:${bg}">
      <td style="padding:10px 14px;font-size:12.5px;border-bottom:1px solid #e4e7f2">${item.description}</td>
      <td style="padding:10px 14px;font-size:12.5px;text-align:center;border-bottom:1px solid #e4e7f2">${item.qty}</td>
      <td style="padding:10px 14px;font-size:12.5px;text-align:right;font-family:'DM Mono',monospace;border-bottom:1px solid #e4e7f2">${fmt(item.price)}</td>
      <td style="padding:10px 14px;font-size:12.5px;text-align:right;font-family:'DM Mono',monospace;font-weight:700;border-bottom:1px solid #e4e7f2">${fmt(item.total)}</td>
    </tr>`;
  });
  document.getElementById('pi-items').innerHTML = rowsHtml;

  const wm = document.getElementById('pi-watermark');
  wm.style.display = (sc === 'paid' || sc === 'approved') ? 'block' : 'none';
  wm.textContent   = sc === 'approved' ? 'APPROVED' : 'PAID';
  wm.style.color   = sc === 'approved' ? '#0a1045' : '#0d7a48';

  // Store inv ref for print trigger
  window._printInv = inv;
  new bootstrap.Modal(document.getElementById('printModal')).show();
}

function triggerPrint() {
  window.print();
}

function openEditModal(id, num, date, due, status) {
  document.getElementById('editId').value = id;
  document.getElementById('editNumber').value = num;
  document.getElementById('editDate').value = date;
  document.getElementById('editDue').value = due;
  document.getElementById('editStatus').value = status;
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

let datePickerOpen = false;

function toggleDatePicker() {
  datePickerOpen = !datePickerOpen;
  const drop = document.getElementById('datePickerDrop');
  const chevron = document.getElementById('dateChevron');
  drop.style.display = datePickerOpen ? 'block' : 'none';
  chevron.style.transform = datePickerOpen ? 'rotate(180deg)' : '';
}

// Close picker when clicking outside
document.addEventListener('click', function(e) {
  const btn  = document.getElementById('dateRangeBtn');
  const drop = document.getElementById('datePickerDrop');
  if (datePickerOpen && !btn.contains(e.target) && !drop.contains(e.target)) {
    datePickerOpen = false;
    drop.style.display = 'none';
    document.getElementById('dateChevron').style.transform = '';
  }
});

function setPreset(preset) {
  const now   = new Date();
  let from    = new Date();
  let to      = new Date();

  if (preset === 'today') {
    from = to = new Date();
  } else if (preset === 'week') {
    const day = now.getDay();
    from = new Date(now); from.setDate(now.getDate() - day);
    to   = new Date(from); to.setDate(from.getDate() + 6);
  } else if (preset === 'month') {
    from = new Date(now.getFullYear(), now.getMonth(), 1);
    to   = new Date(now.getFullYear(), now.getMonth()+1, 0);
  } else if (preset === 'last30') {
    from = new Date(now); from.setDate(now.getDate() - 29);
  } else if (preset === 'quarter') {
    const q = Math.floor(now.getMonth()/3);
    from = new Date(now.getFullYear(), q*3, 1);
    to   = new Date(now.getFullYear(), q*3+3, 0);
  }

  document.getElementById('dateFrom').value = toYMD(from);
  document.getElementById('dateTo').value   = toYMD(to);

  document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
  event.target.classList.add('active');
  updateDateLabel();
  filterTable();
}

function toYMD(d) {
  return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
}

function updateDateLabel() {
  const from = document.getElementById('dateFrom').value;
  const to   = document.getElementById('dateTo').value;
  const lbl  = document.getElementById('dateRangeLabel');
  if (from && to) {
    lbl.textContent = formatLblDate(from) + ' – ' + formatLblDate(to);
    lbl.style.color = 'var(--navy)';
    lbl.style.fontWeight = '600';
  } else if (from) {
    lbl.textContent = 'From ' + formatLblDate(from);
    lbl.style.color = 'var(--navy)';
    lbl.style.fontWeight = '600';
  } else {
    lbl.textContent = 'Date Range';
    lbl.style.color = '';
    lbl.style.fontWeight = '';
  }
}

function formatLblDate(ymd) {
  const d = new Date(ymd + 'T00:00:00');
  return d.toLocaleDateString('en-PH', {month:'short', day:'numeric', year:'numeric'});
}

function clearDateFilter() {
  document.getElementById('dateFrom').value = '';
  document.getElementById('dateTo').value   = '';
  document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
  updateDateLabel();
  filterTable();
}

function clearAllFilters() {
  document.getElementById('searchInput').value = '';
  clearDateFilter();
}

function filterTable() {
  updateDateLabel();
  const q    = document.getElementById('searchInput').value.toLowerCase();
  const from = document.getElementById('dateFrom').value;
  const to   = document.getElementById('dateTo').value;
  const fromDate = from ? new Date(from + 'T00:00:00') : null;
  const toDate   = to   ? new Date(to   + 'T23:59:59') : null;

  let visible = 0;
  document.querySelectorAll('#invoiceTable tbody tr').forEach(row => {
    // Search match
    const textMatch = !q || row.textContent.toLowerCase().includes(q);

    // Date match — read from data attribute on the row
    let dateMatch = true;
    if (fromDate || toDate) {
      const rawDate = row.dataset.date;
      if (rawDate) {
        const rowDate = new Date(rawDate + 'T00:00:00');
        if (fromDate && rowDate < fromDate) dateMatch = false;
        if (toDate   && rowDate > toDate)   dateMatch = false;
      }
    }

    const show = textMatch && dateMatch;
    row.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  // Update count
  const countEl = document.getElementById('visibleCount');
  if (countEl) countEl.textContent = visible;
}

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
</script>
</body>
</html>
