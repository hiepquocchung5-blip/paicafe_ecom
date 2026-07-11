<?php
require_once dirname(__DIR__) . '/config.php';

try {
    if (DB_NAME === '' || DB_USER === '') throw new RuntimeException('Database environment configuration is incomplete.');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    
} catch (Throwable $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    $message = (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : 'The service is temporarily unavailable. Please try again shortly.';
    die('<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Service unavailable</title><body style="font-family:system-ui;padding:3rem;text-align:center"><h1>We will be right back</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></body></html>');
}
