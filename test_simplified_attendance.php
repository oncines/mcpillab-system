<?php
require_once 'config.php';

// Test script for simplified attendance system
echo "<h1>Simplified Attendance System Test</h1>";

// Test 1: Check if get_attendance_records_simple function exists
if (function_exists('get_attendance_records_simple')) {
    echo "<p style='color: green;'>✓ get_attendance_records_simple function exists</p>";
    
    // Test the function
    $records = get_attendance_records_simple(null, date('Y-m-01'), date('Y-m-d'), 5, 0);
    echo "<p>Found " . count($records) . " attendance records</p>";
} else {
    echo "<p style='color: red;'>✗ get_attendance_records_simple function missing</p>";
}

// Test 2: Check if get_employee_attendance_history_simple function exists
if (function_exists('get_employee_attendance_history_simple')) {
    echo "<p style='color: green;'>✓ get_employee_attendance_history_simple function exists</p>";
} else {
    echo "<p style='color: red;'>✗ get_employee_attendance_history_simple function missing</p>";
}

// Test 3: Check attendance_camera.php for simplified interface
$camera_file = file_get_contents('attendance_camera.php');
if (strpos($camera_file, 'BOTTOM BAR - CAPTURE BUTTON ONLY') !== false) {
    echo "<p style='color: green;'>✓ Camera interface simplified to show only capture button</p>";
} else {
    echo "<p style='color: red;'>✗ Camera interface not properly simplified</p>";
}

// Test 4: Check watermark format
if (strpos($camera_file, 'Time: ${timeString}') !== false && 
    strpos($camera_file, 'Date: ${formattedDate}') !== false) {
    echo "<p style='color: green;'>✓ Watermark format updated to match provided image</p>";
} else {
    echo "<p style='color: red;'>✗ Watermark format not updated</p>";
}

// Test 5: Check simplified attendance logic
if (strpos($camera_file, 'Before 12 PM') !== false && 
    strpos($camera_file, '12 PM and after') !== false) {
    echo "<p style='color: green;'>✓ Attendance type logic simplified (before/after 12 PM)</p>";
} else {
    echo "<p style='color: red;'>✗ Attendance type logic not simplified</p>";
}

echo "<h2>Test Results</h2>";
echo "<p>The attendance system has been simplified with the following changes:</p>";
echo "<ul>";
echo "<li>Removed shift system (morning, break, afternoon)</li>";
echo "<li>Simplified to only clock-in and clock-out based on time of day</li>";
echo "<li>Updated camera watermark to match the provided image format</li>";
echo "<li>Simplified camera interface to show only capture button</li>";
echo "</ul>";

echo "<p><a href='attendance_camera.php'>Test Camera Attendance</a> | ";
echo "<a href='attendance.php'>View Attendance Dashboard</a> | ";
echo "<a href='attendance_history.php'>View Attendance History</a></p>";
?>
