<?php
require_once __DIR__ . '/includes/functions.php';

// Destroy the entire session, including shared admin/kitchen credentials.
paicafe_destroy_session();
// Redirect directly to the kitchen subdomain's root-level login page.
header('Location: /login.php');
exit();
?>
