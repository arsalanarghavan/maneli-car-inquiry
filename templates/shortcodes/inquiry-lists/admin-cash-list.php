<?php
/**
 * Template for the Admin/Expert view of the Cash Inquiry List.
 *
 * This template displays statistical widgets, filter controls, and the table for cash inquiries.
 * The table body is populated and managed via AJAX.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Gemini
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$experts = current_user_can('manage_maneli_inquiries') ? get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']) : [];
?>

<div class="row">
    <div class="col-xl-12">
        <?php echo Maneli_Admin_Dashboard_Widgets::render_cash_inquiry_statistics_widgets(); ?>

        <div class="card custom-card mt-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-list-alt me-2"></i>
                    <?php esc_html_e('Cash Purchase Requests List', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <form id="maneli-cash-inquiry-filter-form" onsubmit="return false;">
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-search"></i>
                                </span>
                                <input type="search" id="cash-inquiry-search-input" class="form-control" placeholder="<?php esc_attr_e('Search by customer name, car name, or mobile...', 'maneli-car-inquiry'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><?php esc_html_e('Status:', 'maneli-car-inquiry'); ?></label>
                            <select id="cash-inquiry-status-filter" class="form-select">
                                <option value=""><?php esc_html_e('All Statuses', 'maneli-car-inquiry'); ?></option>
                                <?php foreach (Maneli_CPT_Handler::get_all_cash_inquiry_statuses() as $key => $label) : ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if (current_user_can('manage_maneli_inquiries') && !empty($experts)) : ?>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Assigned Expert:', 'maneli-car-inquiry'); ?></label>
                                <select id="cash-expert-filter" class="form-select maneli-select2">
                                    <option value=""><?php esc_html_e('All Experts', 'maneli-car-inquiry'); ?></option>
                                    <?php foreach ($experts as $expert) : ?>
                                        <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Mobile', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Assigned', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="maneli-cash-inquiry-list-tbody">
                        </tbody>
                    </table>
                </div>

                <div id="cash-inquiry-list-loader" style="display: none; text-align:center; padding: 40px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">در حال بارگذاری...</span>
                    </div>
                </div>
                
                <div class="maneli-cash-pagination-wrapper mt-3 text-center"></div>
            </div>
        </div>
    </div>
</div>
