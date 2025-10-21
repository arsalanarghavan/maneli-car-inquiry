<?php
// Check if we should display a single inquiry report instead of the list
$inquiry_id = isset($_GET['inquiry_id']) ? intval($_GET['inquiry_id']) : 0;
$cash_inquiry_id = isset($_GET['cash_inquiry_id']) ? intval($_GET['cash_inquiry_id']) : 0;
$show_list = true;

// Helper function to enqueue assets for inquiry reports
if (!function_exists('maneli_enqueue_inquiry_report_assets')) {
    function maneli_enqueue_inquiry_report_assets() {
        // Enqueue SweetAlert2 (CRITICAL - required for modals)
        if (!wp_script_is('sweetalert2', 'enqueued')) {
            wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, true);
        }
        
        // Enqueue Select2 for expert assignment modal
        if (!wp_style_is('select2', 'enqueued')) {
            wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
        }
        if (!wp_script_is('select2', 'enqueued')) {
            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
        }

        // Enqueue datepicker for tracking status modal
        if (!wp_script_is('maneli-jalali-datepicker', 'enqueued')) {
            wp_enqueue_script('maneli-jalali-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/kamadatepicker.min.js', [], '2.1.0', true);
        }
        if (!wp_style_is('maneli-datepicker-theme', 'enqueued')) {
            wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css', [], '1.0.0');
        }

        // Enqueue inquiry lists script
        if (!wp_script_is('maneli-inquiry-lists-js', 'enqueued')) {
            wp_enqueue_script('maneli-inquiry-lists-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-lists.js', ['jquery', 'sweetalert2', 'select2', 'maneli-jalali-datepicker'], '1.0.0', true);
            
            // Localize script
            $options = get_option('maneli_inquiry_all_options', []);
            $cash_rejection_reasons = array_filter(array_map('trim', explode("\n", $options['cash_inquiry_rejection_reasons'] ?? '')));
            $installment_rejection_reasons = array_filter(array_map('trim', explode("\n", $options['installment_rejection_reasons'] ?? '')));
            $experts_query = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
            $experts_list = [];
            foreach ($experts_query as $expert) {
                $experts_list[] = ['id' => $expert->ID, 'name' => $expert->display_name ?: $expert->user_login];
            }
            
            wp_localize_script('maneli-inquiry-lists-js', 'maneliInquiryLists', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'experts' => $experts_list,
                'nonces' => [
                    'inquiry_filter' => wp_create_nonce('maneli_inquiry_filter_nonce'),
                    'cash_filter' => wp_create_nonce('maneli_cash_inquiry_filter_nonce'),
                    'details' => wp_create_nonce('maneli_inquiry_details_nonce'),
                    'cash_details' => wp_create_nonce('maneli_cash_inquiry_details_nonce'),
                    'cash_update' => wp_create_nonce('maneli_cash_inquiry_update_nonce'),
                    'cash_delete' => wp_create_nonce('maneli_cash_inquiry_delete_nonce'),
                    'inquiry_delete' => wp_create_nonce('maneli_inquiry_delete_nonce'),
                    'cash_set_downpayment' => wp_create_nonce('maneli_cash_set_downpayment_nonce'),
                    'cash_assign_expert' => wp_create_nonce('maneli_cash_inquiry_assign_expert_nonce'),
                    'assign_expert' => wp_create_nonce('maneli_inquiry_assign_expert_nonce'),
                    'tracking_status' => wp_create_nonce('maneli_tracking_status_nonce'),
                ],
                'cash_rejection_reasons' => $cash_rejection_reasons,
                'installment_rejection_reasons' => $installment_rejection_reasons,
                'text' => [
                    'error' => esc_html__('Error', 'maneli-car-inquiry'),
                    'success' => esc_html__('Success', 'maneli-car-inquiry'),
                    'server_error' => esc_html__('Server error. Please try again.', 'maneli-car-inquiry'),
                    'unknown_error' => esc_html__('An unknown error occurred.', 'maneli-car-inquiry'),
                    'no_experts_available' => esc_html__('No experts available.', 'maneli-car-inquiry'),
                    'select_expert_required' => esc_html__('Please select an expert.', 'maneli-car-inquiry'),
                    'select_expert_placeholder' => esc_html__('Select Expert', 'maneli-car-inquiry'),
                    'auto_assign' => esc_html__('-- Automatic Assignment (Round-robin) --', 'maneli-car-inquiry'),
                    'assign_title' => esc_html__('Assign to Expert', 'maneli-car-inquiry'),
                    'assign_label' => esc_html__('Select Expert:', 'maneli-car-inquiry'),
                    'assign_button' => esc_html__('Assign', 'maneli-car-inquiry'),
                    'cancel_button' => esc_html__('Cancel', 'maneli-car-inquiry'),
                    'confirm_button' => esc_html__('Confirm', 'maneli-car-inquiry'),
                    'delete_title' => esc_html__('Deleted', 'maneli-car-inquiry'),
                    'delete_list_title' => esc_html__('Delete this inquiry?', 'maneli-car-inquiry'),
                    'delete_list_text' => esc_html__('This action cannot be undone.', 'maneli-car-inquiry'),
                    'confirm_delete_title' => esc_html__('Are you sure you want to delete this request?', 'maneli-car-inquiry'),
                    'confirm_delete_text' => esc_html__('This action cannot be undone!', 'maneli-car-inquiry'),
                ]
            ]);
        }
    }
}

