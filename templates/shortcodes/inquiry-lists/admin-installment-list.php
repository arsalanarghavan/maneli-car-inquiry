<?php
/**
 * Template for the Admin/Expert view of the Installment Inquiry List.
 *
 * This template displays statistical widgets, filter controls, and the table for installment inquiries.
 * The table body is populated and managed via AJAX.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Gemini
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Maneli_Admin_Dashboard_Widgets و Maneli_CPT_Handler باید قبلا در هسته بارگذاری شده باشند.
$experts = current_user_can('manage_maneli_inquiries') ? get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']) : [];
?>

<div class="maneli-full-width-container">
    <div class="maneli-inquiry-wrapper">

        <?php echo Maneli_Admin_Dashboard_Widgets::render_inquiry_statistics_widgets(); ?>
        
        <h3 style="margin-top:40px;"><?php esc_html_e('Complete List of Inquiries', 'maneli-car-inquiry'); ?></h3>
        
        <div class="user-list-filters">
            <form id="maneli-inquiry-filter-form" onsubmit="return false;">
                <div class="filter-row search-row">
                    <input type="search" id="inquiry-search-input" class="search-input" placeholder="<?php esc_attr_e('Search by customer name, car name, national ID or mobile...', 'maneli-car-inquiry'); ?>">
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="status-filter"><?php esc_html_e('Status:', 'maneli-car-inquiry'); ?></label>
                        <select id="status-filter">
                            <option value=""><?php esc_html_e('All Statuses', 'maneli-car-inquiry'); ?></option>
                            <?php foreach (Maneli_CPT_Handler::get_all_statuses() as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (current_user_can('manage_maneli_inquiries') && !empty($experts)): ?>
                     <div class="filter-group">
                        <label for="expert-filter"><?php esc_html_e('Assigned Expert:', 'maneli-car-inquiry'); ?></label>
                        <select id="expert-filter" class="maneli-select2">
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
                        <th class="inquiry-status-cell-installment"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                        <?php if (current_user_can('manage_maneli_inquiries')) echo '<th>' . esc_html__('Assigned', 'maneli-car-inquiry') . '</th>'; ?>
                        <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                        <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                    </tr>
                </thead>
                <tbody id="maneli-inquiry-list-tbody">
                    </tbody>
            </table>
        </div>
        
        <div id="inquiry-list-loader" style="display: none; text-align:center; padding: 40px;">
            <div class="spinner is-active" style="float:none;"></div>
        </div>

        <div class="maneli-pagination-wrapper" style="margin-top: 20px; text-align: center;">
            </div>
    </div>

    <!-- Tracking Status Modal -->
    <div id="tracking-status-modal" class="maneli-modal-frontend" style="display:none;">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h3><?php esc_html_e('Set Tracking Status', 'maneli-car-inquiry'); ?></h3>
            <div class="form-group">
                <label for="tracking-status-select"><?php esc_html_e('Select Status:', 'maneli-car-inquiry'); ?></label>
                <select id="tracking-status-select" style="width: 100%; padding: 8px; font-size: 14px;">
                    <?php foreach (Maneli_CPT_Handler::get_tracking_statuses() as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" id="calendar-wrapper" style="display:none; margin-top: 20px;">
                <label id="calendar-label"><?php esc_html_e('Select Date:', 'maneli-car-inquiry'); ?></label>
                <input type="text" id="tracking-date-picker" class="maneli-datepicker" style="width: 100%; padding: 8px; font-size: 14px;" readonly>
            </div>
            
            <button type="button" id="confirm-tracking-status-btn" class="button button-primary" style="margin-top: 20px;">
                <?php esc_html_e('Confirm Status', 'maneli-car-inquiry'); ?>
            </button>
        </div>
    </div>
</div>