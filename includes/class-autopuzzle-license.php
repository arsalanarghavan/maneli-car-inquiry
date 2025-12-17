<?php
/**
 * AutoPuzzle License Management
 *
 * Handles license validation, activation, and checking
 *
 * @package Autopuzzle_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_License {

    private static $instance = null;
    private $license_key = null;
    private $license_status = 'inactive';
    private $expiry_date = null;
    private $is_demo = false;
    
    /**
     * Cache flag to prevent multiple license checks in same request
     */
    private $license_checked = false;
    private $license_check_result = null;
    
    /**
     * Cache for table_exists() to prevent duplicate SHOW TABLES queries
     */
    private $table_exists_cache = null;
    
    /**
     * Static license server URLs - try both
     */
    private static $license_server_urls = [
        'https://puzzlingco.ir',
        'https://puzzlingco.com'
    ];

    /**
     * Get instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Determine if SSL verification should be enabled
     * In production, always verify SSL. In development, allow bypass if needed.
     */
    private function get_ssl_verify_setting() {
        // In production environment, always verify SSL
        if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'production') {
            return true;
        }
        
        // For development/local, check for explicit setting
        // Default to true for security, but allow override via filter
        return apply_filters('autopuzzle_license_verify_ssl', defined('WP_DEBUG') ? false : true);
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Load license data (with table check and error handling)
        $this->load_license_data();
        
        // Don't check license status immediately - use lazy loading
        // This prevents multiple remote API calls on every page load
        
        // Register custom cron schedule for every 12 hours
        add_filter('cron_schedules', [$this, 'add_custom_cron_schedule']);
        
        // Schedule license check every 12 hours
        add_action('autopuzzle_license_check', [$this, 'daily_license_check']);
        if (!wp_next_scheduled('autopuzzle_license_check')) {
            wp_schedule_event(time(), 'autopuzzle_every_12_hours', 'autopuzzle_license_check');
        }
        
        // Listen for real-time license deactivation webhook
        add_action('wp_ajax_nopriv_maneli_license_webhook', [$this, 'handle_license_webhook']);
        add_action('wp_ajax_maneli_license_webhook', [$this, 'handle_license_webhook']);
        add_action('wp_ajax_nopriv_autopuzzle_license_webhook', [$this, 'handle_license_webhook']);
        add_action('wp_ajax_autopuzzle_license_webhook', [$this, 'handle_license_webhook']);
    }

    /**
     * Add custom cron schedule for every 12 hours
     */
    public function add_custom_cron_schedule($schedules) {
        // Use simple string to avoid translation loading before init hook
        // This prevents WordPress 6.7+ warning about early translation loading
        $schedules['autopuzzle_every_12_hours'] = [
            'interval' => 12 * 60 * 60, // 12 hours in seconds
            'display' => 'Every 12 hours'
        ];
        return $schedules;
    }
    
    /**
     * Handle real-time license deactivation webhook from PuzzlingCRM
     */
    public function handle_license_webhook() {
        // Verify request - domain is the license
        $domain = isset($_POST['domain']) ? sanitize_text_field($_POST['domain']) : '';
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        
        if (empty($domain) || empty($action)) {
            wp_send_json_error(['message' => 'Invalid request']);
        }
        
        // Load current license
        $this->load_license_data();
        
        // Get current domain
        $current_domain = $this->get_current_domain();
        
        // Check if domain matches
        if ($current_domain !== $domain) {
            wp_send_json_error(['message' => 'Domain mismatch']);
        }
        
        // Handle action
        if ($action === 'deactivate' || $action === 'cancel') {
            $this->update_license_status('inactive');
            $this->license_status = 'inactive';
            $this->deactivate_plugin();
            wp_send_json_success(['message' => 'License deactivated']);
        } elseif ($action === 'activate') {
            $this->update_license_status('active');
            $this->license_status = 'active';
            wp_send_json_success(['message' => 'License activated']);
        }
        
        wp_send_json_error(['message' => 'Unknown action']);
    }
    
    /**
     * Check if license table exists
     * Uses caching to prevent duplicate SHOW TABLES queries in same request
     * 
     * @return bool True if table exists, false otherwise
     */
    private function table_exists() {
        // Return cached result if available
        if ($this->table_exists_cache !== null) {
            return $this->table_exists_cache;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'autopuzzle_license';
        $result = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
        $this->table_exists_cache = ($result === $table_name);
        return $this->table_exists_cache;
    }
    
    /**
     * Ensure license table exists, create if needed
     * 
     * @return bool True if table exists or was created, false on error
     */
    private function ensure_table_exists() {
        if ($this->table_exists()) {
            return true;
        }
        
        // Try to create table if it doesn't exist
        if (class_exists('Autopuzzle_Activator')) {
            Autopuzzle_Activator::ensure_tables();
            // Clear cache and check again after table creation attempt
            $this->table_exists_cache = null;
            return $this->table_exists();
        }
        
        return false;
    }
    
    /**
     * Get working license server URL (try both URLs)
     */
    private function get_license_server_url() {
        // Try both URLs and return the first one that works
        foreach (self::$license_server_urls as $url) {
            // Quick connectivity check
            $test_url = trailingslashit($url) . 'wp-json/puzzlingcrm/v1/license/check';
            $response = wp_remote_get($test_url, [
                'timeout' => 3,
                'sslverify' => $this->get_ssl_verify_setting(),
            ]);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) < 500) {
                return $url;
            }
        }
        
        // If both fail, return first one as fallback
        return self::$license_server_urls[0];
    }

    /**
     * Load license data from database
     */
    private function load_license_data() {
        global $wpdb;
        
        // Ensure table exists before querying
        if (!$this->ensure_table_exists()) {
            // Table doesn't exist and couldn't be created
            // Use default values
            $this->license_key = null;
            $this->license_status = 'inactive';
            $this->expiry_date = null;
            $this->is_demo = false;
            return;
        }
        
        $table_name = $wpdb->prefix . 'autopuzzle_license';
        $license = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1", ARRAY_A);
        
        // Check for database errors
        if (!empty($wpdb->last_error)) {
            // Database error occurred, use default values
            $this->license_key = null;
            $this->license_status = 'inactive';
            $this->expiry_date = null;
            $this->is_demo = false;
            return;
        }
        
        if ($license) {
            $this->license_key = $license['license_key'];
            $this->license_status = $license['status'];
            $this->expiry_date = $license['expiry_date'];
            $this->is_demo = (bool)$license['is_demo_mode'];
        }
    }

    /**
     * Check license status
     * Uses caching to prevent multiple checks in same request
     */
    public function check_license_status() {
        // Return cached result if already checked in this request
        if ($this->license_checked && $this->license_check_result !== null) {
            return $this->license_check_result;
        }
        
        // Domain is the license - check if domain exists
        $domain = $this->get_current_domain();
        if (empty($domain)) {
            $this->license_status = 'inactive';
            $this->license_checked = true;
            $this->license_check_result = false;
            return false;
        }

        // Check expiry date
        if ($this->expiry_date && strtotime($this->expiry_date) < time()) {
            $this->license_status = 'expired';
            $this->update_license_status('expired');
            $this->deactivate_plugin();
            $this->license_checked = true;
            $this->license_check_result = false;
            return false;
        }

        // Check with remote server (try both URLs)
        $remote_check = $this->remote_license_check();
        if ($remote_check === false) {
            // If connection failed, use local status
            $result = $this->license_status === 'active';
            $this->license_checked = true;
            $this->license_check_result = $result;
            return $result;
        }
        
        $this->license_checked = true;
        $this->license_check_result = $remote_check;
        return $remote_check;
    }

    /**
     * Remote license check via REST API
     * 
     * این متد با سرور PuzzlingCRM ارتباط برقرار می‌کند تا لایسنس را بررسی کند.
     * دامنه خودش لایسنس است - فقط دامنه را می‌فرستیم.
     * هر دو URL (puzzlingco.ir و puzzlingco.com) را امتحان می‌کند.
     */
    private function remote_license_check() {
        $domain = $this->get_current_domain();
        
        if (empty($domain)) {
            return false;
        }
        
        // Try both URLs - use first one that works
        foreach (self::$license_server_urls as $server_url) {
            $api_url = trailingslashit($server_url) . 'wp-json/puzzlingcrm/v1/license/check';
            
            $response = wp_remote_post($api_url, [
                'body' => [
                    'domain' => $domain,
                ],
                'timeout' => 10,
                'sslverify' => $this->get_ssl_verify_setting(),
            ]);

            // If connection error, try next URL
            if (is_wp_error($response)) {
                continue;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            // If we got a valid response (even if invalid license), use this server
            if (isset($body['status'])) {
                if ($body['status'] === 'valid') {
                    // Update expiry date from server
                    if (isset($body['expiry_date'])) {
                        $this->update_license_data(['expiry_date' => $body['expiry_date']]);
                        $this->expiry_date = $body['expiry_date'];
                    }
                    $this->update_license_status('active');
                    $this->license_status = 'active';
                    return true;
                } else {
                    // License is invalid/expired/etc
                    $status = $body['status'];
                    $this->update_license_status($status);
                    $this->license_status = $status;
                    $this->deactivate_plugin();
                    return false;
                }
            }
        }
        
        // If both URLs failed, return false
        return false;
    }

    /**
     * Activate license (domain is the license - no license key needed)
     */
    public function activate_license() {
        $domain = $this->get_current_domain();

        if (empty($domain)) {
            return [
                'success' => false,
                'message' => __('Domain not found', 'autopuzzle')
            ];
        }

        // Try both URLs - use first one that works
        foreach (self::$license_server_urls as $server_url) {
            $api_url = trailingslashit($server_url) . 'wp-json/puzzlingcrm/v1/license/activate';
            
            $response = wp_remote_post($api_url, [
                'body' => [
                    'domain' => $domain,
                ],
                'timeout' => 15,
                'sslverify' => $this->get_ssl_verify_setting(),
            ]);

            // If connection error, try next URL
            if (is_wp_error($response)) {
                continue;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            // If we got a valid response, use this server
            if (isset($body['status'])) {
                if ($body['status'] === 'success') {
                    // Save license data (domain is the license key)
                    $this->save_license_data([
                        'license_key' => $domain, // Domain is stored as license_key for backward compatibility
                        'domain' => $domain,
                        'status' => 'active',
                        'expiry_date' => $body['expiry_date'] ?? null,
                        'activated_at' => current_time('mysql'),
                        'is_demo_mode' => 0,
                    ]);

                    $this->load_license_data();

                    return [
                        'success' => true,
                        'message' => __('License activated successfully', 'autopuzzle'),
                        'expiry_date' => $body['expiry_date'] ?? null
                    ];
                } else {
                    // License activation failed
                    return [
                        'success' => false,
                        'message' => $body['message'] ?? __('License is not valid', 'autopuzzle')
                    ];
                }
            }
        }
        
        // If both URLs failed
        return [
            'success' => false,
            'message' => __('Error connecting to license server. Please try again.', 'autopuzzle')
        ];
    }

    /**
     * Deactivate license
     */
    public function deactivate_license() {
        $this->update_license_status('inactive');
        $this->license_status = 'inactive';
        $this->deactivate_plugin();
    }

    /**
     * Deactivate plugin functionality
     */
    private function deactivate_plugin() {
        update_option('autopuzzle_plugin_disabled', true);
    }

    /**
     * Save license data to database
     */
    private function save_license_data($data) {
        global $wpdb;
        
        // Ensure table exists before saving
        if (!$this->ensure_table_exists()) {
            // Table doesn't exist and couldn't be created
            return false;
        }
        
        $table_name = $wpdb->prefix . 'autopuzzle_license';
        
        // Check if license exists
        $existing = $wpdb->get_row("SELECT id FROM $table_name LIMIT 1", ARRAY_A);
        
        // Check for database errors
        if (!empty($wpdb->last_error)) {
            return false;
        }
        
        if ($existing) {
            // Update
            $result = $wpdb->update(
                $table_name,
                [
                    'license_key' => sanitize_text_field($data['license_key']),
                    'domain' => sanitize_text_field($data['domain']),
                    'status' => sanitize_text_field($data['status']),
                    'expiry_date' => $data['expiry_date'] ? sanitize_text_field($data['expiry_date']) : null,
                    'activated_at' => $data['activated_at'] ? sanitize_text_field($data['activated_at']) : null,
                    'is_demo_mode' => isset($data['is_demo_mode']) ? (int)$data['is_demo_mode'] : 0,
                    'last_check' => current_time('mysql'),
                ],
                ['id' => $existing['id']],
                ['%s', '%s', '%s', '%s', '%s', '%d', '%s'],
                ['%d']
            );
            
            // Check for database errors
            if (!empty($wpdb->last_error)) {
                return false;
            }
        } else {
            // Insert
            $result = $wpdb->insert(
                $table_name,
                [
                    'license_key' => sanitize_text_field($data['license_key']),
                    'domain' => sanitize_text_field($data['domain']),
                    'status' => sanitize_text_field($data['status']),
                    'expiry_date' => $data['expiry_date'] ? sanitize_text_field($data['expiry_date']) : null,
                    'activated_at' => $data['activated_at'] ? sanitize_text_field($data['activated_at']) : null,
                    'is_demo_mode' => isset($data['is_demo_mode']) ? (int)$data['is_demo_mode'] : 0,
                    'last_check' => current_time('mysql'),
                ],
                ['%s', '%s', '%s', '%s', '%s', '%d', '%s']
            );
            
            // Check for database errors
            if (!empty($wpdb->last_error)) {
                return false;
            }
        }
        
        return $result !== false;
    }

    /**
     * Update license status
     */
    private function update_license_status($status) {
        global $wpdb;
        
        // Ensure table exists before updating
        if (!$this->ensure_table_exists()) {
            // Table doesn't exist, just update in-memory status
            $this->license_status = $status;
            return;
        }
        
        $table_name = $wpdb->prefix . 'autopuzzle_license';
        
        // Check if any record exists before updating
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table_name LIMIT 1");
        
        if (empty($existing) || $existing == 0) {
            // No record exists, just update in-memory status
            $this->license_status = $status;
            return;
        }
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET status = %s, last_check = %s",
            $status,
            current_time('mysql')
        ));
        
        // Check for database errors
        if (!empty($wpdb->last_error)) {
            // Error occurred, but still update in-memory status
            $this->license_status = $status;
            return;
        }
        
        $this->license_status = $status;
    }

    /**
     * Update license data
     */
    private function update_license_data($data) {
        global $wpdb;
        
        // Ensure table exists before updating
        if (!$this->ensure_table_exists()) {
            // Table doesn't exist, just update in-memory values
            if (isset($data['expiry_date'])) {
                $this->expiry_date = $data['expiry_date'];
            }
            if (isset($data['status'])) {
                $this->license_status = $data['status'];
            }
            return;
        }
        
        $table_name = $wpdb->prefix . 'autopuzzle_license';
        $update_data = [];
        $format = [];
        
        if (isset($data['expiry_date'])) {
            $update_data['expiry_date'] = $data['expiry_date'] ? sanitize_text_field($data['expiry_date']) : null;
            $format[] = '%s';
        }
        
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $format[] = '%s';
        }
        
        if (!empty($update_data)) {
            $update_data['last_check'] = current_time('mysql');
            $format[] = '%s';
            
            // Check if any record exists before updating
            $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table_name LIMIT 1");
            
            if (empty($existing) || $existing == 0) {
                // No record exists, just update in-memory values
                if (isset($data['expiry_date'])) {
                    $this->expiry_date = $data['expiry_date'];
                }
                if (isset($data['status'])) {
                    $this->license_status = $data['status'];
                }
                return;
            }
            
            $result = $wpdb->update(
                $table_name,
                $update_data,
                [],
                $format,
                []
            );
            
            // Check for database errors
            if (!empty($wpdb->last_error)) {
                // Error occurred, but still update in-memory values
                if (isset($data['expiry_date'])) {
                    $this->expiry_date = $data['expiry_date'];
                }
                if (isset($data['status'])) {
                    $this->license_status = $data['status'];
                }
                return;
            }
            
            // Update in-memory values on success
            if (isset($data['expiry_date'])) {
                $this->expiry_date = $data['expiry_date'];
            }
            if (isset($data['status'])) {
                $this->license_status = $data['status'];
            }
        }
    }

    /**
     * Get current domain
     * 
     * این متد دامنه فعلی سایت را از تنظیمات وردپرس می‌گیرد:
     * - از home_url() استفاده می‌کند (که در تنظیمات WordPress > Settings > General تنظیم شده)
     * - www را حذف می‌کند
     * - فقط hostname را برمی‌گرداند (بدون http/https)
     * 
     * مثال: https://www.example.com -> example.com
     */
    private function get_current_domain() {
        // دریافت URL کامل از تنظیمات وردپرس (Settings > General > WordPress Address)
        $domain = home_url();
        
        // استخراج فقط hostname (بدون protocol و path)
        $domain = parse_url($domain, PHP_URL_HOST);
        
        // حذف www از ابتدای دامنه
        $domain = preg_replace('/^www\./', '', $domain);
        
        return $domain;
    }

    /**
     * License check (cron job - runs every 30 minutes)
     */
    public function daily_license_check() {
        $this->check_license_status();
    }

    /**
     * Check if license is active
     * Uses cached status to avoid multiple checks
     */
    public function is_license_active() {
        // If already checked in this request, use cached result
        if ($this->license_checked) {
            return $this->license_status === 'active' && ($this->license_check_result === true);
        }
        
        // Otherwise check once and cache the result
        $check_result = $this->check_license_status();
        return $this->license_status === 'active' && $check_result;
    }

    /**
     * Check if in demo mode
     */
    public function is_demo_mode() {
        return $this->is_demo === true;
    }

    /**
     * Get license status
     */
    public function get_license_status() {
        return [
            'status' => $this->license_status,
            'expiry_date' => $this->expiry_date,
            'is_demo' => $this->is_demo,
            'is_active' => $this->is_license_active()
        ];
    }

    /**
     * Set license server URL
     */
    public function set_license_server_url($url) {
        $this->license_server_url = $url;
        update_option('autopuzzle_license_server_url', $url);
    }
}

