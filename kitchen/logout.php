<?php
require_once __DIR__ . '/includes/functions.php';

// Destroy the entire session, including shared admin/kitchen credentials.
paicafe_destroy_session();
// Redirect specifically to the kitchen login page
header('Location: /index.php');
exit();
?>
