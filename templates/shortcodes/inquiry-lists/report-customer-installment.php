<?php
/**
 * Template for the Customer's view of a single Installment Inquiry Report (Final Step).
 *
 * این تمپلیت برای سازگاری با تمپلیت اصلی گزارش نهایی مشتری (step-5-final-report.php) و استفاده از تابع
 * کمکی Maneli_Render_Helpers::render_cheque_status_info برای نمایش وضعیت اعتبارسنجی، یکسان سازی شده است.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Gemini
 * @version 1.0.1 (Unified with step-5-final-report)
 *
 * @var int $inquiry_id The ID of the inquiry post.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fetch all necessary data for the report
$status = get_post_meta($inquiry_id, 'inquiry_status', true);
$post_meta = get_post_meta($inquiry_id);
$product_id = $post_meta['product_id'][0] ?? 0;
$car_name = get_the_title($product_id);
$finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
$cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-list-alt me-2"></i>
                    <?php esc_html_e('Step 5: Final Result', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <?php if ($status === 'user_confirmed'): ?>
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="la la-check-circle fs-4 me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-1"><?php esc_html_e('Final Result: Your request has been approved.', 'maneli-car-inquiry'); ?></h5>
                            <p class="mb-0"><?php esc_html_e('Your request has been approved by our experts and referred to the sales unit. One of our colleagues will contact you soon for final coordination.', 'maneli-car-inquiry'); ?></p>
                        </div>
                    </div>
                <?php elseif ($status === 'rejected'): 
                    $reason = get_post_meta($inquiry_id, 'rejection_reason', true);
                ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="la la-times-circle fs-4 me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-1"><?php esc_html_e('Final Result: Your request has been rejected.', 'maneli-car-inquiry'); ?></h5>
                            <?php if (!empty($reason)): ?>
                                <p class="mb-0"><strong><?php esc_html_e('Reason:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($reason); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <h5 class="mb-3"><?php esc_html_e('Request Summary', 'maneli-car-inquiry'); ?></h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td class="fw-semibold bg-light"><?php esc_html_e('Selected Car:', 'maneli-car-inquiry'); ?></td>
                                    <td><?php echo esc_html($car_name); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold bg-light"><?php esc_html_e('Down Payment:', 'maneli-car-inquiry'); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0))); ?> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold bg-light"><?php esc_html_e('Installment Term:', 'maneli-car-inquiry'); ?></td>
                                    <td><?php echo esc_html($post_meta['maneli_inquiry_term_months'][0] ?? 0); ?> <?php esc_html_e('Months', 'maneli-car-inquiry'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    <h5 class="mb-3"><?php esc_html_e('Credit Verification Result', 'maneli-car-inquiry'); ?></h5>
                    <?php if (empty($finotex_data) || (isset($finotex_data['status']) && $finotex_data['status'] === 'SKIPPED')): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Sayad Cheque Status:', 'maneli-car-inquiry'); ?></td>
                                        <td><?php esc_html_e('Undetermined', 'maneli-car-inquiry'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Status Explanation:', 'maneli-car-inquiry'); ?></td>
                                        <td><?php esc_html_e('Bank inquiry was not performed.', 'maneli-car-inquiry'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php else:
                        // استفاده از تابع کمکی برای نمایش نوار وضعیت
                        echo Maneli_Render_Helpers::render_cheque_status_info($cheque_color_code);
                    endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
