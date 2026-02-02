<?php
/**
 * Logger Class
 *
 * Provides comprehensive logging for the SemigApp plugin.
 *
 * @package SemigApp
 */

namespace SemigApp;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Logger
 *
 * Handles error logging, debug logging, and activity tracking.
 */
class Logger {

    /**
     * Instance
     *
     * @var Logger
     */
    private static $instance = null;

    /**
     * Log levels
     *
     * @var array
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    /**
     * Log directory
     *
     * @var string
     */
    private $log_dir;

    /**
     * Whether logging is enabled
     *
     * @var bool
     */
    private $enabled = true;

    /**
     * Minimum log level
     *
     * @var string
     */
    private $min_level = self::LEVEL_INFO;

    /**
     * Get instance
     *
     * @return Logger
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_dir = $upload_dir['basedir'] . '/semigapp-logs/';

        // Create log directory if it doesn't exist
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);

            // Add .htaccess to protect log files
            $htaccess = $this->log_dir . '.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, 'deny from all');
            }

            // Add index.php for additional protection
            $index = $this->log_dir . 'index.php';
            if (!file_exists($index)) {
                file_put_contents($index, '<?php // Silence is golden');
            }
        }

        // Set enabled based on settings
        $settings = Settings::get_instance();
        $this->enabled = (bool) $settings->get('general.enable_logging', true);

        // Set minimum level based on WP_DEBUG
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->min_level = self::LEVEL_DEBUG;
        }
    }

    /**
     * Log a debug message
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public function debug($message, $context = array()) {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log an info message
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public function info($message, $context = array()) {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log a warning message
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public function warning($message, $context = array()) {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log an error message
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public function error($message, $context = array()) {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log a critical message
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public function critical($message, $context = array()) {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Log an exception
     *
     * @param \Exception $exception The exception to log.
     * @param array      $context   Additional context.
     */
    public function exception($exception, $context = array()) {
        $context['exception'] = array(
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        );

        $this->error('Exception: ' . $exception->getMessage(), $context);
    }

    /**
     * Log a payment gateway event
     *
     * @param string $gateway Gateway ID.
     * @param string $event   Event name.
     * @param array  $data    Event data.
     */
    public function payment($gateway, $event, $data = array()) {
        $context = array(
            'gateway' => $gateway,
            'event' => $event,
            'data' => $this->sanitize_sensitive_data($data),
        );

        $this->log(self::LEVEL_INFO, "Payment [{$gateway}]: {$event}", $context, 'payments');
    }

    /**
     * Log an API request
     *
     * @param string $endpoint API endpoint.
     * @param string $method   HTTP method.
     * @param int    $status   Response status code.
     * @param float  $duration Request duration in seconds.
     * @param array  $context  Additional context.
     */
    public function api_request($endpoint, $method, $status, $duration, $context = array()) {
        $message = sprintf(
            'API %s %s - Status: %d - Duration: %.3fs',
            $method,
            $endpoint,
            $status,
            $duration
        );

        $context['api'] = array(
            'endpoint' => $endpoint,
            'method' => $method,
            'status' => $status,
            'duration' => $duration,
        );

        $level = $status >= 400 ? self::LEVEL_ERROR : self::LEVEL_INFO;
        $this->log($level, $message, $context, 'api');
    }

    /**
     * Main log method
     *
     * @param string $level   Log level.
     * @param string $message Log message.
     * @param array  $context Additional context.
     * @param string $channel Log channel/file prefix.
     */
    public function log($level, $message, $context = array(), $channel = 'general') {
        if (!$this->enabled) {
            return;
        }

        // Check if level is high enough
        if (!$this->should_log($level)) {
            return;
        }

        // Build log entry
        $entry = array(
            'timestamp' => current_time('c'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
        );

        // Add request context
        if (!empty($_SERVER['REQUEST_URI'])) {
            $entry['request'] = array(
                'uri' => sanitize_text_field($_SERVER['REQUEST_URI']),
                'method' => sanitize_text_field($_SERVER['REQUEST_METHOD'] ?? 'CLI'),
                'user_id' => get_current_user_id(),
            );
        }

        // Format log line
        $log_line = sprintf(
            "[%s] %s: %s %s\n",
            $entry['timestamp'],
            $entry['level'],
            $entry['message'],
            !empty($entry['context']) ? wp_json_encode($entry['context']) : ''
        );

        // Write to file
        $filename = $this->get_log_filename($channel);
        error_log($log_line, 3, $filename);

        // Also log to WordPress debug.log if WP_DEBUG_LOG is enabled
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && in_array($level, array(self::LEVEL_ERROR, self::LEVEL_CRITICAL))) {
            error_log('[SemigApp] ' . $log_line);
        }

        // Fire action for external integrations
        do_action('semigapp_logged', $level, $message, $context, $channel);
    }

