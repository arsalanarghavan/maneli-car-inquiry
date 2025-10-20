<!-- Start::row -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">مدیریت پرداخت‌ها</div>
                <div class="btn-list">
                    <button class="btn btn-primary">
                        <i class="la la-plus me-1"></i>
                        پرداخت جدید
                    </button>
                    <button class="btn btn-success">
                        <i class="la la-sync me-1"></i>
                        بروزرسانی وضعیت
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <select class="form-select" id="payment-status">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="completed">تکمیل شده</option>
                            <option value="pending">در انتظار</option>
                            <option value="failed">ناموفق</option>
                            <option value="cancelled">لغو شده</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="payment-gateway">
                            <option value="">همه درگاه‌ها</option>
                            <option value="zarinpal">زرین‌پال</option>
                            <option value="sadad">سداد</option>
                            <option value="bank">بانکی</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="payment-search" placeholder="جستجوی پرداخت...">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="filterPayments()">
                            <i class="la la-search me-1"></i> فیلتر
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="payments-table" class="table table-bordered text-nowrap w-100">
                        <thead>
                            <tr>
                                <th>شناسه پرداخت</th>
                                <th>شماره استعلام</th>
                                <th>مبلغ</th>
                                <th>درگاه پرداخت</th>
                                <th>وضعیت</th>
                                <th>تاریخ پرداخت</th>
                                <th>عملیات</th>
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
                                            <button class="btn btn-sm btn-primary-light" onclick="viewPaymentDetails(<?php echo $payment->post_id; ?>)">
                                                <i class="la la-eye"></i> جزئیات
                                            </button>
                                            <button class="btn btn-sm btn-success-light" onclick="refundPayment(<?php echo $payment->post_id; ?>)">
                                                <i class="la la-sync"></i> استرداد
                                            </button>
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

function viewPaymentDetails(paymentId) {
    // Open modal or redirect to payment details page
    console.log('Viewing payment details:', paymentId);
}

function refundPayment(paymentId) {
    if (confirm('آیا از استرداد این پرداخت اطمینان دارید؟')) {
        // AJAX call to process refund
        console.log('Refunding payment:', paymentId);
    }
}
</script>

