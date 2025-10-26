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
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <h6 class="mb-3 fw-semibold">
                                <i class="la la-car text-primary me-1"></i>
                                <?php esc_html_e('Product Image', 'maneli-car-inquiry'); ?>
                            </h6>
                            <div class="product-image-container">
                                <?php 
                                $car_image = get_the_post_thumbnail($product_id, 'medium', ['class' => 'img-fluid rounded shadow-sm product-image']);
                                if ($car_image) {
                                    echo $car_image;
                                } else {
                                    echo '<div class="bg-light rounded d-flex align-items-center justify-content-center text-muted product-image-placeholder">
                                        <div class="text-center">
                                            <i class="la la-image fs-40"></i>
                                            <p class="mb-0 mt-2">بدون تصویر</p>
                                        </div>
                                    </div>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6 class="mb-3 fw-semibold">
                                <i class="la la-info-circle text-primary me-1"></i>
                                <?php esc_html_e('Request Details', 'maneli-car-inquiry'); ?>
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped mb-0 product-details-table">
                                    <tbody>
                                        <tr>
                                            <td class="fw-semibold bg-light" width="40%"><?php esc_html_e('Selected Car:', 'maneli-car-inquiry'); ?></td>
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

<style>
/* Product Image and Table Layout */
.product-image-container {
    display: flex;
    flex-direction: column;
}

.product-image,
.product-image-placeholder {
    width: 100%;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.product-image-placeholder {
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px dashed #dee2e6;
    background: #f8f9fa;
}

/* Make image height match table height dynamically */
.product-image {
    height: auto;
    max-height: 100%;
}

/* Responsive design for mobile */
@media (max-width: 768px) {
    /* On mobile, stack image and table vertically */
    .product-image-container {
        margin-bottom: 20px;
    }
    
    .product-image,
    .product-image-placeholder {
        height: 200px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Match image height with table height
    const imageContainer = document.querySelector('.product-image-container');
    const tableContainer = document.querySelector('.product-details-table');
    
    if (imageContainer && tableContainer) {
        function matchHeights() {
            const tableHeight = tableContainer.offsetHeight;
            const image = imageContainer.querySelector('.product-image');
            if (image) {
                image.style.height = tableHeight + 'px';
            }
        }
        
        // Match heights on load and resize
        matchHeights();
        window.addEventListener('resize', matchHeights);
    }
});
</script>
