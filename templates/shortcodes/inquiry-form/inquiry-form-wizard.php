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

// Enqueue SweetAlert2 for confirmation dialogs
if (file_exists(MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js')) {
    wp_enqueue_script('sweetalert2', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js', ['jquery'], '11.0.0', true);
} else {
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, true);
}

// Enqueue datepicker and inquiry frontend behaviors (for wizard steps)
// Use the same persianDatepicker library that's used in expert reports
if (!wp_script_is('maneli-persian-datepicker', 'enqueued')) {
    wp_enqueue_script('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/persianDatepicker.min.js', ['jquery'], '1.0.0', true);
}
if (!wp_style_is('maneli-persian-datepicker', 'enqueued')) {
    wp_enqueue_style('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/persianDatepicker-default.css', [], '1.0.0');
}
if (!wp_script_is('maneli-inquiry-form-js', 'enqueued')) {
    wp_enqueue_script('maneli-inquiry-form-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-form.js', ['jquery', 'maneli-persian-datepicker', 'sweetalert2'], filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/frontend/inquiry-form.js'), true);
}

// Localize AJAX and nonces for inquiry frontend (confirm car catalog, meetings, car selection)
// CRITICAL: Generate nonce fresh for each page load
$select_car_nonce = wp_create_nonce('maneli_ajax_nonce');
wp_localize_script('maneli-inquiry-form-js', 'maneliInquiryForm', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonces' => [
        'confirm_catalog' => wp_create_nonce('maneli_confirm_car_catalog_nonce'),
        'select_car' => $select_car_nonce, // For replacing car - must match action 'maneli_ajax_nonce'
    ],
    'texts' => [
        'replace_car_confirm' => esc_html__('Are you sure you want to replace the current car with this one?', 'maneli-car-inquiry'),
        'car_replaced_success' => esc_html__('Car replaced successfully. Page is being refreshed...', 'maneli-car-inquiry'),
        'error_replacing_car' => esc_html__('Error replacing car', 'maneli-car-inquiry'),
        'server_error' => esc_html__('Server connection error. Please try again.', 'maneli-car-inquiry'),
        'confirm' => esc_html__('Yes', 'maneli-car-inquiry'),
        'cancel' => esc_html__('Cancel', 'maneli-car-inquiry'),
        'invalid_request' => esc_html__('Invalid security token. Please refresh the page and try again.', 'maneli-car-inquiry'),
        'please_login' => esc_html__('Please log in to continue.', 'maneli-car-inquiry'),
        'product_id_required' => esc_html__('Product ID is required.', 'maneli-car-inquiry'),
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
                    <?php esc_html_e('Browse Other Cars', 'maneli-car-inquiry'); ?>
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <input type="text" id="confirm_car_search" class="form-control" placeholder="<?php esc_attr_e('Search...', 'maneli-car-inquiry'); ?>">
                    </div>
                    <div class="col-md-3">
                        <select id="confirm_car_brand" class="form-select">
                            <option value=""><?php esc_html_e('All Brands', 'maneli-car-inquiry'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="confirm_car_category" class="form-select">
                            <option value=""><?php esc_html_e('All Categories', 'maneli-car-inquiry'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button id="confirm_car_filter_btn" class="btn btn-primary w-100" type="button">
                            <i class="la la-filter me-1"></i>
                            <?php esc_html_e('Filter', 'maneli-car-inquiry'); ?>
                        </button>
                    </div>
                </div>

                <div id="confirm_car_catalog" class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
                    <!-- AJAX cards inserted here -->
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-3">
                    <a href="<?php echo esc_url(get_post_type_archive_link('product')); ?>" class="btn btn-light">
                        <?php esc_html_e('View Full Cars List', 'maneli-car-inquiry'); ?>
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

