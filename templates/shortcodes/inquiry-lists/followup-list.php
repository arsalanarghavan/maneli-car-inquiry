<?php
/**
 * Template for the Follow-up Inquiry List (Admin/Expert view).
 * Modern redesign with Bootstrap theme styling.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Arsalan Arghavan (Redesigned)
 * @version 2.0.0 (Modern redesign)
 */

if (!defined('ABSPATH')) {
    exit;
}

$experts = current_user_can('manage_maneli_inquiries') ? get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']) : [];
?>

<div class="row">
    <div class="col-xl-12">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-warning-transparent">
                                    <i class="la la-clock fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13"><?php esc_html_e("Today's Follow-ups", 'maneli-car-inquiry'); ?></span>
                                </div>
                                <h4 class="fw-semibold mb-0" id="today-followups-count">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-danger-transparent">
                                    <i class="la la-exclamation-triangle fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13"><?php esc_html_e('Overdue', 'maneli-car-inquiry'); ?></span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-danger" id="overdue-followups-count">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-info-transparent">
                                    <i class="la la-calendar-alt fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13"><?php esc_html_e('This Week', 'maneli-car-inquiry'); ?></span>
                                </div>
                                <h4 class="fw-semibold mb-0" id="week-followups-count">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-success-transparent">
                                    <i class="la la-list-alt fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13"><?php esc_html_e('Total', 'maneli-car-inquiry'); ?></span>
                                </div>
                                <h4 class="fw-semibold mb-0" id="total-followups-count">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-tasks me-2"></i>
                    <?php esc_html_e('Follow-up Inquiries List', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <!-- Info Alert -->
                <div class="alert alert-info border-info d-flex align-items-center" role="alert">
                    <i class="la la-info-circle fs-20 me-2"></i>
                    <div>
                        <strong><?php esc_html_e('Guide:', 'maneli-car-inquiry'); ?></strong>
                        <?php esc_html_e('Inquiries that require follow-up contact on the specified dates are displayed here.', 'maneli-car-inquiry'); ?>
                    </div>
                </div>
                
                <!-- Filters -->
                <form id="maneli-followup-filter-form" onsubmit="return false;">
                    <div class="row g-3 mb-4">
                        <div class="col-md-<?php echo (current_user_can('manage_maneli_inquiries') && !empty($experts)) ? '6' : '12'; ?>">
                            <label class="form-label fw-semibold">
                                <i class="la la-search me-1"></i>
                                <?php esc_html_e('Search', 'maneli-car-inquiry'); ?>:
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="la la-search"></i>
                                </span>
                                <input type="search" id="followup-search-input" class="form-control" placeholder="<?php esc_attr_e('Customer name, car name, mobile...', 'maneli-car-inquiry'); ?>">
                            </div>
                        </div>
                        
                        <?php if (current_user_can('manage_maneli_inquiries') && !empty($experts)): ?>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="la la-user-tie me-1"></i>
                                    <?php esc_html_e('Filter by Expert', 'maneli-car-inquiry'); ?>:
                                </label>
                                <select id="followup-expert-filter" class="form-select maneli-select2">
                                    <option value=""><?php esc_html_e('All Experts', 'maneli-car-inquiry'); ?></option>
                                    <?php foreach ($experts as $expert) : ?>
                                        <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap">
                        <thead class="table-primary">
                            <tr>
                                <th><i class="la la-hashtag me-1"></i><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                <th><i class="la la-user me-1"></i><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th>
                                <th><i class="la la-car me-1"></i><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                <th><i class="la la-calendar me-1"></i><?php esc_html_e('Follow-up Date', 'maneli-car-inquiry'); ?></th>
                                <th><i class="la la-info-circle me-1"></i><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                <?php if (current_user_can('manage_maneli_inquiries')) : ?>
                                    <th><i class="la la-user-tie me-1"></i><?php esc_html_e('Assigned', 'maneli-car-inquiry'); ?></th>
                                <?php endif; ?>
                                <th><i class="la la-clock me-1"></i><?php esc_html_e('Created', 'maneli-car-inquiry'); ?></th>
                                <th><i class="la la-wrench me-1"></i><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="maneli-followup-list-tbody">
                            <tr>
                                <td colspan="<?php echo current_user_can('manage_maneli_inquiries') ? '8' : '7'; ?>" class="text-center">
                                    <div class="py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></span>
                                        </div>
                                        <p class="text-muted mt-3"><?php esc_html_e('Loading follow-ups...', 'maneli-car-inquiry'); ?></p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Loader -->
                <div id="followup-list-loader" class="maneli-list-loader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></span>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="maneli-pagination-wrapper mt-4 text-center"></div>
            </div>
        </div>
    </div>
</div>

<!-- Tracking Status Modal -->
<div class="modal fade" id="tracking-status-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary-transparent">
                <h5 class="modal-title">
                    <i class="la la-edit me-2"></i>
                    <?php esc_html_e('Set Tracking Status', 'maneli-car-inquiry'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="tracking-status-select" class="form-label fw-semibold">
                        <i class="la la-list me-1"></i>
                        <?php esc_html_e('Select Status:', 'maneli-car-inquiry'); ?>
                    </label>
                    <select id="tracking-status-select" class="form-select">
                        <?php foreach (Maneli_CPT_Handler::get_tracking_statuses() as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="calendar-wrapper" class="maneli-initially-hidden mb-3">
                    <label id="calendar-label" class="form-label fw-semibold">
                        <i class="la la-calendar me-1"></i>
                        <?php esc_html_e('Select Date:', 'maneli-car-inquiry'); ?>
                    </label>
                    <input type="text" id="tracking-date-picker" class="form-control maneli-datepicker" readonly>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-wave" data-bs-dismiss="modal">
                    <i class="la la-times me-1"></i>
                    <?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?>
                </button>
                <button type="button" id="confirm-tracking-status-btn" class="btn btn-primary btn-wave">
                    <i class="la la-check me-1"></i>
                    <?php esc_html_e('Confirm Status', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Follow-up List Custom Styles */
.table-primary th {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: white;
    font-weight: 600;
    border: none;
}

.table-primary th i {
    opacity: 0.9;
}

.table-hover tbody tr:hover {
    background-color: rgba(var(--primary-rgb), 0.03);
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.form-label.fw-semibold i {
    color: var(--primary-color);
}

.input-group-text.bg-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-color: #dee2e6;
}

#tracking-status-modal .modal-header.bg-primary-transparent {
    background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, transparent 100%) !important;
    border-bottom: 2px solid var(--primary-color);
}
</style>
