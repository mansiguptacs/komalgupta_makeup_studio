<?php
/**
 * Admin-only utility endpoint to verify PHP error log writing.
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin('../login.php');

header('Content-Type: application/json; charset=utf-8');

$timestamp = date('c');
$message = '[health_log] Test warning generated at ' . $timestamp;

// Write one explicit line + one PHP warning to verify runtime error logging.
error_log($message);
trigger_error($message, E_USER_WARNING);

$errorLogPath = (string)ini_get('error_log');
$logExists = $errorLogPath !== '' ? file_exists($errorLogPath) : false;
$logWritable = $errorLogPath !== '' ? is_writable($errorLogPath) || (file_exists($errorLogPath) === false && is_writable(dirname($errorLogPath))) : false;

echo json_encode([
    'success' => true,
    'message' => 'Test log entry attempted.',
    'timestamp' => $timestamp,
    'error_log' => $errorLogPath,
    'log_exists' => $logExists,
    'log_writable' => $logWritable,
], JSON_UNESCAPED_SLASHES);
