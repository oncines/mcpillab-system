<?php
require_once 'config.php';
require_roles(['admin', 'manager', 'employee']);

// Sample employee data (replace with DB query)
$employees = [
    ["name" => "Bagus Fikri",        "id" => "39486846",  "color" => "#e67e22", "in" => "10:02 AM", "dur" => "8h 56m",  "out" => "07:00 PM", "ot" => "2h 12m", "loc" => "Jl. Jendral Sudirman...", "note" => "Discussed mutual value",    "late" => true],
    ["name" => "Ihdizein",           "id" => "34634543",  "color" => "#16a085", "in" => "09:30 AM", "dur" => "8h 1m",   "out" => "07:12 PM", "ot" => "",       "loc" => "Jl. Ahmad Yani No...",    "note" => "Tyrisha is already line",   "late" => false],
    ["name" => "Mufti Hidayat",      "id" => "623473837", "color" => "#8e44ad", "in" => "09:24 AM", "dur" => "7h 36m",  "out" => "05:00 PM", "ot" => "",       "loc" => "Jl. Diponegoro No...",    "note" => "Marci is already doing",    "late" => false],
    ["name" => "Fauzan Ardiansyah",  "id" => "39486846",  "color" => "#2980b9", "in" => "08:56 AM", "dur" => "10h 12m", "out" => "05:01 PM", "ot" => "",       "loc" => "Jl. Basuki Rahmat...",    "note" => "Tyrisha is already line",   "late" => false],
    ["name" => "Raihan Fikri",       "id" => "92864764",  "color" => "#c0392b", "in" => "08:56 AM", "dur" => "10h 12m", "out" => "07:00 PM", "ot" => "",       "loc" => "Jl. Raya Bogor Km...",    "note" => "Discussed mutual value",    "late" => true],
    ["name" => "Ilan",               "id" => "90029388",  "color" => "#27ae60", "in" => "10:02 AM", "dur" => "10h 12m", "out" => "05:00 PM", "ot" => "",       "loc" => "Jl. WR Supratman...",     "note" => "Rachel has agreed to",      "late" => false],
    ["name" => "Panji Dwi",          "id" => "173584473", "color" => "#d35400", "in" => "08:56 AM", "dur" => "10h 12m", "out" => "05:00 PM", "ot" => "",       "loc" => "Jl. Diponegoro No...",    "note" => "Darcel is pretty good",     "late" => false],
    ["name" => "Laokta Raymarley",   "id" => "39486846",  "color" => "#1abc9c", "in" => "08:56 AM", "dur" => "10h 12m", "out" => "05:00 PM", "ot" => "",       "loc" => "Jl. Sisingamangar...",    "note" => "Maryland is a new mar",     "late" => false],
    ["name" => "Bryan",              "id" => "927469748", "color" => "#7f8c8d", "in" => "08:56 AM", "dur" => "10h 12m", "out" => "05:00 PM", "ot" => "",       "loc" => "Jl. Raya Solo-Jogj...",   "note" => "Not heard back from",       "late" => false],
];

$present   = ["on_time" => 365, "late" => 62, "early" => 224];
$absent    = ["absent" => 42, "no_in" => 36, "no_out" => 0, "invalid" => 0];
$away      = ["day_off" => 0, "time_off" => 0];

$active_page = "attendance";
$date_label  = "Monday, 15 October";

function initials(string $name): string {
    $parts = explode(' ', trim($name));
    return strtoupper(implode('', array_map(fn($p) => $p[0], array_slice($parts, 0, 2))));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MCPIL – Attendance</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
/* ══════════════════════════════════════════
   DESIGN TOKENS — identical to delivery_tracking.php
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

  /* ── SIDEBAR TOKENS — exact match to delivery_tracking.php ── */
  --sidebar-w:    220px;
  --sb-bg:        #0d1b3e;
  --sb-active-bg: rgba(255,255,255,0.11);
  --sb-hover-bg:  rgba(255,255,255,0.06);
  --sb-label:     rgba(255,255,255,0.32);
  --sb-text:      rgba(255,255,255,0.70);
  --sb-text-act:  #ffffff;
  --sb-border:    rgba(255,255,255,0.07);
  --sb-highlight: #3b82f6;
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

@keyframes pulse-dot {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:.5; transform:scale(1.5); }
}
@keyframes fadeUp {
  from { opacity:0; transform:translateY(14px); }
  to   { opacity:1; transform:translateY(0); }
}
.fade-up   { animation: fadeUp .45s cubic-bezier(.22,1,.36,1) both; }
.fade-up-1 { animation-delay:.04s; }
.fade-up-2 { animation-delay:.09s; }
.fade-up-3 { animation-delay:.14s; }
.fade-up-4 { animation-delay:.19s; }
.fade-up-5 { animation-delay:.24s; }

/* ══════════════════════════════════════════
   SIDEBAR — exact copy from delivery_tracking.php
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
  width: 40px; height: 40px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.28);
  overflow: hidden; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: #1a2a5e;
}
.sb-logo-ring img { width:100%; height:100%; object-fit:cover; display:block; }
.sb-logo-ring .sb-logo-fallback { font-size:11px; font-weight:800; color:#fff; letter-spacing:.03em; }
.sb-brand-name { font-size:13px; font-weight:800; color:#fff; letter-spacing:.06em; text-transform:uppercase; line-height:1.15; }
.sb-brand-sub  { font-size:8.5px; color:rgba(255,255,255,0.38); letter-spacing:.10em; text-transform:uppercase; line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:130px; }

/* Nav body */
.sb-nav { flex:1; padding:6px 10px 4px; }

/* Section label */
.sb-section { font-size:9.5px; font-weight:700; letter-spacing:.13em; text-transform:uppercase; color:var(--sb-label); padding:14px 8px 5px; }

/* Nav item */
.sb-item {
  display:flex; align-items:center; gap:10px;
  padding:9px 10px; border-radius:9px;
  color:var(--sb-text); text-decoration:none;
  font-size:13px; font-weight:500;
  transition:background .13s, color .13s;
  margin-bottom:2px; line-height:1.2; cursor:pointer;
}
.sb-item:hover  { background:var(--sb-hover-bg); color:var(--sb-text-act); text-decoration:none; }
.sb-item.active { background:var(--sb-active-bg); color:var(--sb-text-act); font-weight:600; }
.sb-item i { font-size:18px; flex-shrink:0; line-height:1; width:22px; text-align:center; }

