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
        
        // Visitor statistics tracking
        add_action('wp_footer', [$this, 'track_visitor_statistics'], 999);
        add_action('template_redirect', [$this, 'track_visitor_statistics_server_side'], 1);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_visitor_tracking_scripts']);

        // Frontend product title enhancements
        add_filter('the_title', [$this, 'append_year_badge_to_single_product_title'], 20, 2);
        add_action('wp_head', [$this, 'output_single_product_year_badge_style'], 50);
        
        // Log cleanup cron job
        add_action('maneli_cleanup_old_logs', [$this, 'cleanup_old_logs']);
        add_action('init', [$this, 'schedule_log_cleanup_cron']);
        
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
     * Appends the manufacturing year badge to the WooCommerce single product title.
     *
     * @param string $title   The original post title.
     * @param int    $post_id The post ID associated with the title.
     *
     * @return string
     */
    public function append_year_badge_to_single_product_title($title, $post_id) {
        if (is_admin()) {
            return $title;
        }

        if (!is_singular('product')) {
            return $title;
        }

        $queried_product_id = get_queried_object_id();
        if ((int) $post_id !== (int) $queried_product_id) {
            return $title;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'product') {
            return $title;
        }

        $manufacture_year_raw = get_post_meta($post_id, '_maneli_car_year', true);
        $manufacture_year_clean = $this->sanitize_product_year($manufacture_year_raw);

        if ($manufacture_year_clean === '') {
            return $title;
        }

        $manufacture_year_display = $this->convert_to_persian_digits($manufacture_year_clean);

        $badge_html = sprintf(
            ' <span class="maneli-year-badge maneli-year-badge--single">%s</span>',
            esc_html($manufacture_year_display)
        );

        return $title . $badge_html;
    }

    /**
     * Outputs inline styles for the product year badge on single product pages.
     *
     * @return void
     */
    public function output_single_product_year_badge_style() {
        if (!is_singular('product')) {
            return;
        }

        echo '<style id="maneli-single-product-year-badge">
.single-product .product_title.entry-title .maneli-year-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 14px;
    border-radius: 999px;
    background: linear-gradient(135deg, rgba(76,110,245,0.18), rgba(59,74,217,0.22));
    color: #2432a7;
    font-size: 0.9rem;
    font-weight: 700;
    line-height: 1.5;
    margin-inline-start: 0.5rem;
    box-shadow: 0 6px 16px rgba(59, 74, 217, 0.18);
}

