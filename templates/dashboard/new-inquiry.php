<?php
/**
 * New Installment Inquiry Page (Customer)
 * Customer multi-step installment inquiry form
 * Permission: Only customers
 * Uses the existing 5-step inquiry form from shortcode
 */

// CRITICAL: Check if user is logged in first - check both WordPress and session
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
    // Redirect to login page
    wp_redirect(home_url('/dashboard/login'));
    exit;
}

// Permission check - Only customers can create new inquiries
// Admins and experts should create inquiries directly from inquiry list
$is_admin = current_user_can('manage_maneli_inquiries');
$is_expert = in_array('maneli_expert', wp_get_current_user()->roles, true);

if ($is_admin || $is_expert) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-info alert-dismissible fade show">
                <i class="la la-info-circle me-2"></i>
                <?php esc_html_e('To submit an installment inquiry, please use the', 'maneli-car-inquiry'); ?>
                <a href="<?php echo home_url('/dashboard/new-installment-inquiry'); ?>" class="alert-link">
                    <?php esc_html_e('New Installment Inquiry', 'maneli-car-inquiry'); ?>
                </a>
                <?php esc_html_e('page.', 'maneli-car-inquiry'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Include the wizard-based inquiry form
// This uses the new wizard format with vanilla-wizard library
// Steps:
// 1. Car Selection (Calculator)
// 2. Identity Form
// 3. Confirm Car
// 4. Payment (if enabled)
// 5. Final Report

// Enqueue wizard scripts
wp_enqueue_script('vanilla-wizard', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/vanilla-wizard/js/wizard.min.js', ['jquery'], '1.0.0', true);
if (file_exists(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/form-wizard.js')) {
    wp_enqueue_script('form-wizard', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/form-wizard.js', ['jquery', 'vanilla-wizard'], filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/form-wizard.js'), true);
}

// Check for pending calculator data in localStorage (for users who logged in after selecting a car)
?>
<script>
(function() {
    'use strict';
    
    // Check for pending calculator data in localStorage
    const pendingData = localStorage.getItem('maneli_pending_calculator_data');
    if (pendingData) {
        try {
            const data = JSON.parse(pendingData);
            // Check if data is recent (within 1 hour)
            const oneHour = 60 * 60 * 1000;
            if (data.timestamp && (Date.now() - data.timestamp < oneHour)) {
                // Send AJAX request to save calculator data to user meta
                if (typeof maneli_ajax_object !== 'undefined' && maneli_ajax_object.ajax_url && maneli_ajax_object.nonce) {
                    const formData = new FormData();
                    formData.append('action', 'maneli_select_car_ajax');
                    formData.append('product_id', data.product_id);
                    formData.append('nonce', maneli_ajax_object.nonce);
                    formData.append('down_payment', data.down_payment);
                    formData.append('term_months', data.term_months);
                    formData.append('installment_amount', data.installment_amount);
                    formData.append('total_price', data.total_price);
                    
                    fetch(maneli_ajax_object.ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            // Remove from localStorage after successful save
                            localStorage.removeItem('maneli_pending_calculator_data');
                            console.log('Maneli Calculator: Restored calculator data from localStorage');
                            // Reload page to step 2 if we're on step 1
                            const currentStep = <?php echo isset($_GET['step']) ? (int)$_GET['step'] : 1; ?>;
                            if (currentStep === 1) {
                                window.location.href = '/dashboard/new-inquiry?step=2';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Maneli Calculator: Error restoring data:', error);
                    });
                }
            } else {
                // Data is too old, remove it
                localStorage.removeItem('maneli_pending_calculator_data');
            }
        } catch (e) {
            console.error('Maneli Calculator: Error parsing localStorage data:', e);
            localStorage.removeItem('maneli_pending_calculator_data');
        }
    }
})();
</script>

<div class="main-content app-content">
    <div class="container-fluid">
        <?php
        // Include wizard form template
        maneli_get_template_part('shortcodes/inquiry-form/inquiry-form-wizard');
        
        // Include installment calculator modal (for step 3 car replacement)
        $current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
        if ($current_step === 3) {
            maneli_get_template_part('dashboard/installment-calculator-modal');
        }
        ?>
    </div>
</div>
<!-- End::main-content -->

