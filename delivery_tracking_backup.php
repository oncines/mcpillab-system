<?php
require_once 'config.php';

// Handle status update
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

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$filter_date   = $_GET['filter_date'] ?? '';

// Build query for deliveries
$all_deliveries = get_deliveries(null, 100);

// Apply filters
$filtered_deliveries = [];
foreach ($all_deliveries as $delivery) {
    if ($status_filter !== 'all' && $delivery['status'] !== $status_filter) continue;
    if ($filter_date && $delivery['delivery_date'] !== $filter_date) continue;
    $filtered_deliveries[] = $delivery;
}

// Helper: convert DB status to timeline step (0–4)
function status_to_step($status) {
    return match($status) {
        'pending'    => 0,
        'approved'   => 1,
        'in_transit' => 3,
        'delivered'  => 4,
        default      => 0,
    };
}

// Helper: status pill config
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

// Helper: carrier badge
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

// Helper: render timeline HTML
function render_timeline($step, $total = 4) {
    $icons = ['fa-clipboard-list','fa-check','fa-box','fa-truck','fa-map-marker-alt'];
    $html  = '<div class="timeline">';
    for ($i = 0; $i <= $total; $i++) {
        $cls  = $i < $step ? 'done' : ($i === $step ? 'active' : 'inactive');
        $html .= '<div class="t-step ' . $cls . '"><i class="fas ' . $icons[$i] . '"></i></div>';
        if ($i < $total) {
            $html .= '<div class="t-line' . ($i < $step ? ' done' : '') . '"></div>';
        }
    }
    $html .= '</div>';
    return $html;
}