// If an inquiry ID is present, display the report template directly
if ($inquiry_id > 0 && get_post_type($inquiry_id) === 'inquiry') {
    // Display installment inquiry report
    $inquiry = get_post($inquiry_id);
    if ($inquiry && Maneli_Permission_Helpers::can_user_view_inquiry($inquiry_id, get_current_user_id())) {
        $is_admin_or_expert = current_user_can('manage_maneli_inquiries') || Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
        
        if ($is_admin_or_expert) {
            // Enqueue necessary assets
            maneli_enqueue_inquiry_report_assets();
            // Load the admin/expert report template
            // Note: The template expects $inquiry_id variable to be set
            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/shortcodes/inquiry-lists/report-admin-installment.php';
        } else {
            // Load the customer report template
            // Note: The template expects $inquiry_id variable to be set
            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/shortcodes/inquiry-form/step-5-final-report.php';
        }
    } else {
        echo '<div class="alert alert-danger">استعلام یافت نشد یا دسترسی ندارید.</div>';
    }
    $show_list = false;
} elseif ($cash_inquiry_id > 0 && get_post_type($cash_inquiry_id) === 'cash_inquiry') {
    // Display cash inquiry report
    $inquiry = get_post($cash_inquiry_id);
    if ($inquiry && Maneli_Permission_Helpers::can_user_view_inquiry($cash_inquiry_id, get_current_user_id())) {
        $is_admin_or_expert = current_user_can('manage_maneli_inquiries') || Maneli_Permission_Helpers::is_assigned_expert($cash_inquiry_id, get_current_user_id());
        
        if ($is_admin_or_expert) {
            // Enqueue necessary assets
            maneli_enqueue_inquiry_report_assets();
            
            // Set $inquiry_id for the template (cash report expects this variable)
            $inquiry_id = $cash_inquiry_id;
            
            // Add flag to load cash report scripts in footer
            add_action('wp_footer', function() use ($cash_inquiry_id) {
                ?>
                <!-- SweetAlert2 for Cash Report -->
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                
                <!-- Cash Report Script -->
                <script>
                var maneliCashReport = {
                    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    nonces: {
                        save_meeting: '<?php echo wp_create_nonce('maneli_save_meeting'); ?>',
                        expert_decision: '<?php echo wp_create_nonce('maneli_expert_decision'); ?>',
                        admin_approve: '<?php echo wp_create_nonce('maneli_admin_approve'); ?>',
                        update_status: '<?php echo wp_create_nonce('maneli_update_cash_status'); ?>',
                        save_note: '<?php echo wp_create_nonce('maneli_save_expert_note'); ?>'
                    }
                };
                </script>
                <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/frontend/cash-report.js?ver=<?php echo filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/frontend/cash-report.js'); ?>"></script>
                <?php
            }, 999);
            
            // Load the admin/expert cash report template
            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/shortcodes/inquiry-lists/report-admin-cash.php';
        } else {
            // Set $inquiry_id for the template
            $inquiry_id = $cash_inquiry_id;
            // Load the customer cash report template
            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/shortcodes/inquiry-lists/report-customer-cash.php';
        }
    } else {
        echo '<div class="alert alert-danger">استعلام یافت نشد یا دسترسی ندارید.</div>';
    }
    $show_list = false;
}

