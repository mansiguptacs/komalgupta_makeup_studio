<?php
/**
 * Admin-only utility endpoint to verify PHP error log writing.
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin('../login.php');
if (!function_exists('kg_log')) {
    require_once __DIR__ . '/../includes/php_logging.php';
}

header('Content-Type: application/json; charset=utf-8');

$timestamp = date('c');
$message = '[health_log] Test log generated at ' . $timestamp;

// Write one explicit line + one handled warning to verify app logger wiring.
kg_log('INFO', $message, ['source' => 'secure/health_log.php']);
trigger_error($message, E_USER_WARNING);

$appLogPath = kg_log_file_path();
$nativeErrorLogPath = (string)ini_get('error_log');
$logExists = file_exists($appLogPath);
$logWritable = is_writable($appLogPath) || ($logExists === false && is_writable(dirname($appLogPath)));
$autoPrepend = (string)ini_get('auto_prepend_file');

echo json_encode([
    'success' => true,
    'message' => 'Test log entry attempted.',
    'timestamp' => $timestamp,
    'auto_prepend_file' => $autoPrepend,
    'app_log' => $appLogPath,
    'log_exists' => $logExists,
    'log_writable' => $logWritable,
    'native_error_log' => $nativeErrorLogPath,
], JSON_UNESCAPED_SLASHES);
