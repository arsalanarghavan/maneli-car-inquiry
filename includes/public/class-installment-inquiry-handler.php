<?php
/**
 * Handles the multi-step process for creating an installment inquiry for a customer.
 * This class now listens for hooks to finalize inquiries, decoupling it from the payment handler.
 *
 * @package Maneli_Car_Inquiry/Includes/Public
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.5 (Refactored: Removed redundant loan calculation, using centralized helper)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Installment_Inquiry_Handler {

    public function __construct() {
        // Step 1: Handle car selection from the product page calculator.
        add_action('wp_ajax_maneli_select_car_ajax', [$this, 'handle_car_selection_ajax']);
        add_action('wp_ajax_nopriv_maneli_select_car_ajax', '__return_false'); // Must be logged in

        // Step 2: Handle identity form submission.
        add_action('admin_post_nopriv_maneli_submit_identity', '__return_false');
        add_action('admin_post_maneli_submit_identity', [$this, 'handle_identity_submission']);

        // New Step 3: Confirm car step submission
        add_action('admin_post_nopriv_maneli_confirm_car_step', '__return_false');
        add_action('admin_post_maneli_confirm_car_step', [$this, 'handle_confirm_car_step']);

        // Step 3 (Implicit): Listens for a successful payment hook to finalize the installment inquiry.
        add_action('maneli_inquiry_payment_successful', [$this, 'finalize_inquiry_from_hook']);
        
        // Bonus: Also listens for cash payment success to update its status. This keeps concerns separate.
        add_action('maneli_cash_inquiry_payment_successful', [$this, 'finalize_cash_inquiry_from_hook'], 10, 2);

        // Handle re-try logic for failed inquiries.
        add_action('admin_post_maneli_retry_inquiry', [$this, 'handle_inquiry_retry']);

        // Public AJAX: Confirm car catalog (customers)
        add_action('wp_ajax_maneli_confirm_car_catalog', [$this, 'ajax_confirm_car_catalog']);
        add_action('wp_ajax_nopriv_maneli_confirm_car_catalog', '__return_false');

        // Meetings AJAX
        add_action('wp_ajax_maneli_get_meeting_slots', [$this, 'ajax_get_meeting_slots']);
        add_action('wp_ajax_maneli_book_meeting', [$this, 'ajax_book_meeting']);
    }
    
    // =======================================================
    //  DECRYPTION HELPERS
    // =======================================================
    
    /**
     * Retrieves a unique, site-specific key for encryption, ensuring it's 32 bytes long.
     * @return string The encryption key.
     */
    private function get_encryption_key() {
        // Use a unique, secure key from wp-config.php
        $key = defined('AUTH_KEY') ? AUTH_KEY : NONCE_KEY;
        // Generate a 32-byte key from the security constant using SHA-256 for openssl_encrypt
        return hash('sha256', $key, true); 
    }

    /**
     * Returns available meeting slots for a given date.
     */
    public function ajax_get_meeting_slots() {
        check_ajax_referer('maneli_meetings_nonce', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
        }
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        if (!$date) {
            wp_send_json_error(['message' => esc_html__('Invalid data sent.', 'maneli-car-inquiry')]);
        }
        $options = get_option('maneli_inquiry_all_options', []);
        $start = $options['meetings_start_hour'] ?? '10:00';
        $end   = $options['meetings_end_hour'] ?? '20:00';
        $slot  = max(5, (int)($options['meetings_slot_minutes'] ?? 30));
        
        // Check if the selected date is an excluded day
        $excluded_days = isset($options['meetings_excluded_days']) && is_array($options['meetings_excluded_days']) 
            ? $options['meetings_excluded_days'] 
            : [];
        
        if (!empty($excluded_days)) {
            $day_of_week = strtolower(date('l', strtotime($date)));
            if (in_array($day_of_week, $excluded_days)) {
                wp_send_json_error(['message' => esc_html__('This day is excluded from meeting schedules.', 'maneli-car-inquiry')]);
                return;
            }
        }

        $start_ts = strtotime($date . ' ' . $start);
        $end_ts   = strtotime($date . ' ' . $end);
        if ($end_ts <= $start_ts) {
            wp_send_json_error(['message' => esc_html__('Invalid schedule range.', 'maneli-car-inquiry')]);
        }

        // Fetch booked meetings on this date
        $meetings = get_posts([
            'post_type' => 'maneli_meeting',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $date . ' 00:00:00',
                    'before'=> $date . ' 23:59:59',
                    'inclusive' => true,
                ],
            ],
        ]);
        $busy_map = [];
        foreach ($meetings as $m) {
            $busy_map[get_post_meta($m->ID, 'meeting_start', true)] = true;
        }

        $slots = [];
        for ($t = $start_ts; $t < $end_ts; $t += $slot * 60) {
            $key = date('Y-m-d H:i', $t);
            $slots[] = [
                'time' => date('H:i', $t),
                'start' => $key,
                'available' => empty($busy_map[$key]),
            ];
        }
        wp_send_json_success(['slots' => $slots]);
    }

    /**
     * Books a meeting if the slot is still free.
     */
    public function ajax_book_meeting() {
        check_ajax_referer('maneli_meetings_nonce', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
        }
        $start = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : '';
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $type = isset($_POST['inquiry_type']) ? sanitize_key($_POST['inquiry_type']) : 'installment';
        if (!$start || !$inquiry_id) {
            wp_send_json_error(['message' => esc_html__('Invalid data sent.', 'maneli-car-inquiry')]);
        }
        
        // Validate time against settings
        $options = get_option('maneli_inquiry_all_options', []);
        $settings_start = $options['meetings_start_hour'] ?? '10:00';
        $settings_end = $options['meetings_end_hour'] ?? '20:00';
        $slot_minutes = max(5, (int)($options['meetings_slot_minutes'] ?? 30));
        
        // Extract date from $start (format: Y-m-d H:i)
        $start_timestamp = strtotime($start);
        if ($start_timestamp === false) {
            wp_send_json_error(['message' => esc_html__('Invalid time format.', 'maneli-car-inquiry')]);
        }
        
        $date = date('Y-m-d', $start_timestamp);
        
        // Check if the selected date is an excluded day
        $excluded_days = isset($options['meetings_excluded_days']) && is_array($options['meetings_excluded_days']) 
            ? $options['meetings_excluded_days'] 
            : [];
        
        if (!empty($excluded_days)) {
            $day_of_week = strtolower(date('l', $start_timestamp));
            if (in_array($day_of_week, $excluded_days)) {
                wp_send_json_error(['message' => esc_html__('This day is excluded from meeting schedules.', 'maneli-car-inquiry')]);
                return;
            }
        }
        
        $workday_start_ts = strtotime($date . ' ' . $settings_start);
        $workday_end_ts = strtotime($date . ' ' . $settings_end);
        
        if ($workday_end_ts <= $workday_start_ts) {
            wp_send_json_error(['message' => esc_html__('Invalid schedule range in settings.', 'maneli-car-inquiry')]);
        }
        
        // Check if time is within allowed range
        if ($start_timestamp < $workday_start_ts || $start_timestamp >= $workday_end_ts) {
            wp_send_json_error(['message' => esc_html__('Selected time is outside allowed working hours.', 'maneli-car-inquiry')]);
        }
        
        // Check if time aligns with slot intervals
        $time_diff = $start_timestamp - $workday_start_ts;
        $slot_seconds = $slot_minutes * 60;
        if ($time_diff % $slot_seconds !== 0) {
            wp_send_json_error(['message' => esc_html__('Selected time does not match available slot intervals.', 'maneli-car-inquiry')]);
        }
        
        // Check conflict
        $exists = get_posts([
            'post_type' => 'maneli_meeting',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_query' => [
                ['key' => 'meeting_start', 'value' => $start, 'compare' => '=']
            ]
        ]);
        if (!empty($exists)) {
            wp_send_json_error(['message' => esc_html__('Selected slot is no longer available.', 'maneli-car-inquiry')]);
        }

        $title = sprintf(esc_html__('Meeting - %s', 'maneli-car-inquiry'), date_i18n('Y/m/d H:i', strtotime($start)));
        $post_id = wp_insert_post([
            'post_type' => 'maneli_meeting',
            'post_title'=> $title,
            'post_status'=> 'publish',
            'post_author'=> get_current_user_id(),
        ]);
        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => esc_html__('Server error:', 'maneli-car-inquiry') . ' ' . $post_id->get_error_message()]);
        }
        update_post_meta($post_id, 'meeting_start', $start);
        update_post_meta($post_id, 'meeting_inquiry_id', $inquiry_id);
        update_post_meta($post_id, 'meeting_inquiry_type', $type);

        // Link for quick view in calendar
        wp_send_json_success(['message' => esc_html__('Meeting booked successfully.', 'maneli-car-inquiry')]);
    }

    /**
     * Returns HTML for product cards (image + title) with pagination, for confirm car step.
     */
    public function ajax_confirm_car_catalog() {
        check_ajax_referer('maneli_confirm_car_catalog_nonce', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }

        $paged = isset($_POST['page']) ? max(1, absint($_POST['page'])) : 1;
        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $per_page = 8; // 2 rows x 4 columns

        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            's'              => $search,
        ];

        // Exclude disabled products for non-admin users
        if (!current_user_can('manage_maneli_inquiries')) {
            $args['meta_query'] = [
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
        }

        // Optional: category filter
        if (!empty($_POST['category'])) {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = [];
            }
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['category']),
            ];
        }
        // Optional: brand filter (via attribute pa_brand)
        if (!empty($_POST['brand'])) {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = [];
            }
            $args['tax_query'][] = [
                'taxonomy' => 'pa_brand',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['brand']),
            ];
        }

        $query = new WP_Query($args);

        ob_start();
        if ($query->have_posts()) {
            while ($query->have_posts()) { $query->the_post();
                $pid = get_the_ID();
                $img = get_the_post_thumbnail($pid, 'medium');
                $product = wc_get_product($pid);
                $price = $product ? $product->get_price() : 0;
                echo '<div class="product-card selectable-car" data-product-id="' . esc_attr($pid) . '" data-product-price="' . esc_attr($price) . '" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform=\'scale(1.02)\'; this.style.boxShadow=\'0 4px 8px rgba(0,0,0,0.1)\';" onmouseout="this.style.transform=\'scale(1)\'; this.style.boxShadow=\'none\';">';
                echo '<div class="thumb">' . wp_kses_post($img) . '</div>';
                echo '<div class="title" style="text-align:center; margin-top:8px;">' . esc_html(get_the_title()) . '</div>';
                echo '<div class="text-center mt-2"><small class="text-muted">' . esc_html__('Click to select', 'maneli-car-inquiry') . '</small></div>';
                echo '</div>';
            }
        }
        $html = ob_get_clean();
        wp_reset_postdata();

        $pagination_html = paginate_links([
            'base'      => '#',
            'format'    => '?paged=%#%',
            'current'   => $paged,
            'total'     => max(1, (int)$query->max_num_pages),
            'prev_text' => esc_html__('&laquo; Previous', 'maneli-car-inquiry'),
            'next_text' => esc_html__('Next &raquo;', 'maneli-car-inquiry'),
            'type'      => 'plain'
        ]);

        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }

    /**
     * Decrypts data using AES-256-CBC.
     * @param string $encrypted_data The encrypted data (Base64 encoded).
     * @return string The decrypted data or empty string on failure.
     */
    private function decrypt_data($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }
        $key = $this->get_encryption_key();
        $cipher = 'aes-256-cbc';
        
        // Decode and separate IV and encrypted data
        $parts = explode('::', base64_decode($encrypted_data), 2);
        
        if (count($parts) !== 2) {
            return ''; // Invalid format or decryption failed
        }
        $encrypted = $parts[0];
        $iv = $parts[1];
        
        // Basic check for IV length
        if (strlen($iv) !== openssl_cipher_iv_length($cipher)) {
            return '';
        }

        // Decrypt
        $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
        
        return $decrypted === false ? '' : $decrypted;
    }

    /**
     * AJAX handler for Step 1: Saving the selected car and calculator data to user meta.
     * It recalculates the installment on the server to prevent client-side tampering.
     */
    public function handle_car_selection_ajax() {
        try {
            // Debug logging
            error_log('Maneli Debug: handle_car_selection_ajax called');
            error_log('Maneli Debug: POST data: ' . print_r($_POST, true));
            error_log('Maneli Debug: User logged in: ' . (is_user_logged_in() ? 'yes' : 'no'));
            error_log('Maneli Debug: Nonce received: ' . (isset($_POST['nonce']) ? substr($_POST['nonce'], 0, 10) . '...' : 'MISSING'));
            
            // Check nonce - try both 'nonce' and '_ajax_nonce' parameters
            $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : (isset($_POST['_ajax_nonce']) ? sanitize_text_field($_POST['_ajax_nonce']) : '');
            
            // Verify nonce with the expected action 'maneli_ajax_nonce'
            $nonce_valid = !empty($nonce) && wp_verify_nonce($nonce, 'maneli_ajax_nonce');
            
            if (!$nonce_valid) {
                error_log('Maneli Debug: Nonce verification failed');
                error_log('Maneli Debug: Expected action: maneli_ajax_nonce');
                error_log('Maneli Debug: Received nonce: ' . ($nonce ? substr($nonce, 0, 20) . '...' : 'EMPTY'));
                error_log('Maneli Debug: POST keys: ' . implode(', ', array_keys($_POST)));
                wp_send_json_error(['message' => esc_html__('Invalid security token. Please refresh the page and try again.', 'maneli-car-inquiry')]);
                return;
            }
            
            error_log('Maneli Debug: Nonce verified successfully');

            if (!is_user_logged_in()) {
                error_log('Maneli Debug: User not logged in');
                wp_send_json_error(['message' => esc_html__('Please log in to continue.', 'maneli-car-inquiry')]);
                return;
            }
            
            if (empty($_POST['product_id'])) {
                error_log('Maneli Debug: Product ID missing');
                wp_send_json_error(['message' => esc_html__('Product ID is required.', 'maneli-car-inquiry')]);
                return;
            }

            $user_id = get_current_user_id();
            $product_id = intval($_POST['product_id']);
            
            // Get product information for the new car
            $product = wc_get_product($product_id);
            if (!$product) {
                error_log('Maneli Debug: Product not found: ' . $product_id);
                wp_send_json_error(['message' => esc_html__('Product not found.', 'maneli-car-inquiry')]);
                return;
            }
            
            // Get product prices and settings
            $cash_price = (int)$product->get_regular_price();
            
            // Get installment_price
            $installment_price_raw = get_post_meta($product_id, 'installment_price', true);
            if (is_string($installment_price_raw) && !empty($installment_price_raw)) {
                $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                $installment_price_raw = str_replace($persian_digits, $english_digits, $installment_price_raw);
                $installment_price_raw = preg_replace('/[^\d]/', '', $installment_price_raw);
            }
            $installment_price = !empty($installment_price_raw) ? (int)$installment_price_raw : 0;
            if (empty($installment_price)) {
                $installment_price = $cash_price;
            }
            
            // Get min_downpayment for the new car
            $min_down_payment_raw = get_post_meta($product_id, 'min_downpayment', true);
            if (is_string($min_down_payment_raw) && !empty($min_down_payment_raw)) {
                $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                $min_down_payment_raw = str_replace($persian_digits, $english_digits, $min_down_payment_raw);
                $min_down_payment_raw = preg_replace('/[^\d]/', '', $min_down_payment_raw);
            }
            $min_down_payment = !empty($min_down_payment_raw) ? (int)$min_down_payment_raw : 0;
            
            // If min_downpayment is not set or is 0, calculate 20% of installment_price as default
            if ($min_down_payment <= 0 && $installment_price > 0) {
                $min_down_payment = (int)($installment_price * 0.2);
            }
            
            // Get total price - use POST value if provided (from modal calculator), otherwise use installment_price
            $total_price_post = sanitize_text_field($_POST['total_price'] ?? 0);
            $total_price_post = (int)preg_replace('/[^\d]/', '', str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $total_price_post));
            
            // Use POST total_price if valid (greater than 0), otherwise fallback to installment_price
            if ($total_price_post > 0) {
                $total_price = $total_price_post;
                error_log('Maneli Debug: Using POST total_price: ' . $total_price);
            } else {
                $total_price = $installment_price;
                error_log('Maneli Debug: Using product installment_price: ' . $total_price);
            }
            
            // Calculate max down payment (80% of total_price, not installment_price!)
            $max_down_payment = (int)($total_price * 0.8);
            
            // Get down payment - use POST value if provided and valid, otherwise use min_down_payment
            $down_payment_post = sanitize_text_field($_POST['down_payment'] ?? 0);
            $down_payment_post = (int)preg_replace('/[^\d]/', '', str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $down_payment_post));
            
            error_log('Maneli Debug: Down payment validation - POST: ' . $down_payment_post . ', Min: ' . $min_down_payment . ', Max: ' . $max_down_payment . ', Total Price: ' . $total_price);
            
            // Use POST down payment if it's valid (between min and max), otherwise use min
            if ($down_payment_post >= $min_down_payment && $down_payment_post <= $max_down_payment) {
                $down_payment = $down_payment_post;
                error_log('Maneli Debug: Using POST down_payment: ' . $down_payment);
            } else {
                $down_payment = $min_down_payment;
                error_log('Maneli Debug: POST down_payment invalid, using min_down_payment: ' . $down_payment);
            }
            
            // Get term months - use POST value if provided, otherwise default to 12
            $term_months = (int)sanitize_text_field($_POST['term_months'] ?? 12);
            if ($term_months < 12) {
                $term_months = 12;
            } elseif ($term_months > 36) {
                $term_months = 36;
            }
            
            // Validate loan amount
            $loan_amount = (int)$total_price - (int)$down_payment;
            if ($loan_amount <= 0) {
                error_log('Maneli Debug: Invalid loan amount calculated: ' . $loan_amount);
                wp_send_json_error(['message' => esc_html__('Invalid loan amount. Please check the down payment.', 'maneli-car-inquiry')]);
                return;
            }
            
            // Recalculate installment using server-side configured rate
            // Check if class exists
            if (!class_exists('Maneli_Render_Helpers')) {
                error_log('Maneli Debug: Maneli_Render_Helpers class not found');
                wp_send_json_error(['message' => esc_html__('Server error: Helper class not found.', 'maneli-car-inquiry')]);
                return;
            }
            
            // Check if method exists
            if (!method_exists('Maneli_Render_Helpers', 'calculate_installment_amount')) {
                error_log('Maneli Debug: calculate_installment_amount method not found');
                wp_send_json_error(['message' => esc_html__('Server error: Calculation method not found.', 'maneli-car-inquiry')]);
                return;
            }
            
            try {
                $recalculated_installment = Maneli_Render_Helpers::calculate_installment_amount($loan_amount, $term_months);
                
                if ($recalculated_installment <= 0) {
                    error_log('Maneli Debug: Invalid installment calculated: ' . $recalculated_installment);
                    wp_send_json_error(['message' => esc_html__('Invalid installment amount calculated. Please try again.', 'maneli-car-inquiry')]);
                    return;
                }
            } catch (Exception $e) {
                error_log('Maneli Debug: Exception in calculate_installment_amount: ' . $e->getMessage());
                error_log('Maneli Debug: Stack trace: ' . $e->getTraceAsString());
                wp_send_json_error(['message' => esc_html__('Server error: Failed to calculate installment.', 'maneli-car-inquiry') . ' ' . $e->getMessage()]);
                return;
            } catch (Error $e) {
                error_log('Maneli Debug: Fatal error in calculate_installment_amount: ' . $e->getMessage());
                error_log('Maneli Debug: Stack trace: ' . $e->getTraceAsString());
                wp_send_json_error(['message' => esc_html__('Server error: Failed to calculate installment.', 'maneli-car-inquiry') . ' ' . $e->getMessage()]);
                return;
            }
            
            error_log('Maneli Debug: Car replacement calculation: ' . print_r([
                'product_id' => $product_id,
                'total_price' => $total_price,
                'down_payment' => $down_payment,
                'term_months' => $term_months,
                'loan_amount' => $loan_amount,
                'installment' => $recalculated_installment
            ], true));
            
            $meta_to_save = [
                'maneli_selected_car_id'      => $product_id,
                'maneli_inquiry_step'         => 'form_pending',
                'maneli_inquiry_down_payment' => $down_payment,
                'maneli_inquiry_term_months'  => $term_months,
                'maneli_inquiry_total_price'  => $total_price,
                'maneli_inquiry_installment'  => $recalculated_installment, // Use server-calculated value
            ];

            foreach ($meta_to_save as $key => $value) {
                update_user_meta($user_id, $key, $value);
            }

            error_log('Maneli Debug: Car selection successful for user ' . $user_id . ', product ' . $product_id);
            
            // Return updated values so frontend can display them
            wp_send_json_success([
                'message' => esc_html__('Car replaced successfully.', 'maneli-car-inquiry'),
                'car_data' => [
                    'total_price' => $total_price,
                    'down_payment' => $down_payment,
                    'term_months' => $term_months,
                    'installment' => $recalculated_installment,
                    'min_down_payment' => $min_down_payment,
                    'max_down_payment' => $max_down_payment,
                ]
            ]);
        } catch (Exception $e) {
            error_log('Maneli Debug: Exception in handle_car_selection_ajax: ' . $e->getMessage());
            error_log('Maneli Debug: Exception file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('Maneli Debug: Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => esc_html__('Server error occurred. Please try again.', 'maneli-car-inquiry') . ' ' . (defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : '')]);
        } catch (Error $e) {
            error_log('Maneli Debug: Fatal error in handle_car_selection_ajax: ' . $e->getMessage());
            error_log('Maneli Debug: Fatal error file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('Maneli Debug: Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => esc_html__('Server error occurred. Please try again.', 'maneli-car-inquiry') . ' ' . (defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : '')]);
        }
    }

    /**
     * Handler for Step 2: Processing the identity form submission.
     */
    public function handle_identity_submission() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_submit_identity_nonce')) {
            wp_die(esc_html__('Security check failed!', 'maneli-car-inquiry'));
        }
        if (!is_user_logged_in()) {
            wp_redirect(home_url());
            exit;
        }

        $user_id = get_current_user_id();
        
        $required_fields = ['first_name', 'last_name', 'national_code', 'mobile_number'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_die(sprintf(esc_html__('Error: The field "%s" is required.', 'maneli-car-inquiry'), $field));
            }
        }
        
        $buyer_fields = [
            'first_name', 'last_name', 'father_name', 'national_code',
            // occupation is populated from job_title below to keep BC with admin views
            'occupation', 'job_type', 'job_title',
            'income_level', 'mobile_number', 'phone_number', 'residency_status', 
            'workplace_status', 'address', 'birth_date', 'bank_name', 'account_number',
            'branch_code', 'branch_name'
        ];
        $buyer_data = [];
        foreach ($buyer_fields as $key) {
            $buyer_data[$key] = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : '';
        }

        // Map new job title to legacy occupation field for compatibility
        if (!empty($buyer_data['job_title'])) {
            $buyer_data['occupation'] = $buyer_data['job_title'];
        }

        $issuer_type = isset($_POST['issuer_type']) ? sanitize_key($_POST['issuer_type']) : 'self';
        $issuer_data = [];
        if ($issuer_type === 'other') {
            $issuer_fields = [
                'issuer_first_name', 'issuer_last_name', 'issuer_national_code', 'issuer_bank_name', 'issuer_account_number',
                'issuer_branch_code', 'issuer_branch_name', 'issuer_residency_status', 
                'issuer_address', 'issuer_phone_number', 'issuer_father_name', 'issuer_income_level',
                // occupation is populated from issuer_job_title below
                'issuer_occupation', 'issuer_job_type', 'issuer_job_title'
            ];
            foreach ($issuer_fields as $key) {
                // Address can be multi-line
                $issuer_data[$key] = isset($_POST[$key]) ? sanitize_textarea_field($_POST[$key]) : '';
            }

            // Combine first and last name for backward compatibility
            if (!empty($issuer_data['issuer_first_name']) || !empty($issuer_data['issuer_last_name'])) {
                $issuer_data['issuer_full_name'] = trim($issuer_data['issuer_first_name'] . ' ' . $issuer_data['issuer_last_name']);
            }

            // Map new issuer job title to legacy issuer_occupation field
            if (!empty($issuer_data['issuer_job_title'])) {
                $issuer_data['issuer_occupation'] = $issuer_data['issuer_job_title'];
            }
        }

        // Store sanitized data temporarily, waiting for car confirmation
        update_user_meta($user_id, 'maneli_temp_inquiry_data', [
            'buyer_data'  => $buyer_data,
            'issuer_data' => $issuer_data,
            'issuer_type' => $issuer_type,
        ]);

        // Always go to step 3 (confirm car) - user must confirm their selected car
        update_user_meta($user_id, 'maneli_inquiry_step', 'confirm_car_pending');

        // Redirect to wizard step 3 (confirm car)
        wp_redirect(home_url('/dashboard/new-inquiry?step=3'));
        exit;
    }

    /**
     * Handles Step 3 confirmation and routes to payment step.
     */
    public function handle_confirm_car_step() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_confirm_car_step_nonce')) {
            wp_die(esc_html__('Security check failed!', 'maneli-car-inquiry'));
        }
        $user_id = 0;
        
        // Try WordPress user first
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
        }
        
        // Fallback to session
        if (!$user_id || $user_id <= 0) {
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
            
            if (isset($_SESSION['maneli']['user_id']) && !empty($_SESSION['maneli']['user_id'])) {
                $user_id = (int)$_SESSION['maneli']['user_id'];
            } elseif (isset($_SESSION['maneli_user_id']) && !empty($_SESSION['maneli_user_id'])) {
                $user_id = (int)$_SESSION['maneli_user_id'];
            }
        }
        
        if (!$user_id || $user_id <= 0) {
            wp_redirect(home_url());
            exit;
        }
        
        if (empty($_POST['confirm_car_agree'])) {
            wp_die(esc_html__('Please confirm your selected car to continue.', 'maneli-car-inquiry'));
        }

        // Check if payment is required
        $options = get_option('maneli_inquiry_all_options', []);
        $payment_enabled = !empty($options['payment_enabled']) && $options['payment_enabled'] == '1';
        $inquiry_fee = !empty($options['inquiry_fee']) ? (int)$options['inquiry_fee'] : 0;

        if ($payment_enabled && $inquiry_fee > 0) {
            // Payment is required - move to payment step
            update_user_meta($user_id, 'maneli_inquiry_step', 'payment_pending');
            wp_redirect(home_url('/dashboard/new-inquiry?step=4'));
        } else {
            // No payment needed - finalize the inquiry right away
            do_action('maneli_inquiry_payment_successful', $user_id);
            // Clean up step meta after successful completion
            delete_user_meta($user_id, 'maneli_inquiry_step');
            wp_redirect(home_url('/dashboard/installment-inquiries'));
        }
        exit;
    }
    
    /**
     * Callback for the hook `maneli_inquiry_payment_successful`.
     * This is the decoupled entry point for finalizing an installment inquiry.
     * @param int $user_id The user ID passed from the hook.
     */
    public function finalize_inquiry_from_hook($user_id) {
        $this->finalize_inquiry($user_id, true);
    }
    
    /**
     * Callback for the hook `maneli_cash_inquiry_payment_successful`.
     * This updates the status of a cash inquiry post after successful payment.
     * @param int $user_id The user ID.
     * @param int $inquiry_id The cash inquiry post ID.
     */
    public function finalize_cash_inquiry_from_hook($user_id, $inquiry_id) {
        if ($inquiry_id && get_post_type($inquiry_id) === 'cash_inquiry') {
            $old_status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
            if (empty($old_status)) {
                $old_status = 'new';
            }
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'completed');
            
            // Send notification
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
            Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $old_status, 'completed');
        }
    }

    /**
     * Finalizes the inquiry process by creating the post, calling APIs, and sending notifications.
     *
     * @param int  $user_id The ID of the user.
     * @param bool $is_new  Whether this is a brand new inquiry (for notification purposes).
     * @return bool True on success, false on failure.
     */
    public function finalize_inquiry($user_id, $is_new = false) {
        $temp_data = get_user_meta($user_id, 'maneli_temp_inquiry_data', true);
        if (empty($temp_data)) {
            return false;
        }

        $buyer_data = $temp_data['buyer_data'];
        $issuer_data = $temp_data['issuer_data'];
        $issuer_type = $temp_data['issuer_type'];

        // Update user profile with submitted data
        wp_update_user(['ID' => $user_id, 'first_name' => $buyer_data['first_name'], 'last_name' => $buyer_data['last_name']]);
        foreach ($buyer_data as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }

        $national_code_for_api = ($issuer_type === 'other' && !empty($issuer_data['issuer_national_code']))
            ? $issuer_data['issuer_national_code']
            : $buyer_data['national_code'];

        $finotex_result = $this->execute_finotex_inquiry($national_code_for_api);

        $car_id = get_user_meta($user_id, 'maneli_selected_car_id', true);
        $post_title = sprintf(
            '%s: %s - %s',
            esc_html__('Inquiry for', 'maneli-car-inquiry'),
            get_the_title($car_id),
            $buyer_data['first_name'] . ' ' . $buyer_data['last_name']
        );
        
        $post_id = wp_insert_post([
            'post_title'   => $post_title,
            'post_content' => "Finotex API raw response:\n<pre>" . esc_textarea($finotex_result['raw_response']) . "</pre>",
            'post_status'  => 'publish',
            'post_author'  => $user_id,
            'post_type'    => 'inquiry'
        ]);

        if (is_wp_error($post_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Inquiry Error: Failed to insert post. ' . $post_id->get_error_message());
                }
            }
            return false;
        }

        $initial_status = ($finotex_result['status'] === 'DONE') ? 'pending' : 'failed';
        if ($finotex_result['status'] === 'SKIPPED') {
            $initial_status = 'pending';
        }
        
        update_post_meta($post_id, 'inquiry_status', $initial_status);
        update_post_meta($post_id, 'issuer_type', $issuer_type);
        
        // Create notifications about new installment inquiry
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        
        $customer_name = $buyer_data['first_name'] . ' ' . $buyer_data['last_name'];
        $car_name = get_the_title($car_id);
        
        // Notify customer
        if ($user_id > 0) {
            $customer_notification = Maneli_Notification_Handler::create_notification(array(
                'user_id' => $user_id,
                'type' => 'inquiry_new',
                'title' => esc_html__('Your installment request has been submitted', 'maneli-car-inquiry'),
                'message' => sprintf(esc_html__('Your installment request for %s has been successfully submitted and is being reviewed', 'maneli-car-inquiry'), $car_name),
                'link' => home_url('/dashboard/inquiries/installment?inquiry_id=' . $post_id),
                'related_id' => $post_id,
            ));
            if (defined('WP_DEBUG') && WP_DEBUG && !$customer_notification) {
                error_log('Maneli: Failed to create customer notification for installment inquiry ' . $post_id . ', user_id: ' . $user_id);
            }
        }
        
        // Notify all managers and admins
        $managers = get_users(array(
            'role__in' => array('administrator', 'maneli_admin'),
            'fields' => 'ids'
        ));
        
        foreach ($managers as $manager_id) {
            $manager_notification = Maneli_Notification_Handler::create_notification(array(
                'user_id' => $manager_id,
                'type' => 'inquiry_new',
                'title' => esc_html__('New Installment Inquiry', 'maneli-car-inquiry'),
                'message' => sprintf(esc_html__('A new installment inquiry from %s for %s has been registered', 'maneli-car-inquiry'), $customer_name, $car_name),
                'link' => home_url('/dashboard/inquiries/installment?inquiry_id=' . $post_id),
                'related_id' => $post_id,
            ));
            if (defined('WP_DEBUG') && WP_DEBUG && !$manager_notification) {
                error_log('Maneli: Failed to create manager notification for installment inquiry ' . $post_id . ', manager_id: ' . $manager_id);
            }
        }
        
        // Mark that payment was completed (even if amount was 0 due to discount or free inquiry)
        update_post_meta($post_id, 'inquiry_payment_completed', 'yes');
        update_post_meta($post_id, 'inquiry_payment_date', current_time('mysql'));
        
        // Also store the actual amount paid (0 if discount or free)
        $options = get_option('maneli_inquiry_all_options', []);
        $inquiry_fee_amount = (int)($options['inquiry_fee'] ?? 0);
        $discount_applied = get_user_meta($user_id, 'maneli_discount_applied', true) === 'yes';
        $paid_amount = ($discount_applied || $inquiry_fee_amount == 0) ? 0 : $inquiry_fee_amount;
        update_post_meta($post_id, 'inquiry_paid_amount', $paid_amount);
        
        // Save transaction details if available (from payment gateway)
        $transaction_data = get_user_meta($user_id, 'maneli_last_payment_transaction', true);
        if (!empty($transaction_data)) {
            update_post_meta($post_id, 'payment_gateway', $transaction_data['gateway'] ?? '');
            if (isset($transaction_data['authority'])) {
                update_post_meta($post_id, 'payment_authority', $transaction_data['authority']);
            }
            if (isset($transaction_data['ref_id'])) {
                update_post_meta($post_id, 'payment_ref_id', $transaction_data['ref_id']);
            }
            if (isset($transaction_data['order_id'])) {
                update_post_meta($post_id, 'payment_order_id', $transaction_data['order_id']);
            }
            if (isset($transaction_data['token'])) {
                update_post_meta($post_id, 'payment_token', $transaction_data['token']);
            }
            // Clean up user meta
            delete_user_meta($user_id, 'maneli_last_payment_transaction');
        }
        
        foreach ($buyer_data as $key => $value) { update_post_meta($post_id, $key, $value); }
        if (!empty($issuer_data)) { foreach ($issuer_data as $key => $value) { update_post_meta($post_id, $key, $value); } }
        
        update_post_meta($post_id, 'product_id', $car_id);
        update_post_meta($post_id, '_finotex_response_data', $finotex_result['data']);
        
        // Ensure the installment amount is calculated with the current server-side rate and is saved to the post meta.
        $down_payment = get_user_meta($user_id, 'maneli_inquiry_down_payment', true);
        $total_price = get_user_meta($user_id, 'maneli_inquiry_total_price', true);
        $term_months = get_user_meta($user_id, 'maneli_inquiry_term_months', true);
        
        $loan_amount = (int)$total_price - (int)$down_payment;
        // USE CENTRALIZED HELPER
        $recalculated_installment = Maneli_Render_Helpers::calculate_installment_amount($loan_amount, (int)$term_months);
        
        $calculator_meta_keys = [
            'maneli_inquiry_down_payment', 
            'maneli_inquiry_term_months', 
            'maneli_inquiry_total_price'
        ];
        
        foreach($calculator_meta_keys as $key) {
            $value = get_user_meta($user_id, $key, true);
            if ($value) { update_post_meta($post_id, $key, $value); }
        }
        
        // IMPORTANT: Overwrite/Save the final, server-calculated installment amount
        update_post_meta($post_id, 'maneli_inquiry_installment', $recalculated_installment); 
        
        if ($is_new && $initial_status === 'pending') {
            $this->send_new_inquiry_notifications($buyer_data, $car_id);
        }
        
        $this->cleanup_user_meta($user_id, $calculator_meta_keys);
        
        return true;
    }

    /**
     * Executes the Finotex API call to get cheque status.
     * @param string $national_code The national ID code to check.
     * @return array An array containing the status, data, and raw response.
     */
    public function execute_finotex_inquiry($national_code) {
        $options = get_option('maneli_inquiry_all_options', []);
        
        if (empty($options['finotex_enabled']) || $options['finotex_enabled'] !== '1' || !defined('MANELI_FINOTEX_API_URL')) {
            return ['status' => 'SKIPPED', 'data' => null, 'raw_response' => 'Finotex inquiry is disabled in settings or API constant is missing.'];
        }

        // === START: SECURITY IMPROVEMENT - Check for constants first, then decrypt ===
        $client_id_raw = $options['finotex_username'] ?? '';
        $api_key_raw = $options['finotex_password'] ?? '';
        
        $client_id = defined('MANELI_FINOTEX_CLIENT_ID') ? MANELI_FINOTEX_CLIENT_ID : $this->decrypt_data($client_id_raw);
        $api_key = defined('MANELI_FINOTEX_API_KEY') ? MANELI_FINOTEX_API_KEY : $this->decrypt_data($api_key_raw);
        // === END: SECURITY IMPROVEMENT ===
        
        $result = ['status' => 'FAILED', 'data' => null, 'raw_response' => ''];

        if (empty($client_id) || empty($api_key)) {
            $result['raw_response'] = 'Plugin Error: Finotex Client ID or API Key not set (missing required credentials/encrypted data).';
            return $result;
        }

        // FIX: Using MANELI_FINOTEX_API_URL constant
        $api_url = sprintf(MANELI_FINOTEX_API_URL, $client_id);
        
        $track_id = 'maneli_' . uniqid();
        $api_url_with_params = add_query_arg(['idCode' => $national_code, 'trackId' => $track_id], $api_url);
        
        $response = wp_remote_get($api_url_with_params, [
            'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Accept' => 'application/json'],
            'timeout' => 45,
        ]);

        if (is_wp_error($response)) {
            $result['raw_response'] = 'WordPress Connection Error: ' . $response->get_error_message();
            return $result;
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);
        $response_data = json_decode($response_body, true);

        $result['raw_response'] = "Request URL: {$api_url_with_params}\nResponse Code: {$response_code}\nResponse Body:\n" . print_r($response_body, true);

        if ($response_code === 200 && isset($response_data['status']) && $response_data['status'] === 'DONE') {
            $result['status'] = 'DONE';
            $result['data'] = $response_data;
        }

        return $result;
    }

    /**
     * Handles the user's request to retry a failed inquiry.
     */
    public function handle_inquiry_retry() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_retry_inquiry_nonce')) {
            wp_die(esc_html__('Security check failed!', 'maneli-car-inquiry'));
        }
        if (!is_user_logged_in() || empty($_POST['inquiry_id'])) {
            wp_die(esc_html__('Invalid request.', 'maneli-car-inquiry'));
        }
        
        $user_id = get_current_user_id();
        $post_id = intval($_POST['inquiry_id']);

        if (get_post_field('post_author', $post_id) != $user_id) {
            wp_die(esc_html__('Access denied.', 'maneli-car-inquiry'));
        }

        wp_delete_post($post_id, true);
        update_user_meta($user_id, 'maneli_inquiry_step', 'form_pending');
        
        wp_redirect(home_url('/dashboard/installment-inquiries'));
        exit;
    }

    /**
     * Sends notifications to admin and customer for a new inquiry.
     */
    private function send_new_inquiry_notifications($buyer_data, $car_id) {
        $sms_handler = new Maneli_SMS_Handler();
        $customer_name = ($buyer_data['first_name'] ?? '') . ' ' . ($buyer_data['last_name'] ?? '');
        $car_name = get_the_title($car_id) ?? '';
        $options = get_option('maneli_inquiry_all_options', []);

        // Notify Admin
        $admin_mobile = $options['admin_notification_mobile'] ?? '';
        $pattern_admin = $options['sms_pattern_new_inquiry'] ?? 0;
        if (!empty($admin_mobile) && $pattern_admin > 0) {
            $sms_handler->send_pattern($pattern_admin, $admin_mobile, [$customer_name, $car_name]);
        }

        // Notify Customer
        $customer_mobile = $buyer_data['mobile_number'] ?? '';
        $pattern_customer = $options['sms_pattern_pending'] ?? 0;
        if (!empty($customer_mobile) && $pattern_customer > 0) {
            $sms_handler->send_pattern($pattern_customer, $customer_mobile, [$customer_name, $car_name]);
        }
    }
    
    /**
     * Cleans up all temporary meta fields from the user's profile after inquiry creation.
     */
    private function cleanup_user_meta($user_id, $calculator_meta_keys) {
        $keys_to_delete = array_merge(
            $calculator_meta_keys,
            ['maneli_inquiry_step', 'maneli_selected_car_id', 'maneli_temp_inquiry_data']
        );
        foreach ($keys_to_delete as $key) {
            delete_user_meta($user_id, $key);
        }
    }
}