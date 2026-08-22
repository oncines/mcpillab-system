<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!is_admin() && !is_manager()) {
        $error_message = "You don't have permission to update delivery status.";
    } else {
        $delivery_id = $_POST['delivery_id'];
        $new_status  = $_POST['new_status'];
        if (set_delivery_status($delivery_id, $new_status)) {
            $success_message = "Delivery status updated successfully!";
        } else {
            $error_message = "Failed to update delivery status.";
        }
    }
}

$status_filter = $_GET['status'] ?? 'all';
$filter_date   = $_GET['filter_date'] ?? '';
$all_deliveries = get_deliveries(null, 100);

$filtered_deliveries = [];
foreach ($all_deliveries as $delivery) {
    if ($status_filter !== 'all' && $delivery['status'] !== $status_filter) continue;
    if ($filter_date && $delivery['delivery_date'] !== $filter_date) continue;
    $filtered_deliveries[] = $delivery;
}

function status_to_step($status) {
    return match($status) {
        'pending'    => 0, 'approved' => 1,
        'in_transit' => 3, 'delivered' => 4, default => 0,
    };
}
function status_pill($status) {
    return match($status) {
        'pending'    => ['label'=>'Pending',    'cls'=>'pill-pending'],
        'approved'   => ['label'=>'Approved',   'cls'=>'pill-approved'],
        'in_transit' => ['label'=>'In Transit', 'cls'=>'pill-transit'],
        'delivered'  => ['label'=>'Delivered',  'cls'=>'pill-delivered'],
        'cancelled'  => ['label'=>'Cancelled',  'cls'=>'pill-cancelled'],
        default      => ['label'=>ucfirst($status), 'cls'=>'pill-default'],
    };
}
function carrier_badge($carrier) {
    if (!$carrier) return '<span class="carrier c-regular">N/A</span>';
    $lower = strtolower($carrier);
    $cls = match(true) {
        str_contains($lower,'fedex')  => 'c-fedex',
        str_contains($lower,'dhl')    => 'c-dhl',
        str_contains($lower,'ups')    => 'c-ups',
        str_contains($lower,'tnt')    => 'c-tnt',
        str_contains($lower,'aramex') => 'c-aramex',
        str_contains($lower,'lbc')    => 'c-lbc',
        str_contains($lower,'j&t')    => 'c-jnt',
        default                        => 'c-regular',
    };
    return '<span class="carrier '.$cls.'">'.htmlspecialchars($carrier).'</span>';
}
function render_timeline($step, $total = 4) {
    $icons = ['fa-clipboard-list','fa-check','fa-box','fa-truck','fa-map-marker-alt'];
    $html  = '<div class="timeline">';
    for ($i = 0; $i <= $total; $i++) {
        $cls = $i < $step ? 'done' : ($i === $step ? 'active' : 'inactive');
        $html .= '<div class="t-step '.$cls.'"><i class="fas '.$icons[$i].'"></i></div>';
        if ($i < $total) $html .= '<div class="t-line'.($i < $step ? ' done' : '').'"></div>';
    }
    return $html.'</div>';
}

$js_deliveries = [];
foreach ($filtered_deliveries as $d) {
    $step = status_to_step($d['status']);
    $steps_arr = [];
    for ($i = 0; $i <= 4; $i++) $steps_arr[] = $i <= $step;
    $tl = [];
    if ($step >= 0) $tl[] = ['label'=>'Order Placed',      'date'=>$d['created_at']??'',   'note'=>'Shipment information received','done'=>true];
    if ($step >= 1) $tl[] = ['label'=>'Approved',          'date'=>$d['approved_at']??'',  'note'=>'Order approved',               'done'=>true];
    if ($step >= 2) $tl[] = ['label'=>'Preparing to Ship', 'date'=>'',                     'note'=>'Preparing shipment',           'done'=>$step>=2];
    if ($step >= 3) $tl[] = ['label'=>'In Transit',        'date'=>$d['shipped_at']??'',   'note'=>'Package picked up by carrier', 'done'=>true];
    if ($step >= 4) $tl[] = ['label'=>'Delivered',         'date'=>$d['delivery_date']??'','note'=>'Package delivered successfully','done'=>true];
    if ($step < 1) $tl[] = ['label'=>'Approved',   'date'=>'Pending','note'=>'','done'=>false];
    if ($step < 3) $tl[] = ['label'=>'In Transit', 'date'=>'Pending','note'=>'','done'=>false];
    if ($step < 4) $tl[] = ['label'=>'Delivered',  'date'=>'Pending','note'=>'','done'=>false];
    $js_deliveries[] = [
        'id'=>$d['id'],'del_number'=>$d['delivery_number']??'','po_number'=>$d['po_number']??'',
        'status'=>$d['status'],'status_label'=>status_pill($d['status'])['label'],
        'status_cls'=>status_pill($d['status'])['cls'],'carrier'=>$d['carrier']??'',
        'supplier'=>$d['supplier_name']??'','supplier_email'=>$d['supplier_email']??'',
        'from_address'=>$d['from_address']??'','to_address'=>$d['to_address']??'',
        'delivery_date'=>format_date($d['delivery_date']),'created_at'=>$d['created_at']??'',
        'total_time'=>$d['total_time']??'—','departure_time'=>$d['departure_time']??'—',
        'steps'=>$steps_arr,'timeline'=>$tl,
        'warning'=>($d['status']==='in_transit'&&!empty($d['delay']))?'High volume or carrier delays may affect delivery time.':'',
        'delay'=>!empty($d['delay']),
    ];
}

$counts = ['all'=>count($all_deliveries),'in_transit'=>0,'delivered'=>0,'pending'=>0,'cancelled'=>0];
foreach ($all_deliveries as $d) { if (isset($counts[$d['status']])) $counts[$d['status']]++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?php echo APP_NAME; ?> – Delivery Tracking</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
/* ══════════════════════════════════════════
   DESIGN TOKENS
══════════════════════════════════════════ */
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

  /* ── SIDEBAR TOKENS (match screenshot exactly) ── */
  --sidebar-w:    220px;
  --sb-bg:        #0d1b3e;
  --sb-active-bg: rgba(255,255,255,0.11);
  --sb-hover-bg:  rgba(255,255,255,0.06);
  --sb-label:     rgba(255,255,255,0.32);
  --sb-text:      rgba(255,255,255,0.70);
  --sb-text-act:  #ffffff;
  --sb-border:    rgba(255,255,255,0.07);
  --sb-highlight: #3b82f6; /* blue for Delivery Tracking */
  --sb-logout:    #ef4444;

  --topbar-h: 64px;
  --radius:   14px;
  --radius-sm: 9px;
  --radius-xs: 6px;
  --font:     'Sora', sans-serif;
  --mono:     'DM Mono', monospace;
  --shadow-xs: 0 1px 2px rgba(10,16,69,0.05);
  --shadow-sm: 0 2px 8px rgba(10,16,69,0.07), 0 1px 3px rgba(10,16,69,0.04);
  --shadow:    0 6px 24px rgba(10,16,69,0.09), 0 2px 8px rgba(10,16,69,0.04);
  --shadow-lg: 0 20px 60px rgba(10,16,69,0.15), 0 6px 20px rgba(10,16,69,0.07);
}

*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body {
  font-family: var(--font);
  background: var(--bg);
  color: var(--text-1);
  font-size: 13px;
  line-height: 1.55;
  -webkit-font-smoothing: antialiased;
}

@keyframes fadeUp {
  from { opacity:0; transform:translateY(14px); }
  to   { opacity:1; transform:translateY(0); }
}
@keyframes slideInRight {
  from { opacity:0; transform:translateX(-10px); }
  to   { opacity:1; transform:translateX(0); }
}
@keyframes pulse-dot {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:.5; transform:scale(1.5); }
}

.fade-up   { animation: fadeUp .45s cubic-bezier(.22,1,.36,1) both; }
.fade-up-1 { animation-delay:.04s; }
.fade-up-2 { animation-delay:.09s; }
.fade-up-3 { animation-delay:.14s; }
.fade-up-4 { animation-delay:.19s; }
.fade-up-5 { animation-delay:.24s; }

/* ══════════════════════════════════════════
   SIDEBAR — matches screenshot exactly
   • Dark navy #0d1b3e background
   • Logo: circle ring with McPIL image + text
   • Section labels: small uppercase muted
   • Nav items: icon (Tabler) + label, no box bg except active
   • Active item: slightly lighter bg, white text
   • Delivery Tracking: blue icon tint
   • Logout: red tint
══════════════════════════════════════════ */
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

/* Logo */
.sb-logo {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 18px 16px 16px;
  border-bottom: 1px solid var(--sb-border);
  flex-shrink: 0;
}
.sb-logo-ring {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.28);
  overflow: hidden;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #1a2a5e;
}
.sb-logo-ring img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.sb-logo-ring .sb-logo-fallback {
  font-size: 11px;
  font-weight: 800;
  color: #fff;
  letter-spacing: .03em;
}
.sb-brand-name {
  font-size: 13px;
  font-weight: 800;
  color: #fff;
  letter-spacing: .06em;
  text-transform: uppercase;
  line-height: 1.15;
}
.sb-brand-sub {
  font-size: 8.5px;
  color: rgba(255,255,255,0.38);
  letter-spacing: .10em;
  text-transform: uppercase;
  line-height: 1.3;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 130px;
}

/* Nav body */
.sb-nav { flex: 1; padding: 6px 10px 4px; }

