<?php
/**
 * Main Wizard Form Template for Customer Inquiry Registration
 * This template creates a wizard-style multi-step form using vanilla-wizard
 * 
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @version 2.0.0 (Wizard Format)
 */

if (!defined('ABSPATH')) {
    exit;
}

// CRITICAL: Check if user is logged in - check both WordPress login and session
// First check WordPress user login
$is_logged_in = is_user_logged_in();

// If not WordPress login, check session (for custom dashboard login)
if (!$is_logged_in) {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    
    // Check session-based login
    if (isset($_SESSION['maneli']['user_id']) && !empty($_SESSION['maneli']['user_id'])) {
        $is_logged_in = true;
    } elseif (isset($_SESSION['maneli_dashboard_logged_in']) && $_SESSION['maneli_dashboard_logged_in'] === true) {
        $is_logged_in = true;
    }
}

if (!$is_logged_in) {
    maneli_get_template_part('shortcodes/inquiry-form/login-prompt');
    return;
}

// Get current step from URL or session
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$current_step = max(1, min(6, $current_step)); // Validate range 1-6

// Get options
$options = get_option('maneli_inquiry_all_options', []);
$payment_enabled = !empty($options['payment_enabled']) && $options['payment_enabled'] == '1';

// Enqueue wizard scripts and styles
wp_enqueue_script('vanilla-wizard', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/vanilla-wizard/js/wizard.min.js', ['jquery'], '1.0.0', true);
if (file_exists(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/form-wizard.js')) {
    wp_enqueue_script('form-wizard', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/form-wizard.js', ['jquery', 'vanilla-wizard'], filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/form-wizard.js'), true);
}

// Enqueue datepicker and inquiry frontend behaviors (for wizard steps)
if (!wp_script_is('maneli-jalali-datepicker', 'enqueued')) {
    wp_enqueue_script('maneli-jalali-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/kamadatepicker.min.js', [], '2.1.0', true);
}
if (!wp_style_is('maneli-datepicker-theme', 'enqueued')) {
    wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css', [], '1.0.0');
}
if (!wp_script_is('maneli-inquiry-form-js', 'enqueued')) {
    wp_enqueue_script('maneli-inquiry-form-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-form.js', ['jquery', 'maneli-jalali-datepicker'], filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/frontend/inquiry-form.js'), true);
}

// Localize AJAX and nonces for inquiry frontend (confirm car catalog, meetings)
wp_localize_script('maneli-inquiry-form-js', 'maneliInquiryForm', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonces' => [
        'confirm_catalog' => wp_create_nonce('maneli_confirm_car_catalog_nonce'),
    ],
]);

