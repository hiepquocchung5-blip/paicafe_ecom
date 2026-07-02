<?php
require_once __DIR__ . '/includes/functions.php';

paicafe_destroy_session();
header('Location: /login.php');
exit();
?>