/* Section label */
.sb-section {
  font-size: 9.5px;
  font-weight: 700;
  letter-spacing: .13em;
  text-transform: uppercase;
  color: var(--sb-label);
  padding: 14px 8px 5px;
}

/* Nav item */
.sb-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border-radius: 9px;
  color: var(--sb-text);
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  transition: background .13s, color .13s;
  margin-bottom: 2px;
  line-height: 1.2;
  cursor: pointer;
}
.sb-item:hover {
  background: var(--sb-hover-bg);
  color: var(--sb-text-act);
  text-decoration: none;
}
.sb-item.active {
  background: var(--sb-active-bg);
  color: var(--sb-text-act);
  font-weight: 600;
}
.sb-item i {
  font-size: 18px;
  flex-shrink: 0;
  line-height: 1;
  width: 22px;
  text-align: center;
}

/* Delivery Tracking gets blue icon */
.sb-item.sb-highlight { color: var(--sb-text); }
.sb-item.sb-highlight i { color: #60a5fa; }
.sb-item.sb-highlight:hover { color: var(--sb-text-act); }
.sb-item.active.sb-highlight { color: var(--sb-text-act); }
.sb-item.active.sb-highlight i { color: #93c5fd; }

/* Logout red */
.sb-item.sb-logout { color: rgba(239,68,68,0.75); }
.sb-item.sb-logout i { color: rgba(239,68,68,0.85); }
.sb-item.sb-logout:hover { background: rgba(239,68,68,0.10); color: #ef4444; }
.sb-item.sb-logout:hover i { color: #ef4444; }

/* Footer */
.sb-footer {
  flex-shrink: 0;
  padding: 4px 10px 18px;
  border-top: 1px solid var(--sb-border);
}

/* Mobile */
.mobile-sb-toggle {
  display: none;
  align-items: center;
  justify-content: center;
  width: 36px; height: 36px;
  border: none; border-radius: var(--radius-sm);
  background: var(--surface);
  color: var(--text-2);
  cursor: pointer;
  border: 1px solid var(--border);
  flex: 0 0 auto;
}
.mobile-sb-backdrop { display: none; }
@media (max-width: 991.98px) {
  .sidebar { transform: translateX(-100%); transition: transform .3s ease; box-shadow: 0 12px 28px rgba(0,0,0,.25); }
  body.sb-open .sidebar { transform: translateX(0); }
  .main-wrap { margin-left: 0 !important; }
  .mobile-sb-toggle { display: inline-flex; }
  .mobile-sb-backdrop {
    display: block; position: fixed; inset: 0;
    background: rgba(9,15,85,.45); opacity: 0;
    pointer-events: none; transition: opacity .3s ease; z-index: 9998;
  }
  body.sb-open .mobile-sb-backdrop { opacity: 1; pointer-events: auto; }
}

/* ══════════════════════════════════════════
   LAYOUT
══════════════════════════════════════════ */
.main-wrap { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; }

/* ══════════════════════════════════════════
   TOPBAR
══════════════════════════════════════════ */
.topbar {
  height: var(--topbar-h);
  background: var(--white);
  border-bottom: 1px solid var(--border);
  padding: 0 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: var(--shadow-xs);
}
.topbar-left { display: flex; align-items: center; gap: 14px; }
.topbar-breadcrumb { display: flex; flex-direction: column; gap: 1px; }
.topbar-title { font-size: 16px; font-weight: 700; color: var(--text-1); letter-spacing: -.025em; line-height: 1.2; }
.topbar-sub { font-size: 11px; color: var(--text-4); font-weight: 400; letter-spacing: .02em; }
.topbar-divider { width: 1px; height: 28px; background: var(--border); margin: 0 4px; }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.tb-icon-btn {
  width: 36px; height: 36px; border-radius: var(--radius-sm);
  background: var(--surface); border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  color: var(--text-3); cursor: pointer; font-size: 13px;
  transition: all .15s; position: relative;
}
.tb-icon-btn:hover { background: var(--surface-2); color: var(--text-1); border-color: var(--border-2); }
.tb-notif-dot {
  position: absolute; top: 8px; right: 8px;
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--red); border: 1.5px solid var(--white);
  animation: pulse-dot 2.5s infinite;
}
.user-chip {
  display: flex; align-items: center; gap: 9px;
  padding: 4px 12px 4px 4px;
  border: 1px solid var(--border); border-radius: 40px;
  cursor: pointer; background: var(--white); transition: all .15s;
}
.user-chip:hover { border-color: var(--border-2); box-shadow: var(--shadow-xs); }
.u-avatar {
  width: 30px; height: 30px; border-radius: 50%;
  background: linear-gradient(135deg, var(--navy-3), var(--navy));
  color: var(--white); font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.u-name { font-size: 12px; font-weight: 700; color: var(--text-1); line-height: 1.2; }
.u-role { font-size: 10px; color: var(--text-4); font-weight: 400; }
.btn-add {
  display: inline-flex; align-items: center; gap: 7px;
  background: var(--navy); color: var(--white); border: none;
  border-radius: var(--radius-sm); padding: 9px 18px;
  font-size: 12.5px; font-weight: 700; cursor: pointer;
  font-family: var(--font); letter-spacing: .01em; transition: all .15s;
  position: relative; overflow: hidden;
}
.btn-add::before {
  content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.12), transparent);
  transition: left .4s ease;
}
.btn-add:hover { background: var(--navy-2); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(10,16,69,.25); }
.btn-add:hover::before { left: 100%; }
.btn-add:active { transform: translateY(0); }
.btn-add .btn-icon {
  width: 18px; height: 18px; border-radius: 4px;
  background: rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center; font-size: 10px;
}

/* ══════════════════════════════════════════
   PAGE BODY
══════════════════════════════════════════ */
.page-body { padding: 28px 32px; flex: 1; }
.page-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 24px; }
.page-eyebrow { font-size: 10.5px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--red); margin-bottom: 4px; }
.page-heading { font-size: 22px; font-weight: 800; color: var(--text-1); letter-spacing: -.03em; line-height: 1; }
.page-heading span { color: var(--text-4); font-weight: 400; font-size: 16px; margin-left: 8px; }
.page-sub { font-size: 12px; color: var(--text-3); margin-top: 5px; }
.page-date-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 12px; background: var(--white);
  border: 1px solid var(--border); border-radius: 20px;
  font-size: 11px; color: var(--text-3); font-weight: 500;
}
.page-date-badge i { color: var(--text-4); font-size: 10px; }

