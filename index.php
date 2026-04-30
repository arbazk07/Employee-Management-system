<?php
// index.php — Entry point, redirect to login or dashboard
require_once 'config.php';
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