// Only show the list if no single inquiry is being displayed
if ($show_list):
?>
<!-- Start::row -->
<?php
// Get current user and role
$current_user_id = get_current_user_id();
$is_admin = current_user_can('manage_maneli_inquiries');
$is_expert = in_array('maneli_expert', wp_get_current_user()->roles, true);

// Determine which inquiries to count based on role and page
// Get subpage from query var or variable
if (!isset($subpage) || $subpage === '') {
    $subpage = get_query_var('maneli_dashboard_subpage', '');
}
$count_type = (!empty($subpage)) ? $subpage : 'all';

// Calculate statistics for cash inquiries
$cash_stats = [
    'total' => 0,
    'new' => 0,
    'referred' => 0,
    'in_progress' => 0,
    'follow_up_scheduled' => 0,
    'awaiting_downpayment' => 0,
    'downpayment_received' => 0,
    'meeting_scheduled' => 0,
    'completed' => 0,
    'rejected' => 0,
    'assigned' => 0,
    'unassigned' => 0
];

if ($count_type === 'cash' || $count_type === 'all') {
    $cash_args = [
        'post_type' => 'cash_inquiry',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ];
    
    // Filter for expert
    if ($is_expert && !$is_admin) {
        $cash_args['meta_query'] = [
            [
                'key' => 'assigned_expert_id',
                'value' => $current_user_id,
                'compare' => '='
            ]
        ];
    }
    
    $all_cash = get_posts($cash_args);
    $cash_stats['total'] = count($all_cash);
    
    foreach ($all_cash as $inq) {
        $status = get_post_meta($inq->ID, 'cash_inquiry_status', true) ?: 'new';
        $expert_id = get_post_meta($inq->ID, 'assigned_expert_id', true);
        
        // Count by status
        switch ($status) {
            case 'new':
            case 'referred':
            case 'in_progress':
            case 'follow_up_scheduled':
            case 'awaiting_downpayment':
            case 'downpayment_received':
            case 'meeting_scheduled':
            case 'completed':
            case 'rejected':
                $cash_stats[$status]++;
                break;
            // Compatibility with old statuses
            case 'pending':
                $cash_stats['new']++;
                break;
            case 'awaiting_payment':
                $cash_stats['awaiting_downpayment']++;
                break;
            case 'approved':
                $cash_stats['completed']++;
                break;
        }
        
        // Count assignments (only for admin)
        if ($is_admin) {
            if ($expert_id) {
                $cash_stats['assigned']++;
            } else {
                $cash_stats['unassigned']++;
            }
        }
    }
}

// Calculate statistics for installment inquiries  
$installment_stats = [
    'total' => 0,
    'new' => 0,
    'referred' => 0,
    'in_progress' => 0,
    'meeting_scheduled' => 0,
    'follow_up_scheduled' => 0,
    'cancelled' => 0,
    'completed' => 0,
    'rejected' => 0,
    'assigned' => 0,
    'unassigned' => 0
];

