<?php
/**
 * Main Wizard Form Template for Customer Inquiry Registration
 * This template creates a wizard-style multi-step form using vanilla-wizard
 * 
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryForm
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
    if (isset($_SESSION['autopuzzle']['user_id']) && !empty($_SESSION['autopuzzle']['user_id'])) {
        $is_logged_in = true;
    } elseif (isset($_SESSION['autopuzzle_dashboard_logged_in']) && $_SESSION['autopuzzle_dashboard_logged_in'] === true) {
        $is_logged_in = true;
    }
}

if (!$is_logged_in) {
    autopuzzle_get_template_part('shortcodes/inquiry-form/login-prompt');
    return;
}

// Get current step from URL or session
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$current_step = max(1, min(6, $current_step)); // Validate range 1-6

// Get options
$options = get_option('autopuzzle_inquiry_all_options', []);
$payment_enabled = !empty($options['payment_enabled']) && $options['payment_enabled'] == '1';

// Enqueue wizard scripts and styles
wp_enqueue_script('vanilla-wizard', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/vanilla-wizard/js/wizard.min.js', ['jquery'], '1.0.0', true);
if (file_exists(AUTOPUZZLE_PLUGIN_PATH . 'assets/js/form-wizard.js')) {
    wp_enqueue_script('form-wizard', AUTOPUZZLE_PLUGIN_URL . 'assets/js/form-wizard.js', ['jquery', 'vanilla-wizard'], filemtime(AUTOPUZZLE_PLUGIN_PATH . 'assets/js/form-wizard.js'), true);
}

// Enqueue CAPTCHA scripts if enabled
if (class_exists('Autopuzzle_Captcha_Helper') && Autopuzzle_Captcha_Helper::is_enabled()) {
    $captcha_type = Autopuzzle_Captcha_Helper::get_captcha_type();
    $site_key = Autopuzzle_Captcha_Helper::get_site_key($captcha_type);
    
    if (!empty($captcha_type) && !empty($site_key)) {
        Autopuzzle_Captcha_Helper::enqueue_script($captcha_type, $site_key);
        
        // Enqueue our CAPTCHA handler script
        wp_enqueue_script(
            'autopuzzle-captcha',
            AUTOPUZZLE_PLUGIN_URL . 'assets/js/captcha.js',
            ['jquery'],
            file_exists(AUTOPUZZLE_PLUGIN_PATH . 'assets/js/captcha.js') ? filemtime(AUTOPUZZLE_PLUGIN_PATH . 'assets/js/captcha.js') : '1.0.0',
            true
        );
        
        // Localize script with CAPTCHA config and error messages
        wp_localize_script('autopuzzle-captcha', 'autopuzzleCaptchaConfig', [
            'enabled' => true,
            'type' => $captcha_type,
            'siteKey' => $site_key,
            'strings' => [
                'verification_failed' => esc_html__('CAPTCHA verification failed. Please complete the CAPTCHA challenge and try again.', 'autopuzzle'),
                'error_title' => esc_html__('Verification Failed', 'autopuzzle'),
                'try_again' => esc_html__('Try Again', 'autopuzzle'),
                'loading' => esc_html__('Verifying...', 'autopuzzle'),
                'network_error' => esc_html__('Network error occurred. Please check your internet connection and try again.', 'autopuzzle'),
                'script_not_loaded' => esc_html__('CAPTCHA script could not be loaded. Please refresh the page and try again.', 'autopuzzle'),
                'token_expired' => esc_html__('CAPTCHA token has expired. Please complete the challenge again.', 'autopuzzle')
            ]
        ]);
    }
}

// Enqueue SweetAlert2 for confirmation dialogs
if (file_exists(AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js')) {
    wp_enqueue_script('sweetalert2', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js', ['jquery'], '11.0.0', true);
} else {
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, true);
}

// Enqueue datepicker and inquiry frontend behaviors (for wizard steps)
// Use the same persianDatepicker library that's used in expert reports when locale is Persian
$datepicker_dependency_loaded = false;
if (function_exists('autopuzzle_enqueue_persian_datepicker')) {
    $datepicker_dependency_loaded = autopuzzle_enqueue_persian_datepicker();
}

if (!wp_script_is('autopuzzle-inquiry-form-js', 'enqueued')) {
    $inquiry_form_deps = ['jquery', 'sweetalert2'];
    if ($datepicker_dependency_loaded) {
        $inquiry_form_deps[] = 'autopuzzle-persian-datepicker';
    }
    wp_enqueue_script(
        'autopuzzle-inquiry-form-js',
        AUTOPUZZLE_PLUGIN_URL . 'assets/js/frontend/inquiry-form.js',
        $inquiry_form_deps,
        filemtime(AUTOPUZZLE_PLUGIN_PATH . 'assets/js/frontend/inquiry-form.js'),
        true
    );
}

// Localize AJAX and nonces for inquiry frontend (confirm car catalog, meetings, car selection)
// CRITICAL: Generate nonce fresh for each page load
$select_car_nonce = wp_create_nonce('autopuzzle_ajax_nonce');
wp_localize_script('autopuzzle-inquiry-form-js', 'autopuzzleInquiryForm', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonces' => [
        'confirm_catalog' => wp_create_nonce('autopuzzle_confirm_car_catalog_nonce'),
        'select_car' => $select_car_nonce, // For replacing car - must match action 'autopuzzle_ajax_nonce'
    ],
    'texts' => [
        'replace_car_confirm' => esc_html__('Are you sure you want to replace the current car with this one?', 'autopuzzle'),
        'car_replaced_success' => esc_html__('Car replaced successfully. Page is being refreshed...', 'autopuzzle'),
        'error_replacing_car' => esc_html__('Error replacing car', 'autopuzzle'),
        'server_error' => esc_html__('Server connection error. Please try again.', 'autopuzzle'),
        'confirm' => esc_html__('Yes', 'autopuzzle'),
        'cancel' => esc_html__('Cancel', 'autopuzzle'),
        'invalid_request' => esc_html__('Invalid security token. Please refresh the page and try again.', 'autopuzzle'),
        'please_login' => esc_html__('Please log in to continue.', 'autopuzzle'),
        'product_id_required' => esc_html__('Product ID is required.', 'autopuzzle'),
    ],
]);

// Get car selection data from user meta (not session)
$selected_car_data = null;
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $car_id = get_user_meta($user_id, 'autopuzzle_selected_car_id', true);
    
    if ($car_id && $car_id > 0) {
        $product = wc_get_product($car_id);
        if ($product) {
            // Get values and ensure they are clean integers/strings (not concatenated)
            $down_payment_raw = get_user_meta($user_id, 'autopuzzle_inquiry_down_payment', true);
            $term_months_raw = get_user_meta($user_id, 'autopuzzle_inquiry_term_months', true);
            $total_price_raw = get_user_meta($user_id, 'autopuzzle_inquiry_total_price', true);
            $installment_amount_raw = get_user_meta($user_id, 'autopuzzle_inquiry_installment', true);
            
            // Clean and convert to proper types
            // For down_payment and installment_amount: remove any non-numeric chars except digits
            $down_payment = is_numeric($down_payment_raw) ? (int)$down_payment_raw : (int)preg_replace('/[^\d]/', '', (string)$down_payment_raw);
            $term_months = is_numeric($term_months_raw) ? (int)$term_months_raw : (int)preg_replace('/[^\d]/', '', (string)$term_months_raw);
            $total_price = is_numeric($total_price_raw) ? (int)$total_price_raw : (int)preg_replace('/[^\d]/', '', (string)$total_price_raw);
            $installment_amount = is_numeric($installment_amount_raw) ? (int)$installment_amount_raw : (int)preg_replace('/[^\d]/', '', (string)$installment_amount_raw);
            
            // Debug: Log values read from user meta
            error_log('AutoPuzzle Debug: Reading user meta for step 3:');
            error_log('  User ID: ' . $user_id);
            error_log('  Car ID: ' . $car_id);
            error_log('  Down Payment Raw: ' . $down_payment_raw . ' -> Cleaned: ' . $down_payment);
            error_log('  Term Months Raw: ' . $term_months_raw . ' -> Cleaned: ' . $term_months);
            error_log('  Total Price Raw: ' . $total_price_raw . ' -> Cleaned: ' . $total_price);
            error_log('  Installment Raw: ' . $installment_amount_raw . ' -> Cleaned: ' . $installment_amount);
            
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
                    <?php esc_html_e('Submit Installment Inquiry', 'autopuzzle'); ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="wizard-container" id="inquiry-wizard">
                    <div class="wizard-content container">
                        <?php
                        // Step 1: Car Selection
                        $active_class = ($current_step === 1) ? 'active' : '';
                        ?>
                        <div class="wizard-step <?php echo esc_attr($active_class); ?>" data-title="<?php esc_attr_e('Car Selection', 'autopuzzle'); ?>" data-id="step1" data-step="0">
                            <div class="row justify-content-center">
                                <div class="col-xl-12">
                                    <?php
                                    if ($selected_car_data) {
                                        // Car is selected, show summary
                                        autopuzzle_get_template_part('shortcodes/inquiry-form/wizard/step-1-car-selected', ['car_data' => $selected_car_data]);
                                    } else {
                                        // No car selected yet
                                        autopuzzle_get_template_part('shortcodes/inquiry-form/wizard/step-1-car-selection');
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Step 2: Identity Information
                        $active_class = ($current_step === 2) ? 'active' : '';
                        ?>
                        <div class="wizard-step <?php echo esc_attr($active_class); ?>" data-title="<?php esc_attr_e('Identity Information', 'autopuzzle'); ?>" data-id="step2" data-step="1">
                            <div class="row justify-content-center">
                                <div class="col-xl-12">
                                    <?php
                                    if ($selected_car_data) {
                                        autopuzzle_get_template_part('shortcodes/inquiry-form/wizard/step-2-identity-form', [
                                            'car_name' => $selected_car_data['car_name'] ?? '',
                                            'car_model' => $selected_car_data['car_model'] ?? '',
                                            'car_image_html' => $selected_car_data['car_image_html'] ?? '',
                                            'down_payment' => $selected_car_data['down_payment'] ?? '',
                                            'term_months' => $selected_car_data['term_months'] ?? '',
                                            'total_price' => $selected_car_data['total_price'] ?? '',
                                            'installment_amount' => $selected_car_data['installment_amount'] ?? '',
                                        ]);
                                    } else {
                                        echo '<div class="alert alert-warning"><i class="la la-exclamation-triangle me-2"></i>' . esc_html__('Please select a car first.', 'autopuzzle') . '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Step 3: Confirm Car
                        $active_class = ($current_step === 3) ? 'active' : '';
                        ?>
                        <div class="wizard-step <?php echo esc_attr($active_class); ?>" data-title="<?php esc_attr_e('Confirm Car', 'autopuzzle'); ?>" data-id="step3" data-step="2">
                            <div class="row justify-content-center">
                                <div class="col-xl-12">
                                    <?php
                                    if ($selected_car_data) {
                                        autopuzzle_get_template_part('shortcodes/inquiry-form/wizard/step-3-confirm-car', [
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
                            <div class="wizard-step <?php echo esc_attr($active_class); ?>" data-title="<?php esc_attr_e('Payment', 'autopuzzle'); ?>" data-id="step4" data-step="<?php echo esc_attr($step_index); ?>">
                                <div class="row justify-content-center">
                                    <div class="col-xl-12">
                                        <?php autopuzzle_get_template_part('shortcodes/inquiry-form/wizard/step-4-payment'); ?>
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
                        <div class="wizard-step <?php echo esc_attr($active_class); ?>" data-title="<?php esc_attr_e('Final Result', 'autopuzzle'); ?>" data-id="step5" data-step="<?php echo esc_attr($step_index); ?>">
                            <div class="row justify-content-center">
                                <div class="col-xl-12">
                                    <?php
                                    $inquiry_id = isset($_GET['inquiry_id']) ? (int)$_GET['inquiry_id'] : 0;
                                    if ($inquiry_id > 0) {
                                        autopuzzle_get_template_part('shortcodes/inquiry-form/step-5-final-report', ['inquiry_id' => $inquiry_id]);
                                    } else {
                                        autopuzzle_get_template_part('shortcodes/inquiry-form/wizard/step-4-wait-message');
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CAPTCHA Widget (for v2 and hCaptcha, before finish button) -->
                    <?php if (class_exists('Autopuzzle_Captcha_Helper') && Autopuzzle_Captcha_Helper::is_enabled()): 
                        $captcha_type = Autopuzzle_Captcha_Helper::get_captcha_type();
                        $site_key = Autopuzzle_Captcha_Helper::get_site_key($captcha_type);
                        if (!empty($captcha_type) && !empty($site_key)):
                            if ($captcha_type === 'recaptcha_v2' || $captcha_type === 'hcaptcha'): ?>
                                <div class="wizard-captcha-container d-flex justify-content-center p-3" style="display: none !important;" data-show-on-finish="true">
                                    <?php echo Autopuzzle_Captcha_Helper::render_widget($captcha_type, $site_key, 'autopuzzle-captcha-widget-inquiry'); ?>
                                </div>
                            <?php elseif ($captcha_type === 'recaptcha_v3'): ?>
                                <!-- reCAPTCHA v3 badge will be automatically displayed by Google -->
                                <div class="autopuzzle-recaptcha-v3-badge" style="display:none;"></div>
                            <?php endif;
                        endif;
                    endif; ?>
                    
                    <!-- Wizard Navigation Buttons -->
                    <div class="wizard-buttons d-flex justify-content-end gap-2 p-3">
                        <button type="button" class="btn btn-primary wizard-btn prev">
                            <i class="bx bx-right-arrow-alt me-2"></i>
                            <?php esc_html_e('Previous', 'autopuzzle'); ?>
                        </button>
                        <button type="button" class="btn btn-secondary wizard-btn next">
                            <?php esc_html_e('Next', 'autopuzzle'); ?>
                            <i class="bx bx-left-arrow-alt ms-2"></i>
                        </button>
                        <button type="button" class="btn btn-success wizard-btn finish" style="display: none;">
                            <?php esc_html_e('Finish', 'autopuzzle'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Show "Browse Other Cars" section only on step 3, below everything else
if ($current_step === 3):
    // Get selected car data for step 3
    $step3_car_data = null;
    if (is_user_logged_in() && isset($selected_car_data)) {
        $step3_car_data = $selected_car_data;
    }
    if ($step3_car_data):
?>
<!-- Browse Other Cars Section - Moved to bottom of page -->
<style>
/* Pagination Styles for confirm_car_pagination */
#confirm_car_pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.25rem;
}

