<?php
/**
 * Maneli License Management
 *
 * Handles license validation, activation, and checking
 *
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_License {

    private static $instance = null;
    private $license_key = null;
    private $license_status = 'inactive';
    private $expiry_date = null;
    private $is_demo = false;
    
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
     * Constructor
     */
    private function __construct() {
        $this->load_license_data();
        $this->check_license_status();
        
        // Register custom cron schedule for every 12 hours
        add_filter('cron_schedules', [$this, 'add_custom_cron_schedule']);
        
        // Schedule license check every 12 hours
        add_action('maneli_license_check', [$this, 'daily_license_check']);
        if (!wp_next_scheduled('maneli_license_check')) {
            wp_schedule_event(time(), 'maneli_every_12_hours', 'maneli_license_check');
        }
        
        // Listen for real-time license deactivation webhook
        add_action('wp_ajax_nopriv_maneli_license_webhook', [$this, 'handle_license_webhook']);
        add_action('wp_ajax_maneli_license_webhook', [$this, 'handle_license_webhook']);
    }

    /**
     * Add custom cron schedule for every 12 hours
     */
    public function add_custom_cron_schedule($schedules) {
        $schedules['maneli_every_12_hours'] = [
            'interval' => 12 * 60 * 60, // 12 hours in seconds
            'display' => __('Every 12 hours', 'maneli-car-inquiry')
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
     * Get working license server URL (try both URLs)
     */
    private function get_license_server_url() {
        // Try both URLs and return the first one that works
        foreach (self::$license_server_urls as $url) {
            // Quick connectivity check
            $test_url = trailingslashit($url) . 'wp-json/puzzlingcrm/v1/license/check';
            $response = wp_remote_get($test_url, [
                'timeout' => 3,
                'sslverify' => false,
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
        
        $table_name = $wpdb->prefix . 'maneli_license';
        $license = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1", ARRAY_A);
        
        if ($license) {
            $this->license_key = $license['license_key'];
            $this->license_status = $license['status'];
            $this->expiry_date = $license['expiry_date'];
            $this->is_demo = (bool)$license['is_demo_mode'];
        }
    }

    /**
     * Check license status
     */
    public function check_license_status() {
        // Domain is the license - check if domain exists
        $domain = $this->get_current_domain();
        if (empty($domain)) {
            $this->license_status = 'inactive';
            return false;
        }

        // Check expiry date
        if ($this->expiry_date && strtotime($this->expiry_date) < time()) {
            $this->license_status = 'expired';
            $this->update_license_status('expired');
            $this->deactivate_plugin();
            return false;
        }

        // Check with remote server (try both URLs)
        $remote_check = $this->remote_license_check();
        if ($remote_check === false) {
            // If connection failed, use local status
            return $this->license_status === 'active';
        }
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
                'sslverify' => false, // Set to true in production
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
                'message' => __('Domain not found', 'maneli-car-inquiry')
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
                'sslverify' => false,
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
                        'message' => __('License activated successfully', 'maneli-car-inquiry'),
                        'expiry_date' => $body['expiry_date'] ?? null
                    ];
                } else {
                    // License activation failed
                    return [
                        'success' => false,
                        'message' => $body['message'] ?? __('License is not valid', 'maneli-car-inquiry')
                    ];
                }
            }
        }
        
        // If both URLs failed
        return [
            'success' => false,
            'message' => __('Error connecting to license server. Please try again.', 'maneli-car-inquiry')
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
        update_option('maneli_plugin_disabled', true);
    }

    /**
     * Save license data to database
     */
    private function save_license_data($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'maneli_license';
        
        // Check if license exists
        $existing = $wpdb->get_row("SELECT id FROM $table_name LIMIT 1", ARRAY_A);
        
        if ($existing) {
            // Update
            $wpdb->update(
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
        } else {
            // Insert
            $wpdb->insert(
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
        }
    }

    /**
     * Update license status
     */
    private function update_license_status($status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'maneli_license';
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET status = %s, last_check = %s",
            $status,
            current_time('mysql')
        ));
        
        $this->license_status = $status;
    }

    /**
     * Update license data
     */
    private function update_license_data($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'maneli_license';
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
            
            $wpdb->update(
                $table_name,
                $update_data,
                [],
                $format,
                []
            );
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
     */
    public function is_license_active() {
        return $this->license_status === 'active' && $this->check_license_status();
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
        update_option('maneli_license_server_url', $url);
    }
}

