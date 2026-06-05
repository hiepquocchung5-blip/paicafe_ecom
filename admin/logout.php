<?php
session_start();
// Unset only admin session variables to not interfere with user sessions
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
header('Location: /login.php');
exit();
?>