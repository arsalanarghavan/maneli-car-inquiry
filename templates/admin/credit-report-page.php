<?php
/**
 * Template for the admin-side Credit Report page.
 * (/wp-admin/admin.php?page=maneli-credit-report&inquiry_id=...)
 *
 * This template renders the detailed report for an installment inquiry within the WordPress backend.
 *
 * @package Maneli_Car_Inquiry/Templates/Admin
 * @author  Gemini
 * @version 1.0.0
 *
 * @var int     $inquiry_id   The ID of the inquiry post.
 * @var WP_Post $inquiry      The inquiry post object.
 * @var array   $post_meta    The post meta data for the inquiry.
 * @var array   $finotex_data The unserialized data from the Finotex API response.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get required data
$options = get_option('maneli_inquiry_all_options', []);
$product_id = $post_meta['product_id'][0] ?? 0;
$cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;
// Prepare rejection reasons from settings for the modal
$rejection_reasons_raw = $options['installment_rejection_reasons'] ?? '';
$rejection_reasons = array_filter(array_map('trim', explode("\n", $rejection_reasons_raw)));

?>

<div class="wrap maneli-report-wrap">
    <h1>
        <?php esc_html_e('Full Credit Report', 'maneli-car-inquiry'); ?>
        <small>(<?php printf(esc_html__('for Inquiry #%d', 'maneli-car-inquiry'), (int)$inquiry_id); ?>)</small>
    </h1>
    
    <div class="report-box">
        <h2><?php esc_html_e('Requested Car', 'maneli-car-inquiry'); ?></h2>
        <div class="report-flex-container">
            <div class="report-image-container">
                <?php
                if ($product_id && has_post_thumbnail($product_id)) {
                    echo get_the_post_thumbnail($product_id, 'medium');
                } else {
                    echo '<div class="no-image">' . esc_html__('No image has been set for this product.', 'maneli-car-inquiry') . '</div>';
                }
                ?>
            </div>
            <div class="report-details-table">
                <table class="form-table">
                    <tbody>
                        <tr><th scope="row"><?php esc_html_e('Selected Car', 'maneli-car-inquiry'); ?></th><td><?php echo esc_html(get_the_title($product_id)); ?></td></tr>
                        <tr><th scope="row"><?php esc_html_e('Total Price', 'maneli-car-inquiry'); ?></th><td><?php printf('%s <span>%s</span>', number_format_i18n((int)($post_meta['maneli_inquiry_total_price'][0] ?? 0)), esc_html__('Toman', 'maneli-car-inquiry')); ?></td></tr>
                        <tr><th scope="row"><?php esc_html_e('Down Payment', 'maneli-car-inquiry'); ?></th><td><?php printf('%s <span>%s</span>', number_format_i18n((int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0)), esc_html__('Toman', 'maneli-car-inquiry')); ?></td></tr>
                        <tr><th scope="row"><?php esc_html_e('Installment Term', 'maneli-car-inquiry'); ?></th><td><?php printf('%s <span>%s</span>', esc_html($post_meta['maneli_inquiry_term_months'][0] ?? 0), esc_html__('Months', 'maneli-car-inquiry')); ?></td></tr>
                        <tr><th scope="row"><?php esc_html_e('Installment Amount', 'maneli-car-inquiry'); ?></th><td><?php printf('%s <span>%s</span>', number_format_i18n((int)($post_meta['maneli_inquiry_installment'][0] ?? 0)), esc_html__('Toman', 'maneli-car-inquiry')); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="report-box">
        <h2><?php esc_html_e('Personal Information', 'maneli-car-inquiry'); ?></h2>
        <?php
        $buyer_fields = [
            'first_name' => __('Buyer Name', 'maneli-car-inquiry'),'last_name' => __('Buyer Last Name', 'maneli-car-inquiry'),
            'national_code' => __('Buyer National Code', 'maneli-car-inquiry'),'father_name' => __('Buyer Father\'s Name', 'maneli-car-inquiry'),
            'birth_date' => __('Buyer Date of Birth', 'maneli-car-inquiry'),'mobile_number' => __('Buyer Mobile Number', 'maneli-car-inquiry')
        ];
        // This part can be turned into a reusable helper function if needed
        echo '<table class="form-table"><tbody>';
        foreach (array_chunk($buyer_fields, 2, true) as $pair) {
            echo '<tr>';
            foreach($pair as $key => $label) {
                echo '<th scope="row" style="width: 15%;"><label>' . esc_html($label) . '</label></th><td style="width: 35%;">' . esc_html($post_meta[$key][0] ?? '') . '</td>';
            }
            if (count($pair) < 2) echo '<th></th><td></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        $issuer_type = $post_meta['issuer_type'][0] ?? 'self';
        if ($issuer_type === 'other') :
            echo '<h3 style="margin-top:20px; border-top:1px solid #ddd; padding-top:20px;">' . esc_html__('Cheque Issuer Information', 'maneli-car-inquiry') . '</h3>';
            $issuer_fields = [
                'issuer_first_name' => __('First Name', 'maneli-car-inquiry'),'issuer_last_name' => __('Last Name', 'maneli-car-inquiry'),
                'issuer_national_code' => __('National Code', 'maneli-car-inquiry'),'issuer_father_name'   => __('Father\'s Name', 'maneli-car-inquiry'),
                'issuer_birth_date'    => __('Date of Birth', 'maneli-car-inquiry'),'issuer_mobile_number' => __('Mobile Number', 'maneli-car-inquiry')
            ];
            echo '<table class="form-table"><tbody>';
            foreach (array_chunk($issuer_fields, 2, true) as $pair) {
                 echo '<tr>';
                 foreach($pair as $key => $label) {
                     echo '<th scope="row" style="width: 15%;"><label>' . esc_html($label) . '</label></th><td style="width: 35%;">' . esc_html($post_meta[$key][0] ?? '') . '</td>';
                 }
                 if (count($pair) < 2) echo '<th></th><td></td>';
                 echo '</tr>';
            }
            echo '</tbody></table>';
        endif;
        ?>
    </div>

    <div class="report-box">
        <h2><?php esc_html_e('Cheque Status Inquiry Result (Sayadi)', 'maneli-car-inquiry'); ?></h2>
        <div class="maneli-status-bar">
            <?php
            $colors = [
                1 => ['name' => __('White', 'maneli-car-inquiry'), 'class' => 'white'], 2 => ['name' => __('Yellow', 'maneli-car-inquiry'), 'class' => 'yellow'],
                3 => ['name' => __('Orange', 'maneli-car-inquiry'), 'class' => 'orange'], 4 => ['name' => __('Brown', 'maneli-car-inquiry'), 'class' => 'brown'],
                5 => ['name' => __('Red', 'maneli-car-inquiry'), 'class' => 'red']
            ];
            foreach ($colors as $code => $color) {
                $active_class = ((string)$code === (string)$cheque_color_code) ? 'active' : '';
                echo "<div class='bar-segment segment-{$color['class']} {$active_class}'><span>" . esc_html($color['name']) . "</span></div>";
            }
            ?>
        </div>
         <table class="widefat fixed" style="margin-top: 20px;">
            <thead><tr><th style="width: 20%;"><?php esc_html_e('Credit Status', 'maneli-car-inquiry'); ?></th><th><?php esc_html_e('Description', 'maneli-car-inquiry'); ?></th></tr></thead>
            <tbody>
                <?php
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
                <tr><td><strong class="cheque-color-<?php echo esc_attr($cheque_color_code); ?>"><?php echo esc_html($color_info['text']); ?></strong></td><td><?php echo esc_html($color_info['desc']); ?></td></tr>
            </tbody>
        </table>
         <div style="margin-top: 20px;">
            <h4><?php esc_html_e('Raw Service Response:', 'maneli-car-inquiry'); ?></h4>
            <pre style="direction: ltr; text-align: left; background-color: #f1f1f1; padding: 15px; border-radius: 4px; max-height: 300px; overflow-y: auto;"><?php echo esc_html($inquiry->post_content); ?></pre>
        </div>
    </div>
    
    <div class="report-box report-actions">
        <h2><?php esc_html_e('Final Decision', 'maneli-car-inquiry'); ?></h2>
        <p><?php esc_html_e('After reviewing the information above, specify the final status of this request.', 'maneli-car-inquiry'); ?></p>
        <form id="admin-action-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="maneli_admin_update_status">
            <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
            <input type="hidden" id="final-status-input" name="new_status" value="">
            <input type="hidden" id="rejection-reason-input" name="rejection_reason" value="">
            <?php wp_nonce_field('maneli_admin_update_status_nonce'); ?>
            <button type="button" id="approve-btn" class="button button-primary button-large"><?php esc_html_e('Approve and Assign to Expert', 'maneli-car-inquiry'); ?></button>
            <button type="button" id="reject-btn" class="button button-secondary button-large" style="margin-right: 10px;"><?php esc_html_e('Final Rejection of Request', 'maneli-car-inquiry'); ?></button>
        </form>
    </div>
</div>

<div id="rejection-modal" class="maneli-modal" style="display:none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3><?php esc_html_e('Reason for Rejection', 'maneli-car-inquiry'); ?></h3>
        <p><?php esc_html_e('Please specify the reason for rejecting this request. This reason will be shown to the user.', 'maneli-car-inquiry'); ?></p>
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
        <button type="button" id="confirm-rejection-btn" class="button button-primary"><?php esc_html_e('Submit Reason and Reject Request', 'maneli-car-inquiry'); ?></button>
    </div>
</div>