<?php
require_once 'config.php';
require_roles(['admin', 'manager', 'store', 'employee']);

$unread_messages = get_unread_message_count($_SESSION['user_id']);

// ── Sample delivery history data ──
$deliveries = [
  [
    'id'             => 1,
    'order_number'   => '#55658',
    'store_name'     => 'MediSupply Co.',
    'store_address'  => 'Davao City, Philippines 8000',
    'driver_name'    => 'Juan Dela Cruz',
    'driver_address' => 'Davao City, Philippines 8000',
    'order_time'     => '08:35',
    'order_date'     => '2026-05-14',
    'status'         => 'On the way',
    'assigned_date'  => '2026-05-14',
    'items'          => [
      ['name'=>'Paracetamol 500mg x100','qty'=>1,'price'=>300.00],
      ['name'=>'Ibuprofen 200mg x50','qty'=>2,'price'=>107.51],
    ],
    'subtotal' => 515.02, 'tax' => 0.00, 'total' => 515.02,
  ],
  [
    'id'             => 2,
    'order_number'   => '#55659',
    'store_name'     => 'BioTech Solutions',
    'store_address'  => 'Davao City, Philippines 8000',
    'driver_name'    => 'Maria Santos',
    'driver_address' => 'Davao City, Philippines 8000',
    'order_time'     => '10:15',
    'order_date'     => '2026-05-14',
    'status'         => 'Delivered',
    'assigned_date'  => '2026-05-14',
    'items'          => [
      ['name'=>'Lab Gloves (Box of 100)','qty'=>2,'price'=>44.97],
    ],
    'subtotal' => 89.94, 'tax' => 0.00, 'total' => 89.94,
  ],
  [
    'id'             => 3,
    'order_number'   => '#55660',
    'store_name'     => 'PharmaCorp Inc.',
    'store_address'  => 'Quezon City, Philippines',
    'driver_name'    => 'Pedro Reyes',
    'driver_address' => 'Davao City, Philippines 8000',
    'order_time'     => '14:00',
    'order_date'     => '2026-05-03',
    'status'         => 'Delivered',
    'assigned_date'  => '2026-05-03',
    'items'          => [
      ['name'=>'Amoxicillin 500mg x200','qty'=>50,'price'=>105.00],
      ['name'=>'Vitamin C 1000mg x100','qty'=>30,'price'=>100.00],
    ],
    'subtotal' => 8750.00, 'tax' => 0.00, 'total' => 8750.00,
  ],
  [
    'id'             => 4,
    'order_number'   => '#55661',
    'store_name'     => 'LabGear Direct',
    'store_address'  => 'Pasig City, Philippines',
    'driver_name'    => 'Ana Gonzales',
    'driver_address' => 'Davao City, Philippines 8000',
    'order_time'     => '09:00',
    'order_date'     => '2026-04-28',
    'status'         => 'Paused',
    'assigned_date'  => '2026-04-28',
    'items'          => [
      ['name'=>'Safety Goggles','qty'=>10,'price'=>65.50],
      ['name'=>'Lab Coat (Large)','qty'=>5,'price'=>115.72],
    ],
    'subtotal' => 1233.60, 'tax' => 0.00, 'total' => 1233.60,
  ],
  [
    'id'             => 5,
    'order_number'   => '#55662',
    'store_name'     => 'MediSupply Co.',
    'store_address'  => 'Davao City, Philippines 8000',
    'driver_name'    => 'Jose Bautista',
    'driver_address' => 'Davao City, Philippines 8000',
    'order_time'     => '11:30',
    'order_date'     => '2026-04-15',
    'status'         => 'Take back',
    'assigned_date'  => '2026-04-15',
    'items'          => [
      ['name'=>'Sterile Syringes 5ml x50','qty'=>4,'price'=>85.00],
    ],
    'subtotal' => 340.00, 'tax' => 0.00, 'total' => 340.00,
  ],
];

