<?php
require_once 'config.php';

// Test script for manual attendance selection
echo "<h1>Manual Attendance Selection Test</h1>";

// Test 1: Check if selectAttendanceType function exists
$camera_file = file_get_contents('attendance_camera.php');
if (strpos($camera_file, 'function selectAttendanceType') !== false) {
    echo "<p style='color: green;'>✓ selectAttendanceType function exists</p>";
} else {
    echo "<p style='color: red;'>✗ selectAttendanceType function missing</p>";
}

// Test 2: Check if attendance type buttons are added
if (strpos($camera_file, 'attendance-type-selector') !== false) {
    echo "<p style='color: green;'>✓ Attendance type selector buttons added</p>";
} else {
    echo "<p style='color: red;'>✗ Attendance type selector buttons missing</p>";
}

// Test 3: Check if automatic selection is removed
if (strpos($camera_file, 'setAttendanceTypeByTime') === false) {
    echo "<p style='color: green;'>✓ Automatic time-based selection removed</p>";
} else {
    echo "<p style='color: red;'>✗ Automatic time-based selection still exists</p>";
}

// Test 4: Check if manual selection is set as default
if (strpos($camera_file, "selectAttendanceType('clock_in')") !== false) {
    echo "<p style='color: green;'>✓ Manual selection set as default (Clock In)</p>";
} else {
    echo "<p style='color: red;'>✗ Manual selection not set as default</p>";
}

// Test 5: Check if watermark shows attendance type
if (strpos($camera_file, 'attendanceTypeText') !== false && 
    strpos($camera_file, 'attendanceTypeColor') !== false) {
    echo "<p style='color: green;'>✓ Watermark displays selected attendance type</p>";
} else {
    echo "<p style='color: red;'>✗ Watermark does not display attendance type</p>";
}

// Test 6: Check if Clock In button styling exists
if (strpos($camera_file, 'clock-in.active') !== false) {
    echo "<p style='color: green;'>✓ Clock In button styling added</p>";
} else {
    echo "<p style='color: red;'>✗ Clock In button styling missing</p>";
}

// Test 7: Check if Clock Out button styling exists
if (strpos($camera_file, 'clock-out.active') !== false) {
    echo "<p style='color: green;'>✓ Clock Out button styling added</p>";
} else {
    echo "<p style='color: red;'>✗ Clock Out button styling missing</p>";
}

echo "<h2>Test Results</h2>";
echo "<p>The attendance system has been updated with manual selection:</p>";
echo "<ul>";
echo "<li>Removed automatic clock-in/clock-out based on time</li>";
echo "<li>Added manual selection buttons for Clock In and Clock Out</li>";
echo "<li>Updated watermark to show selected attendance type</li>";
echo "<li>Default selection is Clock In</li>";
echo "<li>Users can now choose between Clock In and Clock Out at any time</li>";
echo "</ul>";

echo "<p><a href='attendance_camera.php'>Test Manual Selection</a> | ";
echo "<a href='attendance.php'>View Attendance Dashboard</a></p>";

echo "<h3>How to Test:</h3>";
echo "<ol>";
echo "<li>Go to <a href='attendance_camera.php'>Camera Attendance</a></li>";
echo "<li>You should see two buttons at the top: 'Clock In' (green) and 'Clock Out' (orange)</li>";
echo "<li>Click either button to select the attendance type</li>";
echo "<li>The watermark will update to show your selection</li>";
echo "<li>Capture a photo to record the attendance with your selected type</li>";
echo "</ol>";
?>
