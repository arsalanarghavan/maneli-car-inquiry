<?php
/**
 * Handles the [car_inquiry_form] shortcode which displays the multi-step inquiry process for customers
 * or a direct inquiry creation form for experts/admins.
 *
 * @package Maneli_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Inquiry_Form_Shortcode {

    private $datepicker_loaded = false;

    public function __construct() {
        add_shortcode('car_inquiry_form', [$this, 'render_shortcode']);
    }

    /**
     * Main render method for the [car_inquiry_form] shortcode.
     * Acts as a router to display the correct view based on user role and inquiry status.
     *
     * @return string The HTML output for the shortcode.
     */
    public function render_shortcode() {
        // 1. Check if user is logged in
        if (!is_user_logged_in()) {
            return maneli_get_template_part('shortcodes/inquiry-form/login-prompt', [], false);
        }

        // 2. If user is an expert or admin, show the creation form
        if (current_user_can('manage_maneli_inquiries') || current_user_can('maneli_expert')) {
            return $this->render_expert_new_inquiry_form();
        }

        // 3. For customers, determine their current step in the inquiry process
        $user_id = get_current_user_id();
        $latest_inquiry = get_posts([
            'author'         => $user_id,
            'post_type'      => 'inquiry',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => ['publish', 'private'],
        ]);
        $inquiry_step_meta = get_user_meta($user_id, 'maneli_inquiry_step', true);

        ob_start();

        echo '<div class="maneli-inquiry-wrapper">';
        
        // Handle payment status messages from gateway redirect
        if (isset($_GET['payment_status'])) {
            $this->display_payment_message(sanitize_text_field($_GET['payment_status']));
        }

        // Determine which step of the progress tracker is active
        $active_step = 1;
        if ($latest_inquiry) {
            $status = get_post_meta($latest_inquiry[0]->ID, 'inquiry_status', true);
            $active_step = in_array($status, ['user_confirmed', 'rejected']) ? 6 : 5;
        } elseif ($inquiry_step_meta === 'form_pending') {
            $active_step = 2;
        } elseif ($inquiry_step_meta === 'confirm_car_pending') {
            $active_step = 3;
        } elseif ($inquiry_step_meta === 'payment_pending') {
            $active_step = 4;
        }
        
        // Render the progress tracker
        maneli_get_template_part('shortcodes/inquiry-form/progress-tracker', ['active_step' => $active_step]);

        // Render the content for the current step
        if ($latest_inquiry) {
            $inquiry_post = $latest_inquiry[0];
            $status = get_post_meta($inquiry_post->ID, 'inquiry_status', true);
            switch ($status) {
                case 'user_confirmed':
                case 'rejected':
                    maneli_get_template_part('shortcodes/inquiry-form/step-5-final-report', ['inquiry_id' => $inquiry_post->ID]);
                    break;
                case 'failed':
                    maneli_get_template_part('shortcodes/inquiry-form/step-failed', ['inquiry_id' => $inquiry_post->ID]);
                    break;
                default: // 'pending', 'more_docs', etc.
                    maneli_get_template_part('shortcodes/inquiry-form/step-4-wait-message', ['status' => $status]);
                    break;
            }
        } else {
            switch ($inquiry_step_meta) {
                case 'form_pending':
                    $this->render_step2_identity_form($user_id);
                    break;
                case 'confirm_car_pending':
                    $this->render_step3_confirm_car($user_id);
                    break;
                case 'payment_pending':
                    maneli_get_template_part('shortcodes/inquiry-form/step-3-payment', ['user_id' => $user_id]);
                    break;
                default:
                    maneli_get_template_part('shortcodes/inquiry-form/step-1-car-selection');
                    break;
            }
        }

        echo '</div>'; // .maneli-inquiry-wrapper
        
        return ob_get_clean();
    }

    /**
     * Renders Step 2: The identity form, by loading its template and passing data.
     *
     * @param int $user_id The current user's ID.
     */
    private function render_step2_identity_form($user_id) {
        $this->load_datepicker_assets();
        
        $car_id = get_user_meta($user_id, 'maneli_selected_car_id', true);
        $product = $car_id ? wc_get_product($car_id) : null;
        if (!$product) {
            echo '<div class="error-box"><p>' . esc_html__('Could not find the selected car. Please start over.', 'maneli-car-inquiry') . '</p></div>';
            return;
        }

        $template_args = [
            'user_id'             => $user_id,
            'user_info'           => get_userdata($user_id),
            'car_name'            => $product->get_name(),
            'car_model'           => $product->get_attribute('pa_model'),
            'car_image_html'      => get_the_post_thumbnail($car_id, 'medium'),
            'down_payment'        => get_user_meta($user_id, 'maneli_inquiry_down_payment', true),
            'term_months'         => get_user_meta($user_id, 'maneli_inquiry_term_months', true),
            'total_price'         => get_user_meta($user_id, 'maneli_inquiry_total_price', true),
            'installment_amount'  => get_user_meta($user_id, 'maneli_inquiry_installment', true),
        ];

        maneli_get_template_part('shortcodes/inquiry-form/step-2-identity-form', $template_args);
    }

    /**
     * Renders Step 3: Confirm selected car (read-only selection with catalog preview).
     *
     * @param int $user_id The current user's ID.
     */
    private function render_step3_confirm_car($user_id) {
        $this->load_datepicker_assets();

        $car_id = get_user_meta($user_id, 'maneli_selected_car_id', true);
        $product = $car_id ? wc_get_product($car_id) : null;
        if (!$product) {
            echo '<div class="error-box"><p>' . esc_html__('Could not find the selected car. Please start over.', 'maneli-car-inquiry') . '</p></div>';
            return;
        }

        $template_args = [
            'user_id'             => $user_id,
            'car_id'              => $car_id,
            'car_name'            => $product->get_name(),
            'car_model'           => $product->get_attribute('pa_model'),
            'car_image_html'      => get_the_post_thumbnail($car_id, 'medium'),
            'down_payment'        => get_user_meta($user_id, 'maneli_inquiry_down_payment', true),
            'term_months'         => get_user_meta($user_id, 'maneli_inquiry_term_months', true),
            'total_price'         => get_user_meta($user_id, 'maneli_inquiry_total_price', true),
            'installment_amount'  => get_user_meta($user_id, 'maneli_inquiry_installment', true),
        ];

        maneli_get_template_part('shortcodes/inquiry-form/step-3-confirm-car', $template_args);
    }
    
    /**
     * Renders the inquiry creation form for experts and admins.
     *
     * @return string HTML output for the expert form.
     */
    private function render_expert_new_inquiry_form() {
        $this->load_datepicker_assets();
        
        // Enqueue expert panel JS for car search
        wp_enqueue_script(
            'maneli-expert-panel-js',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/expert-panel.js',
            ['jquery', 'select2'],
            '1.0.0',
            true
        );
        
        // Localize the script
        $options = get_option('maneli_inquiry_all_options', []);
        $interest_rate = floatval($options['loan_interest_rate'] ?? 0.035);
        
        wp_localize_script('maneli-expert-panel-js', 'maneli_expert_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('maneli_expert_car_search_nonce'),
            'interestRate' => $interest_rate,
            'text'     => [
                'car_search_placeholder' => esc_html__('Search for a car name...', 'maneli-car-inquiry'),
                'down_payment_label'     => esc_html__('Down Payment Amount (Toman)', 'maneli-car-inquiry'),
                'min_down_payment_desc'  => esc_html__('Minimum recommended down payment:', 'maneli-car-inquiry'),
                'term_months_label'      => esc_html__('Repayment Period (Months)', 'maneli-car-inquiry'),
                'loan_amount_label'      => esc_html__('Total Loan Amount', 'maneli-car-inquiry'),
                'total_repayment_label'  => esc_html__('Total Repayment Amount', 'maneli-car-inquiry'),
                'installment_amount_label' => esc_html__('Approximate Installment Amount', 'maneli-car-inquiry'),
                'toman'                  => esc_html__('Toman', 'maneli-car-inquiry'),
                'term_12'                => esc_html__('12 Months', 'maneli-car-inquiry'),
                'term_18'                => esc_html__('18 Months', 'maneli-car-inquiry'),
                'term_24'                => esc_html__('24 Months', 'maneli-car-inquiry'),
                'term_36'                => esc_html__('36 Months', 'maneli-car-inquiry'),
                'server_error'           => esc_html__('Server error:', 'maneli-car-inquiry'),
                'unknown_error'          => esc_html__('Unknown error', 'maneli-car-inquiry'),
                'datepicker_placeholder' => esc_html__('e.g., 1365/04/15', 'maneli-car-inquiry'),
            ]
        ]);
        
        $template_args = [];
        if (current_user_can('manage_maneli_inquiries')) {
            $template_args['experts'] = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
        }
        
        return maneli_get_template_part('shortcodes/inquiry-form/expert-new-inquiry-form', $template_args, false);
    }
    
    /**
     * Displays a payment status message based on the URL query parameter.
     *
     * @param string $status The status from the URL ('success', 'failed', 'cancelled').
     */
    private function display_payment_message($status) {
        $reason = isset($_GET['reason']) ? sanitize_text_field(urldecode($_GET['reason'])) : '';
        $template_args = ['status' => $status, 'reason' => $reason];
        maneli_get_template_part('shortcodes/inquiry-form/payment-status-message', $template_args);
    }

    /**
     * Enqueues datepicker assets if they haven't been loaded yet for this page request.
     */
    private function load_datepicker_assets() {
        if ($this->datepicker_loaded) {
            return;
        }

        wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css');
        wp_enqueue_script(
            'maneli-jalali-datepicker',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/kamadatepicker.min.js',
            [], '2.1.0', true
        );
        
        // Instead of inline script, this logic is now in a dedicated, enqueued file.
        // The file will be enqueued in class-shortcode-handler.php
        wp_enqueue_script(
            'maneli-inquiry-form-js',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-form.js',
            ['maneli-jalali-datepicker'],
            '1.0.0',
            true
        );
        // Localize shared frontend data (AJAX and texts)
        wp_localize_script('maneli-inquiry-form-js', 'maneliInquiryForm', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonces' => [
                'confirm_catalog' => wp_create_nonce('maneli_confirm_car_catalog_nonce'),
            ],
            'text' => [
                'datepicker_placeholder' => esc_html__('YYYY/MM/DD', 'maneli-car-inquiry'),
            ],
        ]);
        
        // Localize meeting-related texts for inquiry-form.js
        wp_localize_script('maneli-inquiry-form-js', 'maneli_meetings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('maneli_meetings_nonce'),
            'text' => [
                'error_retrieving' => esc_html__('Error retrieving information', 'maneli-car-inquiry'),
                'server_error' => esc_html__('Error communicating with server', 'maneli-car-inquiry'),
                'booking' => esc_html__('Booking...', 'maneli-car-inquiry'),
                'success' => esc_html__('Meeting booked successfully', 'maneli-car-inquiry'),
                'error_booking' => esc_html__('Booking error', 'maneli-car-inquiry'),
                'select_time' => esc_html__('Please select a time', 'maneli-car-inquiry'),
            ],
        ]);
        
        $this->datepicker_loaded = true;
    }
}