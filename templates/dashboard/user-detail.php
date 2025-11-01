<!-- Start::row -->
<?php
/**
 * User Detail Page
 * Shows user account information and inquiries
 * Based on profile-settings.html design
 */

// Helper function to convert numbers to Persian
if (!function_exists('persian_numbers')) {
    function persian_numbers($str) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, $str);
    }
}

// Permission check
if (!current_user_can('manage_maneli_inquiries')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Get user ID from query vars or GET parameters
$view_user_id = isset($_GET['view_user']) ? intval($_GET['view_user']) : (get_query_var('view_user') ? intval(get_query_var('view_user')) : 0);
$edit_user_id = isset($_GET['edit_user']) ? intval($_GET['edit_user']) : (get_query_var('edit_user') ? intval(get_query_var('edit_user')) : 0);
$user_id = $view_user_id ?: $edit_user_id;
$is_edit_mode = $edit_user_id > 0;

if (!$user_id) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-warning">
                <i class="la la-exclamation-triangle me-2"></i>
                <?php esc_html_e('User not specified.', 'maneli-car-inquiry'); ?>
            </div>
        </div>
    </div>
    <?php
    return;
}

$user = get_userdata($user_id);
if (!$user) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-danger">
                <i class="la la-exclamation-triangle me-2"></i>
                <?php esc_html_e('User not found.', 'maneli-car-inquiry'); ?>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Get user meta data
$mobile_number = get_user_meta($user_id, 'mobile_number', true);
$national_code = get_user_meta($user_id, 'national_code', true);
$father_name = get_user_meta($user_id, 'father_name', true);
$birth_date = get_user_meta($user_id, 'birth_date', true);
$occupation = get_user_meta($user_id, 'occupation', true);
$job_title = get_user_meta($user_id, 'job_title', true);
$job_type = get_user_meta($user_id, 'job_type', true);
$income_level = get_user_meta($user_id, 'income_level', true);
$phone_number = get_user_meta($user_id, 'phone_number', true);
$address = get_user_meta($user_id, 'address', true);
$residency_status = get_user_meta($user_id, 'residency_status', true);
$workplace_status = get_user_meta($user_id, 'workplace_status', true);
$bank_name = get_user_meta($user_id, 'bank_name', true);
$account_number = get_user_meta($user_id, 'account_number', true);
$branch_code = get_user_meta($user_id, 'branch_code', true);
$branch_name = get_user_meta($user_id, 'branch_name', true);

// Get user inquiries
$installment_inquiries = get_posts([
    'post_type' => 'inquiry',
    'author' => $user_id,
    'posts_per_page' => -1,
    'post_status' => 'any',
    'orderby' => 'date',
    'order' => 'DESC'
]);

