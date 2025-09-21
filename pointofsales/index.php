<?php
// Redirect to appropriate page based on login status
session_start();

if (isset($_SESSION['user_id'])) {
    // User is logged in, redirect to dashboard
    header('Location: dashboard.php');
    exit;
} else {
    // User is not logged in, redirect to login page
    header('Location: index.html');
    exit;
}
?>
