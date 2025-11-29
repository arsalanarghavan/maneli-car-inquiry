<?php
/**
 * Logger class for system and user logs
 *
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Logger {

    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Cached session-aware user ID to avoid repeated lookups
     */
    private $active_user_id = null;

    /**
     * Cached plugin options for the current request
     *
     * @var array|null
     */
    private $options_cache = null;

    /**
     * Get instance
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Hook into error_log to capture PHP errors
        add_action('wp_loaded', array($this, 'setup_error_log_hook'));
    }

    /**
     * Retrieve the active user ID from WordPress or the plugin session
     */
    private function get_active_user_id() {
        if ($this->active_user_id !== null) {
            return $this->active_user_id ?: null;
        }

        $user_id = get_current_user_id();

        if (!$user_id) {
            // Attempt to use the plugin session if available
            if (class_exists('Maneli_Session')) {
                $session = new Maneli_Session();
                if (session_status() === PHP_SESSION_NONE) {
                    $session->start_session();
                }
                $session_user_id = $session->get_user_id();
                if ($session_user_id) {
                    $user_id = (int) $session_user_id;
                }
            } elseif (isset($_SESSION['maneli']['user_id'])) {
                $user_id = (int) $_SESSION['maneli']['user_id'];
            }
        }

        $this->active_user_id = $user_id ?: 0;

        return $this->active_user_id ?: null;
    }

    /**
     * Retrieve plugin logging options with simple caching (using optimized helper)
     */
    private function get_logging_options() {
        if ($this->options_cache === null) {
            $this->options_cache = Maneli_Options_Helper::get_all_options();
        }

        return $this->options_cache;
    }

    /**
     * Determine whether an option flag is enabled, with support for various truthy values.
     *
     * @param string $key
     * @param bool   $default
     *
     * @return bool
     */
    private function is_option_enabled($key, $default = false) {
        $options = $this->get_logging_options();

        if (!array_key_exists($key, $options)) {
            return $default;
        }

        $value = $options[$key];

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return intval($value) === 1;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, array('1', 'true', 'yes', 'on'), true);
    }

    /**
     * Setup error_log hook
     */
    public function setup_error_log_hook() {
        // Capture errors via error_get_last() and shutdown handler
        register_shutdown_function(array($this, 'capture_last_error'));
    }

    /**
     * Capture last error on shutdown
     */
    public function capture_last_error() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_WARNING, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_USER_ERROR, E_USER_WARNING])) {
            $this->log_error(
                $error['message'],
                array(
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'type' => $error['type'],
                )
            );
        }
    }

    /**
     * Check if logging is enabled
     */
    private function is_logging_enabled() {
        return $this->is_option_enabled('enable_logging_system', true);
    }

    /**
     * Check if specific log type is enabled
     */
    private function is_log_type_enabled($log_type) {
        if (!$this->is_logging_enabled()) {
            return false;
        }

        switch ($log_type) {
            case 'error':
                return $this->is_option_enabled('log_system_errors', true);
            case 'debug':
                return $this->is_option_enabled('log_system_debug', false);
            case 'console':
                return $this->is_option_enabled('log_console_messages', true);
            case 'button_error':
                return $this->is_option_enabled('log_button_errors', true);
            default:
                return true;
        }
    }

    /**
     * Check if user logging is enabled
     */
    private function is_user_logging_enabled() {
        return $this->is_option_enabled('enable_user_logging', true);
    }

    /**
     * Check if specific user action type is enabled
     */
    private function is_user_action_enabled($action_type) {
        if (!$this->is_user_logging_enabled()) {
            return false;
        }

        switch ($action_type) {
            case 'button_click':
                return $this->is_option_enabled('log_button_clicks', true);
            case 'form_submit':
                return $this->is_option_enabled('log_form_submissions', true);
            case 'ajax_call':
                return $this->is_option_enabled('log_ajax_calls', true);
            case 'page_view':
                return $this->is_option_enabled('log_page_views', false);
            default:
                return true;
        }
    }

    /**
     * Truncate message if exceeds limit
     */
    private function truncate_message($message) {
        $options = $this->get_logging_options();
        $max_size = isset($options['log_max_file_size']) ? intval($options['log_max_file_size']) : 5000;
        
        if ($max_size > 0 && strlen($message) > $max_size) {
            return substr($message, 0, $max_size) . '... [truncated]';
        }
        
        return $message;
    }

    /**
     * Log system log
     */
    public function log_system($message, $log_type = 'debug', $severity = 'info', $context = array(), $file = null, $line = null) {
        // Check if this log type is enabled
        if (!$this->is_log_type_enabled($log_type)) {
            return false;
        }
        
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if (!$file && isset($backtrace[0]['file'])) {
            $file = $backtrace[0]['file'];
        }
        if (!$line && isset($backtrace[0]['line'])) {
            $line = $backtrace[0]['line'];
        }

        // Truncate message if needed
        $message = $this->truncate_message($message);

        $user_id = $this->get_active_user_id();

        return Maneli_Database::log_system_log(array(
            'log_type' => $log_type,
            'severity' => $severity,
            'message' => $message,
            'context' => $context,
            'file' => $file,
            'line' => $line,
            'user_id' => $user_id,
        ));
    }

    /**
     * Log user action
     */
    public function log_user_action($action_type, $action_description, $target_type = null, $target_id = null, $metadata = array()) {
        // Check if user logging is enabled for this action type
        if (!$this->is_user_action_enabled($action_type)) {
            return false;
        }
        
        $user_id = $this->get_active_user_id();
        if (!$user_id) {
            return false;
        }

        // Truncate description if needed
        $action_description = $this->truncate_message($action_description);

        return Maneli_Database::log_user_log(array(
            'user_id' => $user_id,
            'action_type' => $action_type,
            'action_description' => $action_description,
            'target_type' => $target_type,
            'target_id' => $target_id,
            'metadata' => $metadata,
        ));
    }

    /**
     * Log error
     */
    public function log_error($message, $context = array(), $file = null, $line = null) {
        return $this->log_system($message, 'error', 'error', $context, $file, $line);
    }

    /**
     * Log debug
     */
    public function log_debug($message, $context = array(), $file = null, $line = null) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return false;
        }
        return $this->log_system($message, 'debug', 'info', $context, $file, $line);
    }

    /**
     * Log console (from JavaScript)
     */
    public function log_console($message, $log_type = 'console', $severity = 'info', $context = array()) {
        return $this->log_system($message, $log_type, $severity, $context);
    }

    /**
     * Log button error
     */
    public function log_button_error($button_id, $error_message, $context = array()) {
        return $this->log_system(
            sprintf('Button error: %s - %s', $button_id, $error_message),
            'button_error',
            'error',
            array_merge($context, array('button_id' => $button_id))
        );
    }

    /**
     * Get system logs
     */
    public function get_system_logs($args = array()) {
        return Maneli_Database::get_system_logs($args);
    }

    /**
     * Get system logs count
     */
    public function get_system_logs_count($args = array()) {
        return Maneli_Database::get_system_logs_count($args);
    }

    /**
     * Get user logs
     */
    public function get_user_logs($args = array()) {
        return Maneli_Database::get_user_logs($args);
    }

    /**
     * Get user logs count
     */
    public function get_user_logs_count($args = array()) {
        return Maneli_Database::get_user_logs_count($args);
    }

    /**
     * Cleanup old logs based on retention settings
     */
    public function cleanup_old_logs() {
        // Check if auto cleanup is enabled (using optimized helper)
        if (!Maneli_Options_Helper::is_option_enabled('enable_auto_log_cleanup', false)) {
            return 0;
        }
        
        // Get retention period (default 90 days, minimum 7)
        $retention_days = isset($options['log_retention_days']) ? max(7, intval($options['log_retention_days'])) : 90;
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        global $wpdb;
        $deleted_count = 0;
        
        // Delete old system logs
        $system_logs_table = $wpdb->prefix . 'maneli_system_logs';
        $deleted_system = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$system_logs_table} WHERE created_at < %s",
            $cutoff_date
        ));
        $deleted_count += $deleted_system ? $deleted_system : 0;
        
        // Delete old user logs
        $user_logs_table = $wpdb->prefix . 'maneli_user_logs';
        $deleted_user = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$user_logs_table} WHERE created_at < %s",
            $cutoff_date
        ));
        $deleted_count += $deleted_user ? $deleted_user : 0;
        
        return $deleted_count;
    }

    /**
     * Delete all system logs
     */
    public function delete_all_system_logs() {
        return Maneli_Database::delete_system_logs();
    }
}

