<?php
// This script is protected by the functions file
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

// Only developers should be able to run this
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'developer') {
    die("Access Denied.");
}

// --- IMPORTANT: Database Credentials from your db_connect.php file ---
// You must manually enter the same credentials you use in your db_connect.php
$db_host = 'localhost';
$db_name = 'zmmlpszw_paicafe'; // Your database name
$db_user = 'zmmlpszw_filip';       // Your database username
$db_pass = '@fekgygn85cCM43';           // Your database password

// --- File Name for the Backup ---
$backup_file_name = $db_name . '_backup_' . date("Y-m-d_H-i-s") . '.sql';

// --- Set Headers for File Download ---
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $backup_file_name . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// --- Execute the mysqldump command ---
// passthru() executes the command and passes the output directly to the browser
// Note: shell_exec() must be enabled on your server for this to work.
$command = "mysqldump --host={$db_host} --user={$db_user} --password={$db_pass} {$db_name}";
passthru($command, $return_var);

// Check if the command was successful
if ($return_var !== 0) {
    // If it fails, you can log an error, but don't output it here
    // as it would corrupt the download file.
    error_log("mysqldump command failed for database: {$db_name}");
}