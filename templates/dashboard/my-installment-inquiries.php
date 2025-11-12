<?php
/**
 * Customer My Installment Inquiries Page
 * Shows customer's own installment inquiries
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
$inquiry_id = isset($_GET['inquiry_id']) ? intval($_GET['inquiry_id']) : 0;
if ($inquiry_id > 0) {
    // Verify ownership - customer can only see their own inquiries
    $inquiry = get_post($inquiry_id);
    if ($inquiry && $inquiry->post_author == $user_id) {
        maneli_get_template_part('shortcodes/inquiry-lists/report-customer-installment', ['inquiry_id' => $inquiry_id]);
        return;
    } else {
        // Unauthorized - redirect
        wp_redirect(home_url('/dashboard/my-installment-inquiries'));
        exit;
    }
}

// Get customer's inquiries - ONLY their own by author (CRITICAL: Must filter by author)
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$inquiries_query = new WP_Query([
    'post_type' => 'inquiry',
    'author' => $user_id, // CRITICAL: Only show inquiries where this user is the author
    'posts_per_page' => 20,
    'paged' => $paged,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
]);

$current_url = home_url('/dashboard/my-installment-inquiries');
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
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('My Installment Inquiries', 'maneli-car-inquiry'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('My Installment Purchase Inquiries', 'maneli-car-inquiry'); ?></h1>
            </div>
            <div class="btn-list">
                <a href="<?php echo home_url('/dashboard/new-inquiry'); ?>" class="btn btn-primary btn-wave">
                    <i class="la la-plus me-1"></i>
                    <?php esc_html_e('New Inquiry', 'maneli-car-inquiry'); ?>
                </a>
            </div>
        </div>
        <!-- End::page-header -->

        <?php
        // Use the same admin template but with author filter for customer
        // The template will use AJAX to load inquiries filtered by author
        // Note: We don't include the wrapper divs from installment-inquiries.php since we already have them
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
                            <?php esc_html_e('My Installment Inquiries', 'maneli-car-inquiry'); ?><span class="badge bg-primary rounded ms-2 fs-12 align-middle" id="inquiry-count-badge">0</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Filter Section -->
                        <div class="p-3 border-bottom">
                            <form id="maneli-inquiry-filter-form" onsubmit="return false;">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-12">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">
                                                <i class="la la-search"></i>
                                            </span>
                                            <input type="search" id="inquiry-search-input" class="form-control form-control-sm" placeholder="<?php esc_attr_e('Search by car name...', 'maneli-car-inquiry'); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row g-2 align-items-end mb-3">
                                    <div class="col">
                                        <label class="form-label"><?php esc_html_e('Status:', 'maneli-car-inquiry'); ?></label>
                                        <select id="status-filter" class="form-select form-select-sm">
                                            <option value=""><?php esc_html_e('All Statuses', 'maneli-car-inquiry'); ?></option>
                                            <?php foreach (Maneli_CPT_Handler::get_tracking_statuses() as $key => $label): ?>
                                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col">
                                        <label class="form-label"><?php esc_html_e('Sort:', 'maneli-car-inquiry'); ?></label>
                                        <select id="inquiry-sort-filter" class="form-select form-select-sm">
                                            <option value="default"><?php esc_html_e('Default (Newest First)', 'maneli-car-inquiry'); ?></option>
                                            <option value="date_desc"><?php esc_html_e('Newest', 'maneli-car-inquiry'); ?></option>
                                            <option value="date_asc"><?php esc_html_e('Oldest', 'maneli-car-inquiry'); ?></option>
                                            <option value="status"><?php esc_html_e('By Status', 'maneli-car-inquiry'); ?></option>
                                        </select>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="col-auto">
                                        <label class="form-label d-block" style="visibility: hidden;">&nbsp;</label>
                                        <div class="d-flex gap-2">
                                            <button type="button" id="inquiry-reset-filters" class="btn btn-outline-secondary btn-sm">
                                                <i class="la la-refresh me-1"></i>
                                                <?php esc_html_e('Clear', 'maneli-car-inquiry'); ?>
                                            </button>
                                            <button type="button" id="inquiry-apply-filters" class="btn btn-primary btn-sm">
                                                <i class="la la-filter me-1"></i>
                                                <?php esc_html_e('Apply', 'maneli-car-inquiry'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
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
                                <tbody id="maneli-inquiry-list-tbody">
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
                        <div id="inquiry-pagination" class="d-flex justify-content-center mt-3">
                        </div>
                        
                        <div id="inquiry-list-loader" style="display: none; text-align:center; padding: 40px;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></span>
                            </div>
                        </div>
                        
                        <div class="maneli-pagination-wrapper mt-3 text-center"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Direct initialization check for installment inquiries
        console.log('🟢 TEMPLATE: installment-inquiries.php script loaded (Customer View)');

        const maneliDigitsHelper = window.maneliLocale || window.maneliDigits || {};

        // Helper function to format numbers based on active locale
        function formatNumberForLocale(num) {
            const htmlLang = (document.documentElement.getAttribute('lang') || '').toLowerCase();
            const htmlDir = (document.documentElement.getAttribute('dir') || '').toLowerCase();
            const shouldUsePersian = (maneliDigitsHelper && typeof maneliDigitsHelper.shouldUsePersianDigits === 'function')
                ? maneliDigitsHelper.shouldUsePersianDigits()
                : (htmlLang.indexOf('fa') === 0 || htmlDir === 'rtl');

            if (maneliDigitsHelper && typeof maneliDigitsHelper.ensureDigits === 'function') {
                return maneliDigitsHelper.ensureDigits(num, shouldUsePersian ? 'fa' : 'en');
            }

            if (!shouldUsePersian) {
                return String(num);
            }

            const persianDigitsFallback = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            const englishDigitsFallback = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            return String(num).replace(/\d/g, function(digit) {
                return persianDigitsFallback[englishDigitsFallback.indexOf(digit)];
            });
        }

        // CRITICAL: Initialize maneliInquiryLists immediately if not already set
        if (typeof maneliInquiryLists === 'undefined') {
            console.warn('🟢 TEMPLATE: maneliInquiryLists is undefined! Creating fallback...');
            window.maneliInquiryLists = {
                ajax_url: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
                nonces: {
                    inquiry_filter: '<?php echo esc_js(wp_create_nonce("maneli_inquiry_filter_nonce")); ?>',
                    details: '<?php echo esc_js(wp_create_nonce("maneli_inquiry_details_nonce")); ?>',
                    assign_expert: '<?php echo esc_js(wp_create_nonce("maneli_inquiry_assign_expert_nonce")); ?>',
                    tracking_status: '<?php echo esc_js(wp_create_nonce("maneli_tracking_status_nonce")); ?>'
                },
                experts: [],
                text: {
                    error: '<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>',
                    success: '<?php echo esc_js(__('Success', 'maneli-car-inquiry')); ?>',
                    server_error: '<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>',
                    unknown_error: '<?php echo esc_js(__('Unknown error', 'maneli-car-inquiry')); ?>',
                    loading: '<?php echo esc_js(__('Loading...', 'maneli-car-inquiry')); ?>'
                },
                installment_rejection_reasons: <?php 
                    $options = get_option('maneli_inquiry_all_options', []);
                    $reasons = array_filter(array_map('trim', explode("\n", $options['installment_rejection_reasons'] ?? '')));
                    echo json_encode(array_values($reasons)); 
                ?>
            };
        }

        const htmlLangAttr = (document.documentElement.getAttribute('lang') || '').toLowerCase();
        const htmlDirAttr = (document.documentElement.getAttribute('dir') || '').toLowerCase();
        const fallbackLocale = htmlLangAttr || (htmlDirAttr === 'rtl' ? 'fa' : (htmlDirAttr === 'ltr' ? 'en' : ''));

        if (typeof maneliInquiryLists.locale === 'undefined' && fallbackLocale) {
            maneliInquiryLists.locale = fallbackLocale;
        }

        if (typeof maneliInquiryLists.use_persian_digits === 'undefined') {
            const localeSource = maneliInquiryLists.locale || fallbackLocale || '';
            maneliInquiryLists.use_persian_digits = localeSource.toLowerCase().indexOf('fa') === 0;
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('🟢 TEMPLATE: DOM ready for customer installment inquiries');
            // Auto-load list after 500ms
            setTimeout(function() {
                if (typeof jQuery !== 'undefined' && jQuery('#maneli-inquiry-list-tbody').length > 0 && typeof maneliInquiryLists !== 'undefined') {
                    console.log('🟢 TEMPLATE: Auto-loading customer inquiries list');
                    jQuery.ajax({
                        url: maneliInquiryLists.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'maneli_filter_inquiries_ajax',
                            nonce: maneliInquiryLists.nonces.inquiry_filter,
                            _ajax_nonce: maneliInquiryLists.nonces.inquiry_filter,
                            page: 1,
                            search: '',
                            status: '',
                            sort: 'default'
                        },
                        success: function(response) {
                            console.log('🟢 TEMPLATE: Auto-load success:', response);
                            if (response && response.success && response.data && response.data.html) {
                                jQuery('#maneli-inquiry-list-tbody').html(response.data.html);
                                var rowCount = jQuery('#maneli-inquiry-list-tbody tr.crm-contact').length;
                                jQuery('#inquiry-count-badge').text(formatNumberForLocale(rowCount));
                                if (response.data.pagination_html) {
                                    jQuery('#inquiry-pagination').html(response.data.pagination_html);
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('🟢 TEMPLATE: Auto-load error:', status, error, xhr.responseText);
                        }
                    });
                }
            }, 500);
        });
        </script>
    </div>
</div>

