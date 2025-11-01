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

// CRITICAL: Always get fresh role from WordPress user object
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$current_user = wp_get_current_user();
// Always check fresh roles from database
$is_admin = current_user_can('manage_maneli_inquiries');
$is_manager = in_array('maneli_manager', $current_user->roles, true) || in_array('maneli_admin', $current_user->roles, true);
$is_expert = in_array('maneli_expert', $current_user->roles, true);
$is_customer = !$is_admin && !$is_manager && !$is_expert;

// Check if viewing a single inquiry report
$cash_inquiry_id = isset($_GET['cash_inquiry_id']) ? intval($_GET['cash_inquiry_id']) : 0;
if ($cash_inquiry_id > 0) {
    if ($is_customer) {
        // Customer sees customer report
        maneli_get_template_part('shortcodes/inquiry-lists/report-customer-cash', ['inquiry_id' => $cash_inquiry_id]);
    } else {
        // Admin/Expert sees admin report
        maneli_get_template_part('shortcodes/inquiry-lists/report-admin-cash', ['inquiry_id' => $cash_inquiry_id]);
    }
    return;
}

$experts = $is_admin ? get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']) : [];
?>

<div class="main-content app-content">
    <div class="container-fluid">

        <!-- Start::page-header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item">
                        <a href="<?php echo home_url('/dashboard'); ?>"><?php esc_html_e('Dashboard', 'maneli-car-inquiry'); ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Cash Inquiries', 'maneli-car-inquiry'); ?></li>
                </ol>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Cash Inquiries', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- End::page-header -->

<div class="row">
    <div class="col-xl-12">
        <?php 
        // Only show statistics widgets for admin/expert, not for customers
        if (!$is_customer) {
            echo Maneli_Admin_Dashboard_Widgets::render_cash_inquiry_statistics_widgets(); 
        }
        ?>

        <div class="card custom-card mt-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="card-title">
                    <?php esc_html_e('Cash Inquiries', 'maneli-car-inquiry'); ?><span class="badge bg-primary rounded ms-2 fs-12 align-middle" id="cash-inquiry-count-badge">0</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success-light btn-sm" id="cash-export-csv-btn">
                        <i class="la la-download me-1"></i><?php esc_html_e('Export CSV', 'maneli-car-inquiry'); ?>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Filter Section -->
                <div class="p-3 border-bottom">
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
                            
                            <?php if ($is_admin && !empty($experts)) : ?>
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
                        <div class="row g-3 mb-0">
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
                </div>

                <!-- Loading Indicator -->
                <div id="cash-inquiry-loading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></span>
                    </div>
                    <p class="mt-2 text-muted"><?php esc_html_e('Loading inquiries...', 'maneli-car-inquiry'); ?></p>
                </div>

                <div class="table-responsive">
                    <table class="table text-nowrap table-hover">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Mobile', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                <?php if ($is_admin): ?><th scope="col"><?php esc_html_e('Expert', 'maneli-car-inquiry'); ?></th><?php endif; ?>
                                <th scope="col"><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
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

                <div id="cash-inquiry-list-loader" style="display: none; text-align:center; padding: 40px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></span>
                    </div>
                </div>
                
                <div class="maneli-cash-pagination-wrapper mt-3 text-center"></div>
            </div>
        </div>
    </div>
</div>

    </div>
</div>
<!-- End::main-content -->
