<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('index.php');
}

$delivery_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$delivery = $delivery_id > 0 ? get_delivery_by_id($delivery_id) : null;

if (!$delivery) {
    http_response_code(404);
}

// Helper functions
function status_to_step($status) {
    return match($status) {
        'pending'    => 0,
        'approved'   => 1,
        'in_transit' => 3,
        'delivered'  => 4,
        default      => 0,
    };
}

function status_pill($status) {
    return match($status) {
        'pending'    => ['label' => 'Pending',    'cls' => 'pill-pending'],
        'approved'   => ['label' => 'Approved',   'cls' => 'pill-approved'],
        'in_transit' => ['label' => 'In Transit', 'cls' => 'pill-transit'],
        'delivered'  => ['label' => 'Delivered',  'cls' => 'pill-delivered'],
        'cancelled'  => ['label' => 'Cancelled',  'cls' => 'pill-cancelled'],
        default      => ['label' => ucfirst($status), 'cls' => 'pill-default'],
    };
}

function carrier_badge($carrier) {
    if (!$carrier) return '<span class="carrier c-regular">N/A</span>';
    $lower = strtolower($carrier);
    $cls = match(true) {
        str_contains($lower, 'fedex')  => 'c-fedex',
        str_contains($lower, 'dhl')    => 'c-dhl',
        str_contains($lower, 'ups')    => 'c-ups',
        str_contains($lower, 'tnt')    => 'c-tnt',
        str_contains($lower, 'aramex') => 'c-aramex',
        default                        => 'c-regular',
    };
    return '<span class="carrier ' . $cls . '">' . htmlspecialchars($carrier) . '</span>';
}

// Build timeline data
$step = status_to_step($delivery['status']);
$timeline = [];
if ($step >= 0) $timeline[] = ['label'=>'Order Placed',      'date'=>$delivery['created_at'] ?? '',  'note'=>'Shipment information received',       'done'=>true];
if ($step >= 1) $timeline[] = ['label'=>'Approved',          'date'=>$delivery['approved_at'] ?? '',  'note'=>'Order approved',                      'done'=>true];
if ($step >= 2) $timeline[] = ['label'=>'Preparing to Ship', 'date'=>'',                       'note'=>'Preparing shipment',                  'done'=>$step>=2];
if ($step >= 3) $timeline[] = ['label'=>'In Transit',        'date'=>$delivery['shipped_at'] ?? '',   'note'=>'Package picked up by carrier',        'done'=>true];
if ($step >= 4) $timeline[] = ['label'=>'Delivered',         'date'=>$delivery['delivery_date'] ?? '', 'note'=>'Package delivered successfully',     'done'=>true];
// Add pending future steps
if ($step < 1) $timeline[] = ['label'=>'Approved',          'date'=>'Pending','note'=>'','done'=>false];
if ($step < 3) $timeline[] = ['label'=>'In Transit',        'date'=>'Pending','note'=>'','done'=>false];
if ($step < 4) $timeline[] = ['label'=>'Delivered',         'date'=>'Pending','note'=>'','done'=>false];

// Build steps array
$steps_arr = [];
for ($i = 0; $i <= 4; $i++) $steps_arr[] = $i <= $step;