if ($count_type === 'installment' || $count_type === 'all') {
    $inst_args = [
        'post_type' => 'inquiry',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ];
    
    // Filter for expert
    if ($is_expert && !$is_admin) {
        $inst_args['meta_query'] = [
            [
                'key' => 'assigned_expert_id',
                'value' => $current_user_id,
                'compare' => '='
            ]
        ];
    }
    
    $all_installment = get_posts($inst_args);
    $installment_stats['total'] = count($all_installment);
    
    foreach ($all_installment as $inq) {
        $tracking_status = get_post_meta($inq->ID, 'tracking_status', true) ?: 'new';
        $expert_id = get_post_meta($inq->ID, 'assigned_expert_id', true);
        
        // Count by tracking status
        switch ($tracking_status) {
            case 'new':
            case 'referred':
            case 'in_progress':
            case 'meeting_scheduled':
            case 'follow_up_scheduled':
            case 'cancelled':
            case 'completed':
            case 'rejected':
                $installment_stats[$tracking_status]++;
                break;
        }
        
        // Count assignments (only for admin)
        if ($is_admin) {
            if ($expert_id) {
                $installment_stats['assigned']++;
            } else {
                // Only count unassigned if status is 'new'
                if ($tracking_status === 'new') {
                    $installment_stats['unassigned']++;
                }
            }
        }
    }
}
?>

