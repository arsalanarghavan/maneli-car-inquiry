<?php
// Check if we should display a single inquiry report instead of the list
$inquiry_id = isset($_GET['inquiry_id']) ? intval($_GET['inquiry_id']) : 0;
$cash_inquiry_id = isset($_GET['cash_inquiry_id']) ? intval($_GET['cash_inquiry_id']) : 0;
$show_list = true;

// Helper function to enqueue assets for inquiry reports
if (!function_exists('maneli_enqueue_inquiry_report_assets')) {
    function maneli_enqueue_inquiry_report_assets() {
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
            wp_enqueue_script('maneli-inquiry-lists-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-lists.js', ['jquery', 'select2', 'maneli-jalali-datepicker'], '1.0.0', true);
            
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
                    'assign_title' => esc_html__('Assign to Expert', 'maneli-car-inquiry'),
                    'assign_label' => esc_html__('Select Expert:', 'maneli-car-inquiry'),
                    'assign_button' => esc_html__('Assign', 'maneli-car-inquiry'),
                    'cancel_button' => esc_html__('Cancel', 'maneli-car-inquiry'),
                    'confirm_button' => esc_html__('Confirm', 'maneli-car-inquiry'),
                    'delete_title' => esc_html__('Deleted', 'maneli-car-inquiry'),
                    'delete_list_title' => esc_html__('Delete this inquiry?', 'maneli-car-inquiry'),
                    'delete_list_text' => esc_html__('This action cannot be undone.', 'maneli-car-inquiry'),
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
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">لیست استعلامات</div>
                <a href="<?php echo home_url('/dashboard/new-inquiry'); ?>" class="btn btn-primary">
                    <i class="la la-plus me-1"></i>
                    استعلام جدید
                </a>
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
                                <th>تاریخ</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Determine which type of inquiries to show based on subpage
                            $show_type = isset($subpage) ? $subpage : 'all'; // 'cash', 'installment', or 'all'
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
                                    
                                    // Get status label for cash inquiry
                                    $status_label = '';
                                    switch ($status) {
                                        case 'pending':
                                            $status_label = '<span class="badge bg-warning">در انتظار</span>';
                                            break;
                                        case 'approved':
                                            $status_label = '<span class="badge bg-info">تایید شده</span>';
                                            break;
                                        case 'awaiting_payment':
                                            $status_label = '<span class="badge bg-primary">در انتظار پرداخت</span>';
                                            break;
                                        case 'completed':
                                            $status_label = '<span class="badge bg-success">تکمیل شده</span>';
                                            break;
                                        case 'rejected':
                                            $status_label = '<span class="badge bg-danger">رد شده</span>';
                                            break;
                                        default:
                                            $status_label = '<span class="badge bg-secondary">نامشخص</span>';
                                    }
                                } else {
                                    // Installment inquiry data
                                    $status = get_post_meta($inquiry->ID, 'inquiry_status', true);
                                    $first_name = get_post_meta($inquiry->ID, 'first_name', true);
                                    $last_name = get_post_meta($inquiry->ID, 'last_name', true);
                                    $user_name = trim($first_name . ' ' . $last_name);
                                    $user_phone = get_post_meta($inquiry->ID, 'mobile_number', true);
                                    $product_id = get_post_meta($inquiry->ID, 'product_id', true);
                                    $car_type = $product_id ? get_the_title($product_id) : '';
                                    $inquiry_label = 'اقساطی';
                                    
                                    // Get status label for installment inquiry
                                    $status_label = '';
                                    switch ($status) {
                                        case 'pending':
                                            $status_label = '<span class="badge bg-warning">در انتظار</span>';
                                            break;
                                        case 'user_confirmed':
                                            $status_label = '<span class="badge bg-info">تایید کاربر</span>';
                                            break;
                                        case 'approved':
                                            $status_label = '<span class="badge bg-success">تایید شده</span>';
                                            break;
                                        case 'rejected':
                                            $status_label = '<span class="badge bg-danger">رد شده</span>';
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
                                    <td colspan="7" class="text-center">
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
