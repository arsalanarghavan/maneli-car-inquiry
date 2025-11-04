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

<div class="row">
    <div class="col-xl-12">
        <?php echo Maneli_Admin_Dashboard_Widgets::render_inquiry_statistics_widgets(); ?>
        
        <div class="card custom-card mt-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-list-alt me-2"></i>
                    <?php esc_html_e('Complete List of Inquiries', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <form id="maneli-inquiry-filter-form" onsubmit="return false;">
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-search"></i>
                                </span>
                                <input type="search" id="inquiry-search-input" class="form-control" placeholder="<?php esc_attr_e('Search by customer name, car name, national ID or mobile...', 'maneli-car-inquiry'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><?php esc_html_e('Status:', 'maneli-car-inquiry'); ?></label>
                            <select id="status-filter" class="form-select">
                                <option value=""><?php esc_html_e('All Statuses', 'maneli-car-inquiry'); ?></option>
                                <?php foreach (Maneli_CPT_Handler::get_tracking_statuses() as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if (current_user_can('manage_maneli_inquiries') && !empty($experts)): ?>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Assigned Expert:', 'maneli-car-inquiry'); ?></label>
                                <select id="expert-filter" class="form-select maneli-select2">
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
                                <th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                <?php if (current_user_can('manage_maneli_inquiries')) echo '<th>' . esc_html__('Assigned', 'maneli-car-inquiry') . '</th>'; ?>
                                <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="maneli-inquiry-list-tbody">
                        </tbody>
                    </table>
                </div>
                
                <div id="inquiry-list-loader" class="maneli-list-loader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></span>
                    </div>
                </div>

                <div class="maneli-pagination-wrapper mt-3 text-center"></div>
            </div>
        </div>
    </div>
</div>

<!-- Tracking Status Modal -->
<div class="modal fade" id="tracking-status-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Set Tracking Status', 'maneli-car-inquiry'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="tracking-status-select" class="form-label"><?php esc_html_e('Select Status:', 'maneli-car-inquiry'); ?></label>
                    <select id="tracking-status-select" class="form-select">
                        <?php foreach (Maneli_CPT_Handler::get_tracking_statuses() as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="calendar-wrapper" class="maneli-initially-hidden">
                    <label id="calendar-label" class="form-label"><?php esc_html_e('Select Date:', 'maneli-car-inquiry'); ?></label>
                    <input type="text" id="tracking-date-picker" class="form-control maneli-datepicker" readonly>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></button>
                <button type="button" id="confirm-tracking-status-btn" class="btn btn-primary">
                    <?php esc_html_e('Confirm Status', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
