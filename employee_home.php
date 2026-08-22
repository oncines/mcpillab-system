<?php
require_once 'config.php';

require_roles(['employee']);

// The shared dashboard is the employee landing page.  Keeping this redirect
// also preserves existing bookmarks to employee_home.php.
redirect('dashboard.php');

$stats = get_dashboard_stats();
$unread_messages = get_unread_message_count($_SESSION['user_id']);
$recent_deliveries = get_deliveries(null, 5);

$full_name = $_SESSION['full_name'] ?? 'Employee';
$name_parts = preg_split('/\s+/', trim($full_name));
$first_name = $name_parts[0] ?? 'Employee';
$initials = strtoupper(substr($name_parts[0] ?? 'E', 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
$greeting_hour = (int) date('H');
$greeting = $greeting_hour < 12 ? 'morning' : ($greeting_hour < 18 ? 'afternoon' : 'evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --fb-blue: #1645b6;
            --fb-blue-dark: #0d1b3e;
            --fb-blue-light: #e8eeff;
            --bg: #f0f2f9;
            --card: #ffffff;
            --text: #0d1030;
            --text-secondary: #3a4066;
            --text-muted: #7b809e;
            --border: #cdd1e8;
            --border-light: #e4e7f2;
            --hover: #f7f8fd;
            --nav-height: 64px;
            --sidebar-w: 220px;
            --right-w: 360px;
            --green: #31a24c;
            --red: #fa3e3e;
            --yellow: #f5c518;
            --app-sidebar-bg: #0d1b3e;
            --app-sidebar-hover: rgba(255,255,255,0.06);
            --app-sidebar-active: rgba(255,255,255,0.11);
            --app-sidebar-text: rgba(255,255,255,0.70);
            --app-sidebar-border: rgba(255,255,255,0.07);
        }

        body {
            font-family: 'Sora', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 13px;
            line-height: 1.55;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--app-sidebar-bg);
            display: flex;
            flex-direction: column;
            z-index: 1200;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
        }
        .sidebar::-webkit-scrollbar { display: none; }
        .sb-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 14px 14px;
            border-bottom: 1px solid var(--app-sidebar-border);
            flex-shrink: 0;
        }
        .sb-logo-ring {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.30);
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a2a5e;
        }
        .sb-logo-ring img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .sb-logo-fallback { font-size: 10px; font-weight: 800; color: #fff; display: none; }
        .sb-brand-name { font-size: 12.5px; font-weight: 800; color: #fff; letter-spacing: .06em; text-transform: uppercase; line-height: 1.2; }
        .sb-brand-sub { font-size: 9px; color: rgba(255,255,255,.36); letter-spacing: .09em; text-transform: uppercase; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
        .sb-nav { flex: 1; padding: 5px 9px 4px; }
        .sb-section { font-size: 9.5px; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; color: rgba(255,255,255,.32); padding: 14px 8px 5px; }
        .sb-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            min-height: 40px;
            border-radius: 9px;
            color: var(--app-sidebar-text);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.2;
            transition: background .13s, color .13s;
            margin-bottom: 2px;
            position: relative;
        }
        .sb-item:hover { background: var(--app-sidebar-hover); color: #fff; text-decoration: none; }
        .sb-item.active { background: var(--app-sidebar-active); color: #fff; font-weight: 600; }
        .sb-item i { font-size: 18px; flex-shrink: 0; width: 22px; text-align: center; line-height: 1; }
        .sb-badge {
            margin-left: auto;
            background: #e5534b;
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            min-width: 16px;
            height: 16px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
        }
        .sb-footer { flex-shrink: 0; padding: 4px 9px 16px; border-top: 1px solid var(--app-sidebar-border); }
        .sb-item.sb-logout { color: rgba(239,68,68,.75); }
        .sb-item.sb-logout i { color: rgba(239,68,68,.85); }
        .sb-item.sb-logout:hover { background: rgba(239,68,68,.10); color: #ef4444; }
        .mobile-sb-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: 1px solid var(--border-light);
            border-radius: 9px;
            background: var(--card);
            color: var(--text-secondary);
        }
        .mobile-sb-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(9,15,85,.45);
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s ease;
            z-index: 1190;
        }
        body.sb-open .mobile-sb-backdrop { opacity: 1; pointer-events: auto; }

        .main-wrap {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: var(--nav-height);
            background: var(--card);
            border-bottom: 1px solid var(--border-light);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-left { display: flex; align-items: center; gap: 14px; }
        .topbar-divider { width: 1px; height: 28px; background: var(--border-light); margin: 0 4px; }
        .topbar-title { font-size: 16px; font-weight: 700; color: var(--text); letter-spacing: -.025em; line-height: 1.2; }
        .topbar-sub { font-size: 11px; color: var(--text-muted); font-weight: 400; letter-spacing: .02em; }
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .tb-icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: var(--card);
            border: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            text-decoration: none;
            position: relative;
        }
        .tb-icon-btn:hover { color: var(--fb-blue); background: var(--hover); }

        .top-nav {
            position: fixed;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: var(--nav-height);
            background: var(--card);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 8px;
            z-index: 1000;
            box-shadow: none;
        }
        .nav-brand {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.02em;
            min-width: 40px;
            text-decoration: none;
        }
        .nav-search {
            position: relative;
            flex: 0 0 240px;
        }
        .nav-search input {
            width: 100%;
            height: 40px;
            background: var(--bg);
            border: none;
            border-radius: 20px;
            padding: 0 16px 0 40px;
            font-size: 0.95rem;
            color: var(--text);
            outline: none;
            font-family: inherit;
        }
        .nav-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .nav-tabs-center {
            flex: 1;
            display: flex;
            justify-content: center;
            gap: 4px;
        }
        .nav-tab {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 112px;
            height: 48px;
            border-radius: 10px;
            color: var(--text-secondary);
            font-size: 1.3rem;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            position: relative;
        }
        .nav-tab:hover { background: var(--bg); color: var(--text); }
        .nav-tab.active { color: var(--fb-blue); }
        .nav-tab.active::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--fb-blue);
            border-radius: 2px 2px 0 0;
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bg);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--text);
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
            position: relative;
        }
        .nav-icon-btn:hover { background: var(--border-light); }
        .badge-dot {
            position: absolute;
            top: 2px;
            right: 2px;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            background: var(--red);
            border-radius: 999px;
            font-size: 0.6rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--card);
        }
        .nav-avatar,
        .avatar-lg,
        .post-avatar,
        .contact-avatar,
        .story-avatar-wrap {
            background: linear-gradient(135deg, var(--fb-blue) 0%, #9b59b6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }
        .nav-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 0.85rem;
            cursor: pointer;
            flex-shrink: 0;
        }

        .page-body {
            display: grid;
            grid-template-columns: 1fr var(--right-w);
            min-height: calc(100vh - var(--nav-height));
            padding: 16px 18px 22px;
            max-width: none;
            width: 100%;
            gap: 18px;
        }
        .sidebar-left {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--app-sidebar-bg);
            border-right: 1px solid var(--app-sidebar-border);
            padding: 0 10px 16px;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
            z-index: 1200;
        }
        .sidebar-right {
            position: sticky;
            top: var(--nav-height);
            height: calc(100vh - var(--nav-height));
            overflow-y: auto;
            scrollbar-width: none;
        }
        .sidebar-right { padding: 0; }
        .sidebar-left::-webkit-scrollbar,
        .sidebar-right::-webkit-scrollbar { display: none; }

        .sidebar-user,
        .sidebar-nav-item,
        .contact-row,
        .quick-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--app-sidebar-text);
        }
        .sidebar-user {
            margin: 0 -10px 10px;
            gap: 12px;
            padding: 16px 14px 14px;
            border-radius: 0;
            border-bottom: 1px solid var(--app-sidebar-border);
            color: #fff;
        }
        .sidebar-user:hover,
        .sidebar-nav-item:hover,
        .contact-row:hover { background: var(--app-sidebar-hover); color: #fff; }
        .avatar-lg {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 0.75rem;
            flex-shrink: 0;
            background: #1a2a5e;
            border: 2px solid rgba(255,255,255,0.30);
        }
        .sidebar-user .name { font-weight: 800; font-size: 0.8rem; letter-spacing: 0.04em; text-transform: uppercase; line-height: 1.15; }
        .sidebar-nav-item {
            gap: 10px;
            padding: 8px 10px;
            min-height: 40px;
            border-radius: 9px;
            font-weight: 500;
            font-size: 13px;
            line-height: 1.2;
            margin-bottom: 2px;
            color: var(--app-sidebar-text);
        }
        .sidebar-nav-item.active {
            background: var(--app-sidebar-active);
            color: #fff;
            font-weight: 600;
        }
        .icon-wrap,
        .see-more-icon,
        .delivery-icon {
            width: 22px;
            height: 22px;
            border-radius: 0;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 18px;
            color: inherit;
        }
        .sidebar-nav-item.blue .icon-wrap,
        .delivery-icon { background: transparent; color: inherit; }
        .sidebar-nav-item.green .icon-wrap,
        .sidebar-nav-item.red .icon-wrap,
        .sidebar-nav-item.purple .icon-wrap,
        .sidebar-nav-item.orange .icon-wrap { background: transparent; color: inherit; }
        .sidebar-nav-item.red { color: rgba(239,68,68,0.78); }
        .sidebar-nav-item.red:hover { background: rgba(239,68,68,0.10); color: #ef4444; }
        .sidebar-divider {
            height: 1px;
            background: var(--app-sidebar-border);
            margin: 10px 0;
        }

        .feed {
            padding: 0;
            max-width: 780px;
            margin: 0 auto;
            width: 100%;
        }
        .stories-row {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            scrollbar-width: none;
            padding-bottom: 4px;
            margin-bottom: 16px;
        }
        .stories-row::-webkit-scrollbar { display: none; }
        .story-card {
            flex: 0 0 112px;
            height: 200px;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            background: var(--card);
            border: 1px solid var(--border-light);
        }
        .story-bg {
            width: 100%;
            height: 65%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        .story-avatar-wrap {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid var(--fb-blue);
            overflow: hidden;
            font-size: 0.9rem;
        }
        .story-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px;
            background: var(--card);
            height: 35%;
            display: flex;
            align-items: center;
        }
        .story-footer span {
            font-weight: 600;
            font-size: 0.78rem;
            color: var(--text);
            line-height: 1.2;
        }
        .create-story .story-bg { background: var(--bg); }
        .plus-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--fb-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
        }

        .composer-card,
        .post-card,
        .right-widget {
            background: var(--card);
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .composer-card {
            padding: 12px 16px;
            margin-bottom: 16px;
        }
        .composer-top {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .composer-input {
            flex: 1;
            height: 40px;
            border-radius: 20px;
            background: var(--bg);
            border: none;
            padding: 0 16px;
            font-size: 0.95rem;
            color: var(--text-secondary);
            cursor: pointer;
            font-family: inherit;
            outline: none;
            min-width: 0;
        }
        .composer-input:hover { background: var(--border-light); }
        .composer-divider { height: 1px; background: var(--border-light); margin-bottom: 10px; }
        .composer-actions,
        .post-actions {
            display: flex;
            gap: 4px;
        }
        .composer-action-btn,
        .post-action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 36px;
            border-radius: 8px;
            border: none;
            background: none;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            color: var(--text-secondary);
            font-family: inherit;
            transition: background 0.15s;
            text-align: center;
        }
        .composer-action-btn:hover,
        .post-action-btn:hover { background: var(--hover); }
        .composer-action-btn.red i { color: #e53935; }
        .composer-action-btn.green i { color: var(--green); }
        .composer-action-btn.orange i { color: #fb8c00; }

        .post-card {
            margin-bottom: 16px;
            overflow: hidden;
        }
        .post-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px 10px;
        }
        .post-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        .post-avatar.green { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .post-avatar.orange { background: linear-gradient(135deg, #e67e22, #f39c12); }
        .post-avatar.system { background: linear-gradient(135deg, #1877f2, #4267b2); font-size: 1.1rem; }
        .post-meta { flex: 1; min-width: 0; }
        .poster-name { font-weight: 700; font-size: 0.95rem; color: var(--text); }
        .post-time { font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 4px; }
        .post-menu-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: none;
            font-size: 1.1rem;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .post-menu-btn:hover { background: var(--hover); }
        .post-body { padding: 0 16px 12px; }
        .post-text { font-size: 0.95rem; line-height: 1.5; color: var(--text); }
        .post-highlight-bg {
            padding: 20px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
        }
        .post-highlight-bg.blue { background: linear-gradient(135deg, #1877f2 0%, #4267b2 100%); }
        .post-highlight-bg.green { background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); }
        .highlight-text {
            font-size: 1.4rem;
            font-weight: 700;
            color: white;
            text-align: center;
            line-height: 1.3;
        }
        .highlight-subtext {
            font-size: 0.9rem;
            font-weight: 400;
            opacity: 0.9;
        }
        .stat-block-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2px;
            background: var(--border-light);
        }
        .stat-block {
            background: var(--card);
            padding: 16px 12px;
            text-align: center;
        }
        .stat-val {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--fb-blue);
            display: block;
        }
        .stat-lbl {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .delivery-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-bottom: 1px solid var(--border-light);
            font-size: 0.88rem;
        }
        .delivery-row:last-child { border-bottom: none; }
        .delivery-info { flex: 1; min-width: 0; }
        .d-name { font-weight: 600; color: var(--text); }
        .d-date { color: var(--text-secondary); font-size: 0.8rem; }
        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 12px;
            white-space: nowrap;
        }
        .status-badge.delivered { background: #e6f4ea; color: #1e8e3e; }
        .status-badge.in_transit { background: var(--fb-blue-light); color: var(--fb-blue); }
        .status-badge.pending { background: #fff3e0; color: #e65100; }
        .post-reactions {
            padding: 6px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--border-light);
            gap: 12px;
        }
        .reactions-summary {
            font-size: 0.85rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .reaction-emojis { font-size: 1rem; }
        .post-actions {
            border-top: 1px solid var(--border-light);
            padding: 4px 8px;
        }
        .post-action-btn.liked { color: var(--fb-blue); }

        .right-widget {
            padding: 14px 16px;
            margin-bottom: 16px;
        }
        .right-widget h6 {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .quick-link {
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-light);
            font-size: 0.88rem;
            font-weight: 500;
        }
        .quick-link:last-child { border-bottom: none; padding-bottom: 0; }
        .quick-link:hover { color: var(--fb-blue); }
        .quick-link i { color: var(--fb-blue); font-size: 1rem; width: 20px; text-align: center; }
        .att-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-light);
            font-size: 0.85rem;
        }
        .att-row:last-child { border-bottom: none; padding-bottom: 0; }
        .att-label { color: var(--text-secondary); }
        .att-value { font-weight: 600; color: var(--text); text-align: right; }
        .att-value.green { color: var(--green); }
        .att-value.red { color: var(--red); }
        .contacts-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-secondary);
            padding: 4px 8px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .contact-row {
            gap: 12px;
            padding: 8px;
            border-radius: 8px;
        }
        .contact-avatar-wrap {
            position: relative;
            width: 36px;
            height: 36px;
            flex: 0 0 auto;
        }
        .contact-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 0.75rem;
        }
        .contact-avatar.g { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .contact-avatar.o { background: linear-gradient(135deg, #e67e22, #f39c12); }
        .online-dot {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--green);
            border: 2px solid var(--card);
        }
        .contact-name { font-weight: 500; font-size: 0.93rem; }
        .footer-links {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 12px;
            padding: 8px;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        .footer-links a { color: inherit; text-decoration: none; }
        .footer-links a:hover { text-decoration: underline; }

        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--card);
            border-top: 1px solid var(--border-light);
            padding: 6px 0;
            z-index: 999;
        }
        .mobile-nav-items {
            display: flex;
            justify-content: space-around;
        }
        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            padding: 6px 8px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 0.65rem;
            font-weight: 600;
            min-width: 0;
        }
        .mobile-nav-item i { font-size: 1.3rem; }
        .mobile-nav-item.active { color: var(--fb-blue); }

        @media (max-width: 1200px) {
            .sidebar-right { display: none; }
            .page-body { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform .3s ease; box-shadow: 0 12px 28px rgba(0,0,0,.25); }
            body.sb-open .sidebar { transform: translateX(0); }
            .mobile-sb-toggle { display: inline-flex; }
            .mobile-sb-backdrop { display: block; }
            .main-wrap { margin-left: 0; }
            .topbar { padding: 0 16px; }
            .topbar-divider { display: none; }
            .page-body { grid-template-columns: 1fr; padding: 14px 12px 24px; }
            .composer-action-btn,
            .post-action-btn { font-size: 0.78rem; gap: 5px; }
            .stat-val { font-size: 1.45rem; }
            .highlight-text { font-size: 1.2rem; }
            .delivery-row { align-items: flex-start; }
            .status-badge { margin-top: 7px; }
        }
        </style>
        <link rel="stylesheet" href="sidebar-standard.css">
</head>
<body>
<nav id="appSidebar" class="sidebar">
    <div class="sb-logo">
        <div class="sb-logo-ring">
            <img src="logo.png" alt="McPIL" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <span class="sb-logo-fallback">McP</span>
        </div>
        <div>
            <div class="sb-brand-name">McPIL</div>
            <div class="sb-brand-sub">Pharmaceutical Lab...</div>
        </div>
    </div>

    <div class="sb-nav">
        <div class="sb-section">Main</div>
        <a class="sb-item active" href="employee_home.php"><i class="ti ti-layout-dashboard"></i>Home</a>
        <a class="sb-item" href="inventory.php"><i class="ti ti-box"></i>Inventory</a>

        <div class="sb-section">Attendance</div>
        <a class="sb-item" href="attendance_camera.php"><i class="ti ti-camera"></i>Check In</a>
        <a class="sb-item" href="attendance_history.php"><i class="ti ti-calendar-event"></i>Attendance Log</a>

        <div class="sb-section">Logistics</div>
        <a class="sb-item" href="delivery_tracking.php"><i class="ti ti-truck-delivery"></i>Delivery Tracking</a>

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

<div class="mobile-sb-backdrop" id="mobileSbBackdrop"></div>

<div class="main-wrap">
    <header class="topbar">
        <div class="topbar-left">
            <button class="mobile-sb-toggle" id="sidebarToggle" aria-label="Open navigation" type="button">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-divider"></div>
            <div>
                <div class="topbar-title">Employee Home</div>
                <div class="topbar-sub">Welcome back, <?php echo htmlspecialchars($first_name); ?> · Daily workspace</div>
            </div>
        </div>
        <div class="topbar-right">
            <a href="chat_interface.php" class="tb-icon-btn" title="Messages">
                <i class="ti ti-message-2"></i>
                <?php if ($unread_messages > 0): ?>
                    <span class="badge-dot"><?php echo min($unread_messages, 9); ?></span>
                <?php endif; ?>
            </a>
            <div class="nav-avatar" title="<?php echo htmlspecialchars($full_name); ?>"><?php echo htmlspecialchars($initials); ?></div>
        </div>
    </header>

<div class="page-body">
    <main class="feed">
        <div class="stories-row">
            <div class="story-card create-story" onclick="window.location='chat_interface.php'">
                <div class="story-bg">
                    <div class="plus-btn"><i class="fas fa-plus"></i></div>
                </div>
                <div class="story-footer"><span>Create Story</span></div>
            </div>

            <div class="story-card" onclick="window.location='attendance_camera.php'">
                <div class="story-bg" style="background:linear-gradient(135deg,#e3f2fd,#bbdefb);">
                    <i class="fas fa-clock" style="font-size:2.5rem; color:#1877f2;"></i>
                </div>
                <div class="story-avatar-wrap" style="background:linear-gradient(135deg,#1877f2,#4267b2);">
                    <i class="fas fa-clock" style="font-size:1rem; color:white;"></i>
                </div>
                <div class="story-footer"><span>Clock In Now</span></div>
            </div>

            <div class="story-card" onclick="window.location='inventory.php'">
                <div class="story-bg" style="background:linear-gradient(135deg,#e8f5e9,#c8e6c9);">
                    <i class="fas fa-boxes" style="font-size:2.5rem; color:#27ae60;"></i>
                </div>
                <div class="story-avatar-wrap" style="background:linear-gradient(135deg,#27ae60,#2ecc71);">
                    <i class="fas fa-boxes" style="font-size:1rem; color:white;"></i>
                </div>
                <div class="story-footer"><span>Inventory</span></div>
            </div>

            <div class="story-card" onclick="window.location='chat_interface.php'">
                <div class="story-bg" style="background:linear-gradient(135deg,#fff3e0,#ffe0b2);">
                    <i class="fas fa-comments" style="font-size:2.5rem; color:#e67e22;"></i>
                </div>
                <div class="story-avatar-wrap" style="background:linear-gradient(135deg,#e67e22,#f39c12);">
                    <i class="fas fa-comments" style="font-size:1rem; color:white;"></i>
                </div>
                <div class="story-footer"><span>Team Messages</span></div>
            </div>

            <div class="story-card" onclick="window.location='reports.php'">
                <div class="story-bg" style="background:linear-gradient(135deg,#f3e5f5,#e1bee7);">
                    <i class="fas fa-chart-bar" style="font-size:2.5rem; color:#8e44ad;"></i>
                </div>
                <div class="story-avatar-wrap" style="background:linear-gradient(135deg,#8e44ad,#9b59b6);">
                    <i class="fas fa-chart-bar" style="font-size:1rem; color:white;"></i>
                </div>
                <div class="story-footer"><span>Reports</span></div>
            </div>
        </div>

        <div class="composer-card">
            <div class="composer-top">
                <div class="nav-avatar" style="width:40px;height:40px;flex-shrink:0;"><?php echo htmlspecialchars($initials); ?></div>
                <input class="composer-input" type="text" placeholder="What's on your mind, <?php echo htmlspecialchars($first_name); ?>?" readonly onclick="window.location='chat_interface.php'">
            </div>
            <div class="composer-divider"></div>
            <div class="composer-actions">
                <button class="composer-action-btn red" type="button" onclick="window.location='attendance_camera.php'">
                    <i class="fas fa-clock"></i> Clock In
                </button>
                <button class="composer-action-btn green" type="button" onclick="window.location='inventory.php'">
                    <i class="fas fa-boxes"></i> Inventory
                </button>
                <button class="composer-action-btn orange" type="button" onclick="window.location='chat_interface.php'">
                    <i class="fas fa-comments"></i> Messages
                </button>
            </div>
        </div>

        <div class="post-card">
            <div class="post-header">
                <div class="post-avatar system"><i class="fas fa-flask" style="font-size:1rem;"></i></div>
                <div class="post-meta">
                    <div class="poster-name">McPIL Pharmaceutical Laboratory</div>
                    <div class="post-time"><i class="fas fa-globe-asia" style="font-size:0.75rem;"></i> Just now</div>
                </div>
                <button class="post-menu-btn" type="button"><i class="fas fa-ellipsis-h"></i></button>
            </div>

            <div class="post-highlight-bg blue">
                <div class="highlight-text">
                    Good <?php echo $greeting; ?>, <?php echo htmlspecialchars($first_name); ?>!<br>
                    <span class="highlight-subtext">Welcome to McPIL Pharmaceutical Laboratory</span>
                </div>
            </div>

            <div class="stat-block-row">
                <div class="stat-block">
                    <span class="stat-val"><?php echo $stats['total_purchase_orders']; ?></span>
                    <span class="stat-lbl">Purchase Orders</span>
                </div>
                <div class="stat-block">
                    <span class="stat-val"><?php echo $stats['pending_deliveries']; ?></span>
                    <span class="stat-lbl">Pending Deliveries</span>
                </div>
                <div class="stat-block">
                    <span class="stat-val"><?php echo $stats['total_employees']; ?></span>
                    <span class="stat-lbl">Team Members</span>
                </div>
            </div>

            <div class="post-reactions">
                <div class="reactions-summary">
                    <span class="reaction-emojis">Like</span>
                    <span><?php echo rand(5, 20); ?> reactions</span>
                </div>
                <span style="font-size:0.83rem;color:var(--text-secondary);"><?php echo rand(1, 5); ?> comments</span>
            </div>
            <div class="post-actions">
                <button class="post-action-btn" type="button" onclick="toggleLike(this)">
                    <i class="far fa-thumbs-up"></i> Like
                </button>
                <button class="post-action-btn" type="button"><i class="far fa-comment"></i> Comment</button>
                <button class="post-action-btn" type="button"><i class="fas fa-share"></i> Share</button>
            </div>
        </div>

        <div class="post-card">
            <div class="post-header">
                <div class="post-avatar orange"><i class="fas fa-bell" style="font-size:0.95rem;"></i></div>
                <div class="post-meta">
                    <div class="poster-name">HR Department</div>
                    <div class="post-time"><i class="fas fa-globe-asia" style="font-size:0.75rem;"></i> Today at <?php echo date('g:i A'); ?></div>
                </div>
                <button class="post-menu-btn" type="button"><i class="fas fa-ellipsis-h"></i></button>
            </div>
            <div class="post-highlight-bg green">
                <div class="highlight-text">
                    Don't forget to clock in today!<br>
                    <span class="highlight-subtext">Tap the button below to record your attendance</span>
                </div>
            </div>
            <div style="padding: 14px 16px;">
                <a href="attendance_camera.php" style="display:inline-flex; align-items:center; gap:8px; background:var(--fb-blue); color:white; border-radius:8px; padding:10px 20px; font-weight:700; font-size:0.9rem; text-decoration:none;">
                    <i class="fas fa-camera"></i> Open Attendance Camera
                </a>
            </div>
            <div class="post-reactions">
                <div class="reactions-summary">
                    <span class="reaction-emojis">Done</span>
                    <span><?php echo rand(3, 12); ?> reactions</span>
                </div>
            </div>
            <div class="post-actions">
                <button class="post-action-btn" type="button" onclick="toggleLike(this)"><i class="far fa-thumbs-up"></i> Like</button>
                <button class="post-action-btn" type="button"><i class="far fa-comment"></i> Comment</button>
                <button class="post-action-btn" type="button"><i class="fas fa-share"></i> Share</button>
            </div>
        </div>

        <div class="post-card">
            <div class="post-header">
                <div class="post-avatar green"><i class="fas fa-truck" style="font-size:0.95rem;"></i></div>
                <div class="post-meta">
                    <div class="poster-name">Delivery Updates</div>
                    <div class="post-time"><i class="fas fa-globe-asia" style="font-size:0.75rem;"></i> Recent activity</div>
                </div>
                <button class="post-menu-btn" type="button"><i class="fas fa-ellipsis-h"></i></button>
            </div>
            <div class="post-body">
                <p class="post-text"><strong>Latest delivery updates</strong> - here's what's happening with your recent shipments:</p>
            </div>
            <div class="delivery-list">
                <?php if (!empty($recent_deliveries)): ?>
                    <?php foreach ($recent_deliveries as $delivery): ?>
                        <div class="delivery-row">
                            <div class="delivery-icon"><i class="fas fa-truck"></i></div>
                            <div class="delivery-info">
                                <div class="d-name"><?php echo htmlspecialchars($delivery['delivery_number']); ?></div>
                                <div class="d-date"><?php echo htmlspecialchars($delivery['supplier_name']); ?> &middot; <?php echo format_date($delivery['delivery_date']); ?></div>
                            </div>
                            <span class="status-badge <?php echo htmlspecialchars($delivery['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $delivery['status'])); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="delivery-row">
                        <div style="color:var(--text-secondary); font-size:0.88rem; padding:8px 0;">No recent deliveries.</div>
                    </div>
                <?php endif; ?>
            </div>
            <div style="padding:12px 16px; border-top:1px solid var(--border-light);">
                <a href="delivery_tracking.php" style="color:var(--fb-blue); font-weight:600; font-size:0.88rem; text-decoration:none;">
                    <i class="fas fa-external-link-alt"></i> View all deliveries
                </a>
            </div>
            <div class="post-actions">
                <button class="post-action-btn" type="button" onclick="toggleLike(this)"><i class="far fa-thumbs-up"></i> Like</button>
                <button class="post-action-btn" type="button"><i class="far fa-comment"></i> Comment</button>
                <button class="post-action-btn" type="button"><i class="fas fa-share"></i> Share</button>
            </div>
        </div>

        <div class="post-card">
            <div class="post-header">
                <div class="post-avatar" style="background:linear-gradient(135deg,#8e44ad,#9b59b6);"><i class="fas fa-flask" style="font-size:0.95rem;"></i></div>
                <div class="post-meta">
                    <div class="poster-name">Lab Inventory</div>
                    <div class="post-time"><i class="fas fa-globe-asia" style="font-size:0.75rem;"></i> Earlier today</div>
                </div>
                <button class="post-menu-btn" type="button"><i class="fas fa-ellipsis-h"></i></button>
            </div>
            <div class="post-body">
                <p class="post-text">The lab inventory has been updated. Check the latest stock levels to ensure all materials are well-stocked for your work today.</p>
            </div>
            <div style="padding:0 16px 14px;">
                <a href="inventory.php" style="display:inline-flex; align-items:center; gap:8px; background:var(--bg); color:var(--text); border:1px solid var(--border); border-radius:8px; padding:10px 20px; font-weight:600; font-size:0.88rem; text-decoration:none;">
                    <i class="fas fa-boxes" style="color:#8e44ad;"></i> View Inventory
                </a>
            </div>
            <div class="post-reactions">
                <div class="reactions-summary">
                    <span class="reaction-emojis">Updated</span>
                    <span><?php echo rand(2, 8); ?> reactions</span>
                </div>
            </div>
            <div class="post-actions">
                <button class="post-action-btn" type="button" onclick="toggleLike(this)"><i class="far fa-thumbs-up"></i> Like</button>
                <button class="post-action-btn" type="button"><i class="far fa-comment"></i> Comment</button>
                <button class="post-action-btn" type="button"><i class="fas fa-share"></i> Share</button>
            </div>
        </div>
    </main>

    <aside class="sidebar-right">
        <div class="right-widget">
            <h6>Quick Access</h6>
            <a href="attendance_camera.php" class="quick-link"><i class="fas fa-clock"></i> Clock In / Out</a>
            <a href="attendance_history.php" class="quick-link"><i class="fas fa-history"></i> Attendance History</a>
            <a href="inventory.php" class="quick-link"><i class="fas fa-boxes"></i> Inventory</a>
            <a href="reports.php" class="quick-link"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="chat_interface.php" class="quick-link"><i class="fas fa-comments"></i> Team Messages</a>
        </div>

        <div class="right-widget attendance-widget">
            <h6>Your Dashboard</h6>
            <div class="att-row">
                <span class="att-label">Purchase Orders</span>
                <span class="att-value"><?php echo $stats['total_purchase_orders']; ?></span>
            </div>
            <div class="att-row">
                <span class="att-label">Pending Deliveries</span>
                <span class="att-value <?php echo $stats['pending_deliveries'] > 0 ? 'red' : 'green'; ?>"><?php echo $stats['pending_deliveries']; ?></span>
            </div>
            <div class="att-row">
                <span class="att-label">Unread Messages</span>
                <span class="att-value <?php echo $unread_messages > 0 ? 'red' : 'green'; ?>"><?php echo $unread_messages > 0 ? $unread_messages : 'None'; ?></span>
            </div>
            <div class="att-row">
                <span class="att-label">Team Size</span>
                <span class="att-value"><?php echo $stats['total_employees']; ?> members</span>
            </div>
        </div>

        <div style="padding: 0 8px;">
            <div class="contacts-title">
                Contacts
                <span>
                    <button style="background:none;border:none;cursor:pointer;color:var(--text-secondary);font-size:0.9rem;padding:4px 6px;border-radius:6px;" title="New message" type="button">
                        <i class="fas fa-edit"></i>
                    </button>
                </span>
            </div>
            <a href="chat_interface.php" class="contact-row">
                <div class="contact-avatar-wrap">
                    <div class="contact-avatar">HR</div>
                    <div class="online-dot"></div>
                </div>
                <span class="contact-name">HR Department</span>
            </a>
            <a href="chat_interface.php" class="contact-row">
                <div class="contact-avatar-wrap">
                    <div class="contact-avatar g">ST</div>
                    <div class="online-dot"></div>
                </div>
                <span class="contact-name">Store Manager</span>
            </a>
            <a href="chat_interface.php" class="contact-row">
                <div class="contact-avatar-wrap">
                    <div class="contact-avatar o">AD</div>
                </div>
                <span class="contact-name">Admin</span>
            </a>
        </div>

        <div class="footer-links">
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
            <a href="#">Help</a>
            <span>McPIL &copy; <?php echo date('Y'); ?></span>
        </div>
    </aside>
</div><!-- /page-body -->
</div><!-- /main-wrap -->

<?php include 'mcbot_widget.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarBackdrop = document.getElementById('mobileSbBackdrop');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => document.body.classList.add('sb-open'));
    }
    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', () => document.body.classList.remove('sb-open'));
    }

    function toggleLike(button) {
        button.classList.toggle('liked');
        button.innerHTML = button.classList.contains('liked')
            ? '<i class="fas fa-thumbs-up"></i> Liked'
            : '<i class="far fa-thumbs-up"></i> Like';
    }
</script>
</body>
</html>