/* KPI */
.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
.kpi-card {
  background: var(--white); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 20px 22px;
  position: relative; overflow: hidden;
  transition: all .22s ease; cursor: default;
}
.kpi-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0;
  height: 3px; border-radius: var(--radius) var(--radius) 0 0;
}
.kpi-card.k-navy::before  { background: var(--navy); }
.kpi-card.k-green::before { background: var(--green); }
.kpi-card.k-red::before   { background: var(--red); }
.kpi-card.k-amber::before { background: #d97706; }
.kpi-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
.kpi-card:hover .kpi-icon-wrap { transform: scale(1.08); }
.kpi-inner { display: flex; justify-content: space-between; align-items: flex-start; }
.kpi-num { font-size: 32px; font-weight: 800; letter-spacing: -.04em; color: var(--text-1); line-height: 1; margin-bottom: 4px; font-variant-numeric: tabular-nums; }
.kpi-lbl { font-size: 11.5px; color: var(--text-3); font-weight: 500; }
.kpi-tag { display: inline-flex; align-items: center; gap: 4px; margin-top: 8px; font-size: 10.5px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
.kpi-tag.up      { background: var(--green-bg); color: var(--green); }
.kpi-tag.down    { background: var(--red-tint); color: var(--red); }
.kpi-tag.neutral { background: var(--surface-2); color: var(--text-3); }
.kpi-icon-wrap { width: 44px; height: 44px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; transition: transform .2s ease; }
.kpi-icon-wrap.navy  { background: var(--navy-light); color: var(--navy-3); }
.kpi-icon-wrap.green { background: var(--green-bg);   color: var(--green); }
.kpi-icon-wrap.red   { background: var(--red-tint);   color: var(--red); }
.kpi-icon-wrap.amber { background: var(--amber-bg);   color: #d97706; }

/* Table card */
.tbl-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; }
.tab-row { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); padding: 0 16px 0 0; background: var(--white); }
.tabs-left { display: flex; }
.tab-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 15px 18px; font-size: 12.5px; font-weight: 500;
  color: var(--text-3); border: none; background: transparent;
  cursor: pointer; text-decoration: none;
  border-bottom: 2px solid transparent; margin-bottom: -1px;
  font-family: var(--font); transition: color .15s, border-color .15s;
  white-space: nowrap; letter-spacing: -.01em;
}
.tab-btn:hover { color: var(--text-1); }
.tab-btn.active { color: var(--navy); border-bottom-color: var(--navy); font-weight: 700; }
.tab-chip { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 5px; background: var(--surface); color: var(--text-4); min-width: 20px; text-align: center; }
.tab-btn.active .tab-chip { background: var(--navy); color: var(--white); }
.tabs-right { display: flex; align-items: center; gap: 8px; padding: 0 4px; }
.filter-bar { display: flex; align-items: center; gap: 10px; padding: 14px 20px; border-bottom: 1px solid var(--border); background: var(--surface); flex-wrap: wrap; }
.search-field { display: flex; align-items: center; gap: 8px; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px 13px; min-width: 240px; transition: border-color .15s, box-shadow .15s; }
.search-field:focus-within { border-color: var(--navy-3); box-shadow: 0 0 0 3px rgba(26,37,128,.08); }
.search-field i { color: var(--text-4); font-size: 11px; }
.search-field input { border: none; background: transparent; font-size: 12.5px; color: var(--text-1); outline: none; font-family: var(--font); width: 170px; }
.search-field input::placeholder { color: var(--text-4); }
.date-pill { display: inline-flex; align-items: center; gap: 7px; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px 13px; font-size: 12px; font-weight: 600; color: var(--text-2); cursor: pointer; white-space: nowrap; transition: all .15s; position: relative; }
.date-pill:hover { border-color: var(--border-2); background: var(--surface-2); }
.date-pill i { color: var(--text-3); font-size: 11px; }
.date-pill input[type="date"] { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
.filter-select-wrap { position: relative; }
.filter-select-btn { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px 13px; font-size: 12.5px; color: var(--text-2); background: var(--white); cursor: pointer; white-space: nowrap; font-family: var(--font); transition: all .15s; }
.filter-select-btn:hover { border-color: var(--border-2); }
.filter-select-btn.active { border-color: var(--navy); color: var(--navy); background: var(--navy-pale); font-weight: 600; }
.filter-select-btn i.fa-chevron-down { font-size: 9px; color: var(--text-4); }
.status-drop { display: none; position: absolute; top: calc(100% + 8px); left: 0; min-width: 210px; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-lg); padding: 6px; z-index: 400; }
.sopt { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: var(--radius-sm); text-decoration: none; color: var(--text-2); font-size: 12.5px; transition: background .1s; cursor: pointer; }
.sopt:hover { background: var(--surface); color: var(--text-1); }
.sopt.sel { background: var(--navy-pale); color: var(--navy); font-weight: 600; }
.sopt-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.sopt-check { width: 16px; height: 16px; border-radius: 4px; border: 1.5px solid var(--border-2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sopt-check.on { background: var(--navy); border-color: var(--navy); }
.icon-btn { width: 35px; height: 35px; border-radius: var(--radius-sm); border: 1px solid var(--border); background: var(--white); display: flex; align-items: center; justify-content: center; color: var(--text-3); cursor: pointer; font-size: 12px; transition: all .15s; }
.icon-btn:hover { background: var(--surface-2); color: var(--text-1); border-color: var(--border-2); }

/* Table */
.tbl-scroll { overflow-x: auto; }
table.mtbl { width: 100%; border-collapse: collapse; min-width: 900px; }
table.mtbl thead { background: var(--surface); border-bottom: 2px solid var(--border); }
table.mtbl thead th { padding: 12px 16px; font-size: 10.5px; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: .1em; text-align: left; white-space: nowrap; }
table.mtbl thead th:first-child { padding-left: 24px; }
table.mtbl tbody tr { transition: background .12s; border-bottom: 1px solid var(--border); animation: slideInRight .3s ease both; }
table.mtbl tbody tr:nth-child(1)  { animation-delay:.02s; }
table.mtbl tbody tr:nth-child(2)  { animation-delay:.04s; }
table.mtbl tbody tr:nth-child(3)  { animation-delay:.06s; }
table.mtbl tbody tr:nth-child(4)  { animation-delay:.08s; }
table.mtbl tbody tr:nth-child(5)  { animation-delay:.10s; }
table.mtbl tbody tr:nth-child(n+6){ animation-delay:.12s; }
table.mtbl td { padding: 14px 16px; vertical-align: middle; font-size: 12.5px; color: var(--text-1); }
table.mtbl td:first-child { padding-left: 24px; }
table.mtbl tbody tr:last-child { border-bottom: none; }
table.mtbl tbody tr:hover { background: var(--navy-pale); cursor: pointer; }
table.mtbl tbody tr.row-active { background: var(--navy-pale); }
.del-num { font-family: var(--mono); font-size: 12.5px; font-weight: 500; color: var(--navy); letter-spacing: -.01em; }
.del-po  { font-family: var(--mono); font-size: 10.5px; color: var(--text-4); margin-top: 2px; }

/* Timeline */
.timeline { display: flex; align-items: center; gap: 0; }
.t-step { width: 20px; height: 20px; border-radius: 50%; border: 1.5px solid var(--border-2); background: var(--white); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 7px; color: var(--text-4); transition: all .2s; }
.t-step.done   { background: var(--navy); border-color: var(--navy); color: var(--white); }
.t-step.active { background: var(--white); border-color: var(--navy); color: var(--navy); box-shadow: 0 0 0 3px rgba(10,16,69,.1); }
.t-line      { height: 2px; flex: 1; background: var(--border); min-width: 6px; }
.t-line.done { background: var(--navy); }

/* Pills */
.pill { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; white-space: nowrap; letter-spacing: .02em; }
.pill-dot { width: 5px; height: 5px; border-radius: 50%; }
.pill-pending   { background: var(--amber-bg);  color: var(--amber); }
.pill-pending   .pill-dot { background: #d97706; }
.pill-approved  { background: var(--blue-bg);   color: var(--blue); }
.pill-approved  .pill-dot { background: var(--blue); }
.pill-transit   { background: var(--navy-light); color: var(--navy-3); }
.pill-transit   .pill-dot { background: var(--navy-3); animation: pulse-dot 2s infinite; }
.pill-delivered { background: var(--green-bg);  color: var(--green); }
.pill-delivered .pill-dot { background: var(--green); }
.pill-cancelled { background: var(--red-tint);  color: var(--red); }
.pill-cancelled .pill-dot { background: var(--red); }
.pill-default   { background: var(--surface-2); color: var(--text-2); }
.pill-default   .pill-dot { background: var(--text-3); }

/* Carrier */
.carrier { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 5px; font-size: 11px; font-weight: 800; letter-spacing: .03em; font-family: var(--mono); }
.c-fedex   { background: #4d148c; color: #ff6600; }
.c-dhl     { background: #ffcc00; color: #d40511; }
.c-ups     { background: #351c15; color: #ffb500; }
.c-tnt     { background: #ff6000; color: #fff; }
.c-aramex  { background: #e32e26; color: #fff; }
.c-lbc     { background: #003087; color: #fff; }
.c-jnt     { background: #e8001b; color: #fff; }
.c-regular { background: var(--surface-2); color: var(--text-2); border: 1px solid var(--border); }

/* Supplier cell */
.supplier-cell { display: flex; align-items: center; gap: 8px; }
.supplier-avatar { width: 26px; height: 26px; border-radius: 6px; background: var(--navy-light); color: var(--navy-3); font-size: 9.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; letter-spacing: .02em; }
.supplier-name { font-size: 12.5px; font-weight: 600; color: var(--text-1); }

/* Action buttons */
.act-btns { display: flex; gap: 4px; }
.act-btn { width: 30px; height: 30px; border-radius: 7px; border: 1px solid var(--border); background: var(--white); display: inline-flex; align-items: center; justify-content: center; font-size: 11px; color: var(--text-3); cursor: pointer; transition: all .15s; }
.act-btn:hover { background: var(--surface-2); color: var(--text-1); border-color: var(--border-2); }
.act-btn.navy  { border-color: var(--navy-light); color: var(--navy-3); }
.act-btn.navy:hover { background: var(--navy-light); color: var(--navy); }
.date-cell { font-family: var(--mono); font-size: 12px; color: var(--text-2); }

/* Empty */
.empty-state { text-align: center; padding: 80px 20px; }
.empty-icon { width: 56px; height: 56px; border-radius: 14px; background: var(--surface-2); color: var(--text-4); display: inline-flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px; }
.empty-state h3 { font-size: 14px; font-weight: 700; color: var(--text-2); margin-bottom: 4px; }
.empty-state p  { font-size: 12.5px; color: var(--text-4); }

/* Table footer */
.tbl-footer { display: flex; align-items: center; justify-content: space-between; padding: 14px 24px; border-top: 1px solid var(--border); background: var(--surface); }
.tbl-count { font-size: 12px; color: var(--text-3); }
.tbl-count strong { color: var(--text-1); font-weight: 700; }

/* Detail panel */
.overlay { display: none; position: fixed; inset: 0; background: rgba(10,16,69,.35); z-index: 400; backdrop-filter: blur(3px); }
.overlay.open { display: block; }
.detail-panel { position: fixed; top: 0; right: -1000px; width: 960px; max-width: 960px; height: 100vh; background: var(--white); z-index: 500; box-shadow: -12px 0 60px rgba(10,16,69,.18); display: flex; flex-direction: column; overflow: hidden; transition: right .35s cubic-bezier(.4,0,.2,1); }
.detail-panel.open { right: 0; }
.panel-x { position: absolute; top: 18px; left: -44px; width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,.95); border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 14px rgba(10,16,69,.2); color: var(--text-3); font-size: 12px; z-index: 10; transition: all .15s; }
.panel-x:hover { background: var(--white); color: var(--text-1); }
.dp-hdr { padding: 20px 26px 18px; background: var(--navy); border-bottom: 1px solid rgba(255,255,255,.06); flex-shrink: 0; position: relative; }
.dp-hdr::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--red); }
.dp-hdr-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
.dp-shp-id { font-family: var(--mono); font-size: 21px; font-weight: 500; color: var(--white); letter-spacing: -.01em; }
.dp-nav { display: flex; gap: 4px; }
.dp-nav-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid rgba(255,255,255,.15); background: rgba(255,255,255,.07); cursor: pointer; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,.65); font-size: 11px; transition: all .12s; }
.dp-nav-btn:hover { background: rgba(255,255,255,.14); color: var(--white); }
.dp-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px; }
.dp-badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 5px; letter-spacing: .03em; }
.dp-badge-progress  { background: rgba(255,255,255,.14); color: rgba(255,255,255,.9); }
.dp-badge-delivered { background: #15803d; color: #fff; }
.dp-badge-pending   { background: #92400e; color: #fff; }
.dp-badge-cancelled { background: var(--red); color: #fff; }
.dp-badge-delay     { background: #c2410c; color: #fff; }
.dp-meta { font-size: 11.5px; color: rgba(255,255,255,.4); }
.dp-meta a { color: rgba(255,255,255,.65); text-decoration: none; }
.dp-meta a:hover { color: var(--white); }
.dp-actions { display: flex; gap: 8px; margin-top: 14px; }
.dp-act-btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 14px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 600; cursor: pointer; font-family: var(--font); transition: all .12s; }
.dp-act-cancel { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.16); color: rgba(255,255,255,.7); }
.dp-act-cancel:hover { background: rgba(255,255,255,.14); color: var(--white); }
.dp-act-notify { background: var(--red); border: none; color: var(--white); box-shadow: 0 4px 12px rgba(212,36,26,.35); }
.dp-act-notify:hover { background: var(--red-2); }
.dp-act-back { background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.13); color: rgba(255,255,255,.6); margin-left: auto; }
.dp-act-back:hover { background: rgba(255,255,255,.12); color: var(--white); }
.dp-body { flex: 1; overflow: hidden; display: flex; }
.dp-left { flex: 1; padding: 22px 24px; overflow-y: auto; border-right: 1px solid var(--border); min-width: 0; }
.dp-left::-webkit-scrollbar { width: 3px; }
.dp-left::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 2px; }
.dp-addr { background: var(--navy-pale); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 13px 15px; margin-bottom: 18px; }
.dp-addr-row { display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; color: var(--text-2); padding: 4px 0; }
.dp-addr-row + .dp-addr-row { border-top: 1px dashed var(--border-2); padding-top: 9px; margin-top: 4px; }
.dp-addr-dot { width: 7px; height: 7px; border-radius: 50%; margin-top: 4px; flex-shrink: 0; }
.dot-from { background: var(--navy); }
.dot-to   { background: var(--red); }
.dp-steps { display: flex; align-items: center; margin: 14px 0 18px; }
.dp-sn { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 10px; }
.dp-sn.done    { background: var(--navy); color: var(--white); border: 2px solid var(--navy); }
.dp-sn.pending { background: var(--white); color: var(--text-4); border: 2px solid var(--border); }
.dp-sl { height: 2px; flex: 1; min-width: 14px; }
.dp-sl.done    { background: var(--navy); }
.dp-sl.pending { background: var(--border); }
.dp-time-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin-bottom: 18px; }
.dp-tc { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px 14px; }
.dp-tc label { font-size: 10px; color: var(--text-3); text-transform: uppercase; letter-spacing: .1em; display: block; margin-bottom: 4px; font-weight: 700; }
.dp-tc span { font-size: 13px; font-weight: 700; color: var(--text-1); font-family: var(--mono); }
.dp-warn { background: #fff7ed; border: 1px solid #fed7aa; border-radius: var(--radius-sm); padding: 10px 13px; font-size: 12px; color: #9a3412; display: flex; gap: 8px; margin-bottom: 18px; }
.dp-sec-title { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .12em; color: var(--text-3); margin-bottom: 14px; }
.dp-tl { list-style: none; padding: 0; margin: 0; }
.dp-tli { display: flex; gap: 12px; padding-bottom: 18px; }
.dp-tli:last-child { padding-bottom: 0; }
.dp-tl-l { display: flex; flex-direction: column; align-items: center; }
.dp-tl-dot { width: 9px; height: 9px; border-radius: 50%; margin-top: 4px; flex-shrink: 0; }
.dp-tl-dot.done    { background: var(--navy); }
.dp-tl-dot.pending { background: var(--border-2); border: 2px solid var(--border); }
.dp-tl-line { width: 2px; flex: 1; min-height: 18px; margin-top: 3px; }
.dp-tl-line.done    { background: var(--navy); }
.dp-tl-line.pending { background: var(--border); }
.dp-tli:last-child .dp-tl-line { display: none; }
.dp-tl-r { flex: 1; }
.dp-tl-lbl { font-size: 13px; font-weight: 600; color: var(--text-1); }
.dp-tl-lbl.pending { color: var(--text-3); font-weight: 400; }
.dp-tl-date { font-size: 11px; color: var(--text-3); font-family: var(--mono); margin-top: 1px; }
.dp-tl-note { font-size: 12px; color: var(--text-2); margin-top: 2px; }
.dp-right { width: 580px; flex-shrink: 0; display: flex; flex-direction: column; overflow: hidden; background: var(--surface); }
.dp-right.email-mode .dp-map  { display: none; }
.dp-right.map-mode   .dp-email { display: none; }
.dp-map { flex: 1; position: relative; overflow: hidden; background: #d4dce8; }
.dp-map svg { width: 100%; height: 100%; }
.dp-map-ctrl { position: absolute; bottom: 52px; right: 14px; display: flex; flex-direction: column; gap: 3px; }
.map-btn { width: 28px; height: 28px; background: var(--white); border: 1px solid var(--border); border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; color: var(--text-2); box-shadow: var(--shadow-xs); transition: all .12s; }
.map-btn:hover { background: var(--surface); }
.dp-map-toggle { position: absolute; bottom: 14px; left: 50%; transform: translateX(-50%); display: flex; background: var(--white); border-radius: 7px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); overflow: hidden; white-space: nowrap; }
.map-tgl-btn { font-size: 11.5px; font-weight: 600; padding: 5px 12px; background: none; border: none; cursor: pointer; color: var(--text-3); font-family: var(--font); }
.map-tgl-btn.on { background: var(--navy); color: var(--white); }
.dp-email { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.ep-hdr { padding: 16px 18px 14px; border-bottom: 1px solid var(--border); display: flex; align-items: flex-start; justify-content: space-between; flex-shrink: 0; background: var(--white); }
.ep-icon { width: 38px; height: 38px; background: var(--navy-light); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.ep-title { font-size: 13px; font-weight: 700; color: var(--text-1); }
.ep-sub   { font-size: 11px; color: var(--text-3); margin-top: 1px; }
.ep-close { background: none; border: none; cursor: pointer; color: var(--text-3); font-size: 12px; padding: 4px; border-radius: 5px; transition: all .12s; }
.ep-close:hover { background: var(--surface); color: var(--text-1); }
.ep-body { flex: 1; overflow-y: auto; padding: 14px 18px; display: flex; flex-direction: column; gap: 10px; }
.ep-row { display: flex; align-items: center; border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; background: var(--white); transition: border-color .15s; }
.ep-row:focus-within { border-color: var(--navy-3); }
.ep-lbl { font-size: 11px; font-weight: 700; color: var(--text-3); padding: 8px 12px; min-width: 58px; border-right: 1px solid var(--border); background: var(--surface); flex-shrink: 0; }
.ep-inp { font-size: 12.5px; color: var(--text-1); border: none; background: transparent; outline: none; flex: 1; padding: 8px 10px; font-family: var(--font); }
.ep-cc  { font-size: 11.5px; color: var(--navy); padding: 8px 10px; cursor: pointer; flex-shrink: 0; font-weight: 700; }
.ep-cnt { font-size: 11px; color: var(--text-4); padding: 8px 10px; flex-shrink: 0; font-family: var(--mono); }
.ep-tpl-row { display: flex; gap: 8px; }
.ep-tpl-sel { flex: 1; font-size: 12.5px; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 7px 10px; background: var(--white); color: var(--text-1); outline: none; font-family: var(--font); cursor: pointer; }
.ep-save-btn { font-size: 12px; padding: 7px 13px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--white); color: var(--text-2); cursor: pointer; font-family: var(--font); white-space: nowrap; }
.ep-save-btn:hover { background: var(--surface); }
.ep-msg { font-size: 12.5px; color: var(--text-1); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 12px; outline: none; resize: none; min-height: 140px; font-family: var(--font); background: var(--white); line-height: 1.65; }
.ep-msg:focus { border-color: var(--navy-3); }
.ep-toolbar { display: flex; align-items: center; gap: 2px; flex-wrap: wrap; }
.ep-tb { background: none; border: none; cursor: pointer; color: var(--text-3); font-size: 12px; padding: 5px 7px; border-radius: 5px; font-family: var(--font); }
.ep-tb:hover { background: var(--surface); color: var(--text-1); }
.ep-div { width: 1px; height: 14px; background: var(--border); margin: 0 3px; }
.ep-ai { display: flex; align-items: center; gap: 5px; margin-left: auto; font-size: 11.5px; font-weight: 700; color: var(--navy); border: 1px solid var(--navy-light); border-radius: 6px; padding: 4px 10px; background: var(--navy-pale); cursor: pointer; font-family: var(--font); transition: all .12s; }
.ep-ai:hover { background: var(--navy-light); }
.ep-footer { padding: 12px 18px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; background: var(--white); }
.ep-foot-info { font-size: 11px; color: var(--text-3); }
.ep-foot-info a { color: var(--navy); text-decoration: none; }
.ep-foot-btns { display: flex; gap: 8px; }
.ep-cancel-btn { padding: 7px 16px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--surface); color: var(--text-2); font-size: 12.5px; font-weight: 500; cursor: pointer; font-family: var(--font); }
.ep-confirm-btn { padding: 7px 20px; border: none; border-radius: var(--radius-sm); background: var(--red); color: var(--white); font-size: 12.5px; font-weight: 700; cursor: pointer; font-family: var(--font); box-shadow: 0 4px 12px rgba(212,36,26,.25); }
.ep-confirm-btn:hover { background: var(--red-2); }

/* Modals */
.modal-content { border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow-lg); }
.modal-header  { border-bottom: 1px solid var(--border); padding: 18px 22px 12px; }
.modal-footer  { border-top: 1px solid var(--border); padding: 14px 22px; }
.modal-body    { padding: 16px 22px; }
.form-select   { border-radius: var(--radius-sm); border: 1px solid var(--border); font-size: 12.5px; font-family: var(--font); }
.form-select:focus { border-color: var(--navy-3); box-shadow: 0 0 0 3px rgba(26,37,128,.08); }
.btn-modal-ok { background: var(--navy); border: none; border-radius: var(--radius-sm); padding: 8px 22px; font-size: 13px; font-weight: 700; color: var(--white); cursor: pointer; font-family: var(--font); }
.btn-modal-ok:hover { background: var(--navy-2); }
.notif-opt { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 14px; border-radius: 10px; cursor: pointer; border: 1.5px solid var(--border); background: var(--white); transition: all .15s; user-select: none; margin-bottom: 8px; }
.notif-opt:last-child { margin-bottom: 0; }
.notif-opt:hover { background: var(--surface); border-color: var(--border-2); }
.notif-opt.sel  { background: var(--navy-pale); border-color: var(--navy-3); }
.notif-opt-title { font-size: 13px; font-weight: 700; color: var(--text-1); margin-bottom: 2px; }
.notif-opt-sub   { font-size: 11px; color: var(--text-3); }
.notif-opt-r     { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.notif-radio     { width: 16px; height: 16px; accent-color: var(--navy); cursor: pointer; }

/* Alerts */
.alert { border-radius: var(--radius-sm); font-size: 12.5px; margin-bottom: 18px; }
.alert-success { background: var(--green-bg); color: var(--green); border: 1px solid #a7f3d0; }
.alert-danger  { background: var(--red-tint); color: var(--red);   border: 1px solid #fecaca; }
</style>
</head>
<body>

<!-- ═══════════════════════════════════════════
   SIDEBAR — matches screenshot style
   Dark navy, Tabler icons, simple flat items,
   active item highlighted, Delivery Tracking
   shown as active with blue icon
═══════════════════════════════════════════ -->
<nav id="appSidebar" class="sidebar">

  <!-- Logo -->
  <div class="sb-logo">
    <div class="sb-logo-ring">
      <img src="logo.png" alt="McPIL"
           onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
      <span class="sb-logo-fallback" style="display:none">McP</span>
    </div>
    <div>
      <div class="sb-brand-name">McPIL</div>
      <div class="sb-brand-sub">Pharmaceutical Lab...</div>
    </div>
  </div>

  <div class="sb-nav">

    <!-- MAIN -->
    <div class="sb-section">Main</div>

    <a class="sb-item" href="<?php echo is_employee() ? 'employee_home.php' : 'dashboard.php'; ?>">
      <i class="ti ti-layout-dashboard"></i>
      <?php echo is_employee() ? 'Home' : 'Dashboard'; ?>
    </a>

    <?php if (is_admin() || is_store()): ?>
    <a class="sb-item" href="purchase_order.php">
      <i class="ti ti-shopping-cart"></i>
      Purchase Order
    </a>
    <a class="sb-item" href="purchase_invoice.php">
      <i class="ti ti-file-invoice"></i>
      Purchase Invoice
    </a>
    <?php endif; ?>

    <?php if (is_admin()): ?>
    <a class="sb-item" href="employee_profile.php">
      <i class="ti ti-users"></i>
      Employee Profile
    </a>
    <a class="sb-item" href="attendance.php">
      <i class="ti ti-clock"></i>
      Attendance
    </a>
    <?php endif; ?>

    <?php if (is_employee()): ?>
    <a class="sb-item" href="inventory.php">
      <i class="ti ti-box"></i>
      Inventory
    </a>
    <?php endif; ?>

    <!-- LOGISTICS -->
    <div class="sb-section">Logistics</div>

    <a class="sb-item sb-highlight active" href="delivery_tracking.php">
      <i class="ti ti-truck-delivery"></i>
      Delivery Tracking
    </a>

    <?php if (!is_employee()): ?>
    <a class="sb-item" href="delivery_history.php">
      <i class="ti ti-history"></i>
      Delivery History
    </a>
    <?php endif; ?>

    <!-- TOOLS -->
    <div class="sb-section">Tools</div>

    <a class="sb-item" href="reports.php">
      <i class="ti ti-chart-bar"></i>
      Reports
    </a>

    <a class="sb-item" href="chat_interface.php">
      <i class="ti ti-message-2"></i>
      Chat
    </a>

    <?php if (is_employee()): ?>
    <a class="sb-item" href="attendance_camera.php">
      <i class="ti ti-camera"></i>
      Check In
    </a>
    <a class="sb-item" href="attendance_history.php">
      <i class="ti ti-calendar-event"></i>
      Attendance Log
    </a>
    <?php endif; ?>

  </div><!-- /sb-nav -->

  <!-- Footer: Account -->
  <div class="sb-footer">
    <div class="sb-section" style="padding-top:8px">Account</div>
    <a class="sb-item" href="settings.php">
      <i class="ti ti-settings"></i>
      Settings
    </a>
    <a class="sb-item sb-logout" href="logout.php">
      <i class="ti ti-logout"></i>
      Logout
    </a>
  </div>

</nav>

<div class="mobile-sb-backdrop" id="mobileSbBackdrop"></div>

<!-- ═══════════════ MAIN ═══════════════ -->
<div class="main-wrap">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="mobile-sb-toggle" id="sidebarToggle" aria-label="Open navigation">
        <i class="fas fa-bars"></i>
      </button>
      <div class="topbar-divider"></div>
      <div class="topbar-breadcrumb">
        <div class="topbar-title">Shipment Tracker</div>
        <div class="topbar-sub">Logistics · Real-time delivery management</div>
      </div>
    </div>
    <div class="topbar-right">
      <div class="tb-icon-btn">
        <i class="fas fa-magnifying-glass"></i>
      </div>
      <div class="tb-icon-btn">
        <i class="fas fa-bell"></i>
        <span class="tb-notif-dot"></span>
      </div>
      <div class="user-chip">
        <div class="u-avatar"><?php echo strtoupper(substr($_SESSION['full_name'],0,2)); ?></div>
        <div>
          <div class="u-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
          <div class="u-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
        </div>
      </div>
      <?php if(is_admin()||is_manager()): ?>
      <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addDeliveryModal">
        <span class="btn-icon"><i class="fas fa-plus"></i></span>
        Add Shipment
      </button>
      <?php endif; ?>
    </div>
  </header>

  <div class="page-body">

    <!-- Alerts -->
    <?php if(isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-circle-check me-2"></i><?php echo htmlspecialchars($success_message); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if(isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-circle-exclamation me-2"></i><?php echo htmlspecialchars($error_message); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Page header -->
    <div class="page-header fade-up">
      <div class="page-header-left">
        <div class="page-eyebrow">Logistics</div>
        <div class="page-heading">
          Shipments
          <span><?php echo $counts['all']; ?> total</span>
        </div>
        <div class="page-sub">Monitor and manage all active and historical deliveries</div>
      </div>
      <div class="page-date-badge">
        <i class="fas fa-calendar"></i>
        <?php echo date('d M Y'); ?>
      </div>
    </div>

    <!-- KPI Grid -->
    <div class="kpi-grid">
      <div class="kpi-card k-navy fade-up fade-up-1">
        <div class="kpi-inner">
          <div>
            <div class="kpi-num"><?php echo $counts['all']; ?></div>
            <div class="kpi-lbl">Total Shipments</div>
            <span class="kpi-tag neutral">All time</span>
          </div>
          <div class="kpi-icon-wrap navy"><i class="fas fa-layer-group"></i></div>
        </div>
      </div>
      <div class="kpi-card k-amber fade-up fade-up-2">
        <div class="kpi-inner">
          <div>
            <div class="kpi-num"><?php echo $counts['in_transit']; ?></div>
            <div class="kpi-lbl">In Transit</div>
            <span class="kpi-tag up"><i class="fas fa-circle" style="font-size:5px"></i> Active</span>
          </div>
          <div class="kpi-icon-wrap amber"><i class="fas fa-truck-fast"></i></div>
        </div>
      </div>
      <div class="kpi-card k-green fade-up fade-up-3">
        <div class="kpi-inner">
          <div>
            <div class="kpi-num"><?php echo $counts['delivered']; ?></div>
            <div class="kpi-lbl">Delivered</div>
            <span class="kpi-tag up"><i class="fas fa-arrow-up" style="font-size:8px"></i> Completed</span>
          </div>
          <div class="kpi-icon-wrap green"><i class="fas fa-circle-check"></i></div>
        </div>
      </div>
      <div class="kpi-card k-red fade-up fade-up-4">
        <div class="kpi-inner">
          <div>
            <div class="kpi-num"><?php echo $counts['cancelled']; ?></div>
            <div class="kpi-lbl">Cancelled</div>
            <span class="kpi-tag down"><i class="fas fa-arrow-down" style="font-size:8px"></i> Inactive</span>
          </div>
          <div class="kpi-icon-wrap red"><i class="fas fa-circle-xmark"></i></div>
        </div>
      </div>
    </div>

    <!-- Table card -->
    <div class="tbl-card fade-up fade-up-5">

      <!-- Tabs -->
      <div class="tab-row">
        <div class="tabs-left">
          <?php
          $tabs = [
            'all'       => ['All Orders',  'fa-list'],
            'pending'   => ['Pending',     'fa-clock'],
            'delivered' => ['Arrived',     'fa-circle-check'],
            'cancelled' => ['Cancelled',   'fa-circle-xmark'],
          ];
          foreach($tabs as $v=>$t):
            $cnt = $v==='all' ? count($all_deliveries) : ($counts[$v]??0);
          ?>
          <a href="delivery_tracking.php?status=<?php echo $v; ?><?php echo $filter_date?'&filter_date='.urlencode($filter_date):''; ?>"
             class="tab-btn <?php echo $status_filter===$v?'active':''; ?>">
            <i class="fas <?php echo $t[1]; ?>" style="font-size:11px"></i>
            <?php echo $t[0]; ?>
            <span class="tab-chip"><?php echo $cnt; ?></span>
          </a>
          <?php endforeach; ?>
        </div>
        <div class="tabs-right">
          <button class="icon-btn" title="Toggle filters">
            <i class="fas fa-sliders"></i>
          </button>
        </div>
      </div>

      <!-- Filter bar -->
      <form method="GET" action="delivery_tracking.php" id="filterForm">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
        <div class="filter-bar">
          <div class="search-field">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="search" id="searchInput"
                   placeholder="Search shipment ID, supplier, order…"
                   value="<?php echo htmlspecialchars($_GET['search']??''); ?>">
          </div>

          <label class="date-pill" for="filterDateInput">
            <i class="fas fa-calendar-days"></i>
            <span id="datePillLabel">
              <?php echo $filter_date
                ? htmlspecialchars(date('d M Y',strtotime($filter_date)))
                : date('d M').' – '.date('d M Y'); ?>
            </span>
            <i class="fas fa-chevron-down" style="font-size:9px"></i>
            <input type="date" name="filter_date" id="filterDateInput"
                   value="<?php echo htmlspecialchars($filter_date); ?>">
          </label>

          <?php if($filter_date): ?>
          <a href="delivery_tracking.php?status=<?php echo htmlspecialchars($status_filter); ?>"
             style="font-size:11px;color:var(--text-3);text-decoration:none;display:inline-flex;align-items:center;gap:4px;padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--white);">
            <i class="fas fa-times" style="font-size:9px"></i> Clear
          </a>
          <?php endif; ?>

          <div class="filter-select-wrap">
            <div class="filter-select-btn <?php echo $status_filter!=='all'?'active':''; ?>"
                 id="statusDropBtn"
                 onclick="toggleStatusDrop(event)">
              <i class="fas fa-filter" style="font-size:10px"></i>
              <?php
                $lbls=['all'=>'All Statuses','pending'=>'Pending','approved'=>'Approved',
                       'in_transit'=>'In Transit','delivered'=>'Arrived','cancelled'=>'Cancelled'];
                echo $lbls[$status_filter]??'All Statuses';
              ?>
              <i class="fas fa-chevron-down"></i>
            </div>
            <div id="statusDrop" class="status-drop" onclick="event.stopPropagation()">
              <?php
              $sOpts = [
                'in_transit'=>['In Transit','var(--navy-3)'],
                'delivered' =>['Arrived',   'var(--green)'],
                'cancelled' =>['Cancelled', 'var(--red)'],
                'pending'   =>['Pending',   '#d97706'],
              ];
              foreach($sOpts as $v=>[$lbl,$clr]):
                $sel = $v===$status_filter;
                $url = 'delivery_tracking.php?status='.$v
                     .($filter_date?'&filter_date='.urlencode($filter_date):'')
                     .(!empty($_GET['search'])?'&search='.urlencode($_GET['search']):'');
              ?>
              <a href="<?php echo $url; ?>" class="sopt <?php echo $sel?'sel':''; ?>">
                <span class="sopt-check <?php echo $sel?'on':''; ?>">
                  <?php if($sel): ?><i class="fas fa-check" style="font-size:8px;color:#fff"></i><?php endif; ?>
                </span>
                <span class="sopt-dot" style="background:<?php echo $clr; ?>"></span>
                <span><?php echo $lbl; ?></span>
              </a>
              <?php endforeach; ?>
            </div>
          </div>

          <div style="display:flex;gap:6px;margin-left:auto;">
            <div class="icon-btn" title="Export"><i class="fas fa-download"></i></div>
            <div class="icon-btn" title="Refresh"><i class="fas fa-rotate-right"></i></div>
          </div>
        </div>
      </form>

      <!-- Table -->
      <div class="tbl-scroll">
        <table class="mtbl">
          <thead>
            <tr>
              <th>Shipment ID</th>
              <th>Progress</th>
              <th>Status</th>
              <th>Expected</th>
              <th>Order #</th>
              <th>Carrier</th>
              <th>Supplier</th>
              <th style="width:80px;text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($filtered_deliveries)): ?>
            <tr>
              <td colspan="8">
                <div class="empty-state">
                  <div class="empty-icon"><i class="fas fa-truck"></i></div>
                  <h3>No shipments found</h3>
                  <p>Try adjusting your filters or date range</p>
                </div>
              </td>
            </tr>
            <?php else: ?>
            <?php foreach($filtered_deliveries as $idx=>$delivery):
              $step = status_to_step($delivery['status']);
              $pill = status_pill($delivery['status']);
              $initials = strtoupper(substr($delivery['supplier_name'],0,2));
            ?>
            <tr data-panel-index="<?php echo $idx; ?>" onclick="openPanel(<?php echo $idx; ?>)">
              <td>
                <div class="del-num"><?php echo htmlspecialchars($delivery['delivery_number']); ?></div>
                <div class="del-po"><?php echo htmlspecialchars($delivery['po_number']); ?></div>
              </td>
              <td><?php echo render_timeline($step); ?></td>
              <td>
                <span class="pill <?php echo $pill['cls']; ?>">
                  <span class="pill-dot"></span>
                  <?php echo $pill['label']; ?>
                </span>
              </td>
              <td><span class="date-cell"><?php echo htmlspecialchars(format_date($delivery['delivery_date'])); ?></span></td>
              <td><span class="date-cell" style="color:var(--text-4)"><?php echo htmlspecialchars($delivery['po_number']); ?></span></td>
              <td><?php echo carrier_badge($delivery['carrier']??''); ?></td>
              <td>
                <div class="supplier-cell">
                  <div class="supplier-avatar"><?php echo $initials; ?></div>
                  <span class="supplier-name"><?php echo htmlspecialchars($delivery['supplier_name']); ?></span>
                </div>
              </td>
              <td onclick="event.stopPropagation()">
                <div class="act-btns" style="justify-content:flex-end">
                  <?php if(is_admin()||is_manager()): ?>
                  <button class="act-btn navy"
                          data-bs-toggle="modal"
                          data-bs-target="#updateStatusModal<?php echo $delivery['id']; ?>"
                          title="Update Status">
                    <i class="fas fa-pen-to-square"></i>
                  </button>
                  <?php endif; ?>
                  <button class="act-btn" onclick="openPanel(<?php echo $idx; ?>)" title="View Details">
                    <i class="fas fa-arrow-right" style="font-size:10px"></i>
                  </button>
                </div>
              </td>
            </tr>

            <?php if(is_admin()||is_manager()): ?>
            <div class="modal fade" id="updateStatusModal<?php echo $delivery['id']; ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" style="font-size:14px;font-weight:800;color:var(--navy)">Update Delivery Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST">
                    <div class="modal-body">
                      <input type="hidden" name="delivery_id" value="<?php echo $delivery['id']; ?>">
                      <p style="font-size:12px;color:var(--text-3);margin-bottom:14px">
                        <span style="font-family:var(--mono);color:var(--navy);font-weight:600"><?php echo htmlspecialchars($delivery['delivery_number']); ?></span>
                        &nbsp;·&nbsp;Current:&nbsp;
                        <span class="pill <?php echo $pill['cls']; ?>" style="padding:2px 8px;font-size:11px">
                          <span class="pill-dot"></span><?php echo $pill['label']; ?>
                        </span>
                      </p>
                      <label class="form-label" style="font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:6px">New Status</label>
                      <select name="new_status" class="form-select" required>
                        <option value="">Select status…</option>
                        <?php if($delivery['status']==='pending'): ?>
                          <option value="approved">Approved</option>
                          <option value="in_transit">In Transit</option>
                          <option value="delivered">Delivered</option>
                          <option value="cancelled">Cancelled</option>
                        <?php elseif($delivery['status']==='approved'): ?>
                          <option value="in_transit">In Transit</option>
                          <option value="delivered">Delivered</option>
                          <option value="cancelled">Cancelled</option>
                        <?php elseif($delivery['status']==='in_transit'): ?>
                          <option value="delivered">Delivered</option>
                          <option value="cancelled">Cancelled</option>
                        <?php elseif($delivery['status']==='delivered'): ?>
                          <option value="pending">Reset to Pending</option>
                          <option value="cancelled">Cancel</option>
                        <?php elseif($delivery['status']==='cancelled'): ?>
                          <option value="pending">Reactivate</option>
                        <?php endif; ?>
                      </select>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" name="update_status" class="btn-modal-ok">Update Status</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if(!empty($filtered_deliveries)): ?>
      <div class="tbl-footer">
        <div class="tbl-count">
          Showing <strong><?php echo count($filtered_deliveries); ?></strong> of <strong><?php echo $counts['all']; ?></strong> shipments
        </div>
        <div style="display:flex;gap:6px">
          <button class="icon-btn" disabled><i class="fas fa-chevron-left" style="font-size:10px"></i></button>
          <button style="width:30px;height:30px;border-radius:6px;border:none;background:var(--navy);color:var(--white);font-size:12px;font-weight:700;cursor:default">1</button>
          <button class="icon-btn"><i class="fas fa-chevron-right" style="font-size:10px"></i></button>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /tbl-card -->
  </div><!-- /page-body -->
</div><!-- /main-wrap -->

<!-- ═══════════ DETAIL PANEL ═══════════ -->
<div class="overlay" id="panelOverlay" onclick="closePanel()"></div>
<div class="detail-panel" id="detailPanel">
  <button class="panel-x" onclick="closePanel()"><i class="fas fa-times"></i></button>
  <div class="dp-hdr">
    <div class="dp-hdr-top">
      <span class="dp-shp-id" id="dp-del-number">—</span>
      <div class="dp-nav">
        <button class="dp-nav-btn" onclick="panelNavigate(-1)"><i class="fas fa-chevron-left"></i></button>
        <button class="dp-nav-btn" onclick="panelNavigate(1)"><i class="fas fa-chevron-right"></i></button>
      </div>
    </div>
    <div class="dp-badges" id="dp-badges"></div>
    <div class="dp-meta" id="dp-meta"></div>
    <div class="dp-actions">
      <button class="dp-act-btn dp-act-cancel"><i class="fas fa-times-circle"></i> Cancel Order</button>
      <button class="dp-act-btn dp-act-notify" onclick="openNotifChoice()"><i class="fas fa-bell"></i> Notify Supplier</button>
      <button class="dp-act-btn dp-act-back" onclick="closePanel()"><i class="fas fa-arrow-left"></i> Back</button>
    </div>
  </div>
  <div class="dp-body">
    <div class="dp-left">
      <div class="dp-addr" id="dp-address"></div>
      <div class="dp-steps" id="dp-steps"></div>
      <div class="dp-time-grid" id="dp-time-grid"></div>
      <div id="dp-warning"></div>
      <div class="dp-sec-title">Shipment Timeline</div>
      <ul class="dp-tl" id="dp-timeline"></ul>
    </div>
    <div class="dp-right map-mode" id="dp-right-col">
      <div class="dp-map" id="dp-map-wrap">
        <svg viewBox="0 0 460 600" xmlns="http://www.w3.org/2000/svg">
          <rect width="460" height="600" fill="#d4dce8"/>
          <rect x="40" y="80"  width="60" height="40" rx="3" fill="#c5cedb" opacity=".7"/>
          <rect x="160" y="120" width="80" height="50" rx="3" fill="#c5cedb" opacity=".7"/>
          <rect x="280" y="90"  width="70" height="45" rx="3" fill="#c5cedb" opacity=".7"/>
          <rect x="350" y="200" width="60" height="35" rx="3" fill="#c5cedb" opacity=".7"/>
          <rect x="60"  y="240" width="90" height="55" rx="3" fill="#c5cedb" opacity=".7"/>
          <rect x="190" y="260" width="70" height="40" rx="3" fill="#c5cedb" opacity=".7"/>
          <rect x="100" y="370" width="80" height="45" rx="3" fill="#c5cedb" opacity=".7"/>
          <rect x="250" y="380" width="65" height="40" rx="3" fill="#c5cedb" opacity=".7"/>
          <line x1="0" y1="160" x2="460" y2="160" stroke="#b8c6d6" stroke-width=".8" opacity=".5"/>
          <line x1="0" y1="320" x2="460" y2="320" stroke="#b8c6d6" stroke-width=".8" opacity=".5"/>
          <line x1="0" y1="480" x2="460" y2="480" stroke="#b8c6d6" stroke-width=".8" opacity=".5"/>
          <line x1="115" y1="0" x2="115" y2="600" stroke="#b8c6d6" stroke-width=".8" opacity=".5"/>
          <line x1="230" y1="0" x2="230" y2="600" stroke="#b8c6d6" stroke-width=".8" opacity=".5"/>
          <line x1="345" y1="0" x2="345" y2="600" stroke="#b8c6d6" stroke-width=".8" opacity=".5"/>
          <polyline points="200,85 185,140 168,195 150,250 132,305 115,358 98,412 82,462 70,500"
            stroke="#d4241a" stroke-width="2.5" fill="none" stroke-dasharray="8,5" opacity=".85"/>
          <circle cx="200" cy="85" r="7" fill="#0a1045"/>
          <circle cx="200" cy="85" r="3.5" fill="#fff"/>
          <circle cx="70" cy="500" r="7" fill="#d4241a"/>
          <circle cx="70" cy="500" r="3.5" fill="#fff"/>
          <rect x="210" y="72" width="48" height="18" rx="4" fill="#0a1045"/>
          <text x="234" y="84" text-anchor="middle" font-size="8" fill="white" font-family="sans-serif" font-weight="600">ORIGIN</text>
          <rect x="80" y="487" width="60" height="18" rx="4" fill="#d4241a"/>
          <text x="110" y="499" text-anchor="middle" font-size="8" fill="white" font-family="sans-serif" font-weight="600">DEST.</text>
        </svg>
        <div class="dp-map-ctrl">
          <button class="map-btn">+</button>
          <button class="map-btn">−</button>
        </div>
        <div class="dp-map-toggle">
          <button class="map-tgl-btn">Satellite</button>
          <button class="map-tgl-btn on">Map</button>
        </div>
      </div>
      <div class="dp-email" id="dp-email-wrap">
        <div class="ep-hdr">
          <div style="display:flex;align-items:flex-start;gap:10px">
            <div class="ep-icon">✉️</div>
            <div>
              <div class="ep-title">Notify Supplier</div>
              <div class="ep-sub">Send shipment update via email</div>
            </div>
          </div>
          <button class="ep-close" onclick="closeEmailComposer()"><i class="fas fa-times"></i></button>
        </div>
        <div class="ep-body">
          <div class="ep-row">
            <span class="ep-lbl">To</span>
            <input class="ep-inp" type="email" id="ep-to" placeholder="recipient@email.com">
            <span class="ep-cc">Cc Bcc</span>
          </div>
          <div class="ep-row">
            <span class="ep-lbl">Subject</span>
            <input class="ep-inp" type="text" id="ep-subject" placeholder="Email subject…">
            <span class="ep-cnt" id="ep-char-count">0</span>
          </div>
          <div class="ep-tpl-row">
            <select class="ep-tpl-sel" id="ep-template" onchange="applyTemplate(this.value)">
              <option value="">Use a template…</option>
              <option value="shipped">Order Shipped</option>
              <option value="delayed">Delivery Delayed</option>
              <option value="out">Out for Delivery</option>
              <option value="delivered">Delivered</option>
            </select>
            <button class="ep-save-btn" onclick="saveTemplate()">Save Template</button>
          </div>
          <textarea class="ep-msg" id="ep-message" placeholder="Write your message…"></textarea>
          <div class="ep-toolbar">
            <button class="ep-tb" onclick="formatText('bold')"><b>B</b></button>
            <button class="ep-tb" onclick="formatText('italic')"><i>I</i></button>
            <button class="ep-tb" onclick="formatText('underline')"><u>U</u></button>
            <div class="ep-div"></div>
            <button class="ep-tb"><i class="fas fa-link"></i></button>
            <button class="ep-tb"><i class="fas fa-paperclip"></i></button>
            <button class="ep-ai"><i class="fas fa-wand-magic-sparkles"></i> AI Assist</button>
          </div>
        </div>
        <div class="ep-footer">
          <div class="ep-foot-info">Draft saved · <a href="#">View history</a></div>
          <div class="ep-foot-btns">
            <button class="ep-cancel-btn" onclick="closeEmailComposer()">Cancel</button>
            <button class="ep-confirm-btn" onclick="sendEmailNotification()">Send</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Notification choice modal -->
<div class="modal fade" id="notificationChoiceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="font-size:14px;font-weight:800;color:var(--navy)">Notification Channel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="notif-opt" onclick="selectNotif('email')">
          <div>
            <div class="notif-opt-title">Email Notification</div>
            <div class="notif-opt-sub">Send via email to supplier</div>
          </div>
          <div class="notif-opt-r">
            <i class="fas fa-envelope" style="font-size:18px;color:var(--navy-3)"></i>
            <input type="radio" name="notif_type" value="email" class="notif-radio">
          </div>
        </div>
        <div class="notif-opt" onclick="selectNotif('whatsapp')">
          <div>
            <div class="notif-opt-title">WhatsApp</div>
            <div class="notif-opt-sub">Send via WhatsApp message</div>
          </div>
          <div class="notif-opt-r">
            <i class="fab fa-whatsapp" style="font-size:18px;color:#25d366"></i>
            <input type="radio" name="notif_type" value="whatsapp" class="notif-radio">
          </div>
        </div>
        <div class="notif-opt" onclick="selectNotif('sms')">
          <div>
            <div class="notif-opt-title">SMS</div>
            <div class="notif-opt-sub">Send via text message</div>
          </div>
          <div class="notif-opt-r">
            <i class="fas fa-sms" style="font-size:18px;color:var(--text-3)"></i>
            <input type="radio" name="notif_type" value="sms" class="notif-radio">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn-modal-ok" onclick="handleNotifContinue()">Continue</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const deliveries = <?php echo json_encode($js_deliveries); ?>;
let currentIdx = null;
let notifModal = null;

function statusToStep(status) {
  return {pending:0,approved:1,in_transit:3,delivered:4}[status] ?? 0;
}

function openPanel(idx) {
  currentIdx = idx;
  const d = deliveries[idx];
  if(!d) return;
  document.getElementById('dp-del-number').textContent = d.del_number || '—';
  let badges = '';
  const bc = {in_transit:'dp-badge-progress',delivered:'dp-badge-delivered',pending:'dp-badge-pending',cancelled:'dp-badge-cancelled'};
  const bl = {in_transit:'In Transit',delivered:'Delivered',pending:'Pending',cancelled:'Cancelled'};
  if(bl[d.status]) badges += `<span class="dp-badge ${bc[d.status]}">${esc(bl[d.status])}</span>`;
  if(d.delay) badges += '<span class="dp-badge dp-badge-delay">Delay Alert</span>';
  document.getElementById('dp-badges').innerHTML = badges;
  document.getElementById('dp-meta').innerHTML = `PO: <a href="#">${esc(d.po_number)}</a> &nbsp;·&nbsp; Supplier: <a href="#">${esc(d.supplier)}</a> &nbsp;·&nbsp; Expected: ${esc(d.delivery_date)}`;
  document.getElementById('dp-address').innerHTML =
    `<div class="dp-addr-row"><div class="dp-addr-dot dot-from"></div><div><div style="font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--text-4);margin-bottom:2px">From</div>${esc(d.from_address)}</div></div>`+
    `<div class="dp-addr-row"><div class="dp-addr-dot dot-to"></div><div><div style="font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--text-4);margin-bottom:2px">To</div>${esc(d.to_address)}</div></div>`;
  const step = statusToStep(d.status);
  const icons = ['fa-clipboard-list','fa-check','fa-box','fa-truck','fa-map-marker-alt'];
  let stepsHtml = '';
  for(let i=0;i<=4;i++){
    const done = i <= step;
    stepsHtml += `<div class="dp-sn ${done?'done':'pending'}"><i class="fas ${icons[i]}"></i></div>`;
    if(i<4) stepsHtml += `<div class="dp-sl ${done?'done':'pending'}"></div>`;
  }
  document.getElementById('dp-steps').innerHTML = stepsHtml;
  document.getElementById('dp-time-grid').innerHTML =
    `<div class="dp-tc"><label>Total Time</label><span>${esc(d.total_time)}</span></div>`+
    `<div class="dp-tc"><label>Departure</label><span>${esc(d.departure_time)}</span></div>`+
    `<div class="dp-tc"><label>Expected</label><span>${esc(d.delivery_date)}</span></div>`;
  document.getElementById('dp-warning').innerHTML = d.warning ?
    `<div class="dp-warn"><i class="fas fa-triangle-exclamation"></i><span>${esc(d.warning)}</span></div>` : '';
  let tlHtml = '';
  d.timeline.forEach(t => {
    tlHtml += `<li class="dp-tli">
      <div class="dp-tl-l">
        <div class="dp-tl-dot ${t.done?'done':'pending'}"></div>
        <div class="dp-tl-line ${t.done?'done':'pending'}"></div>
      </div>
      <div class="dp-tl-r">
        <div class="dp-tl-lbl ${t.done?'':'pending'}">${esc(t.label)}</div>
        <div class="dp-tl-date">${esc(t.date)}</div>
        <div class="dp-tl-note">${esc(t.note)}</div>
      </div>
    </li>`;
  });
  document.getElementById('dp-timeline').innerHTML = tlHtml;
  document.getElementById('panelOverlay').classList.add('open');
  document.getElementById('detailPanel').classList.add('open');
  closeEmailComposer();
}

function closePanel() {
  document.getElementById('panelOverlay').classList.remove('open');
  document.getElementById('detailPanel').classList.remove('open');
  currentIdx = null;
}

function panelNavigate(dir) {
  if(currentIdx === null) return;
  const next = currentIdx + dir;
  if(next >= 0 && next < deliveries.length) openPanel(next);
}

function openEmailComposer() {
  if(currentIdx === null) return;
  const d = deliveries[currentIdx];
  document.getElementById('ep-to').value = d.supplier_email || '';
  document.getElementById('ep-subject').value = `Update on Order #${d.po_number}`;
  document.getElementById('ep-message').value = '';
  document.getElementById('dp-right-col').classList.remove('map-mode');
  document.getElementById('dp-right-col').classList.add('email-mode');
}

function closeEmailComposer() {
  document.getElementById('dp-right-col').classList.add('map-mode');
  document.getElementById('dp-right-col').classList.remove('email-mode');
}

function applyTemplate(val) {
  const tpl = {
    shipped:   {subject:'Order Shipped',    msg:'Your order has been shipped and is on its way.'},
    delayed:   {subject:'Delivery Delayed', msg:'Your shipment is experiencing a delay. We apologize for the inconvenience.'},
    out:       {subject:'Out for Delivery', msg:'Your order is out for delivery today and should arrive soon.'},
    delivered: {subject:'Order Delivered',  msg:'Your order has been delivered successfully. Thank you!'}
  };
  if(tpl[val]) {
    document.getElementById('ep-subject').value = tpl[val].subject;
    document.getElementById('ep-message').value = tpl[val].msg;
  }
}

function saveTemplate() { alert('Template saved!'); }

function formatText(cmd) {
  const ta = document.getElementById('ep-message');
  const start = ta.selectionStart, end = ta.selectionEnd;
  const sel = ta.value.substring(start, end);
  if(!sel) return;
  const markers = {bold:'**', italic:'_', underline:'__'};
  ta.value = ta.value.substring(0,start)+markers[cmd]+sel+markers[cmd]+ta.value.substring(end);
}

function sendEmailNotification() {
  const d = deliveries[currentIdx];
  const to = document.getElementById('ep-to').value;
  const subj = document.getElementById('ep-subject').value;
  const msg = document.getElementById('ep-message').value;
  if(!to || !subj) { alert('Please fill in recipient and subject'); return; }
  const btn = document.querySelector('.ep-confirm-btn');
  btn.textContent = 'Sending…'; btn.disabled = true;
  const fd = new FormData();
  fd.append('delivery_id', d.id); fd.append('recipient', to);
  fd.append('subject', subj); fd.append('message', msg);
  fetch('send_delivery_notification.php', {method:'POST', body:fd})
    .then(r=>r.json())
    .then(data => {
      if(data.success) {
        btn.textContent = '✓ Sent';
        setTimeout(() => { closeEmailComposer(); btn.textContent = 'Send'; btn.disabled = false; }, 1500);
      } else {
        alert('Error: ' + data.message);
        btn.textContent = 'Send'; btn.disabled = false;
      }
    })
    .catch(() => { alert('Error sending email'); btn.textContent = 'Send'; btn.disabled = false; });
}

function openNotifChoice() {
  if(!notifModal) notifModal = new bootstrap.Modal(document.getElementById('notificationChoiceModal'));
  notifModal.show();
}

function selectNotif(type) {
  document.querySelectorAll('.notif-opt').forEach(el => el.classList.remove('sel'));
  event.currentTarget.classList.add('sel');
  document.querySelector(`input[name="notif_type"][value="${type}"]`).checked = true;
}

function handleNotifContinue() {
  const sel = document.querySelector('input[name="notif_type"]:checked');
  if(!sel) { alert('Please select a notification type'); return; }
  notifModal.hide();
  if(sel.value === 'email') setTimeout(openEmailComposer, 300);
  else alert(sel.value.toUpperCase() + ' notification sent');
}

function esc(str) {
  if(!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function toggleStatusDrop(e) {
  e.stopPropagation();
  const drop = document.getElementById('statusDrop');
  drop.style.display = drop.style.display === 'block' ? 'none' : 'block';
}

document.addEventListener('click', function(e) {
  const drop = document.getElementById('statusDrop');
  const btn  = document.getElementById('statusDropBtn');
  if(drop && btn && !drop.contains(e.target) && !btn.contains(e.target)) drop.style.display = 'none';
});

document.addEventListener('keydown', e => { if(e.key === 'Escape') closePanel(); });

document.getElementById('searchInput')?.addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('table.mtbl tbody tr').forEach(row => {
    if(!row.querySelector('td')) return;
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

document.getElementById('filterDateInput')?.addEventListener('change', function() {
  document.getElementById('filterForm').submit();
});

// Mobile sidebar
(function () {
  const body = document.body;
  const toggle = document.getElementById('sidebarToggle');
  const backdrop = document.getElementById('mobileSbBackdrop');
  function closeSidebarOnDesktop() {
    if(window.innerWidth >= 992) body.classList.remove('sb-open');
  }
  if(toggle) toggle.addEventListener('click', () => body.classList.toggle('sb-open'));
  if(backdrop) backdrop.addEventListener('click', () => body.classList.remove('sb-open'));
  window.addEventListener('resize', closeSidebarOnDesktop);
  closeSidebarOnDesktop();
})();
</script>
</body>
