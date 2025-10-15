<?php
/**
 * Template for displaying a single Installment Inquiry Report in the frontend (Admin/Expert view).
 * This template fetches all necessary data and provides action buttons/modals.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Gemini
 * @version 1.0.2 (Added More Docs and Delete buttons)
 *
 * @var int $inquiry_id The ID of the inquiry post.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$post = get_post($inquiry_id);

// 1. Permission Check (Redundant check for security, router already handles it)
if ( ! Maneli_Permission_Helpers::can_user_view_inquiry( $inquiry_id, get_current_user_id() ) ) {
    echo '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('Inquiry not found or you do not have permission to view it.', 'maneli-car-inquiry') . '</p></div>';
    return;
}

// 2. Data Retrieval
$post_meta = get_post_meta($inquiry_id);
$options = get_option('maneli_inquiry_all_options', []);
$finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);

$inquiry_status = $post_meta['inquiry_status'][0] ?? 'pending';
$product_id = $post_meta['product_id'][0] ?? 0;
$car_name = get_the_title($product_id);
$cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;

$back_link = remove_query_arg('inquiry_id');
$status_label = Maneli_CPT_Handler::get_status_label($inquiry_status);
$is_admin_or_expert = current_user_can('manage_maneli_inquiries') || Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());

// Loan Details
$down_payment = (int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0);
$total_price = (int)($post_meta['maneli_inquiry_total_price'][0] ?? 0);
$term_months = (int)($post_meta['maneli_inquiry_term_months'][0] ?? 0);
$installment_amount = (int)($post_meta['maneli_inquiry_installment'][0] ?? 0);
$loan_amount = $total_price - $down_payment;

// Customer & Expert Details
$customer_id = $post->post_author;
$customer_user = get_userdata($customer_id);
$customer_name = $customer_user ? $customer_user->display_name : esc_html__('N/A', 'maneli-car-inquiry');
$expert_name = get_post_meta($inquiry_id, 'assigned_expert_name', true);
$rejection_reason = get_post_meta($inquiry_id, 'rejection_reason', true);

// Modal Data
$rejection_reasons_raw = $options['installment_rejection_reasons'] ?? '';
$rejection_reasons = array_filter(array_map('trim', explode("\n", $rejection_reasons_raw)));

// --- Start HTML Output ---

?>

<div class="maneli-inquiry-report-wrapper frontend-expert-report" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">

    <?php if ( $back_link ) : ?>
        <div class="report-back-button-wrapper">
            <a href="<?php echo esc_url($back_link); ?>" class="button button-secondary">
                &larr; <?php esc_html_e('Back to Inquiry List', 'maneli-car-inquiry'); ?>
            </a>
        </div>
    <?php endif; ?>

    <h2 class="report-title">
        <?php echo sprintf(esc_html__('Installment Request Report #%d - %s', 'maneli-car-inquiry'), absint($inquiry_id), esc_html($car_name)); ?>
    </h2>
    <div class="report-status-box status-bg-<?php echo esc_attr($inquiry_status); ?>">
        <strong><?php esc_html_e('Current Status:', 'maneli-car-inquiry'); ?></strong> 
        <?php echo esc_html($status_label); ?>
    </div>

    <?php if ( !empty($rejection_reason) ) : ?>
        <div class="status-box status-rejected">
            <strong><?php esc_html_e('Reason for Rejection:', 'maneli-car-inquiry'); ?></strong> 
            <?php echo esc_html($rejection_reason); ?>
        </div>
    <?php endif; ?>

    <div class="maneli-report-actions" style="display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 10px; margin: 20px 0;">
        
        <?php if ($inquiry_status === 'pending' || $inquiry_status === 'more_docs' || $inquiry_status === 'failed') : ?>
            <button class="button button-primary confirm-inquiry-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment" data-next-status="user_confirmed">
                <?php esc_html_e('Final Approval & Refer to Sales', 'maneli-car-inquiry'); ?>
            </button>
            <button class="button button-danger reject-inquiry-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">
                <?php esc_html_e('Final Rejection of Request', 'maneli-car-inquiry'); ?>
            </button>
        <?php endif; ?>

        <?php if ($is_admin_or_expert && empty($expert_name)) : ?>
            <button class="button button-secondary assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">
                <?php esc_html_e('Assign Expert', 'maneli-car-inquiry'); ?>
            </button>
        <?php endif; ?>

        <?php if ($inquiry_status === 'failed' && $is_admin_or_expert): ?>
             <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block;">
                <input type="hidden" name="action" value="maneli_admin_retry_finotex">
                <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                <?php wp_nonce_field('maneli_retry_finotex_nonce'); ?>
                <button type="submit" class="button button-info"><?php esc_html_e('Retry Finotex Inquiry', 'maneli-car-inquiry'); ?></button>
            </form>
        <?php endif; ?>

        <?php if (current_user_can('manage_maneli_inquiries')): // Actions for Admin only ?>
            
            <?php if ($inquiry_status !== 'rejected' && $inquiry_status !== 'user_confirmed'): // Request More Docs Button (Admin Only) ?>
                 <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block;">
                    <input type="hidden" name="action" value="maneli_admin_update_status">
                    <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                    <input type="hidden" name="new_status" value="more_docs">
                    <?php wp_nonce_field('maneli_admin_update_status_nonce'); ?>
                    <button type="submit" class="button button-info"><?php esc_html_e('Request More Documents', 'maneli-car-inquiry'); ?></button>
                </form>
            <?php endif; ?>

            <button class="button button-secondary delete-inquiry-report-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment" style="background-color: var(--theme-red); border-color: var(--theme-red); color: white;">
                <?php esc_html_e('Delete Request', 'maneli-car-inquiry'); ?>
            </button>

        <?php endif; ?>
    </div>

    <div class="maneli-report-sections-grid" style="display: flex; flex-wrap: wrap; gap: 20px;">
        
        <div class="maneli-report-section inquiry-details" style="flex: 1 1 45%; border: 1px solid #eee; padding: 20px; border-radius: 4px;">
            <h3><?php esc_html_e('Loan and Car Details', 'maneli-car-inquiry'); ?></h3>
            <table class="summary-table form-table">
                <tr><th><?php esc_html_e('Selected Car', 'maneli-car-inquiry'); ?>:</th><td><a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank"><?php echo esc_html($car_name); ?></a></td></tr>
                <tr><th><?php esc_html_e('Total Price', 'maneli-car-inquiry'); ?>:</th><td><?php echo Maneli_Render_Helpers::format_money($total_price); ?> <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td></tr>
                <tr><th><?php esc_html_e('Down Payment', 'maneli-car-inquiry'); ?>:</th><td><?php echo Maneli_Render_Helpers::format_money($down_payment); ?> <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td></tr>
                <tr><th><?php esc_html_e('Loan Amount', 'maneli-car-inquiry'); ?>:</th><td><?php echo Maneli_Render_Helpers::format_money($loan_amount); ?> <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td></tr>
                <tr><th><?php esc_html_e('Installment Term', 'maneli-car-inquiry'); ?>:</th><td><?php echo absint($term_months); ?> <span><?php esc_html_e('Months', 'maneli-car-inquiry'); ?></span></td></tr>
                <tr><th><?php esc_html_e('Monthly Installment', 'maneli-car-inquiry'); ?>:</th><td><?php echo Maneli_Render_Helpers::format_money($installment_amount); ?> <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td></tr>
                <tr><th><?php esc_html_e('Date Submitted', 'maneli-car-inquiry'); ?>:</th><td><?php echo Maneli_Render_Helpers::maneli_gregorian_to_jalali(date('Y', strtotime($post->post_date)), date('m', strtotime($post->post_date)), date('d', strtotime($post->post_date))); ?></td></tr>
            </table>
        </div>

        <div class="maneli-report-section customer-details" style="flex: 1 1 45%; border: 1px solid #eee; padding: 20px; border-radius: 4px;">
            <h3><?php esc_html_e('Applicant and Expert Details', 'maneli-car-inquiry'); ?></h3>
            <table class="summary-table form-table">
                <tr><th><?php esc_html_e('Customer Name', 'maneli-car-inquiry'); ?>:</th><td><?php echo esc_html($customer_name); ?></td></tr>
                <tr><th><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?>:</th><td><a href="tel:<?php echo esc_attr($post_meta['mobile_number'][0] ?? ''); ?>"><?php echo esc_html($post_meta['mobile_number'][0] ?? esc_html__('N/A', 'maneli-car-inquiry')); ?></a></td></tr>
                <tr><th><?php esc_html_e('National ID', 'maneli-car-inquiry'); ?>:</th><td><?php echo esc_html($post_meta['national_code'][0] ?? esc_html__('N/A', 'maneli-car-inquiry')); ?></td></tr>
                <tr><th><?php esc_html_e('Assigned Expert', 'maneli-car-inquiry'); ?>:</th><td><?php echo esc_html($expert_name ?: esc_html__('Not Assigned', 'maneli-car-inquiry')); ?></td></tr>
                 <?php 
                 $issuer_type = $post_meta['issuer_type'][0] ?? 'self';
                 if ($issuer_type === 'other') : ?>
                    <tr><th colspan="2"><strong><?php esc_html_e('Cheque Issuer: Another Person', 'maneli-car-inquiry'); ?></strong></th></tr>
                    <tr><th><?php esc_html_e('Issuer National ID', 'maneli-car-inquiry'); ?>:</th><td><?php echo esc_html($post_meta['issuer_national_code'][0] ?? esc_html__('N/A', 'maneli-car-inquiry')); ?></td></tr>
                 <?php else: ?>
                    <tr><th><?php esc_html_e('Cheque Issuer', 'maneli-car-inquiry'); ?>:</th><td><?php esc_html_e('Same as Applicant', 'maneli-car-inquiry'); ?></td></tr>
                 <?php endif; ?>
            </table>
        </div>
        
        <div class="maneli-report-section cheque-inquiry" style="flex: 1 1 100%; border: 1px solid #eee; padding: 20px; border-radius: 4px;">
            <h3><?php esc_html_e('Credit Verification Result (Finotex)', 'maneli-car-inquiry'); ?></h3>
            <?php 
            if (empty($finotex_data) || ($finotex_data['status'] ?? '') === 'SKIPPED') : ?>
                <div class="status-box status-pending" style="margin-bottom:0;">
                    <p><?php esc_html_e('Bank inquiry was not performed or failed to return data.', 'maneli-car-inquiry'); ?></p>
                </div>
            <?php else :
                // Use the helper to render the status bar and table
                echo Maneli_Render_Helpers::render_cheque_status_info($cheque_color_code);
            endif; ?>
        </div>
    </div>
    
    <?php if ($is_admin_or_expert): ?>
    <div class="maneli-card meeting-schedule">
        <h3><?php esc_html_e('Schedule In-Person Meeting', 'maneli-car-inquiry'); ?></h3>
        <form id="meeting_form" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">
            <div class="form-row">
                <div class="form-group">
                    <label><?php esc_html_e('Select Date', 'maneli-car-inquiry'); ?>:</label>
                    <input type="date" id="meeting_date" required>
                    <input type="hidden" id="meeting_start" value="">
                </div>
                <div class="form-group" style="flex: 1; min-width: 260px;">
                    <label><?php esc_html_e('Available Slots', 'maneli-car-inquiry'); ?>:</label>
                    <div id="meeting_slots"></div>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="action-btn" style="background: linear-gradient(135deg, var(--theme-green) 0%, #4caf50 100%);">
                        <?php esc_html_e('Book Meeting', 'maneli-car-inquiry'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($is_admin_or_expert): ?>
    <div class="maneli-report-section expert-decision" style="flex: 1 1 100%; border: 1px solid #eee; padding: 20px; border-radius: 4px; margin-top: 20px;">
        <h3><?php esc_html_e('Expert Decision', 'maneli-car-inquiry'); ?></h3>
        <?php $options = get_option('maneli_inquiry_all_options', []); $raw = $options['expert_statuses'] ?? ''; $lines = array_filter(array_map('trim', explode("\n", (string)$raw))); $default_key = $options['expert_default_status'] ?? 'unknown'; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex; gap:10px; flex-wrap: wrap;">
            <input type="hidden" name="action" value="maneli_expert_update_decision">
            <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
            <input type="hidden" name="inquiry_type" value="installment">
            <?php wp_nonce_field('maneli_expert_update_decision'); ?>
            <div style="min-width:240px;">
                <label style="display:block; margin-bottom:6px; "><?php esc_html_e('Status', 'maneli-car-inquiry'); ?>:</label>
                <select name="expert_status" style="width:100%;">
                    <?php foreach ($lines as $line): list($key,$label) = array_pad(explode('|', $line), 3, ''); ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected(get_post_meta($inquiry_id, 'expert_status', true) ?: $default_key, $key); ?>><?php echo esc_html($label ?: $key); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1; min-width:280px;">
                <label style="display:block; margin-bottom:6px; "><?php esc_html_e('Note (optional)', 'maneli-car-inquiry'); ?>:</label>
                <textarea name="expert_note" rows="2" style="width:100%;"><?php echo esc_textarea(get_post_meta($inquiry_id, 'expert_status_note', true)); ?></textarea>
            </div>
            <div style="align-self:flex-end;">
                <button type="submit" class="button button-primary"><?php esc_html_e('Save Decision', 'maneli-car-inquiry'); ?></button>
            </div>
        </form>
    </div>
    <?php endif; ?>

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
                    // Loop through dynamic reasons from settings
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

    <form id="admin-action-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: none;">
        <input type="hidden" name="action" value="maneli_admin_update_status">
        <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
        <input type="hidden" id="final-status-input" name="new_status" value="">
        <input type="hidden" id="rejection-reason-input" name="rejection_reason" value="">
        <input type="hidden" id="assigned-expert-input" name="assigned_expert_id" value="">
        <?php wp_nonce_field('maneli_admin_update_status_nonce'); ?>
    </form>

</div>