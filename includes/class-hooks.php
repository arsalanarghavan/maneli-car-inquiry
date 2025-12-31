<?php
/**
 * Manages general WordPress hooks to modify core behaviors.
 * This includes user redirects, query modifications, and price visibility.
 *
 * @package Autopuzzle_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Hooks {

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
        add_action('autopuzzle_daily_followup_notifications', [$this, 'send_followup_notifications']);
        add_action('init', [$this, 'schedule_followup_notifications_cron']);
        
        // Notification center cron jobs
        add_action('autopuzzle_send_meeting_reminders', [$this, 'send_meeting_reminders']);
        add_action('autopuzzle_process_scheduled_notifications', [$this, 'process_scheduled_notifications']);
        
        // Visitor statistics tracking
        add_action('wp_footer', [$this, 'track_visitor_statistics'], 999);
        add_action('template_redirect', [$this, 'track_visitor_statistics_server_side'], 1);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_visitor_tracking_scripts']);

        // Frontend product title enhancements
        add_filter('the_title', [$this, 'append_year_badge_to_single_product_title'], 20, 2);
        add_action('wp_head', [$this, 'output_single_product_year_badge_style'], 50);
        
        // Log cleanup cron job
        add_action('autopuzzle_cleanup_old_logs', [$this, 'cleanup_old_logs']);
        add_action('init', [$this, 'schedule_log_cleanup_cron']);
        
        // Product tags management (cash/installment)
        if (class_exists('Autopuzzle_Product_Tags_Manager')) {
            // Auto-tagging on product save/update
            add_action('woocommerce_update_product', [$this, 'auto_update_product_tags'], 20, 1);
            add_action('woocommerce_new_product', [$this, 'auto_update_product_tags'], 20, 1);
            // Display tags in shop loop
            add_action('woocommerce_before_shop_loop_item_title', [$this, 'display_product_payment_tags_in_loop'], 15);
            // Display tags in Elementor carousel (hook into product image wrap) - multiple hooks for compatibility
            add_action('woocommerce_before_shop_loop_item', [$this, 'display_product_payment_tags_in_carousel'], 5);
            add_action('woocommerce_shop_loop_item_title', [$this, 'display_product_payment_tags_in_carousel'], 1);
            // Also hook into product thumbnail for Elementor
            add_action('woocommerce_template_loop_product_thumbnail', [$this, 'display_product_payment_tags_after_thumbnail'], 25);
            // Display tags in single product
            add_action('woocommerce_single_product_summary', [$this, 'display_product_payment_tags_in_single'], 5);
            // Filter products by payment type - use pre_get_posts for main query
            // Priority 25 to run after modify_product_query_for_customers (priority 10)
            add_action('pre_get_posts', [$this, 'filter_products_by_payment_type'], 25);
            // Add payment type filter buttons in shop archive
            add_action('woocommerce_before_shop_loop', [$this, 'display_payment_type_filter'], 15);
            // Enqueue product tags CSS and JS
            add_action('wp_enqueue_scripts', [$this, 'enqueue_product_tags_styles'], 15);
            // Enqueue product buttons modal JS
            add_action('wp_enqueue_scripts', [$this, 'enqueue_product_buttons_modal_scripts'], 15);
            // AJAX handler for getting product tags
            add_action('wp_ajax_autopuzzle_get_product_payment_tags', [$this, 'ajax_get_product_payment_tags']);
            add_action('wp_ajax_nopriv_autopuzzle_get_product_payment_tags', [$this, 'ajax_get_product_payment_tags']);
            
            // Replace "Add to Cart" button with "View Product" button
            add_filter('woocommerce_product_add_to_cart_text', [$this, 'change_add_to_cart_text'], 10, 2);
            add_filter('woocommerce_loop_add_to_cart_link', [$this, 'replace_add_to_cart_with_view_product'], 10, 2);
            
            // Add purchase buttons (Cash/Installment) and View Product button in carousels
            add_action('woocommerce_after_shop_loop_item', [$this, 'add_carousel_purchase_buttons'], 10);
            
            // AJAX handler for loading calculator tab content in modal
            add_action('wp_ajax_autopuzzle_get_calculator_tab', [$this, 'ajax_get_calculator_tab']);
            add_action('wp_ajax_nopriv_autopuzzle_get_calculator_tab', [$this, 'ajax_get_calculator_tab']);
            
            // AJAX handler for getting product prices
            add_action('wp_ajax_autopuzzle_get_product_prices', [$this, 'ajax_get_product_prices']);
            add_action('wp_ajax_nopriv_autopuzzle_get_product_prices', [$this, 'ajax_get_product_prices']);
            
            // Add calculator modal to footer
            add_action('wp_footer', [$this, 'add_calculator_modals_to_footer']);
        }
        
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
        if (get_option('autopuzzle_display_names_updated_v2') === 'yes') {
            return;
        }

        $users = get_users(['fields' => 'ID']);
        foreach ($users as $user_id) {
            $this->update_display_name_on_event($user_id);
        }
        
        update_option('autopuzzle_display_names_updated_v2', 'yes');
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
            ' <span class="autopuzzle-year-badge autopuzzle-year-badge--single">%s</span>',
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

        echo '<style id="autopuzzle-single-product-year-badge">
.single-product .product_title.entry-title .autopuzzle-year-badge {
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

.single-product .product_title.entry-title .autopuzzle-year-badge--single {
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
     * همه نقش‌های دیگر (autopuzzle_admin, autopuzzle_expert, customer) به داشبورد frontend هدایت می‌شوند.
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
        if (is_admin() || current_user_can('manage_autopuzzle_inquiries')) {
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
        if (current_user_can('manage_autopuzzle_inquiries')) {
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
        if (is_admin() || current_user_can('manage_autopuzzle_inquiries')) {
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
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS autopuzzle_status_meta ON {$wpdb->posts}.ID = autopuzzle_status_meta.post_id AND autopuzzle_status_meta.meta_key = '_maneli_car_status'";
        $clauses['where'] .= " AND (autopuzzle_status_meta.meta_value != 'disabled' OR autopuzzle_status_meta.meta_value IS NULL)";
        
        return $clauses;
    }
    
    /**
     * Exclude disabled products from WooCommerce product queries
     */
    public function exclude_disabled_products_from_wc_query($query) {
        // Only run on frontend, for non-admin users
        if (is_admin() || current_user_can('manage_autopuzzle_inquiries')) {
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
            return '<span class="price unavailable-text">' . esc_html__('Unavailable', 'autopuzzle') . '</span>';
        }
        
        return $price_html;
    }

    /**
     * Conditionally removes price-related hooks based on plugin settings.
     * This effectively hides prices from non-admin users if the setting is enabled.
     */
    public function conditionally_hide_prices() {
        $is_price_hidden = Autopuzzle_Options_Helper::is_option_enabled('hide_prices_for_customers', false);

        if ($is_price_hidden && !current_user_can('manage_autopuzzle_inquiries')) {
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
            return '<span class="price unavailable-text">' . esc_html__('Unavailable', 'autopuzzle') . '</span>';
        }
        
        // Hide prices for all other products
        return '';
    }

    /**
     * Schedule the daily followup notifications cron job
     */
    public function schedule_followup_notifications_cron() {
        if (!wp_next_scheduled('autopuzzle_daily_followup_notifications')) {
            // Schedule for 9 AM every day
            wp_schedule_event(time(), 'daily', 'autopuzzle_daily_followup_notifications');
        }
    }

    /**
     * Send followup reminder notifications
     * Called daily by WordPress cron
     */
    public function send_followup_notifications() {
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-notification-handler.php';
        $count = Autopuzzle_Notification_Handler::schedule_followup_notifications();
        
        // Log the result
        error_log(sprintf('AutoPuzzle Followup Notifications: %d notifications created', $count));
    }

    /**
     * Send meeting reminders
     * Called hourly by WordPress cron
     */
    public function send_meeting_reminders() {
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-notification-center-handler.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-autopuzzle-database.php';
        
        $options = Autopuzzle_Options_Helper::get_all_options();
        
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
            'post_type' => 'autopuzzle_meeting',
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
                    $existing_reminder = Autopuzzle_Database::get_notification_logs([
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
                            esc_html__('Reminder: You have a meeting scheduled for %s. Car: %s', 'autopuzzle'),
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
                                Autopuzzle_Notification_Center_Handler::send(
                                    $channel,
                                    $recipients[$channel],
                                    $message,
                                    [
                                        'category' => 'meeting_reminder',
                                        'related_id' => $meeting->ID,
                                        'title' => esc_html__('Meeting Reminder', 'autopuzzle'),
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
                    $existing_reminder = Autopuzzle_Database::get_notification_logs([
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
                            esc_html__('Reminder: You have a meeting scheduled for %s (%d days). Car: %s', 'autopuzzle'),
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
                                Autopuzzle_Notification_Center_Handler::send(
                                    $channel,
                                    $recipients[$channel],
                                    $message,
                                    [
                                        'category' => 'meeting_reminder',
                                        'related_id' => $meeting->ID,
                                        'title' => esc_html__('Meeting Reminder', 'autopuzzle'),
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
            error_log(sprintf('AutoPuzzle Meeting Reminders: %d reminders sent', $reminders_sent));
        }
    }

    /**
     * Process scheduled notifications
     * Called hourly by WordPress cron
     */
    public function process_scheduled_notifications() {
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-notification-center-handler.php';
        
        $count = Autopuzzle_Notification_Center_Handler::process_scheduled();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('AutoPuzzle Scheduled Notifications: %d notifications processed', $count));
        }
    }
    
    /**
     * Track visitor statistics (client-side via JavaScript)
     * This injects JavaScript code to track visits via AJAX
     */
    public function track_visitor_statistics() {
        // Skip tracking on admin pages and dashboard pages
        if (is_admin() || get_query_var('autopuzzle_dashboard')) {
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
        if (!Autopuzzle_Options_Helper::is_option_enabled('enable_visitor_statistics', false)) {
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
        $nonce = wp_create_nonce('autopuzzle_visitor_stats_nonce');
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
                        action: 'autopuzzle_track_visit',
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
        if (is_admin() || get_query_var('autopuzzle_dashboard')) {
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
        if (!Autopuzzle_Options_Helper::is_option_enabled('enable_visitor_statistics', false)) {
            return;
        }
        
        // Rate limiting - use transients instead of session to avoid header issues
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        $track_key = 'autopuzzle_tracked_' . md5($current_url . $ip_address);
        
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
        if (class_exists('Autopuzzle_Visitor_Statistics')) {
            Autopuzzle_Visitor_Statistics::track_visit($page_url, $page_title, $referrer, $product_id);
        }
    }
    
    /**
     * Enqueue visitor tracking scripts
     */
    public function enqueue_visitor_tracking_scripts() {
        // Check if visitor statistics is enabled (using optimized helper)
        if (!Autopuzzle_Options_Helper::is_option_enabled('enable_visitor_statistics', false)) {
            return;
        }
        
        // Skip on dashboard pages (they use static HTML template, scripts will be injected manually)
        if (get_query_var('autopuzzle_dashboard')) {
            return;
        }
        
        // Enqueue visitor tracking script for frontend pages
        $script_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/js/frontend/visitor-tracking.js';
        if (file_exists($script_path)) {
            wp_enqueue_script(
                'autopuzzle-visitor-tracking',
                AUTOPUZZLE_PLUGIN_URL . 'assets/js/frontend/visitor-tracking.js',
                ['jquery'],
                filemtime($script_path),
                true
            );
            
            // Localize script
            wp_localize_script('autopuzzle-visitor-tracking', 'maneliVisitorTracking', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('autopuzzle_visitor_stats_nonce'),
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
        if (!Autopuzzle_Options_Helper::is_option_enabled('enable_auto_log_cleanup', false)) {
            // Unschedule if disabled
            $timestamp = wp_next_scheduled('autopuzzle_cleanup_old_logs');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'autopuzzle_cleanup_old_logs');
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
        if (!wp_next_scheduled('autopuzzle_cleanup_old_logs')) {
            wp_schedule_event(time(), $schedule, 'autopuzzle_cleanup_old_logs');
        }
    }

    /**
     * Cleanup old logs (called by cron)
     */
    public function cleanup_old_logs() {
        if (!class_exists('Autopuzzle_Logger')) {
            return;
        }
        
        $logger = Autopuzzle_Logger::instance();
        $deleted_count = $logger->cleanup_old_logs();
        
        if (defined('WP_DEBUG') && WP_DEBUG && $deleted_count > 0) {
            error_log(sprintf('AutoPuzzle Log Cleanup: Deleted %d old log entries', $deleted_count));
        }
    }

    /**
     * Auto-update product tags when product is saved/updated
     *
     * @param int $product_id Product ID
     */
    public function auto_update_product_tags($product_id) {
        if (!class_exists('Autopuzzle_Product_Tags_Manager')) {
            return;
        }

        $tags_manager = new Autopuzzle_Product_Tags_Manager();
        $tags_manager->update_product_tags($product_id);
    }

    /**
     * Display payment tags in shop loop (archive pages, carousels)
     */
    public function display_product_payment_tags_in_loop() {
        global $product;
        
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }

        if (!class_exists('Autopuzzle_Render_Helpers')) {
            return;
        }

        // Display tags - they will be positioned absolutely on the product image
        Autopuzzle_Render_Helpers::render_product_payment_tags($product->get_id(), 'loop');
    }

    /**
     * Display payment tags in Elementor carousel (early hook to ensure tags are inside image-wrap)
     * Also add data attributes for fast JavaScript rendering
     */
    public function display_product_payment_tags_in_carousel() {
        global $product;
        
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }

        if (!class_exists('Autopuzzle_Render_Helpers') || !class_exists('Autopuzzle_Product_Tags_Manager')) {
            return;
        }

        $product_id = $product->get_id();
        
        // Prevent duplicate rendering
        static $rendered_products = [];
        if (isset($rendered_products[$product_id])) {
            return;
        }
        $rendered_products[$product_id] = true;

        $tags_manager = new Autopuzzle_Product_Tags_Manager();
        $payment_tags = $tags_manager->get_product_payment_tags($product_id);

        // Add data attributes to product element for fast JavaScript access
        echo '<script type="text/javascript">';
        echo '(function() {';
        echo 'if (typeof autopuzzleProductTags === "undefined") { window.autopuzzleProductTags = { products: {} }; }';
        echo 'autopuzzleProductTags.products[' . intval($product_id) . '] = {';
        echo 'cash: ' . ($payment_tags['cash'] ? 'true' : 'false') . ',';
        echo 'installment: ' . ($payment_tags['installment'] ? 'true' : 'false');
        echo '};';
        echo '})();';
        echo '</script>';

        // Also render tags directly (faster than waiting for JavaScript)
        Autopuzzle_Render_Helpers::render_product_payment_tags($product_id, 'loop');
    }

    /**
     * Display payment tags after product thumbnail (for Elementor carousel compatibility)
     */
    public function display_product_payment_tags_after_thumbnail() {
        // This hook runs after thumbnail, so we can add tags right after image
        $this->display_product_payment_tags_in_carousel();
    }

    /**
     * Display payment tags in single product page
     */
    public function display_product_payment_tags_in_single() {
        global $product;
        
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }

        if (!class_exists('Autopuzzle_Render_Helpers')) {
            return;
        }

        Autopuzzle_Render_Helpers::render_product_payment_tags($product->get_id(), 'single');
    }

    /**
     * Filter products by payment type (cash/installment) via query parameter
     *
     * @param WP_Query $query WordPress query
     */
    public function filter_products_by_payment_type($query) {
        // Only run on frontend main query for shop/archive pages
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        // Only on shop/archive pages
        if (!function_exists('is_shop') || (!is_shop() && !is_product_category() && !is_product_tag() && !is_product_taxonomy())) {
            return;
        }

        // Check for payment_type parameter
        $payment_type = isset($_GET['payment_type']) ? sanitize_text_field($_GET['payment_type']) : '';
        
        if (empty($payment_type) || !in_array($payment_type, ['cash', 'installment'], true)) {
            return;
        }

        // Get the tag slug
        $tag_slug = $payment_type; // 'cash' or 'installment'

        // Get the tag term
        $tag = get_term_by('slug', $tag_slug, 'product_tag');
        if (!$tag || is_wp_error($tag)) {
            return;
        }

        // Get existing tax_query
        $tax_query = $query->get('tax_query');
        if (!is_array($tax_query)) {
            $tax_query = [];
        }

        // Preserve existing tax queries (like product_cat from WooCommerce)
        $existing_tax_queries = [];
        $relation = 'AND';

        // Extract relation if exists
        if (isset($tax_query['relation'])) {
            $relation = $tax_query['relation'];
        }

        // Extract all tax query items (excluding relation key)
        foreach ($tax_query as $key => $tax_item) {
            if ($key !== 'relation' && is_array($tax_item) && isset($tax_item['taxonomy'])) {
                $existing_tax_queries[] = $tax_item;
            }
        }

        // Add product_tag filter
        $existing_tax_queries[] = [
            'taxonomy' => 'product_tag',
            'field' => 'term_id',
            'terms' => $tag->term_id,
            'operator' => 'IN',
        ];

        // Rebuild tax_query with proper structure
        $new_tax_query = $existing_tax_queries;
        
        // Always set relation if we have multiple queries
        if (count($existing_tax_queries) > 1) {
            $new_tax_query['relation'] = $relation;
        }

        $query->set('tax_query', $new_tax_query);
    }

    /**
     * Display payment type filter buttons in shop archive
     */
    public function display_payment_type_filter() {
        // Only show on shop/archive pages (not on single product pages)
        if (!function_exists('is_shop') || is_product()) {
            return;
        }

        // Check if we're on a shop/archive page
        $is_archive = false;
        if (is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy()) {
            $is_archive = true;
        }

        // Also check if we're on the main products query
        global $wp_query;
        if (!$is_archive && isset($wp_query) && $wp_query->is_main_query() && (is_post_type_archive('product') || is_tax('product_cat') || is_tax('product_tag'))) {
            $is_archive = true;
        }

        if (!$is_archive) {
            return;
        }

        $current_filter = isset($_GET['payment_type']) ? sanitize_text_field($_GET['payment_type']) : '';
        $base_url = remove_query_arg(['payment_type', 'paged']);
        ?>
        <div class="autopuzzle-payment-filter" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: flex-start;">
            <span style="font-weight: 600; margin-left: 10px;"><?php esc_html_e('Filter by Payment Type:', 'autopuzzle'); ?></span>
            <a href="<?php echo esc_url($base_url); ?>" 
               class="autopuzzle-filter-btn <?php echo empty($current_filter) ? 'active' : ''; ?>" 
               style="display: inline-block; padding: 8px 16px; border-radius: 4px; text-decoration: none; background-color: <?php echo empty($current_filter) ? '#333' : '#f0f0f0'; ?>; color: <?php echo empty($current_filter) ? '#fff' : '#333'; ?>; transition: all 0.3s;">
                <?php esc_html_e('All', 'autopuzzle'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('payment_type', 'cash', $base_url)); ?>" 
               class="autopuzzle-filter-btn <?php echo $current_filter === 'cash' ? 'active' : ''; ?>" 
               style="display: inline-block; padding: 8px 16px; border-radius: 4px; text-decoration: none; background-color: <?php echo $current_filter === 'cash' ? 'rgb(33, 206, 158)' : '#f0f0f0'; ?>; color: <?php echo $current_filter === 'cash' ? '#fff' : '#333'; ?>; transition: all 0.3s;">
                <?php esc_html_e('Cash', 'autopuzzle'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('payment_type', 'installment', $base_url)); ?>" 
               class="autopuzzle-filter-btn <?php echo $current_filter === 'installment' ? 'active' : ''; ?>" 
               style="display: inline-block; padding: 8px 16px; border-radius: 4px; text-decoration: none; background-color: <?php echo $current_filter === 'installment' ? 'rgb(14, 165, 232)' : '#f0f0f0'; ?>; color: <?php echo $current_filter === 'installment' ? '#fff' : '#333'; ?>; transition: all 0.3s;">
                <?php esc_html_e('Installment', 'autopuzzle'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Enqueue product tags CSS styles
     */
    public function enqueue_product_tags_styles() {
        // Only enqueue on frontend, on product-related pages
        if (is_admin()) {
            return;
        }

        // Check if we're on a product-related page
        $is_product_page = false;
        if (function_exists('is_product') && is_product()) {
            $is_product_page = true;
        }
        if (function_exists('is_shop') && is_shop()) {
            $is_product_page = true;
        }
        if (function_exists('is_product_category') && is_product_category()) {
            $is_product_page = true;
        }
        if (function_exists('is_product_tag') && is_product_tag()) {
            $is_product_page = true;
        }

        // Also check if we're on archive or any page that might show products
        global $post;
        if (!$is_product_page && is_a($post, 'WP_Post') && 'product' === get_post_type($post)) {
            $is_product_page = true;
        }

        // Always enqueue on frontend (for carousels and other widgets)
        if (!$is_product_page) {
            $is_product_page = true; // Enable for all frontend pages to support carousels
        }

        if (!$is_product_page) {
            return;
        }

        $css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/product-tags.css';
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'autopuzzle-product-tags',
                AUTOPUZZLE_PLUGIN_URL . 'assets/css/product-tags.css',
                [],
                filemtime($css_path)
            );
        }

        // Enqueue JavaScript for carousel support
        $js_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/js/product-tags-carousel.js';
        if (file_exists($js_path)) {
            wp_enqueue_script(
                'autopuzzle-product-tags-carousel',
                AUTOPUZZLE_PLUGIN_URL . 'assets/js/product-tags-carousel.js',
                ['jquery'],
                filemtime($js_path),
                true
            );

            // Localize script
            wp_localize_script('autopuzzle-product-tags-carousel', 'autopuzzleProductTags', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('autopuzzle_product_tags_nonce'),
            ]);
        }
    }

    /**
     * Enqueue product buttons modal JavaScript
     */
    public function enqueue_product_buttons_modal_scripts() {
        // Only enqueue on frontend
        if (is_admin()) {
            return;
        }

        // Check if we're on a product-related page
        $is_product_page = false;
        if (function_exists('is_product') && is_product()) {
            $is_product_page = true;
        }
        if (function_exists('is_shop') && is_shop()) {
            $is_product_page = true;
        }
        if (function_exists('is_product_category') && is_product_category()) {
            $is_product_page = true;
        }
        if (function_exists('is_product_tag') && is_product_tag()) {
            $is_product_page = true;
        }

        // Always enqueue on frontend (for carousels and other widgets)
        if (!$is_product_page) {
            $is_product_page = true; // Enable for all frontend pages to support carousels
        }

        if (!$is_product_page) {
            return;
        }

            // Enqueue Bootstrap if not already enqueued (for modal)
            if (!wp_script_is('bootstrap', 'enqueued')) {
                // Try to enqueue Bootstrap 5
                wp_enqueue_script(
                    'bootstrap',
                    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
                    [],
                    '5.3.0',
                    true
                );
            }
            
            // Enqueue Bootstrap CSS if not already enqueued
            if (!wp_style_is('bootstrap', 'enqueued')) {
                wp_enqueue_style(
                    'bootstrap',
                    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
                    [],
                    '5.3.0'
                );
            }
            
            // Ensure calculator assets are enqueued for modal
            if (class_exists('Autopuzzle_Loan_Calculator_Shortcode')) {
                $calculator_shortcode = new Autopuzzle_Loan_Calculator_Shortcode();
                // This will enqueue calculator assets
                if (method_exists($calculator_shortcode, 'enqueue_calculator_assets')) {
                    $calculator_shortcode->enqueue_calculator_assets();
                }
            }

        // Enqueue product buttons modal JS
        $js_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/js/product-buttons-modal.js';
        if (file_exists($js_path)) {
            wp_enqueue_script(
                'autopuzzle-product-buttons-modal',
                AUTOPUZZLE_PLUGIN_URL . 'assets/js/product-buttons-modal.js',
                ['jquery'],
                filemtime($js_path),
                true
            );

            // Localize script
            wp_localize_script('autopuzzle-product-buttons-modal', 'autopuzzleButtonsModal', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('autopuzzle_calculator_tab_nonce'),
                'pluginUrl' => AUTOPUZZLE_PLUGIN_URL,
                'loadingText' => esc_html__('Loading...', 'autopuzzle'),
                'errorText' => esc_html__('Error loading calculator. Please try again.', 'autopuzzle'),
                'cashPurchaseTitle' => esc_html__('Cash Purchase', 'autopuzzle'),
                'installmentPurchaseTitle' => esc_html__('Installment Purchase', 'autopuzzle'),
                'viewProductTitle' => esc_html__('View Product', 'autopuzzle'),
            ]);
        }
    }

    /**
     * AJAX handler to get product payment tags HTML
     */
    public function ajax_get_product_payment_tags() {
        check_ajax_referer('autopuzzle_product_tags_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (!$product_id || !class_exists('Autopuzzle_Render_Helpers') || !class_exists('Autopuzzle_Product_Tags_Manager')) {
            wp_send_json_error(['message' => 'Invalid product ID']);
            return;
        }

        $tags_manager = new Autopuzzle_Product_Tags_Manager();
        $payment_tags = $tags_manager->get_product_payment_tags($product_id);

        // Return both HTML and data for flexibility
        ob_start();
        Autopuzzle_Render_Helpers::render_product_payment_tags($product_id, 'loop');
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'cash' => $payment_tags['cash'],
            'installment' => $payment_tags['installment']
        ]);
    }

    /**
     * Change "Add to Cart" button text to "View Product"
     *
     * @param string $text Button text
     * @param WC_Product $product Product object
     * @return string
     */
    public function change_add_to_cart_text($text, $product) {
        return esc_html__('View Product', 'autopuzzle');
    }

    /**
     * Replace "Add to Cart" button with "View Product" link
     *
     * @param string $button_html The button HTML
     * @param WC_Product $product The product object
     * @return string
     */
    public function replace_add_to_cart_with_view_product($button_html, $product) {
        if (!$product) {
            return $button_html;
        }

        $product_id = $product->get_id();
        $product_url = get_permalink($product_id);
        
        $button_html = sprintf(
            '<a href="%s" class="button autopuzzle-view-product-btn">%s</a>',
            esc_url($product_url),
            esc_html__('View Product', 'autopuzzle')
        );

        return $button_html;
    }

    /**
     * Add purchase buttons (Cash/Installment) and View Product button in carousels and archive pages
     */
    public function add_carousel_purchase_buttons() {
        global $product;
        
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }

        if (!class_exists('Autopuzzle_Product_Tags_Manager')) {
            return;
        }

        $product_id = $product->get_id();
        $tags_manager = new Autopuzzle_Product_Tags_Manager();
        $payment_tags = $tags_manager->get_product_payment_tags($product_id);

        // Get prices
        $cash_price = (float) $product->get_regular_price();
        $installment_price = (float) get_post_meta($product_id, 'installment_price', true);

        $product_url = get_permalink($product_id);
        ?>
        <div class="autopuzzle-product-buttons" data-product-id="<?php echo esc_attr($product_id); ?>">
            <?php if ($cash_price > 0): ?>
                <button type="button" class="autopuzzle-btn autopuzzle-btn-cash" data-product-id="<?php echo esc_attr($product_id); ?>" data-tab-type="cash">
                    <?php esc_html_e('Cash Purchase', 'autopuzzle'); ?>
                </button>
            <?php endif; ?>
            
            <?php if ($installment_price > 0): ?>
                <button type="button" class="autopuzzle-btn autopuzzle-btn-installment" data-product-id="<?php echo esc_attr($product_id); ?>" data-tab-type="installment">
                    <?php esc_html_e('Installment Purchase', 'autopuzzle'); ?>
                </button>
            <?php endif; ?>
            
            <a href="<?php echo esc_url($product_url); ?>" class="autopuzzle-btn autopuzzle-btn-view">
                <?php esc_html_e('View Product', 'autopuzzle'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * AJAX handler to get calculator tab content for modal
     */
    public function ajax_get_calculator_tab() {
        check_ajax_referer('autopuzzle_calculator_tab_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $tab_type = isset($_POST['tab_type']) ? sanitize_text_field($_POST['tab_type']) : '';

        if (!$product_id || !in_array($tab_type, ['cash', 'installment'], true)) {
            wp_send_json_error(['message' => 'Invalid parameters']);
            return;
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(['message' => 'Product not found']);
            return;
        }

        // Get product data
        $cash_price_raw = $product->get_regular_price();
        if ($cash_price_raw === '' || $cash_price_raw === null || $cash_price_raw === false || $cash_price_raw === 0 || $cash_price_raw === '0') {
            $cash_price = 0;
        } else {
            $cash_price_str = (string)$cash_price_raw;
            $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $cash_price_str = str_replace($persian_digits, $english_digits, $cash_price_str);
            $cash_price_str = preg_replace('/[^\d]/', '', $cash_price_str);
            $cash_price = (!empty($cash_price_str) && $cash_price_str !== '0') ? (int)$cash_price_str : 0;
        }

        $installment_price_raw = get_post_meta($product_id, 'installment_price', true);
        if (is_string($installment_price_raw) && !empty($installment_price_raw)) {
            $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $installment_price_raw = str_replace($persian_digits, $english_digits, $installment_price_raw);
            $installment_price_raw = preg_replace('/[^\d]/', '', $installment_price_raw);
        }
        $installment_price = (!empty($installment_price_raw) && $installment_price_raw !== '0') ? (int)$installment_price_raw : 0;

        $min_down_payment_raw = get_post_meta($product_id, 'min_downpayment', true);
        if (is_string($min_down_payment_raw) && !empty($min_down_payment_raw)) {
            $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $min_down_payment_raw = str_replace($persian_digits, $english_digits, $min_down_payment_raw);
            $min_down_payment_raw = preg_replace('/[^\d]/', '', $min_down_payment_raw);
        }
        $min_down_payment = !empty($min_down_payment_raw) ? (int)$min_down_payment_raw : 0;
        if ($min_down_payment <= 0 && $installment_price > 0) {
            $min_down_payment = (int)($installment_price * 0.2);
        }

        $car_colors_str = get_post_meta($product_id, '_maneli_car_colors', true);
        $car_colors = !empty($car_colors_str) ? array_map('trim', explode(',', $car_colors_str)) : [];

        $car_status = get_post_meta($product_id, '_maneli_car_status', true);
        if (empty($car_status) || !in_array($car_status, ['special_sale', 'unavailable', 'disabled'], true)) {
            $car_status = 'special_sale';
        }

        $is_unavailable = ($car_status === 'unavailable');
        $cash_unavailable = ($is_unavailable || $cash_price <= 0);
        $installment_unavailable = ($is_unavailable || $installment_price <= 0);
        $can_see_prices = true;

        $template_args = [
            'product'                => $product,
            'cash_price'             => $cash_price,
            'installment_price'      => $installment_price,
            'min_down_payment'       => $min_down_payment,
            'max_down_payment'       => (int)($installment_price * 0.8),
            'car_colors'             => $car_colors,
            'car_status'             => $car_status,
            'can_see_prices'         => $can_see_prices,
            'is_unavailable'         => $is_unavailable,
            'cash_unavailable'       => $cash_unavailable,
            'installment_unavailable' => $installment_unavailable,
        ];

        // Render only the requested tab
        ob_start();
        if ($tab_type === 'cash') {
            autopuzzle_get_template_part('shortcodes/calculator/cash-tab-content', $template_args);
        } else {
            autopuzzle_get_template_part('shortcodes/calculator/installment-tab-content', $template_args);
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'product_name' => $product->get_name()
        ]);
    }

    /**
     * AJAX handler to get product prices (cash and installment)
     */
    public function ajax_get_product_prices() {
        check_ajax_referer('autopuzzle_calculator_tab_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (!$product_id) {
            wp_send_json_error(['message' => 'Invalid product ID']);
            return;
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(['message' => 'Product not found']);
            return;
        }

        // Get cash price
        $cash_price_raw = $product->get_regular_price();
        if ($cash_price_raw === '' || $cash_price_raw === null || $cash_price_raw === false || $cash_price_raw === 0 || $cash_price_raw === '0') {
            $cash_price = 0;
        } else {
            $cash_price_str = (string)$cash_price_raw;
            $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $cash_price_str = str_replace($persian_digits, $english_digits, $cash_price_str);
            $cash_price_str = preg_replace('/[^\d]/', '', $cash_price_str);
            $cash_price = (!empty($cash_price_str) && $cash_price_str !== '0') ? (float)$cash_price_str : 0;
        }

        // Get installment price
        $installment_price_raw = get_post_meta($product_id, 'installment_price', true);
        if (is_string($installment_price_raw) && !empty($installment_price_raw)) {
            $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $installment_price_raw = str_replace($persian_digits, $english_digits, $installment_price_raw);
            $installment_price_raw = preg_replace('/[^\d]/', '', $installment_price_raw);
        }
        $installment_price = (!empty($installment_price_raw) && $installment_price_raw !== '0') ? (float)$installment_price_raw : 0;

        wp_send_json_success([
            'cash_price' => $cash_price,
            'installment_price' => $installment_price
        ]);
    }

    /**
     * Add calculator modals to footer
     * Note: Modal will be moved to body by JavaScript for proper display
     */
    public function add_calculator_modals_to_footer() {
        // Only add on frontend pages that might show products
        if (is_admin()) {
            return;
        }
        ?>
        <!-- AutoPuzzle Calculator Modals -->
        <div class="autopuzzle-calculator-modal modal fade" id="autopuzzleCalculatorModal" tabindex="-1" aria-labelledby="autopuzzleCalculatorModalLabel" aria-hidden="true" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1055;">
            <div class="modal-dialog modal-lg modal-dialog-centered" style="margin: 1.75rem auto; max-width: 800px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="autopuzzleCalculatorModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'autopuzzle'); ?>"></button>
                    </div>
                    <div class="modal-body" id="autopuzzleCalculatorModalBody">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden"><?php esc_html_e('Loading...', 'autopuzzle'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">
        // Immediately move modal to body on page load
        (function() {
            const modalElement = document.getElementById('autopuzzleCalculatorModal');
            if (modalElement && modalElement.parentElement && modalElement.parentElement.tagName !== 'BODY') {
                document.body.appendChild(modalElement);
            }
        })();
        </script>
        <?php
    }
    
}