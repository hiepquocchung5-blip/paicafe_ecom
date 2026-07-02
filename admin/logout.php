<?php
require_once __DIR__ . '/../includes/functions.php';

// Unset only admin session variables to not interfere with user sessions
paicafe_clear_admin_session();
paicafe_clear_cookie('admin_remember_me', '/admin');
paicafe_clear_cookie('admin_username', '/admin');

header('Location: /login.php');
exit();
?>
