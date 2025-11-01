<?php
/**
 * Template for New Installment Inquiry Page (Dashboard)
 * 
 * This page allows experts/admins to create a new installment inquiry on behalf of customers
 * Includes full form with all fields from the expert panel
 * 
 * @package Maneli_Car_Inquiry/Templates/Dashboard
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Permission check - Only admins and experts can create inquiries
$is_admin = current_user_can('manage_maneli_inquiries');
$is_expert = in_array('maneli_expert', wp_get_current_user()->roles, true);

if (!$is_admin && !$is_expert) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="la la-exclamation-triangle me-2"></i>
                <?php esc_html_e('You do not have access to this page.', 'maneli-car-inquiry'); ?>
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

// Enqueue Persian Datepicker - use persianDatepicker like in expert reports
if (!wp_script_is('maneli-persian-datepicker', 'enqueued')) {
    wp_enqueue_script('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/persianDatepicker.min.js', ['jquery'], '1.0.0', true);
}
if (!wp_style_is('maneli-persian-datepicker', 'enqueued')) {
    wp_enqueue_style('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/persianDatepicker-default.css', [], '1.0.0');
}

// Enqueue inquiry form script (for datepicker initialization)
if (!wp_script_is('maneli-inquiry-form-js', 'enqueued')) {
    wp_enqueue_script('maneli-inquiry-form-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-form.js', ['jquery', 'maneli-persian-datepicker'], filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/frontend/inquiry-form.js'), true);
}

// Enqueue expert panel JS (for car search and calculator)
add_action('wp_footer', function() {
    $options = get_option('maneli_inquiry_all_options', []);
    $interest_rate = floatval($options['loan_interest_rate'] ?? 0.035);
    ?>
    <script>
    var maneli_expert_ajax = {
        ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('maneli_expert_car_search_nonce'); ?>',
        interestRate: <?php echo $interest_rate; ?>,
        text: {
            search_placeholder: 'جستجوی خودرو...',
            no_results: 'نتیجه‌ای یافت نشد',
            searching: 'در حال جستجو...',
            select_car: 'لطفاً یک خودرو انتخاب کنید',
            server_error: 'خطای سرور. لطفاً دوباره تلاش کنید.',
            unknown_error: 'خطای ناشناخته رخ داده است.',
            datepicker_placeholder: 'YYYY/MM/DD',
            car_price: 'قیمت خودرو',
            down_payment: 'پیش‌پرداخت',
            loan_amount: 'مبلغ وام',
            term_months: 'مدت (ماه)',
            monthly_installment: 'قسط ماهانه',
            total_price: 'قیمت کل',
            toman: <?php echo wp_json_encode(esc_html__('Toman', 'maneli-car-inquiry')); ?>,
            month: 'ماه',
        }
    };
    </script>
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/expert-panel.js?ver=<?php echo filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/expert-panel.js'); ?>"></script>
    <?php
}, 999);

// Get experts list for dropdown (only for admins)
$experts = [];
if (current_user_can('manage_maneli_inquiries')) {
    $experts = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
}
?>
?><div class="main-content app-content">
    <div class="container-fluid">

<!-- Start::row -->
<div class="row">
    <div class="col-xl-12">
        <?php if (isset($_GET['inquiry_created']) && $_GET['inquiry_created'] == '1') : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="la la-check-circle me-2"></i>
                <strong>موفق!</strong> استعلام جدید با موفقیت ثبت شد.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="la la-file-invoice me-2"></i>
                    ثبت استعلام اقساطی جدید
                </div>
                <div class="btn-list">
                    <a href="<?php echo home_url('/dashboard/inquiries/installment'); ?>" class="btn btn-light btn-wave">
                        <i class="la la-arrow-right me-1"></i>
                        بازگشت به لیست
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form id="expert-inquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="maneli_expert_create_inquiry">
                    <?php wp_nonce_field('maneli_expert_create_nonce'); ?>

                    <!-- Step 1: Car Selection -->
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-3">
                            <span class="avatar avatar-sm avatar-rounded bg-primary-transparent me-2">
                                <i class="la la-car fs-18"></i>
                            </span>
                            <span class="align-middle">انتخاب خودرو و شرایط خرید</span>
                        </h5>
                        
                        <div class="mb-3">
                            <label for="product_id_expert" class="form-label">
                                <i class="la la-search me-1 text-primary"></i>
                                جستجوی خودرو
                                <span class="text-danger">*</span>
                            </label>
                            <select id="product_id_expert" name="product_id" class="form-select form-select-lg maneli-car-search-select" required>
                                <option value=""></option>
                            </select>
                            <div class="form-text text-muted">
                                <i class="la la-info-circle me-1"></i>
                                حداقل 2 حرف از نام خودرو را تایپ کنید تا نتایج نمایش داده شود
                            </div>
                        </div>
                        
                        <?php if (current_user_can('manage_maneli_inquiries') && !empty($experts)): ?>
                            <div class="mb-3 pt-3">
                                <label for="assigned_expert_id" class="form-label">
                                    <i class="la la-user-tie me-1 text-info"></i>
                                    ارجاع به کارشناس
                                </label>
                                <select id="assigned_expert_id" name="assigned_expert_id" class="form-select">
                                    <option value="auto">
                                        <i class="la la-sync"></i>
                                        ارجاع خودکار (Round-robin)
                                    </option>
                                    <?php foreach ($experts as $expert) : ?>
                                        <option value="<?php echo esc_attr($expert->ID); ?>">
                                            <?php echo esc_html($expert->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-muted">
                                    در صورت عدم انتخاب، سیستم به صورت خودکار کارشناس را تعیین می‌کند
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Calculator Wrapper -->
                    <div id="loan-calculator-wrapper"></div>

                    <!-- Step 2: Identity Information -->
                    <div id="expert-form-details" style="display: none;">
                        <div class="mb-4 pb-3 border-bottom">
                            <h5 class="mb-3">
                                <span class="avatar avatar-sm avatar-rounded bg-info-transparent me-2">
                                    <i class="la la-id-card fs-18"></i>
                                </span>
                                <span class="align-middle">اطلاعات هویتی</span>
                            </h5>
                            
                            <div class="alert alert-light border" role="alert">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check form-check-lg">
                                        <input class="form-check-input" type="radio" name="issuer_type" id="issuer-self" value="self" checked>
                                        <label class="form-check-label fw-medium" for="issuer-self">
                                            <i class="la la-user-check text-success me-1"></i>
                                            خریدار و صادرکننده چک یک نفر هستند
                                        </label>
                                    </div>
                                    <div class="form-check form-check-lg">
                                        <input class="form-check-input" type="radio" name="issuer_type" id="issuer-other" value="other">
                                        <label class="form-check-label fw-medium" for="issuer-other">
                                            <i class="la la-user-friends text-warning me-1"></i>
                                            صادرکننده چک فرد دیگری است
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                        <div id="buyer-form-wrapper">
                            <div class="card custom-card shadow-none border mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">
                                        <i class="la la-user-circle text-primary me-2"></i>
                                        اطلاعات خریدار
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Personal Info -->
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-user me-1 text-muted"></i>
                                                نام <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="first_name" class="form-control" placeholder="<?php esc_attr_e('First Name', 'maneli-car-inquiry'); ?>" required>
                                            <div class="invalid-feedback">لطفاً نام را وارد کنید.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-user me-1 text-muted"></i>
                                                نام خانوادگی <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="last_name" class="form-control" placeholder="<?php esc_attr_e('Last Name', 'maneli-car-inquiry'); ?>" required>
                                            <div class="invalid-feedback">لطفاً نام خانوادگی را وارد کنید.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-male me-1 text-muted"></i>
                                                نام پدر <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="father_name" class="form-control" placeholder="<?php esc_attr_e('Father\'s Name', 'maneli-car-inquiry'); ?>" required>
                                            <div class="invalid-feedback">لطفاً نام پدر را وارد کنید.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-birthday-cake me-1 text-muted"></i>
                                                تاریخ تولد <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="expert_buyer_birth_date" name="birth_date" class="form-control maneli-datepicker" placeholder="1370/01/01" required>
                                            <div class="invalid-feedback">لطفاً تاریخ تولد را وارد کنید.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-id-card me-1 text-muted"></i>
                                                کد ملی <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="national_code" class="form-control" placeholder="0123456789" required pattern="\d{10}" maxlength="10">
                                            <div class="invalid-feedback">لطفاً کد ملی 10 رقمی را وارد کنید.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-mobile me-1 text-muted"></i>
                                                شماره موبایل <span class="text-danger">*</span>
                                            </label>
                                            <input type="tel" name="mobile_number" class="form-control" placeholder="09123456789" required>
                                            <div class="invalid-feedback">لطفاً شماره موبایل را وارد کنید.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Job Info -->
                                    <div class="row g-3 mt-3">
                                        <div class="col-12">
                                            <h6 class="text-muted mb-2">
                                                <i class="la la-briefcase me-1"></i>
                                                اطلاعات شغلی
                                            </h6>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">نوع شغل</label>
                                            <select name="job_type" id="buyer_job_type" class="form-select">
                                                <option value="">-- انتخاب کنید --</option>
                                                <option value="self"><?php esc_html_e('Self-employed', 'maneli-car-inquiry'); ?></option>
                                                <option value="employee"><?php esc_html_e('Employee', 'maneli-car-inquiry'); ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 buyer-job-title-wrapper" style="display:none;">
                                            <label class="form-label">عنوان شغلی</label>
                                            <input type="text" name="job_title" id="buyer_job_title" class="form-control" placeholder="<?php esc_attr_e('Example: Engineer', 'maneli-car-inquiry'); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-money-bill me-1 text-success"></i>
                                                <?php echo esc_html__('Income Amount (Toman)', 'maneli-car-inquiry'); ?>
                                            </label>
                                            <input type="number" name="income_level" class="form-control" placeholder="0">
                                        </div>
                                        <div class="col-md-4 buyer-property-wrapper" style="display:none;">
                                            <label class="form-label">
                                                <i class="la la-home me-1"></i>
                                                وضعیت مسکن
                                            </label>
                                            <select name="residency_status" id="buyer_residency_status" class="form-select">
                                                <option value="">-- انتخاب کنید --</option>
                                                <option value="owner"><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                                                <option value="tenant"><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Contact Info -->
                                    <div class="row g-3 mt-3">
                                        <div class="col-12">
                                            <h6 class="text-muted mb-2">
                                                <i class="la la-address-book me-1"></i>
                                                اطلاعات تماس
                                            </h6>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="la la-phone me-1"></i>
                                                تلفن ثابت
                                            </label>
                                            <input type="tel" name="phone_number" class="form-control" placeholder="02112345678">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="la la-map-marker me-1"></i>
                                                آدرس
                                            </label>
                                            <textarea name="address" class="form-control" rows="1" placeholder="<?php esc_attr_e('Full Address', 'maneli-car-inquiry'); ?>"></textarea>
                                        </div>
                                    </div>
                                    
                                    <!-- Bank Info -->
                                    <div class="row g-3 mt-3">
                                        <div class="col-12">
                                            <h6 class="text-muted mb-2">
                                                <i class="la la-university me-1"></i>
                                                اطلاعات بانکی
                                            </h6>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">نام بانک</label>
                                            <input type="text" name="bank_name" class="form-control" placeholder="<?php esc_attr_e('Example: National', 'maneli-car-inquiry'); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">شماره حساب</label>
                                            <input type="text" name="account_number" class="form-control" placeholder="1234567890">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">کد شعبه</label>
                                            <input type="text" name="branch_code" class="form-control" placeholder="1234">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">نام شعبه</label>
                                            <input type="text" name="branch_name" class="form-control" placeholder="<?php esc_attr_e('Example: Central', 'maneli-car-inquiry'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="issuer-form-wrapper" style="display: none;">
                            <div class="card custom-card shadow-none border border-warning mb-4">
                                <div class="card-header bg-warning-transparent">
                                    <h6 class="card-title mb-0">
                                        <i class="la la-user-shield text-warning me-2"></i>
                                        اطلاعات صادرکننده چک
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Personal Info -->
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-user me-1 text-muted"></i>
                                                نام
                                            </label>
                                            <input type="text" name="issuer_first_name" class="form-control" placeholder="<?php esc_attr_e('First Name', 'maneli-car-inquiry'); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-user me-1 text-muted"></i>
                                                نام خانوادگی
                                            </label>
                                            <input type="text" name="issuer_last_name" class="form-control" placeholder="<?php esc_attr_e('Last Name', 'maneli-car-inquiry'); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-male me-1 text-muted"></i>
                                                نام پدر
                                            </label>
                                            <input type="text" name="issuer_father_name" class="form-control" placeholder="<?php esc_attr_e('Father\'s Name', 'maneli-car-inquiry'); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-birthday-cake me-1 text-muted"></i>
                                                تاریخ تولد
                                            </label>
                                            <input type="text" id="expert_issuer_birth_date" name="issuer_birth_date" class="form-control maneli-datepicker" placeholder="1370/01/01">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-id-card me-1 text-muted"></i>
                                                کد ملی
                                            </label>
                                            <input type="text" name="issuer_national_code" class="form-control" placeholder="0123456789" pattern="\d{10}" maxlength="10">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-mobile me-1 text-muted"></i>
                                                شماره موبایل
                                            </label>
                                            <input type="tel" name="issuer_mobile_number" class="form-control" placeholder="09123456789">
                                        </div>
                                    </div>
                                    
                                    <!-- Job Info -->
                                    <div class="row g-3 mt-3">
                                        <div class="col-12">
                                            <h6 class="text-muted mb-2">
                                                <i class="la la-briefcase me-1"></i>
                                                اطلاعات شغلی
                                            </h6>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">نوع شغل</label>
                                            <select name="issuer_job_type" id="issuer_job_type" class="form-select">
                                                <option value="">-- انتخاب کنید --</option>
                                                <option value="self"><?php esc_html_e('Self-employed', 'maneli-car-inquiry'); ?></option>
                                                <option value="employee"><?php esc_html_e('Employee', 'maneli-car-inquiry'); ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 issuer-job-title-wrapper" style="display:none;">
                                            <label class="form-label">عنوان شغلی</label>
                                            <input type="text" name="issuer_job_title" id="issuer_job_title" class="form-control" placeholder="مثال: مهندس">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="la la-money-bill me-1 text-success"></i>
                                                <?php echo esc_html__('Income Amount (Toman)', 'maneli-car-inquiry'); ?>
                                            </label>
                                            <input type="number" name="issuer_income_level" class="form-control" placeholder="0">
                                        </div>
                                        <div class="col-md-4 issuer-property-wrapper" style="display:none;">
                                            <label class="form-label">
                                                <i class="la la-home me-1"></i>
                                                وضعیت مسکن
                                            </label>
                                            <select name="issuer_residency_status" id="issuer_residency_status" class="form-select">
                                                <option value="">-- انتخاب کنید --</option>
                                                <option value="owner"><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                                                <option value="tenant"><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Contact Info -->
                                    <div class="row g-3 mt-3">
                                        <div class="col-12">
                                            <h6 class="text-muted mb-2">
                                                <i class="la la-address-book me-1"></i>
                                                اطلاعات تماس
                                            </h6>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="la la-phone me-1"></i>
                                                تلفن ثابت
                                            </label>
                                            <input type="tel" name="issuer_phone_number" class="form-control" placeholder="02112345678">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="la la-map-marker me-1"></i>
                                                آدرس
                                            </label>
                                            <textarea name="issuer_address" class="form-control" rows="1" placeholder="آدرس کامل"></textarea>
                                        </div>
                                    </div>
                                    
                                    <!-- Bank Info -->
                                    <div class="row g-3 mt-3">
                                        <div class="col-12">
                                            <h6 class="text-muted mb-2">
                                                <i class="la la-university me-1"></i>
                                                اطلاعات بانکی
                                            </h6>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">نام بانک</label>
                                            <input type="text" name="issuer_bank_name" class="form-control" placeholder="مثال: ملی">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">شماره حساب</label>
                                            <input type="text" name="issuer_account_number" class="form-control" placeholder="1234567890">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">کد شعبه</label>
                                            <input type="text" name="issuer_branch_code" class="form-control" placeholder="1234">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">نام شعبه</label>
                                            <input type="text" name="issuer_branch_name" class="form-control" placeholder="مثال: مرکزی">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="mt-4">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg btn-wave">
                                    <i class="la la-save me-2"></i>
                                    ثبت استعلام و ایجاد کاربر
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->

<!-- Custom JavaScript for Form Interactivity -->
<script>
jQuery(document).ready(function($) {
    'use strict';
    
    const $form = $('#expert-inquiry-form');
    if (!$form.length) return;
    
    // Handle issuer type radio buttons using event delegation
    $(document).on('change', 'input[name="issuer_type"]', function() {
        const issuerFormWrapper = $('#issuer-form-wrapper');
        const selectedValue = $(this).val();
        
        if (selectedValue === 'other') {
            issuerFormWrapper.slideDown(300);
        } else {
            issuerFormWrapper.slideUp(300);
        }
    });
    
    // Handle buyer job type changes using event delegation
    $(document).on('change', '#buyer_job_type', function() {
        const jobValue = $(this).val();
        const $jobTitleWrapper = $('.buyer-job-title-wrapper');
        const $propertyWrapper = $('.buyer-property-wrapper');
        
        if (jobValue === 'self') {
            // آزاد: نیاز به عنوان شغلی و وضعیت مسکن دارد
            $jobTitleWrapper.slideDown(200);
            $propertyWrapper.slideDown(200);
        } else if (jobValue === 'employee') {
            // کارمند: فقط درآمد لازم است، بقیه مخفی
            $jobTitleWrapper.slideUp(200);
            $propertyWrapper.slideUp(200);
        } else {
            // هیچکدام انتخاب نشده
            $jobTitleWrapper.slideUp(200);
            $propertyWrapper.slideUp(200);
        }
    });
    
    // Handle issuer job type changes using event delegation
    $(document).on('change', '#issuer_job_type', function() {
        const jobValue = $(this).val();
        const $jobTitleWrapper = $('.issuer-job-title-wrapper');
        const $propertyWrapper = $('.issuer-property-wrapper');
        
        if (jobValue === 'self') {
            // آزاد: نیاز به عنوان شغلی و وضعیت مسکن دارد
            $jobTitleWrapper.slideDown(200);
            $propertyWrapper.slideDown(200);
        } else if (jobValue === 'employee') {
            // کارمند: فقط درآمد لازم است، بقیه مخفی
            $jobTitleWrapper.slideUp(200);
            $propertyWrapper.slideUp(200);
        } else {
            // هیچکدام انتخاب نشده
            $jobTitleWrapper.slideUp(200);
            $propertyWrapper.slideUp(200);
        }
    });
    
    // Form validation
    $form.on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
});
</script>

</div>
</div>
