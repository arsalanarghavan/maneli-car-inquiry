<!-- Start::row -->
<?php
/**
 * Cash Followups Dashboard Page
 * Shows cash inquiries with cash_inquiry_status = 'follow_up_scheduled'
 * Accessible by: Admin, Expert (only their own)
 */

// Check permission
if (!current_user_can('manage_maneli_inquiries') && !in_array('maneli_expert', wp_get_current_user()->roles, true)) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Get current user
$current_user_id = get_current_user_id();
$is_admin = current_user_can('manage_maneli_inquiries');

// Get experts for filter (admin only)
$experts = $is_admin ? get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']) : [];

// Query followup cash inquiries
$args = [
    'post_type' => 'cash_inquiry',
    'posts_per_page' => 20,
    'orderby' => 'meta_value',
    'meta_key' => 'followup_date',
    'order' => 'ASC',
    'post_status' => 'publish',
    'meta_query' => [
        [
            'key' => 'cash_inquiry_status',
            'value' => 'follow_up_scheduled',
            'compare' => '='
        ]
    ]
];

// Filter by expert if not admin
if (!$is_admin) {
    $args['meta_query'][] = [
        'key' => 'assigned_expert_id',
        'value' => $current_user_id,
        'compare' => '='
    ];
}

$followups_query = new WP_Query($args);
$followups = $followups_query->posts;

// Calculate statistics
$today = current_time('Y-m-d');
$week_end = date('Y-m-d', strtotime('+7 days'));
$total_count = 0;
$today_count = 0;
$overdue_count = 0;
$week_count = 0;

foreach ($followups as $inquiry) {
    $follow_up_date = get_post_meta($inquiry->ID, 'followup_date', true);
    $total_count++;
    
    if ($follow_up_date) {
        if ($follow_up_date === $today) $today_count++;
        if ($follow_up_date < $today) $overdue_count++;
        if ($follow_up_date <= $week_end) $week_count++;
    }
}

// Enqueue assets for modals
if (!wp_script_is('select2', 'enqueued')) {
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
}

if (!wp_script_is('maneli-jalali-datepicker', 'enqueued')) {
    wp_enqueue_script('maneli-jalali-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/kamadatepicker.min.js', [], '2.1.0', true);
    wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css', [], '1.0.0');
}
?>
<div class="main-content app-content">
    <div class="container-fluid">

        <!-- Start::page-header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item">
                        <a href="<?php echo home_url('/dashboard'); ?>"><?php esc_html_e('Dashboard', 'maneli-car-inquiry'); ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Cash Follow-ups', 'maneli-car-inquiry'); ?></li>
                </ol>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Cash Follow-ups', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- End::page-header -->

