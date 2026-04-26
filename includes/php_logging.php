<?php
/**
 * Central PHP error logging configuration for hosts where platform logs are hidden.
 */

if (!function_exists('kg_configure_php_logging')) {
    function kg_configure_php_logging() {
        static $configured = false;
        if ($configured) {
            return;
        }
        $configured = true;

        $projectRoot = dirname(__DIR__);
        $logDir = $projectRoot . '/data/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $logFile = $logDir . '/php-error.log';
        if (!is_dir($logDir) || !is_writable($logDir)) {
            // Fallback to data/ if logs/ cannot be created on the host.
            $logFile = $projectRoot . '/data/php-error.log';
        }

        ini_set('log_errors', '1');
        ini_set('display_errors', '0');
        ini_set('error_log', $logFile);
        error_reporting(E_ALL);
    }
}

kg_configure_php_logging();
