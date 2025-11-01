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
                    <i class="la la-dollar-sign me-2"></i>
                    <?php esc_html_e('Cash Inquiries List', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <form id="maneli-cash-inquiry-filter-form" onsubmit="return false;">
                    <!-- Search Field -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-search"></i>
                                </span>
                                <input type="search" id="cash-inquiry-search-input" class="form-control" placeholder="<?php esc_attr_e('Search by customer name, car name or mobile number...', 'maneli-car-inquiry'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Controls -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label"><?php esc_html_e('Status:', 'maneli-car-inquiry'); ?></label>
                            <select id="cash-inquiry-status-filter" class="form-select">
                                <option value=""><?php esc_html_e('All Statuses', 'maneli-car-inquiry'); ?></option>
                                <?php foreach (Maneli_CPT_Handler::get_all_cash_inquiry_statuses() as $key => $label) : ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if (current_user_can('manage_maneli_inquiries') && !empty($experts)) : ?>
                            <div class="col-md-4">
                                <label class="form-label"><?php esc_html_e('Expert:', 'maneli-car-inquiry'); ?></label>
                                <select id="cash-expert-filter" class="form-select maneli-select2">
                                    <option value=""><?php esc_html_e('All Experts', 'maneli-car-inquiry'); ?></option>
                                    <?php foreach ($experts as $expert) : ?>
                                        <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?php esc_html_e('Sort:', 'maneli-car-inquiry'); ?></label>
                            <select id="cash-inquiry-sort-filter" class="form-select">
                                <option value="default"><?php esc_html_e('Default (Newest First)', 'maneli-car-inquiry'); ?></option>
                                <option value="date_desc"><?php esc_html_e('Newest', 'maneli-car-inquiry'); ?></option>
                                <option value="date_asc"><?php esc_html_e('Oldest', 'maneli-car-inquiry'); ?></option>
                                <option value="status"><?php esc_html_e('By Status', 'maneli-car-inquiry'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Filter Actions -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="d-flex gap-2">
                                <button type="button" id="cash-inquiry-reset-filters" class="btn btn-outline-secondary btn-sm">
                                    <i class="la la-refresh me-1"></i>
                                    <?php esc_html_e('Clear Filters', 'maneli-car-inquiry'); ?>
                                </button>
                                <button type="button" id="cash-inquiry-apply-filters" class="btn btn-primary btn-sm">
                                    <i class="la la-filter me-1"></i>
                                    <?php esc_html_e('Apply Filters', 'maneli-car-inquiry'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Loading Indicator -->
                <div id="cash-inquiry-loading" class="text-center py-4 maneli-initially-hidden">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></span>
                    </div>
                    <p class="mt-2 text-muted"><?php esc_html_e('Loading inquiries...', 'maneli-car-inquiry'); ?></p>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Mobile', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Expert', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="maneli-cash-inquiry-list-tbody">
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="la la-spinner la-spin fs-24 text-muted"></i>
                                    <p class="mt-2 text-muted"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div id="cash-inquiry-pagination" class="d-flex justify-content-center mt-3">
                </div>

                <div id="cash-inquiry-list-loader" class="maneli-list-loader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></span>
                    </div>
                </div>
                
                <div class="maneli-cash-pagination-wrapper mt-3 text-center"></div>
            </div>
        </div>
    </div>
</div>
