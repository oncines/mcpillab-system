<?php
require_once 'config.php';

echo "<h2>Test Button Click</h2>";

if (is_logged_in()) {
    echo "<p>✅ Logged in as: " . $_SESSION['full_name'] . "</p>";
    echo "<p>Role: " . $_SESSION['user_role'] . "</p>";
    
    // Test direct link
    echo "<h3>Direct Link Test</h3>";
    echo "<p><a href='attendance_history.php' style='background: blue; color: white; padding: 10px; text-decoration: none;'>Click here for Attendance History</a></p>";
    
    // Test button style
    echo "<h3>Button Style Test</h3>";
    echo "<button onclick='window.location.href=\"attendance_history.php\"' style='background: green; color: white; padding: 10px; border: none; cursor: pointer;'>Attendance History Button</button>";
    
    // Test navigation
    echo "<h3>Navigation Test</h3>";
    echo "<nav>";
    echo "<a href='dashboard.php' style='margin-right: 10px;'>Dashboard</a>";
    echo "<a href='attendance_history.php' style='margin-right: 10px;'>Attendance History</a>";
    echo "<a href='attendance_camera.php'>Attendance Camera</a>";
    echo "</nav>";
    
} else {
    echo "<p>❌ Not logged in</p>";
    echo "<p><a href='index.php'>Login first</a></p>";
}
?>

<script>
// Test JavaScript navigation
function goToHistory() {
    window.location.href = 'attendance_history.php';
}
</script>

<h3>JavaScript Test</h3>
<button onclick="goToHistory()" style="background: red; color: white; padding: 10px; border: none; cursor: pointer;">JS Attendance History</button>

<hr>
<p><a href="dashboard.php">Back to Dashboard</a></p>