    /**
     * Get log filename
     *
     * @param string $channel Log channel.
     * @return string Log filename.
     */
    private function get_log_filename($channel) {
        $date = current_time('Y-m-d');
        return $this->log_dir . sanitize_file_name("semigapp-{$channel}-{$date}.log");
    }

    /**
     * Check if we should log at this level
     *
     * @param string $level Log level.
     * @return bool Whether to log.
     */
    private function should_log($level) {
        $levels = array(
            self::LEVEL_DEBUG => 0,
            self::LEVEL_INFO => 1,
            self::LEVEL_WARNING => 2,
            self::LEVEL_ERROR => 3,
            self::LEVEL_CRITICAL => 4,
        );

        $current_level = $levels[$level] ?? 0;
        $min_level = $levels[$this->min_level] ?? 1;

        return $current_level >= $min_level;
    }

    /**
     * Sanitize sensitive data from logs
     *
     * @param array $data Data to sanitize.
     * @return array Sanitized data.
     */
    private function sanitize_sensitive_data($data) {
        $sensitive_keys = array(
            'password', 'pass', 'pwd',
            'secret', 'api_key', 'apikey', 'api_secret',
            'token', 'access_token', 'refresh_token',
            'card_number', 'cvv', 'cvc', 'card_cvc',
            'ssn', 'social_security',
            'authorization', 'auth',
        );

        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            $lower_key = strtolower($key);

            foreach ($sensitive_keys as $sensitive) {
                if (strpos($lower_key, $sensitive) !== false) {
                    $data[$key] = '[REDACTED]';
                    break;
                }
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitize_sensitive_data($value);
            }
        }

        return $data;
    }

    /**
     * Get log files
     *
     * @param int $days Number of days to retrieve.
     * @return array Log files.
     */
    public function get_log_files($days = 7) {
        $files = array();

        if (!is_dir($this->log_dir)) {
            return $files;
        }

        $log_files = glob($this->log_dir . '*.log');

        foreach ($log_files as $file) {
            $filename = basename($file);
            $modified = filemtime($file);

            // Only include files from the last N days
            if ((time() - $modified) > ($days * DAY_IN_SECONDS)) {
                continue;
            }

            $files[] = array(
                'filename' => $filename,
                'path' => $file,
                'size' => filesize($file),
                'modified' => $modified,
            );
        }

        // Sort by modified date, newest first
        usort($files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $files;
    }

    /**
     * Read log file contents
     *
     * @param string $filename Log filename.
     * @param int    $lines    Number of lines to read (from end).
     * @return string Log contents.
     */
    public function read_log($filename, $lines = 100) {
        $filepath = $this->log_dir . sanitize_file_name($filename);

        if (!file_exists($filepath)) {
            return '';
        }

        // Read last N lines
        $file = new \SplFileObject($filepath, 'r');
        $file->seek(PHP_INT_MAX);
        $total_lines = $file->key();

        $start_line = max(0, $total_lines - $lines);
        $output = array();

        $file->seek($start_line);
        while (!$file->eof()) {
            $output[] = $file->current();
            $file->next();
        }

        return implode('', $output);
    }

    /**
     * Clear old log files
     *
     * @param int $days Delete logs older than N days.
     * @return int Number of files deleted.
     */
    public function clear_old_logs($days = 30) {
        $deleted = 0;

        if (!is_dir($this->log_dir)) {
            return $deleted;
        }

        $log_files = glob($this->log_dir . '*.log');
        $cutoff = time() - ($days * DAY_IN_SECONDS);

        foreach ($log_files as $file) {
            if (filemtime($file) < $cutoff) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}
