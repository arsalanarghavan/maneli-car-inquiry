<?php
/**
 * Template for the Customer's view of their Cash Inquiry List.
 * Modern redesign with Bootstrap theme styling.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @version 2.0.0 (Modern redesign)
 *
 * @var WP_Query $inquiries_query The WP_Query object for the user's cash inquiries.
 * @var string   $current_url     The base URL for generating action links.
 * @var string|null $payment_status Status from a payment gateway redirect.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header bg-warning-transparent">
                <div class="card-title">
                    <i class="la la-dollar-sign me-2 fs-20"></i>
                    استعلامات خرید نقدی من
                </div>
            </div>
            <div class="card-body">
                <?php
                // Display payment status message
                if (isset($payment_status)) {
                    $reason = isset($_GET['reason']) ? sanitize_text_field(urldecode($_GET['reason'])) : '';
                    if ($payment_status === 'success') {
                        echo '<div class="alert alert-success border-success d-flex align-items-center"><i class="la la-check-circle fs-20 me-2"></i><div><strong>موفق!</strong> پرداخت با موفقیت انجام شد.</div></div>';
                    } elseif ($payment_status === 'failed') {
                        echo '<div class="alert alert-danger border-danger d-flex align-items-center"><i class="la la-times-circle fs-20 me-2"></i><div><strong>ناموفق!</strong> پرداخت انجام نشد. ' . esc_html($reason) . '</div></div>';
                    }
                }
                ?>

                <div class="alert alert-info border-info d-flex align-items-start" role="alert">
                    <i class="la la-info-circle fs-20 me-2 mt-1"></i>
                    <div>
                        <strong>توجه:</strong>
                        قیمت‌های اعلام شده تقریبی هستند. قیمت نهایی بر اساس نرخ روز بازار در زمان پرداخت پیش‌پرداخت تعیین خواهد شد.
                    </div>
                </div>

                <?php if (!$inquiries_query->have_posts()): ?>
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="la la-inbox" style="font-size: 80px; color: #dee2e6;"></i>
                        </div>
                        <h5 class="text-muted mb-2">هنوز استعلام نقدی ثبت نکرده‌اید</h5>
                        <p class="text-muted mb-4">برای خرید نقدی خودرو، اولین استعلام خود را ثبت کنید.</p>
                        <a href="<?php echo home_url('/cash-inquiry'); ?>" class="btn btn-primary btn-wave">
                            <i class="la la-plus me-1"></i>
                            ثبت درخواست نقدی جدید
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-warning">
                                <tr>
                                    <th><i class="la la-hashtag me-1"></i>شناسه</th>
                                    <th><i class="la la-car me-1"></i>خودرو</th>
                                    <th><i class="la la-info-circle me-1"></i>وضعیت</th>
                                    <th><i class="la la-calendar me-1"></i>تاریخ ثبت</th>
                                    <th><i class="la la-wrench me-1"></i>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($inquiries_query->have_posts()): $inquiries_query->the_post(); ?>
                                    <?php
                                    $inquiry_id = get_the_ID();
                                    $product_id = get_post_meta($inquiry_id, 'product_id', true);
                                    $status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
                                    $expert_status = get_post_meta($inquiry_id, 'expert_status', true);
                                    $report_url = add_query_arg('cash_inquiry_id', $inquiry_id, $current_url);
                                    $expert_status_info = Maneli_Render_Helpers::get_expert_status_info($expert_status);
                                    
                                    $status_data = [
                                        'pending' => ['label' => 'در انتظار بررسی', 'class' => 'warning'],
                                        'approved' => ['label' => 'تایید شده', 'class' => 'success'],
                                        'awaiting_payment' => ['label' => 'در انتظار پرداخت', 'class' => 'info'],
                                        'completed' => ['label' => 'تکمیل شده', 'class' => 'success'],
                                        'rejected' => ['label' => 'رد شده', 'class' => 'danger'],
                                    ];
                                    $badge = $status_data[$status] ?? ['label' => 'نامشخص', 'class' => 'secondary'];
                                    
                                    // Convert to Jalali
                                    $timestamp = strtotime(get_the_date('Y-m-d', $inquiry_id));
                                    if (function_exists('maneli_gregorian_to_jalali')) {
                                        $date = maneli_gregorian_to_jalali(
                                            date('Y', $timestamp),
                                            date('m', $timestamp),
                                            date('d', $timestamp),
                                            'Y/m/d'
                                        );
                                    } else {
                                        $date = get_the_date('Y/m/d', $inquiry_id);
                                    }
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo esc_html($inquiry_id); ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="la la-car text-warning me-2 fs-18"></i>
                                                <span><?php echo esc_html(get_the_title($product_id)); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $badge['class']; ?>">
                                                <?php echo $badge['label']; ?>
                                            </span>
                                            <?php if ($expert_status_info): ?>
                                                <br><span class="badge mt-1" style="background-color: <?php echo esc_attr($expert_status_info['color']); ?>; color: white;">
                                                    <?php echo esc_html($expert_status_info['label']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($date); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url($report_url); ?>" class="btn btn-sm btn-primary-light">
                                                <i class="la la-eye me-1"></i>
                                                مشاهده جزئیات
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($inquiries_query->max_num_pages > 1): ?>
                        <div class="mt-4 text-center">
                            <nav>
                                <?php
                                echo paginate_links([
                                    'total' => $inquiries_query->max_num_pages,
                                    'current' => max(1, get_query_var('paged')),
                                    'prev_text' => '<i class="la la-angle-right"></i> قبلی',
                                    'next_text' => 'بعدی <i class="la la-angle-left"></i>',
                                    'type' => 'plain',
                                    'before_page_number' => '<span class="btn btn-sm btn-light mx-1">',
                                    'after_page_number' => '</span>',
                                ]);
                                ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        </div>
    </div>
</div>

<style>
.table-warning th {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: white;
    font-weight: 600;
}

.table-hover tbody tr:hover {
    background-color: rgba(255, 193, 7, 0.05);
    transform: scale(1.005);
    transition: all 0.3s ease;
}

.la-inbox {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
</style>
