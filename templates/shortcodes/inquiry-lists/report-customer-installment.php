<?php
/**
 * Template for the Customer's view of a single Installment Inquiry Report.
 * Modern redesign with Bootstrap theme styling - similar to admin report but without admin controls.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @version 2.0.0 (Complete modern redesign for customers)
 *
 * @var int $inquiry_id The ID of the inquiry post.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Data Retrieval
$post = get_post($inquiry_id);
$post_meta = get_post_meta($inquiry_id);
$finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);

$inquiry_status = $post_meta['inquiry_status'][0] ?? 'pending';
$product_id = $post_meta['product_id'][0] ?? 0;
$car_name = get_the_title($product_id);
$cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;

$back_link = home_url('/dashboard/installment-inquiries');

// Loan Details
$down_payment = (int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0);
$total_price = (int)($post_meta['maneli_inquiry_total_price'][0] ?? 0);
$term_months = (int)($post_meta['maneli_inquiry_term_months'][0] ?? 0);
$installment_amount = (int)($post_meta['maneli_inquiry_installment'][0] ?? 0);
$loan_amount = $total_price - $down_payment;

// Tracking Status
$tracking_status = get_post_meta($inquiry_id, 'tracking_status', true) ?: 'new';
$tracking_status_label = Maneli_CPT_Handler::get_tracking_status_label($tracking_status);
$rejection_reason = get_post_meta($inquiry_id, 'rejection_reason', true);

// Issuer Type
$issuer_type = $post_meta['issuer_type'][0] ?? 'self';
?>

<!-- Back Button -->
<div class="mb-3 report-back-button-wrapper">
    <a href="<?php echo esc_url($back_link); ?>" class="btn btn-light btn-wave">
        <i class="la la-arrow-right me-1"></i>
        <?php esc_html_e('Back to List', 'maneli-car-inquiry'); ?>
    </a>
</div>

<!-- Header Card -->
        <div class="card custom-card">
            <div class="card-header">
        <div class="card-title">
            <i class="la la-file-invoice me-2"></i>
            <?php esc_html_e('Installment Request Details', 'maneli-car-inquiry'); ?>
            <small class="text-muted">(#<?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($inquiry_id) : esc_html($inquiry_id); ?>)</small>
        </div>
    </div>
    <div class="card-body">
        <!-- Status Alert -->
        <div class="alert alert-<?php echo esc_attr($tracking_status === 'completed' ? 'success' : ($tracking_status === 'rejected' || $tracking_status === 'cancelled' ? 'danger' : ($tracking_status === 'meeting_scheduled' ? 'info' : ($tracking_status === 'in_progress' || $tracking_status === 'follow_up_scheduled' ? 'primary' : ($tracking_status === 'referred' ? 'info' : 'warning'))))); ?>">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <strong><?php esc_html_e('Current Status:', 'maneli-car-inquiry'); ?></strong> 
                    <span class="badge bg-<?php echo esc_attr($tracking_status === 'completed' ? 'success' : ($tracking_status === 'rejected' || $tracking_status === 'cancelled' ? 'danger' : ($tracking_status === 'meeting_scheduled' ? 'info' : ($tracking_status === 'in_progress' || $tracking_status === 'follow_up_scheduled' ? 'primary' : ($tracking_status === 'referred' ? 'info' : 'warning'))))); ?>-transparent fs-14 ms-2">
                        <?php echo esc_html($tracking_status_label); ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($rejection_reason): ?>
        <!-- Rejection Reason -->
        <div class="alert alert-danger border-danger">
            <strong><i class="la la-exclamation-triangle me-1"></i><?php esc_html_e('Reason for Rejection:', 'maneli-car-inquiry'); ?></strong>
            <p class="mb-0 mt-2"><?php echo esc_html($rejection_reason); ?></p>
        </div>
        <?php endif; ?>

        <!-- Status Roadmap -->
        <div class="card custom-card mt-3">
            <div class="card-header bg-light">
                <div class="card-title">
                    <i class="la la-route me-2"></i>
                    <?php esc_html_e('Request Journey', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <?php
                // Define all possible statuses in order
                $all_statuses = [
                    'new' => ['label' => esc_html__('New', 'maneli-car-inquiry'), 'icon' => 'la-folder-open', 'color' => 'secondary'],
                    'referred' => ['label' => esc_html__('Referred', 'maneli-car-inquiry'), 'icon' => 'la-share', 'color' => 'info'],
                    'in_progress' => ['label' => esc_html__('در حال پیگیری', 'maneli-car-inquiry'), 'icon' => 'la-spinner', 'color' => 'primary'],
                    'follow_up_scheduled' => ['label' => esc_html__('Follow-up Scheduled', 'maneli-car-inquiry'), 'icon' => 'la-clock', 'color' => 'warning'],
                    'meeting_scheduled' => ['label' => esc_html__('Meeting Scheduled', 'maneli-car-inquiry'), 'icon' => 'la-calendar-check', 'color' => 'cyan'],
                ];
                
                // End statuses (completed or rejected)
                $end_statuses = [
                    'completed' => ['label' => esc_html__('Completed', 'maneli-car-inquiry'), 'icon' => 'la-check-circle', 'color' => 'success'],
                    'rejected' => ['label' => esc_html__('Rejected', 'maneli-car-inquiry'), 'icon' => 'la-times-circle', 'color' => 'danger'],
                    'cancelled' => ['label' => esc_html__('Cancelled', 'maneli-car-inquiry'), 'icon' => 'la-ban', 'color' => 'danger'],
                ];
                
                $current_status = $tracking_status;
                $status_reached = false;
                
                // Check if current status is an end status
                $is_end_status = in_array($current_status, ['completed', 'rejected', 'cancelled']);
                if ($is_end_status) {
                    $status_reached = true; // Mark all previous statuses as passed
                }
                ?>
                
                <!-- Main Flow -->
                <div class="status-roadmap mb-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <?php foreach ($all_statuses as $status_key => $status_info): 
                            $is_current = ($status_key === $current_status);
                            $is_passed = !$is_current && !$status_reached;
                            
                            if ($is_current) {
                                $status_reached = true;
                            }
                            
                            $opacity = $is_passed ? '1' : ($is_current ? '1' : '0.3');
                            $badge_class = $is_current ? 'bg-' . $status_info['color'] : ($is_passed ? 'bg-success-light' : 'bg-light text-muted');
                        ?>
                            <div class="status-step text-center maneli-status-step" style="opacity: <?php echo esc_attr($opacity); ?>; flex: 1; position: relative;">
                                <?php if ($is_current): ?>
                                    <div class="pulse-ring"></div>
                                <?php endif; ?>
                                <div class="mb-2">
                                    <span class="avatar avatar-md <?php echo esc_attr($badge_class); ?> rounded-circle status-icon-wrapper">
                                        <span class="status-icon-loading">
                                            <span class="spinner-border spinner-border-sm text-white" role="status"></span>
                                        </span>
                                        <i class="la <?php echo esc_attr($status_info['icon']); ?> fs-20 status-icon"></i>
                                    </span>
                                </div>
                                <small class="d-block fw-semibold <?php echo esc_attr($is_current ? 'text-' . $status_info['color'] : ''); ?>">
                                    <?php echo esc_html($status_info['label']); ?>
                                </small>
                                <?php if ($is_current): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-<?php echo esc_attr($status_info['color']); ?>-transparent fs-11">
                                            <i class="la la-map-marker me-1"></i><?php esc_html_e('Current Status', 'maneli-car-inquiry'); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="status-arrow maneli-status-arrow" style="opacity: <?php echo esc_attr($opacity); ?>;">
                                <i class="la la-arrow-left fs-18 text-muted"></i>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- End Status: Completed or Rejected -->
                        <?php 
                        $end_status_info = isset($end_statuses[$current_status]) ? $end_statuses[$current_status] : $end_statuses['completed'];
                        
                        $end_opacity = $is_end_status ? '1' : '0.3';
                        $end_badge_class = $is_end_status ? 'bg-' . $end_status_info['color'] : 'bg-light text-muted';
                        ?>
                        <div class="status-step text-center maneli-status-step" style="opacity: <?php echo esc_attr($end_opacity); ?>; flex: 1; position: relative;">
                            <?php if ($is_end_status): ?>
                                <div class="pulse-ring"></div>
                            <?php endif; ?>
                            <div class="mb-2">
                                <span class="avatar avatar-md <?php echo esc_attr($end_badge_class); ?> rounded-circle status-icon-wrapper">
                                    <span class="status-icon-loading">
                                        <span class="spinner-border spinner-border-sm text-white" role="status"></span>
                                    </span>
                                    <i class="la <?php echo esc_attr($end_status_info['icon']); ?> fs-20 status-icon"></i>
                                </span>
                            </div>
                            <small class="d-block fw-semibold <?php echo esc_attr($is_end_status ? 'text-' . $end_status_info['color'] : ''); ?>">
                                <?php echo $is_end_status ? esc_html($end_status_info['label']) : esc_html__('Completed / Rejected', 'maneli-car-inquiry'); ?>
                            </small>
                            <?php if ($is_end_status): ?>
                                <div class="mt-1">
                                    <span class="badge bg-<?php echo esc_attr($end_status_info['color']); ?>-transparent fs-11">
                                        <i class="la la-map-marker me-1"></i><?php esc_html_e('Current Status', 'maneli-car-inquiry'); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loan & Car Information -->
<div class="card custom-card mt-3">
    <div class="card-header">
        <div class="card-title">
            <i class="la la-info-circle me-2"></i>
            <?php esc_html_e('Loan and Car Details', 'maneli-car-inquiry'); ?>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <?php 
                $car_image = get_the_post_thumbnail($product_id, 'medium', ['class' => 'img-fluid rounded shadow-sm']);
                if ($car_image) {
                    echo wp_kses_post($car_image);
                } else {
                    echo '<div class="bg-light rounded d-flex align-items-center justify-content-center text-muted maneli-placeholder-image">
                        <div class="text-center">
                            <i class="la la-image fs-40"></i>
                            <p class="mb-0 mt-2">' . esc_html__('No Image', 'maneli-car-inquiry') . '</p>
                        </div>
                    </div>';
                }
                ?>
            </div>
            <div class="col-md-8">
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                            <tbody>
                                <tr>
                                <td class="fw-semibold bg-light" width="40%"><?php esc_html_e('Selected Car', 'maneli-car-inquiry'); ?></td>
                                <td><a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank" class="text-primary"><?php echo esc_html($car_name); ?> <i class="la la-external-link-alt"></i></a></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold bg-light"><?php esc_html_e('Total Price', 'maneli-car-inquiry'); ?></td>
                                <td><strong class="text-success"><?php echo function_exists('persian_numbers') ? persian_numbers(Maneli_Render_Helpers::format_money($total_price)) : Maneli_Render_Helpers::format_money($total_price); ?></strong> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold bg-light"><?php esc_html_e('Down Payment', 'maneli-car-inquiry'); ?></td>
                                <td><strong class="text-info"><?php echo function_exists('persian_numbers') ? persian_numbers(Maneli_Render_Helpers::format_money($down_payment)) : Maneli_Render_Helpers::format_money($down_payment); ?></strong> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold bg-light"><?php esc_html_e('Loan Amount', 'maneli-car-inquiry'); ?></td>
                                <td><strong class="text-warning"><?php echo function_exists('persian_numbers') ? persian_numbers(Maneli_Render_Helpers::format_money($loan_amount)) : Maneli_Render_Helpers::format_money($loan_amount); ?></strong> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold bg-light"><?php esc_html_e('Installment Term', 'maneli-car-inquiry'); ?></td>
                                <td><span class="badge bg-secondary-transparent"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($term_months) : absint($term_months); ?> <?php esc_html_e('Months', 'maneli-car-inquiry'); ?></span></td>
                                </tr>
                                <tr>
                                <td class="fw-semibold bg-light"><?php esc_html_e('Monthly Installment', 'maneli-car-inquiry'); ?></td>
                                <td><strong class="text-primary fs-16"><?php echo function_exists('persian_numbers') ? persian_numbers(Maneli_Render_Helpers::format_money($installment_amount)) : Maneli_Render_Helpers::format_money($installment_amount); ?></strong> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                                </tr>
                                <tr>
                                <td class="fw-semibold bg-light"><?php esc_html_e('Date Submitted', 'maneli-car-inquiry'); ?></td>
                                <td><?php 
                                    $formatted_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($post->post_date, 'Y/m/d H:i');
                                    echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($formatted_date) : $formatted_date;
                                ?></td>
                                </tr>
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
                    </div>
                </div>

<!-- Buyer Information Card -->
<div class="card custom-card mt-3">
    <div class="card-header">
        <div class="card-title">
            <i class="la la-user me-2"></i>
            <?php esc_html_e('Buyer Information', 'maneli-car-inquiry'); ?>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('First Name', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['first_name'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Last Name', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['last_name'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Father\'s Name', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['father_name'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('National Code', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['national_code'][0] ?? '—') : esc_html($post_meta['national_code'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Date of Birth', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php 
                        $birth_date = $post_meta['birth_date'][0] ?? '—';
                        if ($birth_date && $birth_date !== '—') {
                            // Try to convert if it's in Gregorian format
                            if (strpos($birth_date, '/') !== false || strpos($birth_date, '-') !== false) {
                                $birth_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($birth_date, 'Y/m/d');
                            }
                            $birth_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($birth_date) : $birth_date;
                        }
                        echo esc_html($birth_date);
                    ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold">
                        <a href="tel:<?php echo esc_attr($post_meta['mobile_number'][0] ?? ''); ?>" class="text-primary">
                            <i class="la la-phone me-1"></i><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['mobile_number'][0] ?? '—') : esc_html($post_meta['mobile_number'][0] ?? '—'); ?>
                        </a>
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Phone Number', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['phone_number'][0] ?? '—') : esc_html($post_meta['phone_number'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Email', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['email'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Job Type', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html(Maneli_Render_Helpers::translate_field_value('job_type', $post_meta['job_type'][0] ?? '—')); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Occupation', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['occupation'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Income Level', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php 
                        $income_level = $post_meta['income_level'][0] ?? '—';
                        if ($income_level && $income_level !== '—') {
                            // If it's a number, format it with Persian number separators
                            $income_level = function_exists('persian_numbers') ? persian_numbers(number_format($income_level, 0, '.', ',')) : $income_level;
                        }
                        echo esc_html($income_level);
                    ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Residency Status', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html(Maneli_Render_Helpers::translate_field_value('residency_status', $post_meta['residency_status'][0] ?? '—')); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Workplace Status', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html(Maneli_Render_Helpers::translate_field_value('workplace_status', $post_meta['workplace_status'][0] ?? '—')); ?></p>
                </div>
                        </div>
            <div class="col-12">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Address', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['address'][0] ?? '—'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($issuer_type === 'self'): ?>
<!-- Bank Information Card (Self) -->
<div class="card custom-card mt-3">
    <div class="card-header bg-success-transparent">
        <div class="card-title">
            <i class="la la-university me-2"></i>
            <?php esc_html_e('Bank Information (Cheque Holder)', 'maneli-car-inquiry'); ?>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Bank Name', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['bank_name'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Account Number', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['account_number'][0] ?? '—') : esc_html($post_meta['account_number'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Branch Code', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['branch_code'][0] ?? '—') : esc_html($post_meta['branch_code'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Branch Name', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['branch_name'][0] ?? '—'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($issuer_type === 'other'): ?>
<!-- Issuer Information Card (Other Person) -->
<div class="card custom-card mt-3">
    <div class="card-header bg-warning-transparent">
        <div class="card-title">
            <i class="la la-user-friends me-2"></i>
            <?php esc_html_e('Cheque Issuer Information', 'maneli-car-inquiry'); ?>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('First Name', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_full_name'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Last Name', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_last_name'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Father\'s Name', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_father_name'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('National Code', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['issuer_national_code'][0] ?? '—') : esc_html($post_meta['issuer_national_code'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Date of Birth', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php 
                        $issuer_birth_date = $post_meta['issuer_birth_date'][0] ?? '—';
                        if ($issuer_birth_date && $issuer_birth_date !== '—') {
                            // Try to convert if it's in Gregorian format
                            if (strpos($issuer_birth_date, '/') !== false || strpos($issuer_birth_date, '-') !== false) {
                                $issuer_birth_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($issuer_birth_date, 'Y/m/d');
                            }
                            $issuer_birth_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($issuer_birth_date) : $issuer_birth_date;
                        }
                        echo esc_html($issuer_birth_date);
                    ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold">
                        <a href="tel:<?php echo esc_attr($post_meta['issuer_mobile_number'][0] ?? ''); ?>" class="text-primary">
                            <i class="la la-phone me-1"></i><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['issuer_mobile_number'][0] ?? '—') : esc_html($post_meta['issuer_mobile_number'][0] ?? '—'); ?>
                        </a>
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Phone Number', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['issuer_phone_number'][0] ?? '—') : esc_html($post_meta['issuer_phone_number'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Job Type', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html(Maneli_Render_Helpers::translate_field_value('job_type', $post_meta['issuer_job_type'][0] ?? '—')); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Occupation', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_occupation'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Residency Status', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html(Maneli_Render_Helpers::translate_field_value('residency_status', $post_meta['issuer_residency_status'][0] ?? '—')); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Workplace Status', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html(Maneli_Render_Helpers::translate_field_value('workplace_status', $post_meta['issuer_workplace_status'][0] ?? '—')); ?></p>
                </div>
            </div>
            <div class="col-12">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Address', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_address'][0] ?? '—'); ?></p>
                </div>
            </div>
        </div>

        <!-- Issuer Bank Information -->
        <h5 class="mt-4 mb-3 pt-3 border-top fw-semibold">
            <i class="la la-university text-success me-1"></i>
            <?php esc_html_e('Bank Information (Cheque Holder)', 'maneli-car-inquiry'); ?>
        </h5>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Bank Name', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_bank_name'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Account Number', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['issuer_account_number'][0] ?? '—') : esc_html($post_meta['issuer_account_number'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Branch Code', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['issuer_branch_code'][0] ?? '—') : esc_html($post_meta['issuer_branch_code'][0] ?? '—'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-3 rounded bg-light">
                    <small class="text-muted"><?php esc_html_e('Branch Name', 'maneli-car-inquiry'); ?></small>
                    <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_branch_name'][0] ?? '—'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Credit Verification Card (Finotex) -->
<div class="card custom-card mt-3">
    <div class="card-header bg-primary-transparent">
        <div class="card-title">
            <i class="la la-shield-alt me-2"></i>
            <?php esc_html_e('Credit Verification Result (Finotex)', 'maneli-car-inquiry'); ?>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($finotex_data) || ($finotex_data['status'] ?? '') === 'SKIPPED'): ?>
            <div class="alert alert-warning border-warning">
                <i class="la la-exclamation-triangle me-2"></i>
                <?php esc_html_e('Bank inquiry was not performed or failed to return data.', 'maneli-car-inquiry'); ?>
            </div>
        <?php else: ?>
            <?php echo Maneli_Render_Helpers::render_cheque_status_info($cheque_color_code); ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Status Roadmap Styles */