// Build JS-safe delivery data for the panel
$js_deliveries = [];
foreach ($filtered_deliveries as $d) {
    $step = status_to_step($d['status']);
    $steps_arr = [];
    for ($i = 0; $i <= 4; $i++) $steps_arr[] = $i <= $step;

    $tl = [];
    if ($step >= 0) $tl[] = ['label'=>'Order Placed',      'date'=>$d['created_at'] ?? '',  'note'=>'Shipment information received',       'done'=>true];
    if ($step >= 1) $tl[] = ['label'=>'Approved',          'date'=>$d['approved_at'] ?? '',  'note'=>'Order approved',                      'done'=>true];
    if ($step >= 2) $tl[] = ['label'=>'Preparing to Ship', 'date'=>'',                       'note'=>'Preparing shipment',                  'done'=>$step>=2];
    if ($step >= 3) $tl[] = ['label'=>'In Transit',        'date'=>$d['shipped_at'] ?? '',   'note'=>'Package picked up by carrier',        'done'=>true];
    if ($step >= 4) $tl[] = ['label'=>'Delivered',         'date'=>$d['delivery_date'] ?? '', 'note'=>'Package delivered successfully',     'done'=>true];
    if ($step < 1) $tl[] = ['label'=>'Approved',          'date'=>'Pending','note'=>'','done'=>false];
    if ($step < 3) $tl[] = ['label'=>'In Transit',        'date'=>'Pending','note'=>'','done'=>false];
    if ($step < 4) $tl[] = ['label'=>'Delivered',         'date'=>'Pending','note'=>'','done'=>false];

    $js_deliveries[] = [
        'id'             => $d['id'],
        'del_number'     => $d['delivery_number'] ?? '',
        'po_number'      => $d['po_number'] ?? '',
        'status'         => $d['status'],
        'status_label'   => status_pill($d['status'])['label'],
        'status_cls'     => status_pill($d['status'])['cls'],
        'carrier'        => $d['carrier'] ?? '',
        'supplier'       => $d['supplier_name'] ?? '',
        'supplier_email' => $d['supplier_email'] ?? '',
        'from_address'   => $d['from_address'] ?? '',
        'to_address'     => $d['to_address'] ?? '',
        'delivery_date'  => format_date($d['delivery_date']),
        'created_at'     => $d['created_at'] ?? '',
        'total_time'     => $d['total_time'] ?? '—',
        'departure_time' => $d['departure_time'] ?? '—',
        'steps'          => $steps_arr,
        'timeline'       => $tl,
        'warning'        => ($d['status'] === 'in_transit' && !empty($d['delay'])) ? 'High volume or carrier delays may affect delivery time.' : '',
        'delay'          => !empty($d['delay']),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Delivery Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="public/css/design-system.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 15px;
        }

        .main-content {
            padding: 0;
            margin-left: var(--sidebar-w);
            width: calc(100vw - var(--sidebar-w));
            max-width: calc(100vw - var(--sidebar-w));
            min-width: 0;
            overflow-x: hidden;
        }

        /* ── Top bar card ── */
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .content-card h2 {
            font-size: 25px;
            font-weight: 700;
            color: #1a1a2e;
        }
        .content-card p {
            font-size: 15px;
            color: #6b7280;
        }
        .user-info { display: flex; align-items: center; gap: 12px; }
        .user-avatar {
            width: 48px; height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold;
            font-size: 15px;
        }
        .user-info .fw-bold { font-size: 15px; color: #1a1a2e; }
        .user-info small    { font-size: 15px; color: #6b7280; }

        .btn-add-shipment {
            background: #e8521a;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background 0.15s;
        }
        .btn-add-shipment:hover { background: #c94416; color: #fff; }

        /* ── Tabs row ── */
        .tabs-row {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #e4e4e7;
            padding: 0 4px;
        }
        .tabs-left { display: flex; align-items: center; flex: 1; }
        .tabs-right { display: flex; align-items: center; gap: 8px; padding-bottom: 4px; }
        .tab-btn {
            padding: 10px 16px;
            font-size: 15px;
            color: #888;
            border: none;
            background: transparent;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: all 0.15s;
            text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
            white-space: nowrap;
        }
        .tab-btn:hover { color: #1a1a2e; }
        .tab-btn.active { color: #374151; border-bottom-color: #d1d5db; font-weight: 600; }
        .btn-collapse {
            background: #fff; border: 1px solid #d1d5db;
            border-radius: 7px; padding: 5px 9px;
            cursor: pointer; color: #6b7280; font-size: 15px;
            display: inline-flex; align-items: center;
        }

        /* ── Filter bar ── */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 0 4px;
            flex-wrap: nowrap;
            overflow-x: auto;
        }
        .search-wrap {
            display: flex; align-items: center; gap: 6px;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 6px 12px;
            min-width: 150px;
        }
        .search-wrap input {
            border: none; background: transparent;
            font-size: 15px; outline: none; width: 120px; color: #374151;
        }
        .date-range-pill {
            display: inline-flex; align-items: center; gap: 5px;
            background: #1e3a5f;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 15px; font-weight: 500;
            cursor: pointer; white-space: nowrap;
        }
        .filter-dropdown {
            display: inline-flex; align-items: center; gap: 5px;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 15px; color: #374151;
            cursor: pointer; white-space: nowrap;
            text-decoration: none;
            transition: background 0.12s;
        }
        .filter-dropdown:hover { background: #f3f4f6; color: #111; }
        .filter-dropdown.active { background: #fff; border-color: #d1d5db; color: #374151; font-weight: 400; }

        /* ── Status Dropdown Items ── */
        .status-drop-item {
            display: flex; align-items: center; gap: 10px;
            padding: 7px 10px; border-radius: 8px;
            text-decoration: none; color: #374151;
            font-size: 15px; transition: background 0.1s;
        }
        .status-drop-item:hover { background: #f9fafb; color: #111827; }
        .status-drop-item--active { background: #eff6ff; color: #111827; }
        .status-drop-item--active:hover { background: #dbeafe; }

        /* ── Table ── */
        .tbl-wrap { overflow-x: auto; }
        .tbl { width: 100%; border-collapse: collapse; min-width: 820px; }
        .tbl th {
            background: #f9fafb;
            padding: 10px 14px;
            text-align: left;
            font-size: 15px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }
        .tbl td {
            padding: 12px 14px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            color: #1a1a2e;
            font-size: 15px;
        }
        .tbl tbody tr:last-child td { border-bottom: none; }
        .tbl tbody tr:hover td { background: #fafafa; cursor: pointer; }

        .date-cell {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            color: #1a1a2e;
            background: transparent;
        }

        .del-id  { font-size: 15px; font-weight: 600; }
        .del-po  { font-size: 15px; color: #9ca3af; margin-top: 2px; }

        /* ── Timeline ── */
        .timeline { display: flex; align-items: center; }
        .t-step {
            width: 24px; height: 24px;
            border-radius: 50%;
            border: 2px solid #d1d5db;
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 15px;
            color: #d1d5db;
            position: relative; z-index: 1;
        }
        .t-step.done     { background: #0d1578; border-color: #0d1578; color: #fff; }
        .t-step.active   { background: #fff;    border-color: #0d1578; color: #0d1578; }
        .t-step.inactive { color: #d1d5db; }
        .t-line          { height: 2px; flex: 1; background: #e5e7eb; min-width: 12px; }
        .t-line.done     { background: #0d1578; }

        /* ── Status Pills ── */
        .pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 15px; font-weight: 500;
        }
        .pill-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
        .pill-pending   { background: #fff8e1; color: #b45309; }
        .pill-pending   .pill-dot { background: #f59e0b; }
        .pill-approved  { background: #eff6ff; color: #1d4ed8; }
        .pill-approved  .pill-dot { background: #3b82f6; }
        .pill-transit   { background: #ecfdf5; color: #065f46; }
        .pill-transit   .pill-dot { background: #10b981; }
        .pill-delivered { background: #f0fdf4; color: #166534; }
        .pill-delivered .pill-dot { background: #16a34a; }
        .pill-cancelled { background: #fef2f2; color: #b91c1c; }
        .pill-cancelled .pill-dot { background: #ef4444; }
        .pill-default   { background: #f3f4f6; color: #374151; }
        .pill-default   .pill-dot { background: #9ca3af; }

        /* ── Carrier Badges ── */
        .carrier {
            display: inline-flex; align-items: center;
            padding: 3px 9px;
            border-radius: 5px;
            font-size: 15px; font-weight: 700;
        }
        .c-fedex   { background: #4d148c; color: #ff6600; }
        .c-dhl     { background: #ffcc00; color: #d40511; }
        .c-ups     { background: #351c15; color: #ffb500; }
        .c-tnt     { background: #ff6000; color: #fff; }
        .c-aramex  { background: #e32e26; color: #fff; }
        .c-regular { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }

        /* ── Action Buttons ── */
        .btn-act {
            background: transparent;
            border: 1px solid #e5e7eb;
            border-radius: 7px;
            padding: 5px 9px;
            font-size: 15px;
            cursor: pointer;
            color: #6b7280;
            transition: background 0.12s;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .btn-act:hover { background: #f3f4f6; color: #374151; }
        .btn-act.blue  { border-color: #c7d2fe; color: #4338ca; }
        .btn-act.blue:hover { background: #eef2ff; }

        /* ── Modal ── */
        .modal-content  { border-radius: 14px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.12); }
        .modal-header   { border-bottom: 1px solid #f0f0f0; padding: 18px 24px; }
        .modal-footer   { border-top: 1px solid #f0f0f0; padding: 14px 24px; }
        .modal-body     { padding: 20px 24px; }
        .form-select    { border-radius: 8px; border: 1px solid #e5e7eb; font-size: 15px; }
        .form-select:focus { border-color: #0d1578; box-shadow: 0 0 0 3px rgba(13,21,120,0.08); }
        .btn-modal-ok {
            background: #e8521a; border: none; border-radius: 8px;
            padding: 8px 20px; font-size: 15px; font-weight: 600; color: #fff; cursor: pointer;
        }
        .btn-modal-ok:hover { background: #c94416; }

        /* ── Empty state ── */
        .empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
        .empty-state i { font-size: 15px; margin-bottom: 14px; display: block; color: #d1d5db; }

        /* ══════════════════════════════════════════
           SLIDE-OVER PANEL STYLES
        ══════════════════════════════════════════ */
        .panel-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17,24,39,0.30);
            z-index: 400;
            backdrop-filter: blur(1px);
        }
        .panel-overlay.open { display: block; }

        .detail-panel {
            position: fixed;
            top: 0;
            right: -960px;
            width: 960px;
            max-width: 960px;
            height: 100vh;
            background: #fff;
            z-index: 500;
            box-shadow: -6px 0 36px rgba(0,0,0,0.12);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: right 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .detail-panel.open { right: 0; }

        /* Panel close button */
        .panel-close-btn {
            position: absolute;
            top: 18px;
            left: -42px;
            width: 34px; height: 34px;
            background: #fff;
            border: none;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            color: #6b7280;
            font-size: 15px;
            transition: all 0.15s;
            z-index: 10;
        }
        .panel-close-btn:hover { background: #f3f4f6; color: #111; }

        /* Panel header */
        .dp-header {
            padding: 18px 22px 14px;
            border-bottom: 1px solid #f3f4f6;
            flex-shrink: 0;
        }
        .dp-header-top {
            display: flex; align-items: center;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        .dp-shp-id {
            font-size: 28px;
            font-weight: 700;
            color: #0d1578;
            font-family: 'Courier New', monospace;
            letter-spacing: -0.01em;
        }
        .dp-nav { display: flex; gap: 4px; }
        .dp-nav button {
            width: 28px; height: 28px;
            border: 1px solid #e5e7eb; border-radius: 6px;
            background: #fff; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: #6b7280; font-size: 15px;
            transition: all 0.12s;
        }
        .dp-nav button:hover { background: #f3f4f6; border-color: #9ca3af; }
        .dp-badges { display: none; gap: 6px; align-items: center; margin-top: 4px; }
        .dp-badge {
            font-size: 15px; font-weight: 600;
            padding: 3px 9px; border-radius: 20px;
        }
        .dp-badge-progress { background: #dbeafe; color: #1d4ed8; }
        .dp-badge-delivered { background: #d1fae5; color: #065f46; }
        .dp-badge-pending   { background: #fff8e1; color: #b45309; }
        .dp-badge-cancelled { background: #fef2f2; color: #b91c1c; }
        .dp-badge-delay     { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
        .dp-meta { font-size: 15px; color: #9ca3af; margin-top: 3px; }
        .dp-meta a { color: #2563eb; text-decoration: none; }
        .dp-meta a:hover { text-decoration: underline; }

        /* Panel actions */
        .dp-actions {
            display: flex; gap: 8px; align-items: center;
            margin-top: 12px;
        }
        .dp-btn-cancel {
            display: flex; align-items: center; gap: 5px;
            background: none; border: 1px solid #e5e7eb;
            padding: 6px 13px; border-radius: 7px;
            font-size: 15px; font-weight: 500; color: #374151;
            cursor: pointer; font-family: inherit;
            transition: all 0.12s;
        }
        .dp-btn-cancel:hover { border-color: #9ca3af; background: #f9fafb; }
        .dp-btn-notify {
            background: #e8521a; color: #fff;
            border: none; padding: 6px 16px; border-radius: 7px;
            font-size: 15px; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: background 0.12s;
        }
        .dp-btn-notify:hover { background: #c94416; }
        .dp-btn-back {
            display: flex; align-items: center; gap: 5px;
            background: #0d1578; border: none;
            padding: 6px 13px; border-radius: 7px;
            font-size: 15px; font-weight: 500; color: #fff;
            cursor: pointer; font-family: inherit;
            transition: all 0.12s; margin-left: auto;
        }
        .dp-btn-back:hover { background: #0b1260; }

        /* Panel body */
        .dp-body {
            flex: 1;
            overflow: hidden;
            display: flex;
        }

        /* Left column */
        .dp-left {
            flex: 1;
            padding: 18px 22px;
            overflow-y: auto;
            border-right: 1px solid #f3f4f6;
            min-width: 0;
        }

        /* Address block */
        .dp-address {
            background: #f9fafb;
            border-radius: 8px;
            padding: 11px 13px;
            margin-bottom: 14px;
        }
        .dp-addr-row {
            display: flex; align-items: flex-start;
            gap: 8px; font-size: 15px; color: #374151;
            padding: 3px 0;
        }
        .dp-addr-row + .dp-addr-row {
            border-top: 1px dashed #e5e7eb;
            padding-top: 8px; margin-top: 4px;
        }
        .dp-addr-dot {
            width: 7px; height: 7px; border-radius: 50%;
            margin-top: 4px; flex-shrink: 0;
        }
        .dot-from { background: #0d1578; }
        .dot-to   { background: #e8521a; }
        .dp-carrier-badge { margin-left: auto; }

        /* Panel step track */
        .dp-steps {
            display: flex; align-items: center;
            margin: 14px 0 18px;
        }
        .dp-step-node {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 15px;
        }
        .dp-step-node.done    { background: #0d1578; color: #fff; border: 2px solid #0d1578; }
        .dp-step-node.pending { background: #fff; color: #d1d5db; border: 2px solid #e5e7eb; }
        .dp-step-line { height: 2px; flex: 1; min-width: 14px; }
        .dp-step-line.done    { background: #0d1578; }
        .dp-step-line.pending { background: #e5e7eb; }

        /* Time grid */
        .dp-time-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px; margin-bottom: 14px;
        }
        .dp-time-cell label {
            font-size: 15px; color: #9ca3af;
            text-transform: uppercase; letter-spacing: 0.06em;
            display: block; margin-bottom: 2px;
        }
        .dp-time-cell span {
            font-size: 15px; font-weight: 600; color: #111827;
            font-family: 'Courier New', monospace;
        }

        /* Warning box */
        .dp-warning {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 15px; color: #9a3412;
            display: flex; gap: 8px;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        /* Shipment timeline */
        .dp-section-title {
            font-size: 15px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.08em;
            color: #9ca3af; margin-bottom: 12px;
        }
        .dp-timeline { list-style: none; padding: 0; margin: 0; }
        .dp-tl-item {
            display: flex; gap: 10px;
            position: relative; padding-bottom: 16px;
        }
        .dp-tl-item:last-child { padding-bottom: 0; }
        .dp-tl-left {
            display: flex; flex-direction: column;
            align-items: center;
        }
        .dp-tl-dot {
            width: 9px; height: 9px; border-radius: 50%;
            margin-top: 4px; flex-shrink: 0;
        }
        .dp-tl-dot.done    { background: #0d1578; }
        .dp-tl-dot.pending { background: #e5e7eb; border: 2px solid #d1d5db; }
        .dp-tl-line {
            width: 2px; flex: 1; margin-top: 3px; min-height: 16px;
        }
        .dp-tl-line.done    { background: #0d1578; }
        .dp-tl-line.pending { background: #e5e7eb; }
        .dp-tl-item:last-child .dp-tl-line { display: none; }
        .dp-tl-right { flex: 1; }
        .dp-tl-label {
            font-size: 15px; font-weight: 600; color: #111827;
        }
        .dp-tl-label.pending { color: #9ca3af; }
        .dp-tl-date {
            font-size: 15px; color: #9ca3af;
            font-family: 'Courier New', monospace; margin-top: 1px;
        }
        .dp-tl-note { font-size: 15px; color: #6b7280; margin-top: 2px; }

        /* ══════════════════════════════════════════
           EMAIL COMPOSER PANEL (right column)
        ══════════════════════════════════════════ */
        .dp-right {
            width: 650px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            background: #fff;
            border-left: 1px solid #f3f4f6;
            transition: width 0.3s cubic-bezier(0.4,0,0.2,1), opacity 0.25s ease;
            overflow: hidden;
        }

        /* When email panel is hidden, show the map */
        .dp-right.map-mode {
            background: #e8eef5;
        }
        .dp-right.email-mode .dp-map-wrap { display: none; }
        .dp-right.map-mode   .dp-email-wrap { display: none; }

        /* Map wrapper */
        .dp-map-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        .dp-map-svg { width: 100%; height: 100%; }
        .dp-map-controls {
            position: absolute; bottom: 50px; right: 10px;
            display: flex; flex-direction: column; gap: 3px;
        }
        .dp-map-btn {
            width: 26px; height: 26px;
            background: #fff; border: 1px solid #d1d5db;
            border-radius: 5px; display: flex; align-items: center;
            justify-content: center; cursor: pointer;
            font-size: 15px; color: #374151;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .dp-map-toggle {
            position: absolute; bottom: 10px; left: 50%;
            transform: translateX(-50%);
            display: flex; background: #fff;
            border-radius: 6px; overflow: hidden;
            border: 1px solid #d1d5db;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            white-space: nowrap;
        }
        .dp-map-toggle-btn {
            font-size: 15px; font-weight: 600;
            padding: 4px 8px; background: none; border: none;
            cursor: pointer; color: #6b7280; font-family: inherit;
        }
        .dp-map-toggle-btn.active { background: #0d1578; color: #fff; }

        /* ── Email composer wrapper ── */
        .dp-email-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Email panel header */
        .ep-header {
            padding: 14px 18px 13px;
            border-bottom: 1px solid #f3f4f6;
            flex-shrink: 0;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }
        .ep-icon-wrap { display: flex; align-items: flex-start; gap: 10px; }
        .ep-icon {
            width: 38px; height: 38px;
            background: #fff8e1;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }
        .ep-title-block {}
        .ep-title {
            font-size: 15px; font-weight: 700;
            color: #111827;
            margin-bottom: 2px;
        }
        .ep-subtitle {
            font-size: 15px;
            color: #9ca3af;
        }
        .ep-close-btn {
            background: none; border: none; cursor: pointer;
            color: #9ca3af; font-size: 15px;
            padding: 2px 4px;
            border-radius: 5px;
            transition: all 0.12s;
            flex-shrink: 0;
        }
        .ep-close-btn:hover { background: #f3f4f6; color: #374151; }

        /* Email body */
        .ep-body {
            flex: 1;
            overflow-y: auto;
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* Field rows */
        .ep-field-row {
            display: flex;
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }
        .ep-field-label {
            font-size: 15px;
            font-weight: 500;
            color: #6b7280;
            padding: 8px 12px;
            min-width: 58px;
            border-right: 1px solid #e5e7eb;
            background: #f9fafb;
            flex-shrink: 0;
        }
        .ep-field-input {
            font-size: 15px;
            color: #111827;
            border: none;
            background: transparent;
            outline: none;
            flex: 1;
            padding: 8px 10px;
            font-family: 'Segoe UI', sans-serif;
        }
        .ep-field-cc {
            font-size: 15px;
            color: #2563eb;
            padding: 8px 10px;
            cursor: pointer;
            flex-shrink: 0;
        }
        .ep-char-count {
            font-size: 15px;
            color: #9ca3af;
            padding: 8px 10px;
            flex-shrink: 0;
        }

        /* Template row */
        .ep-template-row {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .ep-template-select {
            flex: 1;
            font-size: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 7px 10px;
            background: #fff;
            color: #374151;
            outline: none;
            font-family: 'Segoe UI', sans-serif;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            padding-right: 28px;
        }
        .ep-template-select:focus { border-color: #0d1578; }
        .ep-save-tpl-btn {
            font-size: 15px;
            padding: 7px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            color: #374151;
            cursor: pointer;
            font-family: 'Segoe UI', sans-serif;
            white-space: nowrap;
            transition: background 0.12s;
        }
        .ep-save-tpl-btn:hover { background: #f3f4f6; }

        /* Message textarea */
        .ep-message {
            flex: 1;
            font-size: 15px;
            color: #111827;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 12px;
            outline: none;
            resize: none;
            min-height: 150px;
            font-family: 'Segoe UI', sans-serif;
            background: #fff;
            line-height: 1.6;
            transition: border-color 0.12s;
        }
        .ep-message::placeholder { color: #9ca3af; }
        .ep-message:focus { border-color: #0d1578; }

        /* Toolbar */
        .ep-toolbar {
            display: flex;
            align-items: center;
            gap: 2px;
            flex-wrap: wrap;
            padding: 6px 0 2px;
        }
        .ep-tb-btn {
            background: none; border: none; cursor: pointer;
            color: #6b7280; font-size: 15px;
            padding: 5px 7px; border-radius: 5px;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.12s;
        }
        .ep-tb-btn:hover { background: #f3f4f6; color: #374151; }
        .ep-tb-divider {
            width: 1px; height: 16px;
            background: #e5e7eb; margin: 0 4px;
        }
        .ep-ask-ai {
            display: flex; align-items: center; gap: 5px;
            margin-left: auto;
            font-size: 15px; font-weight: 600;
            color: #7c3aed;
            border: 1px solid #c4b5fd;
            border-radius: 6px;
            padding: 4px 10px;
            background: none; cursor: pointer;
            font-family: 'Segoe UI', sans-serif;
            transition: background 0.12s;
        }
        .ep-ask-ai:hover { background: #f5f3ff; }
        .ep-preview-btn {
            font-size: 15px; color: #6b7280;
            background: none; border: none; cursor: pointer;
            text-decoration: underline;
            font-family: 'Segoe UI', sans-serif;
            padding: 4px 6px;
        }

        /* Email footer */
        .ep-footer {
            padding: 12px 18px;
            border-top: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .ep-footer-info {
            font-size: 15px;
            color: #9ca3af;
        }
        .ep-footer-info a { color: #2563eb; text-decoration: none; }
        .ep-footer-info a:hover { text-decoration: underline; }
        .ep-footer-btns { display: flex; gap: 8px; }
        .ep-btn-cancel {
            padding: 7px 18px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #f3f4f6;
            color: #374151;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Segoe UI', sans-serif;
            transition: background 0.12s;
        }
        .ep-btn-cancel:hover { background: #e5e7eb; }
        .ep-btn-confirm {
            padding: 7px 22px;
            border: none;
            border-radius: 8px;
            background: #e8521a;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Segoe UI', sans-serif;
            transition: background 0.12s;
        }
        .ep-btn-confirm:hover { background: #c94416; }

        @media (max-width: 640px) {
            .detail-panel { max-width: 100%; width: 100%; }
            .dp-right { display: none; }
            .dp-left { border-right: none; }
            .dp-time-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">

    <!-- Sidebar -->
    <nav id="appSidebar" class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-logo-ring">
                <img src="logo.png" alt="McPIL Logo">
            </div>
            <div class="sidebar-brand-name">McPIL</div>
            <div class="sidebar-brand-sub">Pharmaceutical Laboratory</div>
        </div>

        <div class="sidebar-nav">
            <div class="nav-section-label">Navigation</div>

            <a class="sidebar-link" href="dashboard.php">
                <span class="icon"><i class="fas fa-tachometer-alt"></i></span>
                <?php echo is_employee() ? 'Home' : 'Dashboard'; ?>
            </a>

            <?php if (is_employee() || is_store()): ?>
            <a class="sidebar-link" href="inventory.php">
                <span class="icon"><i class="fas fa-boxes"></i></span>
                <?php echo is_employee() ? 'Inventory' : 'Inventory Management'; ?>
            </a>
            <?php endif; ?>

            <?php if (is_admin()): ?>
            <a class="sidebar-link" href="purchase_order.php">
                <span class="icon"><i class="fas fa-shopping-cart"></i></span> Purchase Order
            </a>
            <a class="sidebar-link" href="purchase_invoice.php">
                <span class="icon"><i class="fas fa-file-invoice"></i></span> Purchase Invoice
            </a>
            <a class="sidebar-link" href="employee_profile.php">
                <span class="icon"><i class="fas fa-users"></i></span> Employee Profile
            </a>
            <a class="sidebar-link" href="attendance.php">
                <span class="icon"><i class="fas fa-clock"></i></span> Attendance
            </a>
            <?php endif; ?>

            <div class="nav-section-label">Shipments</div>

            <a class="sidebar-link active" href="delivery_tracking.php">
                <span class="icon"><i class="fas fa-truck"></i></span> Delivery Tracking
            </a>
            <a class="sidebar-link" href="delivery_history.php">
                <span class="icon"><i class="fas fa-history"></i></span> Delivery History
            </a>

            <div class="nav-section-label">Other</div>

            <a class="sidebar-link" href="reports.php">
                <span class="icon"><i class="fas fa-chart-bar"></i></span> Reports
            </a>
            <a class="sidebar-link logout" href="chat_interface.php">
                <span class="icon"><i class="fas fa-comments"></i></span> Messages
            </a>

            <?php if (is_employee()): ?>
            <a class="sidebar-link" href="attendance_camera.php">
                <span class="icon"><i class="fas fa-clock"></i></span> Attendance
            </a>
            <a class="sidebar-link" href="attendance_history.php">
                <span class="icon"><i class="fas fa-history"></i></span> Attendance History
            </a>
            <?php endif; ?>

            <?php if (is_store()): ?>
            <a class="sidebar-link" href="purchase_order.php">
                <span class="icon"><i class="fas fa-shopping-cart"></i></span> Purchase Order
            </a>
            <a class="sidebar-link" href="purchase_invoice.php">
                <span class="icon"><i class="fas fa-file-invoice"></i></span> Purchase Invoice
            </a>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <a class="sidebar-link logout" href="logout.php">
                <span class="icon"><i class="fas fa-sign-out-alt"></i></span> Logout
            </a>
        </div>
    </nav>
    <div class="mobile-sidebar-backdrop" id="mobileSidebarBackdrop"></div>

    <!-- Main Content -->
    <main class="main-content">

      <!-- Header card -->
      <div class="content-card">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h2 class="mb-0">Shipment</h2>
            <p class="text-muted mb-0">Track and manage deliveries in real-time</p>
          </div>
          <div class="d-flex align-items-center gap-3">
            <div class="user-info">
              <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
              </div>
              <div>
                <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <small class="text-muted"><?php echo ucfirst($_SESSION['user_role']); ?></small>
              </div>
            </div>
            <?php if (is_admin() || is_manager()): ?>
            <button class="btn-add-shipment" data-bs-toggle="modal" data-bs-target="#addDeliveryModal">
              <i class="fas fa-plus"></i> Add Shipment
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Alerts -->
      <?php if (isset($success_message)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
      <?php if (isset($error_message)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <!-- Deliveries card -->
      <div class="content-card">

        <!-- Tabs row -->
        <div class="tabs-row">
          <div class="tabs-left">
            <a href="delivery_tracking.php?status=all<?php echo $filter_date ? '&filter_date='.urlencode($filter_date) : ''; ?>"
               class="tab-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
              <i class="fas fa-table-list"></i> All Orders
            </a>
            <a href="delivery_tracking.php?status=pending<?php echo $filter_date ? '&filter_date='.urlencode($filter_date) : ''; ?>"
               class="tab-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
              <i class="fas fa-triangle-exclamation"></i> Pending
            </a>
            <a href="delivery_tracking.php?status=delivered<?php echo $filter_date ? '&filter_date='.urlencode($filter_date) : ''; ?>"
               class="tab-btn <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>">
              <i class="fas fa-circle-check"></i> Arrived
            </a>
            <a href="delivery_tracking.php?status=cancelled<?php echo $filter_date ? '&filter_date='.urlencode($filter_date) : ''; ?>"
               class="tab-btn <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
              <i class="fas fa-circle-xmark"></i> Cancelled
            </a>
          </div>
          <div class="tabs-right">
            <button class="btn-collapse">
              <i class="fas fa-chevron-up"></i>
            </button>
          </div>
        </div>

        <!-- Filter bar -->
        <form method="GET" action="delivery_tracking.php" id="filterForm">
          <input type="hidden" name="status" id="hiddenStatus" value="<?php echo htmlspecialchars($status_filter); ?>">
          <div class="filter-bar">

            <div class="search-wrap">
              <i class="fas fa-search" style="color:#9ca3af;font-size:11px"></i>
              <input type="text" name="search" id="searchInput" placeholder="Search"
                     value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>

            <label class="date-range-pill" for="filterDateInput" style="cursor:pointer">
              <i class="fas fa-calendar"></i>
              <span id="datePillLabel">
                <?php echo $filter_date
                    ? htmlspecialchars(date('d M Y', strtotime($filter_date)))
                    : date('d M') . ' – ' . date('d M Y'); ?>
              </span>
              <i class="fas fa-chevron-down" style="font-size:10px"></i>
              <input type="date" name="filter_date" id="filterDateInput"
                     value="<?php echo htmlspecialchars($filter_date); ?>"
                     style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none">
            </label>
            <?php if ($filter_date): ?>
            <a href="delivery_tracking.php?status=<?php echo htmlspecialchars($status_filter); ?>"
               style="font-size:11px;color:#6b7280;text-decoration:none;display:inline-flex;align-items:center;gap:3px;padding:2px 6px;border:1px solid #e5e7eb;border-radius:6px"
               title="Clear date filter">
              <i class="fas fa-times" style="font-size:10px"></i>
            </a>
            <?php endif; ?>

            <div style="position:relative;z-index:1000">
              <div class="filter-dropdown <?php echo $status_filter !== 'all' ? 'active' : ''; ?>"
                   id="statusDropBtn"
                   onclick="var d=document.getElementById('statusDrop');d.style.display=d.style.display==='block'?'none':'block';event.stopPropagation();"
                   style="cursor:pointer">
                <?php
                  $labels = ['all'=>'All Shipment Status','pending'=>'Draft','approved'=>'Approved',
                             'in_transit'=>'In Progress','delivered'=>'Arrived','cancelled'=>'Canceled'];
                  echo $labels[$status_filter] ?? 'All Shipment Status';
                ?>
                <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px"></i>
              </div>
              <div id="statusDrop"
                   style="display:none;position:absolute;top:calc(100% + 6px);left:0;
                          min-width:190px;background:#fff;border-radius:10px;
                          border:1px solid #e5e7eb;
                          box-shadow:0 4px 16px rgba(0,0,0,0.10);
                          padding:6px;z-index:200;"
                   onclick="event.stopPropagation()">
                <?php
                $statusOptions = [
                  'in_transit' => ['label' => 'In Progress', 'color' => '#3b82f6'],
                  'delivered'  => ['label' => 'Arrived',     'color' => '#22c55e'],
                  'cancelled'  => ['label' => 'Canceled',    'color' => '#ef4444'],
                  'pending'    => ['label' => 'Draft',        'color' => '#9ca3af'],
                ];
                foreach ($statusOptions as $val => $opt):
                  $isActive = ($val === $status_filter);
                  $checked  = ($status_filter === $val);
                  $baseUrl  = 'delivery_tracking.php?status=' . $val
                            . ($filter_date ? '&filter_date='.urlencode($filter_date) : '')
                            . (!empty($_GET['search']) ? '&search='.urlencode($_GET['search']) : '');
                ?>
                <a href="<?php echo $baseUrl; ?>"
                   class="status-drop-item <?php echo $isActive ? 'status-drop-item--active' : ''; ?>">
                  <span style="width:17px;height:17px;border-radius:4px;flex-shrink:0;
                               border:1.5px solid <?php echo $checked ? '#3b82f6' : '#d1d5db'; ?>;
                               background:<?php echo $checked ? '#3b82f6' : '#fff'; ?>;
                               display:inline-flex;align-items:center;justify-content:center;">
                    <?php if ($checked): ?>
                    <i class="fas fa-check" style="font-size:9px;color:#fff"></i>
                    <?php endif; ?>
                  </span>
                  <span style="width:9px;height:9px;border-radius:50%;flex-shrink:0;
                               background:<?php echo $opt['color']; ?>;display:inline-block"></span>
                  <span style="font-size:13px;font-weight:<?php echo $checked ? '500' : '400'; ?>;
                               color:<?php echo $checked ? '#111827' : '#374151'; ?>">
                    <?php echo $opt['label']; ?>
                  </span>
                </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </form>

        <!-- Table -->
        <div class="tbl-wrap">
          <table class="tbl">
            <thead>
              <tr>
                <th>Shipment ID</th>
                <th>Shipment Event</th>
                <th>Status</th>
                <th>Expected Arrival</th>
                <th>Order</th>
                <th>Carrier</th>
                <th>Supplier</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($filtered_deliveries)): ?>
              <tr>
                <td colspan="8">
                  <div class="empty-state">
                    <i class="fas fa-truck"></i>
                    <p>No deliveries found.</p>
                  </div>
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($filtered_deliveries as $idx => $delivery):
                  $step = status_to_step($delivery['status']);
                  $pill = status_pill($delivery['status']);
              ?>
              <tr data-panel-index="<?php echo $idx; ?>" onclick="openPanel(<?php echo $idx; ?>)">
                <td>
                  <div class="del-id"><?php echo htmlspecialchars($delivery['delivery_number']); ?></div>
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
                <td style="color:#6b7280"><?php echo htmlspecialchars($delivery['po_number']); ?></td>
                <td><?php echo carrier_badge($delivery['carrier'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($delivery['supplier_name']); ?></td>
                <td onclick="event.stopPropagation()">
                  <div class="d-flex gap-1">
                    <?php if (is_admin() || is_manager()): ?>
                    <button class="btn-act blue"
                            data-bs-toggle="modal"
                            data-bs-target="#updateStatusModal<?php echo $delivery['id']; ?>"
                            title="Update Status">
                      <i class="fas fa-sync-alt"></i>
                    </button>
                    <?php endif; ?>
                    <button class="btn-act"
                            onclick="openPanel(<?php echo $idx; ?>)"
                            title="View Details">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <?php if (is_admin() || is_manager()): ?>
              <div class="modal fade" id="updateStatusModal<?php echo $delivery['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" style="font-size:15px;font-weight:600">Update Shipment Status</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                      <div class="modal-body">
                        <input type="hidden" name="delivery_id" value="<?php echo $delivery['id']; ?>">
                        <p style="font-size:12px;color:#6b7280;margin-bottom:16px">
                          Shipment: <strong><?php echo htmlspecialchars($delivery['delivery_number']); ?></strong>
                          &nbsp;·&nbsp; Current:
                          <span class="pill <?php echo $pill['cls']; ?>" style="padding:2px 8px;font-size:11px">
                            <span class="pill-dot"></span><?php echo $pill['label']; ?>
                          </span>
                        </p>
                        <label class="form-label fw-semibold" style="font-size:12px">New Status</label>
                        <select name="new_status" class="form-select" required>
                          <option value="">Select new status…</option>
                          <?php if ($delivery['status'] === 'pending'): ?>
                            <option value="approved">Approved</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                          <?php elseif ($delivery['status'] === 'approved'): ?>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                          <?php elseif ($delivery['status'] === 'in_transit'): ?>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                          <?php elseif ($delivery['status'] === 'delivered'): ?>
                            <option value="pending">Reset to Pending</option>
                            <option value="cancelled">Cancel</option>
                          <?php elseif ($delivery['status'] === 'cancelled'): ?>
                            <option value="pending">Reactivate</option>
                          <?php endif; ?>
                        </select>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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

      </div>
      <!-- /deliveries card -->

    </main>
    <!-- END MAIN -->

  </div>
</div>

<!-- ══════════════════════════════════════════
     SLIDE-OVER PANEL
══════════════════════════════════════════ -->
<div class="panel-overlay" id="panelOverlay" onclick="closePanel()"></div>

<div class="detail-panel" id="detailPanel">
  <button class="panel-close-btn" onclick="closePanel()">
    <i class="fas fa-times"></i>
  </button>

  <!-- Panel Header -->
  <div class="dp-header">
    <div class="dp-header-top">
      <span class="dp-shp-id" id="dp-del-number">—</span>
      <div class="dp-nav">
        <button onclick="panelNavigate(-1)" title="Previous"><i class="fas fa-chevron-left"></i></button>
        <button onclick="panelNavigate(1)"  title="Next"><i class="fas fa-chevron-right"></i></button>
      </div>
    </div>
    <div class="dp-badges" id="dp-badges"></div>
    <div class="dp-meta" id="dp-meta"></div>
    <div class="dp-actions">
      <button class="dp-btn-cancel" id="dp-cancel-order-btn">
        <i class="fas fa-times-circle"></i> Cancel Order
      </button>
      <button class="dp-btn-notify" onclick="openNotificationChoice()">
        <i class="fas fa-bell"></i> Notify Customer
      </button>
      <button class="dp-btn-back" onclick="closePanel()">
        <i class="fas fa-arrow-left"></i> Back to List
      </button>
    </div>
  </div>

  <!-- Panel Body -->
  <div class="dp-body">

    <!-- Left: shipment detail -->
    <div class="dp-left">
      <div class="dp-address" id="dp-address"></div>
      <div class="dp-steps" id="dp-steps"></div>
      <div class="dp-time-grid" id="dp-time-grid"></div>
      <div id="dp-warning"></div>
      <div class="dp-section-title">Shipment Status</div>
      <ul class="dp-timeline" id="dp-timeline"></ul>
    </div>

    <!-- Right: map OR email composer -->
    <div class="dp-right map-mode" id="dp-right-col">

      <!-- MAP VIEW -->
      <div class="dp-map-wrap" id="dp-map-wrap">
        <svg class="dp-map-svg" viewBox="0 0 460 600" xmlns="http://www.w3.org/2000/svg">
          <rect width="460" height="600" fill="#d4dce8"/>
          <path d="M0 120 Q50 90 100 130 Q150 160 200 120 Q250 100 300 110 Q350 100 400 115 Q430 100 460 120 L460 0 L0 0Z" fill="#c8d4e0" opacity="0.7"/>
          <path d="M0 250 Q60 220 120 260 Q170 290 220 250 L280 230 L340 210 L400 195 L460 180 L460 160 Q420 175 380 195 Q340 220 300 195 Q260 175 220 190 Q170 175 120 190 Q70 175 0 190Z" fill="#bfcfdf" opacity="0.5"/>
          <polyline points="195,90 180,140 165,190 148,240 130,290 112,340 95,390 80,440 68,490"
                    stroke="#e8521a" stroke-width="2.5" fill="none" stroke-dasharray="7,5" opacity="0.9"/>
          <circle cx="195" cy="90" r="6" fill="#0d1578"/>
          <circle cx="195" cy="90" r="3" fill="#fff"/>
          <circle cx="68" cy="490" r="6" fill="#e8521a"/>
          <circle cx="68" cy="490" r="3" fill="#fff"/>
          <line x1="0" y1="150" x2="460" y2="150" stroke="#b0bed0" stroke-width="0.5" opacity="0.4"/>
          <line x1="0" y1="300" x2="460" y2="300" stroke="#b0bed0" stroke-width="0.5" opacity="0.4"/>
          <line x1="0" y1="450" x2="460" y2="450" stroke="#b0bed0" stroke-width="0.5" opacity="0.4"/>
          <line x1="115" y1="0" x2="115" y2="600" stroke="#b0bed0" stroke-width="0.5" opacity="0.4"/>
          <line x1="230" y1="0" x2="230" y2="600" stroke="#b0bed0" stroke-width="0.5" opacity="0.4"/>
          <line x1="345" y1="0" x2="345" y2="600" stroke="#b0bed0" stroke-width="0.5" opacity="0.4"/>
        </svg>
        <div class="dp-map-controls">
          <button class="dp-map-btn">+</button>
          <button class="dp-map-btn">−</button>
        </div>
        <div class="dp-map-toggle">
          <button class="dp-map-toggle-btn">Satellite</button>
          <button class="dp-map-toggle-btn active">Map View</button>
        </div>
      </div>

      <!-- EMAIL COMPOSER VIEW -->
      <div class="dp-email-wrap" id="dp-email-wrap">

        <!-- Email header -->
        <div class="ep-header">
          <div class="ep-icon-wrap">
            <div class="ep-icon">✉️</div>
            <div class="ep-title-block">
              <div class="ep-title">Notify Email</div>
              <div class="ep-subtitle">We have an update on your recent order.</div>
            </div>
          </div>
          <button class="ep-close-btn" onclick="closeEmailComposer()" title="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Email body -->
        <div class="ep-body">

          <!-- To field -->
          <div class="ep-field-row">
            <span class="ep-field-label">To</span>
            <input class="ep-field-input" type="email" id="ep-to" placeholder="recipient@email.com">
            <span class="ep-field-cc">Cc Bcc</span>
          </div>

          <!-- Subject field -->
          <div class="ep-field-row">
            <span class="ep-field-label">Subject</span>
            <input class="ep-field-input" type="text" id="ep-subject" placeholder="Email subject">
            <span class="ep-char-count" id="ep-char-count">0</span>
          </div>

          <!-- Template selector -->
          <div class="ep-template-row">
            <select class="ep-template-select" id="ep-template" onchange="applyTemplate(this.value)">
              <option value="">Template email</option>
              <option value="shipped">Order Shipped</option>
              <option value="delayed">Delivery Delayed</option>
              <option value="out">Out for Delivery</option>
              <option value="delivered">Delivered Successfully</option>
            </select>
            <button class="ep-save-tpl-btn" onclick="saveTemplate()">Save Template</button>
          </div>

          <!-- Message -->
          <textarea class="ep-message" id="ep-message" placeholder="Your message..."></textarea>

          <!-- Toolbar -->
          <div class="ep-toolbar">
            <button class="ep-tb-btn" title="Bold" onclick="formatText('bold')"><b>B</b></button>
            <button class="ep-tb-btn" title="Italic" onclick="formatText('italic')"><i>I</i></button>
            <button class="ep-tb-btn" title="Underline" onclick="formatText('underline')"><u>U</u></button>
            <div class="ep-tb-divider"></div>
            <button class="ep-tb-btn" title="Attach file"><i class="fas fa-paperclip"></i></button>
            <button class="ep-tb-btn" title="Insert image"><i class="fas fa-image"></i></button>
            <button class="ep-tb-btn" title="Insert link"><i class="fas fa-link"></i></button>
            <button class="ep-tb-btn" title="Emoji"><i class="fas fa-smile"></i></button>
            <button class="ep-tb-btn" title="Schedule send"><i class="fas fa-clock"></i></button>
            <button class="ep-tb-btn" title="More options"><i class="fas fa-ellipsis-h"></i></button>
            <div class="ep-tb-divider"></div>
            <button class="ep-ask-ai" onclick="askAI()">
              <i class="fas fa-wand-magic-sparkles" style="font-size:11px"></i> Ask AI
            </button>
            <button class="ep-preview-btn" onclick="previewEmail()">Preview Email</button>
          </div>

        </div>

        <!-- Email footer -->
        <div class="ep-footer">
          <div class="ep-footer-info">
            Learn more about <a href="#">Notification</a>
          </div>
          <div class="ep-footer-btns">
            <button class="ep-btn-cancel" onclick="closeEmailComposer()">Cancel</button>
            <button class="ep-btn-confirm" onclick="sendEmailNotification()">Confirm</button>
          </div>
        </div>

      </div>
      <!-- /email composer -->

    </div>
    <!-- /dp-right -->

  </div>
</div>
<!-- END SLIDE-OVER PANEL -->

<!-- ══════════════════════════════════════════
     SHIPMENT NOTIFICATION CHOICE MODAL
══════════════════════════════════════════ -->
<div class="modal fade" id="notificationChoiceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:380px">
    <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 50px rgba(0,0,0,0.15)">

      <!-- Header -->
      <div class="modal-header" style="border-bottom:none;padding:24px 24px 8px;flex-direction:column;text-align:center;align-items:center">
        <h5 style="font-size:17px;font-weight:700;color:#111827;margin:0 0 4px">Shipment Notification</h5>
        <p style="font-size:13px;color:#9ca3af;margin:0">Please choose shipment notification</p>
      </div>

      <!-- Options -->
      <div class="modal-body" style="padding:12px 20px 8px">
        <div class="notif-option-list">

          <label class="notif-option" id="notif-opt-inbox">
            <div class="notif-opt-left">
              <div class="notif-opt-title">Inbox Notification</div>
              <div class="notif-opt-sub">Shipment notification options for WhatsApp</div>
            </div>
            <div class="notif-opt-right">
              <i class="fab fa-whatsapp" style="font-size:18px;color:#25d366"></i>
              <input type="radio" name="notif_type" value="inbox" class="notif-radio">
            </div>
          </label>

          <label class="notif-option" id="notif-opt-email">
            <div class="notif-opt-left">
              <div class="notif-opt-title">Email Notification</div>
              <div class="notif-opt-sub">Shipment notification options for Email</div>
            </div>
            <div class="notif-opt-right">
              <i class="fas fa-envelope" style="font-size:16px;color:#6b7280"></i>
              <input type="radio" name="notif_type" value="email" class="notif-radio">
            </div>
          </label>

          <label class="notif-option" id="notif-opt-sms">
            <div class="notif-opt-left">
              <div class="notif-opt-title">SMS Notification</div>
              <div class="notif-opt-sub">Shipment notification options for SMS</div>
            </div>
            <div class="notif-opt-right">
              <i class="fas fa-sms" style="font-size:16px;color:#6b7280"></i>
              <input type="radio" name="notif_type" value="sms" class="notif-radio">
            </div>
          </label>

        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer" style="border-top:none;padding:12px 20px 22px;justify-content:center;gap:12px">
        <button type="button"
                style="padding:10px 32px;border:1px solid #d1d5db;border-radius:9px;background:#f3f4f6;color:#374151;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit"
                data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="notif-continue-btn"
                style="padding:10px 32px;border:none;border-radius:9px;background:#e8521a;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit"
                onclick="handleNotificationContinue()">Continue</button>
      </div>

    </div>
  </div>
</div>

<style>
/* ── Notification option list ── */
.notif-option-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.notif-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 14px;
    border-radius: 10px;
    cursor: pointer;
    border: 1.5px solid transparent;
    background: #f9fafb;
    transition: all 0.15s;
    user-select: none;
}
.notif-option:hover {
    background: #f3f4f6;
}
.notif-option.selected {
    background: #fff4f0;
    border-color: #e8521a;
}
.notif-opt-left { flex: 1; }
.notif-opt-title {
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 2px;
}
.notif-opt-sub {
    font-size: 15px;
    color: #9ca3af;
}
.notif-opt-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.notif-radio {
    width: 17px;
    height: 17px;
    accent-color: #e8521a;
    cursor: pointer;
    flex-shrink: 0;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Delivery data from PHP ──
const panelDeliveries = <?php echo json_encode(array_values($js_deliveries)); ?>;
let currentPanelIndex = null;

// ── Step icons ──
const STEP_ICONS = [
    '<i class="fas fa-clipboard-list" style="font-size:10px"></i>',
    '<i class="fas fa-check"          style="font-size:10px"></i>',
    '<i class="fas fa-box"            style="font-size:10px"></i>',
    '<i class="fas fa-truck"          style="font-size:10px"></i>',
    '<i class="fas fa-map-marker-alt" style="font-size:10px"></i>',
];

// ── Status badge class ──
function statusBadgeCls(status) {
    return {
        pending:    'dp-badge-pending',
        approved:   'dp-badge-progress',
        in_transit: 'dp-badge-progress',
        delivered:  'dp-badge-delivered',
        cancelled:  'dp-badge-cancelled',
    }[status] || 'dp-badge-pending';
}

// ── Open panel ──
function openPanel(index) {
    currentPanelIndex = index;
    renderPanel(index);
    // Always reset to map mode when opening a new row
    closeEmailComposer(false);
    document.getElementById('panelOverlay').classList.add('open');
    document.getElementById('detailPanel').classList.add('open');
    document.querySelectorAll('.tbl tbody tr[data-panel-index]').forEach(r => {
        r.classList.toggle('row-active', parseInt(r.dataset.panelIndex) === index);
    });
}

// ── Close panel ──
function closePanel() {
    document.getElementById('panelOverlay').classList.remove('open');
    document.getElementById('detailPanel').classList.remove('open');
    document.querySelectorAll('.tbl tbody tr[data-panel-index]').forEach(r => r.classList.remove('row-active'));
    currentPanelIndex = null;
}

// ── Navigate between rows ──
function panelNavigate(dir) {
    if (currentPanelIndex === null) return;
    const next = currentPanelIndex + dir;
    if (next >= 0 && next < panelDeliveries.length) openPanel(next);
}

// ── Render panel content ──
function renderPanel(index) {
    const d = panelDeliveries[index];
    if (!d) return;

    document.getElementById('dp-del-number').textContent = d.del_number || '—';

    // Badges
    let badges = `<span class="dp-badge ${statusBadgeCls(d.status)}">${d.status_label}</span>`;
    if (d.delay) badges += `<span class="dp-badge dp-badge-delay"><i class="fas fa-triangle-exclamation" style="font-size:9px"></i> Delay</span>`;
    document.getElementById('dp-badges').innerHTML = badges;

    // Meta
    document.getElementById('dp-meta').innerHTML =
        `Delivery Date: <strong>${d.delivery_date || '—'}</strong>&nbsp;&nbsp;·&nbsp;&nbsp;` +
        `PO: <a href="#">${d.po_number || '—'}</a>`;

    // Address
    const fromHtml = d.from_address
        ? `<div class="dp-addr-row">
               <div class="dp-addr-dot dot-from"></div>
               <span>${escHtml(d.from_address)}</span>
           </div>` : '';
    const toHtml = d.to_address
        ? `<div class="dp-addr-row">
               <div class="dp-addr-dot dot-to"></div>
               <span>${escHtml(d.to_address)}</span>
               <span class="dp-carrier-badge">${buildCarrierBadge(d.carrier)}</span>
           </div>` : '';
    document.getElementById('dp-address').innerHTML =
        (fromHtml || toHtml)
            ? fromHtml + toHtml
            : `<div style="font-size:12px;color:#9ca3af">No address information available</div>`;

    // Steps
    let stepsHtml = '';
    d.steps.forEach((done, i) => {
        stepsHtml += `<div class="dp-step-node ${done ? 'done' : 'pending'}">${STEP_ICONS[i]}</div>`;
        if (i < d.steps.length - 1) {
            stepsHtml += `<div class="dp-step-line ${done ? 'done' : 'pending'}"></div>`;
        }
    });
    document.getElementById('dp-steps').innerHTML = stepsHtml;

    // Time grid
    document.getElementById('dp-time-grid').innerHTML = `
        <div class="dp-time-cell"><label>Total Time</label><span>${escHtml(d.total_time)}</span></div>
        <div class="dp-time-cell"><label>Departure</label><span>${escHtml(d.departure_time)}</span></div>
        <div class="dp-time-cell"><label>Expected</label><span>${escHtml(d.delivery_date)}</span></div>`;

    // Warning
    const warnEl = document.getElementById('dp-warning');
    if (d.warning) {
        warnEl.innerHTML = `<div class="dp-warning">
            <i class="fas fa-triangle-exclamation" style="flex-shrink:0;margin-top:1px"></i>
            <span>${escHtml(d.warning)}</span>
        </div>`;
    } else {
        warnEl.innerHTML = '';
    }

    // Timeline
    let tlHtml = '';
    d.timeline.forEach((item, i) => {
        const isLast = i === d.timeline.length - 1;
        tlHtml += `<li class="dp-tl-item">
            <div class="dp-tl-left">
                <div class="dp-tl-dot ${item.done ? 'done' : 'pending'}"></div>
                ${!isLast ? `<div class="dp-tl-line ${item.done ? 'done' : 'pending'}"></div>` : ''}
            </div>
            <div class="dp-tl-right">
                <div class="dp-tl-label ${item.done ? '' : 'pending'}">${escHtml(item.label)}</div>
                <div class="dp-tl-date">${escHtml(item.date)}</div>
                ${item.note ? `<div class="dp-tl-note">${escHtml(item.note)}</div>` : ''}
            </div>
        </li>`;
    });
    document.getElementById('dp-timeline').innerHTML = tlHtml;
}

// ══════════════════════════════════════════
// EMAIL COMPOSER
// ══════════════════════════════════════════

function openEmailComposer() {
    if (currentPanelIndex === null) return;
    const d = panelDeliveries[currentPanelIndex];

    // Pre-fill fields
    document.getElementById('ep-to').value      = d.supplier_email || '';
    document.getElementById('ep-subject').value = `Quick Update on Your Order #${d.po_number} - ${d.supplier}`;
    document.getElementById('ep-message').value = '';
    document.getElementById('ep-template').value = '';
    updateCharCount();

    // Switch right column to email mode
    const rightCol = document.getElementById('dp-right-col');
    rightCol.classList.remove('map-mode');
    rightCol.classList.add('email-mode');
}

function closeEmailComposer(animate = true) {
    const rightCol = document.getElementById('dp-right-col');
    rightCol.classList.remove('email-mode');
    rightCol.classList.add('map-mode');
}

function updateCharCount() {
    const subj  = document.getElementById('ep-subject');
    const count = document.getElementById('ep-char-count');
    if (subj && count) count.textContent = subj.value.length;
}

document.addEventListener('DOMContentLoaded', function() {
    const subj = document.getElementById('ep-subject');
    if (subj) subj.addEventListener('input', updateCharCount);
});

// ── Template presets ──
const EMAIL_TEMPLATES = {
    shipped: {
        subject: 'Your Order Has Been Shipped!',
        message: 'Dear Customer,\n\nWe are pleased to inform you that your order has been shipped and is on its way to you.\n\nThank you for your business!\n\nBest regards,\nMcPIL Team'
    },
    delayed: {
        subject: 'Important Update: Shipment Delay Notice',
        message: 'Dear Customer,\n\nWe want to inform you that your shipment is experiencing a slight delay due to high carrier volume. We apologize for any inconvenience and are working to deliver your order as soon as possible.\n\nBest regards,\nMcPIL Team'
    },
    out: {
        subject: 'Your Order is Out for Delivery Today!',
        message: 'Dear Customer,\n\nGreat news! Your order is out for delivery today. Please ensure someone is available to receive the package.\n\nThank you!\n\nBest regards,\nMcPIL Team'
    },
    delivered: {
        subject: 'Your Order Has Been Delivered Successfully',
        message: 'Dear Customer,\n\nYour order has been delivered successfully. We hope you are satisfied with your purchase.\n\nIf you have any questions or concerns, please don\'t hesitate to contact us.\n\nBest regards,\nMcPIL Team'
    }
};

function applyTemplate(value) {
    if (!value || !EMAIL_TEMPLATES[value]) return;
    const tpl = EMAIL_TEMPLATES[value];
    document.getElementById('ep-subject').value  = tpl.subject;
    document.getElementById('ep-message').value  = tpl.message;
    updateCharCount();
}

function saveTemplate() {
    const subject = document.getElementById('ep-subject').value.trim();
    const message = document.getElementById('ep-message').value.trim();
    if (!subject && !message) {
        alert('Please fill in at least the subject or message before saving a template.');
        return;
    }
    alert('Template saved successfully!');
}

function formatText(command) {
    // Textarea-based bold/italic/underline markers
    const ta  = document.getElementById('ep-message');
    const start = ta.selectionStart;
    const end   = ta.selectionEnd;
    const sel   = ta.value.substring(start, end);
    if (!sel) return;
    const markers = { bold: '**', italic: '_', underline: '__' };
    const m = markers[command] || '';
    ta.value = ta.value.substring(0, start) + m + sel + m + ta.value.substring(end);
    ta.focus();
    ta.setSelectionRange(start + m.length, end + m.length);
}

function askAI() {
    const d = panelDeliveries[currentPanelIndex];
    if (!d) return;
    const prompt = `Write a professional email notification for shipment ${d.del_number} (PO: ${d.po_number}) to the supplier ${d.supplier}. Status: ${d.status_label}. Expected delivery: ${d.delivery_date}.`;
    const ta = document.getElementById('ep-message');
    ta.value = 'Generating email with AI...';
    ta.disabled = true;

    // Simulated AI fill (replace with real Anthropic API call if desired)
    setTimeout(() => {
        ta.value = `Dear ${d.supplier} Team,\n\nI hope this message finds you well. We are writing to provide an update regarding shipment ${d.del_number} associated with Purchase Order ${d.po_number}.\n\nCurrent status: ${d.status_label}\nExpected delivery: ${d.delivery_date}\n\nPlease don't hesitate to reach out if you have any questions or require further information.\n\nBest regards,\nMcPIL Pharmaceutical Laboratory Team`;
        ta.disabled = false;
        ta.focus();
    }, 900);
}

function previewEmail() {
    const to      = document.getElementById('ep-to').value;
    const subject = document.getElementById('ep-subject').value;
    const message = document.getElementById('ep-message').value;

    const previewHtml = `
        <html><body style="font-family:Arial,sans-serif;padding:24px;max-width:600px;margin:auto;color:#111">
        <div style="border:1px solid #e5e7eb;border-radius:8px;padding:24px">
            <p style="font-size:12px;color:#6b7280;margin:0 0 4px"><strong>To:</strong> ${escHtml(to)}</p>
            <p style="font-size:12px;color:#6b7280;margin:0 0 16px"><strong>Subject:</strong> ${escHtml(subject)}</p>
            <hr style="border:none;border-top:1px solid #f3f4f6;margin:0 0 16px">
            <div style="font-size:14px;line-height:1.7;white-space:pre-line">${escHtml(message)}</div>
        </div>
        </body></html>`;

    const win = window.open('', '_blank', 'width=680,height=500,scrollbars=yes');
    win.document.write(previewHtml);
    win.document.close();
}

function sendEmailNotification() {
    const d       = panelDeliveries[currentPanelIndex];
    const to      = document.getElementById('ep-to').value.trim();
    const subject = document.getElementById('ep-subject').value.trim();
    const message = document.getElementById('ep-message').value.trim();

    if (!to || !subject) {
        alert('Please fill in the recipient and subject fields.');
        return;
    }

    const btn = document.querySelector('.ep-btn-confirm');
    btn.textContent = 'Sending…';
    btn.disabled    = true;

    const formData = new FormData();
    formData.append('delivery_id', d.id);
    formData.append('recipient',   to);
    formData.append('subject',     subject);
    formData.append('message',     message);

    fetch('send_delivery_notification.php', {
        method: 'POST',
        body:   formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.textContent = 'Sent ✓';
            btn.style.background = '#16a34a';
            setTimeout(() => {
                closeEmailComposer();
                btn.textContent     = 'Confirm';
                btn.style.background = '';
                btn.disabled        = false;
            }, 1500);
        } else {
            alert('Error: ' + (data.message || 'Failed to send email.'));
            btn.textContent = 'Confirm';
            btn.disabled    = false;
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('An error occurred while sending the email.');
        btn.textContent = 'Confirm';
        btn.disabled    = false;
    });
}

// ══════════════════════════════════════════
// NOTIFICATION CHOICE MODAL
// ══════════════════════════════════════════
let _notifModal = null;

function openNotificationChoice() {
    // Reset selection
    document.querySelectorAll('input[name="notif_type"]').forEach(r => r.checked = false);
    document.querySelectorAll('.notif-option').forEach(el => el.classList.remove('selected'));

    if (!_notifModal) {
        _notifModal = new bootstrap.Modal(document.getElementById('notificationChoiceModal'));
    }
    _notifModal.show();

    // Highlight selected option on radio change
    document.querySelectorAll('input[name="notif_type"]').forEach(radio => {
        radio.onchange = function() {
            document.querySelectorAll('.notif-option').forEach(el => el.classList.remove('selected'));
            this.closest('.notif-option').classList.add('selected');
        };
    });
}

function handleNotificationContinue() {
    const selected = document.querySelector('input[name="notif_type"]:checked');
    if (!selected) {
        alert('Please choose a notification type.');
        return;
    }

    const type = selected.value;
    _notifModal.hide();

    if (type === 'email') {
        // Small delay so modal finishes closing before panel animates
        setTimeout(() => openEmailComposer(), 300);
    } else if (type === 'inbox') {
        setTimeout(() => {
            const d = panelDeliveries[currentPanelIndex];
            alert(`WhatsApp notification queued for shipment ${d ? d.del_number : ''}.`);
        }, 300);
    } else if (type === 'sms') {
        setTimeout(() => {
            const d = panelDeliveries[currentPanelIndex];
            alert(`SMS notification queued for shipment ${d ? d.del_number : ''}.`);
        }, 300);
    }
}


function buildCarrierBadge(carrier) {
    if (!carrier) return '';
    const lower = carrier.toLowerCase();
    let cls = 'c-regular';
    if (lower.includes('fedex'))       cls = 'c-fedex';
    else if (lower.includes('dhl'))    cls = 'c-dhl';
    else if (lower.includes('ups'))    cls = 'c-ups';
    else if (lower.includes('tnt'))    cls = 'c-tnt';
    else if (lower.includes('aramex')) cls = 'c-aramex';
    return `<span class="carrier ${cls}">${escHtml(carrier)}</span>`;
}

// ── HTML escape ──
function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Keyboard close ──
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        const rightCol = document.getElementById('dp-right-col');
        if (rightCol.classList.contains('email-mode')) {
            closeEmailComposer();
        } else {
            closePanel();
        }
    }
});

// ══════════════════════════════════════════
// PAGE SCRIPTS
// ══════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {

    // Date pill
    const dateInput  = document.getElementById('filterDateInput');
    const pillLabel  = document.getElementById('datePillLabel');
    const filterForm = document.getElementById('filterForm');
    if (dateInput) {
        document.querySelector('.date-range-pill').addEventListener('click', function() {
            dateInput.style.position = 'fixed';
            dateInput.style.opacity  = '0';
            dateInput.style.width    = '1px';
            dateInput.style.height   = '1px';
            dateInput.showPicker ? dateInput.showPicker() : dateInput.click();
        });
        dateInput.addEventListener('change', function() {
            if (this.value) {
                const d = new Date(this.value);
                const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                pillLabel.textContent = d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
            } else {
                const now = new Date();
                const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                pillLabel.textContent = now.getDate() + ' ' + months[now.getMonth()] + ' – ' +
                                        now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
            }
            filterForm.submit();
        });
    }

    // Search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); filterForm.submit(); }
        });
        let searchTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => filterForm.submit(), 500);
        });
    }

    // Collapse form
    const collapseBtn   = document.querySelector('.btn-collapse');
    const filterBarForm = document.querySelector('#filterForm');
    if (collapseBtn && filterBarForm) {
        collapseBtn.addEventListener('click', function() {
            const hidden = filterBarForm.style.display === 'none';
            filterBarForm.style.display = hidden ? '' : 'none';
            collapseBtn.querySelector('i').className = hidden ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
        });
    }

    // Status dropdown: click outside to close
    document.addEventListener('click', function(e) {
        const drop    = document.getElementById('statusDrop');
        const dropBtn = document.getElementById('statusDropBtn');
        if (drop && dropBtn && !drop.contains(e.target) && !dropBtn.contains(e.target)) {
            drop.style.display = 'none';
        }
    });

});
</script>
</body>
</html>