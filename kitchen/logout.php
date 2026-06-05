<?php
session_start();
// Destroy the entire session (logging them out from admin panel too, which is secure)
session_destroy();
// Redirect specifically to the kitchen login page
header('Location: /index.php');
exit();
?>