.status-roadmap {
    padding: 20px 10px;
}

.status-step {
    min-width: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
}

.status-arrow {
    padding: 0 15px;
    display: flex;
    align-items: center;
}

/* Make inactive statuses more visible */
.status-step[style*="opacity: 0.3"] {
    opacity: 0.5 !important;
}

.status-step[style*="opacity: 0.3"] .avatar {
    background-color: #e9ecef !important;
}

/* Status Icon Loading Animation */
.status-icon-wrapper {
    position: relative;
}

.status-icon-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: block;
}

.status-icon {
    display: block;
    position: relative;
    z-index: 1;
}

.status-icon-wrapper .status-icon-loading {
    display: none;
}

.status-icon-wrapper:not(.loaded) .status-icon {
    display: none;
}

.status-icon-wrapper:not(.loaded) .status-icon-loading {
    display: block;
}

/* Pulse Animation for Current Status */
.pulse-ring {
    position: absolute;
    top: -5px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 60px;
    border: 3px solid var(--primary-color);
    border-radius: 50%;
    animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
    z-index: -1;
}

@keyframes pulse-ring {
    0% {
        transform: translateX(-50%) scale(0.9);
        opacity: 1;
    }
    50% {
        transform: translateX(-50%) scale(1.1);
        opacity: 0.7;
    }
    100% {
        transform: translateX(-50%) scale(0.9);
        opacity: 1;
    }
}

/* Responsive roadmap for mobile */
@media (max-width: 768px) {
    .status-roadmap .d-flex {
        flex-direction: column !important;
    }
    
    .status-arrow {
        transform: rotate(90deg);
        margin: 10px 0;
    }
    
    .status-arrow i {
        transform: rotate(-90deg);
    }
}
</style>

<script>
// Mark all status icons as loaded when the page loads
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        document.querySelectorAll('.status-icon-wrapper').forEach(function(wrapper) {
            wrapper.classList.add('loaded');
        });
    }, 500);
});
</script>
