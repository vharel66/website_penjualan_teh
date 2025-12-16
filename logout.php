<?php
require_once 'config.php';

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page
$_SESSION['success'] = 'Anda telah berhasil logout!';
redirect('login.php');
?>