$pill = status_pill($delivery['status']);
$warning = ($delivery['status'] === 'in_transit' && !empty($delivery['delay'])) ? 'High volume or carrier delays may affect delivery time.' : '';
$has_delay = !empty($delivery['delay']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Delivery Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* ── Sidebar ── */
        .sidebar {
            background: #0d1578;
            min-height: 100vh;
            color: white;
            width: 280px;
            position: fixed;
            z-index: 100;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        /* ── Main content ── */
        .main-content { padding: 20px; }

        /* ── Top bar card ── */
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        /* ── Full details page layout ── */
        .details-page {
            display: flex;
            gap: 0;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            min-height: calc(100vh - 140px);
        }

        .details-left {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            border-right: 1px solid #f3f4f6;
        }

        .details-right {
            width: 800px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            background: #e8eef5;
            position: relative;
            overflow: hidden;
        }

        /* Header */
        .dp-header {
            padding: 18px 0 14px;
            border-bottom: 1px solid #f3f4f6;
            margin-bottom: 20px;
        }
        .dp-header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .dp-shp-id {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0d1578;
            font-family: 'Courier New', monospace;
            letter-spacing: -0.01em;
        }
        .dp-badges { display: flex; gap: 8px; align-items: center; margin-top: 8px; }
        .dp-badge {
            font-size: 12px; font-weight: 600;
            padding: 4px 12px; border-radius: 20px;
        }
        .dp-badge-progress { background: #dbeafe; color: #1d4ed8; }
        .dp-badge-delivered { background: #d1fae5; color: #065f46; }
        .dp-badge-pending   { background: #fff8e1; color: #b45309; }
        .dp-badge-cancelled { background: #fef2f2; color: #b91c1c; }
        .dp-badge-delay     { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
        .dp-meta { font-size: 13px; color: #9ca3af; margin-top: 6px; }
        .dp-meta a { color: #2563eb; text-decoration: none; }
        .dp-meta a:hover { text-decoration: underline; }

        /* Actions */
        .dp-actions {
            display: flex; gap: 10px; align-items: center;
            margin-top: 16px;
        }
        .dp-btn-cancel {
            display: flex; align-items: center; gap: 6px;
            background: none; border: 1px solid #e5e7eb;
            padding: 8px 16px; border-radius: 8px;
            font-size: 13px; font-weight: 500; color: #374151;
            cursor: pointer; font-family: inherit;
            transition: all 0.12s;
        }
        .dp-btn-cancel:hover { border-color: #9ca3af; background: #f9fafb; }
        .dp-btn-notify {
            background: #e8521a; color: #fff;
            border: none; padding: 8px 18px; border-radius: 8px;
            font-size: 13px; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: background 0.12s;
        }
        .dp-btn-notify:hover { background: #c94416; }
        .dp-btn-back {
            display: flex; align-items: center; gap: 6px;
            background: #0d1578; color: #fff;
            border: none; padding: 8px 18px; border-radius: 8px;
            font-size: 13px; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: background 0.12s;
            text-decoration: none;
        }
        .dp-btn-back:hover { background: #0b1260; }

        /* Address block */
        .dp-address {
            background: #f9fafb;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 18px;
        }
        .dp-addr-row {
            display: flex; align-items: flex-start;
            gap: 10px; font-size: 13px; color: #374151;
            padding: 4px 0;
        }
        .dp-addr-row + .dp-addr-row {
            border-top: 1px dashed #e5e7eb;
            padding-top: 10px; margin-top: 6px;
        }
        .dp-addr-dot {
            width: 8px; height: 8px; border-radius: 50%;
            margin-top: 5px; flex-shrink: 0;
        }
        .dot-from { background: #0d1578; }
        .dot-to   { background: #e8521a; }
        .dp-carrier-badge { margin-left: auto; }

        /* Step progress */
        .dp-steps {
            display: flex; align-items: center;
            margin: 18px 0 22px;
        }
        .dp-step-node {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 11px;
            transition: all 0.2s;
        }
        .dp-step-node.done    { background: #0d1578; color: #fff; border: 2px solid #0d1578; }
        .dp-step-node.pending { background: #fff; color: #d1d5db; border: 2px solid #e5e7eb; }
        .dp-step-line { height: 2px; flex: 1; min-width: 16px; }
        .dp-step-line.done    { background: #0d1578; }
        .dp-step-line.pending { background: #e5e7eb; }

        /* Time grid */
        .dp-time-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px; margin-bottom: 18px;
        }
        .dp-time-cell label {
            font-size: 11px; color: #9ca3af;
            text-transform: uppercase; letter-spacing: 0.06em;
            display: block; margin-bottom: 3px;
        }
        .dp-time-cell span {
            font-size: 13px; font-weight: 600; color: #111827;
            font-family: 'Courier New', monospace;
        }

        /* Warning box */
        .dp-warning {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px; color: #9a3412;
            display: flex; gap: 10px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        /* Shipment timeline */
        .dp-section-title {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.08em;
            color: #9ca3af; margin-bottom: 14px;
        }
        .dp-timeline { list-style: none; padding: 0; margin: 0; }
        .dp-tl-item {
            display: flex; gap: 12px;
            position: relative; padding-bottom: 20px;
        }
        .dp-tl-item:last-child { padding-bottom: 0; }
        .dp-tl-left {
            display: flex; flex-direction: column;
            align-items: center;
        }
        .dp-tl-dot {
            width: 10px; height: 10px; border-radius: 50%;
            margin-top: 5px; flex-shrink: 0;
        }
        .dp-tl-dot.done    { background: #0d1578; }
        .dp-tl-dot.pending { background: #e5e7eb; border: 2px solid #d1d5db; }
        .dp-tl-line {
            width: 2px; flex: 1; margin-top: 4px; min-height: 18px;
        }
        .dp-tl-line.done    { background: #0d1578; }
        .dp-tl-line.pending { background: #e5e7eb; }
        .dp-tl-item:last-child .dp-tl-line { display: none; }
        .dp-tl-right { flex: 1; }
        .dp-tl-label {
            font-size: 14px; font-weight: 600; color: #111827;
        }
        .dp-tl-label.pending { color: #9ca3af; }
        .dp-tl-date {
            font-size: 12px; color: #9ca3af;
            font-family: 'Courier New', monospace; margin-top: 2px;
        }
        .dp-tl-note { font-size: 12px; color: #6b7280; margin-top: 3px; }

        /* Carrier Badges */
        .carrier {
            display: inline-flex; align-items: center;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px; font-weight: 700;
            gap: 6px;
        }
        .carrier-logo {
            height: 14px;
            width: auto;
        }
        .c-fedex   { background: #4d148c; color: #ff6600; }
        .c-dhl     { background: #ffcc00; color: #d40511; }
        .c-ups     { background: #351c15; color: #ffb500; }
        .c-tnt     { background: #ff6000; color: #fff; }
        .c-aramex  { background: #e32e26; color: #fff; }
        .c-regular { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }

        /* Map */
        .dp-map-leaflet {
            width: 100%;
            height: 100%;
        }
        .dp-map-controls {
            position: absolute; top: 16px; right: 16px;
            display: flex; flex-direction: column; gap: 8px;
            z-index: 1000;
        }
        .dp-map-btn {
            width: 36px; height: 36px;
            background: #fff; border: 1px solid #d1d5db;
            border-radius: 8px; display: flex; align-items: center;
            justify-content: center; cursor: pointer;
            font-size: 16px; color: #374151;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.2s;
        }
        .dp-map-btn:hover { background: #f9fafb; transform: scale(1.05); }
        .dp-map-toggle {
            position: absolute; bottom: 16px; left: 50%;
            transform: translateX(-50%);
            display: flex; background: #fff;
            border-radius: 8px; overflow: hidden;
            border: 1px solid #d1d5db;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            white-space: nowrap;
            z-index: 1000;
        }
        .dp-map-toggle-btn {
            font-size: 12px; font-weight: 600;
            padding: 8px 14px; background: none; border: none;
            cursor: pointer; color: #6b7280; font-family: inherit;
            transition: all 0.2s;
        }
        .dp-map-toggle-btn:hover { background: #f3f4f6; }
        .dp-map-toggle-btn.active { background: #0d1578; color: #fff; }

        @media (max-width: 992px) {
            .details-page { flex-direction: column; }
            .details-right { width: 100%; height: 300px; order: -1; }
            .details-left { border-right: none; }
            .dp-time-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { padding: 10px; }
            .details-left { padding: 16px; }
            .dp-time-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">

    <!-- SIDEBAR -->
    <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
      <div class="position-sticky pt-3">
        <div class="text-center mb-4">
          <img src="logo.png" alt="McPIL Logo" class="sidebar-logo"
               style="width:80px;height:80px;border-radius:50%;">
          <h4 class="mt-2">McPIL</h4>
          <small>Pharmaceutical Laboratory</small>
        </div>

        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
              <i class="fas fa-tachometer-alt"></i>
              <?php echo is_employee() ? 'Home' : 'Dashboard'; ?>
            </a>
          </li>

          <?php if (is_employee() || is_store()): ?>
          <li class="nav-item">
            <a class="nav-link" href="inventory.php">
              <i class="fas fa-boxes"></i>
              <?php echo is_employee() ? 'Inventory' : 'Inventory Management'; ?>
            </a>
          </li>
          <?php endif; ?>

          <?php if (is_admin()): ?>
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
            <a class="nav-link" href="employee_profile.php">
              <i class="fas fa-users"></i> Employee Profile
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="attendance.php">
              <i class="fas fa-clock"></i> Attendance
            </a>
          </li>
          <?php endif; ?>

          <li class="nav-item">
            <a class="nav-link active" href="delivery_tracking.php">
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
            </a>
          </li>

          <?php if (is_employee()): ?>
          <li class="nav-item">
            <a class="nav-link" href="attendance_camera.php">
              <i class="fas fa-clock"></i> Attendance
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="attendance_history.php">
              <i class="fas fa-history"></i> Attendance History
            </a>
          </li>
          <?php endif; ?>

          <?php if (is_store()): ?>
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
          <?php endif; ?>

          <li class="nav-item mt-4">
            <a class="nav-link text-danger" href="logout.php">
              <i class="fas fa-sign-out-alt"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </nav>
    <!-- END SIDEBAR -->

    <!-- MAIN CONTENT -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">

      <!-- Header card -->
      <div class="content-card">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h2 class="mb-0">Shipment Details</h2>
            <p class="text-muted mb-0" style="font-size:13px">Full tracking information and status</p>
          </div>
          <div class="d-flex align-items-center gap-3">
            <div class="user-info">
              <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
              </div>
              <div>
                <div class="fw-bold" style="font-size:14px"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <small class="text-muted"><?php echo ucfirst($_SESSION['user_role']); ?></small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Details page -->
      <?php if (!$delivery): ?>
        <div class="content-card">
          <div class="alert alert-danger mb-0">Delivery record not found.</div>
        </div>
      <?php else: ?>
      <div class="details-page">

        <!-- Left column -->
        <div class="details-left">

          <!-- Header -->
          <div class="dp-header">
            <div class="dp-header-top">
              <span class="dp-shp-id"><?php echo htmlspecialchars($delivery['delivery_number']); ?></span>
            </div>
            <div class="dp-badges">
              <span class="dp-badge <?php echo $pill['cls'] === 'pill-pending' ? 'dp-badge-pending' : ($pill['cls'] === 'pill-transit' ? 'dp-badge-progress' : ($pill['cls'] === 'pill-delivered' ? 'dp-badge-delivered' : 'dp-badge-pending')); ?>">
                <?php echo $pill['label']; ?>
              </span>
              <?php if ($has_delay): ?>
                <span class="dp-badge dp-badge-delay"><i class="fas fa-triangle-exclamation" style="font-size:10px"></i> Delay</span>
              <?php endif; ?>
            </div>
            <div class="dp-meta">
              Delivery Date: <strong><?php echo htmlspecialchars(format_date($delivery['delivery_date'])); ?></strong>
              &nbsp;&nbsp;·&nbsp;&nbsp;
              PO: <a href="#"><?php echo htmlspecialchars($delivery['po_number']); ?></a>
            </div>
            <div class="dp-actions">
              <button class="dp-btn-cancel">
                <i class="fas fa-times-circle"></i> Cancel Order
              </button>
              <button class="dp-btn-notify">
                <i class="fas fa-bell"></i> Notify Customer
              </button>
              <a href="delivery_tracking.php" class="dp-btn-back">
                <i class="fas fa-arrow-left"></i> Back to List
              </a>
            </div>
          </div>

          <!-- Addresses -->
          <div class="dp-address">
            <?php if (!empty($delivery['from_address'])): ?>
            <div class="dp-addr-row">
              <div class="dp-addr-dot dot-from"></div>
              <span><?php echo htmlspecialchars($delivery['from_address']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($delivery['to_address'])): ?>
            <div class="dp-addr-row">
              <div class="dp-addr-dot dot-to"></div>
              <span><?php echo htmlspecialchars($delivery['to_address']); ?></span>
              <span class="dp-carrier-badge"><?php echo carrier_badge($delivery['carrier']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (empty($delivery['from_address']) && empty($delivery['to_address'])): ?>
              <div style="font-size:13px;color:#9ca3af">No address information available</div>
            <?php endif; ?>
          </div>

          <!-- Step progress -->
          <div class="dp-steps">
            <?php
            $icons = ['fa-clipboard-list','fa-check','fa-box','fa-truck','fa-map-marker-alt'];
            for ($i = 0; $i <= 4; $i++):
                $done = $steps_arr[$i];
            ?>
            <div class="dp-step-node <?php echo $done ? 'done' : 'pending'; ?>">
              <i class="fas <?php echo $icons[$i]; ?>"></i>
            </div>
            <?php if ($i < 4): ?>
            <div class="dp-step-line <?php echo $done ? 'done' : 'pending'; ?>"></div>
            <?php endif; ?>
            <?php endfor; ?>
          </div>

          <!-- Time grid -->
          <div class="dp-time-grid">
            <div class="dp-time-cell"><label>Total Time</label><span><?php echo htmlspecialchars($delivery['total_time'] ?? '—'); ?></span></div>
            <div class="dp-time-cell"><label>Departure</label><span><?php echo htmlspecialchars($delivery['departure_time'] ?? '—'); ?></span></div>
            <div class="dp-time-cell"><label>Expected</label><span><?php echo htmlspecialchars(format_date($delivery['delivery_date'])); ?></span></div>
          </div>

          <!-- Warning -->
          <?php if ($warning): ?>
          <div class="dp-warning">
            <i class="fas fa-triangle-exclamation" style="flex-shrink:0;margin-top:2px"></i>
            <span><?php echo htmlspecialchars($warning); ?></span>
          </div>
          <?php endif; ?>

          <!-- Timeline -->
          <div class="dp-section-title">Shipment Status</div>
          <ul class="dp-timeline">
            <?php foreach ($timeline as $index => $item): $isLast = $index === count($timeline) - 1; ?>
            <li class="dp-tl-item">
              <div class="dp-tl-left">
                <div class="dp-tl-dot <?php echo $item['done'] ? 'done' : 'pending'; ?>"></div>
                <?php if (!$isLast): ?>
                <div class="dp-tl-line <?php echo $item['done'] ? 'done' : 'pending'; ?>"></div>
                <?php endif; ?>
              </div>
              <div class="dp-tl-right">
                <div class="dp-tl-label <?php echo $item['done'] ? '' : 'pending'; ?>"><?php echo htmlspecialchars($item['label']); ?></div>
                <div class="dp-tl-date"><?php echo htmlspecialchars($item['date']); ?></div>
                <?php if ($item['note']): ?>
                <div class="dp-tl-note"><?php echo htmlspecialchars($item['note']); ?></div>
                <?php endif; ?>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>

        </div>

        <!-- Right map column -->
        <div class="details-right">
          <div id="map" class="dp-map-leaflet"></div>
          <div class="dp-map-controls">
            <button class="dp-map-btn" id="zoomIn"><i class="fas fa-plus"></i></button>
            <button class="dp-map-btn" id="zoomOut"><i class="fas fa-minus"></i></button>
          </div>
          <div class="dp-map-toggle">
            <button class="dp-map-toggle-btn" id="satelliteView">Satellite</button>
            <button class="dp-map-toggle-btn active" id="mapView">Map View</button>
          </div>
        </div>

      </div>
      <?php endif; ?>

    </main>
    <!-- END MAIN -->

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Initialize the map centered on Davao City
const map = L.map('map', {
    center: [7.0731, 125.6128],
    zoom: 11,
    zoomControl: false
});

// Add tile layer (OpenStreetMap)
const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Add satellite layer (ESRI)
const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: '© ESRI'
});

// Create custom icons
const startIcon = L.divIcon({
    className: 'custom-marker',
    html: '<div style="background: #0d1578; border: 3px solid #fff; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"><div style="background: #fff; border-radius: 50%; width: 16px; height: 16px;"></div></div>',
    iconSize: [32, 32],
    iconAnchor: [16, 16]
});

const endIcon = L.divIcon({
    className: 'custom-marker',
    html: '<div style="background: #e8521a; border: 3px solid #fff; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"><div style="background: #fff; border-radius: 50%; width: 16px; height: 16px;"></div></div>',
    iconSize: [32, 32],
    iconAnchor: [16, 16]
});

// Add sample route (Philippines coordinates)
const routeCoordinates = [
    [8.0, 126.0],  // Start
    [7.5, 125.5],
    [7.0, 125.0],
    [6.5, 124.5],
    [6.0, 124.0],
    [5.5, 123.5],
    [5.0, 123.0],
    [4.5, 122.5],
    [4.0, 122.0]   // End
];

// Add route line
const routeLine = L.polyline(routeCoordinates, {
    color: '#e8521a',
    weight: 6,
    dashArray: '15, 12',
    opacity: 0.9
}).addTo(map);

// Add markers
L.marker(routeCoordinates[0], {icon: startIcon}).addTo(map).bindPopup('Origin');
L.marker(routeCoordinates[routeCoordinates.length - 1], {icon: endIcon}).addTo(map).bindPopup('Destination');

// Fit map to show entire route
map.fitBounds(routeLine.getBounds(), {padding: [50, 50]});

// Zoom controls
document.getElementById('zoomIn').addEventListener('click', () => map.zoomIn());
document.getElementById('zoomOut').addEventListener('click', () => map.zoomOut());

// Map view toggle
let currentLayer = osmLayer;
document.getElementById('mapView').addEventListener('click', function() {
    map.removeLayer(currentLayer);
    osmLayer.addTo(map);
    currentLayer = osmLayer;
    this.classList.add('active');
    document.getElementById('satelliteView').classList.remove('active');
});

document.getElementById('satelliteView').addEventListener('click', function() {
    map.removeLayer(currentLayer);
    satelliteLayer.addTo(map);
    currentLayer = satelliteLayer;
    this.classList.add('active');
    document.getElementById('mapView').classList.remove('active');
});
</script>
</body>
</html>
