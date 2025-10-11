<?php
/**
 * Template for displaying a single Installment Inquiry Report in the frontend (Admin/Expert view).
 * This template handles the main display of inquiry details and the rejection modal.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @version 1.0.0
 * @global WP_Post $post The current inquiry post object.
 * @global array $inquiry_data Sanitized and processed inquiry metadata.
 * @global int $product_id The ID of the related WooCommerce product.
 * @global string $back_url URL to return to the inquiry list.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! Maneli_Permission_Helpers::can_user_view_inquiry( $post->ID ) ) {
    Maneli_Render_Helpers::render_access_denied_message();
    return;
}

global $post, $inquiry_data, $product_id, $back_url;

$post_id = $post->ID;
$inquiry_type_label = esc_html__('Installment Request', 'maneli-car-inquiry');
$post_status_label = Maneli_Render_Helpers::get_installment_status_label( $post->post_status );
$is_admin_or_expert = Maneli_Permission_Helpers::is_admin_or_expert();
$can_act = Maneli_Permission_Helpers::can_user_act_on_inquiry( $post_id );
$car_name = $inquiry_data['car']['name'] ?? esc_html__('N/A', 'maneli-car-inquiry');
$customer_name = ($inquiry_data['customer']['first_name'] ?? '') . ' ' . ($inquiry_data['customer']['last_name'] ?? '');

$expert_id = get_post_meta( $post_id, 'assigned_expert_id', true );
$expert_name = Maneli_Render_Helpers::get_expert_name_by_id( $expert_id );
$expert_mobile = get_user_meta( $expert_id, 'billing_phone', true );
$expert_role = get_user_meta( $expert_id, 'maneli_expert_type', true );


// --- Start HTML Output ---

?>

<div class="maneli-inquiry-report-wrapper frontend-expert-report" data-inquiry-id="<?php echo esc_attr($post_id); ?>" data-inquiry-type="installment">

    <?php if ( $back_url ) : ?>
        <div class="report-back-button-wrapper">
            <a href="<?php echo esc_url($back_url); ?>" class="button button-secondary">
                &larr; <?php esc_html_e('Back to Inquiry List', 'maneli-car-inquiry'); ?>
            </a>
        </div>
    <?php endif; ?>

    <h2 class="report-title">
        <?php echo sprintf(esc_html__('%s Report #%d - %s', 'maneli-car-inquiry'), $inquiry_type_label, absint($post_id), esc_html($car_name)); ?>
    </h2>
    <div class="maneli-status-tag status-<?php echo esc_attr($post->post_status); ?>">
        <?php echo esc_html($post_status_label); ?>
    </div>

    <?php if ( !empty($inquiry_data['rejection_reason']) ) : ?>
        <div class="maneli-rejection-reason">
            <strong><?php esc_html_e('Rejection Reason:', 'maneli-car-inquiry'); ?></strong> 
            <?php echo esc_html($inquiry_data['rejection_reason']); ?>
        </div>
    <?php endif; ?>

    <div class="maneli-report-actions">
        <?php if ($can_act && $post->post_status === 'pending') : ?>
            <button class="button button-primary confirm-inquiry-btn" data-inquiry-id="<?php echo esc_attr($post_id); ?>" data-inquiry-type="installment" data-next-status="approved">
                <?php esc_html_e('Approve Request', 'maneli-car-inquiry'); ?>
            </button>
            <button class="button button-danger reject-inquiry-btn" data-inquiry-id="<?php echo esc_attr($post_id); ?>" data-inquiry-type="installment">
                <?php esc_html_e('Reject Request', 'maneli-car-inquiry'); ?>
            </button>
        <?php endif; ?>

        <?php if ($is_admin_or_expert) : ?>
            <button class="button button-secondary assign-expert-btn" data-inquiry-id="<?php echo esc_attr($post_id); ?>" data-inquiry-type="installment">
                <?php esc_html_e('Assign Expert', 'maneli-car-inquiry'); ?>
            </button>
        <?php endif; ?>
    </div>

    <div class="maneli-report-sections-grid">
        <div class="maneli-report-section inquiry-details">
            <h3><?php esc_html_e('Inquiry Details', 'maneli-car-inquiry'); ?></h3>
            <table>
                <tr><th><?php esc_html_e('Date Submitted', 'maneli-car-inquiry'); ?>:</th><td><?php echo Maneli_Render_Helpers::format_jdate($post->post_date); ?></td></tr>
                <tr><th><?php esc_html_e('Car Name', 'maneli-car-inquiry'); ?>:</th><td><a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank"><?php echo esc_html($car_name); ?></a></td></tr>
                <tr><th><?php esc_html_e('Price (Toman)', 'maneli-car-inquiry'); ?>:</th><td><?php echo Maneli_Render_Helpers::format_money($inquiry_data['car']['price'] ?? 0); ?></td></tr>
                <tr><th><?php esc_html_e('Down Payment (Toman)', 'maneli-car-inquiry'); ?>:</th><td><?php echo Maneli_Render_Helpers::format_money($inquiry_data['installment']['down_payment_amount'] ?? 0); ?></td></tr>
                <tr><th><?php esc_html_e('Loan Amount (Toman)', 'maneli-car-inquiry'); ?>:</th><td><?php echo Maneli_Render_Helpers::format_money($inquiry_data['installment']['loan_amount'] ?? 0); ?></td></tr>
                <tr><th><?php esc_html_e('Installments', 'maneli-car-inquiry'); ?>:</th><td><?php echo absint($inquiry_data['installment']['duration'] ?? 0); ?> <?php esc_html_e('Months', 'maneli-car-inquiry'); ?></td></tr>
                <tr><th><?php esc_html_e('Monthly Installment', 'maneli-car-inquiry'); ?>:</th><td><?php echo Maneli_Render_Helpers::format_money($inquiry_data['installment']['monthly_installment'] ?? 0); ?></td></tr>
            </table>
        </div>

        <div class="maneli-report-section customer-details">
            <h3><?php esc_html_e('Customer Details', 'maneli-car-inquiry'); ?></h3>
            <table>
                <tr><th><?php esc_html_e('Customer Name', 'maneli-car-inquiry'); ?>:</th><td><?php echo esc_html($customer_name); ?></td></tr>
                <tr><th><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?>:</th><td><a href="tel:<?php echo esc_attr($inquiry_data['customer']['mobile'] ?? ''); ?>"><?php echo esc_html($inquiry_data['customer']['mobile'] ?? esc_html__('N/A', 'maneli-car-inquiry')); ?></a></td></tr>
                <tr><th><?php esc_html_e('National ID', 'maneli-car-inquiry'); ?>:</th><td><?php echo esc_html($inquiry_data['customer']['national_id'] ?? esc_html__('N/A', 'maneli-car-inquiry')); ?></td></tr>
                <tr><th><?php esc_html_e('Expert', 'maneli-car-inquiry'); ?>:</th><td><?php echo esc_html($expert_name); ?></td></tr>
                <tr><th><?php esc_html_e('Expert Mobile', 'maneli-car-inquiry'); ?>:</th><td><?php echo esc_html($expert_mobile); ?></td></tr>
                <tr><th><?php esc_html_e('Expert Role', 'maneli-car-inquiry'); ?>:</th><td><?php echo esc_html($expert_role ? Maneli_Render_Helpers::get_expert_type_label($expert_role) : esc_html__('N/A', 'maneli-car-inquiry')); ?></td></tr>
            </table>
        </div>

        <div class="maneli-report-section user-documents">
            <h3><?php esc_html_e('Uploaded Documents', 'maneli-car-inquiry'); ?></h3>
            <?php 
            $docs = $inquiry_data['documents'] ?? [];
            if (!empty($docs)) : 
            ?>
            <ul>
                <?php foreach ($docs as $key => $url) : ?>
                    <li>
                        <?php echo Maneli_Render_Helpers::get_document_type_label($key); ?>: 
                        <a href="<?php echo esc_url($url); ?>" target="_blank" class="document-link">
                            <?php esc_html_e('View Document', 'maneli-car-inquiry'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php else : ?>
                <p><?php esc_html_e('No documents have been uploaded yet.', 'maneli-car-inquiry'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="maneli-report-section cheque-inquiry">
            <h3><?php esc_html_e('Cheque Status Inquiry (Finotex)', 'maneli-car-inquiry'); ?></h3>
            <?php 
            $finotex_raw_data = get_post_meta($post_id, 'finotex_raw_response', true);
            $cheque_status = get_post_meta($post_id, 'cheque_status_color', true);

            if (empty($inquiry_data['finotex_inquiry_enabled'])) : ?>
                <p><?php esc_html_e('Finotex inquiry is disabled in plugin settings.', 'maneli-car-inquiry'); ?></p>
            <?php elseif ($post->post_status === 'pending') : ?>
                <p><?php esc_html_e('Inquiry has not been processed yet.', 'maneli-car-inquiry'); ?></p>
            <?php elseif (empty($cheque_status)) : ?>
                <p><?php esc_html_e('Cheque status could not be determined or inquiry failed.', 'maneli-car-inquiry'); ?></p>
            <?php else : ?>
                <p><strong><?php esc_html_e('Status:', 'maneli-car-inquiry'); ?></strong> <span class="cheque-color-status status-<?php echo esc_attr($cheque_status); ?>"><?php echo Maneli_Render_Helpers::get_cheque_status_label($cheque_status); ?></span></p>
                
                <?php if ($finotex_raw_data && current_user_can('manage_options')) : ?>
                    <details>
                        <summary><?php esc_html_e('Show Raw Finotex API Response (Admin Only)', 'maneli-car-inquiry'); ?></summary>
                        <pre style="white-space: pre-wrap; word-wrap: break-word; font-size: 10px; direction: ltr; text-align: left; background-color: #f7f7f7; padding: 10px; border: 1px solid #eee;">
                            <?php echo esc_textarea(print_r($finotex_raw_data, true)); ?>
                        </pre>
                    </details>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
    
    <?php if ( Maneli_Permission_Helpers::can_user_act_on_inquiry( $post_id ) ) : ?>
        <div id="rejection-modal" class="maneli-modal-frontend" style="display:none;">
            <div class="modal-content">
                <span class="modal-close">&times;</span>
                <h3><?php esc_html_e('Reason for Rejection', 'maneli-car-inquiry'); ?></h3>
                <p><?php esc_html_e('Please specify the reason for rejecting this request. This reason will be sent to the user via SMS.', 'maneli-car-inquiry'); ?></p>
                
                <div class="form-group">
                    <label for="rejection-reason-select"><?php esc_html_e('Select a reason:', 'maneli-car-inquiry'); ?></label>
                    <select id="rejection-reason-select" style="width: 100%;">
                        <option value=""><?php esc_html_e('-- Select a reason --', 'maneli-car-inquiry'); ?></option>
                        <?php 
                        // FIX: Load rejection reasons dynamically from settings
                        $options = get_option('maneli_inquiry_all_options', []);
                        $rejection_reasons_raw = $options['installment_rejection_reasons'] ?? '';
                        $rejection_reasons = array_filter(array_map('trim', explode("\n", $rejection_reasons_raw)));
                        
                        foreach ($rejection_reasons as $reason): 
                        ?>
                            <option value="<?php echo esc_attr($reason); ?>"><?php echo esc_html($reason); ?></option>
                        <?php endforeach; ?>
                        <option value="custom"><?php esc_html_e('Other reason (write in the box below)', 'maneli-car-inquiry'); ?></option>
                    </select>
                </div>
                
                <div class="form-group" id="custom-reason-wrapper" style="display:none;">
                    <label for="rejection-reason-custom"><?php esc_html_e('Custom Text:', 'maneli-car-inquiry'); ?></label>
                    <textarea id="rejection-reason-custom" rows="3" style="width: 100%;"></textarea>
                </div>
                
                <button type="button" id="confirm-rejection-btn" class="button button-primary">
                    <?php esc_html_e('Submit Reason and Reject Request', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>

</div>