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

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-calendar-check me-2"></i>
                    <?php esc_html_e('Follow-up List', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <i class="la la-info-circle me-2"></i>
                    <?php esc_html_e('Inquiries that require follow-up contact on the specified dates.', 'maneli-car-inquiry'); ?>
                </div>
                
                <form id="maneli-followup-filter-form" onsubmit="return false;">
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-search"></i>
                                </span>
                                <input type="search" id="followup-search-input" class="form-control" placeholder="<?php esc_attr_e('Search by customer name, car name, national ID or mobile...', 'maneli-car-inquiry'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <?php if (current_user_can('manage_maneli_inquiries') && !empty($experts)): ?>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Assigned Expert:', 'maneli-car-inquiry'); ?></label>
                                <select id="followup-expert-filter" class="form-select maneli-select2">
                                    <option value=""><?php esc_html_e('All Experts', 'maneli-car-inquiry'); ?></option>
                                    <?php foreach ($experts as $expert) : ?>
                                        <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Follow-up Date', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
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
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">در حال بارگذاری...</span>
                    </div>
                </div>

                <div class="maneli-pagination-wrapper mt-3 text-center">
                    <!-- Pagination loaded via AJAX -->
                </div>
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
                
                <div id="calendar-wrapper" style="display:none;">
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
