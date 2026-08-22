<?php
require_once 'config.php';

// Destroy all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page
redirect('index.php');
?>
