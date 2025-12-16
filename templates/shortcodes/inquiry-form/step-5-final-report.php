<?php
/**
 * Template for Step 5 of the inquiry form (Final Result) - Customer view of installment inquiry.
 * Modern redesign with Bootstrap theme styling.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @version 2.0.0 (Modern redesign)
 *
 * @var int $inquiry_id The ID of the inquiry post.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fetch all necessary data for the report
$status = get_post_meta($inquiry_id, 'inquiry_status', true);
$expert_status = get_post_meta($inquiry_id, 'expert_status', true);
$post_meta = get_post_meta($inquiry_id);
$product_id = $post_meta['product_id'][0] ?? 0;
$car_name = get_the_title($product_id);
$down_payment = (int)($post_meta['autopuzzle_inquiry_down_payment'][0] ?? 0);
$term_months = $post_meta['autopuzzle_inquiry_term_months'][0] ?? 0;
$rejection_reason = get_post_meta($inquiry_id, 'rejection_reason', true);
$finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
$cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;
$expert_status_info = Autopuzzle_Render_Helpers::get_expert_status_info($expert_status);

// Product data
$product = wc_get_product($product_id);
$product_image = $product ? wp_get_attachment_url($product->get_image_id()) : '';

// Status badge
$status_data = [
    'pending' => ['label' => esc_html__('Pending Review', 'autopuzzle'), 'class' => 'warning', 'icon' => 'clock'],
    'user_confirmed' => ['label' => esc_html__('Confirmed and Referred', 'autopuzzle'), 'class' => 'success', 'icon' => 'check-circle'],
    'approved' => ['label' => esc_html__('Final Approved', 'autopuzzle'), 'class' => 'success', 'icon' => 'check-double'],
    'rejected' => ['label' => esc_html__('Rejected', 'autopuzzle'), 'class' => 'danger', 'icon' => 'times-circle'],
    'more_docs' => ['label' => esc_html__('Need More Documents', 'autopuzzle'), 'class' => 'warning', 'icon' => 'file-upload'],
];
$badge = $status_data[$status] ?? ['label' => esc_html__('Unknown', 'autopuzzle'), 'class' => 'secondary', 'icon' => 'question-circle'];
?>

<div class="mb-3">
    <a href="<?php echo esc_url(home_url('/dashboard/inquiries/installment')); ?>" class="btn btn-light btn-wave">
        <i class="la la-arrow-right me-1"></i>
        <?php esc_html_e('Back to List', 'autopuzzle'); ?>
    </a>
</div>

<div class="card custom-card">
    <div class="card-header bg-info-transparent">
        <div class="card-title">
            <i class="la la-credit-card me-2 fs-20"></i>
            <?php esc_html_e('Final Installment Inquiry Result', 'autopuzzle'); ?>
            <small class="text-muted">(#<?php echo esc_html($inquiry_id); ?>)</small>
        </div>
    </div>
    <div class="card-body">
        <!-- Status Alert -->
        <div class="alert alert-<?php echo esc_attr($badge['class']); ?> border-<?php echo esc_attr($badge['class']); ?>">
            <div class="d-flex align-items-center">
                <i class="la la-<?php echo esc_attr($badge['icon']); ?> fs-32 me-3"></i>
                <div class="flex-fill">
                    <h5 class="alert-heading mb-2">
                        <?php esc_html_e('Current Status:', 'autopuzzle'); ?> 
                        <span class="badge bg-<?php echo esc_attr($badge['class']); ?> ms-2">
                            <?php echo esc_html($badge['label']); ?>
                        </span>
                    </h5>
                    <?php if ($status === 'user_confirmed'): ?>
                        <p class="mb-0"><?php esc_html_e('Your request has been approved by our experts and referred to the sales department. One of our colleagues will contact you soon for final coordination.', 'autopuzzle'); ?></p>
                    <?php elseif ($status === 'rejected'): ?>
                        <p class="mb-0"><?php esc_html_e('Unfortunately, your request has been rejected.', 'autopuzzle'); ?></p>
                    <?php elseif ($status === 'more_docs'): ?>
                        <p class="mb-0"><?php esc_html_e('Please send the additional documents requested.', 'autopuzzle'); ?></p>
                    <?php else: ?>
                        <p class="mb-0"><?php esc_html_e('Your request is under review. Please wait.', 'autopuzzle'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($expert_status_info): ?>
            <!-- Expert Status -->
            <div class="alert alert-light border">
                <div class="d-flex align-items-center">
                    <i class="la la-user-tie fs-24 me-2" style="color: <?php echo esc_attr($expert_status_info['color']); ?>;"></i>
                    <div>
                        <strong><?php esc_html_e('Expert Status:', 'autopuzzle'); ?></strong>
                        <span class="badge ms-2" style="background-color: <?php echo esc_attr($expert_status_info['color']); ?>; color: white;">
                            <?php echo esc_html($expert_status_info['label']); ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($rejection_reason): ?>
            <!-- Rejection Reason -->
            <div class="alert alert-danger border-danger">
                <i class="la la-exclamation-triangle me-2"></i>
                <strong><?php esc_html_e('Rejection Reason:', 'autopuzzle'); ?></strong>
                <p class="mb-0 mt-2"><?php echo esc_html($rejection_reason); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($status === 'more_docs'): ?>
            <?php 
            $requested_docs = get_post_meta($inquiry_id, 'requested_documents', true) ?: [];
            $uploaded_docs = get_post_meta($inquiry_id, 'uploaded_documents', true) ?: [];
            $doc_request_status = get_post_meta($inquiry_id, 'document_request_status', true);
            ?>
            <!-- Document Upload Section -->
            <div class="card border-warning mb-4">
                <div class="card-header bg-warning-transparent">
                    <h6 class="card-title mb-0">
                        <i class="la la-file-upload text-warning me-2"></i>
                        <?php esc_html_e('Upload Required Documents', 'autopuzzle'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <p class="alert alert-info border-info mb-3">
                        <i class="la la-info-circle me-2"></i>
                        <?php esc_html_e('Please upload the following documents:', 'autopuzzle'); ?>
                    </p>
                    
                    <?php if (!empty($requested_docs)): ?>
                        <div class="document-upload-list">
                            <?php foreach ($requested_docs as $doc_name): ?>
                                <?php 
                                // Check if this document has been uploaded
                                $is_uploaded = false;
                                $uploaded_file_url = '';
                                foreach ($uploaded_docs as $uploaded) {
                                    if (isset($uploaded['name']) && $uploaded['name'] === $doc_name) {
                                        $is_uploaded = true;
                                        $uploaded_file_url = $uploaded['file'] ?? '';
                                        break;
                                    }
                                }
                                ?>
                                <div class="border rounded p-3 mb-3 document-item" data-doc-name="<?php echo esc_attr($doc_name); ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-fill">
                                            <label class="fw-semibold mb-2 d-block">
                                                <?php echo esc_html($doc_name); ?>
                                            </label>
                                            <?php if ($is_uploaded): ?>
                                                <div class="alert alert-success border-success py-2 px-3 mb-0">
                                                    <i class="la la-check-circle me-2"></i>
                                                    <?php esc_html_e('Uploaded', 'autopuzzle'); ?>
                                                    <?php if ($uploaded_file_url): ?>
                                                        <a href="<?php echo esc_url($uploaded_file_url); ?>" target="_blank" class="btn btn-sm btn-success ms-2">
                                                            <i class="la la-download"></i> <?php esc_html_e('Download', 'autopuzzle'); ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <input type="file" 
                                                       accept=".pdf,.jpg,.jpeg,.png" 
                                                       class="form-control doc-file-input" 
                                                       data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>"
                                                       data-doc-name="<?php echo esc_attr($doc_name); ?>">
                                                <small class="text-muted d-block mt-1">
                                                    <?php esc_html_e('Accepted formats: PDF, JPG, PNG', 'autopuzzle'); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted"><?php esc_html_e('No documents have been requested yet.', 'autopuzzle'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Request Summary Card -->
        <div class="card border mb-4">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">
                    <i class="la la-file-alt text-primary me-2"></i>
                    <?php esc_html_e('Request Summary', 'autopuzzle'); ?>
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if ($product_image): ?>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($car_name); ?>" class="img-fluid rounded shadow-sm">
                        </div>
                    <?php endif; ?>
                    <div class="col-md-<?php echo esc_attr($product_image ? '8' : '12'); ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-car me-1"></i>
                                        <?php esc_html_e('Selected Car', 'autopuzzle'); ?>
                                    </div>
                                    <strong class="fs-16"><?php echo esc_html($car_name); ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-success-transparent">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-money-bill me-1"></i>
                                        <?php esc_html_e('Down Payment', 'autopuzzle'); ?>
                                    </div>
                                    <strong class="fs-16 text-success"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($down_payment) : number_format_i18n($down_payment); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-info-transparent">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-calendar me-1"></i>
                                        <?php esc_html_e('Installment Period', 'autopuzzle'); ?>
                                    </div>
                                    <strong class="fs-16 text-info"><?php echo esc_html($term_months); ?> <?php esc_html_e('Months', 'autopuzzle'); ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-clock me-1"></i>
                                        <?php esc_html_e('Registration Date', 'autopuzzle'); ?>
                                    </div>
                                    <strong class="fs-16">
                                        <?php 
                                        $inquiry = get_post($inquiry_id);
                                        $timestamp = strtotime($inquiry->post_date);
                                        if (function_exists('autopuzzle_gregorian_to_jalali')) {
                                            echo autopuzzle_gregorian_to_jalali(
                                                date('Y', $timestamp),
                                                date('m', $timestamp),
                                                date('d', $timestamp),
                                                'Y/m/d'
                                            );
                                        } else {
                                            echo date('Y/m/d', $timestamp);
                                        }
                                        ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Credit Verification Card -->
        <div class="card border mb-4">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">
                    <i class="la la-shield-alt text-success me-2"></i>
                    <?php esc_html_e('Credit Verification Result', 'autopuzzle'); ?>
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($finotex_data) || (isset($finotex_data['status']) && $finotex_data['status'] === 'SKIPPED')): ?>
                    <div class="alert alert-secondary">
                        <i class="la la-info-circle me-2"></i>
                        <strong><?php esc_html_e('Cheque Status:', 'autopuzzle'); ?></strong> <?php esc_html_e('Unknown', 'autopuzzle'); ?>
                        <br>
                        <small class="text-muted"><?php esc_html_e('Bank inquiry has not been performed.', 'autopuzzle'); ?></small>
                    </div>
                <?php else: ?>
                    <?php echo Autopuzzle_Render_Helpers::render_cheque_status_info($cheque_color_code); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Success Message -->
        <?php if ($status === 'user_confirmed' || $status === 'approved'): ?>
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="la la-check-circle text-success" style="font-size: 60px;"></i>
                    <h5 class="text-success mt-3"><?php esc_html_e('Congratulations! Your request has been approved', 'autopuzzle'); ?></h5>
                    <p class="text-muted"><?php esc_html_e('Our colleagues will contact you soon for final coordination.', 'autopuzzle'); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div class="text-center mt-4 pt-3 border-top">
            <a href="<?php echo esc_url(home_url('/dashboard/inquiries/installment')); ?>" class="btn btn-light btn-wave">
                <i class="la la-arrow-right me-1"></i>
                <?php esc_html_e('Back to Inquiries List', 'autopuzzle'); ?>
            </a>
        </div>
    </div>
</div>

<style>
.bg-info-transparent {
    background: linear-gradient(135deg, rgba(23, 162, 184, 0.1) 0%, transparent 100%);
}

.bg-success-transparent {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, transparent 100%);
}
</style>

<script type="text/javascript">
(function() {
    function waitForJQuery() {
        if (typeof jQuery !== "undefined") {
            jQuery(document).ready(function($) {
                // Handle document file upload
                $(document).on('change', '.doc-file-input', function() {
                    var $input = $(this);
                    var file = this.files[0];
                    var inquiryId = $input.data('inquiry-id');
                    var docName = $input.data('doc-name');
                    
                    if (!file) return;
                    
                    // Validate file type
                    var allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                    if (allowedTypes.indexOf(file.type) === -1) {
                        Swal.fire({
                            icon: 'error',
                            title: '<?php esc_html_e('Invalid File Type', 'autopuzzle'); ?>',
                            text: '<?php esc_html_e('Please upload only PDF, JPG, or PNG files.', 'autopuzzle'); ?>'
                        });
                        $input.val('');
                        return;
                    }
                    
                    // Show loading
                    $input.prop('disabled', true);
                    $input.closest('.document-item').append('<div class="upload-progress text-center mt-2"><i class="la la-spinner la-spin me-2"></i><?php esc_html_e('Uploading...', 'autopuzzle'); ?></div>');
                    
                    // Create FormData
                    var formData = new FormData();
                    formData.append('action', 'autopuzzle_upload_document');
                    formData.append('nonce', '<?php echo wp_create_nonce('autopuzzle_tracking_status_nonce'); ?>');
                    formData.append('inquiry_id', inquiryId);
                    formData.append('document_name', docName);
                    formData.append('file', file);
                    
                    // Upload via AJAX
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $input.closest('.document-item').find('.upload-progress').remove();
                            $input.prop('disabled', false);
                            
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '<?php esc_html_e('Success', 'autopuzzle'); ?>',
                                    text: response.data.message
                                }).then(function() {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '<?php esc_html_e('Error', 'autopuzzle'); ?>',
                                    text: response.data.message
                                });
                                $input.val('');
                            }
                        },
                        error: function() {
                            $input.closest('.document-item').find('.upload-progress').remove();
                            $input.prop('disabled', false);
                            Swal.fire({
                                icon: 'error',
                                title: '<?php esc_html_e('Error', 'autopuzzle'); ?>',
                                text: '<?php esc_html_e('Server error. Please try again.', 'autopuzzle'); ?>'
                            });
                            $input.val('');
                        }
                    });
                });
            });
        } else {
            setTimeout(waitForJQuery, 50);
        }
    }
    waitForJQuery();
})();
</script>