<!-- Statistics Cards for Cash Inquiries -->
<?php if ($count_type === 'cash'): ?>
<div class="row mb-4">
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-primary-transparent">
                            <i class="la la-list-alt fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">مجموع</span>
                        </div>
                        <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($cash_stats['total']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($is_admin): ?>
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-info-transparent">
                            <i class="la la-share fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">ارجاع شده</span>
                        </div>
                        <h4 class="fw-semibold mb-0 text-info"><?php echo number_format_i18n($cash_stats['assigned']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-danger-transparent">
                            <i class="la la-exclamation-triangle fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">منتظر ارجاع</span>
                        </div>
                        <h4 class="fw-semibold mb-0 text-danger"><?php echo number_format_i18n($cash_stats['unassigned']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-warning-transparent">
                            <i class="la la-clock fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">انتظار پیش‌پرداخت</span>
                        </div>
                        <h4 class="fw-semibold mb-0 text-warning"><?php echo number_format_i18n($cash_stats['awaiting_downpayment']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-success-transparent">
                            <i class="la la-check-circle fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">تکمیل شده</span>
                        </div>
                        <h4 class="fw-semibold mb-0 text-success"><?php echo number_format_i18n($cash_stats['completed']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-cyan-transparent">
                            <i class="la la-handshake fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">جلسه حضوری</span>
                        </div>
                        <h4 class="fw-semibold mb-0 text-cyan"><?php echo number_format_i18n($cash_stats['meeting_scheduled']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-danger-transparent">
                            <i class="la la-times-circle fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">رد شده</span>
                        </div>
                        <h4 class="fw-semibold mb-0 text-danger"><?php echo number_format_i18n($cash_stats['rejected']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Statistics Cards for Installment Inquiries -->
<?php if ($count_type === 'installment'): ?>
<div class="row mb-4">
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-primary-transparent">
                            <i class="la la-list-alt fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">مجموع</span>
                        </div>
                        <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($installment_stats['total']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($is_admin): ?>
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-info-transparent">
                            <i class="la la-share fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">ارجاع شده</span>
                        </div>
                        <h4 class="fw-semibold mb-0 text-info"><?php echo number_format_i18n($installment_stats['assigned']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-danger-transparent">
                            <i class="la la-exclamation-triangle fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">منتظر ارجاع</span>
                        </div>
                        <h4 class="fw-semibold mb-0 text-danger"><?php echo number_format_i18n($installment_stats['unassigned']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-warning-transparent">
                            <i class="la la-clock fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">در حال پیگیری</span>
                        </div>
                        <h4 class="fw-semibold mb-0 text-warning"><?php echo number_format_i18n($installment_stats['in_progress']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-success-transparent">
                            <i class="la la-check-circle fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">تکمیل شده</span>
                        </div>
                        <h4 class="fw-semibold mb-0 text-success"><?php echo number_format_i18n($installment_stats['completed']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="avatar avatar-md bg-danger-transparent">
                            <i class="la la-times-circle fs-24"></i>
                        </span>
                    </div>
                    <div class="flex-fill">
                        <div class="mb-1">
                            <span class="text-muted fs-13">رد شده</span>
                        </div>
                        <h4 class="fw-semibold mb-0 text-danger"><?php echo number_format_i18n($installment_stats['rejected']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="la la-<?php echo $count_type === 'cash' ? 'dollar-sign' : 'credit-card'; ?> me-2"></i>
                    <?php 
                    if ($count_type === 'cash') {
                        echo 'لیست استعلامات نقدی';
                    } elseif ($count_type === 'installment') {
                        echo 'لیست استعلامات اقساطی';
                    } else {
                        echo 'لیست همه استعلامات';
                    }
                    ?>
                </div>
                <?php if ($count_type === 'cash'): ?>
                    <button type="button" class="btn btn-primary" id="open-new-cash-inquiry-modal">
                        <i class="la la-plus me-1"></i>
                        استعلام نقدی جدید
                    </button>
                <?php else: ?>
                    <a href="<?php echo home_url('/dashboard/new-inquiry'); ?>" class="btn btn-primary">
                        <i class="la la-plus me-1"></i>
                        استعلام جدید
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable-basic" class="table table-bordered text-nowrap w-100">
                        <thead>
                            <tr>
                                <th>شماره استعلام</th>
                                <th>نام کاربر</th>
                                <th>شماره موبایل</th>
                                <th>نوع خودرو</th>
                                <th>وضعیت</th>
                                <?php if (current_user_can('manage_maneli_inquiries')): ?>
                                    <th>کارشناس</th>
                                <?php endif; ?>
                                <th>تاریخ</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Determine which type of inquiries to show based on subpage
                            $show_type = (!empty($subpage)) ? $subpage : 'all'; // 'cash', 'installment', or 'all'
                            $all_inquiries = [];
                            
                            // Get installment inquiries (if needed)
                            if ($show_type === 'installment' || $show_type === 'all') {
                                $installment_inquiries = get_posts([
                                    'post_type' => 'inquiry',
                                    'post_status' => 'publish',
                                    'numberposts' => 20,
                                    'orderby' => 'date',
                                    'order' => 'DESC'
                                ]);
                                
                                // Add to combined list
                                foreach ($installment_inquiries as $inquiry) {
                                    $all_inquiries[] = [
                                        'object' => $inquiry,
                                        'type' => 'installment',
                                        'timestamp' => strtotime($inquiry->post_date)
                                    ];
                                }
                            }
                            
                            // Get cash inquiries (if needed)
                            if ($show_type === 'cash' || $show_type === 'all') {
                                $cash_inquiries = get_posts([
                                    'post_type' => 'cash_inquiry',
                                    'post_status' => 'publish',
                                    'numberposts' => 20,
                                    'orderby' => 'date',
                                    'order' => 'DESC'
                                ]);
                                
                                // Add to combined list
                                foreach ($cash_inquiries as $inquiry) {
                                    $all_inquiries[] = [
                                        'object' => $inquiry,
                                        'type' => 'cash',
                                        'timestamp' => strtotime($inquiry->post_date)
                                    ];
                                }
                            }
                            
                            // Sort by date (newest first)
                            if (!empty($all_inquiries)) {
                                usort($all_inquiries, function($a, $b) {
                                    return $b['timestamp'] - $a['timestamp'];
                                });
                                
                                // Limit to 10 most recent
                                $all_inquiries = array_slice($all_inquiries, 0, 10);
                            }

                            foreach ($all_inquiries as $inquiry_data) {
                                $inquiry = $inquiry_data['object'];
                                $inquiry_type = $inquiry_data['type'];
                                
                                // Get data based on inquiry type
                                if ($inquiry_type === 'cash') {
                                    // Cash inquiry data
                                    $status = get_post_meta($inquiry->ID, 'cash_inquiry_status', true);
                                    $first_name = get_post_meta($inquiry->ID, 'cash_first_name', true);
                                    $last_name = get_post_meta($inquiry->ID, 'cash_last_name', true);
                                    $user_name = trim($first_name . ' ' . $last_name);
                                    $user_phone = get_post_meta($inquiry->ID, 'mobile_number', true);
                                    $product_id = get_post_meta($inquiry->ID, 'product_id', true);
                                    $car_type = $product_id ? get_the_title($product_id) : '';
                                    $inquiry_label = 'نقدی';
                                    
                                    // Get status label for cash inquiry using new statuses
                                    $status_text = Maneli_CPT_Handler::get_cash_inquiry_status_label($status);
                                    $status_label = '';
                                    switch ($status) {
                                        case 'new':
                                            $status_label = '<span class="badge bg-secondary">' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'referred':
                                            $status_label = '<span class="badge bg-info"><i class="la la-share me-1"></i>' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'in_progress':
                                            $status_label = '<span class="badge bg-primary">' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'follow_up_scheduled':
                                            $status_label = '<span class="badge bg-warning">' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'awaiting_downpayment':
                                            $status_label = '<span class="badge bg-warning">' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'downpayment_received':
                                            $status_label = '<span class="badge bg-success-light"><i class="la la-check-double me-1"></i>' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'meeting_scheduled':
                                            $status_label = '<span class="badge bg-cyan"><i class="la la-calendar-check me-1"></i>' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'approved':
                                            $status_label = '<span class="badge bg-success">' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'rejected':
                                            $status_label = '<span class="badge bg-danger">' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'completed':
                                            $status_label = '<span class="badge bg-dark">' . esc_html($status_text) . '</span>';
                                            break;
                                        // برای سازگاری با وضعیت‌های قدیمی
                                        case 'pending':
                                            $status_label = '<span class="badge bg-secondary">جدید</span>';
                                            break;
                                        case 'awaiting_payment':
                                            $status_label = '<span class="badge bg-warning">در انتظار پیش پرداخت</span>';
                                            break;
                                        default:
                                            $status_label = '<span class="badge bg-secondary">نامشخص</span>';
                                    }
                                } else {
                                    // Installment inquiry data
                                    $tracking_status = get_post_meta($inquiry->ID, 'tracking_status', true) ?: 'new';
                                    $first_name = get_post_meta($inquiry->ID, 'first_name', true);
                                    $last_name = get_post_meta($inquiry->ID, 'last_name', true);
                                    $user_name = trim($first_name . ' ' . $last_name);
                                    $user_phone = get_post_meta($inquiry->ID, 'mobile_number', true);
                                    $product_id = get_post_meta($inquiry->ID, 'product_id', true);
                                    $car_type = $product_id ? get_the_title($product_id) : '';
                                    $inquiry_label = 'اقساطی';
                                    
                                    // Get status label for installment inquiry using tracking_status
                                    $status_text = Maneli_CPT_Handler::get_tracking_status_label($tracking_status);
                                    $status_label = '';
                                    switch ($tracking_status) {
                                        case 'new':
                                            $status_label = '<span class="badge bg-secondary">' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'referred':
                                            $status_label = '<span class="badge bg-info"><i class="la la-share me-1"></i>' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'in_progress':
                                            $status_label = '<span class="badge bg-primary">' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'meeting_scheduled':
                                            $status_label = '<span class="badge bg-cyan"><i class="la la-calendar-check me-1"></i>' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'follow_up_scheduled':
                                            $status_label = '<span class="badge bg-warning">' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'cancelled':
                                            $status_label = '<span class="badge bg-danger">' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'completed':
                                            $status_label = '<span class="badge bg-dark">' . esc_html($status_text) . '</span>';
                                            break;
                                        case 'rejected':
                                            $status_label = '<span class="badge bg-danger">' . esc_html($status_text) . '</span>';
                                            break;
                                        default:
                                            $status_label = '<span class="badge bg-secondary">نامشخص</span>';
                                    }
                                }
                                
                                // Convert date to Jalali
                                if (function_exists('maneli_gregorian_to_jalali')) {
                                    $timestamp = strtotime($inquiry->post_date);
                                    $date = maneli_gregorian_to_jalali(
                                        date('Y', $timestamp),
                                        date('m', $timestamp),
                                        date('d', $timestamp),
                                        'Y/m/d'
                                    );
                                } else {
                                    $date = get_the_date('Y/m/d', $inquiry->ID);
                                }
                                
                                // Get assigned expert
                                $expert_id = get_post_meta($inquiry->ID, 'assigned_expert_id', true);
                                $expert_name = '';
                                if ($expert_id) {
                                    $expert = get_userdata($expert_id);
                                    $expert_name = $expert ? $expert->display_name : '';
                                }
                                // Fallback to assigned_expert_name if no ID
                                if (!$expert_name) {
                                    $expert_name = get_post_meta($inquiry->ID, 'assigned_expert_name', true);
                                }
                                
                                // Build view URL based on inquiry type
                                if ($inquiry_type === 'cash') {
                                    $view_url = add_query_arg('cash_inquiry_id', $inquiry->ID, home_url('/dashboard/inquiries/cash'));
                                } else {
                                    $view_url = add_query_arg('inquiry_id', $inquiry->ID, home_url('/dashboard/inquiries/installment'));
                                }
                                ?>
                                <tr>
                                    <td>
                                        #<?php echo $inquiry->ID; ?>
                                        <br><span class="badge bg-light text-dark" style="font-size: 10px; margin-top: 3px;"><?php echo $inquiry_label; ?></span>
                                    </td>
                                    <td><?php echo esc_html($user_name ?: 'نامشخص'); ?></td>
                                    <td><?php echo esc_html($user_phone ?: 'نامشخص'); ?></td>
                                    <td><?php echo esc_html($car_type ?: 'نامشخص'); ?></td>
                                    <td><?php echo $status_label; ?></td>
                                    <?php if (current_user_can('manage_maneli_inquiries')): ?>
                                        <td>
                                            <?php if ($expert_name): ?>
                                                <span class="badge bg-info">
                                                    <i class="la la-user-tie me-1"></i>
                                                    <?php echo esc_html($expert_name); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                                    <i class="la la-exclamation-triangle me-1"></i>
                                                    منتظر ارجاع
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td><?php echo esc_html($date); ?></td>
                                    <td>
                                        <div class="btn-list">
                                            <a href="<?php echo esc_url($view_url); ?>" class="btn btn-sm btn-primary-light">
                                                <i class="la la-eye"></i> مشاهده
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                            
                            // Show empty message if no inquiries found
                            if (empty($all_inquiries)) {
                                ?>
                                <tr>
                                    <td colspan="<?php echo current_user_can('manage_maneli_inquiries') ? '8' : '7'; ?>" class="text-center">
                                        <div class="alert alert-info mb-0">
                                            <i class="la la-info-circle me-2"></i>
                                            هیچ استعلامی یافت نشد.
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->

<?php endif; // End of $show_list check ?>

<!-- Modal حذف شد - استفاده از SweetAlert2 -->
<?php
// Enqueue cash inquiry form script in footer (for modal functionality)
if ($count_type === 'cash') {
    add_action('wp_footer', function() {
        ?>
        <!-- SweetAlert2 for modals (if not already loaded) -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <!-- Cash Inquiry Form Script -->
        <script>
        var maneliCashInquiryForm = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('maneli_create_cash_inquiry'); ?>'
        };
        </script>
        <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/frontend/cash-inquiry-form.js?ver=<?php echo filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/frontend/cash-inquiry-form.js'); ?>"></script>
        <?php
    }, 1000);
}
?>