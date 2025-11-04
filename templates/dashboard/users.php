<!-- Start::row -->
<?php
/**
 * Users Management Page
 * Only accessible by Administrators
 * Redesigned to match CRM Contacts style
 */

// Helper function to convert numbers to Persian
if (!function_exists('persian_numbers')) {
    function persian_numbers($str) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, $str);
    }
}

// Permission check - Only Admin can access
if (!current_user_can('manage_maneli_inquiries')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Check if viewing or editing a specific user
$view_user_id = isset($_GET['view_user']) ? intval($_GET['view_user']) : 0;
$edit_user_id = isset($_GET['edit_user']) ? intval($_GET['edit_user']) : 0;
$detail_user_id = $view_user_id ?: $edit_user_id;

if ($detail_user_id) {
    // Load user detail page
    $user_detail_file = MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/user-detail.php';
    if (file_exists($user_detail_file)) {
        include $user_detail_file;
        return;
    }
}

// Get current page for pagination
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';

// Get users with pagination
$user_query_args = [
    'number' => $per_page,
    'offset' => ($paged - 1) * $per_page,
    'orderby' => 'registered',
    'order' => 'DESC',
];

if (!empty($search)) {
    $user_query_args['search'] = '*' . $search . '*';
    $user_query_args['search_columns'] = ['display_name', 'user_login'];
}

if (!empty($role_filter)) {
    $user_query_args['role'] = $role_filter;
}

$user_query = new WP_User_Query($user_query_args);
$users = $user_query->get_results();
$total_users = $user_query->get_total();
$total_pages = ceil($total_users / $per_page);

// Get mobile numbers for users
foreach ($users as $user) {
    $user->mobile_number = get_user_meta($user->ID, 'mobile_number', true);
}

// Statistics
$role_counts = count_users();
$total_all_users = $role_counts['total_users'];
$admin_count = isset($role_counts['avail_roles']['administrator']) ? $role_counts['avail_roles']['administrator'] : 0;
$customer_count = isset($role_counts['avail_roles']['customer']) ? $role_counts['avail_roles']['customer'] : 0;
$expert_count = isset($role_counts['avail_roles']['maneli_expert']) ? $role_counts['avail_roles']['maneli_expert'] : 0;

// Active users (logged in last 7 days)
$active_users_query = new WP_User_Query([
    'meta_query' => [
        [
            'key' => 'last_login',
            'value' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'compare' => '>',
            'type' => 'DATETIME'
        ]
    ]
]);
$active_users_count = $active_users_query->get_total();
?>
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Pages', 'maneli-car-inquiry'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('User Management', 'maneli-car-inquiry'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('User Management', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- Page Header Close -->

        <!-- Start::row -->
        <div class="row">
            <div class="col-xl-12">
                <!-- Statistics Cards -->
                <div class="row mb-3">
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="avatar avatar-md bg-primary-transparent">
                                            <i class="la la-users fs-24"></i>
                                        </span>
                                    </div>
                                    <div class="flex-fill">
                                        <div class="mb-1">
                                            <span class="text-muted fs-13"><?php esc_html_e('Total Users', 'maneli-car-inquiry'); ?></span>
                                        </div>
                                        <h4 class="fw-semibold mb-0"><?php echo persian_numbers(number_format_i18n($total_all_users)); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="avatar avatar-md bg-success-transparent">
                                            <i class="la la-user-shield fs-24"></i>
                                        </span>
                                    </div>
                                    <div class="flex-fill">
                                        <div class="mb-1">
                                            <span class="text-muted fs-13"><?php esc_html_e('Managers', 'maneli-car-inquiry'); ?></span>
                                        </div>
                                        <h4 class="fw-semibold mb-0 text-success"><?php echo persian_numbers(number_format_i18n($admin_count)); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="avatar avatar-md bg-info-transparent">
                                            <i class="la la-user-tie fs-24"></i>
                                        </span>
                                    </div>
                                    <div class="flex-fill">
                                        <div class="mb-1">
                                            <span class="text-muted fs-13"><?php esc_html_e('Experts', 'maneli-car-inquiry'); ?></span>
                                        </div>
                                        <h4 class="fw-semibold mb-0 text-info"><?php echo persian_numbers(number_format_i18n($expert_count)); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="avatar avatar-md bg-warning-transparent">
                                            <i class="la la-user fs-24"></i>
                                        </span>
                                    </div>
                                    <div class="flex-fill">
                                        <div class="mb-1">
                                            <span class="text-muted fs-13">مشتری‌ها</span>
                                        </div>
                                        <h4 class="fw-semibold mb-0 text-warning"><?php echo persian_numbers(number_format_i18n($customer_count)); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Card -->
                <div class="card custom-card overflow-hidden">
                    <div class="card-header d-flex justify-content-between align-items-center border-block-end">
                        <div class="card-title">
                            <i class="la la-users me-2"></i>
                            مدیریت کاربران
                        </div>
                        <div class="btn-list">
                            <button class="btn btn-primary btn-wave" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="la la-plus me-1"></i>
                                کاربر جدید
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="get" action="" class="mb-3">
                            <div class="row g-3 mb-0">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="la la-search"></i>
                                        </span>
                                        <input type="search" name="search" class="form-control" placeholder="<?php esc_attr_e('Search name, mobile...', 'maneli-car-inquiry'); ?>" value="<?php echo esc_attr($search); ?>">
                                        <?php 
                                        // Preserve page parameter if exists
                                        if (isset($_GET['page'])): 
                                        ?>
                                            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
                                        <?php endif; ?>
                                        <?php 
                                        // Preserve other query parameters
                                        foreach ($_GET as $key => $value) {
                                            if (!in_array($key, ['search', 'role', 'page'])) {
                                                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="role">
                                        <option value=""><?php esc_html_e('All Roles', 'maneli-car-inquiry'); ?></option>
                                        <option value="administrator" <?php selected($role_filter, 'administrator'); ?>><?php esc_html_e('Administrator', 'maneli-car-inquiry'); ?></option>
                                        <option value="maneli_expert" <?php selected($role_filter, 'maneli_expert'); ?>><?php esc_html_e('Expert', 'maneli-car-inquiry'); ?></option>
                                        <option value="customer" <?php selected($role_filter, 'customer'); ?>><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary btn-wave">
                                        <i class="la la-search me-1"></i>
                                        <?php esc_html_e('Search', 'maneli-car-inquiry'); ?>
                                    </button>
                                    <?php if (!empty($search) || !empty($role_filter)): ?>
                                        <a href="?" class="btn btn-light">
                                            <i class="la la-times me-1"></i>
                                            <?php esc_html_e('Clear', 'maneli-car-inquiry'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table text-nowrap table-hover table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th><?php esc_html_e('Username', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Role', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($users)): ?>
                                        <?php foreach ($users as $user): 
                                            $user_roles = $user->roles;
                                            $role_display = !empty($user_roles) ? ucfirst($user_roles[0]) : esc_html__('No Role', 'maneli-car-inquiry');
                                            
                                            // Role names
                                            $role_labels = [
                                                'administrator' => esc_html__('Administrator', 'maneli-car-inquiry'),
                                                'maneli_admin' => esc_html__('Maneli Admin', 'maneli-car-inquiry'),
                                                'maneli_expert' => esc_html__('Expert', 'maneli-car-inquiry'),
                                                'customer' => esc_html__('Customer', 'maneli-car-inquiry'),
                                                'subscriber' => esc_html__('Subscriber', 'maneli-car-inquiry')
                                            ];
                                            $role_display = isset($role_labels[$user_roles[0]]) ? $role_labels[$user_roles[0]] : $role_display;
                                            
                                            // Role badge color
                                            $role_colors = [
                                                'administrator' => 'success',
                                                'maneli_admin' => 'success',
                                                'maneli_expert' => 'info',
                                                'customer' => 'warning',
                                                'subscriber' => 'secondary'
                                            ];
                                            $badge_color = isset($role_colors[$user_roles[0]]) ? $role_colors[$user_roles[0]] : 'secondary';
                                            
                                            $mobile_number = get_user_meta($user->ID, 'mobile_number', true);
                                            if (empty($mobile_number)) {
                                                $mobile_number = $user->user_login; // Use username as fallback
                                            }
                                        ?>
                                            <tr data-role="<?php echo esc_attr($user_roles[0]); ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm avatar-rounded me-2">
                                                            <?php echo get_avatar($user->ID, 32); ?>
                                                        </div>
                                                        <div>
                                                            <span class="fw-medium d-block"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($user->display_name)) : esc_html($user->display_name); ?></span>
                                                            <small class="text-muted"><?php echo esc_html($role_display); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="tel:<?php echo esc_attr($mobile_number); ?>" class="fw-medium text-primary text-decoration-none">
                                                        <?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($mobile_number)) : esc_html($mobile_number); ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $badge_color; ?>-transparent">
                                                        <?php echo esc_html($role_display); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($user->user_status == 0): ?>
                                                        <span class="badge bg-success"><?php esc_html_e('Active', 'maneli-car-inquiry'); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger"><?php esc_html_e('Inactive', 'maneli-car-inquiry'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-list">
                                                        <button class="btn btn-sm btn-primary-light" onclick="viewUser(<?php echo $user->ID; ?>)" title="<?php esc_attr_e('View', 'maneli-car-inquiry'); ?>">
                                                            <i class="la la-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-info-light" onclick="editUser(<?php echo $user->ID; ?>)" title="<?php esc_attr_e('Edit', 'maneli-car-inquiry'); ?>">
                                                            <i class="la la-edit"></i>
                                                        </button>
                                                        <?php if (!empty($mobile_number)): ?>
                                                        <button class="btn btn-sm btn-success-light send-sms-btn" 
                                                                data-user-id="<?php echo esc_attr($user->ID); ?>" 
                                                                data-phone="<?php echo esc_attr($mobile_number); ?>"
                                                                data-user-name="<?php echo esc_attr($user->display_name); ?>"
                                                                title="<?php esc_attr_e('Send SMS', 'maneli-car-inquiry'); ?>">
                                                            <i class="la la-sms"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                        <button class="btn btn-sm btn-danger-light" onclick="deleteUser(<?php echo $user->ID; ?>)" title="<?php esc_attr_e('Delete', 'maneli-car-inquiry'); ?>">
                                                            <i class="la la-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <div class="alert alert-info mb-0">
                                                    <i class="la la-info-circle me-2"></i>
                                                    کاربری یافت نشد.
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="card-footer border-top-0">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <?php printf(esc_html__('Showing %s items', 'maneli-car-inquiry'), persian_numbers(number_format_i18n(count($users)))); ?> <i class="bi bi-arrow-left ms-2 fw-medium"></i>
                                    </div>
                                    <div class="ms-auto">
                                        <nav aria-label="Page navigation" class="pagination-style-4">
                                            <ul class="pagination mb-0">
                                        <?php
                                        // Prepare base URL with filters
                                        $base_url = remove_query_arg('paged');
                                        
                                        // Previous button
                                        $prev_link = $paged > 1 ? add_query_arg('paged', $paged - 1, $base_url) : 'javascript:void(0);';
                                        $prev_disabled = $paged <= 1 ? ' disabled' : '';
                                        echo '<li class="page-item' . $prev_disabled . '">';
                                        echo '<a class="page-link" href="' . esc_url($prev_link) . '">' . esc_html__('Previous', 'maneli-car-inquiry') . '</a>';
                                        echo '</li>';
                                        
                                        // Page numbers
                                        for ($i = 1; $i <= $total_pages; $i++) {
                                            $current_class = ($i == $paged) ? ' active' : '';
                                            $page_link = add_query_arg('paged', $i, $base_url);
                                            echo '<li class="page-item' . $current_class . '">';
                                            echo '<a class="page-link" href="' . esc_url($page_link) . '">' . persian_numbers($i) . '</a>';
                                            echo '</li>';
                                        }
                                        
                                        // Next button
                                        $next_link = $paged < $total_pages ? add_query_arg('paged', $paged + 1, $base_url) : 'javascript:void(0);';
                                        $next_disabled = $paged >= $total_pages ? ' disabled' : '';
                                        echo '<li class="page-item' . $next_disabled . '">';
                                        echo '<a class="page-link text-primary" href="' . esc_url($next_link) . '">' . esc_html__('Next', 'maneli-car-inquiry') . '</a>';
                                        echo '</li>';
                                        ?>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row -->
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary-transparent">
                <h5 class="modal-title">
                    <i class="la la-user-plus me-2"></i>
                    افزودن کاربر جدید
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-user-form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">نام *</label>
                            <input type="text" class="form-control" id="new-first-name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">نام خانوادگی *</label>
                            <input type="text" class="form-control" id="new-last-name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">شماره موبایل *</label>
                            <input type="tel" class="form-control" id="new-mobile" placeholder="09123456789" required>
                            <small class="text-muted">رمز عبور به صورت خودکار ساخته می‌شود و کاربر می‌تواند از طریق فراموشی رمز عبور، رمز خود را تنظیم کند.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">نقش *</label>
                            <select class="form-select" id="new-role" required>
                                <option value="customer"><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></option>
                                <option value="maneli_expert"><?php esc_html_e('Expert', 'maneli-car-inquiry'); ?></option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">
                    <i class="la la-save me-1"></i>
                    <?php esc_html_e('Save', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var maneliAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';

// Localized strings
const userTranslations = {
    error: <?php echo wp_json_encode(esc_html__('Error!', 'maneli-car-inquiry')); ?>,
    fillRequiredFields: <?php echo wp_json_encode(esc_html__('Please fill all required fields.', 'maneli-car-inquiry')); ?>,
    saving: <?php echo wp_json_encode(esc_html__('Saving...', 'maneli-car-inquiry')); ?>,
    success: <?php echo wp_json_encode(esc_html__('Success!', 'maneli-car-inquiry')); ?>,
    userAdded: <?php echo wp_json_encode(esc_html__('User added successfully.', 'maneli-car-inquiry')); ?>,
    addUserError: <?php echo wp_json_encode(esc_html__('Error adding user', 'maneli-car-inquiry')); ?>,
    serverError: <?php echo wp_json_encode(esc_html__('Server connection error. Please try again.', 'maneli-car-inquiry')); ?>,
    deleteUser: <?php echo wp_json_encode(esc_html__('Delete User?', 'maneli-car-inquiry')); ?>,
    deleteIrreversible: <?php echo wp_json_encode(esc_html__('This action cannot be undone!', 'maneli-car-inquiry')); ?>,
    confirmDelete: <?php echo wp_json_encode(esc_html__('Yes, Delete', 'maneli-car-inquiry')); ?>,
    cancel: <?php echo wp_json_encode(esc_html__('Cancel', 'maneli-car-inquiry')); ?>,
    deleting: <?php echo wp_json_encode(esc_html__('Deleting...', 'maneli-car-inquiry')); ?>,
    deleted: <?php echo wp_json_encode(esc_html__('Deleted!', 'maneli-car-inquiry')); ?>,
    userDeleted: <?php echo wp_json_encode(esc_html__('User deleted successfully.', 'maneli-car-inquiry')); ?>,
    deleteUserError: <?php echo wp_json_encode(esc_html__('Error deleting user', 'maneli-car-inquiry')); ?>
};

// Helper function to generate email from mobile
function generateEmailFromMobile(mobile) {
    return mobile + '@manelikhodro.com';
}

// Generate random password
function generateRandomPassword(length = 12) {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < length; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return password;
}

// View User Details - Redirect to user detail page
function viewUser(userId) {
    const url = '<?php echo esc_url(home_url('/dashboard/users')); ?>?view_user=' + userId;
    window.location.href = url;
}

// Edit User - Redirect to user detail page with edit mode
function editUser(userId) {
    const url = '<?php echo esc_url(home_url('/dashboard/users')); ?>?edit_user=' + userId;
    window.location.href = url;
}

function saveUser() {
    const firstName = jQuery('#new-first-name').val();
    const lastName = jQuery('#new-last-name').val();
    const mobile = jQuery('#new-mobile').val();
    const role = jQuery('#new-role').val();
    
    if (!firstName || !lastName || !mobile) {
        Swal.fire({
            icon: 'error',
            title: userTranslations.error,
            text: userTranslations.fillRequiredFields
        });
        return;
    }
    
    // Generate email and password from mobile
    const email = generateEmailFromMobile(mobile);
    const password = generateRandomPassword();
    
    Swal.fire({
        title: userTranslations.saving,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    jQuery.ajax({
        url: maneliAjaxUrl,
        type: 'POST',
        data: {
            action: 'maneli_add_user',
            first_name: firstName,
            last_name: lastName,
            display_name: firstName + ' ' + lastName,
            mobile_number: mobile,
            role: role,
            password: password,
            email: email,
            nonce: '<?php echo wp_create_nonce('maneli_add_user_nonce'); ?>'
        },
        success: function(response) {
            Swal.close();
            if (response.success) {
                Swal.fire(userTranslations.success, userTranslations.userAdded, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire(userTranslations.error, response.data?.message || userTranslations.addUserError, 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('AJAX Error:', error, xhr.responseText);
            Swal.fire(userTranslations.error, userTranslations.serverError, 'error');
        }
    });
}

function deleteUser(userId) {
    Swal.fire({
        title: userTranslations.deleteUser,
        text: userTranslations.deleteIrreversible,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: userTranslations.confirmDelete,
        cancelButtonText: userTranslations.cancel,
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: userTranslations.deleting,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            jQuery.ajax({
                url: maneliAjaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_delete_user',
                    user_id: userId,
                    nonce: '<?php echo wp_create_nonce('maneli_delete_user_nonce'); ?>'
                },
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        Swal.fire(userTranslations.deleted, userTranslations.userDeleted, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire(userTranslations.error, response.data?.message || userTranslations.deleteUserError, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error('AJAX Error:', error, xhr.responseText);
                    Swal.fire(userTranslations.error, userTranslations.serverError, 'error');
                }
            });
        }
    });
}

// Send SMS handler
jQuery(document).ready(function($) {
    $('.send-sms-btn').on('click', function() {
        var phone = $(this).data('phone');
        var userName = $(this).data('user-name');
        var userId = $(this).data('user-id');
        
        Swal.fire({
            title: '<?php echo esc_js(__('Send SMS', 'maneli-car-inquiry')); ?>',
            html: `
                <div class="text-start">
                    <p><strong><?php echo esc_js(__('Recipient:', 'maneli-car-inquiry')); ?></strong> ${userName} (${phone})</p>
                    <div class="mb-3">
                        <label class="form-label"><?php echo esc_js(__('Message:', 'maneli-car-inquiry')); ?></label>
                        <textarea id="sms-message" class="form-control" rows="5" placeholder="<?php echo esc_js(__('Enter your message...', 'maneli-car-inquiry')); ?>"></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<?php echo esc_js(__('Send', 'maneli-car-inquiry')); ?>',
            cancelButtonText: '<?php echo esc_js(__('Cancel', 'maneli-car-inquiry')); ?>',
            preConfirm: function() {
                var message = $('#sms-message').val();
                if (!message.trim()) {
                    Swal.showValidationMessage('<?php echo esc_js(__('Please enter a message', 'maneli-car-inquiry')); ?>');
                    return false;
                }
                return { message: message };
            }
        }).then(function(result) {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    title: '<?php echo esc_js(__('Sending...', 'maneli-car-inquiry')); ?>',
                    text: '<?php echo esc_js(__('Please wait', 'maneli-car-inquiry')); ?>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function() {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: maneli_ajax.url,
                    type: 'POST',
                    data: {
                        action: 'maneli_send_single_sms',
                        nonce: maneli_ajax.nonce,
                        recipient: phone,
                        message: result.value.message,
                        related_id: userId
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            Swal.fire('<?php echo esc_js(__('Success', 'maneli-car-inquiry')); ?>', '<?php echo esc_js(__('SMS sent successfully!', 'maneli-car-inquiry')); ?>', 'success');
                        } else {
                            Swal.fire('<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>', response.data?.message || '<?php echo esc_js(__('Failed to send SMS', 'maneli-car-inquiry')); ?>', 'error');
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire('<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>', '<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>', 'error');
                    }
                });
            }
        });
    });
});
</script>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(var(--primary-rgb), 0.03);
    transform: scale(1.01);
    transition: all 0.3s ease;
}

.avatar-rounded img {
    border-radius: 50%;
}
</style>
