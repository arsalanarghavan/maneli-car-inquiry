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
        $options = get_option('maneli_inquiry_all_options', []);
        return !empty($options['enable_logging_system']) && $options['enable_logging_system'] == '1';
    }

    /**
     * Check if specific log type is enabled
     */
    private function is_log_type_enabled($log_type) {
        if (!$this->is_logging_enabled()) {
            return false;
        }
        
        $options = get_option('maneli_inquiry_all_options', []);
        
        switch ($log_type) {
            case 'error':
                return !empty($options['log_system_errors']) && $options['log_system_errors'] == '1';
            case 'debug':
                return !empty($options['log_system_debug']) && $options['log_system_debug'] == '1';
            case 'console':
                return !empty($options['log_console_messages']) && $options['log_console_messages'] == '1';
            case 'button_error':
                return !empty($options['log_button_errors']) && $options['log_button_errors'] == '1';
            default:
                return true;
        }
    }

    /**
     * Check if user logging is enabled
     */
    private function is_user_logging_enabled() {
        $options = get_option('maneli_inquiry_all_options', []);
        return !empty($options['enable_user_logging']) && $options['enable_user_logging'] == '1';
    }

    /**
     * Check if specific user action type is enabled
     */
    private function is_user_action_enabled($action_type) {
        if (!$this->is_user_logging_enabled()) {
            return false;
        }
        
        $options = get_option('maneli_inquiry_all_options', []);
        
        switch ($action_type) {
            case 'button_click':
                return !empty($options['log_button_clicks']) && $options['log_button_clicks'] == '1';
            case 'form_submit':
                return !empty($options['log_form_submissions']) && $options['log_form_submissions'] == '1';
            case 'ajax_call':
                return !empty($options['log_ajax_calls']) && $options['log_ajax_calls'] == '1';
            case 'page_view':
                return !empty($options['log_page_views']) && $options['log_page_views'] == '1';
            default:
                return true;
        }
    }

    /**
     * Truncate message if exceeds limit
     */
    private function truncate_message($message) {
        $options = get_option('maneli_inquiry_all_options', []);
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

        return Maneli_Database::log_system_log(array(
            'log_type' => $log_type,
            'severity' => $severity,
            'message' => $message,
            'context' => $context,
            'file' => $file,
            'line' => $line,
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
        
        $user_id = get_current_user_id();
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
        $options = get_option('maneli_inquiry_all_options', []);
        
        // Check if auto cleanup is enabled
        if (empty($options['enable_auto_log_cleanup']) || $options['enable_auto_log_cleanup'] != '1') {
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
}