function format_date_label($d) { return date('d/m/Y', strtotime($d)); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>McPIL – Delivery History</title>
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
  --orange:     #c2410c;
  --orange-bg:  #fff0e8;

  /* Midnight blue accent (card headers, detail panel) */
  --mid-blue:        #0d1b4b;
  --mid-blue-2:      #112060;
  --mid-blue-3:      #162880;
  --mid-blue-card:   #0f1e5a;
  --mid-blue-pale:   #e8ecfa;
  --mid-blue-accent: #3b5bdb;

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
  --shadow-card: 0 8px 32px rgba(13,27,75,0.18),0 2px 8px rgba(13,27,75,0.08);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font);background:var(--bg);color:var(--text-1);font-size:13px;line-height:1.55;-webkit-font-smoothing:antialiased}

@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulse-dot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.5)}}
@keyframes slideInRight{from{opacity:0;transform:translateX(24px)}to{opacity:1;transform:translateX(0)}}
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

/* ── PAGE BODY ── */
.page-body{padding:28px 32px;flex:1}

/* ── FILTER BAR ── */
.filter-bar{display:flex;align-items:center;gap:10px;margin-bottom:24px;flex-wrap:wrap}
.search-wrap{display:flex;align-items:center;gap:8px;background:var(--white);border:1px solid var(--border);border-radius:30px;padding:9px 16px;min-width:220px;transition:all .15s;box-shadow:var(--shadow-xs)}
.search-wrap:focus-within{border-color:var(--mid-blue-3);box-shadow:0 0 0 3px rgba(22,40,128,.09)}
.search-wrap i{color:var(--text-4);font-size:12px}
.search-wrap input{border:none;background:transparent;font-size:12.5px;color:var(--text-1);outline:none;font-family:var(--font);width:160px}
.search-wrap input::placeholder{color:var(--text-4)}

.filter-tab{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:24px;font-size:12px;font-weight:600;border:1.5px solid var(--border);background:var(--white);color:var(--text-2);cursor:pointer;transition:all .15s;white-space:nowrap}
.filter-tab:hover{border-color:var(--mid-blue-3);color:var(--mid-blue-3)}
.filter-tab.active{background:var(--mid-blue-card);color:#fff;border-color:var(--mid-blue-card)}
.filter-tab .tab-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}

.date-range-wrap{display:flex;align-items:center;gap:8px;margin-left:auto}
.date-input-pair{display:flex;align-items:center;gap:6px;background:var(--white);border:1px solid var(--border);border-radius:var(--radius-sm);padding:7px 12px;font-size:12px;color:var(--text-2)}
.date-input-pair i{color:var(--text-4);font-size:13px}
.date-input-pair input{border:none;background:transparent;font-size:12px;color:var(--text-2);outline:none;font-family:var(--mono);width:90px}
.date-sep{color:var(--text-4);font-size:13px}
.filter-icon-btn{width:35px;height:35px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--text-3);cursor:pointer;transition:all .15s}
.filter-icon-btn:hover{background:var(--mid-blue-card);color:#fff;border-color:var(--mid-blue-card)}

/* ── MAIN CONTENT SPLIT ── */
.content-split{display:grid;grid-template-columns:1fr 420px;gap:20px;align-items:start}
@media(max-width:1100px){.content-split{grid-template-columns:1fr}}
.detail-col{position:sticky;top:calc(var(--topbar-h) + 28px)}

/* ── DELIVERY CARDS LIST ── */
.cards-list{display:flex;flex-direction:column;gap:14px}

.delivery-card{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);padding:0;overflow:hidden;cursor:pointer;transition:all .2s ease;box-shadow:var(--shadow-xs)}
.delivery-card:hover{border-color:var(--mid-blue-3);box-shadow:var(--shadow)}
.delivery-card.selected{border-color:var(--mid-blue-card);box-shadow:0 0 0 3px rgba(13,27,75,.12),var(--shadow)}

