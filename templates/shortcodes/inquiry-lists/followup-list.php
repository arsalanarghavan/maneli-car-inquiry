<?php
/**
 * Template for the Follow-up Inquiry List (Admin/Expert view).
 *
 * This template displays inquiries with tracking_status = 'follow_up'.
 * The table body is populated and managed via AJAX.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Arsalan Arghavan
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$experts = current_user_can('manage_maneli_inquiries') ? get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']) : [];
?>

<div class="maneli-full-width-container">
    <div class="maneli-inquiry-wrapper">

        <h3 style="margin-top:20px;"><?php esc_html_e('Follow-up List', 'maneli-car-inquiry'); ?></h3>
        <p style="color: #666; margin-bottom: 20px;"><?php esc_html_e('Inquiries that require follow-up contact on the specified dates.', 'maneli-car-inquiry'); ?></p>
        
        <div class="user-list-filters">
            <form id="maneli-followup-filter-form" onsubmit="return false;">
                <div class="filter-row search-row">
                    <input type="search" id="followup-search-input" class="search-input" placeholder="<?php esc_attr_e('Search by customer name, car name, national ID or mobile...', 'maneli-car-inquiry'); ?>">
                </div>
                <div class="filter-row">
                    <?php if (current_user_can('manage_maneli_inquiries') && !empty($experts)): ?>
                     <div class="filter-group">
                        <label for="followup-expert-filter"><?php esc_html_e('Assigned Expert:', 'maneli-car-inquiry'); ?></label>
                        <select id="followup-expert-filter" class="maneli-select2">
                            <option value=""><?php esc_html_e('All Experts', 'maneli-car-inquiry'); ?></option>
                            <?php foreach ($experts as $expert) : ?>
                                <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="maneli-table-wrapper">
            <table class="shop_table shop_table_responsive">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                        <th><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th>
                        <th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                        <th><?php esc_html_e('Follow-up Date', 'maneli-car-inquiry'); ?></th>
                        <th class="inquiry-status-cell-installment"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                        <?php if (current_user_can('manage_maneli_inquiries')) echo '<th>' . esc_html__('Assigned', 'maneli-car-inquiry') . '</th>'; ?>
                        <th><?php esc_html_e('Date Created', 'maneli-car-inquiry'); ?></th>
                        <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                    </tr>
                </thead>
                <tbody id="maneli-followup-list-tbody">
                    <!-- Content loaded via AJAX -->
                </tbody>
            </table>
        </div>
        
        <div id="followup-list-loader" style="display: none; text-align:center; padding: 40px;">
            <div class="spinner is-active" style="float:none;"></div>
        </div>

        <div class="maneli-pagination-wrapper" style="margin-top: 20px; text-align: center;">
            <!-- Pagination loaded via AJAX -->
        </div>
    </div>
</div>

