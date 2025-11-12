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
                <style>
                /* User Statistics Cards - Matching CRM style */
                .card.custom-card.crm-card {
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                    border: 1px solid rgba(0, 0, 0, 0.06) !important;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
                    position: relative !important;
                    overflow: hidden !important;
                    border-radius: 0.5rem !important;
                    background: #fff !important;
                }
                [data-theme-mode=dark] .card.custom-card.crm-card {
                    background: rgb(25, 25, 28) !important;
                    border: 1px solid rgba(255, 255, 255, 0.1) !important;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
                    color: rgba(255, 255, 255, 0.9) !important;
                }
                .card.custom-card.crm-card::before {
                    content: '' !important;
                    position: absolute !important;
                    top: 0 !important;
                    right: 0 !important;
                    width: 100% !important;
                    height: 100% !important;
                    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%) !important;
                    pointer-events: none !important;
                    transition: opacity 0.3s ease !important;
                    opacity: 0 !important;
                }
                .card.custom-card.crm-card:hover {
                    transform: translateY(-4px) !important;
                    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12) !important;
                    border-color: rgba(0, 0, 0, 0.1) !important;
                }
                .card.custom-card.crm-card:hover::before {
                    opacity: 1 !important;
                }
                .card.custom-card.crm-card .card-body {
                    position: relative !important;
                    z-index: 1 !important;
                    padding: 1.5rem !important;
                }
                .card.custom-card.crm-card:hover .p-2 {
                    transform: scale(1.1) !important;
                }
                .card.custom-card.crm-card:hover .avatar {
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
                }
                .card.custom-card.crm-card h4 {
                    font-weight: 700 !important;
                    letter-spacing: -0.5px !important;
                    font-size: 1.75rem !important;
                    color: #1f2937 !important;
                    transition: color 0.3s ease !important;
                }
                [data-theme-mode=dark] .card.custom-card.crm-card h4 {
                    color: rgba(255, 255, 255, 0.9) !important;
                }
                .card.custom-card.crm-card:hover h4 {
                    color: #5e72e4 !important;
                }
                [data-theme-mode=dark] .card.custom-card.crm-card:hover h4 {
                    color: var(--primary-color) !important;
                }
                [data-theme-mode=dark] .card.custom-card.crm-card .text-muted,
                [data-theme-mode=dark] .card.custom-card.crm-card p {
                    color: rgba(255, 255, 255, 0.6) !important;
                }
                </style>
                <div class="row mb-4">
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
                        <div class="card custom-card crm-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                                        <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                            <i class="la la-users fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Users', 'maneli-car-inquiry'); ?></p>
                                <div class="d-flex align-items-center justify-content-between mt-1">
                                    <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($total_all_users) : persian_numbers(number_format_i18n($total_all_users)); ?></h4>
                                    <span class="badge bg-primary-transparent rounded-pill fs-11"><?php esc_html_e('Total Users', 'maneli-car-inquiry'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
                        <div class="card custom-card crm-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="p-2 border border-success border-opacity-10 bg-success-transparent rounded-pill">
                                        <span class="avatar avatar-md avatar-rounded bg-success svg-white">
                                            <i class="la la-user-shield fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Managers', 'maneli-car-inquiry'); ?></p>
                                <div class="d-flex align-items-center justify-content-between mt-1">
                                    <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($admin_count) : persian_numbers(number_format_i18n($admin_count)); ?></h4>
                                    <span class="badge bg-success-transparent rounded-pill fs-11"><?php esc_html_e('Managers', 'maneli-car-inquiry'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
                        <div class="card custom-card crm-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="p-2 border border-info border-opacity-10 bg-info-transparent rounded-pill">
                                        <span class="avatar avatar-md avatar-rounded bg-info svg-white">
                                            <i class="la la-user-tie fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Experts', 'maneli-car-inquiry'); ?></p>
                                <div class="d-flex align-items-center justify-content-between mt-1">
                                    <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($expert_count) : persian_numbers(number_format_i18n($expert_count)); ?></h4>
                                    <span class="badge bg-info-transparent rounded-pill fs-11"><?php esc_html_e('Experts', 'maneli-car-inquiry'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
                        <div class="card custom-card crm-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="p-2 border border-warning border-opacity-10 bg-warning-transparent rounded-pill">
                                        <span class="avatar avatar-md avatar-rounded bg-warning svg-white">
                                            <i class="la la-user fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Customers', 'maneli-car-inquiry'); ?></p>
                                <div class="d-flex align-items-center justify-content-between mt-1">
                                    <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($customer_count) : persian_numbers(number_format_i18n($customer_count)); ?></h4>
                                    <span class="badge bg-warning-transparent rounded-pill fs-11"><?php esc_html_e('Customers', 'maneli-car-inquiry'); ?></span>
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
                            <?php esc_html_e('User Management', 'maneli-car-inquiry'); ?>
                        </div>
                        <div class="btn-list">
                            <button class="btn btn-primary btn-wave" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="la la-plus me-1"></i>
                                <?php esc_html_e('New User', 'maneli-car-inquiry'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="maneli-mobile-filter mb-3" data-maneli-mobile-filter>
                            <button
                                type="button"
                                class="maneli-mobile-filter-toggle-btn d-flex align-items-center justify-content-between w-100 d-md-none"
                                data-maneli-filter-toggle
                                aria-expanded="false"
                            >
                                <span class="fw-semibold"><?php esc_html_e('Filters', 'maneli-car-inquiry'); ?></span>
                                <i class="ri-arrow-down-s-line maneli-mobile-filter-arrow"></i>
                            </button>
                            <div class="maneli-mobile-filter-body" data-maneli-filter-body>
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
                            </div>
                        </div>

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
                                            $user_roles = is_array($user->roles) ? $user->roles : [];
                                            $primary_role = !empty($user_roles) && isset($user_roles[0]) ? $user_roles[0] : '';
                                            $role_display = !empty($primary_role) ? ucfirst($primary_role) : esc_html__('No Role', 'maneli-car-inquiry');
                                            
                                            // Role names
                                            $role_labels = [
                                                'administrator' => esc_html__('Administrator', 'maneli-car-inquiry'),
                                                'maneli_admin' => esc_html__('Maneli Admin', 'maneli-car-inquiry'),
                                                'maneli_expert' => esc_html__('Expert', 'maneli-car-inquiry'),
                                                'customer' => esc_html__('Customer', 'maneli-car-inquiry'),
                                                'subscriber' => esc_html__('Subscriber', 'maneli-car-inquiry')
                                            ];
                                            $role_display = !empty($primary_role) && isset($role_labels[$primary_role]) ? $role_labels[$primary_role] : $role_display;
                                            
                                            // Role badge color
                                            $role_colors = [
                                                'administrator' => 'success',
                                                'maneli_admin' => 'success',
                                                'maneli_expert' => 'info',
                                                'customer' => 'warning',
                                                'subscriber' => 'secondary'
                                            ];
                                            $badge_color = !empty($primary_role) && isset($role_colors[$primary_role]) ? $role_colors[$primary_role] : 'secondary';
                                            
                                            $mobile_number = get_user_meta($user->ID, 'mobile_number', true);
                                            if (empty($mobile_number)) {
                                                $mobile_number = $user->user_login; // Use username as fallback
                                            }
                                        ?>
                                            <tr data-role="<?php echo esc_attr($primary_role); ?>">
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
                                                        <button class="btn btn-sm btn-info-light view-sms-history-btn" 
                                                                data-user-id="<?php echo esc_attr($user->ID); ?>" 
                                                                data-phone="<?php echo esc_attr($mobile_number); ?>"
                                                                title="<?php esc_attr_e('SMS History', 'maneli-car-inquiry'); ?>">
                                                            <i class="la la-history"></i>
                                                        </button>
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
                    <?php esc_html_e('Add New User', 'maneli-car-inquiry'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-user-form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="new-first-name">
                                <?php esc_html_e('First Name', 'maneli-car-inquiry'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="new-first-name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="new-last-name">
                                <?php esc_html_e('Last Name', 'maneli-car-inquiry'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="new-last-name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="new-mobile">
                                <?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="tel" class="form-control" id="new-mobile" placeholder="<?php esc_attr_e('Enter mobile number', 'maneli-car-inquiry'); ?>" required>
                            <small class="text-muted">
                                <?php esc_html_e("Password is generated automatically. The user can reset it via \"Forgot Password\".", 'maneli-car-inquiry'); ?>
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="new-role">
                                <?php esc_html_e('Role', 'maneli-car-inquiry'); ?>
                                <span class="text-danger">*</span>
                            </label>
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
var maneliAjaxNonce = '<?php echo wp_create_nonce('maneli-ajax-nonce'); ?>';

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
    if (typeof jQuery === 'undefined') {
        alert('<?php echo esc_js(__('Please wait for the page to fully load.', 'maneli-car-inquiry')); ?>');
        return;
    }
    
    const $ = jQuery;
    const firstName = $('#new-first-name').val();
    const lastName = $('#new-last-name').val();
    const mobile = $('#new-mobile').val();
    const role = $('#new-role').val();
    
    if (!firstName || !lastName || !mobile) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: userTranslations.error,
                text: userTranslations.fillRequiredFields
            });
        } else {
            alert(userTranslations.fillRequiredFields);
        }
        return;
    }
    
    // Generate email and password from mobile
    const email = generateEmailFromMobile(mobile);
    const password = generateRandomPassword();
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: userTranslations.saving,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
    
    $.ajax({
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
            if (typeof Swal !== 'undefined') {
                Swal.close();
                if (response.success) {
                    Swal.fire(userTranslations.success, userTranslations.userAdded, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire(userTranslations.error, response.data?.message || userTranslations.addUserError, 'error');
                }
            } else {
                if (response.success) {
                    alert(userTranslations.userAdded);
                    location.reload();
                } else {
                    alert(response.data?.message || userTranslations.addUserError);
                }
            }
        },
        error: function(xhr, status, error) {
            if (typeof Swal !== 'undefined') {
                Swal.close();
                Swal.fire(userTranslations.error, userTranslations.serverError, 'error');
            } else {
                alert(userTranslations.serverError);
            }
            console.error('AJAX Error:', error, xhr.responseText);
        }
    });
}

function deleteUser(userId) {
    if (typeof jQuery === 'undefined' || typeof Swal === 'undefined') {
        if (confirm(userTranslations.deleteUser + '\n' + userTranslations.deleteIrreversible)) {
            // Fallback without jQuery/Swal
            if (typeof jQuery !== 'undefined') {
                const $ = jQuery;
                $.ajax({
                    url: maneliAjaxUrl,
                    type: 'POST',
                    data: {
                        action: 'maneli_delete_user',
                        user_id: userId,
                        nonce: '<?php echo wp_create_nonce('maneli_delete_user_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(userTranslations.userDeleted);
                            location.reload();
                        } else {
                            alert(response.data?.message || userTranslations.deleteUserError);
                        }
                    },
                    error: function() {
                        alert(userTranslations.serverError);
                    }
                });
            } else {
                alert('<?php echo esc_js(__('Please wait for the page to fully load.', 'maneli-car-inquiry')); ?>');
            }
        }
        return;
    }
    
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

// Send SMS handler - Wait for jQuery to load
(function() {
    function initSMSHandler() {
        if (typeof jQuery === 'undefined') {
            // Wait for jQuery to load
            setTimeout(initSMSHandler, 100);
            return;
        }
        
        var $ = jQuery;
        
        // Wait for DOM to be ready
        $(document).ready(function() {
            $(document).on('click', '.send-sms-btn', function() {
                var phone = $(this).data('phone');
                var userName = $(this).data('user-name');
                var userId = $(this).data('user-id');
                
                if (typeof Swal === 'undefined') {
                    console.error('SweetAlert2 is not loaded');
                    alert('<?php echo esc_js(__('Please refresh the page and try again.', 'maneli-car-inquiry')); ?>');
                    return;
                }
                
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
                            url: maneliAjaxUrl,
                            type: 'POST',
                            data: {
                                action: 'maneli_send_single_sms',
                                nonce: maneliAjaxNonce,
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
    }
    
    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSMSHandler);
    } else {
        initSMSHandler();
    }
})();

// SMS History handler - Wait for jQuery to load
(function() {
    function initSMSHistoryHandler() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initSMSHistoryHandler, 100);
            return;
        }
        
        var $ = jQuery;
        
        $(document).ready(function() {
            $(document).on('click', '.view-sms-history-btn', function() {
                var userId = $(this).data('user-id');
                var phone = $(this).data('phone');
                
                if (!userId) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>',
                            text: '<?php echo esc_js(__('Invalid user ID.', 'maneli-car-inquiry')); ?>',
                            icon: 'error'
                        });
                    }
                    return;
                }
                
                const modalElement = document.getElementById('sms-history-modal');
                if (!modalElement) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>',
                            text: '<?php echo esc_js(__('SMS history modal not found.', 'maneli-car-inquiry')); ?>',
                            icon: 'error'
                        });
                    }
                    return;
                }
                
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                } else if (typeof jQuery !== 'undefined' && jQuery(modalElement).modal) {
                    jQuery(modalElement).modal('show');
                } else {
                    jQuery(modalElement).addClass('show').css('display', 'block');
                    jQuery('.modal-backdrop').remove();
                    jQuery('body').append('<div class="modal-backdrop fade show"></div>');
                }
                
                $('#sms-history-loading').removeClass('maneli-initially-hidden').show();
                $('#sms-history-content').addClass('maneli-initially-hidden').hide();
                $('#sms-history-table-container').empty();
                
                if (!maneliAjaxNonce) {
                    $('#sms-history-loading').addClass('maneli-initially-hidden').hide();
                    $('#sms-history-content').removeClass('maneli-initially-hidden').show();
                    $('#sms-history-table-container').html(
                        '<div class="alert alert-danger">' +
                        '<i class="la la-exclamation-triangle me-2"></i>' +
                        '<?php echo esc_js(__('Nonce is missing. Please refresh the page and try again.', 'maneli-car-inquiry')); ?>' +
                        '</div>'
                    );
                    return;
                }
                
                $.ajax({
                    url: maneliAjaxUrl,
                    type: 'POST',
                    data: {
                        action: 'maneli_get_sms_history',
                        nonce: maneliAjaxNonce,
                        user_id: userId
                    },
                    success: function(response) {
                        $('#sms-history-loading').addClass('maneli-initially-hidden').hide();
                        $('#sms-history-content').removeClass('maneli-initially-hidden').show();
                        
                        if (response && response.success && response.data && response.data.html) {
                            $('#sms-history-table-container').html(response.data.html);
                        } else {
                            $('#sms-history-table-container').html(
                                '<div class="alert alert-info">' +
                                '<i class="la la-info-circle me-2"></i>' +
                                '<?php echo esc_js(__('No SMS messages have been sent to this user yet.', 'maneli-car-inquiry')); ?>' +
                                '</div>'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#sms-history-loading').addClass('maneli-initially-hidden').hide();
                        $('#sms-history-content').removeClass('maneli-initially-hidden').show();
                        $('#sms-history-table-container').html(
                            '<div class="alert alert-danger">' +
                            '<i class="la la-exclamation-triangle me-2"></i>' +
                            '<?php echo esc_js(__('Error loading SMS history.', 'maneli-car-inquiry')); ?>' +
                            '</div>'
                        );
                    }
                });
            });
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSMSHistoryHandler);
    } else {
        initSMSHistoryHandler();
    }
})();
</script>

<?php
// Include SMS History Modal (shared template)
maneli_get_template_part('partials/sms-history-modal');
?>

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
