<?php
/**
 * AJAX Handler for Elementor Home Page
 * Handles form submissions, product filters, and other AJAX requests
 * 
 * @package Maneli_Car_Inquiry/Includes/Elementor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Elementor_AJAX_Handler {

    /**
     * Initialize AJAX handlers
     */
    public static function init() {
        // Contact form submission
        add_action('wp_ajax_nopriv_maneli_contact_form', [__CLASS__, 'handle_contact_form']);
        add_action('wp_ajax_maneli_contact_form', [__CLASS__, 'handle_contact_form']);

        // Product filter
        add_action('wp_ajax_nopriv_maneli_filter_products', [__CLASS__, 'handle_product_filter']);
        add_action('wp_ajax_maneli_filter_products', [__CLASS__, 'handle_product_filter']);

        // Newsletter subscription
        add_action('wp_ajax_nopriv_maneli_subscribe_newsletter', [__CLASS__, 'handle_newsletter_subscription']);
        add_action('wp_ajax_maneli_subscribe_newsletter', [__CLASS__, 'handle_newsletter_subscription']);
    }

    /**
     * Handle contact form submission
     */
    public static function handle_contact_form() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'maneli_home_nonce')) {
            wp_send_json_error([
                'message' => __('خطای امنیتی. لطفاً صفحه را بارگذاری کنید.', 'maneli-car-inquiry')
            ]);
            wp_die();
        }

        // Get form data
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

        // Validation
        $errors = [];

        if (empty($name)) {
            $errors[] = __('نام الزامی است', 'maneli-car-inquiry');
        }

        if (empty($email) || !is_email($email)) {
            $errors[] = __('ایمیل معتبر الزامی است', 'maneli-car-inquiry');
        }

        if (empty($phone)) {
            $errors[] = __('شماره تماس الزامی است', 'maneli-car-inquiry');
        }

        if (empty($message)) {
            $errors[] = __('پیام الزامی است', 'maneli-car-inquiry');
        }

        if (!empty($errors)) {
            wp_send_json_error([
                'message' => implode(' | ', $errors)
            ]);
            wp_die();
        }

        // Save to database
        $inquiry_id = Maneli_Elementor_AJAX_Handler::save_inquiry($name, $email, $phone, $message);

        if (!$inquiry_id) {
            wp_send_json_error([
                'message' => __('خطا در ثبت درخواست. لطفاً دوباره تلاش کنید.', 'maneli-car-inquiry')
            ]);
            wp_die();
        }

        // Send email notification
        self::send_notification_email($name, $email, $phone, $message);

        // Return success response
        wp_send_json_success([
            'message' => __('درخواست شما با موفقیت ثبت شد. به زودی با شما تماس خواهیم گرفت.', 'maneli-car-inquiry'),
            'inquiry_id' => $inquiry_id
        ]);
        wp_die();
    }

    /**
     * Save inquiry to database
     */
    private static function save_inquiry($name, $email, $phone, $message) {
        global $wpdb;

        // Try to save as WordPress comment/post meta for easy management
        if (post_type_exists('inquiry')) {
            $inquiry_id = wp_insert_post([
                'post_type' => 'inquiry',
                'post_title' => sprintf(__('درخواست از %s', 'maneli-car-inquiry'), $name),
                'post_content' => $message,
                'post_status' => 'pending',
            ]);

            if ($inquiry_id) {
                update_post_meta($inquiry_id, 'inquiry_name', $name);
                update_post_meta($inquiry_id, 'inquiry_email', $email);
                update_post_meta($inquiry_id, 'inquiry_phone', $phone);
                update_post_meta($inquiry_id, 'inquiry_source', 'elementor_home');

                return $inquiry_id;
            }
        }

        return false;
    }

    /**
     * Send notification email
     */
    private static function send_notification_email($name, $email, $phone, $message) {
        $blog_name = get_bloginfo('name');
        $admin_email = get_option('admin_email');

        // Admin email
        $admin_subject = sprintf(__('[%s] درخواست جدید از %s', 'maneli-car-inquiry'), $blog_name, $name);
        $admin_message = sprintf(
            __("درخواست جدید دریافت شده:\n\nنام: %s\nایمیل: %s\nشماره تماس: %s\n\nپیام:\n%s", 'maneli-car-inquiry'),
            $name,
            $email,
            $phone,
            $message
        );

        wp_mail($admin_email, $admin_subject, $admin_message);

        // Customer email
        $customer_subject = sprintf(__('تشکر از درخواست شما - %s', 'maneli-car-inquiry'), $blog_name);
        $customer_message = sprintf(
            __("سلام %s،\n\nتشکر از درخواست شما. ما درخواست شما را دریافت کردیم و به زودی با شما تماس خواهیم گرفت.\n\nبا احترام،\n%s", 'maneli-car-inquiry'),
            $name,
            $blog_name
        );

        wp_mail($email, $customer_subject, $customer_message);
    }

    /**
     * Handle product filter
     */
    public static function handle_product_filter() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'maneli_home_nonce')) {
            wp_send_json_error(['message' => __('خطای امنیتی', 'maneli-car-inquiry')]);
            wp_die();
        }

        $filter = isset($_POST['filter']) ? sanitize_text_field(wp_unslash($_POST['filter'])) : 'all';
        $paged = isset($_POST['paged']) ? intval(wp_unslash($_POST['paged'])) : 1;

        // Get products
        $args = [
            'post_type' => 'product',
            'posts_per_page' => 12,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => []
        ];

        // Apply filter
        if ($filter !== 'all') {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $filter
                ]
            ];
        }

        $products = get_posts($args);
        $total_pages = ceil(wp_count_posts('product')->publish / 12);

        ob_start();

        if (!empty($products)) {
            foreach ($products as $product) {
                set_post_data($product);
                $woo_product = wc_get_product($product->ID);

                // Get product category for filter data
                $categories = get_the_terms($product->ID, 'product_cat');
                $category_slug = !empty($categories) ? $categories[0]->slug : 'all';

                ?>
                <div class="product-card" data-filter="<?php echo esc_attr($category_slug); ?>">
                    <div class="product-image">
                        <?php if (has_post_thumbnail($product->ID)) {
                            echo get_the_post_thumbnail($product->ID, 'medium', ['class' => 'w-100']);
                        } else {
                            echo '<img src="' . esc_url(wc_placeholder_img_src()) . '" alt="' . esc_attr($product->post_title) . '">';
                        } ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo esc_html($product->post_title); ?></h3>
                        <div class="product-price">
                            <?php if ($woo_product) {
                                echo wp_kses_post($woo_product->get_price_html());
                            } ?>
                        </div>
                        <a href="<?php echo esc_url($product->guid); ?>" class="btn-view-details">
                            <?php _e('مشاهده جزئیات', 'maneli-car-inquiry'); ?>
                        </a>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<p class="text-center">' . esc_html(__('محصولی یافت نشد', 'maneli-car-inquiry')) . '</p>';
        }

        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'total_pages' => $total_pages,
            'current_page' => $paged
        ]);
        wp_die();
    }

    /**
     * Handle newsletter subscription
     */
    public static function handle_newsletter_subscription() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'maneli_home_nonce')) {
            wp_send_json_error(['message' => __('خطای امنیتی', 'maneli-car-inquiry')]);
            wp_die();
        }

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => __('لطفاً ایمیل معتبر وارد کنید', 'maneli-car-inquiry')]);
            wp_die();
        }

        // Check if email already subscribed
        $subscriber = get_posts([
            'post_type' => 'newsletter_subscriber',
            'meta_query' => [
                [
                    'key' => 'email',
                    'value' => $email,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1
        ]);

        if (!empty($subscriber)) {
            wp_send_json_error(['message' => __('این ایمیل قبلاً ثبت شده است', 'maneli-car-inquiry')]);
            wp_die();
        }

        // Save subscriber
        $subscriber_id = wp_insert_post([
            'post_type' => 'newsletter_subscriber',
            'post_title' => $email,
            'post_status' => 'publish'
        ]);

        if ($subscriber_id) {
            update_post_meta($subscriber_id, 'email', $email);
            update_post_meta($subscriber_id, 'subscribed_date', current_time('mysql'));

            wp_send_json_success(['message' => __('با تشکر از عضویت! ایمیل تاییدی برای شما ارسال شد.', 'maneli-car-inquiry')]);
        } else {
            wp_send_json_error(['message' => __('خطا در ثبت نام. لطفاً دوباره تلاش کنید.', 'maneli-car-inquiry')]);
        }

        wp_die();
    }
}

// Initialize AJAX handlers
Maneli_Elementor_AJAX_Handler::init();
