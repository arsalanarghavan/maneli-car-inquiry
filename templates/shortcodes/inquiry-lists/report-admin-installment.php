<?php
/**
 * Template for the Admin/Expert view of a single Installment Inquiry Report.
 *
 * This template displays the full details of an installment inquiry and provides action buttons
 * for managing the request (assign, approve, reject, etc.).
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Gemini
 * @version 1.0.0
 *
 * @var int $inquiry_id The ID of the inquiry post.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fetch all necessary data for the report
$inquiry = get_post($inquiry_id);
$post_meta = get_post_meta($inquiry_id);
$finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
$product_id = $post_meta['product_id'][0] ?? 0;
$status = $post_meta['inquiry_status'][0] ?? 'pending';
$back_link = remove_query_arg('inquiry_id');

// Map status keys to labels and CSS classes
$status_info = [
    'user_confirmed' => ['label' => esc_html__('Approved and Referred', 'maneli-car-inquiry'), 'class' => 'status-bg-approved'],
    'rejected'       => ['label' => esc_html__('Rejected', 'maneli-car-inquiry'), 'class' => 'status-bg-rejected'],
    'more_docs'      => ['label' => esc_html__('More Documents Required', 'maneli-car-inquiry'), 'class' => 'status-bg-pending'],
    'pending'        => ['label' => esc_html__('Pending Review', 'maneli-car-inquiry'), 'class' => 'status-bg-pending'],
    'failed'         => ['label' => esc_html__('Inquiry Failed', 'maneli-car-inquiry'), 'class' => 'status-bg-rejected'],
];
$current_status_info = $status_info[$status] ?? ['label' => esc_html__('Unknown', 'maneli-car-inquiry'), 'class' => ''];

// Helper function to render a grid of details
$render_fields_grid = function($fields, $meta) {
    echo '<div class="form-grid">';
    foreach (array_chunk($fields, 2, true) as $pair) {
        echo '<div class="form-row">';
        foreach($pair as $key => $label) {
            $value = $meta[$key][0] ?? '—';
            if (str_contains($key, 'residency_status')) {
                $value = ($value === 'owner') ? esc_html__('Owner', 'maneli-car-inquiry') : (($value === 'tenant') ? esc_html__('Tenant', 'maneli-car-inquiry') : $value);
            }
            if (str_contains($key, 'workplace_status')) {
                $statuses = ['permanent' => esc_html__('Permanent', 'maneli-car-inquiry'), 'contract' => esc_html__('Contract', 'maneli-car-inquiry'), 'freelance' => esc_html__('Freelance', 'maneli-car-inquiry')];
                $value = $statuses[$value] ?? $value;
            }
            echo '<div class="form-group"><label>' . esc_html($label) . '</label><div class="detail-value-box">' . esc_html($value) . '</div></div>';
        }
        if (count($pair) < 2) echo '<div class="form-group"></div>'; // Fill empty grid space
        echo '</div>';
    }
    echo '</div>';
};
?>

<div class="maneli-inquiry-wrapper frontend-expert-report">
    <h2 class="report-main-title">
        <?php esc_html_e('Full Credit Report', 'maneli-car-inquiry'); ?>
        <small>(<?php printf(esc_html__('for Inquiry #%d', 'maneli-car-inquiry'), (int)$inquiry_id); ?>)</small>
    </h2>

    <div class="report-status-box <?php echo esc_attr($current_status_info['class']); ?>">
        <strong><?php esc_html_e('Current Status:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($current_status_info['label']); ?>
    </div>
    
    <div class="report-box">
        <h3 class="report-box-title"><?php esc_html_e('Car and Installment Details', 'maneli-car-inquiry'); ?></h3>
        <div class="report-box-flex">
            <div class="report-car-image">
                <?php if ($product_id && has_post_thumbnail($product_id)) : ?>
                    <?php echo get_the_post_thumbnail($product_id, 'medium'); ?>
                <?php endif; ?>
            </div>
            <div class="report-details-table">
                <table class="summary-table">
                    <tbody>
                        <tr><th><?php esc_html_e('Selected Car', 'maneli-car-inquiry'); ?></th><td><?php echo get_the_title($product_id); ?></td></tr>
                        <tr><th><?php esc_html_e('Total Price', 'maneli-car-inquiry'); ?></th><td><?php printf('%s <span>%s</span>', number_format_i18n((int)($post_meta['maneli_inquiry_total_price'][0] ?? 0)), esc_html__('Toman', 'maneli-car-inquiry')); ?></td></tr>
                        <tr><th><?php esc_html_e('Down Payment', 'maneli-car-inquiry'); ?></th><td><?php printf('%s <span>%s</span>', number_format_i18n((int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0)), esc_html__('Toman', 'maneli-car-inquiry')); ?></td></tr>
                        <tr><th><?php esc_html_e('Installment Term', 'maneli-car-inquiry'); ?></th><td><?php printf('%s <span>%s</span>', esc_html($post_meta['maneli_inquiry_term_months'][0] ?? 0), esc_html__('Months', 'maneli-car-inquiry')); ?></td></tr>
                        <tr><th><?php esc_html_e('Installment Amount', 'maneli-car-inquiry'); ?></th><td><?php printf('%s <span>%s</span>', number_format_i18n((int)($post_meta['maneli_inquiry_installment'][0] ?? 0)), esc_html__('Toman', 'maneli-car-inquiry')); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="report-box">
        <h3 class="report-box-title"><?php esc_html_e('Applicant Information', 'maneli-car-inquiry'); ?></h3>
        <?php
        $buyer_fields = [
            'first_name' => __('Name', 'maneli-car-inquiry'), 'last_name' => __('Last Name', 'maneli-car-inquiry'), 'father_name' => __('Father\'s Name', 'maneli-car-inquiry'), 'national_code' => __('National Code', 'maneli-car-inquiry'),
            'occupation' => __('Occupation', 'maneli-car-inquiry'), 'income_level' => __('Income Level', 'maneli-car-inquiry'), 'mobile_number' => __('Mobile Number', 'maneli-car-inquiry'), 'phone_number' => __('Phone Number', 'maneli-car-inquiry'),
            'residency_status' => __('Residency Status', 'maneli-car-inquiry'), 'workplace_status' => __('Workplace Status', 'maneli-car-inquiry'), 'address' => __('Address', 'maneli-car-inquiry'), 'birth_date' => __('Date of Birth', 'maneli-car-inquiry'),
            'bank_name' => __('Bank Name', 'maneli-car-inquiry'), 'account_number' => __('Account Number', 'maneli-car-inquiry'), 'branch_code' => __('Branch Code', 'maneli-car-inquiry'), 'branch_name' => __('Branch Name', 'maneli-car-inquiry')
        ];
        $render_fields_grid($buyer_fields, $post_meta);
        ?>
    </div>
    
    <?php if (($post_meta['issuer_type'][0] ?? 'self') === 'other'): ?>
    <div class="report-box">
        <h3 class="report-box-title"><?php esc_html_e('Cheque Issuer Information', 'maneli-car-inquiry'); ?></h3>
        <?php
        $issuer_fields = [
            'issuer_full_name' => __('Issuer Name', 'maneli-car-inquiry'), 'issuer_national_code' => __('Issuer National Code', 'maneli-car-inquiry'), 'issuer_bank_name' => __('Bank Name', 'maneli-car-inquiry'),
            'issuer_account_number' => __('Account Number', 'maneli-car-inquiry'), 'issuer_branch_code' => __('Branch Code', 'maneli-car-inquiry'), 'issuer_branch_name' => __('Branch Name', 'maneli-car-inquiry'),
            'issuer_residency_status' => __('Residency Status', 'maneli-car-inquiry'), 'issuer_workplace_status' => __('Workplace Status', 'maneli-car-inquiry'),
            'issuer_father_name' => __('Father\'s Name', 'maneli-car-inquiry'), 'issuer_occupation' => __('Occupation', 'maneli-car-inquiry'), 'issuer_phone_number' => __('Phone Number', 'maneli-car-inquiry'), 'issuer_address' => __('Address', 'maneli-car-inquiry')
        ];
        $render_fields_grid($issuer_fields, $post_meta);
        ?>
    </div>
    <?php endif; ?>

    <div class="report-box">
        <h3 class="report-box-title"><?php esc_html_e('Cheque Status Inquiry Result (Sayadi)', 'maneli-car-inquiry'); ?></h3>
        <?php 
        $finotex_skipped = (empty($finotex_data) || ($finotex_data['status'] ?? '') === 'SKIPPED');
        if ($finotex_skipped): 
        ?>
             <table class="summary-table right-aligned-table" style="margin-top: 20px;">
                <tbody>
                     <tr>
                        <th><?php esc_html_e('Credit Status', 'maneli-car-inquiry'); ?></th>
                        <td><?php esc_html_e('Undetermined', 'maneli-car-inquiry'); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Explanation', 'maneli-car-inquiry'); ?></th>
                        <td><?php esc_html_e('Finotex inquiry is disabled in settings or was not performed.', 'maneli-car-inquiry'); ?></td>
                    </tr>
                </tbody>
            </table>
            <?php if (current_user_can('manage_maneli_inquiries')): ?>
                <div class="admin-notice" style="margin-top: 20px;">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                         <input type="hidden" name="action" value="maneli_admin_retry_finotex">
                         <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                         <?php wp_nonce_field('maneli_retry_finotex_nonce'); ?>
                         <button type="submit" class="action-btn approve"><?php esc_html_e('Retry Finotex Inquiry', 'maneli-car-inquiry'); ?></button>
                    </form>
                </div>
            <?php endif; ?>
        <?php else: 
            $cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;
            $cheque_color_map = [
                '1' => ['text' => __('White', 'maneli-car-inquiry'), 'desc' => __('No history of bounced cheques.', 'maneli-car-inquiry')],
                '2' => ['text' => __('Yellow', 'maneli-car-inquiry'), 'desc' => __('One bounced cheque or a maximum of 50 million Rials in returned commitments.', 'maneli-car-inquiry')],
                '3' => ['text' => __('Orange', 'maneli-car-inquiry'), 'desc' => __('Two to four bounced cheques or a maximum of 200 million Rials in returned commitments.', 'maneli-car-inquiry')],
                '4' => ['text' => __('Brown', 'maneli-car-inquiry'), 'desc' => __('Five to ten bounced cheques or a maximum of 500 million Rials in returned commitments.', 'maneli-car-inquiry')],
                '5' => ['text' => __('Red', 'maneli-car-inquiry'), 'desc' => __('More than ten bounced cheques or more than 500 million Rials in returned commitments.', 'maneli-car-inquiry')],
                 0  => ['text' => __('Undetermined', 'maneli-car-inquiry'), 'desc' => __('Information was not received from Finotex or the inquiry was unsuccessful.', 'maneli-car-inquiry')]
            ];
            $color_info = $cheque_color_map[$cheque_color_code] ?? $cheque_color_map[0];
        ?>
            <div class="maneli-status-bar">
                <?php
                $colors = [ 1 => 'white', 2 => 'yellow', 3 => 'orange', 4 => 'brown', 5 => 'red' ];
                foreach ($colors as $code => $class) {
                    $active_class = ((string)$code === (string)$cheque_color_code) ? 'active' : '';
                    echo "<div class='bar-segment segment-{$class} {$active_class}'><span>" . esc_html($cheque_color_map[$code]['text']) . "</span></div>";
                }
                ?>
            </div>
            <table class="summary-table right-aligned-table" style="margin-top: 20px;">
                <tbody>
                    <tr><th><?php esc_html_e('Credit Status', 'maneli-car-inquiry'); ?></th><td><strong class="cheque-color-<?php echo esc_attr($cheque_color_code); ?>"><?php echo esc_html($color_info['text']); ?></strong></td></tr>
                    <tr><th><?php esc_html_e('Explanation', 'maneli-car-inquiry'); ?></th><td><?php echo esc_html($color_info['desc']); ?></td></tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if (current_user_can('manage_maneli_inquiries')): ?>
    <div class="admin-actions-box">
        <h3 class="report-box-title"><?php esc_html_e('Final Decision', 'maneli-car-inquiry'); ?></h3>
        <p><?php esc_html_e('After reviewing the information above, specify the final status of this request.', 'maneli-car-inquiry'); ?></p>
        <form id="admin-action-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="maneli_admin_update_status">
            <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
            <input type="hidden" id="final-status-input" name="new_status" value="">
            <input type="hidden" id="rejection-reason-input" name="rejection_reason" value="">
            <?php wp_nonce_field('maneli_admin_update_status_nonce'); ?>

            <div class="action-button-group">
                <div class="approve-section">
                    <label for="assigned_expert_id_frontend"><?php esc_html_e('Assign to Expert:', 'maneli-car-inquiry'); ?></label>
                     <?php
                        $experts = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
                        if (!empty($experts)):
                    ?>
                    <select name="assigned_expert_id" id="assigned_expert_id_frontend">
                        <option value="auto"><?php esc_html_e('-- Automatic Assignment (Round-robin) --', 'maneli-car-inquiry'); ?></option>
                        <?php foreach ($experts as $expert) : ?>
                            <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    <button type="button" id="approve-btn" class="action-btn approve">
                        &#10004; <?php esc_html_e('Approve and Assign', 'maneli-car-inquiry'); ?>
                    </button>
                </div>
                <button type="button" id="reject-btn" class="action-btn reject">
                    &#10006; <?php esc_html_e('Final Rejection of Request', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="report-back-button-wrapper">
        <a href="<?php echo esc_url($back_link); ?>" class="loan-action-btn"><?php esc_html_e('Back to Inquiry List', 'maneli-car-inquiry'); ?></a>
    </div>
</div>

<?php if (current_user_can('manage_maneli_inquiries')): ?>
<div id="rejection-modal" class="maneli-modal-frontend" style="display:none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span><h3><?php esc_html_e('Reason for Rejection', 'maneli-car-inquiry'); ?></h3>
        <p><?php esc_html_e('Please specify the reason for rejecting this request. This reason will be sent to the user via SMS.', 'maneli-car-inquiry'); ?></p>
        <div class="form-group"><label for="rejection-reason-select"><?php esc_html_e('Select a reason:', 'maneli-car-inquiry'); ?></label><select id="rejection-reason-select" style="width: 100%;"><option value=""><?php esc_html_e('-- Select a reason --', 'maneli-car-inquiry'); ?></option><option value="<?php esc_attr_e('Unfortunately, purchasing with this down payment amount is not possible at the moment.', 'maneli-car-inquiry'); ?>"><?php esc_html_e('Down payment is not sufficient.', 'maneli-car-inquiry'); ?></option><option value="<?php esc_attr_e('Unfortunately, your credit history was not approved for the purchase of this vehicle.', 'maneli-car-inquiry'); ?>"><?php esc_html_e('Credit history not approved.', 'maneli-car-inquiry'); ?></option><option value="<?php esc_attr_e('Your submitted documents are incomplete or invalid. Please contact support.', 'maneli-car-inquiry'); ?>"><?php esc_html_e('Documents incomplete or invalid.', 'maneli-car-inquiry'); ?></option><option value="custom"><?php esc_html_e('Other reason (write in the box below)', 'maneli-car-inquiry'); ?></option></select></div>
        <div class="form-group" id="custom-reason-wrapper" style="display:none;"><label for="rejection-reason-custom"><?php esc_html_e('Custom Text:', 'maneli-car-inquiry'); ?></label><textarea id="rejection-reason-custom" rows="3" style="width: 100%;"></textarea></div>
        <button type="button" id="confirm-rejection-btn" class="button button-primary"><?php esc_html_e('Submit Reason and Reject Request', 'maneli-car-inquiry'); ?></button>
    </div>
</div>
<?php endif; ?>