$cash_inquiries = get_posts([
    'post_type' => 'cash_inquiry',
    'author' => $user_id,
    'posts_per_page' => -1,
    'post_status' => 'any',
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Role display
$user_roles = $user->roles;
$role_display = !empty($user_roles) ? $user_roles[0] : '';
$role_labels = [
    'administrator' => esc_html__('Administrator', 'maneli-car-inquiry'),
    'maneli_admin' => esc_html__('Maneli Admin', 'maneli-car-inquiry'),
    'maneli_expert' => esc_html__('Expert', 'maneli-car-inquiry'),
    'customer' => esc_html__('Customer', 'maneli-car-inquiry'),
    'subscriber' => esc_html__('Subscriber', 'maneli-car-inquiry')
];
$role_display_persian = isset($role_labels[$role_display]) ? $role_labels[$role_display] : $role_display;

// Residency status display
$residency_display = '';
if ($residency_status === 'owner') {
    $residency_display = esc_html__('Owner', 'maneli-car-inquiry');
} elseif ($residency_status === 'tenant') {
    $residency_display = esc_html__('Tenant', 'maneli-car-inquiry');
}

// Workplace status display
$workplace_display = '';
if ($workplace_status === 'owner') {
    $workplace_display = esc_html__('Owner', 'maneli-car-inquiry');
} elseif ($workplace_status === 'employee') {
    $workplace_display = esc_html__('Employee', 'maneli-car-inquiry');
} elseif ($workplace_status === 'tenant') {
    $workplace_display = esc_html__('Tenant', 'maneli-car-inquiry');
}
?>
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Dashboard', 'maneli-car-inquiry'); ?></a></li>
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/dashboard/users')); ?>"><?php esc_html_e('User Management', 'maneli-car-inquiry'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $is_edit_mode ? esc_html__('Edit', 'maneli-car-inquiry') : esc_html__('View', 'maneli-car-inquiry'); ?> <?php esc_html_e('User', 'maneli-car-inquiry'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php echo $is_edit_mode ? esc_html__('Edit User', 'maneli-car-inquiry') : esc_html__('User Details', 'maneli-car-inquiry'); ?></h1>
            </div>
            <div>
                <a href="<?php echo esc_url(home_url('/dashboard/users')); ?>" class="btn btn-primary btn-wave">
                    <i class="la la-arrow-right me-1"></i>
                    <?php esc_html_e('Back to List', 'maneli-car-inquiry'); ?>
                </a>
            </div>
        </div>
        <!-- Page Header Close -->

        <!-- Start::row -->
        <div class="row gap-3 justify-content-center">
            <div class="col-xl-9">
                <div class="card custom-card">
                    <ul class="nav nav-tabs tab-style-8 scaleX rounded m-3 profile-settings-tab gap-2" id="userDetailTabs" role="tablist">
                        <li class="nav-item me-1" role="presentation">
                            <button class="nav-link px-4 bg-primary-transparent active" id="account-tab" data-bs-toggle="tab" data-bs-target="#account-pane" type="button" role="tab" aria-controls="account-pane" aria-selected="true">
                                <?php esc_html_e('User Account', 'maneli-car-inquiry'); ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-4 bg-primary-transparent" id="inquiries-tab" data-bs-toggle="tab" data-bs-target="#inquiries-pane" type="button" role="tab" aria-controls="inquiries-pane" aria-selected="false">
                                <?php esc_html_e('Registered Inquiries', 'maneli-car-inquiry'); ?>
                            </button>
                        </li>
                    </ul>
                    <div class="p-3 border-bottom border-top border-block-end-dashed tab-content">
                        <!-- Account Tab -->
                        <div class="tab-pane show active overflow-hidden p-0 border-0" id="account-pane" role="tabpanel" aria-labelledby="account-tab" tabindex="0">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                                    <div class="fw-semibold d-block fs-15"><?php echo $is_edit_mode ? esc_html__('Edit User Account', 'maneli-car-inquiry') : esc_html__('User Account Information', 'maneli-car-inquiry'); ?>:</div>
                                    <?php if ($is_edit_mode): ?>
                                        <button class="btn btn-primary btn-sm" onclick="saveUserDetails(<?php echo $user_id; ?>)">
                                            <i class="la la-save me-1"></i><?php esc_html_e('Save Changes', 'maneli-car-inquiry'); ?>
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url(add_query_arg(['edit_user' => $user_id], home_url('/dashboard/users'))); ?>" class="btn btn-primary btn-sm">
                                            <i class="la la-edit me-1"></i><?php esc_html_e('Edit', 'maneli-car-inquiry'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('First Name', 'maneli-car-inquiry'); ?> <?php echo $is_edit_mode ? '*' : ''; ?>:</label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="text" class="form-control" id="edit-user-first-name" value="<?php echo esc_attr($user->first_name); ?>" placeholder="<?php esc_attr_e('First Name', 'maneli-car-inquiry'); ?>">
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($user->first_name)) : esc_html($user->first_name); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Last Name', 'maneli-car-inquiry'); ?> <?php echo $is_edit_mode ? '*' : ''; ?>:</label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="text" class="form-control" id="edit-user-last-name" value="<?php echo esc_attr($user->last_name); ?>" placeholder="<?php esc_attr_e('Last Name', 'maneli-car-inquiry'); ?>">
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($user->last_name)) : esc_html($user->last_name); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?> <?php echo $is_edit_mode ? '*' : ''; ?>:</label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="tel" class="form-control" id="edit-user-mobile" value="<?php echo esc_attr($mobile_number); ?>" placeholder="09123456789">
                                        <?php else: ?>
                                            <div class="form-control-plaintext">
                                                <a href="tel:<?php echo esc_attr($mobile_number); ?>" class="text-primary text-decoration-none">
                                                    <?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($mobile_number)) : esc_html($mobile_number); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Role:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <select class="form-select" id="edit-user-role">
                                                <option value="customer" <?php selected($role_display, 'customer'); ?>><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></option>
                                                <option value="maneli_expert" <?php selected($role_display, 'maneli_expert'); ?>><?php esc_html_e('Expert', 'maneli-car-inquiry'); ?></option>
                                                <option value="administrator" <?php selected($role_display, 'administrator'); ?>><?php esc_html_e('Administrator', 'maneli-car-inquiry'); ?></option>
                                            </select>
                                        <?php else: ?>
                                            <div class="form-control-plaintext">
                                                <span class="badge bg-info"><?php echo esc_html($role_display_persian); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Identity Information Section -->
                                    <div class="col-xl-12">
                                        <hr class="my-3">
                                        <h5 class="fw-semibold mb-3"><?php esc_html_e('Identity Information', 'maneli-car-inquiry'); ?></h5>
                                    </div>
                                    
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('National Code:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="text" class="form-control" id="edit-user-national-code" value="<?php echo esc_attr($national_code); ?>" placeholder="0123456789" pattern="\d{10}" maxlength="10">
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($national_code ?: '-')) : esc_html($national_code ?: '-'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Father\'s Name:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="text" class="form-control" id="edit-user-father-name" value="<?php echo esc_attr($father_name); ?>" placeholder="<?php esc_attr_e('Father\'s Name', 'maneli-car-inquiry'); ?>">
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo esc_html($father_name ?: '-'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Birth Date:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="text" class="form-control maneli-datepicker" id="edit-user-birth-date" value="<?php echo esc_attr($birth_date); ?>" placeholder="1370/01/01">
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo persian_numbers(esc_html($birth_date ?: '-')); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Phone Number:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="tel" class="form-control" id="edit-user-phone" value="<?php echo esc_attr($phone_number); ?>" placeholder="02112345678">
                                        <?php else: ?>
                                            <div class="form-control-plaintext">
                                                <?php if ($phone_number): ?>
                                                    <a href="tel:<?php echo esc_attr($phone_number); ?>" class="text-primary text-decoration-none">
                                                        <?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($phone_number)) : esc_html($phone_number); ?>
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Job Information Section -->
                                    <div class="col-xl-12">
                                        <hr class="my-3">
                                        <h5 class="fw-semibold mb-3"><?php esc_html_e('Job Information', 'maneli-car-inquiry'); ?></h5>
                                    </div>
                                    
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Occupation / Position:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="text" class="form-control" id="edit-user-occupation" value="<?php echo esc_attr($job_title ?: $occupation); ?>" placeholder="<?php esc_attr_e('Occupation or Position', 'maneli-car-inquiry'); ?>">
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo esc_html($job_title ?: $occupation ?: '-'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Job Type:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <select class="form-select" id="edit-user-job-type">
                                                <option value=""><?php esc_html_e('-- Please Select --', 'maneli-car-inquiry'); ?></option>
                                                <option value="government" <?php selected($job_type, 'government'); ?>><?php esc_html_e('Government', 'maneli-car-inquiry'); ?></option>
                                                <option value="private" <?php selected($job_type, 'private'); ?>><?php esc_html_e('Private', 'maneli-car-inquiry'); ?></option>
                                                <option value="self_employed" <?php selected($job_type, 'self_employed'); ?>><?php esc_html_e('Self-Employed', 'maneli-car-inquiry'); ?></option>
                                            </select>
                                        <?php else: ?>
                                            <div class="form-control-plaintext">
                                                <?php
                                                $job_type_display = '';
                                                if ($job_type === 'government') $job_type_display = esc_html__('Government', 'maneli-car-inquiry');
                                                elseif ($job_type === 'private') $job_type_display = esc_html__('Private', 'maneli-car-inquiry');
                                                elseif ($job_type === 'self_employed') $job_type_display = esc_html__('Self-Employed', 'maneli-car-inquiry');
                                                echo esc_html($job_type_display ?: '-');
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Income Level (Toman):', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="number" class="form-control" id="edit-user-income" value="<?php echo esc_attr($income_level); ?>" placeholder="<?php esc_attr_e('Example: 50000000', 'maneli-car-inquiry'); ?>">
                                        <?php else: ?>
                                            <div class="form-control-plaintext">
                                                <?php echo $income_level ? persian_numbers(number_format_i18n($income_level)) . ' ' . esc_html__('Toman', 'maneli-car-inquiry') : '-'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Workplace Status:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <select class="form-select" id="edit-user-workplace-status">
                                                <option value=""><?php esc_html_e('-- Please Select --', 'maneli-car-inquiry'); ?></option>
                                                <option value="owner" <?php selected($workplace_status, 'owner'); ?>><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                                                <option value="employee" <?php selected($workplace_status, 'employee'); ?>><?php esc_html_e('Employee', 'maneli-car-inquiry'); ?></option>
                                                <option value="tenant" <?php selected($workplace_status, 'tenant'); ?>><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                                            </select>
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo esc_html($workplace_display ?: '-'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Address Information Section -->
                                    <div class="col-xl-12">
                                        <hr class="my-3">
                                        <h5 class="fw-semibold mb-3"><?php esc_html_e('Contact Information', 'maneli-car-inquiry'); ?></h5>
                                    </div>
                                    
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Residency Status:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <select class="form-select" id="edit-user-residency-status">
                                                <option value=""><?php esc_html_e('-- Please Select --', 'maneli-car-inquiry'); ?></option>
                                                <option value="owner" <?php selected($residency_status, 'owner'); ?>><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                                                <option value="tenant" <?php selected($residency_status, 'tenant'); ?>><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                                            </select>
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo esc_html($residency_display ?: '-'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-12">
                                        <label class="form-label"><?php esc_html_e('Address:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <textarea class="form-control" id="edit-user-address" rows="3" placeholder="<?php esc_attr_e('Full Address', 'maneli-car-inquiry'); ?>"><?php echo esc_textarea($address); ?></textarea>
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo esc_html($address ?: '-'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Bank Information Section -->
                                    <div class="col-xl-12">
                                        <hr class="my-3">
                                        <h5 class="fw-semibold mb-3"><?php esc_html_e('Bank Information', 'maneli-car-inquiry'); ?></h5>
                                    </div>
                                    
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Bank Name:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="text" class="form-control" id="edit-user-bank-name" value="<?php echo esc_attr($bank_name); ?>" placeholder="<?php esc_attr_e('Bank Name', 'maneli-car-inquiry'); ?>">
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo esc_html($bank_name ?: '-'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Account Number:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="text" class="form-control" id="edit-user-account-number" value="<?php echo esc_attr($account_number); ?>" placeholder="<?php esc_attr_e('Account Number', 'maneli-car-inquiry'); ?>">
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($account_number ?: '-')) : esc_html($account_number ?: '-'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Branch Code:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="text" class="form-control" id="edit-user-branch-code" value="<?php echo esc_attr($branch_code); ?>" placeholder="<?php esc_attr_e('Branch Code', 'maneli-car-inquiry'); ?>">
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($branch_code ?: '-')) : esc_html($branch_code ?: '-'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Branch Name:', 'maneli-car-inquiry'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="text" class="form-control" id="edit-user-branch-name" value="<?php echo esc_attr($branch_name); ?>" placeholder="<?php esc_attr_e('Branch Name', 'maneli-car-inquiry'); ?>">
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo esc_html($branch_name ?: '-'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Inquiries Tab -->
                        <div class="tab-pane overflow-hidden p-0 border-0" id="inquiries-pane" role="tabpanel" aria-labelledby="inquiries-tab" tabindex="0">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                                    <div class="fw-semibold d-block fs-15"><?php esc_html_e('Registered Inquiries:', 'maneli-car-inquiry'); ?></div>
                                    <div class="text-muted">
                                        <span class="badge bg-info-transparent me-2"><?php esc_html_e('Installment Inquiries:', 'maneli-car-inquiry'); ?> <?php echo persian_numbers(number_format_i18n(count($installment_inquiries))); ?></span>
                                        <span class="badge bg-warning-transparent"><?php esc_html_e('Cash Inquiries:', 'maneli-car-inquiry'); ?> <?php echo persian_numbers(number_format_i18n(count($cash_inquiries))); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Installment Inquiries -->
                                <?php if (!empty($installment_inquiries)): ?>
                                    <div class="mb-4">
                                        <h6 class="fw-semibold mb-3">
                                            <i class="la la-bank me-2"></i>
                                            <?php esc_html_e('Installment Inquiries', 'maneli-car-inquiry'); ?> (<?php echo persian_numbers(number_format_i18n(count($installment_inquiries))); ?>)
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                                        <th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                                        <th><?php esc_html_e('Total Price', 'maneli-car-inquiry'); ?></th>
                                                        <th><?php esc_html_e('Down Payment', 'maneli-car-inquiry'); ?></th>
                                                        <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                                        <th><?php esc_html_e('Registration Date', 'maneli-car-inquiry'); ?></th>
                                                        <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($installment_inquiries as $inquiry): 
                                                        $product_id = get_post_meta($inquiry->ID, 'product_id', true);
                                                        $product = wc_get_product($product_id);
                                                        $total_price = get_post_meta($inquiry->ID, 'maneli_inquiry_total_price', true);
                                                        $down_payment = get_post_meta($inquiry->ID, 'maneli_inquiry_down_payment', true);
                                                        $status = get_post_meta($inquiry->ID, 'inquiry_status', true);
                                                        $status_display = [
                                                            'pending' => ['label' => esc_html__('Pending', 'maneli-car-inquiry'), 'class' => 'warning'],
                                                            'approved' => ['label' => esc_html__('Approved', 'maneli-car-inquiry'), 'class' => 'success'],
                                                            'rejected' => ['label' => esc_html__('Rejected', 'maneli-car-inquiry'), 'class' => 'danger'],
                                                            'new' => ['label' => esc_html__('New', 'maneli-car-inquiry'), 'class' => 'info']
                                                        ];
                                                        $status_info = isset($status_display[$status]) ? $status_display[$status] : ['label' => $status, 'class' => 'secondary'];
                                                    ?>
                                                        <tr>
                                                            <td><?php echo persian_numbers('#' . $inquiry->ID); ?></td>
                                                            <td><?php echo $product ? persian_numbers(esc_html($product->get_name())) : '-'; ?></td>
                                                            <td><?php echo $total_price ? persian_numbers(number_format_i18n($total_price)) . ' ' . esc_html__('Toman', 'maneli-car-inquiry') : '-'; ?></td>
                                                            <td><?php echo $down_payment ? persian_numbers(number_format_i18n($down_payment)) . ' ' . esc_html__('Toman', 'maneli-car-inquiry') : '-'; ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo esc_attr($status_info['class']); ?>">
                                                                    <?php echo esc_html($status_info['label']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo persian_numbers(date_i18n('Y/m/d H:i', strtotime($inquiry->post_date))); ?></td>
                                                            <td>
                                                                <a href="<?php echo esc_url(home_url('/dashboard/installment-inquiries?view=' . $inquiry->ID)); ?>" class="btn btn-sm btn-primary-light">
                                                                    <i class="la la-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Cash Inquiries -->
                                <?php if (!empty($cash_inquiries)): ?>
                                    <div class="mb-4">
                                        <h6 class="fw-semibold mb-3">
                                            <i class="la la-money-bill-wave me-2"></i>
                                            <?php esc_html_e('Cash Inquiries', 'maneli-car-inquiry'); ?> (<?php echo persian_numbers(number_format_i18n(count($cash_inquiries))); ?>)
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                                        <th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                                        <th><?php esc_html_e('Price', 'maneli-car-inquiry'); ?></th>
                                                        <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                                        <th><?php esc_html_e('Registration Date', 'maneli-car-inquiry'); ?></th>
                                                        <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($cash_inquiries as $inquiry): 
                                                        $product_id = get_post_meta($inquiry->ID, 'product_id', true);
                                                        $product = wc_get_product($product_id);
                                                        $total_price = get_post_meta($inquiry->ID, 'cash_total_price', true);
                                                        $status = get_post_meta($inquiry->ID, 'cash_inquiry_status', true);
                                                        $status_display = [
                                                            'new' => ['label' => esc_html__('New', 'maneli-car-inquiry'), 'class' => 'info'],
                                                            'in_progress' => ['label' => esc_html__('In Progress', 'maneli-car-inquiry'), 'class' => 'warning'],
                                                            'approved' => ['label' => esc_html__('Approved', 'maneli-car-inquiry'), 'class' => 'success'],
                                                            'rejected' => ['label' => esc_html__('Rejected', 'maneli-car-inquiry'), 'class' => 'danger'],
                                                            'completed' => ['label' => esc_html__('Completed', 'maneli-car-inquiry'), 'class' => 'primary']
                                                        ];
                                                        $status_info = isset($status_display[$status]) ? $status_display[$status] : ['label' => $status, 'class' => 'secondary'];
                                                    ?>
                                                        <tr>
                                                            <td><?php echo persian_numbers('#' . $inquiry->ID); ?></td>
                                                            <td><?php echo $product ? persian_numbers(esc_html($product->get_name())) : '-'; ?></td>
                                                            <td><?php echo $total_price ? persian_numbers(number_format_i18n($total_price)) . ' ' . esc_html__('Toman', 'maneli-car-inquiry') : '-'; ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo esc_attr($status_info['class']); ?>">
                                                                    <?php echo esc_html($status_info['label']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo persian_numbers(date_i18n('Y/m/d H:i', strtotime($inquiry->post_date))); ?></td>
                                                            <td>
                                                                <a href="<?php echo esc_url(home_url('/dashboard/cash-inquiries?view=' . $inquiry->ID)); ?>" class="btn btn-sm btn-primary-light">
                                                                    <i class="la la-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (empty($installment_inquiries) && empty($cash_inquiries)): ?>
                                    <div class="alert alert-info">
                                        <i class="la la-info-circle me-2"></i>
                                        هیچ استعلامی ثبت نشده است.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row -->
    </div>
</div>

<script>
var maneliAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';

// Helper function to generate email from mobile
function generateEmailFromMobile(mobile) {
    return mobile + '@manelikhodro.com';
}

// Save user details
function saveUserDetails(userId) {
    const firstName = jQuery('#edit-user-first-name').val();
    const lastName = jQuery('#edit-user-last-name').val();
    const mobile = jQuery('#edit-user-mobile').val();
    const role = jQuery('#edit-user-role').val();
    const nationalCode = jQuery('#edit-user-national-code').val();
    const fatherName = jQuery('#edit-user-father-name').val();
    const birthDate = jQuery('#edit-user-birth-date').val();
    const phone = jQuery('#edit-user-phone').val();
    const occupation = jQuery('#edit-user-occupation').val();
    const jobType = jQuery('#edit-user-job-type').val();
    const income = jQuery('#edit-user-income').val();
    const workplaceStatus = jQuery('#edit-user-workplace-status').val();
    const residencyStatus = jQuery('#edit-user-residency-status').val();
    const address = jQuery('#edit-user-address').val();
    const bankName = jQuery('#edit-user-bank-name').val();
    const accountNumber = jQuery('#edit-user-account-number').val();
    const branchCode = jQuery('#edit-user-branch-code').val();
    const branchName = jQuery('#edit-user-branch-name').val();
    
    if (!firstName || !lastName || !mobile) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: 'لطفا فیلدهای الزامی را پر کنید.'
        });
        return;
    }
    
    Swal.fire({
        title: 'در حال ذخیره...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    const email = generateEmailFromMobile(mobile);
    
    jQuery.ajax({
        url: maneliAjaxUrl,
        type: 'POST',
        data: {
            action: 'maneli_update_user_full',
            user_id: userId,
            first_name: firstName,
            last_name: lastName,
            display_name: firstName + ' ' + lastName,
            mobile_number: mobile,
            role: role,
            email: email,
            national_code: nationalCode,
            father_name: fatherName,
            birth_date: birthDate,
            phone_number: phone,
            occupation: occupation,
            job_title: occupation,
            job_type: jobType,
            income_level: income,
            workplace_status: workplaceStatus,
            residency_status: residencyStatus,
            address: address,
            bank_name: bankName,
            account_number: accountNumber,
            branch_code: branchCode,
            branch_name: branchName,
            nonce: '<?php echo wp_create_nonce('maneli_update_user_full_nonce'); ?>'
        },
        success: function(response) {
            Swal.close();
            if (response.success) {
                Swal.fire('موفق!', 'اطلاعات کاربر با موفقیت به‌روزرسانی شد.', 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('خطا!', response.data?.message || 'خطا در به‌روزرسانی', 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('AJAX Error:', error, xhr.responseText);
            Swal.fire('خطا!', 'خطا در ارتباط با سرور. لطفا دوباره تلاش کنید.', 'error');
        }
    });
}
</script>

<style>
.tab-style-8 .nav-link.active {
    background-color: var(--primary-color) !important;
    color: white !important;
}

.form-control-plaintext {
    min-height: 38px;
    padding: 0.375rem 0.75rem;
    line-height: 1.5;
}

.table-hover tbody tr:hover {
    background-color: rgba(var(--primary-rgb), 0.03);
}
</style>