/* Delivery Tracking — blue icon highlight */
.sb-item.sb-highlight { color:var(--sb-text); }
.sb-item.sb-highlight i { color:#60a5fa; }
.sb-item.sb-highlight:hover { color:var(--sb-text-act); }
.sb-item.active.sb-highlight { color:var(--sb-text-act); }
.sb-item.active.sb-highlight i { color:#93c5fd; }

/* Logout red */
.sb-item.sb-logout { color:rgba(239,68,68,0.75); }
.sb-item.sb-logout i { color:rgba(239,68,68,0.85); }
.sb-item.sb-logout:hover { background:rgba(239,68,68,0.10); color:#ef4444; }
.sb-item.sb-logout:hover i { color:#ef4444; }

/* Footer */
.sb-footer { flex-shrink:0; padding:4px 10px 18px; border-top:1px solid var(--sb-border); }

/* Mobile toggle */
.mobile-sb-toggle {
  display:none; align-items:center; justify-content:center;
  width:36px; height:36px; border:none; border-radius:var(--radius-sm);
  background:var(--surface); color:var(--text-2); cursor:pointer;
  border:1px solid var(--border); flex:0 0 auto;
}
.mobile-sb-backdrop { display:none; }
@media (max-width:991.98px) {
  .sidebar { transform:translateX(-100%); transition:transform .3s ease; box-shadow:0 12px 28px rgba(0,0,0,.25); }
  body.sb-open .sidebar { transform:translateX(0); }
  .main-wrap { margin-left:0 !important; }
  .mobile-sb-toggle { display:inline-flex; }
  .mobile-sb-backdrop { display:block; position:fixed; inset:0; background:rgba(9,15,85,.45); opacity:0; pointer-events:none; transition:opacity .3s ease; z-index:9998; }
  body.sb-open .mobile-sb-backdrop { opacity:1; pointer-events:auto; }
}

/* ══════════════════════════════════════════
   LAYOUT
══════════════════════════════════════════ */
.main-wrap { margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; }

/* ══════════════════════════════════════════
   TOPBAR — same as delivery_tracking.php
══════════════════════════════════════════ */
.topbar {
  height:var(--topbar-h); background:var(--white);
  border-bottom:1px solid var(--border);
  padding:0 32px; display:flex; align-items:center;
  justify-content:space-between;
  position:sticky; top:0; z-index:100; box-shadow:var(--shadow-xs);
}
.topbar-left  { display:flex; align-items:center; gap:14px; }
.topbar-breadcrumb { display:flex; flex-direction:column; gap:1px; }
.topbar-title { font-size:16px; font-weight:700; color:var(--text-1); letter-spacing:-.025em; line-height:1.2; }
.topbar-sub   { font-size:11px; color:var(--text-4); font-weight:400; letter-spacing:.02em; }
.topbar-divider { width:1px; height:28px; background:var(--border); margin:0 4px; }
.topbar-right { display:flex; align-items:center; gap:10px; }
.tb-icon-btn  {
  width:36px; height:36px; border-radius:var(--radius-sm);
  background:var(--surface); border:1px solid var(--border);
  display:flex; align-items:center; justify-content:center;
  color:var(--text-3); cursor:pointer; font-size:13px;
  transition:all .15s; position:relative;
}
.tb-icon-btn:hover { background:var(--surface-2); color:var(--text-1); border-color:var(--border-2); }
.tb-notif-dot {
  position:absolute; top:8px; right:8px;
  width:6px; height:6px; border-radius:50%;
  background:var(--red); border:1.5px solid var(--white);
  animation:pulse-dot 2.5s infinite;
}
.user-chip {
  display:flex; align-items:center; gap:9px;
  padding:4px 12px 4px 4px;
  border:1px solid var(--border); border-radius:40px;
  cursor:pointer; background:var(--white); transition:all .15s;
}
.user-chip:hover { border-color:var(--border-2); box-shadow:var(--shadow-xs); }
.u-avatar { width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg,var(--navy-3),var(--navy)); color:var(--white); font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; }
.u-name { font-size:12px; font-weight:700; color:var(--text-1); line-height:1.2; }
.u-role { font-size:10px; color:var(--text-4); font-weight:400; }
.btn-add {
  display:inline-flex; align-items:center; gap:7px;
  background:var(--navy); color:var(--white); border:none;
  border-radius:var(--radius-sm); padding:9px 18px;
  font-size:12.5px; font-weight:700; cursor:pointer;
  font-family:var(--font); letter-spacing:.01em; transition:all .15s;
  position:relative; overflow:hidden;
}
.btn-add:hover { background:var(--navy-2); transform:translateY(-1px); box-shadow:0 6px 20px rgba(10,16,69,.25); }
.btn-add .btn-icon { width:18px; height:18px; border-radius:4px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:10px; }

/* ══════════════════════════════════════════
   PAGE BODY
══════════════════════════════════════════ */
.page-body { padding:28px 32px; flex:1; }
.page-header { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:24px; }
.page-eyebrow { font-size:10.5px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--red); margin-bottom:4px; }
.page-heading { font-size:22px; font-weight:800; color:var(--text-1); letter-spacing:-.03em; line-height:1; }
.page-sub { font-size:12px; color:var(--text-3); margin-top:5px; }
.page-date-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; background:var(--white); border:1px solid var(--border); border-radius:20px; font-size:11px; color:var(--text-3); font-weight:500; }
.page-date-badge i { color:var(--text-4); font-size:10px; }

/* date nav */
.date-nav-pill {
  display:inline-flex; align-items:center; gap:10px;
  background:var(--white); border:1px solid var(--border);
  border-radius:var(--radius-sm); padding:7px 14px;
  font-size:13px; font-weight:600; color:var(--text-2);
  cursor:pointer; user-select:none;
}
.date-nav-pill button {
  background:none; border:none; cursor:pointer;
  color:var(--text-3); padding:0 2px; font-size:12px;
  display:flex; align-items:center;
  transition:color .12s;
}
.date-nav-pill button:hover { color:var(--text-1); }

/* ── KPI / SUMMARY CARDS ── */
.kpi-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-bottom:28px; }
.kpi-card {
  background:var(--white); border:1px solid var(--border);
  border-radius:var(--radius); padding:20px 22px;
  position:relative; overflow:hidden; transition:all .22s ease;
}
.kpi-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; border-radius:var(--radius) var(--radius) 0 0; }
.kpi-card.k-green::before { background:var(--green); }
.kpi-card.k-amber::before { background:#d97706; }
.kpi-card.k-blue::before  { background:var(--blue); }
.kpi-card:hover { transform:translateY(-3px); box-shadow:var(--shadow); }
.kpi-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.kpi-card-label  { display:flex; align-items:center; gap:7px; font-size:11.5px; font-weight:700; color:var(--text-2); }
.kpi-dot { width:8px; height:8px; border-radius:50%; }
.kpi-dot-green  { background:#22c55e; }
.kpi-dot-amber  { background:#f59e0b; }
.kpi-dot-blue   { background:#3b82f6; }
.kpi-card-more  { color:var(--text-4); cursor:pointer; font-size:16px; font-weight:700; letter-spacing:.05em; }
.kpi-stat-row   { display:flex; gap:22px; flex-wrap:wrap; }
.kpi-stat { }
.kpi-stat label { display:block; font-size:9.5px; color:var(--text-3); text-transform:uppercase; letter-spacing:.08em; margin-bottom:2px; font-weight:700; }
.kpi-stat .val  { font-size:28px; font-weight:800; color:var(--text-1); line-height:1; letter-spacing:-.03em; font-variant-numeric:tabular-nums; }
.kpi-stat .chg  { font-size:10.5px; margin-top:3px; font-weight:500; }
.chg-up   { color:var(--green); }
.chg-down { color:var(--red); }
.chg-flat { color:var(--text-4); }

/* ── TABLE CARD ── */
.tbl-card { background:var(--white); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm); overflow:hidden; }
.tbl-toolbar {
  display:flex; align-items:center; gap:10px;
  padding:14px 20px; border-bottom:1px solid var(--border);
  background:var(--surface); flex-wrap:wrap;
}
.search-field {
  display:flex; align-items:center; gap:8px;
  background:var(--white); border:1px solid var(--border);
  border-radius:var(--radius-sm); padding:8px 13px; min-width:220px;
  transition:border-color .15s, box-shadow .15s;
}
.search-field:focus-within { border-color:var(--navy-3); box-shadow:0 0 0 3px rgba(26,37,128,.08); }
.search-field i { color:var(--text-4); font-size:11px; }
.search-field input { border:none; background:transparent; font-size:12.5px; color:var(--text-1); outline:none; font-family:var(--font); width:160px; }
.search-field input::placeholder { color:var(--text-4); }
.filter-btn {
  display:inline-flex; align-items:center; gap:6px;
  padding:8px 13px; border-radius:var(--radius-sm);
  border:1px solid var(--border); background:var(--white);
  color:var(--text-2); font-size:12.5px; cursor:pointer;
  font-family:var(--font); white-space:nowrap; transition:all .15s;
}
.filter-btn:hover { background:var(--surface-2); border-color:var(--border-2); }
.filter-btn i { font-size:13px; color:var(--text-3); }
.ml-auto { margin-left:auto; }
.icon-btn { width:35px; height:35px; border-radius:var(--radius-sm); border:1px solid var(--border); background:var(--white); display:flex; align-items:center; justify-content:center; color:var(--text-3); cursor:pointer; font-size:12px; transition:all .15s; }
.icon-btn:hover { background:var(--surface-2); color:var(--text-1); border-color:var(--border-2); }
.btn-add-small {
  display:inline-flex; align-items:center; gap:6px;
  background:var(--navy); color:var(--white); border:none;
  border-radius:var(--radius-sm); padding:8px 16px;
  font-size:12.5px; font-weight:700; cursor:pointer; font-family:var(--font);
  transition:all .15s;
}
.btn-add-small:hover { background:var(--navy-2); }

/* TABLE */
.tbl-scroll { overflow-x:auto; }
table.mtbl { width:100%; border-collapse:collapse; min-width:860px; }
table.mtbl thead { background:var(--surface); border-bottom:2px solid var(--border); }
table.mtbl thead th { padding:11px 16px; font-size:10.5px; font-weight:700; color:var(--text-3); text-transform:uppercase; letter-spacing:.1em; text-align:left; white-space:nowrap; }
table.mtbl thead th:first-child { padding-left:24px; }
table.mtbl tbody tr { border-bottom:1px solid var(--border); transition:background .12s; }
table.mtbl tbody tr:last-child { border-bottom:none; }
table.mtbl tbody tr:hover td { background:var(--navy-pale); cursor:pointer; }
table.mtbl td { padding:13px 16px; vertical-align:middle; font-size:12.5px; color:var(--text-1); }
table.mtbl td:first-child { padding-left:24px; }

/* employee cell */
.emp-cell { display:flex; align-items:center; gap:10px; }
.emp-avatar { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#fff; flex-shrink:0; letter-spacing:.02em; }
.emp-name { font-size:13px; font-weight:600; color:var(--text-1); line-height:1.2; }
.emp-id   { font-size:11px; color:var(--text-4); font-family:var(--mono); }

/* time */
.time-in  { color:var(--blue); font-weight:600; font-family:var(--mono); }
.time-out { color:var(--red);  font-weight:600; font-family:var(--mono); }
.time-dur { color:var(--text-3); font-size:11.5px; font-family:var(--mono); }

/* OT badge */
.ot-badge { background:var(--amber-bg); color:var(--amber); font-size:11px; padding:3px 9px; border-radius:6px; font-weight:700; display:inline-flex; align-items:center; gap:4px; font-family:var(--mono); }

/* picture link */
.pic-link { color:var(--blue); font-size:12px; text-decoration:none; font-family:var(--mono); transition:color .12s; }
.pic-link:hover { color:var(--navy); text-decoration:underline; }

/* location */
.loc-link { display:inline-flex; align-items:center; gap:5px; color:var(--blue); font-size:12px; text-decoration:none; transition:color .12s; max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.loc-link:hover { color:var(--navy); }
.loc-link i { font-size:12px; flex-shrink:0; }

/* note */
.note-text { font-size:12px; color:var(--text-2); max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:block; }

/* late tag */
.late-tag { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700; padding:2px 7px; border-radius:5px; background:var(--red-tint); color:var(--red); margin-left:6px; vertical-align:middle; }

/* tbl footer */
.tbl-footer { display:flex; align-items:center; justify-content:space-between; padding:14px 24px; border-top:1px solid var(--border); background:var(--surface); }
.tbl-count { font-size:12px; color:var(--text-3); }
.tbl-count strong { color:var(--text-1); font-weight:700; }

/* alert */
.alert { border-radius:var(--radius-sm); font-size:12.5px; margin-bottom:18px; }
.alert-success { background:var(--green-bg); color:var(--green); border:1px solid #a7f3d0; }
.alert-danger  { background:var(--red-tint); color:var(--red); border:1px solid #fecaca; }
</style>
</head>
<body>

<!-- ═══════════════════════════════════════════
   SIDEBAR — exact structure & classes from delivery_tracking.php
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
      Dashboard
    </a>
    <a class="sb-item" href="purchase_order.php">
      <i class="ti ti-shopping-cart"></i>
      Purchase Order
    </a>
    <a class="sb-item" href="purchase_invoice.php">
      <i class="ti ti-file-invoice"></i>
      Purchase Invoice
    </a>
    <a class="sb-item" href="employee_profile.php">
      <i class="ti ti-users"></i>
      Employee Profile
    </a>
    <a class="sb-item active" href="attendance.php">
      <i class="ti ti-calendar-check"></i>
      Attendance
    </a>

    <!-- LOGISTICS -->
    <div class="sb-section">Logistics</div>

    <a class="sb-item" href="delivery_tracking.php">
      <i class="ti ti-truck-delivery"></i>
      Delivery Tracking
    </a>
    <a class="sb-item" href="delivery_history.php">
      <i class="ti ti-history"></i>
      Delivery History
    </a>

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
        <div class="topbar-title">Attendance</div>
        <div class="topbar-sub">HR · Daily employee clock-in & out</div>
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
      <a href="attendance_report.php" class="btn-add" style="background:var(--surface);color:var(--text-2);border:1px solid var(--border);box-shadow:none;">
        <span class="btn-icon" style="background:rgba(10,16,69,.07)"><i class="fas fa-file-alt"></i></span>
        Attendance Report
      </a>
      <button class="btn-add">
        <span class="btn-icon"><i class="fas fa-plus"></i></span>
        Add
      </button>
    </div>
  </header>

  <div class="page-body">

    <!-- Page header -->
    <div class="page-header fade-up">
      <div>
        <div class="page-eyebrow">HR</div>
        <div class="page-heading">
          Attendance
          <span style="font-size:16px;font-weight:400;color:var(--text-4);margin-left:8px"><?php echo count($employees); ?> employees today</span>
        </div>
        <div class="page-sub">Daily clock-in and clock-out records</div>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <div class="date-nav-pill">
          <button><i class="fas fa-chevron-left"></i></button>
          <span><?php echo htmlspecialchars($date_label); ?></span>
          <button><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="page-date-badge">
          <i class="fas fa-calendar"></i>
          <?php echo date('d M Y'); ?>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="tbl-card fade-up fade-up-1">

      <div class="tbl-toolbar">
        <div class="search-field">
          <i class="fas fa-magnifying-glass"></i>
          <input type="text" id="searchInput" placeholder="Search employee…" onkeyup="filterTable()">
        </div>
        <button class="filter-btn"><i class="ti ti-calendar" style="font-size:14px"></i> Date Range</button>
        <button class="filter-btn"><i class="ti ti-adjustments-horizontal" style="font-size:14px"></i> Advance Filter</button>
        <div class="ml-auto" style="display:flex;gap:6px">
          <div class="icon-btn" title="Export"><i class="fas fa-download"></i></div>
          <div class="icon-btn" title="Refresh"><i class="fas fa-rotate-right"></i></div>
        </div>
      </div>

      <div class="tbl-scroll">
        <table class="mtbl" id="empTable">
          <thead>
            <tr>
              <th>Employee Name</th>
              <th>Clock-in &amp; Out</th>
              <th>Overtime</th>
              <th>Picture</th>
              <th>Location</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($employees as $idx => $emp):
              $init = initials($emp['name']);
              $slug = strtolower(str_replace(' ', '_', $emp['name']));
            ?>
            <tr onclick="openEmpPanel(<?php echo $idx; ?>)" style="cursor:pointer">
              <td>
                <div class="emp-cell">
                  <div class="emp-avatar" style="background:<?php echo htmlspecialchars($emp['color']); ?>">
                    <?php echo htmlspecialchars($init); ?>
                  </div>
                  <div>
                    <div class="emp-name">
                      <?php echo htmlspecialchars($emp['name']); ?>
                      <?php if ($emp['late']): ?>
                        <span class="late-tag"><i class="fas fa-clock" style="font-size:8px"></i> Late</span>
                      <?php endif; ?>
                    </div>
                    <div class="emp-id"><?php echo htmlspecialchars($emp['id']); ?></div>
                  </div>
                </div>
              </td>
              <td>
                <span class="time-in"><?php echo htmlspecialchars($emp['in']); ?></span>
                <span class="time-dur"> &nbsp;<?php echo htmlspecialchars($emp['dur']); ?>&nbsp; </span>
                <span class="time-out"><?php echo htmlspecialchars($emp['out']); ?></span>
              </td>
              <td>
                <?php if (!empty($emp['ot'])): ?>
                  <span class="ot-badge"><i class="fas fa-plus" style="font-size:8px"></i> <?php echo htmlspecialchars($emp['ot']); ?></span>
                <?php else: ?>
                  <span style="color:var(--text-4)">—</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="profile.php?id=<?php echo urlencode($emp['id']); ?>" class="pic-link" onclick="event.stopPropagation()">
                  <?php echo htmlspecialchars($slug); ?>_profi...
                </a>
              </td>
              <td>
                <a href="#" class="loc-link" onclick="event.stopPropagation()">
                  <i class="ti ti-map-pin"></i>
                  <?php echo htmlspecialchars($emp['loc']); ?>
                </a>
              </td>
              <td>
                <span class="note-text" title="<?php echo htmlspecialchars($emp['note']); ?>">
                  <?php echo htmlspecialchars($emp['note']); ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="tbl-footer">
        <div class="tbl-count">
          Showing <strong><?php echo count($employees); ?></strong> employees
        </div>
        <div style="display:flex;gap:6px">
          <button class="icon-btn" disabled><i class="fas fa-chevron-left" style="font-size:10px"></i></button>
          <button style="width:30px;height:30px;border-radius:6px;border:none;background:var(--navy);color:var(--white);font-size:12px;font-weight:700;cursor:default">1</button>
          <button class="icon-btn"><i class="fas fa-chevron-right" style="font-size:10px"></i></button>
        </div>
      </div>

    </div><!-- /tbl-card -->
  </div><!-- /page-body -->
</div><!-- /main-wrap -->

<!-- ═══════════════════════════════════════════
   EMPLOYEE DETAIL PANEL
═══════════════════════════════════════════ -->
<style>
/* Overlay */
.ep-overlay {
  display:none; position:fixed; inset:0;
  background:rgba(10,16,69,.35); z-index:800;
  backdrop-filter:blur(3px);
}
.ep-overlay.open { display:block; }

/* Panel */
.emp-panel {
  position:fixed; top:0; right:-720px;
  width:680px; max-width:100vw; height:100vh;
  background:var(--white); z-index:900;
  box-shadow:-12px 0 60px rgba(10,16,69,.15);
  display:flex; flex-direction:column;
  transition:right .35s cubic-bezier(.4,0,.2,1);
  overflow:hidden;
}
.emp-panel.open { right:0; }

/* Panel close X */
.ep-close-btn {
  position:absolute; top:16px; left:-44px;
  width:36px; height:36px; border-radius:50%;
  background:rgba(255,255,255,.95); border:none;
  display:flex; align-items:center; justify-content:center;
  cursor:pointer; box-shadow:0 2px 14px rgba(10,16,69,.2);
  color:var(--text-3); font-size:12px; z-index:10;
  transition:all .15s;
}
.ep-close-btn:hover { background:var(--white); color:var(--text-1); }

/* Panel nav counter */
.ep-nav-counter {
  font-size:11px; color:rgba(255,255,255,.5);
  font-family:var(--mono);
}
.ep-nav-btns { display:flex; gap:4px; }
.ep-nav-btn {
  width:28px; height:28px; border-radius:6px;
  border:1px solid rgba(255,255,255,.18);
  background:rgba(255,255,255,.08);
  color:rgba(255,255,255,.65); font-size:11px;
  display:flex; align-items:center; justify-content:center;
  cursor:pointer; transition:all .12s;
}
.ep-nav-btn:hover { background:rgba(255,255,255,.16); color:#fff; }

/* Header */
.ep-hdr {
  background:var(--navy); padding:20px 24px 18px;
  flex-shrink:0; position:relative;
}
.ep-hdr::after {
  content:''; position:absolute; top:0; left:0; right:0;
  height:3px; background:var(--red);
}
.ep-hdr-top {
  display:flex; align-items:flex-start;
  justify-content:space-between; margin-bottom:14px;
}
.ep-hdr-right { display:flex; align-items:center; gap:8px; }
.ep-profile-row { display:flex; align-items:center; gap:14px; }
.ep-big-avatar {
  width:54px; height:54px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-size:18px; font-weight:800; color:#fff;
  flex-shrink:0; border:2.5px solid rgba(255,255,255,.25);
}
.ep-emp-name  { font-size:18px; font-weight:800; color:#fff; letter-spacing:-.02em; line-height:1.15; }
.ep-emp-role  { font-size:12px; color:rgba(255,255,255,.5); margin-top:1px; }
.ep-emp-meta  { display:flex; gap:18px; margin-top:6px; flex-wrap:wrap; }
.ep-meta-item { display:flex; flex-direction:column; gap:1px; }
.ep-meta-lbl  { font-size:9.5px; color:rgba(255,255,255,.35); text-transform:uppercase; letter-spacing:.1em; font-weight:700; }
.ep-meta-val  { font-size:12px; color:rgba(255,255,255,.8); font-family:var(--mono); }

/* Stats strip */
.ep-stats-strip {
  display:flex; background:var(--white);
  border-bottom:1px solid var(--border);
  flex-shrink:0; overflow-x:auto;
}
.ep-stat-col {
  flex:1; min-width:90px; padding:14px 16px;
  border-right:1px solid var(--border);
  text-align:center;
}
.ep-stat-col:last-child { border-right:none; }
.ep-stat-num  { font-size:22px; font-weight:800; color:var(--text-1); letter-spacing:-.03em; line-height:1; }
.ep-stat-lbl  { font-size:10px; color:var(--text-3); text-transform:uppercase; letter-spacing:.07em; font-weight:700; margin-top:3px; }
.ep-stat-chg  { font-size:10px; margin-top:3px; font-weight:500; }
.ep-stat-chg.up   { color:var(--green); }
.ep-stat-chg.down { color:var(--red); }

/* Body scroll area */
.ep-body {
  flex:1; overflow-y:auto; background:var(--bg);
}
.ep-body::-webkit-scrollbar { width:3px; }
.ep-body::-webkit-scrollbar-thumb { background:var(--border-2); border-radius:2px; }

/* Month nav + search */
.ep-month-bar {
  display:flex; align-items:center; justify-content:space-between;
  padding:14px 20px; background:var(--white);
  border-bottom:1px solid var(--border);
  flex-shrink:0; gap:12px; flex-wrap:wrap;
}
.ep-month-nav {
  display:flex; align-items:center; gap:10px;
  font-size:14px; font-weight:700; color:var(--text-1);
}
.ep-month-nav button {
  background:none; border:none; cursor:pointer;
  color:var(--text-3); font-size:12px; padding:2px 4px;
  border-radius:4px; transition:all .12s;
}
.ep-month-nav button:hover { background:var(--surface-2); color:var(--text-1); }
.ep-search-mini {
  display:flex; align-items:center; gap:7px;
  background:var(--surface); border:1px solid var(--border);
  border-radius:var(--radius-sm); padding:6px 11px; font-size:12px;
}
.ep-search-mini input { border:none; background:transparent; outline:none; font-size:12px; font-family:var(--font); width:120px; color:var(--text-1); }
.ep-search-mini input::placeholder { color:var(--text-4); }
.ep-status-sel {
  font-size:12px; border:1px solid var(--border); border-radius:var(--radius-sm);
  padding:6px 10px; background:var(--white); color:var(--text-2);
  outline:none; font-family:var(--font); cursor:pointer;
}

/* Day cards */
.ep-day-list { padding:16px; display:flex; flex-direction:column; gap:12px; }
.ep-day-card {
  background:var(--white); border:1px solid var(--border);
  border-radius:var(--radius); overflow:hidden;
  transition:box-shadow .15s;
}
.ep-day-card:hover { box-shadow:var(--shadow-sm); }

/* Day card header */
.ep-day-hdr {
  display:flex; align-items:center; justify-content:space-between;
  padding:11px 16px; border-bottom:1px solid var(--border);
}
.ep-day-title { font-size:13px; font-weight:700; color:var(--text-1); }
.ep-day-badge {
  display:inline-flex; align-items:center; gap:5px;
  font-size:11px; font-weight:700; padding:3px 10px; border-radius:6px;
}
.ep-day-badge.approved  { background:var(--green-bg); color:var(--green); }
.ep-day-badge.ot-pend   { background:var(--amber-bg); color:var(--amber); }
.ep-day-badge.requested { background:var(--blue-bg);  color:var(--blue); }

/* Timeline bar */
.ep-timeline-wrap { padding:10px 16px 4px; }
.ep-tl-hours {
  display:flex; justify-content:space-between;
  font-size:9.5px; color:var(--text-4); font-family:var(--mono);
  margin-bottom:4px; padding:0 2px;
}
.ep-tl-bar {
  position:relative; height:22px; background:var(--surface-2);
  border-radius:6px; overflow:hidden; margin-bottom:6px;
}
.ep-tl-seg {
  position:absolute; top:0; height:100%; border-radius:3px;
  display:flex; align-items:center; justify-content:center;
  font-size:9px; font-weight:700; color:#fff; white-space:nowrap;
  overflow:hidden; padding:0 4px;
}
.seg-work  { background:#1e40af; }
.seg-break { background:#f59e0b; }
.seg-over  { background:#ef4444; }
.seg-off   { background:#fde68a; border:1px dashed #d97706; }

/* Tooltip bubble */
.ep-tl-tooltip {
  position:absolute; top:-40px; left:50%;
  transform:translateX(-50%);
  background:var(--navy); color:#fff;
  font-size:10px; font-weight:600; padding:4px 9px;
  border-radius:6px; white-space:nowrap; z-index:10;
  display:none;
  font-family:var(--mono);
}
.ep-tl-tooltip::after {
  content:''; position:absolute; top:100%; left:50%;
  transform:translateX(-50%);
  border:4px solid transparent; border-top-color:var(--navy);
}
.ep-tl-bar:hover .ep-tl-tooltip { display:block; }

/* Attendance photos */
.ep-photos {
  display:flex; gap:12px; padding:0 16px 14px; flex-wrap:wrap;
}
.ep-photo-item { display:flex; flex-direction:column; gap:5px; }
.ep-photo-lbl  { font-size:9.5px; color:var(--text-3); text-transform:uppercase; letter-spacing:.08em; font-weight:700; }
.ep-photo-thumb {
  width:72px; height:72px; border-radius:10px;
  position:relative; overflow:hidden; cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  border:2px solid rgba(255,255,255,.3);
  box-shadow:var(--shadow-sm);
  transition:transform .15s, box-shadow .15s;
}
.ep-photo-thumb:hover { transform:scale(1.04); box-shadow:var(--shadow); }
.ep-photo-init {
  font-size:20px; font-weight:800; color:rgba(255,255,255,.9);
  letter-spacing:.02em; z-index:1;
}
.ep-photo-overlay {
  position:absolute; inset:0; background:rgba(0,0,0,.18);
  display:flex; align-items:flex-start; justify-content:flex-end;
  padding:5px;
}
.ep-photo-overlay i { font-size:10px; color:rgba(255,255,255,.7); }
.ep-photo-time {
  position:absolute; bottom:0; left:0; right:0;
  background:rgba(0,0,0,.45); color:#fff;
  font-size:9px; font-weight:700; text-align:center;
  padding:2px 0; font-family:var(--mono);
}

/* Lightbox */
.lb-overlay {
  display:none; position:fixed; inset:0; z-index:9999;
  background:rgba(0,0,0,.88); backdrop-filter:blur(6px);
  align-items:center; justify-content:center; flex-direction:column; gap:16px;
}
.lb-overlay.open { display:flex; }
.lb-box {
  background:var(--white); border-radius:var(--radius);
  overflow:hidden; box-shadow:0 30px 80px rgba(0,0,0,.5);
  max-width:340px; width:90%; animation:lbPop .2s cubic-bezier(.22,1,.36,1);
}
@keyframes lbPop {
  from { opacity:0; transform:scale(.92); }
  to   { opacity:1; transform:scale(1); }
}
.lb-photo {
  width:100%; aspect-ratio:1/1;
  display:flex; align-items:center; justify-content:center;
  font-size:72px; font-weight:800; color:rgba(255,255,255,.9);
  position:relative;
}
.lb-meta {
  padding:14px 18px; border-top:1px solid var(--border);
  display:flex; align-items:center; justify-content:space-between;
}
.lb-meta-left { }
.lb-meta-type { font-size:10px; text-transform:uppercase; letter-spacing:.1em; font-weight:700; color:var(--text-3); margin-bottom:2px; }
.lb-meta-time { font-size:15px; font-weight:800; color:var(--text-1); font-family:var(--mono); }
.lb-meta-name { font-size:11px; color:var(--text-3); margin-top:2px; }
.lb-close-btn {
  background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2);
  color:#fff; border-radius:50%; width:38px; height:38px;
  display:flex; align-items:center; justify-content:center;
  cursor:pointer; font-size:14px; transition:all .15s;
  flex-shrink:0; align-self:flex-start; margin-top:4px;
}
.lb-close-btn:hover { background:rgba(255,255,255,.22); }
.ep-clock-row {
  display:flex; align-items:center; gap:24px;
  padding:8px 16px 12px; flex-wrap:wrap;
}
.ep-clock-item { }
.ep-clock-lbl { font-size:9.5px; color:var(--text-3); text-transform:uppercase; letter-spacing:.08em; font-weight:700; margin-bottom:2px; }
.ep-clock-val { font-size:13px; font-weight:700; color:var(--text-1); font-family:var(--mono); }
.ep-clock-val.blue { color:var(--blue); }
.ep-clock-val.red  { color:var(--red); }
.ep-clock-val.muted { color:var(--text-4); }
</style>

<!-- Lightbox -->
<div class="lb-overlay" id="lbOverlay" onclick="closeLightbox()">
  <button class="lb-close-btn" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
  <div class="lb-box" onclick="event.stopPropagation()">
    <div class="lb-photo" id="lb-photo"></div>
    <div class="lb-meta">
      <div class="lb-meta-left">
        <div class="lb-meta-type" id="lb-type">Clock-in Photo</div>
        <div class="lb-meta-time" id="lb-time">—</div>
        <div class="lb-meta-name" id="lb-name">—</div>
      </div>
      <i class="fas fa-expand" style="color:var(--text-4);font-size:14px;cursor:pointer"></i>
    </div>
  </div>
</div>

<!-- Panel overlay -->
<div class="ep-overlay" id="epOverlay" onclick="closeEmpPanel()"></div>

<!-- Employee Detail Panel -->
<div class="emp-panel" id="empPanel">
  <button class="ep-close-btn" onclick="closeEmpPanel()"><i class="fas fa-times"></i></button>

  <!-- Header -->
  <div class="ep-hdr">
    <div class="ep-hdr-top">
      <!-- Profile -->
      <div class="ep-profile-row">
        <div class="ep-big-avatar" id="ep-avatar"></div>
        <div>
          <div class="ep-emp-name" id="ep-name">—</div>
          <div class="ep-emp-role" id="ep-role">UI Designer</div>
          <div class="ep-emp-meta">
            <div class="ep-meta-item">
              <span class="ep-meta-lbl">Employee ID</span>
              <span class="ep-meta-val" id="ep-empid">—</span>
            </div>
            <div class="ep-meta-item">
              <span class="ep-meta-lbl">Phone Number</span>
              <span class="ep-meta-val" id="ep-phone">—</span>
            </div>
          </div>
        </div>
      </div>
      <!-- Nav -->
      <div class="ep-hdr-right">
        <span class="ep-nav-counter" id="ep-counter">1 out of 9</span>
        <div class="ep-nav-btns">
          <button class="ep-nav-btn" onclick="navigatePanel(-1)"><i class="fas fa-chevron-left"></i></button>
          <button class="ep-nav-btn" onclick="navigatePanel(1)"><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- Stats Strip -->
  <div class="ep-stats-strip">
    <div class="ep-stat-col">
      <div class="ep-stat-num">12</div>
      <div class="ep-stat-lbl">Day off</div>
      <div class="ep-stat-chg up">+12 vs last month</div>
    </div>
    <div class="ep-stat-col">
      <div class="ep-stat-num" id="ep-late-count">6</div>
      <div class="ep-stat-lbl">Late clock-in</div>
      <div class="ep-stat-chg down">-2 vs last month</div>
    </div>
    <div class="ep-stat-col">
      <div class="ep-stat-num">21</div>
      <div class="ep-stat-lbl">Late clock-out</div>
      <div class="ep-stat-chg down">-12 vs last month</div>
    </div>
    <div class="ep-stat-col">
      <div class="ep-stat-num">2</div>
      <div class="ep-stat-lbl">No clock-out</div>
      <div class="ep-stat-chg up">+4 vs last month</div>
    </div>
    <div class="ep-stat-col">
      <div class="ep-stat-num">0</div>
      <div class="ep-stat-lbl">Off time quota</div>
      <div class="ep-stat-chg up">0 vs last month</div>
    </div>
    <div class="ep-stat-col">
      <div class="ep-stat-num">2</div>
      <div class="ep-stat-lbl">Absent</div>
      <div class="ep-stat-chg up">0 vs last month</div>
    </div>
  </div>

  <!-- Scrollable body -->
  <div class="ep-body">

    <!-- Month bar -->
    <div class="ep-month-bar">
      <div class="ep-month-nav">
        <button><i class="fas fa-chevron-left"></i></button>
        <span>October 2023</span>
        <button><i class="fas fa-chevron-right"></i></button>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <div class="ep-search-mini">
          <i class="fas fa-magnifying-glass" style="color:var(--text-4);font-size:10px"></i>
          <input type="text" placeholder="Search">
        </div>
        <select class="ep-status-sel">
          <option>All Status</option>
          <option>On Time</option>
          <option>Late</option>
          <option>Absent</option>
        </select>
      </div>
    </div>

    <!-- Day list -->
    <div class="ep-day-list" id="ep-day-list">
      <!-- Injected by JS -->
    </div>

  </div><!-- /ep-body -->
</div><!-- /emp-panel -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Employee data passed from PHP ──
const employees = <?php echo json_encode(array_values($employees)); ?>;
let currentEmpIdx = null;

// Sample daily records per employee (in a real app, fetched from DB)
const sampleDays = [
  {
    label:'Today', date:'', badge:'ot', badgeText:'Overtime approval',
    showApprove:true,
    clockIn:'09:00 AM', clockOut:'09:12 PM', duration:'10h 12m',
    tooltip:'Working time\n09:00 – 12:30  (3h 1m)',
    segments:[
      {left:'0%',   width:'38%', cls:'seg-work',  label:'Working time'},
      {left:'38%',  width:'10%', cls:'seg-break', label:'Break'},
      {left:'48%',  width:'30%', cls:'seg-work',  label:'Working time'},
      {left:'78%',  width:'22%', cls:'seg-over',  label:'Over time'},
    ]
  },
  {
    label:'Thursday, 18', date:'', badge:'approved', badgeText:'Approved',
    showApprove:false,
    clockIn:'—', clockOut:'—', duration:'—',
    tooltip:'',
    segments:[
      {left:'0%', width:'100%', cls:'seg-off', label:'Requested day off'},
    ]
  },
  {
    label:'Wednesday, 17', date:'', badge:'', badgeText:'',
    showApprove:false,
    clockIn:'09:00 AM', clockOut:'05:00PM', duration:'8 hour',
    tooltip:'',
    segments:[
      {left:'0%',  width:'40%', cls:'seg-work',  label:'Working time'},
      {left:'40%', width:'8%',  cls:'seg-break', label:'Break'},
      {left:'48%', width:'38%', cls:'seg-work',  label:'Working time'},
    ]
  },
  {
    label:'Tuesday, 16', date:'', badge:'', badgeText:'',
    showApprove:false,
    clockIn:'09:00 AM', clockOut:'07:12 PM', duration:'8 hour',
    tooltip:'',
    segments:[
      {left:'0%',  width:'6%',  cls:'seg-over',  label:'Late'},
      {left:'6%',  width:'36%', cls:'seg-work',  label:'Working time'},
      {left:'42%', width:'8%',  cls:'seg-break', label:'Break'},
      {left:'50%', width:'36%', cls:'seg-work',  label:'Working time'},
    ]
  },
  {
    label:'Monday, 15', date:'', badge:'', badgeText:'',
    showApprove:false,
    clockIn:'09:00 AM', clockOut:'05:00 PM', duration:'8 hour',
    tooltip:'',
    segments:[
      {left:'0%',  width:'40%', cls:'seg-work',  label:'Working time'},
      {left:'40%', width:'8%',  cls:'seg-break', label:'Break'},
      {left:'48%', width:'38%', cls:'seg-work',  label:'Working time'},
    ]
  },
];

const hours = ['09:00','11:00','13:00','15:00','17:00','19:00','21:00','23:59'];

function renderDayCard(day, empIdx) {
  const badgeHtml = day.badge === 'approved'
    ? `<span class="ep-day-badge approved"><i class="fas fa-circle-check" style="font-size:9px"></i> Approved</span>`
    : '';

  const segsHtml = day.segments.map(s =>
    `<div class="ep-tl-seg ${s.cls}" style="left:${s.left};width:${s.width}">${s.label}</div>`
  ).join('');

  const tooltipHtml = day.tooltip
    ? `<div class="ep-tl-tooltip">${day.tooltip.replace('\n','<br>')}</div>`
    : '';

  const clockIn  = day.clockIn  !== '—' ? `<span class="ep-clock-val blue">${day.clockIn}</span>`  : `<span class="ep-clock-val muted">—</span>`;
  const clockOut = day.clockOut !== '—' ? `<span class="ep-clock-val red">${day.clockOut}</span>` : `<span class="ep-clock-val muted">—</span>`;
  const dur      = day.duration !== '—' ? `<span class="ep-clock-val">${day.duration}</span>`      : `<span class="ep-clock-val muted">—</span>`;

  // Attendance photos — clock-in and clock-out selfie thumbnails
  const emp = employees[empIdx] || {};
  const color = emp.color || '#888';
  const init  = (emp.name||'?').split(' ').slice(0,2).map(p=>p[0]).join('').toUpperCase();

  const photoHtml = day.clockIn !== '—' ? `
    <div class="ep-photos">
      <div class="ep-photo-item">
        <div class="ep-photo-lbl">Clock-in photo</div>
        <div class="ep-photo-thumb" style="background:${color}" onclick="openLightbox('Clock-in Photo','${day.clockIn}','${init}','${color}','${emp.name||''}')">
          <span class="ep-photo-init">${init}</span>
          <div class="ep-photo-overlay"><i class="fas fa-camera"></i></div>
          <div class="ep-photo-time">${day.clockIn}</div>
        </div>
      </div>
      ${day.clockOut !== '—' ? `
      <div class="ep-photo-item">
        <div class="ep-photo-lbl">Clock-out photo</div>
        <div class="ep-photo-thumb" style="background:${color}88" onclick="openLightbox('Clock-out Photo','${day.clockOut}','${init}','${color}88','${emp.name||''}')">
          <span class="ep-photo-init">${init}</span>
          <div class="ep-photo-overlay"><i class="fas fa-camera"></i></div>
          <div class="ep-photo-time">${day.clockOut}</div>
        </div>
      </div>` : ''}
    </div>` : '';

  return `
  <div class="ep-day-card">
    <div class="ep-day-hdr">
      <div class="ep-day-title">${day.label}</div>
      <div>${badgeHtml}</div>
    </div>
    <div class="ep-timeline-wrap">
      <div class="ep-tl-hours">
        ${hours.map(h=>`<span>${h}</span>`).join('')}
      </div>
      <div class="ep-tl-bar">
        ${tooltipHtml}
        ${segsHtml}
      </div>
    </div>
    <div class="ep-clock-row">
      <div class="ep-clock-item">
        <div class="ep-clock-lbl">Clock-in</div>
        ${clockIn}
      </div>
      <div class="ep-clock-item">
        <div class="ep-clock-lbl">Clock-out</div>
        ${clockOut}
      </div>
      <div class="ep-clock-item" style="margin-left:auto">
        <div class="ep-clock-lbl">Duration</div>
        ${dur}
      </div>
    </div>
    ${photoHtml}
  </div>`;
}

function openLightbox(type, time, init, color, name) {
  document.getElementById('lb-photo').style.background = color;
  document.getElementById('lb-photo').innerHTML = `<span style="font-size:72px;font-weight:800;color:rgba(255,255,255,.9)">${init}</span>`;
  document.getElementById('lb-type').textContent = type;
  document.getElementById('lb-time').textContent = time;
  document.getElementById('lb-name').textContent = name;
  document.getElementById('lbOverlay').classList.add('open');
}
function closeLightbox() {
  document.getElementById('lbOverlay').classList.remove('open');
}

function openEmpPanel(idx) {
  currentEmpIdx = idx;
  const emp = employees[idx];
  if (!emp) return;

  // Fill header
  const init = emp.name.split(' ').slice(0,2).map(p=>p[0]).join('').toUpperCase();
  const av = document.getElementById('ep-avatar');
  av.textContent = init;
  av.style.background = emp.color;
  document.getElementById('ep-name').textContent    = emp.name;
  document.getElementById('ep-empid').textContent   = '#EMP0' + (idx+1);
  document.getElementById('ep-phone').textContent   = '+62 921 019 ' + (100 + idx);
  document.getElementById('ep-counter').textContent = (idx+1) + ' out of ' + employees.length;
  document.getElementById('ep-late-count').textContent = emp.late ? '6' : '0';

  // Render days
  document.getElementById('ep-day-list').innerHTML = sampleDays.map(d => renderDayCard(d, idx)).join('');

  // Open
  document.getElementById('epOverlay').classList.add('open');
  document.getElementById('empPanel').classList.add('open');
}

function closeEmpPanel() {
  document.getElementById('epOverlay').classList.remove('open');
  document.getElementById('empPanel').classList.remove('open');
  currentEmpIdx = null;
}

function navigatePanel(dir) {
  if (currentEmpIdx === null) return;
  const next = currentEmpIdx + dir;
  if (next >= 0 && next < employees.length) openEmpPanel(next);
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    if (document.getElementById('lbOverlay').classList.contains('open')) closeLightbox();
    else closeEmpPanel();
  }
});

function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('#empTable tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// Mobile sidebar
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
