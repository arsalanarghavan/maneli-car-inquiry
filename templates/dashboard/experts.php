<!-- Start::row -->
<?php
/**
 * Experts Management Page
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

// Check if viewing or editing a specific expert
$view_expert_id = isset($_GET['view_expert']) ? intval($_GET['view_expert']) : 0;
$edit_expert_id = isset($_GET['edit_expert']) ? intval($_GET['edit_expert']) : 0;
$detail_expert_id = $view_expert_id ?: $edit_expert_id;

if ($detail_expert_id) {
    // Load expert detail page
    $expert_detail_file = MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/expert-detail.php';
    if (file_exists($expert_detail_file)) {
        include $expert_detail_file;
        return;
    }
}

// Get current page for pagination
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Statistics for experts
global $wpdb;

// Total experts
$total_experts = count(get_users(['role' => 'maneli_expert']));

// Active/Inactive experts
$active_experts = 0;
$inactive_experts = 0;
$all_experts_list = get_users(['role' => 'maneli_expert']);
foreach ($all_experts_list as $exp) {
    $is_active = get_user_meta($exp->ID, 'expert_active', true) !== 'no';
    if ($is_active) {
        $active_experts++;
    } else {
        $inactive_experts++;
    }
}

// Get experts with pagination
$user_query_args = [
    'role' => 'maneli_expert',
    'number' => $per_page,
    'offset' => ($paged - 1) * $per_page,
    'orderby' => 'display_name',
    'order' => 'ASC',
];

if (!empty($search)) {
    $user_query_args['search'] = '*' . $search . '*';
    $user_query_args['search_columns'] = ['display_name', 'user_login'];
}

$user_query = new WP_User_Query($user_query_args);
$experts = $user_query->get_results();
$total_experts_filtered = $user_query->get_total();
$total_pages = ceil($total_experts_filtered / $per_page);

// Filter by status if needed
if (!empty($status_filter)) {
    $filtered_experts = [];
    foreach ($experts as $expert) {
        $is_active = get_user_meta($expert->ID, 'expert_active', true) !== 'no';
        if (($status_filter === 'active' && $is_active) || ($status_filter === 'inactive' && !$is_active)) {
            $filtered_experts[] = $expert;
        }
    }
    $experts = $filtered_experts;
}

// Get mobile numbers and inquiry counts for experts
foreach ($experts as $expert) {
    $expert->mobile_number = get_user_meta($expert->ID, 'mobile_number', true);
    
    // Count installment inquiries
    $expert->installment_count = count(get_posts([
        'post_type' => 'inquiry',
        'post_status' => 'any',
        'meta_query' => [
            [
                'key' => 'assigned_expert_id',
                'value' => $expert->ID,
                'compare' => '='
            ]
        ],
        'posts_per_page' => -1
    ]));
    
    // Count cash inquiries
    $expert->cash_count = count(get_posts([
        'post_type' => 'cash_inquiry',
        'post_status' => 'any',
        'meta_query' => [
            [
                'key' => 'assigned_expert_id',
                'value' => $expert->ID,
                'compare' => '='
            ]
        ],
        'posts_per_page' => -1
    ]));
}

// Experts with inquiries
$experts_with_inquiries = $wpdb->get_var("
    SELECT COUNT(DISTINCT meta_value)
    FROM {$wpdb->postmeta}
    WHERE meta_key = 'assigned_expert_id'
    AND meta_value != ''
");

// Total inquiries assigned to experts
$total_assigned = $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type IN ('inquiry', 'cash_inquiry')
    AND p.post_status = 'publish'
    AND pm.meta_key = 'assigned_expert_id'
    AND pm.meta_value != ''
");

// Completed by experts
$completed_by_experts = $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
    INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id
    WHERE p.post_type = 'inquiry'
    AND pm_status.meta_key = 'tracking_status'
    AND pm_status.meta_value = 'completed'
    AND pm_expert.meta_key = 'assigned_expert_id'
    AND pm_expert.meta_value != ''
");

$cash_completed_by_experts = $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
    INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id
    WHERE p.post_type = 'cash_inquiry'
    AND pm_status.meta_key = 'cash_inquiry_status'
    AND pm_status.meta_value = 'completed'
    AND pm_expert.meta_key = 'assigned_expert_id'
    AND pm_expert.meta_value != ''
");

$total_completed = $completed_by_experts + $cash_completed_by_experts;

// In progress
$in_progress_count = $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type IN ('inquiry', 'cash_inquiry')
    AND p.post_status = 'publish'
    AND pm.meta_key IN ('tracking_status', 'cash_inquiry_status')
    AND pm.meta_value = 'in_progress'
    AND p.ID IN (
        SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = 'assigned_expert_id' AND meta_value != ''
    )
");

// Today's assignments
$today_assigned = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type IN ('inquiry', 'cash_inquiry')
    AND pm.meta_key = 'assigned_expert_id'
    AND pm.meta_value != ''
    AND p.post_date >= %s
    AND p.post_date <= %s
", date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')));
?>
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Pages', 'maneli-car-inquiry'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Expert Management', 'maneli-car-inquiry'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Expert Management', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- Page Header Close -->

        <!-- Start::row -->
<div class="row">
    <div class="col-xl-12">
        <!-- Statistics Cards -->
                <style>
                /* Expert Statistics Cards - Matching CRM style */
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
                <div class="row mb-4 maneli-mobile-card-scroll">
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
                        <div class="card custom-card crm-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                                        <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                            <i class="la la-user-tie fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Experts', 'maneli-car-inquiry'); ?></p>
                                <div class="d-flex align-items-center justify-content-between mt-1">
                                    <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($total_experts) : persian_numbers(number_format_i18n($total_experts)); ?></h4>
                                    <span class="badge bg-primary-transparent rounded-pill fs-11"><?php esc_html_e('Total Experts', 'maneli-car-inquiry'); ?></span>
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
                                            <i class="la la-user-check fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Active', 'maneli-car-inquiry'); ?></p>
                                <div class="d-flex align-items-center justify-content-between mt-1">
                                    <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($active_experts) : persian_numbers(number_format_i18n($active_experts)); ?></h4>
                                    <span class="badge bg-success-transparent rounded-pill fs-11"><?php esc_html_e('Active', 'maneli-car-inquiry'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
            
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
                        <div class="card custom-card crm-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="p-2 border border-danger border-opacity-10 bg-danger-transparent rounded-pill">
                                        <span class="avatar avatar-md avatar-rounded bg-danger svg-white">
                                            <i class="la la-user-slash fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Inactive', 'maneli-car-inquiry'); ?></p>
                                <div class="d-flex align-items-center justify-content-between mt-1">
                                    <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($inactive_experts) : persian_numbers(number_format_i18n($inactive_experts)); ?></h4>
                                    <span class="badge bg-danger-transparent rounded-pill fs-11"><?php esc_html_e('Inactive', 'maneli-car-inquiry'); ?></span>
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
                                            <i class="la la-tasks fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Referrals', 'maneli-car-inquiry'); ?></p>
                                <div class="d-flex align-items-center justify-content-between mt-1">
                                    <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($total_assigned) : persian_numbers(number_format_i18n($total_assigned)); ?></h4>
                                    <span class="badge bg-info-transparent rounded-pill fs-11"><?php esc_html_e('Total Referrals', 'maneli-car-inquiry'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                <!-- Main Card -->
                <div class="card custom-card overflow-hidden">
                    <div class="card-header d-flex justify-content-between align-items-center border-block-end">
                <div class="card-title">
                    <i class="la la-user-tie me-2"></i>
                    <?php esc_html_e('Expert Management', 'maneli-car-inquiry'); ?>
                </div>
                        <div class="btn-list">
                            <button class="btn btn-primary btn-wave" data-bs-toggle="modal" data-bs-target="#addExpertModal">
                        <i class="la la-user-plus me-1"></i>
                        <?php esc_html_e('Add New Expert', 'maneli-car-inquiry'); ?>
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
                                            if (!in_array($key, ['search', 'status', 'page'])) {
                                                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                                            }
                                        }
                                        ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                                    <select class="form-select" name="status">
                            <option value=""><?php esc_html_e('All Statuses', 'maneli-car-inquiry'); ?></option>
                                        <option value="active" <?php selected($status_filter, 'active'); ?>><?php esc_html_e('Active', 'maneli-car-inquiry'); ?></option>
                                        <option value="inactive" <?php selected($status_filter, 'inactive'); ?>><?php esc_html_e('Inactive', 'maneli-car-inquiry'); ?></option>
                        </select>
                    </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary btn-wave">
                                        <i class="la la-search me-1"></i>
                                        <?php esc_html_e('Search', 'maneli-car-inquiry'); ?>
                                    </button>
                                    <?php if (!empty($search) || !empty($status_filter)): ?>
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
                                        <th><?php esc_html_e('Expert Name', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Mobile', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Installment Inquiries', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Cash Inquiries', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                                    <?php if (!empty($experts)): ?>
                                        <?php foreach ($experts as $expert): 
                                            $mobile_number = get_user_meta($expert->ID, 'mobile_number', true);
                                            if (empty($mobile_number)) {
                                                $mobile_number = $expert->user_login; // Use username as fallback
                                            }
                                            
                                    $is_active = get_user_meta($expert->ID, 'expert_active', true) !== 'no';
                                            $installment_count = isset($expert->installment_count) ? $expert->installment_count : 0;
                                            $cash_count = isset($expert->cash_count) ? $expert->cash_count : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm avatar-rounded me-2">
                                                            <?php echo get_avatar($expert->ID, 32); ?>
                                                        </div>
                                                        <div>
                                                            <span class="fw-medium d-block"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($expert->display_name)) : esc_html($expert->display_name); ?></span>
                                                            <small class="text-muted"><?php esc_html_e('Expert', 'maneli-car-inquiry'); ?></small>
                                                        </div>
                                            </div>
                                        </td>
                                                <td>
                                                    <a href="tel:<?php echo esc_attr($mobile_number); ?>" class="fw-medium text-primary text-decoration-none">
                                                        <?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($mobile_number)) : esc_html($mobile_number); ?>
                                                    </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-transparent">
                                                        <?php echo persian_numbers(number_format_i18n($installment_count)); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning-transparent">
                                                        <?php echo persian_numbers(number_format_i18n($cash_count)); ?>
                                            </span>
                                        </td>
                                        <td>
                                                    <?php if ($is_active): ?>
                                                <span class="badge bg-success"><?php esc_html_e('Active', 'maneli-car-inquiry'); ?></span>
                                                    <?php else: ?>
                                                <span class="badge bg-danger"><?php esc_html_e('Inactive', 'maneli-car-inquiry'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                                    <div class="btn-list">
                                                        <button class="btn btn-sm btn-primary-light" onclick="viewExpert(<?php echo $expert->ID; ?>)" title="<?php esc_attr_e('View', 'maneli-car-inquiry'); ?>">
                                                            <i class="la la-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-info-light" onclick="editExpert(<?php echo $expert->ID; ?>)" title="<?php esc_attr_e('Edit', 'maneli-car-inquiry'); ?>">
                                                            <i class="la la-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-secondary-light" onclick="editExpertPermissions(<?php echo $expert->ID; ?>)" title="<?php esc_attr_e('Permission Level', 'maneli-car-inquiry'); ?>">
                                                            <i class="la la-key"></i>
                                                </button>
                                                        <button class="btn btn-sm btn-<?php echo $is_active ? 'warning' : 'success'; ?>-light" onclick="toggleExpertStatus(<?php echo $expert->ID; ?>, <?php echo $is_active ? 'false' : 'true'; ?>)" title="<?php echo $is_active ? esc_attr__('Deactivate', 'maneli-car-inquiry') : esc_attr__('Activate', 'maneli-car-inquiry'); ?>">
                                                    <i class="la la-toggle-<?php echo $is_active ? 'on' : 'off'; ?>"></i>
                                                </button>
                                                        <button class="btn btn-sm btn-danger-light" onclick="deleteExpert(<?php echo $expert->ID; ?>)" title="<?php esc_attr_e('Delete', 'maneli-car-inquiry'); ?>">
                                                            <i class="la la-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">
                                        <div class="alert alert-info mb-0">
                                            <i class="la la-info-circle me-2"></i>
                                                    <?php esc_html_e('No experts found.', 'maneli-car-inquiry'); ?>
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
                                        <?php printf(esc_html__('Showing %s items', 'maneli-car-inquiry'), persian_numbers(number_format_i18n(count($experts)))); ?> <i class="bi bi-arrow-left ms-2 fw-medium"></i>
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
                                                
                                                // Page numbers - show limited pages
                                                $start_page = max(1, $paged - 2);
                                                $end_page = min($total_pages, $paged + 2);
                                                
                                                if ($start_page > 1) {
                                                    echo '<li class="page-item">';
                                                    echo '<a class="page-link" href="' . esc_url(add_query_arg('paged', 1, $base_url)) . '">' . persian_numbers('1') . '</a>';
                                                    echo '</li>';
                                                    if ($start_page > 2) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                }
                                                
                                                for ($i = $start_page; $i <= $end_page; $i++) {
                                                    $current_class = ($i == $paged) ? ' active' : '';
                                                    $page_link = add_query_arg('paged', $i, $base_url);
                                                    echo '<li class="page-item' . $current_class . '">';
                                                    echo '<a class="page-link" href="' . esc_url($page_link) . '">' . persian_numbers($i) . '</a>';
                                                    echo '</li>';
                                                }
                                                
                                                if ($end_page < $total_pages) {
                                                    if ($end_page < $total_pages - 1) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                    echo '<li class="page-item">';
                                                    echo '<a class="page-link" href="' . esc_url(add_query_arg('paged', $total_pages, $base_url)) . '">' . persian_numbers($total_pages) . '</a>';
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
        <!-- End::row -->
    </div>
</div>

<!-- Add Expert Modal -->
<div class="modal fade" id="addExpertModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary-transparent">
                <h5 class="modal-title">
                    <i class="la la-user-plus me-2"></i>
                    <?php esc_html_e('Add New Expert', 'maneli-car-inquiry'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-expert-form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php esc_html_e('First Name', 'maneli-car-inquiry'); ?> *</label>
                            <input type="text" class="form-control" id="expert-first-name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php esc_html_e('Last Name', 'maneli-car-inquiry'); ?> *</label>
                            <input type="text" class="form-control" id="expert-last-name" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold"><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?> *</label>
                            <input type="tel" class="form-control" id="expert-mobile" placeholder="09123456789" required>
                            <small class="text-muted"><?php esc_html_e('Password will be automatically generated. The expert can set their password through the forgot password option.', 'maneli-car-inquiry'); ?></small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold mb-3"><?php esc_html_e('Permissions:', 'maneli-car-inquiry'); ?></label>
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="expert-cash-inquiry" checked>
                                    <label class="form-check-label" for="expert-cash-inquiry"><?php esc_html_e('Cash Inquiry', 'maneli-car-inquiry'); ?></label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="expert-installment-inquiry" checked>
                                    <label class="form-check-label" for="expert-installment-inquiry"><?php esc_html_e('Installment Inquiry', 'maneli-car-inquiry'); ?></label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="expert-calendar" checked>
                                    <label class="form-check-label" for="expert-calendar"><?php esc_html_e('Meeting Calendar', 'maneli-car-inquiry'); ?></label>
                        </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></button>
                <button type="button" class="btn btn-primary" onclick="saveExpert()">
                    <i class="la la-save me-1"></i>
                    <?php esc_html_e('Save Expert', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var maneliAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
// Translation object
const maneliTranslations = {
    loading: <?php echo wp_json_encode(esc_html__('Loading...', 'maneli-car-inquiry')); ?>,
    editExpert: <?php echo wp_json_encode(esc_html__('Edit Expert', 'maneli-car-inquiry')); ?>,
    editPermissions: <?php echo wp_json_encode(esc_html__('Edit Permissions', 'maneli-car-inquiry')); ?>,
    firstName: <?php echo wp_json_encode(esc_html__('First Name', 'maneli-car-inquiry')); ?>,
    lastName: <?php echo wp_json_encode(esc_html__('Last Name', 'maneli-car-inquiry')); ?>,
    mobileNumber: <?php echo wp_json_encode(esc_html__('Mobile Number', 'maneli-car-inquiry')); ?>,
    save: <?php echo wp_json_encode(esc_html__('Save', 'maneli-car-inquiry')); ?>,
    cancel: <?php echo wp_json_encode(esc_html__('Cancel', 'maneli-car-inquiry')); ?>,
    saving: <?php echo wp_json_encode(esc_html__('Saving...', 'maneli-car-inquiry')); ?>,
    success: <?php echo wp_json_encode(esc_html__('Success!', 'maneli-car-inquiry')); ?>,
    error: <?php echo wp_json_encode(esc_html__('Error!', 'maneli-car-inquiry')); ?>,
    expertUpdated: <?php echo wp_json_encode(esc_html__('Expert updated successfully.', 'maneli-car-inquiry')); ?>,
    permissionsUpdated: <?php echo wp_json_encode(esc_html__('Permissions updated successfully.', 'maneli-car-inquiry')); ?>,
    expertAdded: <?php echo wp_json_encode(esc_html__('Expert added successfully.', 'maneli-car-inquiry')); ?>,
    updateError: <?php echo wp_json_encode(esc_html__('Error updating', 'maneli-car-inquiry')); ?>,
    serverError: <?php echo wp_json_encode(esc_html__('Server connection error', 'maneli-car-inquiry')); ?>,
    serverErrorRetry: <?php echo wp_json_encode(esc_html__('Server connection error. Please try again.', 'maneli-car-inquiry')); ?>,
    loadError: <?php echo wp_json_encode(esc_html__('Error loading data', 'maneli-car-inquiry')); ?>,
    loadPermissionsError: <?php echo wp_json_encode(esc_html__('Error loading permissions', 'maneli-car-inquiry')); ?>,
    fillFields: <?php echo wp_json_encode(esc_html__('Please fill all required fields.', 'maneli-car-inquiry')); ?>,
    activateExpert: <?php echo wp_json_encode(esc_html__('Activate Expert?', 'maneli-car-inquiry')); ?>,
    deactivateExpert: <?php echo wp_json_encode(esc_html__('Deactivate Expert?', 'maneli-car-inquiry')); ?>,
    expertWillActivate: <?php echo wp_json_encode(esc_html__('Expert will be activated', 'maneli-car-inquiry')); ?>,
    expertWillDeactivate: <?php echo wp_json_encode(esc_html__('Expert will be deactivated', 'maneli-car-inquiry')); ?>,
    yes: <?php echo wp_json_encode(esc_html__('Yes', 'maneli-car-inquiry')); ?>,
    no: <?php echo wp_json_encode(esc_html__('No', 'maneli-car-inquiry')); ?>,
    updating: <?php echo wp_json_encode(esc_html__('Updating...', 'maneli-car-inquiry')); ?>,
    deleteExpert: <?php echo wp_json_encode(esc_html__('Delete Expert?', 'maneli-car-inquiry')); ?>,
    deleteIrreversible: <?php echo wp_json_encode(esc_html__('This action cannot be undone!', 'maneli-car-inquiry')); ?>,
    confirmDelete: <?php echo wp_json_encode(esc_html__('Yes, Delete', 'maneli-car-inquiry')); ?>,
    deleting: <?php echo wp_json_encode(esc_html__('Deleting...', 'maneli-car-inquiry')); ?>,
    deleted: <?php echo wp_json_encode(esc_html__('Deleted!', 'maneli-car-inquiry')); ?>,
    expertDeleted: <?php echo wp_json_encode(esc_html__('Expert deleted successfully.', 'maneli-car-inquiry')); ?>,
    addExpertError: <?php echo wp_json_encode(esc_html__('Error adding expert', 'maneli-car-inquiry')); ?>
};

// View Expert Details - Redirect to expert detail page
function viewExpert(userId) {
    const url = '<?php echo esc_url(home_url('/dashboard/experts')); ?>?view_expert=' + userId;
    window.location.href = url;
}

// Helper function to generate email from mobile
function generateEmailFromMobile(mobile) {
    return mobile + '@manelikhodro.com';
}

// Edit Expert
function editExpert(userId) {
    // Load expert data first
    jQuery.ajax({
        url: maneliAjaxUrl,
        type: 'POST',
        data: {
            action: 'maneli_get_expert_data',
            user_id: userId,
            nonce: '<?php echo wp_create_nonce('maneli_expert_data_nonce'); ?>'
        },
        beforeSend: function() {
            Swal.fire({
                title: maneliTranslations.loading,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        success: function(response) {
            Swal.close();
            if (response.success) {
                const data = response.data;
                Swal.fire({
                    title: maneliTranslations.editExpert,
                    html: `
                        <div class="mb-3">
                            <label class="form-label">${maneliTranslations.firstName} *</label>
                            <input type="text" id="edit-first-name" class="form-control" value="${data.first_name || ''}" placeholder="${maneliTranslations.firstName}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">${maneliTranslations.lastName} *</label>
                            <input type="text" id="edit-last-name" class="form-control" value="${data.last_name || ''}" placeholder="${maneliTranslations.lastName}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">${maneliTranslations.mobileNumber} *</label>
                            <input type="tel" id="edit-mobile" class="form-control" value="${data.mobile || ''}" placeholder="09123456789">
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: maneliTranslations.save,
                    cancelButtonText: maneliTranslations.cancel,
                    preConfirm: () => {
                        const firstName = document.getElementById('edit-first-name').value;
                        const lastName = document.getElementById('edit-last-name').value;
                        const mobile = document.getElementById('edit-mobile').value;
                        
                        if (!firstName || !lastName || !mobile) {
                            Swal.showValidationMessage(maneliTranslations.fillFields);
                            return false;
                        }
                        
                        const email = generateEmailFromMobile(mobile);
                        return { firstName, lastName, mobile, email };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: maneliTranslations.saving,
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
                                action: 'maneli_update_expert',
                                user_id: userId,
                                first_name: result.value.firstName,
                                last_name: result.value.lastName,
                                display_name: result.value.firstName + ' ' + result.value.lastName,
                                mobile_number: result.value.mobile,
                                email: result.value.email,
                                nonce: '<?php echo wp_create_nonce('maneli_update_expert_nonce'); ?>'
                            },
                            success: function(response) {
                                Swal.close();
                                if (response.success) {
                                    Swal.fire(maneliTranslations.success, maneliTranslations.expertUpdated, 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire(maneliTranslations.error, response.data.message || maneliTranslations.updateError, 'error');
                                }
                            },
                            error: function() {
                                Swal.close();
                                Swal.fire(maneliTranslations.error, maneliTranslations.serverError, 'error');
                            }
                        });
                    }
                });
            } else {
                Swal.fire(maneliTranslations.error, response.data?.message || maneliTranslations.loadError, 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('AJAX Error:', error);
            Swal.fire(maneliTranslations.error, maneliTranslations.serverErrorRetry, 'error');
        }
    });
}

// Edit Expert Permissions - Redirect to expert detail page with permissions tab
function editExpertPermissions(userId) {
    const url = '<?php echo esc_url(home_url('/dashboard/experts')); ?>?edit_expert=' + userId + '&tab=permissions';
    window.location.href = url;
}

// Old function kept for compatibility (not used anymore)
function editExpertPermissionsOld(userId) {
    // Load expert permissions
    jQuery.ajax({
        url: maneliAjaxUrl,
        type: 'POST',
        data: {
            action: 'maneli_get_expert_permissions',
            user_id: userId,
            nonce: '<?php echo wp_create_nonce('maneli_expert_permissions_nonce'); ?>'
        },
        beforeSend: function() {
            Swal.fire({
                title: maneliTranslations.loading,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        success: function(response) {
            Swal.close();
            if (response.success) {
                const perms = response.data.permissions || {};
                Swal.fire({
                    title: maneliTranslations.editPermissions,
                    html: `
                        <div class="d-flex flex-column gap-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit-cash-inquiry" ${perms.cash_inquiry ? 'checked' : ''}>
                                <label class="form-check-label" for="edit-cash-inquiry">${<?php echo wp_json_encode(esc_html__('Cash Inquiry', 'maneli-car-inquiry')); ?>}</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit-installment-inquiry" ${perms.installment_inquiry ? 'checked' : ''}>
                                <label class="form-check-label" for="edit-installment-inquiry">${<?php echo wp_json_encode(esc_html__('Installment Inquiry', 'maneli-car-inquiry')); ?>}</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit-calendar" ${perms.calendar ? 'checked' : ''}>
                                <label class="form-check-label" for="edit-calendar">${<?php echo wp_json_encode(esc_html__('Meeting Calendar', 'maneli-car-inquiry')); ?>}</label>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: maneliTranslations.save,
                    cancelButtonText: maneliTranslations.cancel,
                    preConfirm: () => {
                        return {
                            cash_inquiry: document.getElementById('edit-cash-inquiry').checked,
                            installment_inquiry: document.getElementById('edit-installment-inquiry').checked,
                            calendar: document.getElementById('edit-calendar').checked
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: maneliTranslations.saving,
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
                                action: 'maneli_update_expert_permissions',
                                user_id: userId,
                                'permissions[cash_inquiry]': result.value.cash_inquiry ? 1 : 0,
                                'permissions[installment_inquiry]': result.value.installment_inquiry ? 1 : 0,
                                'permissions[calendar]': result.value.calendar ? 1 : 0,
                                nonce: '<?php echo wp_create_nonce('maneli_update_expert_permissions_nonce'); ?>'
                            },
                            success: function(response) {
                                Swal.close();
                                if (response.success) {
                                    Swal.fire(maneliTranslations.success, maneliTranslations.permissionsUpdated, 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire(maneliTranslations.error, response.data.message || maneliTranslations.updateError, 'error');
                                }
                            },
                            error: function() {
                                Swal.close();
                                Swal.fire(maneliTranslations.error, maneliTranslations.serverError, 'error');
                            }
                        });
                    }
                });
            } else {
                Swal.fire(maneliTranslations.error, response.data?.message || maneliTranslations.loadPermissionsError, 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('AJAX Error:', error);
            Swal.fire(maneliTranslations.error, maneliTranslations.serverErrorRetry, 'error');
        }
    });
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

// Save New Expert
function saveExpert() {
    const firstName = jQuery('#expert-first-name').val();
    const lastName = jQuery('#expert-last-name').val();
    const mobile = jQuery('#expert-mobile').val();
    const cashInquiry = jQuery('#expert-cash-inquiry').is(':checked');
    const installmentInquiry = jQuery('#expert-installment-inquiry').is(':checked');
    const calendar = jQuery('#expert-calendar').is(':checked');
    
    if (!firstName || !lastName || !mobile) {
        Swal.fire({
            icon: 'error',
            title: maneliTranslations.error,
            text: maneliTranslations.fillFields
        });
        return;
    }
    
    // Generate email and password from mobile
    const email = generateEmailFromMobile(mobile);
    const password = generateRandomPassword();

    Swal.fire({
        title: maneliTranslations.saving,
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
            action: 'maneli_add_expert',
            first_name: firstName,
            last_name: lastName,
            display_name: firstName + ' ' + lastName,
            mobile_number: mobile,
            password: password,
            email: email,
            'permissions[cash_inquiry]': cashInquiry ? 1 : 0,
            'permissions[installment_inquiry]': installmentInquiry ? 1 : 0,
            'permissions[calendar]': calendar ? 1 : 0,
            nonce: '<?php echo wp_create_nonce('maneli_add_expert_nonce'); ?>'
        },
        success: function(response) {
            Swal.close();
            if (response.success) {
                Swal.fire(maneliTranslations.success, maneliTranslations.expertAdded, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire(maneliTranslations.error, response.data.message || maneliTranslations.addExpertError, 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('AJAX Error:', error, xhr.responseText);
            Swal.fire(maneliTranslations.error, maneliTranslations.serverErrorRetry, 'error');
        }
    });
}

// Toggle Expert Status
function toggleExpertStatus(userId, activate) {
    Swal.fire({
        title: activate ? maneliTranslations.activateExpert : maneliTranslations.deactivateExpert,
        text: activate ? maneliTranslations.expertWillActivate : maneliTranslations.expertWillDeactivate,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: maneliTranslations.yes,
        cancelButtonText: maneliTranslations.no
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: maneliTranslations.updating,
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
                action: 'maneli_toggle_expert_status',
                user_id: userId,
                    active: activate === true || activate === 'true',
                    nonce: '<?php echo wp_create_nonce('maneli_toggle_expert_nonce'); ?>'
                },
                success: function(response) {
                    Swal.close();
                if (response.success) {
                        Swal.fire(maneliTranslations.success, response.data.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire(maneliTranslations.error, response.data.message, 'error');
                    }
                },
                error: function() {
                    Swal.close();
                    Swal.fire(maneliTranslations.error, maneliTranslations.serverError, 'error');
                }
            });
        }
    });
}

// Delete Expert
function deleteExpert(userId) {
    Swal.fire({
        title: maneliTranslations.deleteExpert,
        text: maneliTranslations.deleteIrreversible,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: maneliTranslations.confirmDelete,
        cancelButtonText: maneliTranslations.cancel,
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: maneliTranslations.deleting,
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
                        Swal.fire(maneliTranslations.deleted, maneliTranslations.expertDeleted, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire(maneliTranslations.error, response.data.message, 'error');
                    }
                },
                error: function() {
                    Swal.close();
                    Swal.fire(maneliTranslations.error, maneliTranslations.serverError, 'error');
                }
            });
        }
    });
}
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

.btn-list .btn {
    margin: 0 2px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-list .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.pagination-style-4 .page-link {
    border-radius: 6px;
    margin: 0 2px;
    border: 1px solid #e9ecef;
    color: #6c757d;
    transition: all 0.3s ease;
}

.pagination-style-4 .page-link:hover {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
    transform: translateY(-1px);
}

.pagination-style-4 .page-item.active .page-link {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.pagination-style-4 .page-item.disabled .page-link {
    opacity: 0.5;
    cursor: not-allowed;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5em 0.75em;
    border-radius: 6px;
}

.text-primary {
    color: var(--primary-color) !important;
}

.text-decoration-none:hover {
    text-decoration: underline !important;
}

.card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

@media (max-width: 768px) {
    .btn-list .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>
