<?php
require_once 'config.php';

require_login();

$stats = get_dashboard_stats();
$recent_orders = get_purchase_orders(5);
$recent_deliveries = get_deliveries(null, 5);
$all_purchase_orders = get_purchase_orders(100);
$all_deliveries = get_deliveries(null, 100);
$all_invoices = get_purchase_invoices_admin(100);
$unread_notifications = (is_admin() || is_manager()) ? get_unread_attendance_notifications(10) : [];
$unread_messages = get_unread_message_count($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo APP_NAME; ?> – Dashboard</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root {
  --navy:       #0d1b3e;
  --navy-2:     #1a2a5e;
  --navy-3:     #243580;
  --sb-bg:      #0d1b3e;
  --sb-border:  rgba(255,255,255,.07);
  --sb-label:   rgba(255,255,255,.30);
  --sb-text:    rgba(255,255,255,.68);
  --sb-text-act:#ffffff;
  --sb-active:  rgba(255,255,255,.11);
  --sb-hover:   rgba(255,255,255,.06);
  --sb-w:       220px;
  --topbar-h:   56px;
  --bg:         #f0f2f9;
  --surface:    #ffffff;
  --border:     #e4e7f2;
  --border-2:   #cdd1e8;
  --text-1:     #0d1030;
  --text-2:     #3a4066;
  --text-3:     #7b809e;
  --text-4:     #b0b4cc;
  --blue:       #1645b6;
  --blue-bg:    #e8eeff;
  --green:      #0d7a48;
  --green-bg:   #e6f4ee;
  --amber:      #875200;
  --amber-bg:   #fff4de;
  --red:        #c9221a;
  --red-bg:     #fdecea;
  --violet:     #5b3fc4;
  --violet-bg:  #eeebfa;
  --r:          10px;
  --r-sm:       7px;
  --sh:         0 1px 3px rgba(10,16,69,.06), 0 0 0 0.5px rgba(10,16,69,.05);
  --sh-md:      0 4px 16px rgba(10,16,69,.08), 0 1px 4px rgba(10,16,69,.04);
  --font:       'Sora', sans-serif;
  --mono:       'DM Mono', monospace;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: var(--font);
  background: var(--bg);
  color: var(--text-1);
  font-size: 12.5px;
  line-height: 1.5;
  -webkit-font-smoothing: antialiased;
}

/* ── SIDEBAR ── */
.sidebar {
  position: fixed; top: 0; left: 0;
  width: var(--sb-w); height: 100vh;
  background: var(--sb-bg);
  display: flex; flex-direction: column;
  z-index: 9999; overflow-y: auto; overflow-x: hidden; scrollbar-width: none;
}
.sidebar::-webkit-scrollbar { display: none; }
.sb-logo {
  display: flex; align-items: center; gap: 10px;
  padding: 16px 14px 14px;
  border-bottom: 1px solid var(--sb-border); flex-shrink: 0;
}
.sb-logo-ring {
  width: 34px; height: 34px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,.25);
  overflow: hidden; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: #1a2a5e;
}
.sb-logo-ring img { width: 100%; height: 100%; object-fit: cover; display: block; }
.sb-logo-fallback { font-size: 10px; font-weight: 800; color: #fff; display: none; }
.sb-brand-name { font-size: 12.5px; font-weight: 800; color: #fff; letter-spacing: .06em; text-transform: uppercase; line-height: 1.2; }
.sb-brand-sub { font-size: 9px; color: rgba(255,255,255,.36); letter-spacing: .09em; text-transform: uppercase; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
.sb-nav { flex: 1; padding: 5px 9px 4px; }
.sb-section { font-size: 9px; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; color: var(--sb-label); padding: 12px 7px 4px; }
.sb-item { display: flex; align-items: center; gap: 8px; padding: 7px 9px; border-radius: 8px; color: var(--sb-text); text-decoration: none; font-size: 12px; font-weight: 500; transition: background .13s, color .13s; margin-bottom: 1px; position: relative; cursor: pointer; }
.sb-item:hover { background: var(--sb-hover); color: var(--sb-text-act); text-decoration: none; }
.sb-item.active { background: var(--sb-active); color: var(--sb-text-act); font-weight: 600; }
.sb-item i { font-size: 17px; flex-shrink: 0; width: 19px; text-align: center; line-height: 1; }
.sb-badge { position: absolute; right: 9px; top: 50%; transform: translateY(-50%); background: #e5534b; color: #fff; font-size: 9px; font-weight: 700; min-width: 15px; height: 15px; border-radius: 50%; display: flex; align-items: center; justify-content: center; padding: 0 3px; }
.sb-item.sb-logout { color: rgba(239,68,68,.72); }
.sb-item.sb-logout i { color: rgba(239,68,68,.80); }
.sb-item.sb-logout:hover { background: rgba(239,68,68,.10); color: #ef4444; }
.sb-footer { flex-shrink: 0; padding: 4px 9px 16px; border-top: 1px solid var(--sb-border); }

/* ── LAYOUT ── */
.main { margin-left: var(--sb-w); min-height: 100vh; display: flex; flex-direction: column; }

/* ── TOPBAR ── */
.topbar { height: var(--topbar-h); background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 20px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
.topbar-title { font-size: 15px; font-weight: 700; color: var(--text-1); letter-spacing: -.025em; }
.topbar-sub { font-size: 11px; color: var(--text-4); margin-top: 1px; }
.topbar-right { display: flex; align-items: center; gap: 7px; }
.search-box { display: flex; align-items: center; gap: 7px; background: var(--bg); border: 0.5px solid var(--border-2); border-radius: 8px; padding: 6px 10px; transition: border-color .15s; }
.search-box:focus-within { border-color: var(--navy-2); }
.search-box input { border: none; background: transparent; outline: none; font-family: var(--font); font-size: 11.5px; color: var(--text-1); width: 150px; }
.search-box input::placeholder { color: var(--text-4); }
.kbd { background: var(--border); border-radius: 3px; padding: 1px 5px; font-family: var(--mono); font-size: 9.5px; color: var(--text-3); }
.tb-btn { width: 30px; height: 30px; border-radius: 7px; background: var(--bg); border: 0.5px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--text-3); cursor: pointer; font-size: 15px; transition: all .13s; position: relative; }
.tb-btn:hover { background: var(--blue-bg); color: var(--blue); border-color: var(--blue); }
.notif-dot { position: absolute; top: 6px; right: 6px; width: 5px; height: 5px; border-radius: 50%; background: #e5534b; border: 1.5px solid var(--surface); }
.btn-primary { display: inline-flex; align-items: center; gap: 5px; background: var(--navy); color: #fff; border: none; border-radius: 8px; padding: 7px 13px; font-size: 11.5px; font-weight: 700; cursor: pointer; font-family: var(--font); transition: all .15s; text-decoration: none; white-space: nowrap; }
.btn-primary:hover { background: var(--navy-2); transform: translateY(-1px); color: #fff; }

/* ── PROFILE DROPDOWN ── */
.profile-wrap { position: relative; }
.user-chip { display: flex; align-items: center; gap: 8px; padding: 4px 10px 4px 4px; border: 0.5px solid var(--border); border-radius: 40px; cursor: pointer; background: var(--surface); transition: all .15s; user-select: none; }
.user-chip:hover { border-color: var(--border-2); box-shadow: 0 2px 8px rgba(10,16,69,.08); }
.user-chip.open { border-color: var(--navy-2); box-shadow: 0 0 0 3px rgba(22,69,182,.08); }
.u-avatar { width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, var(--navy-2), var(--navy-3)); color: #fff; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
.u-avatar img { width: 100%; height: 100%; object-fit: cover; }
.u-info { line-height: 1.2; }
.u-name { font-size: 12px; font-weight: 700; color: var(--text-1); white-space: nowrap; }
.u-role { font-size: 9.5px; color: var(--text-4); white-space: nowrap; }
.u-chevron { font-size: 10px; color: var(--text-3); transition: transform .2s; margin-left: 2px; }
.user-chip.open .u-chevron { transform: rotate(180deg); }
.profile-dropdown { position: absolute; top: calc(100% + 8px); right: 0; min-width: 210px; background: var(--surface); border: 0.5px solid var(--border); border-radius: 12px; box-shadow: 0 12px 40px rgba(10,16,69,.13), 0 2px 8px rgba(10,16,69,.06); z-index: 9999; overflow: hidden; opacity: 0; transform: translateY(8px) scale(.97); pointer-events: none; transition: opacity .18s ease, transform .18s ease; }
.profile-dropdown.open { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
.pd-header { display: flex; align-items: center; gap: 10px; padding: 14px 16px 12px; border-bottom: 0.5px solid var(--border); }
.pd-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, var(--navy-2), var(--navy-3)); color: #fff; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; }
.pd-avatar img { width: 100%; height: 100%; object-fit: cover; }
.pd-name { font-size: 12.5px; font-weight: 700; color: var(--text-1); line-height: 1.25; }
.pd-email { font-size: 10.5px; color: var(--text-3); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
.pd-menu { padding: 5px 7px; }
.pd-item { display: flex; align-items: center; gap: 10px; padding: 8px 11px; border-radius: 8px; color: var(--text-2); text-decoration: none; font-size: 12px; font-weight: 500; transition: background .13s, color .13s; cursor: pointer; border: none; background: none; width: 100%; font-family: var(--font); }
.pd-item:hover { background: var(--bg); color: var(--text-1); text-decoration: none; }
.pd-item i { font-size: 15px; color: var(--text-3); width: 18px; text-align: center; transition: color .13s; }
.pd-item:hover i { color: var(--blue); }
.pd-divider { height: 0.5px; background: var(--border); margin: 3px 7px; }
.pd-item.logout { color: rgba(201,34,26,.8); }
.pd-item.logout i { color: rgba(201,34,26,.6); }
.pd-item.logout:hover { background: var(--red-bg); color: var(--red); }
.pd-item.logout:hover i { color: var(--red); }

/* ── CONTENT ── */
.content { padding: 16px 18px 22px; flex: 1; }

/* ── KPI GRID ── */
.kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; margin-bottom: 12px; }
.kpi-card { background: var(--surface); border: 0.5px solid var(--border); border-radius: var(--r); padding: 14px 16px; position: relative; overflow: hidden; transition: all .2s; cursor: default; box-shadow: var(--sh); }
.kpi-card::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2.5px; border-radius: var(--r) var(--r) 0 0; }
.kpi-card.k-blue::after  { background: var(--blue); }
.kpi-card.k-green::after { background: var(--green); }
.kpi-card.k-amber::after { background: #d97706; }
.kpi-card.k-red::after   { background: var(--red); }
.kpi-card:hover { transform: translateY(-2px); box-shadow: var(--sh-md); }
.kpi-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; margin-bottom: 9px; }
.kpi-icon.i-blue   { background: var(--blue-bg);  color: var(--blue); }
.kpi-icon.i-green  { background: var(--green-bg); color: var(--green); }
.kpi-icon.i-amber  { background: var(--amber-bg); color: #d97706; }
.kpi-icon.i-red    { background: var(--red-bg);   color: var(--red); }
.kpi-val { font-size: 22px; font-weight: 800; color: var(--text-1); letter-spacing: -.03em; font-variant-numeric: tabular-nums; line-height: 1; }
.kpi-label { font-size: 10px; color: var(--text-3); text-transform: uppercase; letter-spacing: .08em; font-weight: 700; margin-top: 3px; }
.kpi-chip { display: inline-flex; align-items: center; gap: 3px; font-size: 9.5px; font-weight: 700; padding: 2px 6px; border-radius: 20px; margin-top: 6px; }
.kpi-chip.up { background: var(--green-bg); color: var(--green); }
.kpi-chip.dn { background: var(--red-bg);   color: var(--red); }

/* ── ROW A ── */
.row-a { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 12px; }

/* ── CARD BASE ── */
.card { background: var(--surface); border: 0.5px solid var(--border); border-radius: var(--r); box-shadow: var(--sh); }
.card-head { display: flex; align-items: flex-start; justify-content: space-between; padding: 12px 14px 0; }
.card-title { font-size: 12.5px; font-weight: 700; color: var(--text-1); }
.card-sub   { font-size: 10.5px; color: var(--text-4); margin-top: 1px; }
.period-btn { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; color: var(--text-2); background: var(--bg); border: 0.5px solid var(--border); border-radius: 6px; padding: 4px 8px; cursor: pointer; font-family: var(--font); white-space: nowrap; }
.period-btn:hover { border-color: var(--border-2); background: #eceef7; }

/* ── DONUT ── */
.donut-body { padding: 10px 14px 14px; display: flex; align-items: center; gap: 14px; }
.donut-wrap { position: relative; width: 92px; height: 92px; flex-shrink: 0; }
.donut-wrap canvas { width: 92px !important; height: 92px !important; }
.donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; }
.donut-center-val { font-size: 11px; font-weight: 700; color: var(--text-1); line-height: 1; }
.donut-center-sub { font-size: 8.5px; color: var(--text-3); margin-top: 2px; }
.legend-rows { flex: 1; display: flex; flex-direction: column; gap: 7px; }
.leg-row { display: flex; align-items: center; gap: 6px; }
.leg-dot  { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.leg-label { font-size: 10.5px; color: var(--text-2); width: 52px; }
.leg-bar  { flex: 1; height: 3px; background: #f0f2f9; border-radius: 4px; overflow: hidden; }
.leg-fill { height: 100%; border-radius: 4px; }
.leg-pct  { font-size: 10.5px; font-weight: 700; color: var(--text-1); min-width: 26px; text-align: right; }

/* ── CALENDAR ── */
.cal-wrap { padding: 10px 12px 12px; }
.cal-dow { display: grid; grid-template-columns: repeat(7,1fr); text-align: center; margin-bottom: 3px; }
.cal-dow span { font-size: 8.5px; font-weight: 700; color: var(--text-4); padding: 2px 0; letter-spacing: .04em; }
.cal-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 1px; }
.cal-cell { position: relative; display: flex; flex-direction: column; align-items: center; padding: 2px 1px; border-radius: 6px; cursor: pointer; transition: background .12s; min-height: 28px; }
.cal-cell:hover { background: var(--blue-bg); }
.cal-num { font-size: 10px; font-weight: 500; color: var(--text-1); width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
.cal-cell.today    .cal-num { background: var(--navy); color: #fff; }
.cal-cell.selected .cal-num { background: var(--blue); color: #fff; }
.cal-cell.other    .cal-num { color: var(--text-4); }
.cal-dots { display: flex; gap: 2px; justify-content: center; margin-top: 1px; }
.cal-dot  { width: 3.5px; height: 3.5px; border-radius: 50%; }

/* ── DELIVERY MODAL ── */
.dm-overlay { display: none; position: fixed; inset: 0; z-index: 99999; align-items: center; justify-content: center; padding: 16px; }
.dm-overlay.open { display: flex; }
.dm-backdrop { position: absolute; inset: 0; background: rgba(11,20,55,.45); backdrop-filter: blur(3px); }
.dm-box { position: relative; background: #fff; border-radius: 16px; width: 100%; max-width: 390px; box-shadow: 0 20px 60px rgba(0,0,0,.18); overflow: hidden; animation: dmIn .22s ease; }
@keyframes dmIn { from { opacity: 0; transform: translateY(14px) scale(.97); } to { opacity: 1; transform: none; } }
.dm-header { display: flex; align-items: flex-start; justify-content: space-between; padding: 18px 20px 12px; border-bottom: 1px solid var(--border); }
.dm-title { font-size: .95rem; font-weight: 700; color: var(--text-1); }
.dm-sub   { font-size: .72rem; color: var(--text-3); margin-top: 3px; }
.dm-close { width: 28px; height: 28px; border-radius: 50%; border: none; background: var(--bg); color: var(--text-2); font-size: .75rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .15s; flex-shrink: 0; }
.dm-close:hover { background: var(--red-bg); color: var(--red); }
.dm-body { max-height: 320px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: var(--border) transparent; }
.dm-item { display: flex; align-items: center; padding: 12px 20px; border-bottom: 1px solid var(--border); transition: background .12s; }
.dm-item:last-child { border-bottom: none; }
.dm-item:hover { background: #f8faff; }
.dm-item-bar { width: 4px; min-height: 40px; border-radius: 4px; flex-shrink: 0; margin-right: 12px; align-self: stretch; }
.dm-item-ico { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0; margin-right: 11px; }
.dm-item-body { flex: 1; min-width: 0; }
.dm-item-num  { font-family: var(--mono); font-size: .75rem; font-weight: 700; color: var(--text-1); }
.dm-item-time { font-size: .66rem; color: var(--text-3); margin-top: 2px; }
.dm-item-sup  { font-size: .68rem; color: var(--text-2); margin-top: 3px; font-weight: 500; }
.dm-item-pill { display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px; border-radius: 20px; font-size: .60rem; font-weight: 600; margin-top: 4px; }
.dm-footer { padding: 12px 20px; border-top: 1px solid var(--border); background: var(--bg); }
.dm-link-btn { display: inline-flex; align-items: center; gap: 6px; font-size: .75rem; font-weight: 600; color: var(--blue); text-decoration: none; }
.dm-link-btn:hover { opacity: .75; }

/* ── ROW B ── */
.row-b { display: grid; grid-template-columns: 1fr 1fr 230px; gap: 10px; margin-bottom: 12px; }
.chart-card { padding: 12px 14px; }
.chart-total { font-size: 10.5px; color: var(--text-3); margin-bottom: 2px; }
.chart-total strong { font-size: 18px; font-weight: 800; color: var(--text-1); letter-spacing: -.03em; margin-right: 4px; }

/* Top Employees */
.emp-card { padding: 12px 14px; }
.emp-item { display: flex; align-items: center; gap: 9px; padding: 7px 0; border-bottom: 0.5px solid var(--border); }
.emp-item:last-child { border-bottom: none; }
.emp-avatar { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; font-weight: 700; flex-shrink: 0; }
.emp-name { font-size: 12px; font-weight: 700; color: var(--text-1); }
.emp-meta { font-size: 10px; color: var(--text-3); margin-top: 1px; }
.emp-badge { margin-left: auto; font-size: .9rem; }

/* ── ROW C ── */
.row-c { display: grid; grid-template-columns: 1fr 248px 220px; gap: 10px; }

/* Table */
.tbl-wrap { overflow: hidden; }
.tbl-toolbar { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-bottom: 0.5px solid var(--border); }
table.dt { width: 100%; border-collapse: collapse; }
table.dt thead th { font-size: 9.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--text-3); padding: 7px 12px; text-align: left; border-bottom: 0.5px solid var(--border); background: var(--bg); white-space: nowrap; }
table.dt thead th:first-child { padding-left: 14px; }
table.dt tbody td { padding: 9px 12px; font-size: 11.5px; color: var(--text-2); border-bottom: 0.5px solid var(--border); vertical-align: middle; }
table.dt tbody td:first-child { padding-left: 14px; }
table.dt tbody tr:last-child td { border-bottom: none; }
table.dt tbody tr:hover td { background: #fafbff; }
.po-num   { font-family: var(--mono); font-size: 10.5px; color: var(--blue); font-weight: 500; }
.cell-name { font-weight: 600; color: var(--text-1); }
.cell-amt  { font-family: var(--mono); font-size: 11.5px; font-weight: 700; color: var(--text-1); }
.pill { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 5px; font-size: 9.5px; font-weight: 700; }
.pill::before { content: ''; width: 4px; height: 4px; border-radius: 50%; }
.pill.s  { background: var(--green-bg); color: var(--green); } .pill.s::before  { background: var(--green); }
.pill.p  { background: var(--amber-bg); color: var(--amber); } .pill.p::before  { background: #d97706; }
.pill.d  { background: var(--red-bg);   color: var(--red);   } .pill.d::before  { background: var(--red); }
.pill.i  { background: var(--blue-bg);  color: var(--blue);  } .pill.i::before  { background: var(--blue); }
.view-btn { font-size: 9.5px; font-weight: 700; padding: 3px 9px; border-radius: 5px; border: 0.5px solid var(--border); background: var(--surface); color: var(--text-2); cursor: pointer; font-family: var(--font); transition: all .13s; }
.view-btn:hover { background: var(--navy); color: #fff; border-color: var(--navy); }

/* Gauge */
.gauge-card { padding: 12px 14px; display: flex; flex-direction: column; }
.gauge-wrap { position: relative; display: flex; justify-content: center; margin: 6px 0 2px; }
.gauge-center { position: absolute; bottom: 5px; left: 50%; transform: translateX(-50%); text-align: center; white-space: nowrap; }
.gauge-val { font-size: 20px; font-weight: 800; color: var(--text-1); line-height: 1; }
.gauge-lbl { font-size: 9px; color: var(--text-3); }
.g-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; margin-top: 8px; }
.g-item { background: var(--bg); border-radius: 7px; padding: 8px 10px; }
.g-item-lbl { font-size: 9.5px; color: var(--text-3); display: flex; align-items: center; gap: 4px; margin-bottom: 2px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.g-item-val { font-size: 14px; font-weight: 800; color: var(--text-1); }

/* Activity */
.act-item { display: flex; align-items: flex-start; gap: 9px; padding: 9px 14px; border-bottom: 0.5px solid var(--border); }
.act-item:last-child { border-bottom: none; }
.act-ico { width: 27px; height: 27px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
.act-title { font-size: 11.5px; font-weight: 700; color: var(--text-1); line-height: 1.3; }
.act-desc  { font-size: 10px; color: var(--text-3); margin-top: 1.5px; line-height: 1.4; }
.act-time  { font-size: 9.5px; color: var(--text-4); margin-top: 2.5px; display: flex; align-items: center; gap: 4px; }

/* Shared logistics dashboard */
.content > .kpi-grid, .content > .row-a, .content > .row-b, .content > .row-c, .content > .dm-overlay { display:none; }
.list-dashboard { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; align-items:start; }
.list-dashboard-head { grid-column:1/-1; display:flex; justify-content:space-between; align-items:flex-end; margin:2px 0 2px; }
.list-dashboard-title { font-size:20px; font-weight:800; letter-spacing:-.035em; }
.list-dashboard-sub { color:var(--text-3); font-size:11px; margin-top:3px; }
.list-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--r); overflow:hidden; box-shadow:var(--sh); }
.list-card-head { padding:14px 15px 12px; display:flex; align-items:center; gap:9px; border-bottom:1px solid var(--border); }
.list-card-icon { width:30px; height:30px; border-radius:8px; display:grid; place-items:center; background:var(--blue-bg); color:var(--blue); }
.list-card h2 { font-size:13px; font-weight:750; margin:0; }
.list-card-count { margin-left:auto; font-size:10px; font-weight:700; color:var(--text-3); background:var(--bg); padding:3px 7px; border-radius:999px; }
.list-table-wrap { max-height:510px; overflow:auto; }
.list-table { width:100%; border-collapse:collapse; }
.list-table th { position:sticky; top:0; z-index:1; background:var(--bg); color:var(--text-3); font-size:9px; text-transform:uppercase; letter-spacing:.07em; text-align:left; padding:8px 12px; }
.list-table td { padding:10px 12px; border-top:1px solid var(--border); font-size:11px; color:var(--text-2); vertical-align:middle; }
.list-table tr:hover td { background:#fafbff; }
.list-table .ref { font-family:var(--mono); font-size:10px; font-weight:700; color:var(--blue); white-space:nowrap; }
.list-table .name { font-weight:650; color:var(--text-1); max-width:135px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.list-card-foot { display:block; padding:10px 14px; color:var(--blue); font-size:11px; font-weight:700; text-decoration:none; border-top:1px solid var(--border); }
.list-card-foot:hover { background:var(--blue-bg); color:var(--blue); }

/* ── MOBILE ── */
.mob-toggle { display: none; width: 30px; height: 30px; border-radius: 7px; background: var(--bg); border: 0.5px solid var(--border); align-items: center; justify-content: center; color: var(--text-2); cursor: pointer; font-size: 15px; }
.sb-backdrop { display: none; position: fixed; inset: 0; background: rgba(11,20,55,.45); opacity: 0; pointer-events: none; transition: opacity .3s; z-index: 9998; }
@media (max-width: 991px) {
  .sidebar { transform: translateX(-100%); transition: transform .3s ease; }
  body.sb-open .sidebar { transform: translateX(0); }
  .main { margin-left: 0; }
  .mob-toggle { display: flex; }
  .sb-backdrop { display: block; }
  body.sb-open .sb-backdrop { opacity: 1; pointer-events: auto; }
  .row-a, .row-b, .row-c { grid-template-columns: 1fr; }
  .list-dashboard { grid-template-columns:1fr; }
  .kpi-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 600px) {
  .search-box { display: none; }
  .topbar { padding: 0 12px; }
  .content { padding: 12px 12px 18px; }
  .kpi-grid { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body class="list-dashboard-page">

<!-- ═══════ SIDEBAR ═══════ -->
<nav id="sidebar" class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-ring">
      <img src="logo.png" alt="McPIL"
           onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
      <span class="sb-logo-fallback">McP</span>
    </div>
    <div>
      <div class="sb-brand-name">McPIL</div>
      <div class="sb-brand-sub">Pharmaceutical Lab</div>
    </div>
  </div>

  <div class="sb-nav">
    <div class="sb-section">Main</div>
    <a class="sb-item active" href="dashboard.php"><i class="ti ti-layout-dashboard"></i>Dashboard</a>
    <a class="sb-item" href="purchase_order.php"><i class="ti ti-shopping-cart"></i>Purchase Orders</a>
    <a class="sb-item" href="purchase_invoice.php"><i class="ti ti-file-invoice"></i>Purchase Invoice</a>
    <a class="sb-item" href="inventory.php"><i class="ti ti-box"></i>Inventory</a>

    <?php if (is_admin() || is_manager()): ?>
    <div class="sb-section">People</div>
    <a class="sb-item" href="employee_profile.php"><i class="ti ti-users"></i>Employees</a>
    <a class="sb-item" href="attendance.php">
      <i class="ti ti-calendar-check"></i>Attendance
      <?php if (!empty($unread_notifications)): ?>
        <span class="sb-badge"><?php echo count($unread_notifications); ?></span>
      <?php endif; ?>
    </a>

    <?php endif; ?>

    <div class="sb-section">Logistics</div>
    <a class="sb-item" href="delivery_tracking.php"><i class="ti ti-truck-delivery"></i>Delivery Tracking</a>
    <a class="sb-item" href="delivery_history.php"><i class="ti ti-history"></i>Delivery History</a>

    <div class="sb-section">Tools</div>
    <a class="sb-item" href="reports.php"><i class="ti ti-chart-bar"></i>Reports</a>
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

<div class="sb-backdrop" id="sbBackdrop"></div>

<!-- ═══════ MAIN ═══════ -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:10px;">
      <button class="mob-toggle" id="sbToggle"><i class="ti ti-menu-2"></i></button>
      <div>
        <div class="topbar-title">Overview</div>
        <div class="topbar-sub">
          Good <?php echo date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening'); ?>,
          <?php echo htmlspecialchars($_SESSION['full_name']); ?> 👋
        </div>
      </div>
    </div>

    <div class="topbar-right">
      <div class="search-box">
        <i class="ti ti-search" style="font-size:14px;color:var(--text-4)"></i>
        <input type="text" placeholder="Search anything…">
        <span class="kbd">⌘F</span>
      </div>
      <div class="tb-btn"><i class="ti ti-moon"></i></div>
      <div class="tb-btn"><i class="ti ti-world"></i></div>
      <div class="tb-btn">
        <i class="ti ti-bell"></i>
        <?php if (!empty($unread_notifications)): ?><div class="notif-dot"></div><?php endif; ?>
      </div>
      <?php if (is_admin() || is_manager()): ?><a href="employee_profile.php#add" class="btn-primary">
        <i class="ti ti-user-plus" style="font-size:14px"></i>Add Employee
      </a><?php endif; ?>

      <!-- PROFILE CHIP -->
      <div class="profile-wrap" id="profileWrap">
        <div class="user-chip" id="profileChip">
          <div class="u-avatar" id="chipAvatar">
            <?php if (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo'])): ?>
              <img src="<?php echo htmlspecialchars($_SESSION['profile_photo']); ?>" alt="">
            <?php else: ?>
              <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
            <?php endif; ?>
          </div>
          <div class="u-info">
            <div class="u-name"><?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?></div>
            <div class="u-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
          </div>
          <i class="ti ti-chevron-down u-chevron"></i>
        </div>

        <div class="profile-dropdown" id="profileDropdown">
          <div class="pd-header">
            <div class="pd-avatar" id="dropAvatar">
              <?php if (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo'])): ?>
                <img src="<?php echo htmlspecialchars($_SESSION['profile_photo']); ?>" alt="">
              <?php else: ?>
                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
              <?php endif; ?>
            </div>
            <div>
              <div class="pd-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
              <div class="pd-email"><?php echo htmlspecialchars($_SESSION['email'] ?? strtolower(str_replace(' ', '.', $_SESSION['full_name'])) . '@mcpil.com'); ?></div>
            </div>
          </div>
          <div class="pd-menu">
            <a class="pd-item" href="employee_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="ti ti-user"></i>Profile</a>
            <a class="pd-item" href="settings.php?tab=profile"><i class="ti ti-edit"></i>Edit Profile</a>
            <a class="pd-item" href="settings.php"><i class="ti ti-settings"></i>Settings</a>
            <div class="pd-divider"></div>
            <a class="pd-item logout" href="logout.php"><i class="ti ti-logout"></i>Logout</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">

    <section class="list-dashboard">
      <div class="list-dashboard-head">
        <div><div class="list-dashboard-title">Delivery, PO &amp; Invoice Lists</div><div class="list-dashboard-sub">Shared view for <?php echo htmlspecialchars(ucfirst(current_user_role())); ?> accounts. Records are view-only for employees.</div></div>
      </div>

      <section class="list-card">
        <div class="list-card-head"><div class="list-card-icon"><i class="ti ti-truck-delivery"></i></div><h2>Delivery List</h2><span class="list-card-count"><?php echo count($all_deliveries); ?></span></div>
        <div class="list-table-wrap"><table class="list-table"><thead><tr><th>Delivery</th><th>PO / Supplier</th><th>Status</th></tr></thead><tbody>
          <?php foreach ($all_deliveries as $delivery): ?><tr><td class="ref"><?php echo htmlspecialchars($delivery['delivery_number'] ?? ('DEL-'.$delivery['id'])); ?></td><td><div class="name"><?php echo htmlspecialchars($delivery['supplier_name'] ?? '—'); ?></div><small><?php echo htmlspecialchars($delivery['po_number'] ?? 'No PO'); ?></small></td><td><span class="pill i"><?php echo htmlspecialchars(ucwords(str_replace('_',' ', $delivery['status'] ?? 'pending'))); ?></span></td></tr><?php endforeach; ?>
          <?php if (!$all_deliveries): ?><tr><td colspan="3">No delivery records yet.</td></tr><?php endif; ?>
        </tbody></table></div>
      </section>

      <section class="list-card">
        <div class="list-card-head"><div class="list-card-icon"><i class="ti ti-shopping-cart"></i></div><h2>Purchase Order List</h2><span class="list-card-count"><?php echo count($all_purchase_orders); ?></span></div>
        <div class="list-table-wrap"><table class="list-table"><thead><tr><th>PO number</th><th>Supplier</th><th>Status</th></tr></thead><tbody>
          <?php foreach ($all_purchase_orders as $order): ?><tr><td class="ref"><?php echo htmlspecialchars($order['po_number']); ?></td><td><div class="name"><?php echo htmlspecialchars($order['supplier_name'] ?? '—'); ?></div><small><?php echo htmlspecialchars(format_currency($order['total_amount'] ?? 0)); ?></small></td><td><span class="pill p"><?php echo htmlspecialchars(ucfirst($order['status'] ?? 'pending')); ?></span></td></tr><?php endforeach; ?>
          <?php if (!$all_purchase_orders): ?><tr><td colspan="3">No purchase orders yet.</td></tr><?php endif; ?>
        </tbody></table></div>
      </section>

      <section class="list-card">
        <div class="list-card-head"><div class="list-card-icon"><i class="ti ti-file-invoice"></i></div><h2>PO Invoice List</h2><span class="list-card-count"><?php echo count($all_invoices); ?></span></div>
        <div class="list-table-wrap"><table class="list-table"><thead><tr><th>Invoice</th><th>PO / Supplier</th><th>Status</th></tr></thead><tbody>
          <?php foreach ($all_invoices as $invoice): ?><tr><td class="ref"><?php echo htmlspecialchars($invoice['invoice_number'] ?? ('INV-'.$invoice['id'])); ?></td><td><div class="name"><?php echo htmlspecialchars($invoice['supplier_name'] ?? '—'); ?></div><small><?php echo htmlspecialchars($invoice['po_number'] ?? 'No PO'); ?></small></td><td><span class="pill s"><?php echo htmlspecialchars(ucwords(str_replace('_',' ', $invoice['status'] ?? 'pending'))); ?></span></td></tr><?php endforeach; ?>
          <?php if (!$all_invoices): ?><tr><td colspan="3">No purchase invoices yet.</td></tr><?php endif; ?>
        </tbody></table></div>
      </section>
    </section>

    <!-- KPI CARDS -->
    <div class="kpi-grid">
      <div class="kpi-card k-blue">
        <div class="kpi-icon i-blue"><i class="ti ti-shopping-cart"></i></div>
        <div class="kpi-val"><?php echo number_format($stats['total_purchase_orders']); ?></div>
        <div class="kpi-label">Purchase Orders</div>
        <div class="kpi-chip up"><i class="ti ti-arrow-up" style="font-size:9px"></i>+<?php echo rand(8,15); ?>% since last month</div>
      </div>
      <div class="kpi-card k-green">
        <div class="kpi-icon i-green"><i class="ti ti-users"></i></div>
        <div class="kpi-val"><?php echo number_format($stats['total_employees']); ?>+</div>
        <div class="kpi-label">Active Employees</div>
        <div class="kpi-chip up"><i class="ti ti-arrow-up" style="font-size:9px"></i>+0.7% this month</div>
      </div>
      <div class="kpi-card k-amber">
        <div class="kpi-icon i-amber"><i class="ti ti-truck-delivery"></i></div>
        <div class="kpi-val"><?php echo number_format($stats['pending_deliveries']); ?>+</div>
        <div class="kpi-label">Deliveries</div>
        <div class="kpi-chip up"><i class="ti ti-arrow-up" style="font-size:9px"></i>+25% since last month</div>
      </div>
      <div class="kpi-card k-red">
        <div class="kpi-icon i-red"><i class="ti ti-alert-circle"></i></div>
        <div class="kpi-val"><?php echo isset($stats['low_stock_items']) ? number_format($stats['low_stock_items']) : '12'; ?>+</div>
        <div class="kpi-label">Low Stock</div>
        <div class="kpi-chip dn"><i class="ti ti-arrow-down" style="font-size:9px"></i>-5.8% since last month</div>
      </div>
    </div>

    <!-- ROW A -->
    <div class="row-a">
      <!-- Order Status Donut -->
      <div class="card">
        <div class="card-head">
          <div>
            <div class="card-title">Order Status</div>
            <div class="card-sub">Breakdown this week</div>
          </div>
          <button class="period-btn">This week <i class="ti ti-chevron-down" style="font-size:11px"></i></button>
        </div>
        <div class="donut-body">
          <div class="donut-wrap">
            <canvas id="donutChart"></canvas>
            <div class="donut-center">
              <div class="donut-center-val">Approved</div>
              <div class="donut-center-sub">42% · <?php echo $stats['total_purchase_orders']; ?> POs</div>
            </div>
          </div>
          <div class="legend-rows">
            <div class="leg-row">
              <div class="leg-dot" style="background:#1645b6"></div>
              <div class="leg-label">Approved</div>
              <div class="leg-bar"><div class="leg-fill" style="width:42%;background:#1645b6"></div></div>
              <div class="leg-pct">42%</div>
            </div>
            <div class="leg-row">
              <div class="leg-dot" style="background:#d97706"></div>
              <div class="leg-label">Pending</div>
              <div class="leg-bar"><div class="leg-fill" style="width:35%;background:#d97706"></div></div>
              <div class="leg-pct">35%</div>
            </div>
            <div class="leg-row">
              <div class="leg-dot" style="background:#0d7a48"></div>
              <div class="leg-label">Delivered</div>
              <div class="leg-bar"><div class="leg-fill" style="width:17%;background:#0d7a48"></div></div>
              <div class="leg-pct">17%</div>
            </div>
            <div class="leg-row">
              <div class="leg-dot" style="background:#c9221a"></div>
              <div class="leg-label">Rejected</div>
              <div class="leg-bar"><div class="leg-fill" style="width:6%;background:#c9221a"></div></div>
              <div class="leg-pct">6%</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Delivery Calendar (spans 2 cols) -->
      <div class="card" style="grid-column:span 2">
        <div class="card-head">
          <div>
            <div class="card-title">Delivery Schedule</div>
            <div class="card-sub" id="calMonthLabel">June 2026</div>
          </div>
          <div style="display:flex;gap:3px">
            <button class="period-btn" id="calPrev" style="padding:4px 7px"><i class="ti ti-chevron-left" style="font-size:11px"></i></button>
            <button class="period-btn" id="calNext" style="padding:4px 7px"><i class="ti ti-chevron-right" style="font-size:11px"></i></button>
          </div>
        </div>
        <div class="cal-wrap">
          <div class="cal-dow">
            <span>Su</span><span>Mo</span><span>Tu</span>
            <span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
          </div>
          <div class="cal-grid" id="calGrid"></div>
        </div>
      </div>
    </div>

    <!-- DELIVERY MODAL -->
    <div id="deliveryModal" class="dm-overlay">
      <div class="dm-backdrop" id="dmBackdrop"></div>
      <div class="dm-box">
        <div class="dm-header">
          <div>
            <div class="dm-title" id="dmTitle">Deliveries</div>
            <div class="dm-sub"  id="dmSub"></div>
          </div>
          <button class="dm-close" id="dmClose"><i class="ti ti-x"></i></button>
        </div>
        <div class="dm-body" id="dmBody"></div>
        <div class="dm-footer">
          <a href="delivery_tracking.php" class="dm-link-btn">
            <i class="ti ti-truck-delivery"></i> View All Deliveries
          </a>
        </div>
      </div>
    </div>

    <!-- ROW B -->
    <div class="row-b">
      <!-- Bar Chart -->
      <div class="card chart-card">
        <div class="card-head">
          <div>
            <div class="card-title">Purchase Breakdown</div>
            <div class="chart-total">Total <strong><?php echo isset($stats['total_amount']) ? format_currency($stats['total_amount']) : '₱0.00'; ?></strong></div>
          </div>
          <button class="period-btn">This week <i class="ti ti-chevron-down" style="font-size:11px"></i></button>
        </div>
        <canvas id="barChart" height="140" style="margin-top:10px"></canvas>
      </div>

      <!-- Line Chart -->
      <div class="card chart-card">
        <div class="card-head">
          <div>
            <div class="card-title">Orders vs Deliveries Trend</div>
            <div class="card-sub">📦 Orders &nbsp;🚚 Deliveries</div>
          </div>
          <button class="period-btn">Monthly <i class="ti ti-chevron-down" style="font-size:11px"></i></button>
        </div>
        <canvas id="lineChart" height="140" style="margin-top:10px"></canvas>
      </div>

      <!-- Top Employees -->
      <div class="card emp-card">
        <div class="card-head" style="padding-bottom:8px">
          <div class="card-title">Top Employees</div>
          <button class="period-btn">Week <i class="ti ti-chevron-down" style="font-size:11px"></i></button>
        </div>
        <div class="emp-item">
          <div class="emp-avatar" style="background:linear-gradient(135deg,#1645b6,#5b3fc4)">MA</div>
          <div>
            <div class="emp-name">Maria Santos</div>
            <div class="emp-meta">Inventory · 13 tasks · 411 pts</div>
          </div>
          <div class="emp-badge">🥇</div>
        </div>
        <div class="emp-item">
          <div class="emp-avatar" style="background:linear-gradient(135deg,#0d7a48,#0a6e6e)">JR</div>
          <div>
            <div class="emp-name">Juan Reyes</div>
            <div class="emp-meta">Logistics · 11 tasks · 387 pts</div>
          </div>
          <div class="emp-badge">🥈</div>
        </div>
        <div class="emp-item">
          <div class="emp-avatar" style="background:linear-gradient(135deg,#d97706,#c9221a)">AL</div>
          <div>
            <div class="emp-name">Ana Lim</div>
            <div class="emp-meta">Purchasing · 10 tasks · 297 pts</div>
          </div>
          <div class="emp-badge">🥉</div>
        </div>
      </div>
    </div>

    <!-- ROW C -->
    <div class="row-c">

      <!-- Recent PO Table -->
      <div class="card tbl-wrap">
        <div class="tbl-toolbar">
          <div>
            <div class="card-title">Recent Purchase Orders</div>
            <div class="card-sub">Latest entries this period</div>
          </div>
          <button class="period-btn">This week <i class="ti ti-chevron-down" style="font-size:11px"></i></button>
        </div>
        <div style="overflow-x:auto">
          <table class="dt">
            <thead>
              <tr><th>PO #</th><th>Supplier</th><th>Amount</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php foreach ($recent_orders as $o): ?>
              <tr>
                <td><span class="po-num"><?php echo htmlspecialchars($o['po_number']); ?></span></td>
                <td><div class="cell-name"><?php echo htmlspecialchars($o['supplier_name']); ?></div></td>
                <td><span class="cell-amt"><?php echo format_currency($o['total_amount']); ?></span></td>
                <td>
                  <span class="pill <?php
                    echo $o['status'] === 'approved' ? 's' :
                        ($o['status'] === 'pending'  ? 'p' :
                        ($o['status'] === 'rejected' ? 'd' : 'i'));
                  ?>"><?php echo ucfirst($o['status']); ?></span>
                </td>
                <td><button class="view-btn">View</button></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Inventory Gauge -->
      <div class="card gauge-card">
        <div class="card-head" style="padding-bottom:0">
          <div class="card-title">Inventory Overview</div>
          <button class="period-btn">Monthly <i class="ti ti-chevron-down" style="font-size:11px"></i></button>
        </div>
        <div class="gauge-wrap">
          <canvas id="gaugeChart" width="190" height="110"></canvas>
          <div class="gauge-center">
            <div class="gauge-val"><?php echo isset($stats['total_inventory_items']) ? number_format($stats['total_inventory_items']) : '1,240'; ?></div>
            <div class="gauge-lbl">Total Items</div>
          </div>
        </div>
        <div class="g-grid">
          <div class="g-item">
            <div class="g-item-lbl"><i class="ti ti-circle-filled" style="font-size:7px;color:#0d7a48"></i>Available</div>
            <div class="g-item-val">874</div>
          </div>
          <div class="g-item">
            <div class="g-item-lbl"><i class="ti ti-circle-filled" style="font-size:7px;color:#1645b6"></i>Issued</div>
            <div class="g-item-val">309</div>
          </div>
          <div class="g-item">
            <div class="g-item-lbl"><i class="ti ti-circle-filled" style="font-size:7px;color:#d97706"></i>Reserved</div>
            <div class="g-item-val">51</div>
          </div>
          <div class="g-item">
            <div class="g-item-lbl"><i class="ti ti-circle-filled" style="font-size:7px;color:#c9221a"></i>Damaged</div>
            <div class="g-item-val">6</div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="card">
        <div class="card-head" style="padding-bottom:10px;border-bottom:0.5px solid var(--border)">
          <div class="card-title">Recent Activities</div>
          <button class="period-btn">Week <i class="ti ti-chevron-down" style="font-size:11px"></i></button>
        </div>
        <?php
        $activities = [
          ['ico'=>'ti ti-file-invoice',  'bg'=>'var(--blue-bg)',  'ic'=>'var(--blue)',  'title'=>'New PO Created',    'desc'=>'PO-2024-001 submitted for approval',    'time'=>'Jan 09, 2025 · 10:30 AM'],
          ['ico'=>'ti ti-user-plus',     'bg'=>'var(--green-bg)', 'ic'=>'var(--green)', 'title'=>'Employee Added',     'desc'=>'New staff registered in the system',    'time'=>'Jan 09, 2025 · 9:25 AM'],
          ['ico'=>'ti ti-truck-delivery','bg'=>'var(--amber-bg)', 'ic'=>'#d97706',      'title'=>'Delivery Updated',   'desc'=>'DEL-2024-015 marked as in transit',     'time'=>'Jan 09, 2025 · 9:10 AM'],
          ['ico'=>'ti ti-alert-triangle','bg'=>'var(--red-bg)',   'ic'=>'var(--red)',   'title'=>'Low Stock Alert',    'desc'=>'Ibuprofen 200mg below reorder point',   'time'=>'Jan 09, 2025 · 8:50 AM'],
        ];
        foreach ($activities as $a): ?>
        <div class="act-item">
          <div class="act-ico" style="background:<?php echo $a['bg']; ?>;color:<?php echo $a['ic']; ?>">
            <i class="<?php echo $a['ico']; ?>"></i>
          </div>
          <div>
            <div class="act-title"><?php echo $a['title']; ?></div>
            <div class="act-desc"><?php echo $a['desc']; ?></div>
            <div class="act-time"><i class="ti ti-clock" style="font-size:10px"></i><?php echo $a['time']; ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </div><!-- /row-c -->
  </div><!-- /content -->
</div><!-- /main -->

<?php include 'mcbot_widget.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* Sidebar */
const body=document.body,sbToggle=document.getElementById('sbToggle'),sbBackdrop=document.getElementById('sbBackdrop');
if(sbToggle)sbToggle.addEventListener('click',()=>body.classList.toggle('sb-open'));
if(sbBackdrop)sbBackdrop.addEventListener('click',()=>body.classList.remove('sb-open'));
window.addEventListener('resize',()=>{if(window.innerWidth>=992)body.classList.remove('sb-open');});

/* Profile dropdown */
(function(){
  const chip=document.getElementById('profileChip'),dropdown=document.getElementById('profileDropdown');
  const open=()=>{chip.classList.add('open');dropdown.classList.add('open');};
  const close=()=>{chip.classList.remove('open');dropdown.classList.remove('open');};
  chip.addEventListener('click',(e)=>{e.stopPropagation();chip.classList.contains('open')?close():open();});
  document.addEventListener('click',(e)=>{if(!document.getElementById('profileWrap').contains(e.target))close();});
  document.addEventListener('keydown',(e)=>{if(e.key==='Escape')close();});
})();

/* Charts */
const FONT='Sora',TICK='#b0b4cc',GRID='rgba(0,0,0,.04)';

new Chart(document.getElementById('donutChart'),{
  type:'doughnut',
  data:{labels:['Approved','Pending','Delivered','Rejected'],datasets:[{data:[42,35,17,6],backgroundColor:['#1645b6','#d97706','#0d7a48','#c9221a'],borderWidth:0,hoverOffset:4}]},
  options:{cutout:'72%',plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.label}: ${c.parsed}%`}}}}
});

new Chart(document.getElementById('barChart'),{
  type:'bar',
  data:{labels:['Reagents','Equipment','PPE','Chemicals','Supplies','Others'],datasets:[{label:'Amount (₱)',data:[85000,120000,45000,95000,62000,38000],backgroundColor:['#1645b6','#1645b6','#b5d4f4','#0d7a48','#b5d4f4','#b5d4f4'],borderRadius:4,borderSkipped:false}]},
  options:{responsive:true,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' ₱'+c.parsed.y.toLocaleString()}}},scales:{x:{grid:{display:false},ticks:{font:{family:FONT,size:9.5},color:TICK}},y:{grid:{color:GRID},ticks:{font:{family:FONT,size:9.5},color:TICK,callback:v=>'₱'+(v/1000)+'k'}}}}
});

new Chart(document.getElementById('lineChart'),{
  type:'line',
  data:{labels:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],datasets:[
    {label:'Orders',data:[12,19,15,25,22,30,28,35,29,40,38,45],borderColor:'#1645b6',backgroundColor:'rgba(22,69,182,.07)',borderWidth:2,pointRadius:2.5,pointBackgroundColor:'#1645b6',tension:.4,fill:true},
    {label:'Deliveries',data:[8,14,12,20,18,24,22,30,24,35,32,40],borderColor:'#0d7a48',backgroundColor:'rgba(13,122,72,.05)',borderWidth:2,pointRadius:2.5,pointBackgroundColor:'#0d7a48',tension:.4,fill:true}
  ]},
  options:{responsive:true,plugins:{legend:{labels:{font:{family:FONT,size:9.5},color:TICK,boxWidth:9,usePointStyle:true}},tooltip:{mode:'index',intersect:false}},scales:{x:{grid:{display:false},ticks:{font:{family:FONT,size:9.5},color:TICK}},y:{grid:{color:GRID},ticks:{font:{family:FONT,size:9.5},color:TICK}}}}
});

/* Gauge */
(function(){
  const gCtx=document.getElementById('gaugeChart').getContext('2d');
  const gData=[{value:874,color:'#0d7a48'},{value:309,color:'#1645b6'},{value:51,color:'#d97706'},{value:6,color:'#c9221a'}];
  const gTotal=gData.reduce((s,d)=>s+d.value,0);
  let sa=Math.PI;
  gData.forEach(d=>{
    const sl=(d.value/gTotal)*Math.PI;
    gCtx.beginPath();gCtx.moveTo(95,95);gCtx.arc(95,95,74,sa,sa+sl);gCtx.closePath();
    gCtx.fillStyle=d.color;gCtx.fill();sa+=sl;
  });
  gCtx.beginPath();gCtx.arc(95,95,48,0,2*Math.PI);gCtx.fillStyle='#fff';gCtx.fill();
})();

/* Delivery Calendar */
(function(){
  const deliveries=<?php
    $all_deliveries=function_exists('get_deliveries')?get_deliveries(null,100):[];
    $cal_data=[];
    foreach($all_deliveries as $d){
      $date=date('Y-m-d',strtotime($d['delivery_date']));
      if(!isset($cal_data[$date]))$cal_data[$date]=[];
      $status=$d['status'];
      $color=$status==='delivered'?'#0d7a48':($status==='in_transit'?'#1645b6':'#d97706');
      $bg=$status==='delivered'?'#e6f4ee':($status==='in_transit'?'#e8eeff':'#fff4de');
      $cal_data[$date][]=['title'=>$d['delivery_number'],'supplier'=>$d['supplier_name'],'time'=>date('h:i A',strtotime($d['delivery_date'])).' — '.ucfirst(str_replace('_',' ',$status)),'status'=>ucfirst(str_replace('_',' ',$status)),'color'=>$color,'bg'=>$bg];
    }
    if(empty($cal_data)){
      $y=date('Y');$m=date('m');
      $demo=[[$y.'-'.$m.'-03','DEL-001','PharmaCorp Inc.','09:00 AM — In Transit','In Transit','#1645b6','#e8eeff'],[$y.'-'.$m.'-03','DEL-002','MedSupply Co.','02:00 PM — Pending','Pending','#d97706','#fff4de'],[$y.'-'.$m.'-07','DEL-003','LabChem Ltd.','10:30 AM — Delivered','Delivered','#0d7a48','#e6f4ee'],[$y.'-'.$m.'-10','DEL-004','PharmaCorp Inc.','08:00 AM — In Transit','In Transit','#1645b6','#e8eeff'],[$y.'-'.$m.'-10','DEL-005','MedSupply Co.','01:00 PM — Pending','Pending','#d97706','#fff4de'],[$y.'-'.$m.'-10','DEL-006','BioReg Corp.','03:30 PM — Pending','Pending','#d97706','#fff4de'],[$y.'-'.$m.'-14','DEL-007','LabChem Ltd.','09:00 AM — Delivered','Delivered','#0d7a48','#e6f4ee'],[$y.'-'.$m.'-17','DEL-008','PharmaCorp Inc.','11:00 AM — Pending','Pending','#d97706','#fff4de'],[$y.'-'.$m.'-21','DEL-009','MedSupply Co.','08:30 AM — In Transit','In Transit','#1645b6','#e8eeff'],[$y.'-'.$m.'-21','DEL-010','BioReg Corp.','02:00 PM — In Transit','In Transit','#1645b6','#e8eeff'],[$y.'-'.$m.'-25','DEL-011','LabChem Ltd.','10:00 AM — Pending','Pending','#d97706','#fff4de'],[$y.'-'.$m.'-28','DEL-012','PharmaCorp Inc.','09:00 AM — Pending','Pending','#d97706','#fff4de']];
      foreach($demo as $row)$cal_data[$row[0]][]=['title'=>$row[1],'supplier'=>$row[2],'time'=>$row[3],'status'=>$row[4],'color'=>$row[5],'bg'=>$row[6]];
    }
    echo json_encode($cal_data);
  ?>;

  const MONTHS=['January','February','March','April','May','June','July','August','September','October','November','December'];
  const DAYS=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
  let cur=new Date();cur.setDate(1);
  const modal=document.getElementById('deliveryModal'),dmTitle=document.getElementById('dmTitle'),dmSub=document.getElementById('dmSub'),dmBody=document.getElementById('dmBody'),dmClose=document.getElementById('dmClose'),dmBackdrop=document.getElementById('dmBackdrop');

  function openModal(key,events){
    const[y,m,d]=key.split('-'),dateObj=new Date(y,m-1,d);
    dmTitle.textContent=`${DAYS[dateObj.getDay()]}, ${d} ${MONTHS[m-1]} ${y}`;
    dmSub.textContent=`${events.length} deliver${events.length>1?'ies':'y'} scheduled`;
    dmBody.innerHTML='';
    const pillMap={'Delivered':'background:#e6f4ee;color:#0d7a48;','In Transit':'background:#e8eeff;color:#1645b6;','Pending':'background:#fff4de;color:#875200;'};
    events.forEach(ev=>{
      const item=document.createElement('div');item.className='dm-item';
      item.innerHTML=`<div class="dm-item-bar" style="background:${ev.color}"></div><div class="dm-item-ico" style="background:${ev.bg};color:${ev.color}"><i class="ti ti-truck-delivery"></i></div><div class="dm-item-body"><div class="dm-item-num">${ev.title}</div><div class="dm-item-time">${ev.time}</div><div class="dm-item-sup">${ev.supplier}</div><span class="dm-item-pill" style="${pillMap[ev.status]||'background:#f0f2f9;color:#3a4066;'}">${ev.status}</span></div>`;
      dmBody.appendChild(item);
    });
    modal.classList.add('open');document.body.style.overflow='hidden';
  }
  function closeModal(){modal.classList.remove('open');document.body.style.overflow='';}
  dmClose.addEventListener('click',closeModal);dmBackdrop.addEventListener('click',closeModal);
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});

  function pad(n){return String(n).padStart(2,'0');}
  function dateKey(y,m,d){return`${y}-${pad(m+1)}-${pad(d)}`;}

  function renderCalendar(){
    const y=cur.getFullYear(),m=cur.getMonth();
    document.getElementById('calMonthLabel').textContent=MONTHS[m]+' '+y;
    const grid=document.getElementById('calGrid');grid.innerHTML='';
    const firstDay=new Date(y,m,1).getDay(),daysInMonth=new Date(y,m+1,0).getDate(),daysInPrev=new Date(y,m,0).getDate();
    const today=new Date();
    for(let i=firstDay-1;i>=0;i--)grid.appendChild(makeCell(daysInPrev-i,dateKey(m===0?y-1:y,m===0?11:m-1,daysInPrev-i),true,false));
    for(let d=1;d<=daysInMonth;d++){const key=dateKey(y,m,d);grid.appendChild(makeCell(d,key,false,today.getFullYear()===y&&today.getMonth()===m&&today.getDate()===d));}
    const remaining=(firstDay+daysInMonth)%7===0?0:7-((firstDay+daysInMonth)%7);
    for(let d=1;d<=remaining;d++)grid.appendChild(makeCell(d,dateKey(m===11?y+1:y,m===11?0:m+1,d),true,false));
  }

  function makeCell(d,key,otherMonth,isToday){
    const cell=document.createElement('div');
    cell.className='cal-cell'+(otherMonth?' other':'')+(isToday?' today':'');
    const num=document.createElement('div');num.className='cal-num';num.textContent=d;cell.appendChild(num);
    const events=deliveries[key];
    if(events&&events.length){
      const dw=document.createElement('div');dw.className='cal-dots';
      events.slice(0,3).forEach(ev=>{const dot=document.createElement('div');dot.className='cal-dot';dot.style.background=ev.color;dw.appendChild(dot);});
      cell.appendChild(dw);cell.style.cursor='pointer';
      cell.addEventListener('click',()=>{if(!otherMonth)openModal(key,events);});
    }
    return cell;
  }

  document.getElementById('calPrev').addEventListener('click',()=>{cur.setMonth(cur.getMonth()-1);renderCalendar();});
  document.getElementById('calNext').addEventListener('click',()=>{cur.setMonth(cur.getMonth()+1);renderCalendar();});
  renderCalendar();
})();
</script>
</body>
</html>
