<?php
/**
 * Payments Management Page
 * Only accessible by Administrators
 */

// Permission check - Only Admin can access
if (!current_user_can('manage_maneli_inquiries')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Helper function to convert numbers to Persian
if (!function_exists('persian_numbers')) {
    function persian_numbers($str) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, $str);
    }
}

// Get options
$options = get_option('maneli_inquiry_all_options', []);
$active_gateway = $options['active_gateway'] ?? 'zarinpal';
$inquiry_fee = (int)($options['inquiry_fee'] ?? 0);

// Localization helpers for labels
$payment_type_labels = [
    'inquiry_fee' => esc_html__('Inquiry Fee Payment', 'maneli-car-inquiry'),
    'cash_down_payment' => esc_html__('Cash Car Down Payment', 'maneli-car-inquiry'),
];

$gateway_labels = [
    'zarinpal' => esc_html__('Zarinpal Gateway', 'maneli-car-inquiry'),
    'sadad' => esc_html__('Sadad Gateway', 'maneli-car-inquiry'),
];

$default_gateway_label = esc_html__('Bank Gateway', 'maneli-car-inquiry');

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$gateway_filter = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : '';
$payment_type_filter = isset($_GET['payment_type']) ? sanitize_text_field($_GET['payment_type']) : '';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;

// Fetch payments
$payments_list = [];

// 1. Installment inquiries (payment type: inquiry_fee)
// Only show inquiries where payment was actually completed (has inquiry_payment_completed meta)
$installment_inquiries = get_posts([
    'post_type' => 'inquiry',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'meta_query' => [
        [
            'key' => 'inquiry_payment_completed',
            'value' => 'yes',
            'compare' => '='
        ]
    ]
]);

foreach ($installment_inquiries as $inquiry) {
    $user_id = $inquiry->post_author;
    $user = get_userdata($user_id);
    if (!$user) continue;
    
    $product_id = get_post_meta($inquiry->ID, 'product_id', true);
    $product = wc_get_product($product_id);
    $mobile_number = get_user_meta($user_id, 'mobile_number', true);
    
    // Get the actual paid amount stored in meta (handles discount and free cases)
    $paid_amount = get_post_meta($inquiry->ID, 'inquiry_paid_amount', true);
    if ($paid_amount === '') {
        // Fallback: if meta doesn't exist, calculate from current settings
        $discount_applied = get_user_meta($user_id, 'maneli_discount_applied', true) === 'yes';
        $paid_amount = ($discount_applied || $inquiry_fee == 0) ? 0 : $inquiry_fee;
    } else {
        $paid_amount = (int)$paid_amount;
    }
    
    // Get payment date from meta if available, otherwise use post date
    $payment_date = get_post_meta($inquiry->ID, 'inquiry_payment_date', true);
    if (empty($payment_date)) {
        $payment_date = $inquiry->post_date;
    }
    
    $payments_list[] = [
        'inquiry_id' => $inquiry->ID,
        'inquiry_type' => 'installment',
        'payment_type' => 'inquiry_fee',
        'payment_type_label' => $payment_type_labels['inquiry_fee'] ?? esc_html__('Inquiry Fee Payment', 'maneli-car-inquiry'),
        'product_id' => $product_id,
        'product_name' => $product ? $product->get_name() : '-',
        'user_id' => $user_id,
        'user_name' => $user->display_name,
        'mobile_number' => $mobile_number,
        'amount' => $paid_amount,
        'gateway' => $active_gateway,
        'gateway_label' => $gateway_labels[$active_gateway] ?? $default_gateway_label,
        'payment_date' => $payment_date,
        'status' => 'completed'
    ];
}

// 2. Cash inquiries with downpayment (payment type: cash_down_payment)
$cash_inquiries = get_posts([
    'post_type' => 'cash_inquiry',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'meta_query' => [
        [
            'key' => 'downpayment_paid_date',
            'compare' => 'EXISTS'
        ]
    ]
]);

