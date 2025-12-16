<?php
/**
 * Template for the Admin/Expert view of the Cash Inquiry List.
 *
 * This template displays statistical widgets, filter controls, and the table for cash inquiries.
 * The table body is populated and managed via AJAX.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Gemini
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$experts = current_user_can('manage_autopuzzle_inquiries') ? get_users(['role' => 'autopuzzle_expert', 'orderby' => 'display_name', 'order' => 'ASC']) : [];
?>

<div class="row">
    <div class="col-xl-12">
        <?php echo Autopuzzle_Admin_Dashboard_Widgets::render_cash_inquiry_statistics_widgets(); ?>

        <div class="card custom-card mt-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-dollar-sign me-2"></i>
                    <?php esc_html_e('Cash Inquiries List', 'autopuzzle'); ?>
                </div>
            </div>
            <div class="card-body">
                <form id="autopuzzle-cash-inquiry-filter-form" onsubmit="return false;">
                    <!-- Search Field -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-search"></i>
                                </span>
                                <input type="search" id="cash-inquiry-search-input" class="form-control" placeholder="<?php esc_attr_e('Search by customer name, car name or mobile number...', 'autopuzzle'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Controls -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label"><?php esc_html_e('Status:', 'autopuzzle'); ?></label>
                            <select id="cash-inquiry-status-filter" class="form-select">
                                <option value=""><?php esc_html_e('All Statuses', 'autopuzzle'); ?></option>
                                <?php foreach (Autopuzzle_CPT_Handler::get_all_cash_inquiry_statuses() as $key => $label) : ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if (current_user_can('manage_autopuzzle_inquiries') && !empty($experts)) : ?>
                            <div class="col-md-4">
                                <label class="form-label"><?php esc_html_e('Expert:', 'autopuzzle'); ?></label>
                                <select id="cash-expert-filter" class="form-select autopuzzle-select2">
                                    <option value=""><?php esc_html_e('All Experts', 'autopuzzle'); ?></option>
                                    <?php foreach ($experts as $expert) : ?>
                                        <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?php esc_html_e('Sort:', 'autopuzzle'); ?></label>
                            <select id="cash-inquiry-sort-filter" class="form-select">
                                <option value="default"><?php esc_html_e('Default (Newest First)', 'autopuzzle'); ?></option>
                                <option value="date_desc"><?php esc_html_e('Newest', 'autopuzzle'); ?></option>
                                <option value="date_asc"><?php esc_html_e('Oldest', 'autopuzzle'); ?></option>
                                <option value="status"><?php esc_html_e('By Status', 'autopuzzle'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Filter Actions -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="d-flex gap-2">
                                <button type="button" id="cash-inquiry-reset-filters" class="btn btn-outline-secondary btn-sm">
                                    <i class="la la-refresh me-1"></i>
                                    <?php esc_html_e('Clear Filters', 'autopuzzle'); ?>
                                </button>
                                <button type="button" id="cash-inquiry-apply-filters" class="btn btn-primary btn-sm">
                                    <i class="la la-filter me-1"></i>
                                    <?php esc_html_e('Apply Filters', 'autopuzzle'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Loading Indicator -->
                <div id="cash-inquiry-loading" class="text-center py-4 autopuzzle-initially-hidden">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'autopuzzle'); ?></span>
                    </div>
                    <p class="mt-2 text-muted"><?php esc_html_e('Loading inquiries...', 'autopuzzle'); ?></p>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th><?php esc_html_e('ID', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Customer', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Mobile', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Car', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Status', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Expert', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Date', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Actions', 'autopuzzle'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="autopuzzle-cash-inquiry-list-tbody">
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="la la-spinner la-spin fs-24 text-muted"></i>
                                    <p class="mt-2 text-muted"><?php esc_html_e('Loading...', 'autopuzzle'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div id="cash-inquiry-pagination" class="d-flex justify-content-center mt-3">
                </div>

                <div id="cash-inquiry-list-loader" class="autopuzzle-list-loader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'autopuzzle'); ?></span>
                    </div>
                </div>
                
                <div class="autopuzzle-cash-pagination-wrapper mt-3 text-center"></div>
            </div>
        </div>
    </div>
</div>

<?php
// Include SMS History Modal (shared template)
autopuzzle_get_template_part('partials/sms-history-modal');
?>
