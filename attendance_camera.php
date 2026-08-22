<?php
require_once 'config.php';

if (!is_logged_in() || !is_employee()) {
    redirect('index.php');
}

$employee_info = get_employee_by_user_id($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['capture_attendance'])) {
    $employee_id = $_POST['employee_id'];
    $attendance_type = $_POST['attendance_type'] ?? 'clock_in';
    $capture_date = date('Y-m-d');
    $capture_time = date('H:i:s');
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $location_address = $_POST['location_address'] ?? '';
    $azimuth = $_POST['azimuth'] ?? '';
    $temperature = $_POST['temperature'] ?? null;
    $device_info = $_POST['device_info'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $is_video = isset($_POST['is_video']) && $_POST['is_video'] == '1';

    $media_path = '';
    $media_type = $is_video ? 'video' : 'photo';

    if ($is_video) {
        if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
            $upload_dir = 'public/attendance_videos/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = 'attendance_video_' . $employee_id . '_' . time() . '.webm';
            $media_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['video']['tmp_name'], $media_path)) $upload_success = true;
            else $error_message = "Failed to upload video.";
        } else {
            $error_message = "Please record a video.";
        }
    } else {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $upload_dir = 'public/attendance_photos/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = 'attendance_' . $employee_id . '_' . time() . '.jpg';
            $media_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $media_path)) {
                // Apply watermark to the uploaded photo
                $attendance_data = [
                    'capture_time' => $capture_time,
                    'capture_date' => $capture_date,
                    'location_address' => $location_address,
                    'azimuth' => $azimuth,
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ];
                
                $watermarked_path = add_watermark_to_photo($media_path, $attendance_data);
                if ($watermarked_path) {
                    $media_path = $watermarked_path;
                    $upload_success = true;
                } else {
                    $error_message = "Failed to apply watermark to photo.";
                }
            } else {
                $error_message = "Failed to upload photo.";
            }
        } else {
            $error_message = "Please capture a photo.";
        }
    }

    if (isset($upload_success) && $upload_success) {
        $camera_success = record_camera_attendance($employee_id, $capture_date, $capture_time, $media_path,
                                                  $latitude, $longitude, $location_address, $azimuth,
                                                  $temperature, $device_info, $notes);
        $media_text = $is_video ? "Video" : "Photo";
        $type_text = ucfirst(str_replace('_', ' ', $attendance_type));
        $attendance_notes = "Camera attendance - {$type_text} - {$media_text} verification - Location: {$location_address}, Azimuth: {$azimuth}, Media: {$media_path}";
        
        // Handle clock-in vs clock-out differently
        if ($attendance_type === 'clock_out') {
            // For clock out, find existing attendance record and update it
            $database = new Database();
            $db = $database->getConnection();
            
            // Find today's attendance record for this employee
            $query = "SELECT check_in FROM attendance WHERE employee_id = :employee_id AND date = :date ORDER BY check_in ASC LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':employee_id', $employee_id);
            $stmt->bindParam(':date', $capture_date);
            $stmt->execute();
            $existing_record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_record && $existing_record['check_in']) {
                // Update existing record with check_out time and determine status based on check_out
                $attendance_status = determine_attendance_status($existing_record['check_in'], $capture_time);
                $regular_success = record_attendance($employee_id, $capture_date, $existing_record['check_in'], $capture_time, $attendance_status, $attendance_notes);
            } else {
                // No check-in found, create new record
                $attendance_status = determine_attendance_status(null, $capture_time);
                $regular_success = record_attendance($employee_id, $capture_date, null, $capture_time, $attendance_status, $attendance_notes);
            }
        } else {
            // For clock in, determine status based on check_in time
            $attendance_status = determine_attendance_status($capture_time);
            $regular_success = record_attendance($employee_id, $capture_date, $capture_time, null, $attendance_status, $attendance_notes);
        }

        if ($camera_success && $regular_success) {
            $employee_name = $employee_info['first_name'] . ' ' . $employee_info['last_name'];
            $notification_message = "{$employee_name} has submitted {$type_text} attendance with {$media_text} verification from {$location_address}";
            $database = new Database();
            $db = $database->getConnection();
            $camera_attendance_id = $db->lastInsertId();
            create_attendance_notification($employee_id, $camera_attendance_id, $notification_message, 'new_attendance', 'medium');
            header('Location: dashboard.php');
            exit();
        } else {
            $error_message = "Failed to send attendance.";
        }
    }
}

