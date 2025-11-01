<?php
/**
 * Customer My Cash Inquiries Page
 * Shows customer's own cash inquiries
 *
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = get_current_user_id();

// CRITICAL: Check current role - if user is expert or admin, they shouldn't see customer pages
$is_admin = current_user_can('manage_maneli_inquiries');
$is_expert = in_array('maneli_expert', $current_user->roles, true);
$is_customer = !$is_admin && !$is_expert;

// Only customers can access this page
if (!$is_customer) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Check if viewing a single inquiry report
$cash_inquiry_id = isset($_GET['cash_inquiry_id']) ? intval($_GET['cash_inquiry_id']) : 0;
if ($cash_inquiry_id > 0) {
    // Verify ownership - customer can only see their own inquiries
    $inquiry = get_post($cash_inquiry_id);
    if ($inquiry && $inquiry->post_author == $user_id) {
        maneli_get_template_part('shortcodes/inquiry-lists/report-customer-cash', ['inquiry_id' => $cash_inquiry_id]);
        return;
    } else {
        // Unauthorized - redirect
        wp_redirect(home_url('/dashboard/my-cash-inquiries'));
        exit;
    }
}

// Get customer's cash inquiries - ONLY their own by author (CRITICAL: Must filter by author)
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$inquiries_query = new WP_Query([
    'post_type' => 'cash_inquiry',
    'author' => $user_id, // CRITICAL: Only show inquiries where this user is the author
    'posts_per_page' => 20,
    'paged' => $paged,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
]);

$current_url = home_url('/dashboard/my-cash-inquiries');
$payment_status = isset($_GET['payment_status']) ? sanitize_text_field($_GET['payment_status']) : null;
?>
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item">
                            <a href="<?php echo home_url('/dashboard'); ?>"><?php esc_html_e('Dashboard', 'maneli-car-inquiry'); ?></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('My Cash Inquiries', 'maneli-car-inquiry'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('My Cash Purchase Inquiries', 'maneli-car-inquiry'); ?></h1>
            </div>
            <div class="btn-list">
                <a href="<?php echo home_url('/dashboard/new-cash-inquiry'); ?>" class="btn btn-primary btn-wave">
                    <i class="la la-plus me-1"></i>
                    <?php esc_html_e('Create New Inquiry', 'maneli-car-inquiry'); ?>
                </a>
            </div>
        </div>
        <!-- End::page-header -->

        <?php
        // Use the same admin template but with author filter for customer
        // The template will use AJAX to load inquiries filtered by author
        // Note: We don't include the wrapper divs from cash-inquiries.php since we already have them
        ?>
        
        <div class="row">
            <div class="col-xl-12">
                <?php 
                // Only show statistics widgets for admin/expert, not for customers
                // Statistics widgets are not shown for customers
                ?>

                <div class="card custom-card mt-4">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="card-title">
                            <?php esc_html_e('My Cash Inquiries', 'maneli-car-inquiry'); ?><span class="badge bg-primary rounded ms-2 fs-12 align-middle" id="cash-inquiry-count-badge">0</span>
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
                                            <input type="search" id="cash-inquiry-search-input" class="form-control" placeholder="<?php esc_attr_e('Search by car name...', 'maneli-car-inquiry'); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Filter Controls -->
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
                                    
                                    <div class="col-md-6">
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
                                                پاک کردن فیلترها
                                            </button>
                                            <button type="button" id="cash-inquiry-apply-filters" class="btn btn-primary btn-sm">
                                                <i class="la la-filter me-1"></i>
                                                اعمال فیلتر
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
                                        <th scope="col"><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="maneli-cash-inquiry-list-tbody">
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
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
        
        <script>
        // Direct initialization check for cash inquiries
        console.log('🔴 TEMPLATE: cash-inquiries.php script loaded (Customer View)');

        // Helper function to convert numbers to Persian
        function toPersianNumber(num) {
            const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            const englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            return String(num).replace(/\d/g, function(digit) {
                return persianDigits[englishDigits.indexOf(digit)];
            });
        }

        // CRITICAL: Initialize maneliInquiryLists immediately if not already set
        if (typeof maneliInquiryLists === 'undefined') {
            console.warn('🔴 TEMPLATE: maneliInquiryLists is undefined! Creating fallback...');
            window.maneliInquiryLists = {
                ajax_url: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
                nonces: {
                    cash_filter: '<?php echo esc_js(wp_create_nonce("maneli_cash_inquiry_filter_nonce")); ?>',
                    cash_details: '<?php echo esc_js(wp_create_nonce("maneli_cash_inquiry_details_nonce")); ?>',
                    cash_update: '<?php echo esc_js(wp_create_nonce("maneli_cash_inquiry_update_nonce")); ?>',
                    cash_delete: '<?php echo esc_js(wp_create_nonce("maneli_cash_inquiry_delete_nonce")); ?>',
                    cash_set_downpayment: '<?php echo esc_js(wp_create_nonce("maneli_cash_set_downpayment_nonce")); ?>',
                    cash_assign_expert: '<?php echo esc_js(wp_create_nonce("maneli_cash_inquiry_assign_expert_nonce")); ?>'
                },
                experts: [],
                text: {
                    error: <?php echo wp_json_encode(esc_html__('Error!', 'maneli-car-inquiry')); ?>,
                    success: <?php echo wp_json_encode(esc_html__('Success!', 'maneli-car-inquiry')); ?>,
                    server_error: <?php echo wp_json_encode(esc_html__('Server error. Please try again.', 'maneli-car-inquiry')); ?>,
                    unknown_error: <?php echo wp_json_encode(esc_html__('Unknown error', 'maneli-car-inquiry')); ?>
                },
                cash_rejection_reasons: <?php 
                    $options = get_option('maneli_inquiry_all_options', []);
                    $reasons = array_filter(array_map('trim', explode("\n", $options['cash_inquiry_rejection_reasons'] ?? '')));
                    echo json_encode(array_values($reasons)); 
                ?>
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔴 TEMPLATE: DOM ready for customer cash inquiries');
            // Auto-load list after 500ms
            setTimeout(function() {
                if (typeof jQuery !== 'undefined' && jQuery('#maneli-cash-inquiry-list-tbody').length > 0 && typeof maneliInquiryLists !== 'undefined') {
                    console.log('🔴 TEMPLATE: Auto-loading customer cash inquiries list');
                    jQuery.ajax({
                        url: maneliInquiryLists.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'maneli_filter_cash_inquiries_ajax',
                            nonce: maneliInquiryLists.nonces.cash_filter,
                            _ajax_nonce: maneliInquiryLists.nonces.cash_filter,
                            page: 1,
                            search: '',
                            status: '',
                            sort: 'default'
                        },
                        success: function(response) {
                            console.log('🔴 TEMPLATE: Auto-load success:', response);
                            if (response && response.success && response.data && response.data.html) {
                                jQuery('#maneli-cash-inquiry-list-tbody').html(response.data.html);
                                var rowCount = jQuery('#maneli-cash-inquiry-list-tbody tr.crm-contact').length;
                                jQuery('#cash-inquiry-count-badge').text(toPersianNumber(rowCount));
                                if (response.data.pagination_html) {
                                    jQuery('#cash-inquiry-pagination').html(response.data.pagination_html);
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('🔴 TEMPLATE: Auto-load error:', status, error, xhr.responseText);
                        }
                    });
                }
            }, 500);
        });
        </script>
    </div>
</div>

