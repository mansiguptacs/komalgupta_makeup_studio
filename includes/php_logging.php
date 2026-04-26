<?php
/**
 * Central app-level logging for hosts where PHP logs are inaccessible.
 */

if (!function_exists('kg_log_file_path')) {
    function kg_log_file_path() {
        $projectRoot = dirname(__DIR__);
        $logDir = $projectRoot . '/data/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        if (is_dir($logDir) && is_writable($logDir)) {
            return $logDir . '/app.log';
        }
        return $projectRoot . '/data/app.log';
    }
}

if (!function_exists('kg_log_level_name')) {
    function kg_log_level_name($severity) {
        $map = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED',
        ];
        return isset($map[$severity]) ? $map[$severity] : 'INFO';
    }
}

if (!function_exists('kg_log')) {
    function kg_log($level, $message, $context = []) {
        static $isWriting = false;
        if ($isWriting) {
            return;
        }
        $isWriting = true;

        $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper((string)$level) . '] ' . (string)$message;
        if (!empty($context)) {
            $json = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json !== false) {
                $line .= ' ' . $json;
            }
        }
        $line .= PHP_EOL;

        @file_put_contents(kg_log_file_path(), $line, FILE_APPEND);
        $isWriting = false;
    }
}

if (!function_exists('kg_configure_php_logging')) {
    function kg_configure_php_logging() {
        static $configured = false;
        if ($configured) {
            return;
        }
        $configured = true;

        // Keep native settings predictable, but do not rely on error_log destination.
        ini_set('log_errors', '1');
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            kg_log(
                kg_log_level_name($severity),
                (string)$message,
                ['file' => (string)$file, 'line' => (int)$line]
            );
            return true;
        });

        set_exception_handler(function ($e) {
            kg_log('EXCEPTION', $e->getMessage(), [
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            if (!headers_sent()) {
                http_response_code(500);
            }
        });

        register_shutdown_function(function () {
            $fatal = error_get_last();
            if (!is_array($fatal)) {
                return;
            }
            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            if (!in_array((int)$fatal['type'], $fatalTypes, true)) {
                return;
            }
            kg_log('FATAL', (string)$fatal['message'], [
                'file' => (string)$fatal['file'],
                'line' => (int)$fatal['line'],
                'type' => kg_log_level_name((int)$fatal['type']),
            ]);
        });
    }
}

kg_configure_php_logging();