$today_camera_attendance = [];
if ($employee_info && isset($employee_info['id'])) {
    $today_camera_attendance = get_camera_attendance_by_employee($employee_info['id'], date('Y-m-d'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo APP_NAME; ?> - Camera Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #000;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #fff;
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }

        /* ── PHONE SHELL — centres a 390×844 frame on any screen ── */
        .phone-shell {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
        }

        .phone-frame {
            width: 390px;
            height: 844px;
            max-width: 100vw;
            max-height: 100dvh;
            position: relative;
            overflow: hidden;
            display: flex !important;
            flex-direction: column !important;
            background: #000;
        }

        /* On real phones that are exactly 390px wide, fill edge-to-edge */
        @media (max-width: 430px) {
            .phone-frame {
                width: 100vw;
                height: 100dvh;
                height: 100vh;
            }
        }

        /* ── STATUS BAR ── */
        .status-bar {
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            flex-shrink: 0;
            position: relative;
            z-index: 20;
            background: transparent;
        }

        .status-time {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            text-shadow: 0 1px 3px rgba(0,0,0,0.8);
        }

        .status-icons {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ── VIEWFINDER ── */
        .viewfinder {
            position: relative;
            flex: 1 !important;
            overflow: hidden;
            background: #111;
            min-height: 0;
            display: flex !important;
            flex-direction: column !important;
        }

        #videoElement {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
            display: block;
        }

        #videoElement.environment { transform: scaleX(1); }
        #canvas { display: none; }

        /* ── BACK BUTTON ── */
        .btn-back {
            position: absolute;
            top: 10px;
            left: 12px;
            background: rgba(0,0,0,0.45);
            border: none;
            color: #fff;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            z-index: 20;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        /* ── TOP-RIGHT BUTTONS ── */
        .top-right {
            position: absolute;
            top: 10px;
            right: 12px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
            z-index: 20;
        }

        .btn-guide {
            background: #1565C0;
            color: #fff;
            border: none;
            border-radius: 22px;
            padding: 8px 14px 8px 10px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .btn-guide .guide-icon {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-guide .guide-icon i { color: #1565C0; font-size: 12px; }

        .crosshair {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            border: 1.5px solid rgba(255,255,255,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .crosshair i { font-size: 15px; color: #fff; }

        /* ── WATERMARK OVERLAY ── */
        .mcpillab-overlay {
            position: absolute;
            top: 56px;
            left: 14px;
            right: 14px;
            z-index: 10;
            pointer-events: none;
        }

        .wm-title {
            font-size: 18px;
            font-weight: 800;
            color: #fff;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.9), -1px -1px 3px rgba(0,0,0,0.9);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 3px;
            cursor: pointer;
            pointer-events: auto;
            -webkit-tap-highlight-color: transparent;
        }

        .wm-title .diamond { color: #fff; font-size: 9px; flex-shrink: 0; }

        .wm-dash-line {
            border: none;
            border-top: 2px dashed rgba(255,255,255,0.7);
            margin: 3px 0 4px;
            width: 220px;
        }

        .wm-row {
            display: flex;
            align-items: flex-start;
            gap: 5px;
            margin-bottom: 2px;
            line-height: 1.3;
        }

        .wm-row .diamond { color: #fff; font-size: 8px; flex-shrink: 0; margin-top: 3px; }

        .wm-text {
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.95), -1px -1px 3px rgba(0,0,0,0.95), 0 1px 4px rgba(0,0,0,0.9);
        }

        .wm-verified {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 4px;
        }

        .wm-verified i { font-size: 11px; color: #fff; }

        .wm-verified span {
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.95);
        }

        /* ── CLOUD BAR ── */
        .cloud-bar {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(40,40,40,0.82);
            border-radius: 20px;
            padding: 7px 16px;
            font-size: 11px;
            color: #ccc;
            display: flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
            z-index: 10;
            cursor: pointer;
        }

        .cloud-bar i { color: #90CAF9; font-size: 11px; }
        .cloud-bar .arrow { color: #90CAF9; font-size: 10px; }

        /* ── FLASH ── */
        .flash-overlay {
            position: absolute;
            inset: 0;
            background: #fff;
            opacity: 0;
            pointer-events: none;
            z-index: 50;
            transition: opacity 0.05s;
        }

        .flash-overlay.flash { opacity: 0.85; }

        /* ── SPINNER ── */
        .spinner {
            display: none;
            position: absolute;
            inset: 0;
            align-items: center;
            justify-content: center;
            z-index: 40;
            background: rgba(0,0,0,0.5);
        }

        .spinner.show { display: flex; }

        .spin-ring {
            width: 42px;
            height: 42px;
            border: 3px solid rgba(255,255,255,0.2);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── ATTENDANCE OPTIONS PANEL ── */
        .options-panel {
            position: absolute;
            top: 56px;
            left: 10px;
            right: 10px;
            background: rgba(10,10,10,0.97);
            border-radius: 18px;
            padding: 14px 12px;
            z-index: 30;
            max-height: 68vh;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.08);
            display: none;
            opacity: 0;
            transform: translateY(-8px);
            transition: opacity 0.22s ease, transform 0.22s ease;
        }

        .options-panel.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .opt-item {
            display: flex;
            align-items: center;
            padding: 11px 12px;
            border-radius: 12px;
            cursor: pointer;
            margin-bottom: 6px;
            background: rgba(255,255,255,0.06);
            border: 2px solid transparent;
            transition: background 0.2s, border-color 0.2s;
            -webkit-tap-highlight-color: transparent;
        }

        .opt-item:last-child { margin-bottom: 0; }
        .opt-item.selected { border-color: #1565C0; background: rgba(21,101,192,0.2); }

        .opt-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1565C0, #42A5F5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 13px;
            flex-shrink: 0;
            margin-right: 12px;
        }

        .opt-title { font-weight: 600; font-size: 13px; margin-bottom: 2px; color: #fff; }
        .opt-sub { font-size: 11px; color: #aaa; }
        .opt-check { margin-left: auto; color: #4CAF50; font-size: 14px; }

        /* ── WATERMARKS PANEL ── */
        .wm-panel {
            position: absolute;
            top: 56px;
            left: 10px;
            right: 10px;
            background: rgba(8,8,18,0.97);
            border-radius: 18px;
            padding: 12px 12px 14px;
            z-index: 30;
            max-height: 74vh;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.08);
            display: none;
        }

        .wm-panel.show { display: block; }

        .wm-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .wm-panel-tabs {
            display: flex;
            background: rgba(255,255,255,0.06);
            border-radius: 10px;
            padding: 3px;
        }

        .wm-tab {
            background: none;
            border: none;
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 8px;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .wm-tab.active { background: #1565C0; color: #fff; }

        /* Toggle switch */
        .toggle-sw {
            width: 42px;
            height: 22px;
            background: #444;
            border-radius: 11px;
            position: relative;
            cursor: pointer;
            transition: background 0.3s;
            flex-shrink: 0;
        }

        .toggle-sw.on { background: #1565C0; }

        .toggle-sw::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 16px;
            height: 16px;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.3s;
        }

        .toggle-sw.on::after { transform: translateX(20px); }

        /* Template grid */
        .wm-template-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 4px;
        }

        .wm-card {
            border-radius: 10px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.2s;
            position: relative;
            background: #1a1a2e;
            -webkit-tap-highlight-color: transparent;
        }

        .wm-card.selected { border-color: #1565C0; }

        .wm-card-inner {
            width: 100%;
            aspect-ratio: 16/9;
            padding: 6px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .wm-card-label {
            text-align: center;
            font-size: 10px;
            color: #ccc;
            padding: 4px 0 3px;
            font-weight: 500;
        }

        .wm-card.selected .wm-card-label { color: #fff; }

        .wm-card-check {
            position: absolute;
            bottom: 24px;
            right: 6px;
            width: 16px;
            height: 16px;
            background: #1565C0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #fff;
        }

        /* Template previews */
        .customize-preview { background: #2a2a3e; justify-content: flex-start; }

        .tpl-edit-btn {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.7);
            color: #fff;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 2;
        }

        .tpl-mini-text { font-size: 6px; color: rgba(255,255,255,0.7); line-height: 1.5; }
        .tpl-mini-title { font-weight: 700; font-size: 7px; color: #fff; }
        .tpl-mini-line { letter-spacing: 1px; color: rgba(255,255,255,0.4); margin: 1px 0; }
        .tpl-mini-row { color: rgba(255,255,255,0.7); }

        .security-preview { background: #1e2a3a; justify-content: flex-start; padding: 5px; }
        .sec-header { background: #1565C0; color: #fff; font-size: 6px; font-weight: 800; text-align: center; padding: 2px 3px; border-radius: 2px 2px 0 0; letter-spacing: 0.5px; }
        .sec-time-row { display: flex; align-items: center; background: #0d1a2a; border: 1px solid rgba(255,255,255,0.15); padding: 3px 5px; gap: 5px; border-radius: 0 0 2px 2px; }
        .sec-time { font-size: 14px; font-weight: 800; color: #fff; line-height: 1; }
        .sec-date-col { display: flex; flex-direction: column; font-size: 5px; color: #ccc; line-height: 1.4; }
        .sec-dash { border-top: 1px dashed rgba(255,255,255,0.3); margin-top: 3px; }

        .clockin-preview, .clockout-preview, .construction-preview, .inspection-preview {
            background: #2a2a3a; justify-content: flex-start; padding: 5px;
        }

        .ci-badge {
            display: inline-flex; align-items: center; gap: 3px;
            padding: 2px 6px; border-radius: 3px; font-size: 6px; font-weight: 700; margin-bottom: 3px;
        }

        .ci-in          { background: #1565C0; color: #fff; }
        .ci-out         { background: #e65100; color: #fff; }
        .ci-construction{ background: #f9a825; color: #000; }
        .ci-inspection  { background: #6a1b9a; color: #fff; }

        .ci-time-row { display: flex; align-items: center; gap: 5px; margin-bottom: 2px; }
        .ci-time { font-size: 14px; font-weight: 800; color: #fff; line-height: 1; }
        .ci-date-col { display: flex; flex-direction: column; font-size: 5px; color: #ccc; line-height: 1.5; }
        .ci-location { font-size: 5.5px; color: #aaa; display: flex; align-items: center; gap: 2px; }
        .ci-location i { font-size: 5px; color: #90CAF9; }

        /* Watermark options */
        .wm-section-title {
            font-size: 10px; font-weight: 700; color: #90CAF9;
            margin-bottom: 6px; margin-top: 4px; letter-spacing: 0.8px;
        }

        .wm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 10px; }

        .wm-opt {
            display: flex; align-items: center; gap: 7px;
            padding: 8px 9px; background: rgba(21,101,192,0.08);
            border-radius: 8px; cursor: pointer;
            border: 1.5px solid rgba(21,101,192,0.25);
            font-size: 11px; color: #ccc; transition: all 0.2s;
            -webkit-tap-highlight-color: transparent;
        }

        .wm-opt.selected { border-color: #1565C0; background: rgba(21,101,192,0.22); color: #fff; }
        .wm-opt input { accent-color: #1565C0; pointer-events: none; }

        /* ── LOCATION MODAL ── */
        .location-modal {
            display: none;
            position: absolute;
            inset: 0;
            background: #fff;
            z-index: 100;
            flex-direction: column;
        }

        .location-modal.show {
            display: flex;
        }

        .location-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #1565C0;
            color: #fff;
        }

        .location-modal-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .location-modal-header .btn-back,
        .location-modal-header .btn-refresh {
            background: none;
            border: none;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
        }

        .location-modal-search {
            padding: 12px 16px;
            border-bottom: 1px solid #e0e0e0;
        }

        .location-modal-search input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }

        .location-modal-search input:focus {
            border-color: #1565C0;
        }

        .location-modal-tabs {
            display: flex;
            background: #f5f5f5;
            border-bottom: 1px solid #e0e0e0;
        }

        .loc-tab {
            flex: 1;
            background: none;
            border: none;
            padding: 12px;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }

        .loc-tab.active {
            color: #1565C0;
            border-bottom-color: #1565C0;
            background: #fff;
        }

        .location-modal-notice {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #fff3cd;
            color: #856404;
            font-size: 12px;
            border-bottom: 1px solid #ffeaa7;
        }

        .location-list {
            flex: 1;
            overflow-y: auto;
            background: #fff;
        }

        .location-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }

        .location-item:hover {
            background: #f8f9fa;
        }

        .location-item.selected {
            background: #e3f2fd;
        }

        .location-item-info {
            flex: 1;
        }

        .location-item-address {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-bottom: 2px;
        }

        .location-item-details {
            font-size: 12px;
            color: #666;
        }

        .location-item-distance {
            font-size: 12px;
            color: #1565C0;
            font-weight: 500;
        }

        .location-item-favorite {
            color: #ffc107;
            font-size: 16px;
            margin-left: 12px;
        }

        /* ── PREVIEW MODAL ── */
        .preview-modal {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.93);
            z-index: 100;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .preview-modal.show { display: flex; }

        .preview-img {
            max-width: 92%;
            max-height: 66%;
            border-radius: 12px;
            object-fit: contain;
        }

        .preview-actions { display: flex; gap: 12px; margin-top: 20px; }

        .preview-actions button {
            padding: 12px 22px; border-radius: 28px; border: none;
            font-size: 14px; font-weight: 600; cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .btn-send { background: #1565C0; color: #fff; }
        .btn-retake { background: rgba(255,255,255,0.12); color: #fff; }

        /* ── BOTTOM BAR ── */
        .bottom-bar {
            background: transparent !important;
            flex-shrink: 0 !important;
            padding-bottom: env(safe-area-inset-bottom, 8px);
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            padding: 25px 0 !important;
            position: fixed !important;
            z-index: 9999 !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            height: auto !important;
            min-height: 80px !important;
            border-top: none !important;
            box-shadow: none !important;
        }

        /* Capture button */
        .capture-btn {
            width: 70px !important;
            height: 70px !important;
            border-radius: 50% !important;
            border: 4px solid #1565C0 !important;
            background: #1565C0 !important;
            cursor: pointer !important;
            box-shadow: 0 0 0 6px rgba(21,101,192,0.2) !important;
            transition: transform 0.1s;
            flex-shrink: 0;
            -webkit-tap-highlight-color: transparent;
            color: #ffffff !important;
            font-size: 24px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: relative !important;
            margin: 0 !important;
            top: auto !important;
            right: auto !important;
            bottom: auto !important;
            left: auto !important;
        }

        .capture-btn:active { transform: scale(0.93); }

        .capture-btn.recording {
            border-color: #e53935;
            background: #e53935;
            animation: pulse-red 1.2s infinite;
        }

        @keyframes pulse-red {
            0%   { box-shadow: 0 0 0 5px rgba(229,57,53,0.3); }
            70%  { box-shadow: 0 0 0 16px rgba(229,57,53,0); }
            100% { box-shadow: 0 0 0 5px rgba(229,57,53,0); }
        }


        /* ── ALERT ── */
        .alert-wrap {
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 200;
            min-width: 240px;
            max-width: 88%;
            pointer-events: none;
        }

        .alert-wrap .alert { pointer-events: auto; font-size: 13px; }

        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

<div class="phone-shell">
<div class="phone-frame">

    <!-- STATUS BAR -->
    <div class="status-bar">
        <span class="status-time" id="statusTime">9:41</span>
        <div class="status-icons">
            <!-- Signal bars -->
            <svg width="16" height="11" viewBox="0 0 16 11">
                <rect x="0"   y="5"  width="3" height="6"  rx="1" fill="rgba(255,255,255,0.9)"/>
                <rect x="4.5" y="3"  width="3" height="8"  rx="1" fill="rgba(255,255,255,0.9)"/>
                <rect x="9"   y="1"  width="3" height="10" rx="1" fill="rgba(255,255,255,0.9)"/>
                <rect x="13.5" y="0" width="2.5" height="11" rx="1" fill="rgba(255,255,255,0.3)"/>
            </svg>
            <!-- WiFi -->
            <svg width="15" height="11" viewBox="0 0 15 11">
                <path d="M7.5 9a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" fill="#fff"/>
                <path d="M3.5 6.2C4.7 5 5.9 4.3 7.5 4.3s2.8.7 4 1.9l1.2-1.2C11.1 3.5 9.4 2.8 7.5 2.8S3.9 3.5 2.3 4.9z" fill="#fff"/>
                <path d="M0.5 3.5C2.3 1.7 4.8 0.6 7.5 0.6s5.2 1.1 7 2.9l1.1-1.1C13.5 1 10.7 0 7.5 0S1.5 1 -0.6 2.4z" fill="rgba(255,255,255,0.5)"/>
            </svg>
            <!-- Battery -->
            <div style="display:flex;align-items:center;gap:1px;">
                <div style="width:23px;height:11px;border-radius:2.5px;border:1.5px solid rgba(255,255,255,0.5);padding:1.5px;position:relative;">
                    <div style="width:75%;height:100%;background:#4CD964;border-radius:1px;"></div>
                </div>
                <div style="width:2px;height:5px;background:rgba(255,255,255,0.4);border-radius:0 1px 1px 0;"></div>
            </div>
        </div>
    </div>

    <!-- VIEWFINDER -->
    <div class="viewfinder" id="viewfinder">
        <video id="videoElement" autoplay playsinline muted></video>
        <canvas id="canvas"></canvas>

        <!-- Back -->
        <button class="btn-back" onclick="goBack()">
            <i class="fas fa-arrow-left"></i>
        </button>

        <!-- Camera retry button -->
        <button class="btn-back" style="right: 12px; left: auto;" onclick="retryCamera()" title="Retry Camera">
            <i class="fas fa-redo"></i>
        </button>

        <!-- Watermark overlay -->
        <div class="mcpillab-overlay" id="mcpillabOverlay">
            <div class="wm-title" onclick="toggleOptions()">
                <span class="diamond">&#9670;</span>
                <span id="overlayTitle">Camera Attendance</span>
            </div>
            <hr class="wm-dash-line">
            <div class="wm-row" id="wmRowTime">
                <span class="diamond">&#9670;</span>
                <span class="wm-text">Time: <span id="cardTime">--:--</span></span>
            </div>
            <div class="wm-row" id="wmRowDate">
                <span class="diamond">&#9670;</span>
                <span class="wm-text">Date: <span id="cardDate">--</span> <span id="cardWeekday">--</span></span>
            </div>
            <div class="wm-row" id="wmRowLocation">
                <span class="diamond">&#9670;</span>
                <span class="wm-text">Location: <span id="cardLocation">Getting location…</span></span>
            </div>
            <div class="wm-row" id="wmRowAzimuth">
                <span class="diamond">&#9670;</span>
                <span class="wm-text">Azimuth: <span id="cardAzimuth">N 0°</span></span>
            </div>
            <div class="wm-row" id="wmRowCoords">
                <span class="diamond">&#9670;</span>
                <span class="wm-text">Coordinate: <span id="cardCoords">--°N, --°E</span></span>
            </div>
            <div class="wm-row" id="wmRowTemp" style="display:none;">
                <span class="diamond">&#9670;</span>
                <span class="wm-text">Temperature: <span id="cardTemp">null°C</span></span>
            </div>
            <div class="wm-verified" id="wmVerified">
                <i class="fas fa-shield-check"></i>
                <span>Time &amp; location verified by McPILLAB APP</span>
            </div>
        </div>

        <!-- TOP-RIGHT BUTTONS -->
    <div class="top-right">
        <div class="crosshair" onclick="switchCamera()">
            <i class="fas fa-sync-alt"></i>
        </div>
    </div>
        </div>
        <!-- Attendance options panel -->
        <div class="options-panel" id="optionsPanel">
            <!-- Time indicator -->
            <div style="background: rgba(21,101,192,0.1); border-radius: 8px; padding: 8px 12px; margin-bottom: 10px; border-left: 3px solid #1565C0;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clock" style="color: #1565C0; font-size: 12px;"></i>
                    <div>
                        <div style="font-size: 11px; color: #666; margin-bottom: 2px;">Current Time</div>
                        <div id="currentTimeIndicator" style="font-size: 13px; font-weight: 600; color: #333;">--:-- --</div>
                    </div>
                </div>
                <div id="timeBasedHint" style="font-size: 10px; color: #1565C0; margin-top: 4px; font-weight: 500;"></div>
            </div>
            
            <!-- Shift Schedule -->
            <div style="background: rgba(0,0,0,0.05); border-radius: 6px; padding: 8px; margin-bottom: 10px;">
                <div style="font-size: 10px; font-weight: 600; color: #333; margin-bottom: 4px;">Work Schedule:</div>
                <div style="display: flex; justify-content: space-between; font-size: 9px; color: #666;">
                    <div>🌅 8:00 AM - 12:00 PM</div>
                    <div>🌤️ 1:00 PM - 5:00 PM</div>
                </div>
            </div>
            
            <div class="opt-item selected" data-type="clock_in" onclick="selectType('clock_in')">
                <div class="opt-icon"><i class="fas fa-sign-in-alt"></i></div>
                <div>
                    <div class="opt-title">Clock In</div>
                    <div class="opt-sub" id="subClockIn">--:-- -- · -- ----</div>
                </div>
                <span class="opt-check"><i class="fas fa-check"></i></span>
            </div>
            <div class="opt-item" data-type="clock_out" onclick="selectType('clock_out')">
                <div class="opt-icon"><i class="fas fa-sign-out-alt"></i></div>
                <div>
                    <div class="opt-title">Clock Out</div>
                    <div class="opt-sub" id="subClockOut">--:-- -- · -- ----</div>
                </div>
                <span class="opt-check" style="display:none"><i class="fas fa-check"></i></span>
            </div>
        </div>



        <!-- Flash & spinner -->
        <div class="flash-overlay" id="flashOverlay"></div>
        <div class="spinner" id="spinner"><div class="spin-ring"></div></div>


        <!-- Preview modal -->
        <div class="preview-modal" id="previewModal">
            <img id="previewImg" class="preview-img" alt="Preview">
            <div class="preview-actions">
                <button class="btn-send"   onclick="confirmPhoto()"><i class="fas fa-paper-plane"></i> Send Attendance</button>
                <button class="btn-retake" onclick="retakePhoto()"> <i class="fas fa-redo"></i> Retake</button>
            </div>
        </div>

        <!-- Alerts -->
        <div class="alert-wrap" id="alertWrap"></div>

    </div><!-- /viewfinder -->

    <!-- BOTTOM BAR - CAPTURE BUTTON ONLY -->
    <div class="bottom-bar">
        <button class="capture-btn" id="captureBtn" onclick="handleCapture()">
            <i class="fas fa-camera"></i>
        </button>
    </div>
</div>
</div>

<!-- Hidden form for attendance submission -->
<form id="attendanceForm" method="POST" enctype="multipart/form-data" style="display: none;">
    <input type="hidden" name="capture_attendance" value="1">
    <input type="hidden" name="employee_id" id="employeeId" value="<?php echo $employee_info['id'] ?? ''; ?>">
    <input type="hidden" name="attendance_type" id="attendanceType" value="clock_in">
    <input type="hidden" name="latitude" id="latitude">
    <input type="hidden" name="longitude" id="longitude">
    <input type="hidden" name="location_address" id="locationAddress">
    <input type="hidden" name="azimuth" id="azimuth">
    <input type="hidden" name="temperature" id="temperature">
    <input type="hidden" name="device_info" id="deviceInfo">
    <input type="hidden" name="notes" id="notes">
    <input type="hidden" name="is_video" id="isVideo" value="0">
    <input type="file" name="photo" id="photoInput" accept="image/*">
    <input type="file" name="video" id="videoInput" accept="video/*">
</form>

<?php if (isset($error_message)): ?>
    <script>
        alert('<?php echo addslashes($error_message); ?>');
    </script>
<?php endif; ?>

<script>
// Global variables
let stream = null;
let selectedType = 'clock_in'; // Default to clock in
let capturedImageData = null;
let currentLocation = null;
let currentAzimuth = 0;

// Update attendance type UI selection
function updateAttendanceTypeUI() {
    // Remove selected class from all options and hide check marks
    document.querySelectorAll('.opt-item[data-type]').forEach(item => {
        item.classList.remove('selected');
        const check = item.querySelector('.opt-check');
        if (check) check.style.display = 'none';
    });
    
    // Add selected class to current type and show check mark
    const selectedItem = document.querySelector(`.opt-item[data-type="${selectedType}"]`);
    if (selectedItem) {
        selectedItem.classList.add('selected');
        const check = selectedItem.querySelector('.opt-check');
        if (check) check.style.display = 'block';
    }
    
    // Update overlay title based on type
    const overlayTitle = document.getElementById('overlayTitle');
    if (overlayTitle) {
        if (selectedType === 'clock_in') {
            overlayTitle.textContent = 'Clock In';
        } else if (selectedType === 'clock_out') {
            overlayTitle.textContent = 'Clock Out';
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set default attendance type to clock_in
    selectedType = 'clock_in';
    document.getElementById('attendanceType').value = 'clock_in';
    updateAttendanceTypeUI();
    
    initializeCamera();
    updateDateTime();
    getLocation();
    getAzimuth();
    getDeviceInfo();
    
    // Update time every second
    setInterval(updateDateTime, 1000);
    
    // Update location every 30 seconds
    setInterval(getLocation, 30000);
    
    // Update azimuth every 2 seconds
    setInterval(getAzimuth, 2000);
});

// Camera initialization
async function initializeCamera() {
    const video = document.getElementById('videoElement');
    
    // Check if browser supports getUserMedia
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showAlert('Your browser does not support camera access. Please try a modern browser like Chrome, Firefox, or Safari.', 'danger');
        return;
    }
    
    // Try different camera configurations in order of preference
    const cameraConfigs = [
        // Try rear camera first (better for attendance)
        {
            video: {
                facingMode: 'environment',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            },
            audio: false
        },
        // Fallback to front camera
        {
            video: {
                facingMode: 'user',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            },
            audio: false
        },
        // Basic camera configuration
        {
            video: true,
            audio: false
        }
    ];
    
    let cameraStarted = false;
    
    for (let i = 0; i < cameraConfigs.length; i++) {
        try {
            console.log(`Trying camera configuration ${i + 1}...`);
            
            // Stop any existing stream
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            
            stream = await navigator.mediaDevices.getUserMedia(cameraConfigs[i]);
            
            // Check if we got video tracks
            if (stream.getVideoTracks().length > 0) {
                video.srcObject = stream;
                
                // Wait for video to be ready
                video.onloadedmetadata = function() {
                    video.play().catch(function(e) {
                        console.error('Video play error:', e);
                    });
                };
                
                cameraStarted = true;
                console.log(`Camera started with configuration ${i + 1}`);
                
                // Show success message
                if (i === 0) {
                    showAlert('Camera started (rear camera)', 'success');
                } else if (i === 1) {
                    showAlert('Camera started (front camera)', 'success');
                } else {
                    showAlert('Camera started (basic mode)', 'success');
                }
                
                break;
            }
        } catch (error) {
            console.error(`Camera configuration ${i + 1} failed:`, error);
            
            if (i === cameraConfigs.length - 1) {
                // Last attempt failed, show detailed error
                let errorMessage = 'Unable to access camera. ';
                
                if (error.name === 'NotAllowedError') {
                    errorMessage += 'Camera permission denied. Please allow camera access in your browser settings and refresh the page.';
                } else if (error.name === 'NotFoundError') {
                    errorMessage += 'No camera found. Please ensure your device has a working camera.';
                } else if (error.name === 'NotReadableError') {
                    errorMessage += 'Camera is already in use by another application. Please close other apps using the camera.';
                } else if (error.name === 'OverconstrainedError') {
                    errorMessage += 'Camera does not support the required settings. Trying basic mode...';
                } else if (error.name === 'SecurityError') {
                    errorMessage += 'Camera access blocked due to security restrictions. Please ensure you are accessing this page over HTTPS.';
                } else {
                    errorMessage += 'Error: ' + error.message;
                }
                
                showAlert(errorMessage, 'danger');
                
                // Show troubleshooting tips
                setTimeout(() => {
                    showCameraTroubleshooting();
                }, 3000);
            }
        }
    }
    
    if (!cameraStarted) {
        // Disable capture button if camera failed
        const captureBtn = document.getElementById('captureBtn');
        if (captureBtn) {
            captureBtn.disabled = true;
            captureBtn.style.opacity = '0.5';
        }
    }
}

// Show camera troubleshooting tips
function showCameraTroubleshooting() {
    const troubleshootingHTML = `
        <div style="text-align: left; padding: 10px;">
            <h6>Camera Troubleshooting:</h6>
            <ul style="font-size: 12px; margin: 0; padding-left: 20px;">
                <li>Ensure you're using HTTPS (required for camera access)</li>
                <li>Allow camera permissions when prompted</li>
                <li>Check if another app is using the camera</li>
                <li>Try refreshing the page</li>
                <li>Test with a different browser (Chrome/Firefox/Safari)</li>
                <li>On mobile: ensure camera app works</li>
                <li>On laptop: check camera privacy settings</li>
            </ul>
        </div>
    `;
    
    showAlert(troubleshootingHTML, 'warning', 10000);
}

// Retry camera initialization
function retryCamera() {
    showAlert('Retrying camera...', 'info');
    
    // Stop existing stream
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    
    // Clear video source
    const video = document.getElementById('videoElement');
    video.srcObject = null;
    
    // Re-enable capture button
    const captureBtn = document.getElementById('captureBtn');
    if (captureBtn) {
        captureBtn.disabled = false;
        captureBtn.style.opacity = '1';
    }
    
    // Try to initialize camera again
    setTimeout(() => {
        initializeCamera();
    }, 1000);
}

// Update date and time
function updateDateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    const dateString = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    
    // Update display elements
    const cardTime = document.getElementById('cardTime');
    const cardDate = document.getElementById('cardDate');
    const cardWeekday = document.getElementById('cardWeekday');
    
    if (cardTime) cardTime.textContent = timeString;
    if (cardDate) cardDate.textContent = dateString.split(',')[0];
    if (cardWeekday) cardWeekday.textContent = dateString.split(',')[1];
    
    // Update current time indicator in options panel
    const currentTimeIndicator = document.getElementById('currentTimeIndicator');
    if (currentTimeIndicator) {
        currentTimeIndicator.textContent = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit'
        });
    }
}

// Get device location
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                currentLocation = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                
                // Update display
                updateLocationDisplay();
                
                // Get address from coordinates (reverse geocoding)
                reverseGeocode(position.coords.latitude, position.coords.longitude);
            },
            function(error) {
                console.error('Geolocation error:', error);
                document.getElementById('cardLocation').textContent = 'Location unavailable';
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 30000
            }
        );
    }
}

// Reverse geocoding to get address
function reverseGeocode(lat, lon) {
    // Using Nominatim (OpenStreetMap) for reverse geocoding
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.display_name) {
                const address = data.display_name;
                document.getElementById('cardLocation').textContent = address.length > 50 ? 
                    address.substring(0, 47) + '...' : address;
                document.getElementById('locationAddress').value = address;
            }
        })
        .catch(error => {
            console.error('Reverse geocoding error:', error);
            document.getElementById('cardLocation').textContent = 'Location unavailable';
        });
}

// Update location display
function updateLocationDisplay() {
    if (currentLocation) {
        const cardLocation = document.getElementById('cardLocation');
        const cardCoords = document.getElementById('cardCoords');
        
        if (cardLocation) {
            // Format location address
            let address = currentLocation.address || 'Unknown Location';
            if (address.length > 30) {
                address = address.substring(0, 27) + '...';
            }
            cardLocation.textContent = address;
        }
        
        if (cardCoords) {
            const lat = currentLocation.latitude.toFixed(6);
            const lng = currentLocation.longitude.toFixed(6);
            cardCoords.textContent = `${lat}°N, ${lng}°E`;
        }
        
        // Update hidden form fields
        document.getElementById('latitude').value = currentLocation.latitude;
        document.getElementById('longitude').value = currentLocation.longitude;
    }
}

// Get device orientation (azimuth)
function getAzimuth() {
    if (window.DeviceOrientationEvent) {
        window.addEventListener('deviceorientation', function(event) {
            if (event.alpha !== null) {
                currentAzimuth = Math.round(event.alpha);
                updateAzimuthDisplay();
            }
        });
    }
}

// Update azimuth display
function updateAzimuthDisplay() {
    const cardAzimuth = document.getElementById('cardAzimuth');
    if (cardAzimuth) {
        const directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        const index = Math.round(currentAzimuth / 45) % 8;
        cardAzimuth.textContent = `${directions[index]} ${currentAzimuth}°`;
    }
    document.getElementById('azimuth').value = `${currentAzimuth}°`;
}

// Get device information
function getDeviceInfo() {
    const userAgent = navigator.userAgent;
    const platform = navigator.platform;
    const deviceInfo = `${platform} - ${userAgent}`;
    
    document.getElementById('deviceInfo').value = deviceInfo;
}


// Apply watermark to canvas
function applyWatermarkToCanvas(canvas, context) {
    const width = canvas.width;
    const height = canvas.height;
    
    // Add semi-transparent overlay background at top for better text visibility
    const overlayHeight = 140;
    const overlayY = 0;
    context.fillStyle = 'rgba(0, 0, 0, 0.7)';
    context.fillRect(0, overlayY, width, overlayHeight);
    
    // Set up text styles
    context.fillStyle = '#FFFFFF';
    context.font = '14px Arial';
    context.textAlign = 'left';
    
    // Get current attendance data
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    const dateString = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    const location = document.getElementById('cardLocation').textContent || 'Getting location...';
    const azimuth = document.getElementById('cardAzimuth').textContent || 'N 0°';
    const coords = document.getElementById('cardCoords').textContent || '--°N, --°E';
    
    // Watermark text lines
    const watermarkLines = [
        'McPILLAB APP - Camera Attendance',
        'Time: ' + timeString,
        'Date: ' + dateString,
        'Location: ' + (location.length > 40 ? location.substring(0, 37) + '...' : location),
        'Azimuth: ' + azimuth
    ];
    
    if (coords !== '--°N, --°E') {
        watermarkLines.push('Coordinate: ' + coords);
    }
    
    watermarkLines.push('Time & location verified by McPILLAB APP');
    
    // Draw watermark text at top
    const lineHeight = 18;
    const margin = 15;
    const startY = overlayY + 20;
    
    watermarkLines.forEach((line, index) => {
        context.fillText(line, margin, startY + (index * lineHeight));
    });
    
    // Add McPILLAB logo at top-right (above the overlay)
    const logoText = 'McPILLAB';
    const logoBoxWidth = 150;
    const logoBoxHeight = 30;
    const logoX = width - logoBoxWidth - 10;
    const logoY = overlayY + overlayHeight + 10;
    
    // Draw semi-transparent background for logo
    context.fillStyle = 'rgba(21, 101, 192, 0.8)';
    context.fillRect(logoX, logoY, logoBoxWidth, logoBoxHeight);
    
    // Draw logo text
    context.fillStyle = '#FFFFFF';
    context.font = 'bold 16px Arial';
    context.textAlign = 'left';
    context.fillText(logoText, logoX + 10, logoY + 20);
}

// Handle capture button click
function handleCapture() {
    capturePhoto();
}

// Capture photo
function capturePhoto() {
    const video = document.getElementById('videoElement');
    const canvas = document.getElementById('canvas');
    const context = canvas.getContext('2d');
    
    // Set canvas dimensions to match video
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Draw video frame to canvas
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Apply watermark to canvas
    applyWatermarkToCanvas(canvas, context);
    
    // Flash effect
    flashScreen();
    
    // Convert to blob and show preview
    canvas.toBlob(function(blob) {
        capturedImageData = blob;
        showPreview(blob);
    }, 'image/jpeg', 0.9);
}



// Show preview modal
function showPreview(blob) {
    const previewModal = document.getElementById('previewModal');
    const previewImg = document.getElementById('previewImg');
    
    const url = URL.createObjectURL(blob);
    previewImg.src = url;
    
    previewModal.classList.add('show');
}

// Flash screen effect
function flashScreen() {
    const flashOverlay = document.getElementById('flashOverlay');
    flashOverlay.classList.add('flash');
    
    setTimeout(() => {
        flashOverlay.classList.remove('flash');
    }, 100);
}

// Confirm and send attendance
function confirmPhoto() {
    console.log('confirmPhoto called');
    
    if (!capturedImageData) {
        showAlert('No image captured', 'danger');
        return;
    }
    
    console.log('Image data available, preparing submission...');
    showSpinner(true);
    
    // Prepare form data
    const formData = new FormData();
    formData.append('capture_attendance', '1');
    
    const employeeId = document.getElementById('employeeId').value || '<?php echo $employee_info["id"] ?? ""; ?>';
    console.log('Employee ID:', employeeId);
    formData.append('employee_id', employeeId);
    
    formData.append('attendance_type', selectedType);
    formData.append('latitude', document.getElementById('latitude').value);
    formData.append('longitude', document.getElementById('longitude').value);
    formData.append('location_address', document.getElementById('locationAddress').value);
    formData.append('azimuth', document.getElementById('azimuth').value);
    formData.append('device_info', document.getElementById('deviceInfo').value);
    formData.append('notes', `${selectedType} - Camera Attendance`);
    formData.append('is_video', '0');
    
    // Add media file
    formData.append('photo', capturedImageData, `attendance_${Date.now()}.jpg`);
    console.log('Adding photo to form data');
    
    console.log('Submitting form...');
    
    // Submit form
    fetch('attendance_camera.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', response.status);
        return response.text();
    })
    .then(data => {
        console.log('Response data:', data.substring(0, 200));
        showSpinner(false);
        
        // Check if redirected to dashboard (success)
        if (data.includes('dashboard') || data.includes('success')) {
            showAlert('Attendance submitted successfully!', 'success');
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 1500);
        } else {
            showAlert('Attendance submitted successfully!', 'success');
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 1500);
        }
    })
    .catch(error => {
        showSpinner(false);
        console.error('Submission error:', error);
        showAlert('Failed to submit attendance: ' + error.message, 'danger');
    });
}

// Retake photo
function retakePhoto() {
    const previewModal = document.getElementById('previewModal');
    previewModal.classList.remove('show');
    
    capturedImageData = null;
    
    // Close any open panels and return to camera view
    closeAllPanels();
    
    // Reinitialize camera if needed
    if (!stream || stream.getTracks().length === 0) {
        initializeCamera();
    }
}

// Close all panels and return to camera view
function closeAllPanels() {
    const optionsPanel = document.getElementById('optionsPanel');
    const previewModal = document.getElementById('previewModal');
    
    optionsPanel.classList.remove('show');
    previewModal.classList.remove('show');
}

// Toggle attendance options panel
function toggleOptions() {
    const optionsPanel = document.getElementById('optionsPanel');
    
    // Toggle options panel
    optionsPanel.classList.toggle('show');
}

// Select attendance type
function selectType(type) {
    selectedType = type;
    
    // Update the hidden input field
    document.getElementById('attendanceType').value = type;
    
    // Update UI using our unified function
    updateAttendanceTypeUI();
    
    // Close panel and return to camera view
    setTimeout(() => {
        closeAllPanels();
    }, 300);
}


// Show/hide spinner
function showSpinner(show) {
    const spinner = document.getElementById('spinner');
    if (show) {
        spinner.classList.add('show');
    } else {
        spinner.classList.remove('show');
    }
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertWrap = document.getElementById('alertWrap');
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    alertWrap.innerHTML = alertHtml;
    
    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        const alert = alertWrap.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 3000);
}

// Go back - context-aware navigation
function goBack() {
    const optionsPanel = document.getElementById('optionsPanel');
    const previewModal = document.getElementById('previewModal');
    
    // Check if any panels are open and close them first
    if (previewModal.classList.contains('show')) {
        previewModal.classList.remove('show');
        capturedImageData = null;
        return;
    }
    
    if (optionsPanel.classList.contains('show')) {
        optionsPanel.classList.remove('show');
        return;
    }
    
    // If no panels are open, navigate back to dashboard
    window.location.href = 'dashboard.php';
}


// Switch camera (front/back)
function switchCamera() {
    if (!stream) {
        showAlert('Camera not started. Please wait...', 'warning');
        return;
    }
    
    const video = document.getElementById('videoElement');
    const currentFacingMode = video.classList.contains('environment') ? 'user' : 'environment';
    
    showAlert('Switching camera...', 'info');
    
    // Stop current stream
    stream.getTracks().forEach(track => track.stop());
    
    // Request camera with opposite facing mode
    navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: currentFacingMode,
            width: { ideal: 1280 },
            height: { ideal: 720 }
        },
        audio: false
    }).then(function(newStream) {
        // Update video element
        stream = newStream;
        video.srcObject = stream;
        
        // Update CSS class for transform
        if (currentFacingMode === 'environment') {
            video.classList.add('environment');
            showAlert('Switched to rear camera', 'success');
        } else {
            video.classList.remove('environment');
            showAlert('Switched to front camera', 'success');
        }
        
        // Wait for video to be ready
        video.onloadedmetadata = function() {
            video.play().catch(function(e) {
                console.error('Video play error after switch:', e);
            });
        };
        
    }).catch(function(error) {
        console.error('Error switching camera:', error);
        
        // Try to restart with original camera if switching failed
        showAlert('Failed to switch camera, restoring original...', 'warning');
        initializeCamera();
    });
}



// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
});
</script>
<?php include 'mcbot_widget.php'; ?>
</body>
</html>