/* For plain pagination (default WordPress output) */
#confirm_car_pagination > a,
#confirm_car_pagination > span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.5rem;
    height: 2.5rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--default-border, #e0e7ed);
    border-radius: 0.3rem;
    color: var(--default-text-color, #495057);
    background-color: var(--custom-white, #fff);
    text-decoration: none;
    font-size: 0.8125rem;
    transition: all 0.3s ease;
    margin: 0 0.125rem;
}

#confirm_car_pagination > a:hover {
    color: var(--primary-color, #5e72e4);
    background-color: rgba(var(--primary-rgb, 94, 114, 228), 0.1);
    border-color: var(--primary-color, #5e72e4);
}

#confirm_car_pagination > span.current,
#confirm_car_pagination > a.current {
    color: #fff;
    background-color: var(--primary-color, #5e72e4);
    border-color: var(--primary-color, #5e72e4);
    font-weight: 500;
}

#confirm_car_pagination > span.dots {
    border: none;
    background: transparent;
    cursor: default;
    min-width: auto;
}

/* For list-based pagination (if type is 'list') */
#confirm_car_pagination .page-numbers {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 0.25rem;
    flex-wrap: wrap;
}

#confirm_car_pagination .page-numbers li {
    margin: 0;
}

#confirm_car_pagination .page-numbers a,
#confirm_car_pagination .page-numbers span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.5rem;
    height: 2.5rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--default-border, #e0e7ed);
    border-radius: 0.3rem;
    color: var(--default-text-color, #495057);
    background-color: var(--custom-white, #fff);
    text-decoration: none;
    font-size: 0.8125rem;
    transition: all 0.3s ease;
}

#confirm_car_pagination .page-numbers a:hover {
    color: var(--primary-color, #5e72e4);
    background-color: rgba(var(--primary-rgb, 94, 114, 228), 0.1);
    border-color: var(--primary-color, #5e72e4);
}

#confirm_car_pagination .page-numbers .current,
#confirm_car_pagination .page-numbers .page-numbers.current {
    color: #fff;
    background-color: var(--primary-color, #5e72e4);
    border-color: var(--primary-color, #5e72e4);
    font-weight: 500;
}

#confirm_car_pagination .page-numbers .dots,
#confirm_car_pagination .page-numbers .page-numbers.dots {
    border: none;
    background: transparent;
    cursor: default;
}

@media (max-width: 575.98px) {
    #confirm_car_pagination {
        width: 100%;
        justify-content: center;
    }
    #confirm_car_pagination > a,
    #confirm_car_pagination > span {
        min-width: 2rem;
        height: 2rem;
        padding: 0.375rem 0.5rem;
        font-size: 0.75rem;
    }
}
</style>
<div class="row mt-4">
    <div class="col-xl-12">
        <div class="card border mb-4">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">
                    <i class="la la-car me-2"></i>
                    <?php esc_html_e('Browse Other Cars', 'autopuzzle'); ?>
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <input type="text" id="confirm_car_search" class="form-control" placeholder="<?php esc_attr_e('Search...', 'autopuzzle'); ?>">
                    </div>
                    <div class="col-md-3">
                        <select id="confirm_car_brand" class="form-select">
                            <option value=""><?php esc_html_e('All Brands', 'autopuzzle'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="confirm_car_category" class="form-select">
                            <option value=""><?php esc_html_e('All Categories', 'autopuzzle'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button id="confirm_car_filter_btn" class="btn btn-primary w-100" type="button">
                            <i class="la la-filter me-1"></i>
                            <?php esc_html_e('Filter', 'autopuzzle'); ?>
                        </button>
                    </div>
                </div>

                <div id="confirm_car_catalog" class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
                    <!-- AJAX cards inserted here -->
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-3">
                    <a href="<?php echo esc_url(get_post_type_archive_link('product')); ?>" class="btn btn-light">
                        <?php esc_html_e('View Full Cars List', 'autopuzzle'); ?>
                    </a>
                    <div id="confirm_car_pagination" class="pagination-style-1"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
    endif;
endif;
?>