.dc-header{background:var(--mid-blue-card);padding:12px 20px;display:flex;align-items:center;justify-content:space-between}
.dc-order-num{font-size:13px;font-weight:800;color:#fff;font-family:var(--mono);letter-spacing:.04em}
.dc-assigned{font-size:10.5px;color:rgba(255,255,255,0.55);display:flex;align-items:center;gap:5px}
.dc-assigned i{font-size:12px}

.dc-body{padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:12px}
.dc-party{display:flex;align-items:flex-start;gap:10px}
.dc-avatar{width:36px;height:36px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff}
.dc-avatar.store{background:linear-gradient(135deg,#d4241a,#a01810)}
.dc-avatar.driver{background:linear-gradient(135deg,#0d7a48,#065c34)}
.dc-party-label{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--text-4);margin-bottom:2px}
.dc-party-name{font-size:12.5px;font-weight:700;color:var(--text-1);line-height:1.2}
.dc-party-addr{font-size:11px;color:var(--text-3);margin-top:1px}

.dc-footer{padding:10px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface)}
.dc-meta{font-size:11px;color:var(--text-3);display:flex;align-items:center;gap:5px}
.dc-meta i{font-size:12px;color:var(--text-4)}
.dc-actions{display:flex;align-items:center;gap:6px}
.dc-act-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border);background:var(--white);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;transition:all .13s;text-decoration:none;color:var(--text-3)}
.dc-act-btn:hover{background:var(--mid-blue-card);color:#fff;border-color:var(--mid-blue-card)}

/* Status badge */
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:10.5px;font-weight:700;letter-spacing:.04em}
.status-badge::before{content:'';width:6px;height:6px;border-radius:50%;flex-shrink:0}
.s-ontheway    {background:#e8eeff;color:#1645b6}
.s-ontheway::before{background:#1645b6}
.s-delivered   {background:var(--green-bg);color:var(--green)}
.s-delivered::before{background:var(--green)}
.s-paused      {background:var(--amber-bg);color:var(--amber)}
.s-paused::before{background:#d97706}
.s-takeback    {background:var(--red-tint);color:var(--red)}
.s-takeback::before{background:var(--red)}
.s-assigned    {background:var(--mid-blue-pale);color:var(--mid-blue-3)}
.s-assigned::before{background:var(--mid-blue-3)}

/* ── DETAIL PANEL ── */
.detail-panel{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow-card);animation:slideInRight .35s cubic-bezier(.22,1,.36,1) both}

.dp-header{background:var(--mid-blue-card);padding:20px 24px}
.dp-order-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);border-radius:20px;padding:4px 12px;font-size:11px;font-weight:700;color:rgba(255,255,255,0.85);font-family:var(--mono);letter-spacing:.06em;margin-bottom:10px}
.dp-title{font-size:18px;font-weight:800;color:#fff;letter-spacing:-.02em;line-height:1.1;margin-bottom:4px}
.dp-assigned-txt{font-size:11px;color:rgba(255,255,255,0.5);display:flex;align-items:center;gap:5px}

.dp-section{padding:18px 24px;border-bottom:1px solid var(--border)}
.dp-section:last-child{border-bottom:none}
.dp-sec-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:var(--text-4);margin-bottom:12px}

.dp-party-row{display:flex;align-items:center;gap:12px;margin-bottom:10px}
.dp-party-row:last-child{margin-bottom:0}
.dp-av{width:42px;height:42px;border-radius:12px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:800;color:#fff}
.dp-av.store{background:linear-gradient(135deg,#d4241a,#9b1810)}
.dp-av.driver{background:linear-gradient(135deg,#0d7a48,#085533)}
.dp-party-info .label{font-size:9.5px;font-weight:700;color:var(--text-4);text-transform:uppercase;letter-spacing:.1em;margin-bottom:2px}
.dp-party-info .name{font-size:13.5px;font-weight:700;color:var(--text-1);line-height:1.2}
.dp-party-info .addr{font-size:11px;color:var(--text-3);margin-top:1px}
.dp-party-info .order-meta{font-size:11px;color:var(--text-3);margin-top:2px}
.dp-party-contacts{display:flex;gap:7px;margin-top:7px}
.dp-contact-btn{width:30px;height:30px;border-radius:8px;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;transition:all .13s;text-decoration:none}
.dp-contact-btn.phone{color:var(--green);border-color:#a7d9bc}
.dp-contact-btn.phone:hover{background:var(--green-bg)}
.dp-contact-btn.email{color:var(--mid-blue-3);border-color:#c5d6f7}
.dp-contact-btn.email:hover{background:var(--mid-blue-pale)}

/* Status pill in header */
.dp-status-row{display:flex;align-items:center;gap:8px;margin-top:10px}

/* Product items */
.prod-item{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)}
.prod-item:last-child{border-bottom:none}
.prod-icon{width:34px;height:34px;border-radius:8px;background:var(--mid-blue-pale);display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--mid-blue-3);flex-shrink:0}
.prod-name{font-size:12.5px;font-weight:600;color:var(--text-1);line-height:1.2}
.prod-qty{font-size:11px;color:var(--mid-blue-accent);font-weight:600;margin-top:1px}
.prod-price{font-size:13px;font-weight:700;color:var(--text-1);font-family:var(--mono);text-align:right}

/* Price breakdown */
.price-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;font-size:12.5px;border-bottom:1px solid var(--border)}
.price-row:last-of-type{border-bottom:none}
.price-row .label{color:var(--text-3)}
.price-row .val{font-weight:600;font-family:var(--mono);color:var(--text-1)}
.price-total-bar{display:flex;justify-content:space-between;align-items:center;background:var(--mid-blue-card);border-radius:var(--radius-sm);padding:12px 16px;margin-top:10px}
.price-total-bar .label{font-size:13px;font-weight:700;color:rgba(255,255,255,0.85)}
.price-total-bar .val{font-size:16px;font-weight:800;color:#fff;font-family:var(--mono)}

/* Empty state */
.empty-detail{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 24px;text-align:center}
.empty-detail .icon{width:64px;height:64px;border-radius:18px;background:var(--mid-blue-pale);display:flex;align-items:center;justify-content:center;font-size:26px;color:var(--mid-blue-3);margin-bottom:16px}
.empty-detail .title{font-size:15px;font-weight:700;color:var(--text-2);margin-bottom:4px}
.empty-detail .sub{font-size:12px;color:var(--text-4)}

/* Page header */
.page-header{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:20px}
.page-eyebrow{font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--mid-blue-3);margin-bottom:4px}
.page-heading{font-size:22px;font-weight:800;color:var(--text-1);letter-spacing:-.03em;line-height:1}
.page-sub{font-size:12px;color:var(--text-3);margin-top:5px}
</style>
<link rel="stylesheet" href="sidebar-standard.css">
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
    <a class="sb-item" href="purchase_invoice.php"><i class="ti ti-file-invoice"></i>Purchase Invoice</a>
    <?php if (is_admin() || is_manager()): ?>
    <a class="sb-item" href="employee_profile.php"><i class="ti ti-users"></i>Employee Profile</a>
    <a class="sb-item" href="attendance.php"><i class="ti ti-calendar-check"></i>Attendance</a>

    <div class="sb-section">Logistics</div>
    <a class="sb-item" href="delivery_tracking.php"><i class="ti ti-truck-delivery"></i>Delivery Tracking</a>
    <a class="sb-item active" href="delivery_history.php"><i class="ti ti-history"></i>Delivery History</a>
    <?php endif; ?>

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
        <div class="topbar-title">Delivery History</div>
        <div class="topbar-sub">Logistics · Past delivery records</div>
      </div>
    </div>
    <div class="topbar-right">
      <div class="tb-icon-btn" style="background:var(--mid-blue-pale);border-color:#c5d6f7;color:var(--mid-blue-3)" title="Online">
        <i class="fas fa-circle" style="font-size:8px;color:#22c55e"></i>
      </div>
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
    </div>
  </header>

  <div class="page-body">

    <!-- Page Header -->
    <div class="page-header fade-up">
      <div>
        <div class="page-eyebrow">Logistics</div>
        <div class="page-heading">
          Delivery History
          <span style="font-size:16px;font-weight:400;color:var(--text-4);margin-left:8px"><?php echo count($deliveries); ?> records</span>
        </div>
        <div class="page-sub">Track all past and ongoing deliveries. Click any record to view full details.</div>
      </div>
      <div style="font-size:11px;color:var(--text-4);font-family:var(--mono)">
        <?php echo date('l, d F Y'); ?>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar fade-up fade-up-1">
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search order, driver, store…" oninput="filterCards()">
      </div>

      <button class="filter-tab active" data-status="all" onclick="setStatusFilter(this,'all')">
        <span class="tab-dot" style="background:var(--text-3)"></span> All
      </button>
      <button class="filter-tab" data-status="assigned" onclick="setStatusFilter(this,'assigned')">
        <span class="tab-dot" style="background:var(--mid-blue-3)"></span> Assigned
      </button>
      <button class="filter-tab" data-status="on the way" onclick="setStatusFilter(this,'on the way')">
        <span class="tab-dot" style="background:#1645b6"></span> Out for Delivery
      </button>
      <button class="filter-tab" data-status="paused" onclick="setStatusFilter(this,'paused')">
        <span class="tab-dot" style="background:#d97706"></span> Paused
      </button>
      <button class="filter-tab" data-status="take back" onclick="setStatusFilter(this,'take back')">
        <span class="tab-dot" style="background:var(--red)"></span> Take Back
      </button>

      <div class="date-range-wrap">
        <div class="date-input-pair">
          <i class="ti ti-calendar"></i>
          <input type="date" id="dateFrom" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" oninput="filterCards()">
        </div>
        <div class="date-sep"><i class="fas fa-arrow-right" style="font-size:10px;color:var(--text-4)"></i></div>
        <div class="date-input-pair">
          <i class="ti ti-calendar"></i>
          <input type="date" id="dateTo" value="<?php echo date('Y-m-d'); ?>" oninput="filterCards()">
        </div>
        <div class="filter-icon-btn" title="Apply filter" onclick="filterCards()">
          <i class="ti ti-filter" style="font-size:14px"></i>
        </div>
      </div>
    </div>

    <!-- Split Layout -->
    <div class="content-split fade-up fade-up-2">

      <!-- Cards List -->
      <div>
        <div class="cards-list" id="cardsList">
          <?php foreach ($deliveries as $d):
            $skey = strtolower(str_replace([' ','-'], '', $d['status']));
          ?>
          <div class="delivery-card"
               data-id="<?php echo $d['id']; ?>"
               data-status="<?php echo strtolower($d['status']); ?>"
               data-date="<?php echo $d['order_date']; ?>"
               onclick="selectCard(this, <?php echo $d['id']; ?>)">

            <div class="dc-header">
              <div class="dc-order-num"><?php echo htmlspecialchars($d['order_number']); ?></div>
              <div style="display:flex;align-items:center;gap:10px">
                <span class="status-badge s-<?php echo $skey; ?>"><?php echo htmlspecialchars($d['status']); ?></span>
                <div class="dc-assigned"><i class="ti ti-calendar-event"></i> <?php echo format_date($d['assigned_date']); ?></div>
              </div>
            </div>

            <div class="dc-body">
              <div class="dc-party">
                <div class="dc-avatar store"><?php echo strtoupper(substr($d['store_name'],0,1)); ?></div>
                <div>
                  <div class="dc-party-label">Store Details</div>
                  <div class="dc-party-name"><?php echo htmlspecialchars($d['store_name']); ?></div>
                  <div class="dc-party-addr"><?php echo htmlspecialchars($d['store_address']); ?></div>
                </div>
              </div>
              <div class="dc-party">
                <div class="dc-avatar driver"><?php echo strtoupper(substr($d['driver_name'],0,1)); ?></div>
                <div>
                  <div class="dc-party-label">Driver Details</div>
                  <div class="dc-party-name"><?php echo htmlspecialchars($d['driver_name']); ?></div>
                  <div class="dc-party-addr"><?php echo htmlspecialchars($d['driver_address']); ?></div>
                </div>
              </div>
            </div>

            <div class="dc-footer">
              <div class="dc-meta">
                <i class="ti ti-clock"></i>
                Order at <?php echo $d['order_time']; ?> &nbsp;·&nbsp;
                <i class="ti ti-calendar"></i>
                <?php echo format_date($d['order_date']); ?>
              </div>
              <div class="dc-actions">
                <a class="dc-act-btn" title="Call" href="tel:"><i class="fas fa-phone"></i></a>
                <a class="dc-act-btn" title="Email" href="mailto:"><i class="fas fa-envelope"></i></a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div id="noResults" style="display:none;text-align:center;padding:48px 20px;color:var(--text-4)">
          <i class="ti ti-search" style="font-size:36px;display:block;margin-bottom:10px"></i>
          No deliveries found matching your filters.
        </div>
      </div>

      <!-- Detail Panel -->
      <div class="detail-col">
        <div class="detail-panel" id="detailPanel">
          <div class="empty-detail" id="emptyDetail">
            <div class="icon"><i class="ti ti-truck-delivery"></i></div>
            <div class="title">Select a delivery</div>
            <div class="sub">Click any record on the left to view its full details here.</div>
          </div>
          <div id="detailContent" style="display:none"></div>
        </div>
      </div>

    </div>
  </div><!-- /page-body -->
</div><!-- /main-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Data (mirrors PHP) ──
const deliveries = <?php echo json_encode($deliveries); ?>;

const statusColors = {
  'on the way': {bg:'#e8eeff',color:'#1645b6'},
  'delivered':  {bg:'#e6f4ee',color:'#0d7a48'},
  'paused':     {bg:'#fff4de',color:'#875200'},
  'take back':  {bg:'#fdecea',color:'#d4241a'},
  'assigned':   {bg:'#e8ecfa',color:'#162880'},
};
const statusClass = {
  'on the way':'s-ontheway','delivered':'s-delivered','paused':'s-paused','take back':'s-takeback','assigned':'s-assigned'
};

function fmt(n) { return '₱'+Number(n).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function fmtDate(d){return new Date(d+'T00:00:00').toLocaleDateString('en-PH',{year:'numeric',month:'long',day:'numeric'});}

let activeStatus = 'all';

function setStatusFilter(el, status) {
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  activeStatus = status;
  filterCards();
}

function filterCards() {
  const q       = document.getElementById('searchInput').value.toLowerCase();
  const fromVal = document.getElementById('dateFrom').value;
  const toVal   = document.getElementById('dateTo').value;
  const fromD   = fromVal ? new Date(fromVal+'T00:00:00') : null;
  const toD     = toVal   ? new Date(toVal+'T23:59:59')   : null;

  let visible = 0;
  document.querySelectorAll('.delivery-card').forEach(card => {
    const status = card.dataset.status;
    const date   = card.dataset.date;
    const text   = card.textContent.toLowerCase();

    const statusOk = activeStatus === 'all' || status.includes(activeStatus);
    const searchOk = !q || text.includes(q);
    let dateOk = true;
    if (date && (fromD || toD)) {
      const d = new Date(date+'T00:00:00');
      if (fromD && d < fromD) dateOk = false;
      if (toD   && d > toD)   dateOk = false;
    }
    const show = statusOk && searchOk && dateOk;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  document.getElementById('noResults').style.display = visible === 0 ? 'flex' : 'none';
  document.getElementById('noResults').style.flexDirection = 'column';
  document.getElementById('noResults').style.alignItems = 'center';
}

function selectCard(el, id) {
  document.querySelectorAll('.delivery-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');

  const d = deliveries.find(x => x.id === id);
  if (!d) return;

  const sc    = d.status.toLowerCase();
  const sClass = statusClass[sc] || 's-assigned';
  const col   = statusColors[sc] || {bg:'#e8ecfa',color:'#162880'};

  let itemsHtml = '';
  d.items.forEach(item => {
    itemsHtml += `
      <div class="prod-item">
        <div style="display:flex;align-items:center;gap:10px">
          <div class="prod-icon"><i class="ti ti-pill"></i></div>
          <div>
            <div class="prod-name">${item.name}</div>
            <div class="prod-qty">${item.qty} x ${fmt(item.price)}</div>
          </div>
        </div>
        <div class="prod-price">${fmt(item.price * item.qty)}</div>
      </div>`;
  });

  const html = `
    <div class="dp-header">
      <div class="dp-order-badge"><i class="ti ti-package" style="font-size:12px"></i> ${d.order_number}</div>
      <div class="dp-title">${d.store_name}</div>
      <div class="dp-assigned-txt"><i class="ti ti-calendar-check"></i> Assigned ${fmtDate(d.assigned_date)}</div>
      <div class="dp-status-row">
        <span class="status-badge ${sClass}">${d.status}</span>
      </div>
    </div>

    <div class="dp-section">
      <div class="dp-sec-title">Store Details</div>
      <div class="dp-party-row">
        <div class="dp-av store">${d.store_name.charAt(0).toUpperCase()}</div>
        <div class="dp-party-info">
          <div class="label">Store</div>
          <div class="name">${d.store_name}</div>
          <div class="addr">${d.store_address}</div>
        </div>
      </div>
    </div>

    <div class="dp-section">
      <div class="dp-sec-title">Customer Details</div>
      <div class="dp-party-row">
        <div class="dp-av driver">${d.driver_name.charAt(0).toUpperCase()}</div>
        <div class="dp-party-info">
          <div class="label">Driver</div>
          <div class="name">${d.driver_name}</div>
          <div class="addr">${d.driver_address}</div>
          <div class="order-meta">Order at ${d.order_time} &nbsp;·&nbsp; ${fmtDate(d.order_date)}</div>
          <div class="dp-party-contacts">
            <a class="dp-contact-btn phone" href="tel:" title="Call"><i class="fas fa-phone"></i></a>
            <a class="dp-contact-btn email" href="mailto:" title="Email"><i class="fas fa-envelope"></i></a>
          </div>
          <div style="margin-top:8px">
            <span class="status-badge ${sClass}">Status: ${d.status}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="dp-section">
      <div class="dp-sec-title">Product Details</div>
      ${itemsHtml}
    </div>

    <div class="dp-section">
      <div class="dp-sec-title">Product Price</div>
      <div class="price-row"><span class="label">Product Price</span><span class="val">${fmt(d.subtotal)}</span></div>
      <div class="price-row"><span class="label">Tax</span><span class="val">+ ${fmt(d.tax)}</span></div>
      <div class="price-total-bar">
        <span class="label">Total</span>
        <span class="val">${fmt(d.total)}</span>
      </div>
    </div>`;

  const content = document.getElementById('detailContent');
  const empty   = document.getElementById('emptyDetail');
  content.innerHTML = html;
  empty.style.display   = 'none';
  content.style.display = 'block';
}

// Sidebar toggle
(function () {
  const body     = document.body;
  const toggle   = document.getElementById('sidebarToggle');
  const backdrop = document.getElementById('mobileSbBackdrop');
  function closeSidebarOnDesktop() {
    if (window.innerWidth >= 992) body.classList.remove('sb-open');
  }
  if (toggle)   toggle.addEventListener('click', () => body.classList.toggle('sb-open'));
  if (backdrop) backdrop.addEventListener('click', () => body.classList.remove('sb-open'));
  window.addEventListener('resize', closeSidebarOnDesktop);
  closeSidebarOnDesktop();
})();

// Auto-select first card
window.addEventListener('DOMContentLoaded', () => {
  const first = document.querySelector('.delivery-card');
  if (first) selectCard(first, parseInt(first.dataset.id));
});
</script>
</body>
</html>
