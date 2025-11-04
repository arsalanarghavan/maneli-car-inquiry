<!-- Start::row -->
<?php
/**
 * Payment Details Page
 * Shows detailed payment information including transaction IDs and tracking numbers
 * Based on order-details.html design
 */

// Helper function to convert numbers to Persian
if (!function_exists('persian_numbers')) {
    function persian_numbers($str) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, $str);
    }
}

// Get payment ID from query var
$payment_id = get_query_var('view_payment');
if (empty($payment_id)) {
    $payment_id = isset($_GET['view_payment']) ? intval($_GET['view_payment']) : 0;
}

if (empty($payment_id)) {
    ?>
    <div class="main-content app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="la la-exclamation-triangle me-2"></i>
                        <strong>خطا!</strong> شناسه پرداخت مشخص نشده است.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Permission check
if (!current_user_can('manage_maneli_inquiries')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Determine inquiry type and get payment data
$inquiry = get_post($payment_id);
if (!$inquiry) {
    ?>
    <div class="main-content app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="la la-exclamation-triangle me-2"></i>
                        <strong>خطا!</strong> استعلام یافت نشد.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return;
}

$inquiry_type = get_post_type($payment_id);
$is_installment = ($inquiry_type === 'inquiry');
$is_cash = ($inquiry_type === 'cash_inquiry');

// Get payment details
$user_id = $inquiry->post_author;
$user = get_userdata($user_id);
$product_id = get_post_meta($payment_id, 'product_id', true);
$product = $product_id ? wc_get_product($product_id) : null;

// Payment transaction details
$payment_gateway = get_post_meta($payment_id, 'payment_gateway', true);
$payment_authority = get_post_meta($payment_id, 'payment_authority', true);
$payment_ref_id = get_post_meta($payment_id, 'payment_ref_id', true);
$payment_order_id = get_post_meta($payment_id, 'payment_order_id', true);
$payment_token = get_post_meta($payment_id, 'payment_token', true);
$payment_amount = $is_installment ? get_post_meta($payment_id, 'inquiry_paid_amount', true) : get_post_meta($payment_id, 'payment_amount', true);
$payment_date = $is_installment ? get_post_meta($payment_id, 'inquiry_payment_date', true) : get_post_meta($payment_id, 'payment_date', true);

// Fallback for payment date
if (empty($payment_date)) {
    $payment_date = $inquiry->post_date;
}

// Get gateway label
$gateway_label = esc_html__('Unknown', 'maneli-car-inquiry');
if ($payment_gateway === 'zarinpal') {
    $gateway_label = esc_html__('Zarinpal Gateway', 'maneli-car-inquiry');
} elseif ($payment_gateway === 'sadad') {
    $gateway_label = esc_html__('Sadad Gateway', 'maneli-car-inquiry');
} else {
    $gateway_label = esc_html__('Bank Gateway', 'maneli-car-inquiry');
}

// Get payment type label
$payment_type_label = esc_html__('Unknown', 'maneli-car-inquiry');
if ($is_installment) {
    $payment_type_label = esc_html__('Inquiry Payment Fee', 'maneli-car-inquiry');
} elseif ($is_cash) {
    $payment_type_label = esc_html__('Cash Car Down Payment', 'maneli-car-inquiry');
}

// Format payment date to Jalali
$payment_date_formatted = '';
if (!empty($payment_date)) {
    $timestamp = strtotime($payment_date);
    if (function_exists('maneli_gregorian_to_jalali')) {
        $jalali_date = maneli_gregorian_to_jalali(
            date('Y', $timestamp),
            date('m', $timestamp),
            date('d', $timestamp),
            'Y/m/d'
        );
        $payment_date_formatted = persian_numbers($jalali_date . ' ' . date('H:i', $timestamp));
    } else {
        $payment_date_formatted = persian_numbers(date_i18n('Y/m/d H:i', $timestamp));
    }
}

// Get user meta
$user_first_name = get_user_meta($user_id, 'first_name', true);
$user_last_name = get_user_meta($user_id, 'last_name', true);
$user_mobile = get_user_meta($user_id, 'mobile_number', true);
$user_display_name = trim($user_first_name . ' ' . $user_last_name);
if (empty($user_display_name)) {
    $user_display_name = $user->display_name;
}

// Get tracking code (ref_id for zarinpal, order_id for sadad)
$tracking_code = '';
if ($payment_gateway === 'zarinpal' && !empty($payment_ref_id)) {
    $tracking_code = $payment_ref_id;
} elseif ($payment_gateway === 'sadad' && !empty($payment_order_id)) {
    $tracking_code = $payment_order_id;
}

// Get transaction ID (authority for zarinpal, token for sadad)
$transaction_id = '';
if ($payment_gateway === 'zarinpal' && !empty($payment_authority)) {
    $transaction_id = $payment_authority;
} elseif ($payment_gateway === 'sadad' && !empty($payment_token)) {
    $transaction_id = $payment_token;
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2 mb-4">
    <div>
        <nav>
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/dashboard/payments')); ?>"><?php esc_html_e('Payment Management', 'maneli-car-inquiry'); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Payment Details', 'maneli-car-inquiry'); ?></li>
            </ol>
        </nav>
        <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Payment Details', 'maneli-car-inquiry'); ?></h1>
    </div>
    <div class="btn-list">
        <a href="<?php echo esc_url(home_url('/dashboard/payments')); ?>" class="btn btn-white btn-wave">
            <i class="ri-arrow-right-line align-middle me-1 lh-1"></i> <?php esc_html_e('Back', 'maneli-car-inquiry'); ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-title">
                    <?php esc_html_e('Payment Number', 'maneli-car-inquiry'); ?> - <span class="text-primary">#<?php echo persian_numbers($payment_id); ?></span>
                </div>
                <div>
                    <span class="badge bg-success-transparent">
                        <?php echo esc_html($payment_date_formatted); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="fw-semibold mb-2"><?php esc_html_e('Payment Type:', 'maneli-car-inquiry'); ?></div>
                        <div class="text-muted"><?php echo esc_html($payment_type_label); ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="fw-semibold mb-2"><?php esc_html_e('Payment Method:', 'maneli-car-inquiry'); ?></div>
                        <div class="text-muted"><?php echo esc_html($gateway_label); ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="fw-semibold mb-2"><?php esc_html_e('Payment Amount:', 'maneli-car-inquiry'); ?></div>
                        <div class="fs-15 fw-semibold">
                            <?php if ($payment_amount > 0): ?>
                                <?php echo persian_numbers(number_format_i18n($payment_amount)); ?> تومان
                            <?php else: ?>
                                <span class="text-success">رایگان!</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="fw-semibold mb-2"><?php esc_html_e('Payment Date:', 'maneli-car-inquiry'); ?></div>
                        <div class="text-muted"><?php echo esc_html($payment_date_formatted); ?></div>
                    </div>
                </div>

                <?php if (!empty($tracking_code) || !empty($transaction_id)): ?>
                <div class="border-top pt-4">
                    <h5 class="mb-3"><?php esc_html_e('Transaction Information', 'maneli-car-inquiry'); ?></h5>
                    <div class="row">
                        <?php if (!empty($tracking_code)): ?>
                        <div class="col-md-6 mb-3">
                            <div class="fw-semibold mb-2">کد رهگیری:</div>
                            <div class="text-primary fw-medium">
                                <?php if ($payment_gateway === 'zarinpal'): ?>
                                    <a href="javascript:void(0);" class="text-primary"><?php echo persian_numbers(esc_html($tracking_code)); ?></a>
                                <?php else: ?>
                                    <?php echo persian_numbers(esc_html($tracking_code)); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($transaction_id)): ?>
                        <div class="col-md-6 mb-3">
                            <div class="fw-semibold mb-2">شماره تراکنش:</div>
                            <div class="text-primary fw-medium">
                                <?php echo persian_numbers(esc_html($transaction_id)); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($payment_gateway === 'zarinpal' && !empty($payment_authority)): ?>
                        <div class="col-md-6 mb-3">
                            <div class="fw-semibold mb-2">Authority Code:</div>
                            <div class="text-muted"><?php echo esc_html($payment_authority); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($payment_gateway === 'sadad' && !empty($payment_order_id)): ?>
                        <div class="col-md-6 mb-3">
                            <div class="fw-semibold mb-2">Order ID:</div>
                            <div class="text-muted"><?php echo esc_html($payment_order_id); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="border-top pt-4">
                    <h5 class="mb-3"><?php esc_html_e('Inquiry Information', 'maneli-car-inquiry'); ?></h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="fw-semibold mb-2">شناسه استعلام:</div>
                            <div class="text-muted">#<?php echo persian_numbers($payment_id); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="fw-semibold mb-2">خودرو:</div>
                            <div class="text-muted">
                                <?php if ($product): ?>
                                    <?php echo persian_numbers(esc_html($product->get_name())); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top-0 d-flex align-items-center justify-content-between gap-2">
                <a href="<?php echo esc_url(home_url('/dashboard/payments')); ?>" class="btn btn-primary-light btn-wave">
                    <i class="ri-arrow-right-line me-1 align-middle d-inline-block"></i>بازگشت به لیست پرداخت‌ها
                </a>
                <?php if ($is_installment): ?>
                    <a href="<?php echo esc_url(home_url('/dashboard/installment-inquiries?view_inquiry=' . $payment_id)); ?>" class="btn btn-primary btn-wave">
                        <i class="ri-eye-line me-1 align-middle d-inline-block"></i>مشاهده استعلام
                    </a>
                <?php elseif ($is_cash): ?>
                    <a href="<?php echo esc_url(home_url('/dashboard/cash-inquiries?view_inquiry=' . $payment_id)); ?>" class="btn btn-primary btn-wave">
                        <i class="ri-eye-line me-1 align-middle d-inline-block"></i>مشاهده استعلام
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    اطلاعات مشتری
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="fw-semibold mb-2">نام و نام خانوادگی:</div>
                    <div class="text-muted"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($user_display_name)) : esc_html($user_display_name); ?></div>
                </div>
                <div class="mb-3">
                    <div class="fw-semibold mb-2">شماره موبایل:</div>
                    <div class="text-muted">
                        <a href="tel:<?php echo esc_attr($user_mobile); ?>" class="text-primary">
                            <?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($user_mobile)) : esc_html($user_mobile); ?>
                        </a>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="fw-semibold mb-2">شناسه کاربر:</div>
                    <div class="text-muted">#<?php echo persian_numbers($user_id); ?></div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    خلاصه پرداخت
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table">
                    <tbody>
                        <tr>
                            <td>
                                <div class="fw-semibold">مبلغ کل:</div>
                            </td>
                            <td>
                                <?php if ($payment_amount > 0): ?>
                                    <span class="fs-15 fw-medium"><?php echo persian_numbers(number_format_i18n($payment_amount)); ?> تومان</span>
                                <?php else: ?>
                                    <span class="text-success">رایگان!</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="fw-semibold">وضعیت:</div>
                            </td>
                            <td>
                                <span class="badge bg-success-transparent">پرداخت موفق</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
    </div>
</div>
<!-- End::row -->

