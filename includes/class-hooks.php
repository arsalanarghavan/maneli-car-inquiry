<?php
/**
 * Manages general WordPress hooks to modify core behaviors.
 * This includes user redirects, query modifications, and price visibility.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Hooks {

    public function __construct() {
        // User profile related hooks
        add_action('user_register', [$this, 'update_display_name_on_event'], 20, 1);
        add_action('profile_update', [$this, 'update_display_name_on_event'], 20, 1);
        add_action('init', [$this, 'run_once_update_all_display_names']);

        // Admin and frontend behavior hooks
        add_action('admin_init', [$this, 'redirect_non_admins_from_backend']);
        add_action('pre_get_posts', [$this, 'modify_product_query_for_customers']);
        add_action('wp', [$this, 'conditionally_hide_prices']);
        
        // Hide disabled products completely (high priority to override WooCommerce defaults)
        add_filter('woocommerce_product_is_visible', [$this, 'hide_disabled_products'], 10, 2);
        add_filter('woocommerce_product_is_purchasable', [$this, 'hide_disabled_products'], 10, 2);
        add_filter('posts_clauses', [$this, 'exclude_disabled_products_from_query'], 10, 2);
        // Also exclude from WooCommerce product query hooks
        add_filter('woocommerce_product_query', [$this, 'exclude_disabled_products_from_wc_query'], 10, 1);
        
        // Show "ناموجود" text for unavailable products
        add_filter('woocommerce_get_price_html', [$this, 'replace_price_for_unavailable'], 10, 2);
        
        // Schedule followup notification cron job
        add_action('maneli_daily_followup_notifications', [$this, 'send_followup_notifications']);
        add_action('init', [$this, 'schedule_followup_notifications_cron']);
        
        // Notification center cron jobs
        add_action('maneli_send_meeting_reminders', [$this, 'send_meeting_reminders']);
        add_action('maneli_process_scheduled_notifications', [$this, 'process_scheduled_notifications']);
        
    }

    /**
     * Updates a user's display name based on their first and last name.
     * Hooked to 'user_register' and 'profile_update'.
     *
     * @param int $user_id The ID of the user being updated or registered.
     */
    public function update_display_name_on_event($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $first_name = $user->first_name;
        $last_name = $user->last_name;
        $display_name = trim($first_name . ' ' . $last_name);

        // Update only if the new display name is not empty and is different from the current one.
        if (!empty($display_name) && $user->display_name !== $display_name) {
            wp_update_user([
                'ID'           => $user_id,
                'display_name' => $display_name,
            ]);
        }
    }

    /**
     * One-time function to update display names for all existing users.
     * This helps normalize data for users created before the plugin was activated.
     */
    public function run_once_update_all_display_names() {
        if (get_option('maneli_display_names_updated_v2') === 'yes') {
            return;
        }

        $users = get_users(['fields' => 'ID']);
        foreach ($users as $user_id) {
            $this->update_display_name_on_event($user_id);
        }
        
        update_option('maneli_display_names_updated_v2', 'yes');
    }

    /**
     * Redirects non-administrator users from the WordPress backend (/wp-admin) to the frontend dashboard.
     * تنها مدیرکل (Administrator) به پیشخوان دسترسی دارد.
     * همه نقش‌های دیگر (maneli_admin, maneli_expert, customer) به داشبورد frontend هدایت می‌شوند.
     */
    public function redirect_non_admins_from_backend() {
        // Do not redirect for AJAX, Cron, or admin-post.php requests
        if (wp_doing_ajax() || wp_doing_cron() || (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === 'admin-post.php')) {
            return;
        }

        // Get current user
        $current_user = wp_get_current_user();
        
        // Check if user is logged in and in wp-admin
        if (!is_admin() || empty($current_user->ID)) {
            return;
        }

        // Get user roles
        $user_roles = (array) $current_user->roles;
        
        // فقط مدیرکل (Administrator) به wp-admin دسترسی دارد
        // اگر کاربر Administrator نیست، به داشبورد frontend هدایت شود
        if (!in_array('administrator', $user_roles, true)) {
            wp_redirect(home_url('/dashboard/'));
            exit;
        }
    }

    /**
     * Modifies the main product query for non-admin users on the frontend
     * to exclude products with the status 'disabled'.
     *
     * @param WP_Query $query The main WordPress query object.
     */
    public function modify_product_query_for_customers($query) {
        // Only run on the frontend, for product queries, and for non-privileged users.
        if (is_admin() || current_user_can('manage_maneli_inquiries')) {
            return;
        }
        
        // Check if this is a product query (main query or WooCommerce query)
        $post_type = $query->get('post_type');
        $is_product_query = false;
        
        if ($query->is_main_query() && (is_post_type_archive('product') || is_product_category() || is_product_tag() || is_product())) {
            $is_product_query = true;
        } elseif ($post_type === 'product' || (is_array($post_type) && in_array('product', $post_type))) {
            $is_product_query = true;
        }
        
        if (!$is_product_query) {
            return;
        }

        $meta_query = $query->get('meta_query') ?: [];
        if (!is_array($meta_query)) {
            $meta_query = [];
        }

        // Add a condition to exclude products with status 'disabled'.
        // This ensures both old products without the meta key and new, active products are shown.
        $meta_query['relation'] = 'OR';
        $meta_query[] = [
            'key'     => '_maneli_car_status',
            'value'   => 'disabled',
            'compare' => '!=',
        ];
        $meta_query[] = [
            'key'     => '_maneli_car_status',
            'compare' => 'NOT EXISTS',
        ];

        $query->set('meta_query', $meta_query);
    }
    
    /**
     * Hide disabled products from visibility (WooCommerce filter)
     */
    public function hide_disabled_products($visible, $product_id) {
        // Allow admins to see all products
        if (current_user_can('manage_maneli_inquiries')) {
            return $visible;
        }
        
        $car_status = get_post_meta($product_id, '_maneli_car_status', true);
        if ($car_status === 'disabled') {
            return false;
        }
        
        return $visible;
    }
    
    /**
     * Exclude disabled products from all queries (search, catalog, cross-sell, etc.)
     */
    public function exclude_disabled_products_from_query($clauses, $query) {
        // Only run on frontend, for non-admin users
        if (is_admin() || current_user_can('manage_maneli_inquiries')) {
            return $clauses;
        }
        
        global $wpdb;
        
        // Check if this is a product query
        $post_type = $query->get('post_type');
        $is_product_query = false;
        
        if (is_array($post_type)) {
            $is_product_query = in_array('product', $post_type);
        } elseif ($post_type === 'product' || $post_type === 'any') {
            $is_product_query = true;
        }
        
        if (!$is_product_query) {
            return $clauses;
        }
        
        // Exclude disabled products
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS maneli_status_meta ON {$wpdb->posts}.ID = maneli_status_meta.post_id AND maneli_status_meta.meta_key = '_maneli_car_status'";
        $clauses['where'] .= " AND (maneli_status_meta.meta_value != 'disabled' OR maneli_status_meta.meta_value IS NULL)";
        
        return $clauses;
    }
    
    /**
     * Exclude disabled products from WooCommerce product queries
     */
    public function exclude_disabled_products_from_wc_query($query) {
        // Only run on frontend, for non-admin users
        if (is_admin() || current_user_can('manage_maneli_inquiries')) {
            return;
        }
        
        $meta_query = $query->get('meta_query');
        if (!is_array($meta_query)) {
            $meta_query = [];
        }
        
        // Exclude disabled products
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key'     => '_maneli_car_status',
                'value'   => 'disabled',
                'compare' => '!=',
            ],
            [
                'key'     => '_maneli_car_status',
                'compare' => 'NOT EXISTS',
            ],
        ];
        
        $query->set('meta_query', $meta_query);
    }
    
    /**
     * Replace price with "ناموجود" text for unavailable products
     */
    public function replace_price_for_unavailable($price_html, $product) {
        if (!$product) {
            return $price_html;
        }
        
        $product_id = $product->get_id();
        $car_status = get_post_meta($product_id, '_maneli_car_status', true);
        
        if ($car_status === 'unavailable') {
            return '<span class="price unavailable-text">' . esc_html__('Unavailable', 'maneli-car-inquiry') . '</span>';
        }
        
        return $price_html;
    }

    /**
     * Conditionally removes price-related hooks based on plugin settings.
     * This effectively hides prices from non-admin users if the setting is enabled.
     */
    public function conditionally_hide_prices() {
        $options = get_option('maneli_inquiry_all_options', []);
        $is_price_hidden = !empty($options['hide_prices_for_customers']) && $options['hide_prices_for_customers'] == '1';

        if ($is_price_hidden && !current_user_can('manage_maneli_inquiries')) {
            // Remove prices from shop loop and single product pages
            remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
            
            // Filter the price HTML to return an empty string everywhere else
            // But first check for unavailable status to show "ناموجود"
            add_filter('woocommerce_get_price_html', [$this, 'hide_prices_or_show_unavailable'], 100, 2);
        }
    }
    
    /**
     * Hide prices completely if setting is enabled, except for unavailable products which show "ناموجود"
     */
    public function hide_prices_or_show_unavailable($price_html, $product) {
        if (!$product) {
            return $price_html;
        }
        
        $product_id = $product->get_id();
        $car_status = get_post_meta($product_id, '_maneli_car_status', true);
        
        // Show "ناموجود" for unavailable products
        if ($car_status === 'unavailable') {
            return '<span class="price unavailable-text">' . esc_html__('Unavailable', 'maneli-car-inquiry') . '</span>';
        }
        
        // Hide prices for all other products
        return '';
    }

    /**
     * Schedule the daily followup notifications cron job
     */
    public function schedule_followup_notifications_cron() {
        if (!wp_next_scheduled('maneli_daily_followup_notifications')) {
            // Schedule for 9 AM every day
            wp_schedule_event(time(), 'daily', 'maneli_daily_followup_notifications');
        }
    }

    /**
     * Send followup reminder notifications
     * Called daily by WordPress cron
     */
    public function send_followup_notifications() {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        $count = Maneli_Notification_Handler::schedule_followup_notifications();
        
        // Log the result
        error_log(sprintf('Maneli Followup Notifications: %d notifications created', $count));
    }

    /**
     * Send meeting reminders
     * Called hourly by WordPress cron
     */
    public function send_meeting_reminders() {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-center-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-database.php';
        
        $options = get_option('maneli_inquiry_all_options', []);
        
        // Get reminder settings - parse comma-separated values
        $hours_before_raw = $options['meeting_reminder_hours'] ?? '2,6,24';
        if (is_array($hours_before_raw)) {
            $hours_before = $hours_before_raw;
        } else {
            $hours_before = array_filter(array_map('intval', explode(',', $hours_before_raw)));
            if (empty($hours_before)) {
                $hours_before = [2, 6, 24];
            }
        }
        
        $days_before_raw = $options['meeting_reminder_days'] ?? '1';
        if (is_array($days_before_raw)) {
            $days_before = $days_before_raw;
        } else {
            $days_before = array_filter(array_map('intval', explode(',', $days_before_raw)));
            if (empty($days_before)) {
                $days_before = [1];
            }
        }
        
        // Get active channels
        $channels = [];
        if (!empty($options['meeting_reminder_sms_enabled'])) {
            $channels[] = 'sms';
        }
        if (!empty($options['meeting_reminder_telegram_enabled'])) {
            $channels[] = 'telegram';
        }
        if (!empty($options['meeting_reminder_email_enabled'])) {
            $channels[] = 'email';
        }
        if (!empty($options['meeting_reminder_notification_enabled'])) {
            $channels[] = 'notification';
        }
        
        if (empty($channels)) {
            return; // No channels enabled
        }
        
        // Get all upcoming meetings
        $now = current_time('mysql');
        $meetings = get_posts([
            'post_type' => 'maneli_meeting',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'meeting_start',
                    'value' => $now,
                    'compare' => '>='
                ]
            ],
            'orderby' => 'meta_value',
            'meta_key' => 'meeting_start',
            'order' => 'ASC'
        ]);
        
        $reminders_sent = 0;
        
        foreach ($meetings as $meeting) {
            $meeting_start = get_post_meta($meeting->ID, 'meeting_start', true);
            if (empty($meeting_start)) {
                continue;
            }
            
            $meeting_time = strtotime($meeting_start);
            $now_time = current_time('timestamp');
            $time_diff = $meeting_time - $now_time;
            
            // Check hours before
            foreach ($hours_before as $hours) {
                $hours_seconds = $hours * 3600;
                $reminder_time = $meeting_time - $hours_seconds;
                
                // Check if reminder should be sent now (within current hour)
                if ($now_time >= $reminder_time && $now_time < $reminder_time + 3600) {
                    // Check if already sent
                    $existing_reminder = Maneli_Database::get_notification_logs([
                        'related_id' => $meeting->ID,
                        'type' => 'sms', // Check for any type
                        'search' => 'meeting_reminder_' . $hours . 'h'
                    ]);
                    
                    if (empty($existing_reminder)) {
                        // Get customer info
                        $customer_id = get_post_field('post_author', $meeting->ID);
                        $customer = get_userdata($customer_id);
                        $customer_name = $customer ? $customer->display_name : '';
                        $customer_phone = get_user_meta($customer_id, 'mobile_number', true);
                        
                        // Get inquiry info
                        $inquiry_id = get_post_meta($meeting->ID, 'related_inquiry_id', true);
                        $car_name = '';
                        if ($inquiry_id) {
                            $product_id = get_post_meta($inquiry_id, 'product_id', true);
                            $car_name = $product_id ? get_the_title($product_id) : '';
                        }
                        
                        // Format meeting date
                        $meeting_date_formatted = date_i18n('Y/m/d H:i', $meeting_time);
                        
                        // Prepare message
                        $message = sprintf(
                            esc_html__('Reminder: You have a meeting scheduled for %s. Car: %s', 'maneli-car-inquiry'),
                            $meeting_date_formatted,
                            $car_name
                        );
                        
                        // Prepare recipients
                        $recipients = [];
                        if (!empty($customer_phone)) {
                            $recipients['sms'] = $customer_phone;
                        }
                        // Parse telegram chat IDs
                        $telegram_chat_ids = $options['telegram_chat_ids'] ?? '';
                        if (!empty($telegram_chat_ids)) {
                            if (is_array($telegram_chat_ids)) {
                                $recipients['telegram'] = $telegram_chat_ids;
                            } else {
                                // Parse comma-separated values
                                $chat_ids = array_filter(array_map('trim', explode(',', $telegram_chat_ids)));
                                if (!empty($chat_ids)) {
                                    $recipients['telegram'] = $chat_ids;
                                }
                            }
                        }
                        if ($customer && !empty($customer->user_email)) {
                            $recipients['email'] = $customer->user_email;
                        }
                        $recipients['notification'] = $customer_id;
                        
                        // Send reminders
                        foreach ($channels as $channel) {
                            if (isset($recipients[$channel])) {
                                Maneli_Notification_Center_Handler::send(
                                    $channel,
                                    $recipients[$channel],
                                    $message,
                                    [
                                        'related_id' => $meeting->ID,
                                        'title' => esc_html__('Meeting Reminder', 'maneli-car-inquiry'),
                                    ]
                                );
                            }
                        }
                        
                        $reminders_sent++;
                    }
                }
            }
            
            // Check days before
            foreach ($days_before as $days) {
                $days_seconds = $days * 86400;
                $reminder_time = $meeting_time - $days_seconds;
                $reminder_day = date('Y-m-d', $reminder_time);
                $today = date('Y-m-d', $now_time);
                
                // Check if reminder should be sent today
                if ($reminder_day === $today) {
                    // Check if already sent
                    $existing_reminder = Maneli_Database::get_notification_logs([
                        'related_id' => $meeting->ID,
                        'type' => 'sms',
                        'search' => 'meeting_reminder_' . $days . 'd'
                    ]);
                    
                    if (empty($existing_reminder)) {
                        // Similar logic as hours before
                        $customer_id = get_post_field('post_author', $meeting->ID);
                        $customer = get_userdata($customer_id);
                        $customer_name = $customer ? $customer->display_name : '';
                        $customer_phone = get_user_meta($customer_id, 'mobile_number', true);
                        
                        $inquiry_id = get_post_meta($meeting->ID, 'related_inquiry_id', true);
                        $car_name = '';
                        if ($inquiry_id) {
                            $product_id = get_post_meta($inquiry_id, 'product_id', true);
                            $car_name = $product_id ? get_the_title($product_id) : '';
                        }
                        
                        $meeting_date_formatted = date_i18n('Y/m/d H:i', $meeting_time);
                        
                        $message = sprintf(
                            esc_html__('Reminder: You have a meeting scheduled for %s (%d days). Car: %s', 'maneli-car-inquiry'),
                            $meeting_date_formatted,
                            $days,
                            $car_name
                        );
                        
                        $recipients = [];
                        if (!empty($customer_phone)) {
                            $recipients['sms'] = $customer_phone;
                        }
                        // Parse telegram chat IDs
                        $telegram_chat_ids = $options['telegram_chat_ids'] ?? '';
                        if (!empty($telegram_chat_ids)) {
                            if (is_array($telegram_chat_ids)) {
                                $recipients['telegram'] = $telegram_chat_ids;
                            } else {
                                // Parse comma-separated values
                                $chat_ids = array_filter(array_map('trim', explode(',', $telegram_chat_ids)));
                                if (!empty($chat_ids)) {
                                    $recipients['telegram'] = $chat_ids;
                                }
                            }
                        }
                        if ($customer && !empty($customer->user_email)) {
                            $recipients['email'] = $customer->user_email;
                        }
                        $recipients['notification'] = $customer_id;
                        
                        foreach ($channels as $channel) {
                            if (isset($recipients[$channel])) {
                                Maneli_Notification_Center_Handler::send(
                                    $channel,
                                    $recipients[$channel],
                                    $message,
                                    [
                                        'related_id' => $meeting->ID,
                                        'title' => esc_html__('Meeting Reminder', 'maneli-car-inquiry'),
                                    ]
                                );
                            }
                        }
                        
                        $reminders_sent++;
                    }
                }
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('Maneli Meeting Reminders: %d reminders sent', $reminders_sent));
        }
    }

    /**
     * Process scheduled notifications
     * Called hourly by WordPress cron
     */
    public function process_scheduled_notifications() {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-center-handler.php';
        
        $count = Maneli_Notification_Center_Handler::process_scheduled();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('Maneli Scheduled Notifications: %d notifications processed', $count));
        }
    }
    
}