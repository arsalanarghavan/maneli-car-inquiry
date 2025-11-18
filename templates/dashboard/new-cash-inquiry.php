<?php
/**
 * Template for New Cash Inquiry Page (Customer Dashboard)
 * 
 * Customer can create a cash inquiry by selecting a car
 * Shows car image, price, and collects customer information
 * 
 * @package Maneli_Car_Inquiry/Templates/Dashboard
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Permission check - Only customers can create cash inquiries from this page
$is_admin = current_user_can('manage_maneli_inquiries');
$is_expert = in_array('maneli_expert', wp_get_current_user()->roles, true);
$current_user = wp_get_current_user();

if ($is_admin || $is_expert) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-info alert-dismissible fade show">
                <i class="la la-info-circle me-2"></i>
                <?php echo esc_html__('To submit a cash inquiry, please use the', 'maneli-car-inquiry'); ?> <a href="<?php echo esc_url(home_url('/dashboard/inquiries/cash')); ?>" class="alert-link"><?php esc_html_e('Cash Inquiry List', 'maneli-car-inquiry'); ?></a> <?php esc_html_e('page.', 'maneli-car-inquiry'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Enqueue Select2 for car search
if (!wp_style_is('select2', 'enqueued')) {
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
}
if (!wp_script_is('select2', 'enqueued')) {
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
}

// Enqueue SweetAlert2 - Use local version if available
if (!wp_script_is('sweetalert2', 'enqueued')) {
    $sweetalert2_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
    if (file_exists($sweetalert2_path)) {
        wp_enqueue_style('sweetalert2', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css', [], '11.0.0');
        wp_enqueue_script('sweetalert2', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js', ['jquery'], '11.0.0', true);
    } else {
        // Fallback to CDN
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, true);
    }
}

// Enqueue CAPTCHA scripts if enabled
if (class_exists('Maneli_Captcha_Helper') && Maneli_Captcha_Helper::is_enabled()) {
    $captcha_type = Maneli_Captcha_Helper::get_captcha_type();
    $site_key = Maneli_Captcha_Helper::get_site_key($captcha_type);
    
    if (!empty($captcha_type) && !empty($site_key)) {
        Maneli_Captcha_Helper::enqueue_script($captcha_type, $site_key);
        
        // Enqueue our CAPTCHA handler script
        wp_enqueue_script(
            'maneli-captcha',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/captcha.js',
            ['jquery'],
            file_exists(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/captcha.js') ? filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/captcha.js') : '1.0.0',
            true
        );
        
        // Localize script with CAPTCHA config and error messages
        wp_localize_script('maneli-captcha', 'maneliCaptchaConfig', [
            'enabled' => true,
            'type' => $captcha_type,
            'siteKey' => $site_key,
            'strings' => [
                'verification_failed' => esc_html__('CAPTCHA verification failed. Please complete the CAPTCHA challenge and try again.', 'maneli-car-inquiry'),
                'error_title' => esc_html__('Verification Failed', 'maneli-car-inquiry'),
                'try_again' => esc_html__('Try Again', 'maneli-car-inquiry'),
                'loading' => esc_html__('Verifying...', 'maneli-car-inquiry'),
                'network_error' => esc_html__('Network error occurred. Please check your internet connection and try again.', 'maneli-car-inquiry'),
                'script_not_loaded' => esc_html__('CAPTCHA script could not be loaded. Please refresh the page and try again.', 'maneli-car-inquiry'),
                'token_expired' => esc_html__('CAPTCHA token has expired. Please complete the challenge again.', 'maneli-car-inquiry')
            ]
        ]);
    }
}

// Pre-fill customer data
$customer_first_name = get_user_meta($current_user->ID, 'first_name', true) ?: $current_user->first_name;
$customer_last_name = get_user_meta($current_user->ID, 'last_name', true) ?: $current_user->last_name;
$customer_mobile = get_user_meta($current_user->ID, 'billing_phone', true) ?: $current_user->user_login;
?>

<div class="main-content app-content">
    <div class="container-fluid">

<div class="row">
    <div class="col-xl-12">
        <?php if (isset($_GET['inquiry_created']) && $_GET['inquiry_created'] == '1') : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="la la-check-circle me-2"></i>
                <strong>موفق!</strong> درخواست نقدی شما با موفقیت ثبت شد و در حال بررسی است.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="la la-hand-holding-usd me-2"></i>
                    ثبت درخواست خرید نقدی
                </div>
                <div class="btn-list">
                    <a href="<?php echo esc_url(home_url('/dashboard/inquiries/cash')); ?>" class="btn btn-light btn-wave">
                        <i class="la la-arrow-right me-1"></i>
                        <?php esc_html_e('Back to List', 'maneli-car-inquiry'); ?>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form id="customer-cash-inquiry-form" method="post"<?php echo (class_exists('Maneli_Captcha_Helper') && Maneli_Captcha_Helper::is_enabled()) ? ' data-captcha-required="true"' : ''; ?>>
                    
                    <!-- Step 1: Car Selection -->
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-3">
                            <span class="avatar avatar-sm avatar-rounded bg-primary-transparent me-2">
                                <i class="la la-car fs-18"></i>
                            </span>
                            <span class="align-middle">انتخاب خودرو</span>
                        </h5>
                        
                        <div class="mb-3">
                            <label for="product_id_customer" class="form-label">
                                <i class="la la-search me-1 text-primary"></i>
                                جستجوی خودرو
                                <span class="text-danger">*</span>
                            </label>
                            <select id="product_id_customer" name="product_id" class="form-select form-select-lg customer-car-search-select" required>
                                <option value=""></option>
                            </select>
                            <div class="form-text text-muted">
                                <i class="la la-info-circle me-1"></i>
                                حداقل 2 حرف از نام خودرو را تایپ کنید تا نتایج نمایش داده شود
                            </div>
                        </div>
                    </div>

                    <!-- Car Details Display (Hidden by default) -->
                    <div id="car-details-section" class="maneli-initially-hidden">
                        <div class="mb-4 pb-3 border-bottom">
                            <h5 class="mb-3">
                                <span class="avatar avatar-sm avatar-rounded bg-success-transparent me-2">
                                    <i class="la la-info-circle fs-18"></i>
                                </span>
                                <span class="align-middle">مشخصات خودرو انتخابی</span>
                            </h5>
                            
                            <div class="card custom-card shadow-none border border-success">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-4 text-center">
                                            <div id="car-image-container" class="mb-3 mb-md-0">
                                                <img src="" alt="Car Image" id="car-image" class="img-fluid rounded maneli-img-max-height-200">
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <h4 class="mb-3" id="car-name">-</h4>
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <div class="alert alert-primary mb-0">
                                                        <label class="fw-semibold mb-1 d-block">قیمت تقریبی</label>
                                                        <div class="fs-5" id="car-price">-</div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="alert alert-warning mb-0">
                                                        <label class="fw-semibold mb-1 d-block">حداقل پیش‌پرداخت</label>
                                                        <div class="fs-5" id="car-min-downpayment">-</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="alert alert-light border mt-3 mb-0">
                                                <i class="la la-info-circle text-info me-1"></i>
                                                <small class="text-muted">قیمت‌ها تقریبی بوده و ممکن است تا زمان تایید نهایی تغییر کند.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Customer Information -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <span class="avatar avatar-sm avatar-rounded bg-info-transparent me-2">
                                    <i class="la la-user fs-18"></i>
                                </span>
                                <span class="align-middle">اطلاعات شما</span>
                            </h5>
                            
                            <div class="card custom-card shadow-none border">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="la la-user me-1 text-muted"></i>
                                                نام <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="first_name" class="form-control" value="<?php echo esc_attr($customer_first_name); ?>" placeholder="<?php esc_attr_e('First Name', 'maneli-car-inquiry'); ?>" required>
                                            <div class="invalid-feedback">لطفاً نام را وارد کنید.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="la la-user me-1 text-muted"></i>
                                                نام خانوادگی <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="last_name" class="form-control" value="<?php echo esc_attr($customer_last_name); ?>" placeholder="<?php esc_attr_e('Last Name', 'maneli-car-inquiry'); ?>" required>
                                            <div class="invalid-feedback">لطفاً نام خانوادگی را وارد کنید.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="la la-mobile me-1 text-muted"></i>
                                                شماره موبایل <span class="text-danger">*</span>
                                            </label>
                                            <input type="tel" name="mobile_number" class="form-control" value="<?php echo esc_attr($customer_mobile); ?>" placeholder="09123456789" required>
                                            <div class="invalid-feedback">لطفاً شماره موبایل را وارد کنید.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="la la-palette me-1 text-muted"></i>
                                                رنگ خودرو مورد نظر
                                            </label>
                                            <input type="text" name="car_color" class="form-control" placeholder="<?php esc_attr_e('Example: White', 'maneli-car-inquiry'); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">
                                                <i class="la la-comment me-1 text-muted"></i>
                                                توضیحات اضافی
                                            </label>
                                            <textarea name="description" class="form-control" rows="3" placeholder="<?php esc_attr_e('Write your description or special request...', 'maneli-car-inquiry'); ?>"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CAPTCHA Widget -->
                        <?php if (class_exists('Maneli_Captcha_Helper') && Maneli_Captcha_Helper::is_enabled()): 
                            $captcha_type = Maneli_Captcha_Helper::get_captcha_type();
                            $site_key = Maneli_Captcha_Helper::get_site_key($captcha_type);
                            if (!empty($captcha_type) && !empty($site_key)):
                                if ($captcha_type === 'recaptcha_v2' || $captcha_type === 'hcaptcha'): ?>
                                    <div class="mt-3 mb-3">
                                        <?php echo Maneli_Captcha_Helper::render_widget($captcha_type, $site_key, 'maneli-captcha-widget-cash-inquiry'); ?>
                                    </div>
                                <?php elseif ($captcha_type === 'recaptcha_v3'): ?>
                                    <!-- reCAPTCHA v3 badge will be automatically displayed by Google -->
                                    <div class="maneli-recaptcha-v3-badge" style="display:none;"></div>
                                <?php endif;
                            endif;
                        endif; ?>

                        <!-- Submit Button -->
                        <div class="mt-4">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg btn-wave" id="submit-cash-inquiry">
                                    <i class="la la-paper-plane me-2"></i>
                                    ارسال درخواست خرید نقدی
                                </button>
                            </div>
                            <div class="text-center mt-3">
                                <a href="<?php echo esc_url(home_url('/dashboard')); ?>" class="btn btn-light btn-sm">
                                    <i class="la la-arrow-right me-1"></i>
                                    <?php esc_html_e('Back to Dashboard', 'maneli-car-inquiry'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

    </div>
</div>
<!-- End::main-content -->

<script>
(function() {
    function waitForJQuery() {
        if (typeof jQuery !== "undefined") {
            jQuery(document).ready(function($) {
                'use strict';
                
                const productSelect = $('#product_id_customer');
                const carDetailsSection = $('#car-details-section');
                let selectedCarData = null;
                
                // Initialize Select2 for car search
                productSelect.select2({
                    placeholder: 'جستجوی خودرو...',
                    allowClear: true,
                    minimumInputLength: 2,
                    dir: 'rtl',
                    language: {
                        inputTooShort: function() {
                            return 'حداقل 2 حرف تایپ کنید';
                        },
                        searching: function() {
                            return 'در حال جستجو...';
                        },
                        noResults: function() {
                            return 'نتیجه‌ای یافت نشد';
                        }
                    },
                    ajax: {
                        url: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
                        dataType: 'json',
                        delay: 250,
                        method: 'POST',
                        data: function(params) {
                            return {
                                action: 'maneli_search_cars',
                                nonce: '<?php echo wp_create_nonce('maneli_expert_car_search_nonce'); ?>',
                                search: params.term
                            };
                        },
                        processResults: function(data) {
                            if (data.success) {
                                return { results: data.data.results };
                            } else {
                                return { results: [] };
                            }
                        },
                        cache: true
                    }
                });
                
                // Handle car selection
                productSelect.on('select2:select', function(e) {
                    const data = e.params.data;
                    selectedCarData = data;
                    
                    // Update car details
                    $('#car-name').text(data.text);
                    $('#car-price').text(formatMoney(data.price) + ' تومان');
                    $('#car-min-downpayment').text(formatMoney(data.min_downpayment) + ' تومان');
                    
                    // Update car image
                    if (data.image_url) {
                        $('#car-image').attr('src', data.image_url);
                        $('#car-image-container').show();
                    } else {
                        $('#car-image-container').hide();
                    }
                    
                    // Show details section with animation
                    carDetailsSection.slideDown(400);
                });
                
                // Handle car unselect
                productSelect.on('select2:unselect', function() {
                    selectedCarData = null;
                    carDetailsSection.slideUp(400);
                });
                
                // Helper function to format money
                function formatMoney(num) {
                    if (isNaN(num) || num === null) return '۰';
                    return Math.ceil(num).toLocaleString('fa-IR');
                }
                
                // Form submission
                $('#customer-cash-inquiry-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    if (!this.checkValidity()) {
                        e.stopPropagation();
                        $(this).addClass('was-validated');
                        return;
                    }
                    
                    if (!selectedCarData) {
                        Swal.fire({
                            icon: 'warning',
                            title: <?php echo wp_json_encode(esc_html__('Attention', 'maneli-car-inquiry')); ?>,
                            text: <?php echo wp_json_encode(esc_html__('Please select a car', 'maneli-car-inquiry')); ?>,
                            confirmButtonText: <?php echo wp_json_encode(esc_html__('Got it', 'maneli-car-inquiry')); ?>
                        });
                        return;
                    }
                    
                    const formData = new FormData(this);
                    formData.append('action', 'maneli_create_customer_cash_inquiry');
                    formData.append('nonce', '<?php echo wp_create_nonce('maneli_customer_cash_inquiry'); ?>');
                    
                    // Get CAPTCHA token if enabled
                    if (typeof maneliCaptcha !== 'undefined' && maneliCaptchaConfig && maneliCaptchaConfig.enabled) {
                        maneliCaptcha.getToken().then(function(token) {
                            if (token) {
                                if (maneliCaptchaConfig.type === 'hcaptcha') {
                                    formData.append('h-captcha-response', token);
                                } else if (maneliCaptchaConfig.type === 'recaptcha_v2') {
                                    formData.append('g-recaptcha-response', token);
                                } else if (maneliCaptchaConfig.type === 'recaptcha_v3') {
                                    formData.append('captcha_token', token);
                                }
                            }
                            submitForm(formData);
                        }).catch(function() {
                            submitForm(formData);
                        });
                    } else {
                        submitForm(formData);
                    }
                    
                    function submitForm(formDataToSubmit) {
                        // Show loading
                        const submitBtn = $('#submit-cash-inquiry');
                        const originalText = submitBtn.html();
                        submitBtn.prop('disabled', true).html('<i class="la la-spinner la-spin me-2"></i>در حال ارسال...');
                        
                        $.ajax({
                            url: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
                            type: 'POST',
                            data: formDataToSubmit,
                            processData: false,
                            contentType: false,
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'موفق!',
                                    html: response.data.message || 'درخواست نقدی شما ثبت شد و به زودی کارشناس با شما تماس خواهد گرفت.',
                                    confirmButtonText: 'مشاهده درخواست‌ها',
                                    allowOutsideClick: false
                                }).then(() => {
                                    window.location.href = <?php echo wp_json_encode(home_url('/dashboard/inquiries/cash')); ?>;
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطا',
                                    text: response.data.message || 'خطا در ثبت درخواست',
                                    confirmButtonText: 'متوجه شدم'
                                });
                                submitBtn.prop('disabled', false).html(originalText);
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطا',
                                text: 'خطا در برقراری ارتباط با سرور',
                                confirmButtonText: 'متوجه شدم'
                            });
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    });
                });
            });
        } else {
            setTimeout(waitForJQuery, 50);
        }
    }
    waitForJQuery();
})();
</script>