foreach ($cash_inquiries as $inquiry) {
    $user_id = $inquiry->post_author;
    $user = get_userdata($user_id);
    if (!$user) continue;
    
    $product_id = get_post_meta($inquiry->ID, 'product_id', true);
    $product = wc_get_product($product_id);
    $mobile_number = get_user_meta($user_id, 'mobile_number', true);
    $downpayment = get_post_meta($inquiry->ID, 'cash_down_payment', true);
    $payment_date = get_post_meta($inquiry->ID, 'downpayment_paid_date', true);
    
    $payments_list[] = [
        'inquiry_id' => $inquiry->ID,
        'inquiry_type' => 'cash',
        'payment_type' => 'cash_down_payment',
        'payment_type_label' => $payment_type_labels['cash_down_payment'] ?? esc_html__('Cash Car Down Payment', 'maneli-car-inquiry'),
        'product_id' => $product_id,
        'product_name' => $product ? $product->get_name() : '-',
        'user_id' => $user_id,
        'user_name' => $user->display_name,
        'mobile_number' => $mobile_number,
        'amount' => (int)$downpayment,
        'gateway' => $active_gateway,
        'gateway_label' => $gateway_labels[$active_gateway] ?? $default_gateway_label,
        'payment_date' => $payment_date ?: $inquiry->post_date,
        'status' => 'completed'
    ];
}

// Calculate statistics BEFORE filtering
$all_payments_list = $payments_list; // Keep a copy for statistics
$total_payments_unfiltered = count($all_payments_list);
$total_amount_unfiltered = array_sum(array_column($all_payments_list, 'amount'));
$inquiry_fee_count = count(array_filter($all_payments_list, function($p) { return $p['payment_type'] === 'inquiry_fee'; }));
$cash_downpayment_count = count(array_filter($all_payments_list, function($p) { return $p['payment_type'] === 'cash_down_payment'; }));

// Apply filters
if ($search) {
    $payments_list = array_filter($payments_list, function($payment) use ($search) {
        return stripos($payment['inquiry_id'], $search) !== false ||
               stripos($payment['user_name'], $search) !== false ||
               stripos($payment['mobile_number'], $search) !== false ||
               stripos($payment['product_name'], $search) !== false;
    });
}

if ($status_filter) {
    $payments_list = array_filter($payments_list, function($payment) use ($status_filter) {
        return $payment['status'] === $status_filter;
    });
}

if ($gateway_filter) {
    $payments_list = array_filter($payments_list, function($payment) use ($gateway_filter) {
        return $payment['gateway'] === $gateway_filter;
    });
}

if ($payment_type_filter) {
    $payments_list = array_filter($payments_list, function($payment) use ($payment_type_filter) {
        return $payment['payment_type'] === $payment_type_filter;
    });
}

// Sort by payment date (newest first)
usort($payments_list, function($a, $b) {
    return strtotime($b['payment_date']) - strtotime($a['payment_date']);
});

// Pagination
$total_payments = count($payments_list);
$total_pages = ceil($total_payments / $per_page);
$offset = ($paged - 1) * $per_page;
$payments_list = array_slice($payments_list, $offset, $per_page);

// Statistics
$total_amount = array_sum(array_column($payments_list, 'amount'));
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Pages', 'maneli-car-inquiry'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Payment Management', 'maneli-car-inquiry'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Payment Management', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- Page Header Close -->
        
<style>
/* Payment Statistics Cards - Inline Styles for Immediate Effect */
.card.custom-card.crm-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border: 1px solid rgba(0, 0, 0, 0.06) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
    position: relative !important;
    overflow: hidden !important;
    border-radius: 0.5rem !important;
    background: #fff !important;
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

.card.custom-card.crm-card:hover h4 {
    color: #5e72e4 !important;
}