.single-product .product_title.entry-title .maneli-year-badge--single {
    letter-spacing: 0.08em;
}
</style>';
    }

    /**
     * Normalizes the manufacturing year value.
     *
     * @param mixed $year Raw year value.
     *
     * @return string
     */
    private function sanitize_product_year($year) {
        if (!is_scalar($year)) {
            return '';
        }

        $year_string = trim((string) $year);

        if ($year_string === '') {
            return '';
        }

        $separators = ['،', '٬', ',', '/', '\\', '-', '_'];
        $year_string = str_replace($separators, '', $year_string);
        $year_string = preg_replace('/\s+/u', '', $year_string);
        $year_string = preg_replace('/[^\p{N}]/u', '', $year_string);

        return $year_string;
    }

    /**
     * Converts English digits to Persian digits.
     *
     * @param string $value Input numeric string.
     *
     * @return string
     */
    private function convert_to_persian_digits($value) {
        $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

        return str_replace($english_digits, $persian_digits, (string) $value);
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
        $is_price_hidden = Maneli_Options_Helper::is_option_enabled('hide_prices_for_customers', false);

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
        
        $options = Maneli_Options_Helper::get_all_options();
        
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
    
    /**
     * Track visitor statistics (client-side via JavaScript)
     * This injects JavaScript code to track visits via AJAX
     */
    public function track_visitor_statistics() {
        // Skip tracking on admin pages and dashboard pages
        if (is_admin() || get_query_var('maneli_dashboard')) {
            return;
        }
        
        // Skip dashboard pages by URL
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if (strpos($current_url, '/dashboard/') !== false || 
            strpos($current_url, '/wp-admin/') !== false || 
            strpos($current_url, '/wp-login.php') !== false ||
            strpos($current_url, '/admin/') !== false) {
            return;
        }
        
        // Check if visitor statistics is enabled (using optimized helper)
        if (!Maneli_Options_Helper::is_option_enabled('enable_visitor_statistics', false)) {
            return;
        }
        
        $page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $page_title = wp_get_document_title();
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        
        // Extract product ID if on product page
        $product_id = null;
        if (is_product()) {
            global $product;
            if ($product) {
                $product_id = $product->get_id();
            }
        }
        
        // Get nonce
        $nonce = wp_create_nonce('maneli_visitor_stats_nonce');
        $ajax_url = admin_url('admin-ajax.php');
        
        ?>
        <script type="text/javascript">
        (function() {
            if (typeof jQuery === 'undefined') return;
            
            jQuery(document).ready(function($) {
                // Track visit via AJAX
                $.ajax({
                    url: '<?php echo esc_js($ajax_url); ?>',
                    type: 'POST',
                    data: {
                        action: 'maneli_track_visit',
                        nonce: '<?php echo esc_js($nonce); ?>',
                        page_url: '<?php echo esc_js($page_url); ?>',
                        page_title: <?php echo json_encode($page_title); ?>,
                        referrer: <?php echo $referrer ? json_encode($referrer) : 'null'; ?>,
                        product_id: <?php echo $product_id ? intval($product_id) : 'null'; ?>
                    },
                    timeout: 5000
                }).fail(function() {
                    // Silently fail - don't interrupt user experience
                });
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Track visitor statistics (server-side)
     * This tracks visits directly via PHP (backup method)
     */
    public function track_visitor_statistics_server_side() {
        // Skip tracking on admin pages and dashboard pages
        if (is_admin() || get_query_var('maneli_dashboard')) {
            return;
        }
        
        // Skip AJAX requests to avoid double tracking
        if (wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Skip dashboard pages by URL
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if (strpos($current_url, '/dashboard/') !== false || 
            strpos($current_url, '/wp-admin/') !== false || 
            strpos($current_url, '/wp-login.php') !== false ||
            strpos($current_url, '/admin/') !== false) {
            return;
        }
        
        // Check if visitor statistics is enabled (using optimized helper)
        if (!Maneli_Options_Helper::is_option_enabled('enable_visitor_statistics', false)) {
            return;
        }
        
        // Rate limiting - use transients instead of session to avoid header issues
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        $track_key = 'maneli_tracked_' . md5($current_url . $ip_address);
        
        // Check if already tracked in last 5 seconds (rate limiting)
        if (get_transient($track_key)) {
            return; // Already tracked this page recently
        }
        
        // Set transient for 5 seconds to prevent duplicate tracking
        set_transient($track_key, true, 5);
        
        $page_url = $current_url;
        $page_title = wp_get_document_title();
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        
        // Extract product ID if on product page
        $product_id = null;
        if (is_product() && function_exists('wc_get_product')) {
            // Use get_queried_object_id() instead of global $product
            $queried_object_id = get_queried_object_id();
            if ($queried_object_id) {
                $product_obj = wc_get_product($queried_object_id);
                if ($product_obj && is_a($product_obj, 'WC_Product')) {
                    $product_id = $product_obj->get_id();
                }
            }
        }
        
        // Track visit directly (lightweight operation)
        if (class_exists('Maneli_Visitor_Statistics')) {
            Maneli_Visitor_Statistics::track_visit($page_url, $page_title, $referrer, $product_id);
        }
    }
    
    /**
     * Enqueue visitor tracking scripts
     */
    public function enqueue_visitor_tracking_scripts() {
        // Check if visitor statistics is enabled (using optimized helper)
        if (!Maneli_Options_Helper::is_option_enabled('enable_visitor_statistics', false)) {
            return;
        }
        
        // Skip on dashboard pages (they use static HTML template, scripts will be injected manually)
        if (get_query_var('maneli_dashboard')) {
            return;
        }
        
        // Enqueue visitor tracking script for frontend pages
        $script_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/frontend/visitor-tracking.js';
        if (file_exists($script_path)) {
            wp_enqueue_script(
                'maneli-visitor-tracking',
                MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/visitor-tracking.js',
                ['jquery'],
                filemtime($script_path),
                true
            );
            
            // Localize script
            wp_localize_script('maneli-visitor-tracking', 'maneliVisitorTracking', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('maneli_visitor_stats_nonce'),
                'enabled' => true,
                'debug' => defined('WP_DEBUG') && WP_DEBUG
            ]);
        }
    }

    /**
     * Schedule log cleanup cron job
     */
    public function schedule_log_cleanup_cron() {
        // Check if auto cleanup is enabled (using optimized helper)
        if (!Maneli_Options_Helper::is_option_enabled('enable_auto_log_cleanup', false)) {
            // Unschedule if disabled
            $timestamp = wp_next_scheduled('maneli_cleanup_old_logs');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'maneli_cleanup_old_logs');
            }
            return;
        }
        
        // Get cleanup frequency
        $frequency = isset($options['log_cleanup_frequency']) ? $options['log_cleanup_frequency'] : 'daily';
        
        // Map frequency to WordPress schedule
        $schedule_map = [
            'hourly' => 'hourly',
            'daily' => 'daily',
            'weekly' => 'weekly',
        ];
        $schedule = $schedule_map[$frequency] ?? 'daily';
        
        // Schedule if not already scheduled or schedule changed
        if (!wp_next_scheduled('maneli_cleanup_old_logs')) {
            wp_schedule_event(time(), $schedule, 'maneli_cleanup_old_logs');
        }
    }

    /**
     * Cleanup old logs (called by cron)
     */
    public function cleanup_old_logs() {
        if (!class_exists('Maneli_Logger')) {
            return;
        }
        
        $logger = Maneli_Logger::instance();
        $deleted_count = $logger->cleanup_old_logs();
        
        if (defined('WP_DEBUG') && WP_DEBUG && $deleted_count > 0) {
            error_log(sprintf('Maneli Log Cleanup: Deleted %d old log entries', $deleted_count));
        }
    }
    
}