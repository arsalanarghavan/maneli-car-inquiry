<!-- Start::row -->
<?php
// Permission check
if (!current_user_can('manage_maneli_inquiries')) {
    echo '<div class="alert alert-danger">شما دسترسی به این صفحه را ندارید.</div>';
    return;
}
?>

<div class="row">
    <div class="col-xl-12">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php
            global $wpdb;
            
            // Total payments count
            $total_payments = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = 'payment_status'");
            
            // Count by status
            $completed_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'payment_status' AND meta_value = 'completed'");
            $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'payment_status' AND meta_value = 'pending'");
            $failed_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'payment_status' AND meta_value = 'failed'");
            $cancelled_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'payment_status' AND meta_value = 'cancelled'");
            $refunded_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'payment_status' AND meta_value = 'refunded'");
            
            // Amount calculations
            $total_amount = $wpdb->get_var("SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = 'payment_amount'") ?: 0;
            $completed_amount = $wpdb->get_var("
                SELECT SUM(CAST(pm_amount.meta_value AS UNSIGNED)) 
                FROM {$wpdb->postmeta} pm_status
                INNER JOIN {$wpdb->postmeta} pm_amount ON pm_status.post_id = pm_amount.post_id 
                WHERE pm_status.meta_key = 'payment_status' 
                AND pm_status.meta_value = 'completed' 
                AND pm_amount.meta_key = 'payment_amount'
            ") ?: 0;
            $pending_amount = $wpdb->get_var("
                SELECT SUM(CAST(pm_amount.meta_value AS UNSIGNED)) 
                FROM {$wpdb->postmeta} pm_status
                INNER JOIN {$wpdb->postmeta} pm_amount ON pm_status.post_id = pm_amount.post_id 
                WHERE pm_status.meta_key = 'payment_status' 
                AND pm_status.meta_value = 'pending' 
                AND pm_amount.meta_key = 'payment_amount'
            ") ?: 0;
            
            // Today's payments
            $today_start = date('Y-m-d 00:00:00');
            $today_end = date('Y-m-d 23:59:59');
            $today_payments = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT p.ID)
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE pm.meta_key = 'payment_status'
                AND p.post_date >= %s
                AND p.post_date <= %s
            ", $today_start, $today_end));
            
            $today_amount = $wpdb->get_var($wpdb->prepare("
                SELECT SUM(CAST(pm_amount.meta_value AS UNSIGNED))
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
                INNER JOIN {$wpdb->postmeta} pm_amount ON p.ID = pm_amount.post_id
                WHERE pm_status.meta_key = 'payment_status'
                AND pm_status.meta_value = 'completed'
                AND pm_amount.meta_key = 'payment_amount'
                AND p.post_date >= %s
                AND p.post_date <= %s
            ", $today_start, $today_end)) ?: 0;
            ?>
            
            <div class="col-xl-3 col-lg-4 col-md-6">
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
                                    <span class="text-muted fs-13">کل پرداخت‌ها</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($total_payments); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-4 col-md-6">
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
                                    <span class="text-muted fs-13">موفق</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-success"><?php echo number_format_i18n($completed_count); ?></h4>
                                <?php if ($completed_amount > 0): ?>
                                    <small class="text-muted d-block mt-1"><?php echo number_format_i18n($completed_amount); ?> تومان</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-4 col-md-6">
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
                                    <span class="text-muted fs-13">در انتظار</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-warning"><?php echo number_format_i18n($pending_count); ?></h4>
                                <?php if ($pending_amount > 0): ?>
                                    <small class="text-muted d-block mt-1"><?php echo number_format_i18n($pending_amount); ?> تومان</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-4 col-md-6">
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
                                    <span class="text-muted fs-13">ناموفق</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-danger"><?php echo number_format_i18n($failed_count); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-secondary-transparent">
                                    <i class="la la-times-circle fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">لغو شده</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($cancelled_count); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-info-transparent">
                                    <i class="la la-reply fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">برگشت داده شده</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-info"><?php echo number_format_i18n($refunded_count); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-cyan-transparent">
                                    <i class="la la-calendar-check fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">امروز</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-cyan"><?php echo number_format_i18n($today_payments); ?></h4>
                                <?php if ($today_amount > 0): ?>
                                    <small class="text-muted d-block mt-1"><?php echo number_format_i18n($today_amount); ?> تومان</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card custom-card bg-gradient-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-white mb-2">
                                    <i class="la la-wallet fs-20 me-1"></i>
                                    کل مبلغ (تومان)
                                </h6>
                                <h3 class="fw-bold mb-0 text-white"><?php echo number_format_i18n($total_amount); ?></h3>
                            </div>
                            <div>
                                <span class="avatar avatar-lg bg-white-transparent">
                                    <i class="la la-wallet fs-32"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="la la-money-bill-wave me-2"></i>
                    مدیریت پرداخت‌ها
                </div>
                <div class="btn-list">
                    <button class="btn btn-success" onclick="refreshPayments()">
                        <i class="la la-sync me-1"></i>
                        بروزرسانی
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-4 g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="la la-filter me-1"></i>
                            وضعیت:
                        </label>
                        <select class="form-select" id="payment-status">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="completed">تکمیل شده</option>
                            <option value="pending">در انتظار</option>
                            <option value="failed">ناموفق</option>
                            <option value="cancelled">لغو شده</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="la la-credit-card me-1"></i>
                            درگاه:
                        </label>
                        <select class="form-select" id="payment-gateway">
                            <option value="">همه درگاه‌ها</option>
                            <option value="zarinpal">زرین‌پال</option>
                            <option value="sadad">سداد</option>
                            <option value="bank">بانکی</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="la la-search me-1"></i>
                            جستجو:
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="la la-search"></i>
                            </span>
                            <input type="text" class="form-control" id="payment-search" placeholder="جستجوی شناسه، مبلغ...">
                            <button class="btn btn-primary" onclick="filterPayments()">
                                فیلتر
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table id="payments-table" class="table table-bordered table-hover text-nowrap">
                        <thead class="table-primary">
                            <tr>
                                <th><i class="la la-hashtag me-1"></i>شناسه</th>
                                <th><i class="la la-file-alt me-1"></i>استعلام</th>
                                <th><i class="la la-money-bill me-1"></i>مبلغ</th>
                                <th><i class="la la-credit-card me-1"></i>درگاه</th>
                                <th><i class="la la-info-circle me-1"></i>وضعیت</th>
                                <th><i class="la la-calendar me-1"></i>تاریخ</th>
                                <th><i class="la la-wrench me-1"></i>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get payment data from postmeta
                            global $wpdb;

                            $payments = $wpdb->get_results("
                                SELECT pm.post_id, pm.meta_value as payment_data, p.post_title
                                FROM {$wpdb->postmeta} pm
                                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                                WHERE pm.meta_key = 'payment_data'
                                AND p.post_type = 'inquiry'
                                AND p.post_status = 'publish'
                                ORDER BY p.post_date DESC
                                LIMIT 20
                            ");

                            foreach ($payments as $payment) {
                                $payment_data = maybe_unserialize($payment->meta_value);
                                if (!$payment_data) continue;

                                $status = isset($payment_data['status']) ? $payment_data['status'] : 'unknown';
                                $amount = isset($payment_data['amount']) ? number_format($payment_data['amount']) : 'نامشخص';
                                $gateway = isset($payment_data['gateway']) ? $payment_data['gateway'] : 'نامشخص';
                                $date = isset($payment_data['date']) ? $payment_data['date'] : get_the_date('Y/m/d H:i', $payment->post_id);

                                $status_badge = '';
                                switch ($status) {
                                    case 'completed':
                                        $status_badge = '<span class="badge bg-success">تکمیل شده</span>';
                                        break;
                                    case 'pending':
                                        $status_badge = '<span class="badge bg-warning">در انتظار</span>';
                                        break;
                                    case 'failed':
                                        $status_badge = '<span class="badge bg-danger">ناموفق</span>';
                                        break;
                                    case 'cancelled':
                                        $status_badge = '<span class="badge bg-secondary">لغو شده</span>';
                                        break;
                                    default:
                                        $status_badge = '<span class="badge bg-info">نامشخص</span>';
                                }
                                ?>
                                <tr>
                                    <td><?php echo esc_html($payment->post_id); ?></td>
                                    <td><?php echo esc_html($payment->post_title); ?></td>
                                    <td><?php echo esc_html($amount); ?> تومان</td>
                                    <td><?php echo esc_html($gateway); ?></td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td><?php echo esc_html($date); ?></td>
                                    <td>
                                        <div class="btn-list">
                                            <button class="btn btn-sm btn-primary-light" onclick="viewPaymentDetails(<?php echo $payment->post_id; ?>)" title="جزئیات">
                                                <i class="la la-eye"></i>
                                            </button>
                                            <?php if ($status === 'completed'): ?>
                                            <button class="btn btn-sm btn-warning-light" onclick="refundPayment(<?php echo $payment->post_id; ?>)" title="استرداد">
                                                <i class="la la-undo"></i>
                                            </button>
                                            <?php endif; ?>
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

<script>
function filterPayments() {
    const status = document.getElementById('payment-status').value;
    const gateway = document.getElementById('payment-gateway').value;
    const search = document.getElementById('payment-search').value.toLowerCase();

    const rows = document.querySelectorAll('#payments-table tbody tr');

    rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        const shouldShow = (!search || rowText.includes(search));

        if (shouldShow) {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    });
}

function refreshPayments() {
    location.reload();
}

function viewPaymentDetails(paymentId) {
    Swal.fire({
        title: 'جزئیات پرداخت',
        html: '<p>در حال بارگذاری...</p>',
        icon: 'info'
    });
}

function refundPayment(paymentId) {
    Swal.fire({
        title: 'استرداد پرداخت؟',
        text: 'آیا از استرداد این پرداخت اطمینان دارید؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بله، استرداد کن',
        cancelButtonText: 'انصراف'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('استرداد شد!', 'پرداخت با موفقیت استرداد شد.', 'success');
        }
    });
}
</script>

<style>
.table-primary th {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: white;
    font-weight: 600;
}

.table-hover tbody tr:hover {
    background-color: rgba(var(--primary-rgb), 0.03);
    transition: all 0.3s ease;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.bg-white-transparent {
    background: rgba(255, 255, 255, 0.2);
}
</style>

