<?php
require_once 'config.php';

require_roles(['admin', 'store']);

// ── POST handlers ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_po'])) {
    if (empty($_POST['po_number']) || empty($_POST['supplier_id']) || empty($_POST['order_date'])) {
        $error_message = "Please fill in all required fields.";
    } else {
        $po_number   = sanitize_input($_POST['po_number']);
        $supplier_id = (int)$_POST['supplier_id'];
        $order_date  = $_POST['order_date'];
        $expected_delivery_date = !empty($_POST['expected_delivery_date']) ? $_POST['expected_delivery_date'] : null;
        $notes = sanitize_input($_POST['notes']);
        $items = [];
        if (isset($_POST['items']['item_name'])) {
            foreach ($_POST['items']['item_name'] as $key => $item_name) {
                if (!empty(trim($item_name))) {
                    $quantity   = isset($_POST['items']['quantity'][$key])   ? (float)$_POST['items']['quantity'][$key]   : 0;
                    $unit_price = isset($_POST['items']['unit_price'][$key]) ? (float)$_POST['items']['unit_price'][$key] : 0;
                    if ($quantity > 0 && $unit_price > 0) {
                        $items[] = ['item_name' => sanitize_input($item_name), 'quantity' => $quantity, 'unit_price' => $unit_price];
                    }
                }
            }
        }
        if (!empty($items)) {
            $po_id = create_purchase_order($po_number, $supplier_id, $order_date, $expected_delivery_date, $items, $notes, $_SESSION['user_id']);
            $success_message = $po_id ? "Purchase Order created! PO: $po_number" : "Failed to create Purchase Order.";
            if (!$po_id) $error_message = $success_message;
        } else {
            $error_message = "Please add at least one valid item.";
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    update_po_status($_POST['po_id'], $_POST['new_status'], $_POST['admin_notes'])
        ? $success_message = "Status updated!" : $error_message = "Failed to update status.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $type = ($_SESSION['user_role'] === 'admin') ? 'admin' : 'store';
    add_po_message($_POST['po_id'], $_SESSION['user_id'], $_POST['message'], $type)
        ? $success_message = "Message sent!" : $error_message = "Failed to send message.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_po'])) {
    delete_purchase_order($_POST['po_id']) ? $success_message = "PO archived!" : $error_message = "Failed.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_po'])) {
    archive_purchase_order($_POST['po_id']) ? $success_message = "PO archived!" : $error_message = "Failed.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_po'])) {
    $items = [];
    if (isset($_POST['items'])) {
        foreach ($_POST['items']['item_name'] as $key => $item_name) {
            if (!empty($item_name)) $items[] = ['item_name'=>$item_name,'quantity'=>$_POST['items']['quantity'][$key],'unit_price'=>$_POST['items']['unit_price'][$key]];
        }
    }
    !empty($items)
        ? (update_purchase_order($_POST['po_id'],$_POST['po_number'],$_POST['supplier_id'],$_POST['order_date'],$_POST['expected_delivery_date'],$items,$_POST['notes']) ? $success_message="PO updated!" : $error_message="Failed.")
        : $error_message = "Add at least one item.";
}

$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

if ($_SESSION['user_role'] === 'admin') {
    $status_filter   = isset($_GET['status']) ? $_GET['status'] : null;
    $purchase_orders = get_purchase_orders_admin($limit, $offset, $status_filter);
} else {
    $purchase_orders = get_purchase_orders_store($_SESSION['user_id'], $limit, $offset);
}

$suppliers       = get_suppliers();
$unread_messages = get_unread_message_count($_SESSION['user_id']);
$is_store        = ($_SESSION['user_role'] === 'store');
$is_admin        = ($_SESSION['user_role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo APP_NAME; ?> – Purchase Order</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="public/css/design-system.css" rel="stylesheet">
<style>
/* ── CSS Variables ── */
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
    --accent: #0d1578;
    --accent2: #e8521a;
    --gray-50: #f5f6fa;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-600: #4b5563;
    --gray-900: #111827;
}

* { box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; background: var(--gray-50); margin: 0; }

/* ── Sidebar ── */
.sidebar {
    position: fixed; top: 0; left: 0;
    width: var(--sidebar-w); height: 100vh;
    background: var(--sb-bg);
    display: flex; flex-direction: column;
    z-index: 9999; overflow-y: auto; overflow-x: hidden;
    scrollbar-width: none;
}
.sidebar::-webkit-scrollbar { display: none; }
.sidebar-brand {
    display: flex; align-items: center; gap: 12px;
    padding: 20px 16px 18px;
    border-bottom: 1px solid var(--sb-border); margin-bottom: 10px;
}
.sidebar-logo-ring {
    width: 40px; height: 40px; border-radius: 50%; overflow: hidden;
    flex-shrink: 0; border: 2px solid rgba(255,255,255,0.30);
    display: flex; align-items: center; justify-content: center;
}
.sidebar-logo-ring img { width: 100%; height: 100%; object-fit: cover; }
.sidebar-brand-name { font-size: .92rem; font-weight: 800; color: #fff; letter-spacing: .06em; text-transform: uppercase; }
.sidebar-brand-sub  { font-size: .55rem; color: rgba(255,255,255,.45); letter-spacing: .10em; text-transform: uppercase; white-space: nowrap; }
.sidebar-nav { flex: 1; padding: 0 10px; }
.nav-section-label { font-size: .62rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--sb-label); padding: 14px 8px 6px; }
.sidebar-link {
    display: flex; align-items: center; gap: 10px; padding: 9px 10px;
    border-radius: var(--sb-radius); color: var(--sb-text);
    text-decoration: none; font-size: .84rem; font-weight: 500;
    transition: background .15s, color .15s; margin-bottom: 2px;
}
.sidebar-link:hover { background: var(--sb-hover); color: var(--sb-text-active); }
.sidebar-link.active { background: var(--sb-active); color: var(--sb-text-active); }
.sidebar-link .icon {
    width: 30px; height: 30px; border-radius: 7px; background: var(--sb-icon-bg);
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; flex-shrink: 0; transition: background .15s;
}
.sidebar-link.active .icon { background: var(--sb-icon-active); color: #fff; }
.sidebar-link .link-label { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sidebar-link .badge-dot {
    width: 18px; height: 18px; border-radius: 50%; background: #e5534b;
    color: #fff; font-size: .6rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.sidebar-footer { padding: 10px 10px 20px; border-top: 1px solid var(--sb-border); margin-top: 6px; }
.sidebar-link.logout .icon { background: rgba(229,83,75,.15); color: #e5534b; }
.sidebar-link.logout { color: rgba(229,83,75,.85); }
.sidebar-link.logout:hover { background: rgba(229,83,75,.10); color: #e5534b; }

/* ── Main layout ── */
.main-content { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; }

/* ── Top bar ── */
.topbar {
    background: #fff; border-bottom: 1px solid var(--gray-200);
    padding: 9px 18px; display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
}
.topbar-left { display: flex; align-items: center; gap: 10px; }
.topbar-title { font-size: .92rem; font-weight: 700; color: var(--gray-900); }
.topbar-sub   { font-size: .68rem; color: var(--gray-400); }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.topbar-icon-btn {
    width: 30px; height: 30px; border-radius: 8px;
    border: 1px solid var(--gray-200); background: #fff;
    display: flex; align-items: center; justify-content: center;
    color: var(--gray-600); cursor: pointer; position: relative; transition: background .15s;
}
.topbar-icon-btn:hover { background: var(--gray-100); }
.topbar-icon-btn .badge-dot {
    position: absolute; top: -4px; right: -4px;
    width: 16px; height: 16px; border-radius: 50%;
    background: #e5534b; color: #fff; font-size: .58rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.user-chip { display: flex; align-items: center; gap: 8px; }
.user-avatar {
    width: 28px; height: 28px; border-radius: 50%;
    background: linear-gradient(135deg,#0d1578,#2f69ff);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .68rem; font-weight: 700;
}
.user-name { font-size: .76rem; font-weight: 600; color: var(--gray-900); line-height: 1.2; }
.user-role { font-size: .64rem; color: var(--gray-400); }
.mobile-sidebar-toggle {
    display: none; align-items: center; justify-content: center;
    width: 36px; height: 36px; border: none; border-radius: 10px;
    background: var(--accent); color: #fff; flex: 0 0 auto; cursor: pointer;
}
.mobile-sidebar-backdrop { display: none; }

/* ══════════════════════════════════════
   STORE — Cashier / POS Layout
══════════════════════════════════════ */
.pos-layout {
    flex: 1; display: flex; overflow: hidden;
    height: calc(100vh - 52px); background: var(--gray-50);
}
.pos-catalog {
    flex: 1; display: flex; flex-direction: column;
    overflow: hidden; padding: 16px 16px 0 16px;
}
.pos-topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px; gap: 10px; flex-shrink: 0;
}
.pos-topbar-left { display: flex; align-items: center; gap: 10px; flex: 1; }
.pos-search-wrap {
    display: flex; align-items: center; gap: 8px;
    background: #fff; border: 1.5px solid var(--gray-200);
    border-radius: 9px; padding: 7px 12px; flex: 1; max-width: 340px;
    transition: border-color .15s;
}
.pos-search-wrap:focus-within { border-color: var(--accent); }
.pos-search-wrap i { color: var(--gray-400); font-size: .8rem; }
.pos-search-wrap input {
    border: none; outline: none; background: transparent;
    font-size: .83rem; color: var(--gray-900);
    font-family: 'DM Sans', sans-serif; width: 100%;
}
.pos-notif-btn {
    width: 36px; height: 36px; border-radius: 9px;
    border: 1.5px solid var(--gray-200); background: #fff;
    display: flex; align-items: center; justify-content: center;
    color: var(--gray-600); cursor: pointer;
}
.cat-pills {
    display: flex; align-items: center; gap: 7px;
    margin-bottom: 14px; flex-wrap: wrap; flex-shrink: 0;
}
.cat-pill {
    padding: 6px 16px; border-radius: 20px; font-size: .76rem; font-weight: 600;
    border: 1.5px solid var(--gray-200); background: #fff;
    color: var(--gray-600); cursor: pointer; transition: all .15s; white-space: nowrap;
}
.cat-pill:hover { border-color: var(--accent); color: var(--accent); }
.cat-pill.active { background: var(--accent); border-color: var(--accent); color: #fff; }
.product-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 12px; overflow-y: auto; padding-bottom: 16px; flex: 1;
}
.product-grid::-webkit-scrollbar { width: 4px; }
.product-grid::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 4px; }
@media (max-width: 1300px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 1000px) { .product-grid { grid-template-columns: repeat(2, 1fr); } }
.product-card {
    background: #fff; border-radius: 12px; overflow: hidden; cursor: pointer;
    border: 2px solid transparent; transition: all .18s;
    box-shadow: 0 1px 4px rgba(0,0,0,.06); position: relative;
}
.product-card:hover { box-shadow: 0 6px 18px rgba(0,0,0,.10); transform: translateY(-2px); border-color: var(--gray-200); }
.product-card.in-cart { border-color: var(--accent); }
.product-card.in-cart .pc-selected-badge { display: flex; }
.pc-selected-badge {
    display: none; position: absolute; top: 8px; right: 8px;
    width: 22px; height: 22px; border-radius: 50%;
    background: var(--accent); color: #fff; font-size: .65rem; font-weight: 700;
    align-items: center; justify-content: center; z-index: 2;
}
.pc-img {
    width: 100%; aspect-ratio: 1/1; background: var(--gray-100);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.8rem; overflow: hidden;
}
.pc-img img { width: 100%; height: 100%; object-fit: cover; }
.pc-body { padding: 10px 10px 12px; }
.pc-name { font-size: .8rem; font-weight: 700; color: var(--gray-900); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pc-price { font-size: .85rem; font-weight: 700; color: var(--accent); }
.pc-btn {
    width: 24px; height: 24px; border-radius: 6px;
    background: var(--accent); border: none; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; cursor: pointer; flex-shrink: 0; transition: background .15s;
}
.pc-btn:hover { background: #0b1260; }
.pc-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 6px; }
.order-panel {
    width: 380px; flex-shrink: 0; background: #fff;
    border-left: 1px solid var(--gray-200);
    display: flex; flex-direction: column; overflow: hidden;
}
.op-header { padding: 12px 14px 10px; border-bottom: 1px solid var(--gray-100); flex-shrink: 0; }
.op-header h3 { font-size: .92rem; font-weight: 700; color: var(--gray-900); margin: 0 0 10px; }
.op-field { margin-bottom: 7px; }
.op-field label { font-size: .65rem; font-weight: 600; color: var(--gray-400); text-transform: uppercase; letter-spacing: .06em; display: block; margin-bottom: 2px; }
.op-field input, .op-field select {
    width: 100%; border: 1.5px solid var(--gray-200); border-radius: 7px;
    padding: 0 10px; font-size: .8rem; font-family: 'DM Sans', sans-serif;
    color: var(--gray-900); outline: none; background: #fff;
    transition: border-color .15s; height: 34px; box-sizing: border-box;
}
.op-field input:focus, .op-field select:focus { border-color: var(--accent); }
.op-field select { appearance: auto; height: 34px; padding: 0 8px; }
.op-fields-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.op-fields-row.payment-supplier-row { grid-template-columns: 120px 1fr; }
.op-fields-row.date-row { grid-template-columns: 1fr 1fr; gap: 8px; }
.op-readonly-field {
    display: flex; align-items: center; gap: 7px;
    width: 100%; border: 1.5px solid var(--gray-200); border-radius: 7px;
    padding: 0 10px; font-size: .78rem; font-family: 'DM Sans', sans-serif;
    color: var(--gray-900); background: var(--gray-50);
    height: 34px; box-sizing: border-box; overflow: hidden;
}
.op-readonly-field i { color: var(--accent); font-size: .75rem; flex-shrink: 0; }
.op-readonly-field span { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.op-date-field {
    display: flex; align-items: center; gap: 6px;
    width: 100%; border: 1.5px solid var(--gray-200); border-radius: 7px;
    padding: 6px 10px; font-size: .77rem; font-family: 'DM Mono', monospace;
    color: var(--gray-900); background: var(--gray-50);
    white-space: nowrap; overflow: hidden; height: 34px;
}
.op-date-field i { color: var(--accent); font-size: .72rem; flex-shrink: 0; }
.op-date-field span { font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.op-date-input {
    width: 100%; border: 1.5px solid var(--gray-200); border-radius: 7px;
    padding: 0 10px; font-size: .77rem; font-family: 'DM Mono', monospace;
    color: var(--gray-900); background: #fff; outline: none;
    transition: border-color .15s; cursor: pointer;
    height: 34px; box-sizing: border-box;
}
.op-date-input:hover { border-color: var(--accent); }
.op-date-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(13,21,120,.08); }
.op-itemlist-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px 6px; flex-shrink: 0;
}
.op-itemlist-header span { font-size: .78rem; font-weight: 700; color: var(--gray-900); }
.op-clear-btn { font-size: .72rem; font-weight: 600; color: var(--accent2); background: none; border: none; cursor: pointer; padding: 0; }
.op-clear-btn:hover { text-decoration: underline; }
.op-items { flex: 1; overflow-y: auto; padding: 4px 16px; }
.op-items::-webkit-scrollbar { width: 3px; }
.op-items::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 4px; }
.oi-row { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--gray-100); }
.oi-row:last-child { border-bottom: none; }
.oi-info { flex: 1; min-width: 0; }
.oi-name { font-size: .8rem; font-weight: 600; color: var(--gray-900); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.oi-price { font-size: .78rem; font-weight: 700; color: var(--gray-900); margin-top: 2px; }
.oi-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.oi-total { font-size: .78rem; font-weight: 700; color: var(--gray-900); white-space: nowrap; }
.qty-ctrl { display: flex; align-items: center; gap: 6px; }
.qty-btn {
    width: 20px; height: 20px; border-radius: 5px;
    border: 1.5px solid var(--gray-200); background: #fff;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: .8rem; color: var(--gray-600);
    transition: all .12s; line-height: 1; font-weight: 700;
}
.qty-btn:hover { border-color: var(--accent); color: var(--accent); }
.qty-val { font-size: .78rem; font-weight: 700; color: var(--gray-900); min-width: 18px; text-align: center; }
.oi-note-btn {
    font-size: .65rem; color: var(--gray-400); background: none;
    border: none; cursor: pointer; padding: 0; display: flex;
    align-items: center; gap: 3px; white-space: nowrap;
}
.oi-note-btn:hover { color: var(--accent); }
.op-empty {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    color: var(--gray-400); font-size: .82rem; gap: 8px;
    text-align: center; padding: 20px;
}
.op-empty i { font-size: 2rem; color: var(--gray-200); }
.op-summary { padding: 12px 16px 16px; border-top: 1px solid var(--gray-100); flex-shrink: 0; background: #fff; }
.sum-row { display: flex; align-items: center; justify-content: space-between; font-size: .78rem; color: var(--gray-600); margin-bottom: 5px; }
.sum-row .sum-val { font-weight: 600; color: var(--gray-900); }
.sum-divider { border: none; border-top: 1.5px solid var(--gray-200); margin: 8px 0; }
.sum-row.total { font-size: .92rem; font-weight: 700; color: var(--gray-900); margin-bottom: 14px; }
.sum-row.total .sum-val { color: var(--accent); }
.btn-proceed {
    width: 100%; padding: 12px; background: var(--accent); border: none; border-radius: 9px;
    color: #fff; font-size: .88rem; font-weight: 700; cursor: pointer;
    font-family: 'DM Sans', sans-serif; transition: background .15s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-proceed:hover { background: #0b1260; }
.btn-proceed:disabled { background: var(--gray-200); color: var(--gray-400); cursor: not-allowed; }

/* ══════════════════════════════════════
   ADMIN VIEW
══════════════════════════════════════ */
.admin-area { padding: 24px; flex: 1; }
.content-card { background: #fff; border-radius: 14px; padding: 22px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 20px; }
.table th { background: #0d1b3e; color: rgba(255,255,255,.65); font-size: .78rem; text-transform: uppercase; letter-spacing: .06em; border: none; padding: 11px 10px; }
.table td { padding: 11px 10px; vertical-align: middle; font-size: .88rem; border-bottom: 1px solid var(--gray-100); }
.table tbody tr:hover td { background: #f0f4ff; }
.badge { padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; }
.pill-pending    { background: #fff8e1; color: #b45309; }
.pill-approved   { background: #eff6ff; color: #1d4ed8; }
.pill-rejected   { background: #fef2f2; color: #b91c1c; }
.pill-processing { background: #ecfdf5; color: #065f46; }
.pill-completed  { background: #f0fdf4; color: #166534; }

/* ══════════════════════════════════════
   PO DETAIL MODAL — Image 2 layout
══════════════════════════════════════ */
.pod-modal-content { border-radius: 16px; border: none; overflow: hidden; }
.pod-modal-header  { background: #fff; border-bottom: 1px solid var(--gray-100); padding: 14px 20px; }
.pod-back-btn {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1.5px solid var(--gray-200); background: #fff;
    display: flex; align-items: center; justify-content: center;
    color: var(--gray-600); cursor: pointer; font-size: .8rem;
    transition: background .15s;
}
.pod-back-btn:hover { background: var(--gray-100); }

/* Three-column layout */
.pod-layout {
    display: grid;
    grid-template-columns: 240px 1fr 230px;
    min-height: 520px;
}
@media (max-width: 900px) {
    .pod-layout { grid-template-columns: 1fr; }
    .pod-right  { border-left: none; border-top: 1px solid var(--gray-100); }
}

/* LEFT panel */
.pod-left {
    padding: 22px 18px;
    border-right: 1px solid var(--gray-100);
    background: #fff;
    overflow-y: auto;
}
.pod-total-label  { font-size: .62rem; font-weight: 700; letter-spacing: .10em; text-transform: uppercase; color: var(--gray-400); margin-bottom: 4px; }
.pod-total-amount { font-size: 1.7rem; font-weight: 800; color: var(--gray-900); margin-bottom: 12px; font-family: 'DM Mono', monospace; }
.pod-payment-chip {
    display: flex; align-items: center; gap: 8px;
    background: var(--gray-50); border: 1.5px solid var(--gray-200);
    border-radius: 9px; padding: 8px 12px; margin-bottom: 14px;
    flex-wrap: wrap;
}
.pod-payment-icon { font-size: .9rem; color: var(--accent); }
.pod-payment-text { font-size: .78rem; font-weight: 600; color: var(--gray-900); flex: 1; }
.pod-divider { border: none; border-top: 1px solid var(--gray-100); margin: 10px 0; }
.pod-detail-label { font-size: .6rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--gray-400); margin-bottom: 8px; }
.pod-detail-row {
    display: flex; justify-content: space-between; align-items: center;
    font-size: .76rem; color: var(--gray-600); margin-bottom: 5px;
}
.pod-detail-row span:last-child { font-weight: 600; color: var(--gray-900); text-align: right; max-width: 55%; }
.pod-detail-row--total { font-size: .84rem; font-weight: 700; color: var(--gray-900); }
.pod-detail-row--total span:last-child { color: var(--accent); font-size: .9rem; }
.pod-mono { font-family: 'DM Mono', monospace; font-size: .72rem !important; }
.pod-actions { display: flex; flex-direction: column; gap: 7px; margin-top: 16px; }
.pod-btn {
    width: 100%; padding: 9px; border-radius: 8px; border: none;
    font-size: .78rem; font-weight: 700; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    font-family: 'DM Sans', sans-serif; transition: filter .15s;
}
.pod-btn:hover { filter: brightness(0.92); }
.pod-btn-approve { background: #ecfdf5; color: #065f46; }
.pod-btn-reject  { background: #fef2f2; color: #b91c1c; }
.pod-btn-print   { background: var(--gray-100); color: var(--gray-600); }

/* CENTER panel */
.pod-center { padding: 22px 20px; background: #fafbfc; overflow-y: auto; }
.pod-section-title { font-size: .8rem; font-weight: 700; color: var(--gray-900); margin-bottom: 12px; }
.pod-item-row {
    display: flex; align-items: center; gap: 12px;
    background: #fff; border-radius: 10px; padding: 10px 12px;
    margin-bottom: 8px; border: 1.5px solid var(--gray-100);
    transition: border-color .15s;
}
.pod-item-row:hover { border-color: var(--gray-200); }
.pod-item-icon { font-size: 1.4rem; flex-shrink: 0; }
.pod-item-info { flex: 1; min-width: 0; }
.pod-item-name { font-size: .82rem; font-weight: 700; color: var(--gray-900); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pod-item-meta { font-size: .72rem; color: var(--gray-400); margin-top: 1px; }
.pod-item-total { font-size: .84rem; font-weight: 700; color: var(--gray-900); flex-shrink: 0; }
.pod-dest-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
.pod-dest-block { background: #fff; border-radius: 9px; padding: 10px 12px; border: 1.5px solid var(--gray-100); }
.pod-dest-label { font-size: .6rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--gray-400); margin-bottom: 4px; }
.pod-dest-val   { font-size: .8rem; font-weight: 700; color: var(--gray-900); }
.pod-notes {
    margin-top: 14px; padding: 10px 14px; background: #fffbeb;
    border-radius: 9px; font-size: .78rem; color: #92400e;
    border: 1.5px solid #fde68a; display: flex; align-items: flex-start; gap: 8px;
}

/* RIGHT panel */
.pod-right { border-left: 1px solid var(--gray-100); background: #fff; display: flex; flex-direction: column; overflow: hidden; }
.pod-supplier-map {
    height: 130px;
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 50%, #93c5fd 100%);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; position: relative; overflow: hidden;
}
.pod-map-inner {
    width: 100%; height: 100%; display: flex; align-items: center;
    justify-content: center; position: relative;
}
.pod-map-grid {
    position: absolute; inset: 0; opacity: .15;
    background-image: linear-gradient(#60a5fa 1px, transparent 1px), linear-gradient(90deg, #60a5fa 1px, transparent 1px);
    background-size: 24px 24px;
}
.pod-map-pin {
    width: 44px; height: 44px; border-radius: 50%;
    background: var(--accent); display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.1rem; z-index: 1;
    box-shadow: 0 4px 12px rgba(13,21,120,.35);
}
.pod-supplier-card { padding: 16px; flex: 1; overflow-y: auto; }
.pod-supplier-name { font-size: .9rem; font-weight: 800; color: var(--gray-900); margin-bottom: 2px; }
.pod-supplier-meta { font-size: .68rem; color: var(--gray-400); }
.pod-supplier-label { font-size: .6rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--gray-400); margin-bottom: 6px; }
.pod-supplier-rating { display: flex; align-items: center; gap: 10px; }
.pod-rating-num { font-size: 1.8rem; font-weight: 800; color: var(--gray-900); font-family: 'DM Mono', monospace; line-height: 1; }
.pod-stars { font-size: 1rem; color: #f59e0b; letter-spacing: 1px; }

/* Status badge variants for pod modal */
.pod-pill-pending    { background: #fff8e1; color: #b45309; border-radius: 20px; padding: 2px 10px; font-size: .7rem; font-weight: 700; white-space: nowrap; }
.pod-pill-approved   { background: #eff6ff; color: #1d4ed8; border-radius: 20px; padding: 2px 10px; font-size: .7rem; font-weight: 700; white-space: nowrap; }
.pod-pill-rejected   { background: #fef2f2; color: #b91c1c; border-radius: 20px; padding: 2px 10px; font-size: .7rem; font-weight: 700; white-space: nowrap; }
.pod-pill-processing { background: #ecfdf5; color: #065f46; border-radius: 20px; padding: 2px 10px; font-size: .7rem; font-weight: 700; white-space: nowrap; }
.pod-pill-completed  { background: #f0fdf4; color: #166534; border-radius: 20px; padding: 2px 10px; font-size: .7rem; font-weight: 700; white-space: nowrap; }

/* Edit PO modal */
.modal-content  { border-radius: 14px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,.12); }
.modal-header   { border-bottom: 1px solid #f0f0f0; padding: 18px 24px; }
.modal-footer   { border-top: 1px solid #f0f0f0; padding: 14px 24px; }
.form-control, .form-select { border-radius: 9px; border: 1.5px solid var(--gray-200); padding: 9px 13px; font-size: .88rem; font-family: 'DM Sans', sans-serif; }
.form-control:focus, .form-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(13,21,120,.08); }

/* Toast */
.toast-wrap {
    position: fixed; top: 20px; right: 20px; z-index: 99999;
    display: flex; flex-direction: column; gap: 8px;
}
.toast-msg {
    background: #fff; border-radius: 10px; padding: 12px 18px;
    box-shadow: 0 4px 20px rgba(0,0,0,.13);
    display: flex; align-items: center; gap: 10px;
    font-size: .85rem; font-weight: 500; color: var(--gray-900);
    animation: slideIn .25s ease;
    border-left: 4px solid var(--accent); min-width: 260px;
}
.toast-msg.error { border-left-color: #ef4444; }
.toast-msg i { font-size: 1rem; }
.toast-msg.success i { color: var(--accent); }
.toast-msg.error   i { color: #ef4444; }
@keyframes slideIn { from { transform: translateX(40px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

/* Mobile */
@media (max-width: 991.98px) {
    .sidebar { width: min(var(--sidebar-w),86vw); transform: translateX(-100%); transition: transform .3s ease; box-shadow: 0 12px 28px rgba(0,0,0,.25); }
    body.sidebar-open .sidebar { transform: translateX(0); }
    .main-content { margin-left: 0; }
    .mobile-sidebar-toggle { display: inline-flex; }
    .mobile-sidebar-backdrop { display: block; position: fixed; inset: 0; background: rgba(9,15,85,.45); opacity: 0; pointer-events: none; transition: opacity .3s ease; z-index: 9998; }
    body.sidebar-open .mobile-sidebar-backdrop { opacity: 1; pointer-events: auto; }
    .order-panel { width: 260px; }
    .product-grid { grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 640px) {
    .pos-layout { flex-direction: column; height: auto; }
    .order-panel { width: 100%; border-left: none; border-top: 1px solid var(--gray-200); }
    .product-grid { grid-template-columns: repeat(2,1fr); }
}
</style>
</head>
<body>
<div class="toast-wrap" id="toastWrap"></div>

<!-- ── SIDEBAR ── -->
<nav id="appSidebar" class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo-ring"><img src="logo.png" alt="McPIL"></div>
        <div class="sidebar-brand-text">
            <div class="sidebar-brand-name">McPIL</div>
            <div class="sidebar-brand-sub">Pharmaceutical Laboratory</div>
        </div>
    </div>
    <div class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a class="sidebar-link" href="dashboard.php"><span class="icon"><i class="fas fa-tachometer-alt"></i></span><span class="link-label">Dashboard</span></a>

        <?php if (is_admin()): ?>
        <a class="sidebar-link active" href="purchase_order.php"><span class="icon"><i class="fas fa-shopping-cart"></i></span><span class="link-label">Purchase Order</span></a>
        <a class="sidebar-link" href="purchase_invoice.php"><span class="icon"><i class="fas fa-file-invoice"></i></span><span class="link-label">Purchase Invoice</span></a>
        <a class="sidebar-link" href="employee_profile.php"><span class="icon"><i class="fas fa-users"></i></span><span class="link-label">Employee Profile</span></a>
        <a class="sidebar-link" href="attendance.php"><span class="icon"><i class="fas fa-clock"></i></span><span class="link-label">Attendance</span></a>
        <?php endif; ?>

        <?php if (is_store()): ?>
        <a class="sidebar-link" href="inventory.php"><span class="icon"><i class="fas fa-boxes"></i></span><span class="link-label">Inventory Management</span></a>
        <a class="sidebar-link active" href="purchase_order.php"><span class="icon"><i class="fas fa-shopping-cart"></i></span><span class="link-label">Purchase Order</span></a>
        <a class="sidebar-link" href="purchase_invoice.php"><span class="icon"><i class="fas fa-file-invoice"></i></span><span class="link-label">Purchase Invoice</span></a>
        <?php endif; ?>

        <div class="nav-section-label">Logistics</div>
        <a class="sidebar-link" href="delivery_tracking.php"><span class="icon"><i class="fas fa-truck"></i></span><span class="link-label">Delivery Tracking</span></a>
        <a class="sidebar-link" href="delivery_history.php"><span class="icon"><i class="fas fa-history"></i></span><span class="link-label">Delivery History</span></a>

        <div class="nav-section-label">Tools</div>
        <a class="sidebar-link" href="reports.php"><span class="icon"><i class="fas fa-chart-bar"></i></span><span class="link-label">Reports</span></a>
        <a class="sidebar-link" href="chat_interface.php">
            <span class="icon"><i class="fas fa-comments"></i></span>
            <span class="link-label">Chat</span>
            <?php if ($unread_messages > 0): ?><span class="badge-dot"><?php echo $unread_messages; ?></span><?php endif; ?>
        </a>
    </div>
    <div class="sidebar-footer">
        <div class="nav-section-label" style="padding-top:6px">Account</div>
        <a class="sidebar-link" href="settings.php"><span class="icon"><i class="fas fa-cog"></i></span><span class="link-label">Settings</span></a>
        <a class="sidebar-link logout" href="logout.php"><span class="icon"><i class="fas fa-sign-out-alt"></i></span><span class="link-label">Logout</span></a>
    </div>
</nav>
<div class="mobile-sidebar-backdrop" id="mobileSidebarBackdrop"></div>

<!-- ── MAIN ── -->
<div class="main-content">

    <!-- Top bar -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="mobile-sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">
                    <?php if ($is_store): ?>Purchase Order<?php else: ?>Purchase Order Management<?php endif; ?>
                </div>
                <div class="topbar-sub">
                    <?php if ($is_store): ?>Select products and submit an order<?php else: ?>Create and manage purchase orders<?php endif; ?>
                </div>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-icon-btn" title="Notifications">
                <i class="fas fa-bell" style="font-size:.85rem"></i>
                <?php if ($unread_messages > 0): ?><span class="badge-dot"><?php echo $unread_messages; ?></span><?php endif; ?>
            </div>
            <div class="user-chip">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'],0,2)); ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                    <div class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($is_store): ?>
    <!-- ══════════════════════════════════════
         STORE VIEW — POS / Cashier Layout
    ══════════════════════════════════════ -->
    <div class="pos-layout">

        <!-- ── Left: Catalog ── -->
        <div class="pos-catalog">

            <div class="pos-topbar">
                <div class="pos-topbar-left">
                    <div class="pos-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="productSearch" placeholder="Search Product..." oninput="filterProducts()">
                    </div>
                </div>
                <div class="pos-notif-btn" title="Notifications">
                    <i class="fas fa-bell" style="font-size:.85rem;color:var(--gray-600)"></i>
                </div>
            </div>

            <div class="cat-pills" id="catPills">
                <button class="cat-pill active" data-cat="all" onclick="filterCat(this,'all')">All Categories</button>
                <?php
                $supply_cats = [];
                foreach ($suppliers as $s) {
                    if (!empty($s['category'])) $supply_cats[] = $s['category'];
                }
                $supply_cats = array_unique($supply_cats);
                $ui_cats = !empty($supply_cats) ? $supply_cats : ['Acetone', 'Potassium Alum', 'Mcson Scent', 'Aceite de Manzanilla', 'Nail Polish Solvent'];
                foreach ($ui_cats as $cat): ?>
                <button class="cat-pill" data-cat="<?php echo htmlspecialchars($cat); ?>" onclick="filterCat(this,'<?php echo htmlspecialchars($cat); ?>')"><?php echo htmlspecialchars($cat); ?></button>
                <?php endforeach; ?>
            </div>

            <div class="product-grid" id="productGrid">
                <?php
                $icons  = ['💊','🧪','🔬','🧬','🩺','💉','🧫','🩹','🔭','⚗️','🧲','📋'];
                $colors = ['#f0f4ff','#fff7ed','#f0fdf4','#fef2f2','#f5f3ff','#fffbeb','#ecfeff','#fdf4ff'];
                $i = 0;
                foreach ($suppliers as $supplier):
                    $icon  = $icons[$i % count($icons)];
                    $color = $colors[$i % count($colors)];
                    $price = rand(10, 150) + 0.99;
                    $cat   = $supplier['category'] ?? $ui_cats[$i % count($ui_cats)];
                    $i++;
                ?>
                <div class="product-card"
                     id="pcard-<?php echo $supplier['id']; ?>"
                     data-id="<?php echo $supplier['id']; ?>"
                     data-name="<?php echo htmlspecialchars($supplier['name']); ?>"
                     data-cat="<?php echo htmlspecialchars($cat); ?>"
                     data-price="<?php echo $price; ?>"
                     data-code="<?php echo htmlspecialchars($supplier['supplier_code']); ?>"
                     data-icon="<?php echo $icon; ?>"
                     data-color="<?php echo $color; ?>"
                     onclick="toggleCart(<?php echo $supplier['id']; ?>)">

                    <div class="pc-selected-badge"><i class="fas fa-check"></i></div>
                    <div class="pc-img" style="background:<?php echo $color; ?>">
                        <span><?php echo $icon; ?></span>
                    </div>
                    <div class="pc-body">
                        <div class="pc-name"><?php echo htmlspecialchars($supplier['name']); ?></div>
                        <div class="pc-footer">
                            <div class="pc-price">₱<?php echo number_format($price, 2); ?></div>
                            <button class="pc-btn" id="btn-<?php echo $supplier['id']; ?>"
                                    onclick="event.stopPropagation();toggleCart(<?php echo $supplier['id']; ?>)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
        <!-- /pos-catalog -->

        <!-- ── Right: Order Detail Panel ── -->
        <div class="order-panel">

            <div class="op-header">
                <h3>Detail Orders</h3>

                <div class="op-field">
                    <label>Store</label>
                    <div class="op-readonly-field">
                        <i class="fas fa-store"></i>
                        <span><?php echo htmlspecialchars($_SESSION['store_name'] ?? 'Lots for Less Branch-Ma-a'); ?></span>
                    </div>
                </div>

                <div class="op-field">
                    <label>P.O Number</label>
                    <input type="text" id="P.ONumberName" placeholder="P.O Number">
                </div>

                <div class="op-field">
                    <label>Location</label>
                    <input type="text" id="locationField" placeholder="Location">
                </div>

                <div class="op-fields-row payment-supplier-row">
                    <div class="op-field">
                        <label>Payment Type</label>
                        <select id="orderType">
                            <option>Cheque</option>
                            <option>Cash</option>
                            <option>Bank Transfer</option>
                            <option>Credit</option>
                        </select>
                    </div>
                    <div class="op-field">
                        <label>Supplier</label>
                        <div class="op-readonly-field" title="McPIL Pharmaceutical Laboratory">
                            <i class="fas fa-pills"></i>
                            <span style="font-size:.74rem">McPIL Pharmaceutical Laboratory</span>
                        </div>
                        <select id="cartSupplier" style="display:none">
                            <option value="">— Select —</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="op-fields-row date-row">
                    <div class="op-field">
                        <label>Order Date</label>
                        <div class="op-date-field">
                            <i class="fas fa-calendar-alt"></i>
                            <span id="orderDateDisplay"></span>
                        </div>
                    </div>
                    <div class="op-field">
                        <label>Expected Delivery</label>
                        <input type="date" id="expectedDeliveryInput" class="op-date-input">
                    </div>
                </div>

            </div>

            <div class="op-itemlist-header">
                <span>Item List</span>
                <button class="op-clear-btn" onclick="clearCart()">Clear</button>
            </div>

            <div class="op-items" id="opItems">
                <div class="op-empty" id="opEmpty">
                    <i class="fas fa-clipboard-list"></i>
                    <span>No items yet</span>
                    <span style="font-size:.73rem">Click products to add them</span>
                </div>
            </div>

            <div class="op-summary">
                <div class="sum-row"><span>Summary</span></div>
                <hr class="sum-divider">
                <div class="sum-row"><span>Subtotal</span><span class="sum-val" id="sumSubtotal">₱0.00</span></div>
                <div class="sum-row"><span>Tax (11%)</span><span class="sum-val" id="sumTax">₱0.00</span></div>
                <div class="sum-row"><span>Discount</span><span class="sum-val">₱0.00</span></div>
                <div class="sum-row"><span>Delivery Fee</span><span class="sum-val">₱0.00</span></div>
                <hr class="sum-divider">
                <div class="sum-row total">
                    <span>Total</span>
                    <span class="sum-val" id="sumTotal">₱0.00</span>
                </div>
                <button class="btn-proceed" id="btnPlaceOrder" onclick="placeOrder()" disabled>
                    Proceed to Payment &nbsp;<i class="fas fa-arrow-right"></i>
                </button>
            </div>

        </div>
        <!-- /order-panel -->

    </div>

    <?php else: ?>
    <!-- ══════════════════════════════════════
         ADMIN VIEW — standard table
    ══════════════════════════════════════ -->
    <div class="admin-area">

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

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0"><i class="fas fa-list text-primary"></i> Purchase Orders
                    <small class="text-muted ms-2" style="font-size:.75rem"><i class="fas fa-sync-alt fa-spin"></i> Auto-refresh every 30s</small>
                </h5>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" style="width:auto" onchange="window.location.href='?status='+this.value">
                        <option value="">All Status</option>
                        <?php foreach(['Pending','Approved','Rejected','Processing','Completed'] as $st): ?>
                        <option value="<?php echo $st; ?>" <?php echo (isset($_GET['status']) && $_GET['status']===$st)?'selected':''; ?>><?php echo $st; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary" onclick="location.reload()"><i class="fas fa-sync"></i> Refresh</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>PO Number</th><th>Supplier</th><th>Order Date</th><th>Total Amount</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchase_orders as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['po_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                            <td><?php echo format_date($order['order_date']); ?></td>
                            <td><?php echo format_currency($order['total_amount']); ?></td>
                            <td>
                                <?php $sc = strtolower($order['status']); ?>
                                <span class="badge pill-<?php echo $sc; ?>"><?php echo $order['status']; ?></span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-info"      onclick="viewPO(<?php echo $order['id']; ?>)"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-outline-primary"   onclick="editPO(<?php echo $order['id']; ?>)"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-warning"   onclick="viewMessages(<?php echo $order['id']; ?>)"><i class="fas fa-comment"></i></button>
                                <button class="btn btn-sm btn-outline-danger"    onclick="deletePO(<?php echo $order['id']; ?>)"><i class="fas fa-trash"></i></button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="archivePO(<?php echo $order['id']; ?>)"><i class="fas fa-archive"></i></button>
                                <?php if ($order['status'] === 'Pending'): ?>
                                <button class="btn btn-sm btn-outline-success" onclick="approvePO(<?php echo $order['id']; ?>)"><i class="fas fa-check"></i></button>
                                <button class="btn btn-sm btn-outline-danger"  onclick="rejectPO(<?php echo $order['id']; ?>)"><i class="fas fa-times"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /main-content -->

<?php include 'mcbot_widget.php'; ?>

<!-- ══════════════════════════════════════
     MODALS
══════════════════════════════════════ -->

<!-- PO Detail / View Modal (Image 2 layout) -->
<div class="modal fade" id="poModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content pod-modal-content">
            <div class="modal-header pod-modal-header">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="pod-back-btn" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h5 class="modal-title mb-0" id="poModalTitle">Order</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="poModalBody"></div>
        </div>
    </div>
</div>

<!-- Edit PO Modal -->
<div class="modal fade" id="editPoModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit Purchase Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="editPoModalBody"></div>
    </div></div>
</div>

<!-- Messages Modal -->
<div class="modal fade" id="messagesModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Purchase Order Messages</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="messagesModalBody"></div>
    </div></div>
</div>

<!-- Confirm Order Modal (Store) -->
<div class="modal fade" id="confirmOrderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Confirm Purchase Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="confirmOrderBody"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitOrder()">Submit Order</button>
        </div>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Sidebar toggle ──
(function(){
    const body=document.body,toggle=document.getElementById('sidebarToggle'),backdrop=document.getElementById('mobileSidebarBackdrop');
    function close(){ if(window.innerWidth>=992) body.classList.remove('sidebar-open'); }
    if(toggle) toggle.addEventListener('click',()=>body.classList.toggle('sidebar-open'));
    if(backdrop) backdrop.addEventListener('click',()=>body.classList.remove('sidebar-open'));
    window.addEventListener('resize',close); close();
})();

function toast(msg,type='success'){
    const w=document.getElementById('toastWrap');
    const d=document.createElement('div');
    d.className=`toast-msg ${type}`;
    d.innerHTML=`<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i><span>${msg}</span>`;
    w.appendChild(d); setTimeout(()=>d.remove(),3500);
}

<?php if ($is_store): ?>
/* ════════════════════════════════════
   STORE / POS LOGIC
════════════════════════════════════ */
let cart = {};
const TAX = 0.11;

(function(){
    const now = new Date();
    const pad = n => String(n).padStart(2,'0');
    const orderStr = pad(now.getMonth()+1)+'/'+pad(now.getDate())+'/'+now.getFullYear();
    document.getElementById('orderDateDisplay').textContent = orderStr;
    window._orderDate = now.toISOString().slice(0,10);

    const delivery = new Date(now);
    delivery.setDate(delivery.getDate() + 7);
    const deliveryInput = document.getElementById('expectedDeliveryInput');
    deliveryInput.value = delivery.toISOString().slice(0,10);
    deliveryInput.min   = now.toISOString().slice(0,10);

    const supplierSel = document.getElementById('cartSupplier');
    for (let i = 0; i < supplierSel.options.length; i++) {
        if (supplierSel.options[i].text.toLowerCase().includes('mcpil') ||
            supplierSel.options[i].text.toLowerCase().includes('pharmaceutical laboratory')) {
            supplierSel.selectedIndex = i; break;
        }
    }
    if (!supplierSel.value && supplierSel.options.length > 1) supplierSel.selectedIndex = 1;
})();

function toggleCart(id){
    const card=document.getElementById('pcard-'+id);
    const btn=document.getElementById('btn-'+id);
    const d=card.dataset;
    if(cart[id]){
        delete cart[id];
        card.classList.remove('in-cart');
        btn.innerHTML='<i class="fas fa-plus"></i>';
    } else {
        cart[id]={id,name:d.name,cat:d.cat,price:parseFloat(d.price),icon:d.icon,color:d.color,code:d.code,qty:1};
        card.classList.add('in-cart');
        btn.innerHTML='<i class="fas fa-check"></i>';
    }
    renderCart();
}

function renderCart(){
    const items=Object.values(cart);
    const el=document.getElementById('opItems');
    const empty=document.getElementById('opEmpty');
    const btn=document.getElementById('btnPlaceOrder');
    if(items.length===0){
        el.innerHTML=''; el.appendChild(empty); empty.style.display='flex';
        updateTotals(0); btn.disabled=true; return;
    }
    empty.style.display='none'; el.innerHTML='';
    let sub=0;
    items.forEach(item=>{
        sub+=item.price*item.qty;
        const row=document.createElement('div');
        row.className='oi-row';
        row.innerHTML=`
        <div class="oi-info">
            <div class="oi-name">${item.name}</div>
            <div class="oi-price">₱${item.price.toFixed(2)}</div>
            <div class="qty-ctrl" style="margin-top:5px">
                <button class="qty-btn" onclick="changeQty(${item.id},-1)">−</button>
                <span class="qty-val" id="qty-${item.id}">${item.qty}</span>
                <button class="qty-btn" onclick="changeQty(${item.id},1)">+</button>
            </div>
        </div>
        <div class="oi-right">
            <div class="oi-total" id="oi-total-${item.id}">₱${(item.price*item.qty).toFixed(2)}</div>
            <button class="oi-note-btn" onclick="removeFromCart(${item.id})"><i class="fas fa-times"></i> Remove</button>
            <button class="oi-note-btn"><i class="fas fa-sticky-note"></i> Add Note</button>
        </div>`;
        el.appendChild(row);
    });
    updateTotals(sub);
    btn.disabled=items.length===0;
}

function changeQty(id,delta){
    if(!cart[id]) return;
    cart[id].qty=Math.max(1,cart[id].qty+delta);
    document.getElementById('qty-'+id).textContent=cart[id].qty;
    const t=document.getElementById('oi-total-'+id);
    if(t) t.textContent='₱'+(cart[id].price*cart[id].qty).toFixed(2);
    let sub=0; Object.values(cart).forEach(i=>sub+=i.price*i.qty);
    updateTotals(sub);
}

function removeFromCart(id){
    if(!cart[id]) return;
    delete cart[id];
    const card=document.getElementById('pcard-'+id);
    const btn=document.getElementById('btn-'+id);
    if(card) card.classList.remove('in-cart');
    if(btn) btn.innerHTML='<i class="fas fa-plus"></i>';
    renderCart();
}

function clearCart(){
    Object.keys(cart).forEach(id=>{
        const card=document.getElementById('pcard-'+id);
        const btn=document.getElementById('btn-'+id);
        if(card) card.classList.remove('in-cart');
        if(btn) btn.innerHTML='<i class="fas fa-plus"></i>';
    });
    cart={}; renderCart();
}

function updateTotals(sub){
    const tax=sub*TAX, total=sub+tax;
    document.getElementById('sumSubtotal').textContent='₱'+sub.toFixed(2);
    document.getElementById('sumTax').textContent='₱'+tax.toFixed(2);
    document.getElementById('sumTotal').textContent='₱'+total.toFixed(2);
}

function filterProducts(){
    const q=document.getElementById('productSearch').value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(c=>{
        c.style.display=(c.dataset.name||'').toLowerCase().includes(q)?'':'none';
    });
}

let activecat='all';
function filterCat(btn,cat){
    activecat=cat;
    document.querySelectorAll('.cat-pill').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.product-card').forEach(c=>{
        c.style.display=(cat==='all'||(c.dataset.cat||'').toLowerCase()===cat.toLowerCase())?'':'none';
    });
}

function placeOrder(){
    const supplier=document.getElementById('cartSupplier').value;
    if(!supplier){ toast('Please select a supplier','error'); return; }
    const items=Object.values(cart);
    if(!items.length){ toast('Add items first','error'); return; }
    let sub=items.reduce((a,i)=>a+i.price*i.qty,0), tax=sub*TAX;
    let rows=items.map(i=>`<tr><td>${i.name}</td><td>${i.qty}</td><td>₱${i.price.toFixed(2)}</td><td>₱${(i.price*i.qty).toFixed(2)}</td></tr>`).join('');
    document.getElementById('confirmOrderBody').innerHTML=`
        <p style="font-size:.85rem;color:var(--gray-600);margin-bottom:12px">Review your order before submitting.</p>
        <table class="table table-sm"><thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead><tbody>${rows}</tbody></table>
        <div style="text-align:right;font-weight:700;margin-top:8px">Total: ₱${(sub+tax).toFixed(2)} <span style="font-size:.75rem;font-weight:400;color:var(--gray-400)">(incl. 11% tax)</span></div>`;
    new bootstrap.Modal(document.getElementById('confirmOrderModal')).show();
}

function submitOrder(){
    const supplier=document.getElementById('cartSupplier').value;
    const items=Object.values(cart);
    const poNum='PO-'+Date.now();
    const today=new Date().toISOString().slice(0,10);
    const fd=new FormData();
    fd.append('create_po','1'); fd.append('po_number',poNum);
    fd.append('supplier_id',supplier); fd.append('order_date', window._orderDate || today);
    fd.append('expected_delivery_date', document.getElementById('expectedDeliveryInput').value || '');
    fd.append('notes','Order from store catalog');
    items.forEach((item,i)=>{
        fd.append(`items[item_name][${i}]`,item.name);
        fd.append(`items[quantity][${i}]`,item.qty);
        fd.append(`items[unit_price][${i}]`,item.price);
    });
    fetch('',{method:'POST',body:fd})
        .then(()=>{ bootstrap.Modal.getInstance(document.getElementById('confirmOrderModal')).hide(); clearCart(); toast(`Order ${poNum} placed!`); })
        .catch(()=>toast('Failed to place order','error'));
}

<?php else: ?>
/* ════════════════════════════════════
   ADMIN JS
════════════════════════════════════ */

// ── Helpers ──
function fmt(n){ return new Intl.NumberFormat('en-PH',{style:'currency',currency:'PHP'}).format(n); }
function fmtDate(d){ return d ? new Date(d).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'2-digit'}) : '—'; }
function statusClass(s){ return ({Pending:'pod-pill-pending',Approved:'pod-pill-approved',Rejected:'pod-pill-rejected',Processing:'pod-pill-processing',Completed:'pod-pill-completed'})[s]||''; }

// ── View PO — Image 2 three-column modal ──
function viewPO(id){
    document.getElementById('poModalTitle').textContent = 'Order';
    // Show loading state
    document.getElementById('poModalBody').innerHTML = `
        <div style="display:flex;align-items:center;justify-content:center;padding:60px;color:var(--gray-400)">
            <i class="fas fa-spinner fa-spin" style="font-size:1.5rem;margin-right:10px"></i> Loading order details…
        </div>`;
    const modal = new bootstrap.Modal(document.getElementById('poModal'));
    modal.show();

    fetch('get_po_details_api.php?id='+id)
        .then(r=>r.json())
        .then(data=>{
            if(data.error){ toast(data.error,'error'); return; }
            const po = data.po;

            document.getElementById('poModalTitle').textContent = po.po_number || 'Order';

            // ── Build items list ──
            let itemRows = `<p style="color:var(--gray-400);font-size:.82rem;padding:12px 0">No items on this order.</p>`;
            let sub = 0;
            const itemIcons = ['💊','🧪','🔬','🧬','🩺','💉','🧫','🩹','⚗️','📋'];
            if(po.items && po.items.length){
                itemRows = po.items.map((item,idx)=>{
                    const lineTotal = parseFloat(item.quantity) * parseFloat(item.unit_price);
                    sub += lineTotal;
                    return `
                    <div class="pod-item-row">
                        <div class="pod-item-icon">${itemIcons[idx % itemIcons.length]}</div>
                        <div class="pod-item-info">
                            <div class="pod-item-name">${item.item_name}</div>
                            <div class="pod-item-meta">Qty: ${item.quantity} &nbsp;×&nbsp; ₱${parseFloat(item.unit_price).toFixed(2)}</div>
                        </div>
                        <div class="pod-item-total">₱${lineTotal.toFixed(2)}</div>
                    </div>`;
                }).join('');
            }

            const tax   = sub * 0.11;
            const total = sub + tax;
            const sc    = statusClass(po.status);
            const rating = parseFloat(po.supplier_rating || 4.8);
            const stars  = '★'.repeat(Math.round(rating)) + '☆'.repeat(5 - Math.round(rating));

            document.getElementById('poModalBody').innerHTML = `
            <div class="pod-layout">

                <!-- ── LEFT: Summary ── -->
                <div class="pod-left">
                    <div class="pod-total-label">TOTAL ORDER</div>
                    <div class="pod-total-amount">₱${total.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>

                    <div class="pod-payment-chip">
                        <span class="pod-payment-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                        <span class="pod-payment-text">${po.payment_type || 'Purchase Order'}</span>
                        <span class="${sc}">${po.status}</span>
                    </div>
                    <div style="font-size:.68rem;color:var(--gray-400);margin-bottom:14px;line-height:1.5">
                        This order was authorized for processing based on ${(po.items||[]).length} line item${(po.items||[]).length!==1?'s':''}.
                    </div>

                    <div class="pod-divider"></div>
                    <div class="pod-detail-label">Order Details</div>

                    <div class="pod-detail-row"><span>Subtotal</span><span>₱${sub.toFixed(2)}</span></div>
                    <div class="pod-detail-row"><span>Tax (11%)</span><span>₱${tax.toFixed(2)}</span></div>
                    <div class="pod-detail-row"><span>Discount</span><span>₱0.00</span></div>
                    <div class="pod-detail-row"><span>Delivery Fee</span><span>₱0.00</span></div>
                    <div class="pod-divider"></div>
                    <div class="pod-detail-row pod-detail-row--total"><span>Total</span><span>₱${total.toFixed(2)}</span></div>

                    <div class="pod-divider"></div>
                    <div class="pod-detail-row"><span>PO Number</span><span class="pod-mono">${po.po_number}</span></div>
                    <div class="pod-detail-row"><span>Order Date</span><span>${fmtDate(po.order_date)}</span></div>
                    <div class="pod-detail-row"><span>Expected</span><span>${fmtDate(po.expected_delivery_date)}</span></div>
                    <div class="pod-detail-row"><span>Issued by</span><span>${po.created_by_name || '—'}</span></div>

                    <div class="pod-actions">
                        ${po.status === 'Pending' ? `
                        <button class="pod-btn pod-btn-approve" onclick="approvePO(${po.id});bootstrap.Modal.getInstance(document.getElementById('poModal')).hide()">
                            <i class="fas fa-check"></i> Approve Order
                        </button>
                        <button class="pod-btn pod-btn-reject" onclick="rejectPO(${po.id});bootstrap.Modal.getInstance(document.getElementById('poModal')).hide()">
                            <i class="fas fa-times"></i> Reject Order
                        </button>` : ''}
                        <button class="pod-btn pod-btn-print" onclick="window.open('print_po.php?id=${po.id}','_blank')">
                            <i class="fas fa-print"></i> Print Order
                        </button>
                    </div>
                </div>

                <!-- ── CENTER: Item details + destination ── -->
                <div class="pod-center">
                    <div class="pod-section-title">Order Details</div>
                    <div class="pod-items-list">${itemRows}</div>

                    <div class="pod-divider" style="margin:16px 0"></div>
                    <div class="pod-section-title">Destination</div>

                    <div class="pod-dest-grid">
                        <div class="pod-dest-block">
                            <div class="pod-dest-label">Supplier</div>
                            <div class="pod-dest-val" style="font-size:.75rem;white-space:normal">${po.supplier_name || '—'}</div>
                        </div>
                        <div class="pod-dest-block">
                            <div class="pod-dest-label">Status</div>
                            <div class="pod-dest-val"><span class="${sc}">${po.status}</span></div>
                        </div>
                        <div class="pod-dest-block">
                            <div class="pod-dest-label">Items Count</div>
                            <div class="pod-dest-val">${(po.items||[]).length} item${(po.items||[]).length!==1?'s':''}</div>
                        </div>
                    </div>

                    ${po.notes ? `
                    <div class="pod-notes">
                        <i class="fas fa-sticky-note" style="flex-shrink:0;margin-top:1px"></i>
                        <span>${po.notes}</span>
                    </div>` : ''}
                </div>

                <!-- ── RIGHT: Supplier card ── -->
                <div class="pod-right">
                    <div class="pod-supplier-map">
                        <div class="pod-map-inner">
                            <div class="pod-map-grid"></div>
                            <div class="pod-map-pin"><i class="fas fa-map-marker-alt"></i></div>
                        </div>
                    </div>
                    <div class="pod-supplier-card">
                        <div class="pod-supplier-name">${po.supplier_name || '—'}</div>
                        <div class="pod-supplier-meta">${po.supplier_address || 'Davao Region, PH'}</div>

                        <div class="pod-divider" style="margin:10px 0"></div>
                        <div class="pod-detail-row"><span>Total Sales</span><span>${fmt(po.supplier_total_sales || total)}</span></div>
                        <div class="pod-detail-row"><span>Date Joined</span><span>${fmtDate(po.supplier_created_at) || '—'}</span></div>

                        <div class="pod-divider" style="margin:10px 0"></div>
                        <div class="pod-supplier-label">Rating</div>
                        <div class="pod-supplier-rating">
                            <span class="pod-rating-num">${rating.toFixed(1)}</span>
                            <div>
                                <div class="pod-stars">${stars}</div>
                                <div class="pod-supplier-meta">Based on reviews</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>`;
        })
        .catch(()=>toast('Error loading order details','error'));
}

// ── Edit PO ──
const _suppliers=<?php echo json_encode($suppliers); ?>;
function editPO(id){
    fetch('get_po_details_api.php?id='+id).then(r=>r.json()).then(data=>{
        if(data.error){ toast(data.error,'error'); return; }
        const modal=new bootstrap.Modal(document.getElementById('editPoModal'));
        const body=document.getElementById('editPoModalBody');
        let items='';
        (data.po.items||[]).forEach(i=>{
            items+=`<div class="item-row mb-2"><div class="row">
                <div class="col-md-4"><input type="text" class="form-control" name="items[item_name][]" value="${i.item_name}" required></div>
                <div class="col-md-3"><input type="number" class="form-control" name="items[quantity][]" value="${i.quantity}" min="1" required></div>
                <div class="col-md-3"><input type="number" class="form-control" name="items[unit_price][]" value="${i.unit_price}" step=".01" min="0" required></div>
                <div class="col-md-2"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.item-row').remove()"><i class="fas fa-trash"></i></button></div>
            </div></div>`;
        });
        let opts='<option value="">Select Supplier</option>';
        _suppliers.forEach(s=>{ opts+=`<option value="${s.id}" ${s.id==data.po.supplier_id?'selected':''}>${s.name}</option>`; });
        body.innerHTML=`<form onsubmit="updatePO(event,${data.po.id})">
            <div class="row g-2 mb-3">
                <div class="col-md-4"><label class="form-label">PO Number</label><input type="text" class="form-control" name="po_number" value="${data.po.po_number}" required></div>
                <div class="col-md-4"><label class="form-label">Supplier</label><select class="form-select" name="supplier_id" required>${opts}</select></div>
                <div class="col-md-2"><label class="form-label">Order Date</label><input type="date" class="form-control" name="order_date" value="${data.po.order_date}" required></div>
                <div class="col-md-2"><label class="form-label">Expected</label><input type="date" class="form-control" name="expected_delivery_date" value="${data.po.expected_delivery_date||''}"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Items</label>
                <div id="editItemsCon">${items}</div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addEI()"><i class="fas fa-plus"></i> Add Item</button>
            </div>
            <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2">${data.po.notes||''}</textarea></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update PO</button>
            <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
        </form>`;
        modal.show();
    }).catch(()=>toast('Error loading details','error'));
}
function addEI(){
    const c=document.getElementById('editItemsCon'),d=document.createElement('div');
    d.className='item-row mb-2';
    d.innerHTML=`<div class="row">
        <div class="col-md-4"><input type="text" class="form-control" name="items[item_name][]" placeholder="Item Name" required></div>
        <div class="col-md-3"><input type="number" class="form-control" name="items[quantity][]" placeholder="Qty" min="1" required></div>
        <div class="col-md-3"><input type="number" class="form-control" name="items[unit_price][]" placeholder="Unit Price" step=".01" min="0" required></div>
        <div class="col-md-2"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.item-row').remove()"><i class="fas fa-trash"></i></button></div>
    </div>`;
    c.appendChild(d);
}
function updatePO(event,poId){
    event.preventDefault();
    const fd=new FormData(event.target);
    fd.append('edit_po','1'); fd.append('po_id',poId);
    fetch('',{method:'POST',body:fd})
        .then(()=>{ toast('PO updated'); setTimeout(()=>location.reload(),1500); })
        .catch(()=>toast('Error','error'));
}

// ── Messages ──
function viewMessages(id){
    fetch('get_po_details_api.php?id='+id).then(r=>r.json()).then(data=>{
        if(data.error){ toast(data.error,'error'); return; }
        const modal=new bootstrap.Modal(document.getElementById('messagesModal'));
        let msgs='<p class="text-muted">No messages yet.</p>';
        if(data.messages && data.messages.length){
            msgs='<div class="mb-3">'+data.messages.map(m=>`
            <div class="mb-2 p-2 border rounded">
                <div class="d-flex justify-content-between">
                    <strong class="${m.message_type==='admin'?'text-primary':'text-success'}">${m.full_name}</strong>
                    <small>${new Date(m.created_at).toLocaleString()}</small>
                </div>
                <div class="mt-1">${m.message}</div>
            </div>`).join('')+'</div>';
        }
        document.getElementById('messagesModalBody').innerHTML=`
            <h6>PO: ${data.po.po_number}</h6>
            ${msgs}
            <form onsubmit="sendMsg(event,${data.po.id})">
                <textarea class="form-control mb-2" name="message" rows="3" placeholder="Type a message…" required></textarea>
                <button class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> Send</button>
            </form>`;
        modal.show();
    }).catch(()=>toast('Error loading messages','error'));
}
function sendMsg(event,poId){
    event.preventDefault();
    const fd=new FormData(event.target);
    fd.append('send_message','1'); fd.append('po_id',poId);
    fetch('',{method:'POST',body:fd})
        .then(()=>{ toast('Message sent'); setTimeout(()=>location.reload(),1500); })
        .catch(()=>toast('Error','error'));
}

// ── Delete / Archive / Approve / Reject ──
function deletePO(id){ if(confirm('Delete this PO?')){ fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`delete_po=1&po_id=${id}`}).then(()=>{ toast('PO deleted'); setTimeout(()=>location.reload(),1500); }); } }
function archivePO(id){ if(confirm('Archive this PO?')){ fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`archive_po=1&po_id=${id}`}).then(()=>{ toast('PO archived'); setTimeout(()=>location.reload(),1500); }); } }
function approvePO(id){ if(confirm('Approve this PO?')){ fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`update_status=1&po_id=${id}&new_status=Approved&admin_notes=Approved`}).then(()=>{ toast('PO approved'); setTimeout(()=>location.reload(),1500); }); } }
function rejectPO(id){ const r=prompt('Reason for rejection:'); if(r){ fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`update_status=1&po_id=${id}&new_status=Rejected&admin_notes=${encodeURIComponent(r)}`}).then(()=>{ toast('PO rejected'); setTimeout(()=>location.reload(),1500); }); } }

// ── Auto-refresh (pauses when a modal is open) ──
let ari = setInterval(()=>location.reload(), 30000);
document.querySelectorAll('.modal').forEach(m=>{
    m.addEventListener('show.bs.modal',  ()=>clearInterval(ari));
    m.addEventListener('hidden.bs.modal',()=>{ ari=setInterval(()=>location.reload(),30000); });
});
<?php endif; ?>
</script>
</body>
</html>