<div class="row">
    <div class="col-xl-12">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="flex-fill">
                                <span class="d-block mb-1 text-muted fs-12"><?php esc_html_e('Today\'s Follow-ups', 'maneli-car-inquiry'); ?></span>
                                <h4 class="mb-0 fw-semibold"><?php echo persian_numbers_no_separator($today_count); ?></h4>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-md avatar-rounded bg-warning-transparent">
                                    <i class="la la-clock fs-20 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="flex-fill">
                                <span class="d-block mb-1 text-muted fs-12"><?php esc_html_e('Overdue', 'maneli-car-inquiry'); ?></span>
                                <h4 class="mb-0 fw-semibold text-danger"><?php echo persian_numbers_no_separator($overdue_count); ?></h4>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-md avatar-rounded bg-danger-transparent">
                                    <i class="la la-exclamation-triangle fs-20 text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="flex-fill">
                                <span class="d-block mb-1 text-muted fs-12"><?php esc_html_e('This Week', 'maneli-car-inquiry'); ?></span>
                                <h4 class="mb-0 fw-semibold"><?php echo persian_numbers_no_separator($week_count); ?></h4>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-md avatar-rounded bg-info-transparent">
                                    <i class="la la-calendar-alt fs-20 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="flex-fill">
                                <span class="d-block mb-1 text-muted fs-12"><?php esc_html_e('Total', 'maneli-car-inquiry'); ?></span>
                                <h4 class="mb-0 fw-semibold"><?php echo persian_numbers_no_separator($total_count); ?></h4>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-md avatar-rounded bg-primary-transparent">
                                    <i class="la la-list-alt fs-20 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card custom-card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="card-title">
                    <?php esc_html_e('Cash Inquiry Follow-ups List', 'maneli-car-inquiry'); ?><span class="badge bg-primary rounded ms-2 fs-12 align-middle"><?php echo persian_numbers_no_separator($total_count); ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Table -->
                <div class="table-responsive">
                    <table class="table text-nowrap table-hover">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Follow-up Date', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                <?php if ($is_admin): ?>
                                    <th scope="col"><?php esc_html_e('Expert', 'maneli-car-inquiry'); ?></th>
                                <?php endif; ?>
                                <th scope="col"><?php esc_html_e('Registration Date', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($followups)): ?>
                                <tr>
                                    <td colspan="<?php echo $is_admin ? '8' : '7'; ?>" class="text-center py-4">
                                        <i class="la la-inbox fs-24 text-muted"></i>
                                        <p class="text-muted mt-3"><?php esc_html_e('No follow-ups found.', 'maneli-car-inquiry'); ?></p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($followups as $inquiry): 
                                    $inquiry_id = $inquiry->ID;
                                    $first_name = get_post_meta($inquiry_id, 'cash_first_name', true);
                                    $last_name = get_post_meta($inquiry_id, 'cash_last_name', true);
                                    $customer_name = trim($first_name . ' ' . $last_name);
                                    $product_id = get_post_meta($inquiry_id, 'product_id', true);
                                    $follow_up_date = get_post_meta($inquiry_id, 'followup_date', true);
                                    $cash_status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
                                    $cash_status_label = Maneli_CPT_Handler::get_cash_inquiry_status_label($cash_status);
                                    $expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
                                    $expert = $expert_id ? get_userdata($expert_id) : null;
                                    
                                    // Get followup history
                                    $followup_history = get_post_meta($inquiry_id, 'followup_history', true) ?: [];
                                    
                                    // Convert dates to Jalali
                                    $created_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($inquiry->post_date, 'Y/m/d');
                                    $created_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($created_date) : $created_date;
                                    
                                    // Overdue check
                                    $is_overdue = $follow_up_date && $follow_up_date < $today;
                                    $row_class = $is_overdue ? 'table-danger' : '';
                                ?>
                                    <tr class="crm-contact contacts-list <?php echo $row_class; ?>">
                                        <td data-title="<?php esc_attr_e('ID', 'maneli-car-inquiry'); ?>">
                                            <span class="fw-medium">#<?php echo persian_numbers_no_separator($inquiry_id); ?></span>
                                        </td>
                                        <td data-title="<?php esc_attr_e('Customer', 'maneli-car-inquiry'); ?>">
                                            <div class="d-flex align-items-center gap-2">
                                                <div>
                                                    <span class="d-block fw-medium"><?php echo esc_html($customer_name ?: esc_html__('Unknown', 'maneli-car-inquiry')); ?></span>
                                                    <span class="d-block text-muted fs-11">
                                                        <i class="la la-user me-1 fs-13 align-middle"></i><?php echo esc_html($created_date); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-title="<?php esc_attr_e('Car', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_title($product_id)); ?></td>
                                        <td data-title="<?php esc_attr_e('Follow-up Date', 'maneli-car-inquiry'); ?>">
                                            <?php if ($follow_up_date): 
                                                $follow_up_date_persian = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($follow_up_date) : $follow_up_date;
                                            ?>
                                                <strong class="<?php echo $is_overdue ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo esc_html($follow_up_date_persian); ?>
                                                </strong>
                                                <?php if ($is_overdue): ?>
                                                    <br><span class="badge bg-danger-transparent"><?php esc_html_e('Overdue', 'maneli-car-inquiry'); ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td data-title="<?php esc_attr_e('Status', 'maneli-car-inquiry'); ?>">
                                            <span class="badge bg-warning-transparent">
                                                <?php echo esc_html($cash_status_label); ?>
                                            </span>
                                            <?php if (!empty($followup_history)): ?>
                                                <br><small class="text-muted fs-11">
                                                    (<?php printf(esc_html__('%s previous follow-ups', 'maneli-car-inquiry'), persian_numbers_no_separator(count($followup_history))); ?>)
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($is_admin): ?>
                                            <td data-title="<?php esc_attr_e('Expert', 'maneli-car-inquiry'); ?>">
                                                <?php echo $expert ? esc_html($expert->display_name) : '<span class="text-muted">—</span>'; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td data-title="<?php esc_attr_e('Registration Date', 'maneli-car-inquiry'); ?>"><?php echo esc_html($created_date); ?></td>
                                        <td data-title="<?php esc_attr_e('Actions', 'maneli-car-inquiry'); ?>">
                                            <div class="btn-list">
                                                <a href="<?php echo add_query_arg('cash_inquiry_id', $inquiry_id, home_url('/dashboard/inquiries/cash')); ?>" 
                                                   class="btn btn-sm btn-primary-light btn-icon" title="<?php esc_attr_e('View', 'maneli-car-inquiry'); ?>">
                                                    <i class="la la-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->

</div>
</div>
