<?php
// This script is protected by the functions file
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config.php';
require_admin_login();

// Only developers should be able to run this
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'developer') {
    die("Access Denied.");
}

$db_host = DB_HOST;
$db_name = DB_NAME;
$db_user = DB_USER;
$db_pass = DB_PASS;

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
$command = sprintf(
    'mysqldump --host=%s --port=%d --user=%s --password=%s --single-transaction --skip-lock-tables %s',
    escapeshellarg($db_host), DB_PORT, escapeshellarg($db_user), escapeshellarg($db_pass), escapeshellarg($db_name)
);
passthru($command, $return_var);

// Check if the command was successful
if ($return_var !== 0) {
    // If it fails, you can log an error, but don't output it here
    // as it would corrupt the download file.
    error_log("mysqldump command failed for database: {$db_name}");
}