// Get car selection data from user meta (not session)
$selected_car_data = null;
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $car_id = get_user_meta($user_id, 'maneli_selected_car_id', true);
    
    if ($car_id && $car_id > 0) {
        $product = wc_get_product($car_id);
        if ($product) {
            $down_payment = get_user_meta($user_id, 'maneli_inquiry_down_payment', true);
            $term_months = get_user_meta($user_id, 'maneli_inquiry_term_months', true);
            $total_price = get_user_meta($user_id, 'maneli_inquiry_total_price', true);
            $installment_amount = get_user_meta($user_id, 'maneli_inquiry_installment', true);
            
            $selected_car_data = [
                'car_id' => $car_id,
                'car_name' => $product->get_name(),
                'car_model' => $product->get_short_description(),
                'car_image_html' => $product->get_image('medium'),
                'down_payment' => $down_payment,
                'term_months' => $term_months,
                'total_price' => $total_price,
                'installment_amount' => $installment_amount,
            ];
        }
    }
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-file-invoice me-2"></i>
                    <?php esc_html_e('Submit Installment Inquiry', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="wizard-container" id="inquiry-wizard">
                    <div class="wizard-content container">
                        <?php
                        // Step 1: Car Selection
                        $active_class = ($current_step === 1) ? 'active' : '';
                        ?>
                        <div class="wizard-step <?php echo esc_attr($active_class); ?>" data-title="<?php esc_attr_e('Car Selection', 'maneli-car-inquiry'); ?>" data-id="step1" data-step="0">
                            <div class="row justify-content-center">
                                <div class="col-xl-12">
                                    <?php
                                    if ($selected_car_data) {
                                        // Car is selected, show summary
                                        maneli_get_template_part('shortcodes/inquiry-form/wizard/step-1-car-selected', ['car_data' => $selected_car_data]);
                                    } else {
                                        // No car selected yet
                                        maneli_get_template_part('shortcodes/inquiry-form/wizard/step-1-car-selection');
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Step 2: Identity Information
                        $active_class = ($current_step === 2) ? 'active' : '';
                        ?>
                        <div class="wizard-step <?php echo esc_attr($active_class); ?>" data-title="<?php esc_attr_e('Identity Information', 'maneli-car-inquiry'); ?>" data-id="step2" data-step="1">
                            <div class="row justify-content-center">
                                <div class="col-xl-12">
                                    <?php
                                    if ($selected_car_data) {
                                        maneli_get_template_part('shortcodes/inquiry-form/wizard/step-2-identity-form', [
                                            'car_name' => $selected_car_data['car_name'] ?? '',
                                            'car_model' => $selected_car_data['car_model'] ?? '',
                                            'car_image_html' => $selected_car_data['car_image_html'] ?? '',
                                            'down_payment' => $selected_car_data['down_payment'] ?? '',
                                            'term_months' => $selected_car_data['term_months'] ?? '',
                                            'total_price' => $selected_car_data['total_price'] ?? '',
                                            'installment_amount' => $selected_car_data['installment_amount'] ?? '',
                                        ]);
                                    } else {
                                        echo '<div class="alert alert-warning"><i class="la la-exclamation-triangle me-2"></i>' . esc_html__('Please select a car first.', 'maneli-car-inquiry') . '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Step 3: Confirm Car
                        $active_class = ($current_step === 3) ? 'active' : '';
                        ?>
                        <div class="wizard-step <?php echo esc_attr($active_class); ?>" data-title="<?php esc_attr_e('Confirm Car', 'maneli-car-inquiry'); ?>" data-id="step3" data-step="2">
                            <div class="row justify-content-center">
                                <div class="col-xl-12">
                                    <?php
                                    if ($selected_car_data) {
                                        maneli_get_template_part('shortcodes/inquiry-form/wizard/step-3-confirm-car', [
                                            'car_id' => $selected_car_data['car_id'] ?? 0,
                                            'car_name' => $selected_car_data['car_name'] ?? '',
                                            'car_model' => $selected_car_data['car_model'] ?? '',
                                            'car_image_html' => $selected_car_data['car_image_html'] ?? '',
                                            'down_payment' => $selected_car_data['down_payment'] ?? '',
                                            'term_months' => $selected_car_data['term_months'] ?? '',
                                            'total_price' => $selected_car_data['total_price'] ?? '',
                                            'installment_amount' => $selected_car_data['installment_amount'] ?? '',
                                        ]);
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <?php 
                        // Calculate step index for conditional steps
                        $step_index = 3;
                        if ($payment_enabled): 
                            $active_class = ($current_step === 4) ? 'active' : '';
                        ?>
                            <div class="wizard-step <?php echo esc_attr($active_class); ?>" data-title="<?php esc_attr_e('Payment', 'maneli-car-inquiry'); ?>" data-id="step4" data-step="<?php echo esc_attr($step_index); ?>">
                                <div class="row justify-content-center">
                                    <div class="col-xl-12">
                                        <?php maneli_get_template_part('shortcodes/inquiry-form/wizard/step-4-payment'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            $step_index++;
                        endif; 
                        ?>

                        <?php
                        // Step 5: Final Report / Pending Review
                        $active_class = ($current_step >= 5) ? 'active' : '';
                        ?>
                        <div class="wizard-step <?php echo esc_attr($active_class); ?>" data-title="<?php esc_attr_e('Final Result', 'maneli-car-inquiry'); ?>" data-id="step5" data-step="<?php echo esc_attr($step_index); ?>">
                            <div class="row justify-content-center">
                                <div class="col-xl-12">
                                    <?php
                                    $inquiry_id = isset($_GET['inquiry_id']) ? (int)$_GET['inquiry_id'] : 0;
                                    if ($inquiry_id > 0) {
                                        maneli_get_template_part('shortcodes/inquiry-form/step-5-final-report', ['inquiry_id' => $inquiry_id]);
                                    } else {
                                        maneli_get_template_part('shortcodes/inquiry-form/wizard/step-4-wait-message');
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Wizard Navigation Buttons -->
                    <div class="wizard-buttons d-flex justify-content-end gap-2 p-3">
                        <button type="button" class="btn btn-primary wizard-btn prev">
                            <i class="bx bx-right-arrow-alt me-2"></i>
                            <?php esc_html_e('Previous', 'maneli-car-inquiry'); ?>
                        </button>
                        <button type="button" class="btn btn-secondary wizard-btn next">
                            <?php esc_html_e('Next', 'maneli-car-inquiry'); ?>
                            <i class="bx bx-left-arrow-alt ms-2"></i>
                        </button>
                        <button type="button" class="btn btn-success wizard-btn finish" style="display: none;">
                            <?php esc_html_e('Finish', 'maneli-car-inquiry'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Wizard initialization is handled in dashboard.php for dashboard context -->