.card.custom-card.crm-card .border-primary,
.card.custom-card.crm-card .bg-primary {
    background: linear-gradient(135deg, #5e72e4 0%, #7c3aed 100%) !important;
}

.card.custom-card.crm-card .border-success,
.card.custom-card.crm-card .bg-success {
    background: linear-gradient(135deg, #2dce89 0%, #20c997 100%) !important;
}

.card.custom-card.crm-card .border-warning,
.card.custom-card.crm-card .bg-warning {
    background: linear-gradient(135deg, #fb6340 0%, #fbb140 100%) !important;
}

.card.custom-card.crm-card .border-secondary,
.card.custom-card.crm-card .bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
}

.card.custom-card.crm-card .border-danger,
.card.custom-card.crm-card .bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}
</style>

<div class="row mb-4 maneli-mobile-card-scroll">
    <!-- کارت مجموع پرداخت‌ها -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
        <div class="card custom-card crm-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                        <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                            <i class="la la-money-bill-wave fs-20"></i>
                        </span>
                    </div>
                </div>
                <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Payments', 'maneli-car-inquiry'); ?></p>
                <div class="d-flex align-items-center justify-content-between mt-1">
                    <h4 class="mb-0 d-flex align-items-center"><?php echo persian_numbers(number_format_i18n($total_payments_unfiltered)); ?></h4>
                    <span class="badge bg-primary-transparent rounded-pill fs-11"><?php esc_html_e('All', 'maneli-car-inquiry'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- کارت مجموع مبلغ پرداخت‌ها -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
        <div class="card custom-card crm-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <div class="p-2 border border-success border-opacity-10 bg-success-transparent rounded-pill">
                        <span class="avatar avatar-md avatar-rounded bg-success svg-white">
                            <i class="la la-coins fs-20"></i>
                        </span>
                    </div>
                </div>
                <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Amount', 'maneli-car-inquiry'); ?></p>
                <div class="d-flex align-items-center justify-content-between mt-1">
                    <h4 class="mb-0 d-flex align-items-center"><?php echo persian_numbers(number_format_i18n($total_amount_unfiltered)); ?></h4>
                    <span class="badge bg-success-transparent rounded-pill fs-11"><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- کارت پرداخت‌های هزینه استعلام -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
        <div class="card custom-card crm-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <div class="p-2 border border-warning border-opacity-10 bg-warning-transparent rounded-pill">
                        <span class="avatar avatar-md avatar-rounded bg-warning svg-white">
                            <i class="la la-file-invoice fs-20"></i>
                        </span>
                    </div>
                </div>
                <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Inquiry Fee Payments', 'maneli-car-inquiry'); ?></p>
                <div class="d-flex align-items-center justify-content-between mt-1">
                    <h4 class="mb-0 d-flex align-items-center"><?php echo persian_numbers(number_format_i18n($inquiry_fee_count)); ?></h4>
                    <span class="badge bg-warning-transparent rounded-pill fs-11"><?php esc_html_e('Installment', 'maneli-car-inquiry'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- کارت پیش پرداخت‌های نقدی -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
        <div class="card custom-card crm-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <div class="p-2 border border-secondary border-opacity-10 bg-secondary-transparent rounded-pill">
                        <span class="avatar avatar-md avatar-rounded bg-secondary svg-white">
                            <i class="la la-car fs-20"></i>
                        </span>
                    </div>
                </div>
                <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Cash Down Payments', 'maneli-car-inquiry'); ?></p>
                <div class="d-flex align-items-center justify-content-between mt-1">
                    <h4 class="mb-0 d-flex align-items-center"><?php echo persian_numbers(number_format_i18n($cash_downpayment_count)); ?></h4>
                    <span class="badge bg-secondary-transparent rounded-pill fs-11"><?php esc_html_e('Cash', 'maneli-car-inquiry'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
            
        <!-- Start::row-1 -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="card-title">
                            <?php esc_html_e('Payment Management', 'maneli-car-inquiry'); ?>
                        </div>
                        <div class="btn-list payments-header-actions">
                            <div class="custom-form-group flex-grow-1 me-2">
                                <input type="search" name="search" class="form-control" placeholder="<?php esc_attr_e('Search payments...', 'maneli-car-inquiry'); ?>" value="<?php echo esc_attr($search); ?>" id="payment-search-input">
                                <a href="javascript:void(0);" class="text-muted custom-form-btn" onclick="filterPayments()"><i class="ti ti-search"></i></a>
                            </div>
                            <div class="dropdown">
                                <button type="button" class="btn btn-primary-light btn-sm btn-wave" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-3-line align-middle me-1 d-inline-block"></i><?php esc_html_e('Filter', 'maneli-car-inquiry'); ?>
                                </button>
                                <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/payments')); ?>"><?php esc_html_e('All', 'maneli-car-inquiry'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo esc_url(add_query_arg(['payment_type' => 'inquiry_fee'], home_url('/dashboard/payments'))); ?>"><?php esc_html_e('Inquiry Fee Payment', 'maneli-car-inquiry'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo esc_url(add_query_arg(['payment_type' => 'cash_down_payment'], home_url('/dashboard/payments'))); ?>"><?php esc_html_e('Cash Car Down Payment', 'maneli-car-inquiry'); ?></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php esc_html_e('Inquiry ID', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Mobile', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Payment Date', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Payment Type', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Payment Method', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Amount', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($payments_list)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="la la-inbox fs-48 mb-3 d-block"></i>
                                                    پرداختی یافت نشد
                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($payments_list as $payment): ?>
                                            <tr class="order-list">
                                                <td>#<?php echo persian_numbers(esc_html($payment['inquiry_id'])); ?></td>
                                                <td>
                        <div class="d-flex align-items-center">
                                                        <div class="ms-2">
                                                            <p class="fw-semibold mb-0 d-flex align-items-center">
                                                                <?php echo persian_numbers(esc_html($payment['product_name'])); ?>
                                                            </p>
                            </div>
                        </div>
                                                </td>
                                                <td>
                        <div class="d-flex align-items-center">
                                                        <span class="fw-medium"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($payment['user_name'])) : esc_html($payment['user_name']); ?></span>
                            </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($payment['mobile_number'])): ?>
                                                        <a href="tel:<?php echo esc_attr($payment['mobile_number']); ?>" class="text-primary text-decoration-none">
                                                            <?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($payment['mobile_number'])) : esc_html($payment['mobile_number']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $timestamp = strtotime($payment['payment_date']);
                                                    if (function_exists('maneli_gregorian_to_jalali')) {
                                                        echo persian_numbers(maneli_gregorian_to_jalali(
                                                            date('Y', $timestamp),
                                                            date('m', $timestamp),
                                                            date('d', $timestamp),
                                                            'Y/m/d'
                                                        ));
                                                    } else {
                                                        echo persian_numbers(date_i18n('Y/m/d', $timestamp));
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo esc_html($payment['payment_type_label']); ?></td>
                                                <td><?php echo esc_html($payment['gateway_label']); ?></td>
                                                <td class="fw-semibold">
                                                    <?php if ($payment['amount'] > 0): ?>
                                                        <?php echo persian_numbers(number_format_i18n($payment['amount'])); ?> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?>
                                                    <?php else: ?>
                                                        <span class="text-success"><?php esc_html_e('Free!', 'maneli-car-inquiry'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="<?php echo esc_url(add_query_arg('view_payment', $payment['inquiry_id'], home_url('/dashboard/payments'))); ?>" class="btn btn-icon btn-sm btn-primary-light btn-wave waves-effect waves-light" title="<?php esc_attr_e('View Payment Details', 'maneli-car-inquiry'); ?>">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer border-top-0">
                            <div class="d-flex align-items-center flex-wrap overflow-auto">
                                <div class="mb-2 mb-sm-0">
                                    <?php printf(esc_html__('Showing %1$s to %2$s of %3$s items', 'maneli-car-inquiry'), '<b>' . persian_numbers($offset + 1) . '</b>', '<b>' . persian_numbers(min($offset + $per_page, $total_payments)) . '</b>', '<b>' . persian_numbers($total_payments) . '</b>'); ?> <i class="bi bi-arrow-left ms-2 fw-semibold"></i>
                                </div>
                                <div class="ms-auto">
                                    <?php
                                    $base_url = remove_query_arg('paged');
                                    $base_url = add_query_arg([
                                        'search' => $search,
                                        'status' => $status_filter,
                                        'gateway' => $gateway_filter,
                                        'payment_type' => $payment_type_filter
                                    ], $base_url);
                                    ?>
                                    <ul class="pagination mb-0 overflow-auto">
                                        <?php
                                        // Previous button
                                        $prev_link = $paged > 1 ? add_query_arg('paged', $paged - 1, $base_url) : 'javascript:void(0);';
                                        $prev_disabled = $paged <= 1 ? ' disabled' : '';
                                        echo '<li class="page-item' . $prev_disabled . '">';
                                        echo '<a class="page-link" href="' . esc_url($prev_link) . '">' . esc_html__('Previous', 'maneli-car-inquiry') . '</a>';
                                        echo '</li>';
                                        
                                        // Page numbers
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
                                </div>
                            </div>
                        </div>
                                            <?php endif; ?>
                </div>
            </div>
        </div>
        <!--End::row-1 -->
    </div>
</div>

<script>
function filterPayments() {
    const search = document.getElementById('payment-search-input').value;
    const url = new URL(window.location.href);
    if (search) {
        url.searchParams.set('search', search);
        } else {
        url.searchParams.delete('search');
    }
    window.location.href = url.toString();
}
</script>
