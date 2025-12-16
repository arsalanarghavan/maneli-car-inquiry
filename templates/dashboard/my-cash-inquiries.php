<?php
/**
 * Customer My Cash Inquiries Page
 * Shows customer's own cash inquiries
 *
 * @package AutoPuzzle
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = get_current_user_id();

// CRITICAL: Check current role - if user is expert or admin, they shouldn't see customer pages
$is_admin = current_user_can('manage_autopuzzle_inquiries');
$is_expert = in_array('autopuzzle_expert', $current_user->roles, true);
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
        autopuzzle_get_template_part('shortcodes/inquiry-lists/report-customer-cash', ['inquiry_id' => $cash_inquiry_id]);
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
                            <a href="<?php echo home_url('/dashboard'); ?>"><?php esc_html_e('Dashboard', 'autopuzzle'); ?></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('My Cash Inquiries', 'autopuzzle'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('My Cash Purchase Inquiries', 'autopuzzle'); ?></h1>
            </div>
            <div class="btn-list">
                <a href="<?php echo home_url('/dashboard/new-cash-inquiry'); ?>" class="btn btn-primary btn-wave">
                    <i class="la la-plus me-1"></i>
                    <?php esc_html_e('Create New Inquiry', 'autopuzzle'); ?>
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
                            <?php esc_html_e('My Cash Inquiries', 'autopuzzle'); ?><span class="badge bg-primary rounded ms-2 fs-12 align-middle" id="cash-inquiry-count-badge">0</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Filter Section -->
                        <div class="p-3 border-bottom autopuzzle-mobile-filter" data-autopuzzle-mobile-filter>
                            <button
                                type="button"
                                class="autopuzzle-mobile-filter-toggle-btn d-flex align-items-center justify-content-between w-100 d-md-none"
                                data-autopuzzle-filter-toggle
                                aria-expanded="false"
                            >
                                <span class="fw-semibold"><?php esc_html_e('Filters', 'autopuzzle'); ?></span>
                                <i class="ri-arrow-down-s-line autopuzzle-mobile-filter-arrow"></i>
                            </button>
                            <div class="autopuzzle-mobile-filter-body" data-autopuzzle-filter-body>
                            <form id="autopuzzle-cash-inquiry-filter-form" onsubmit="return false;">
                                <!-- Search Field -->
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">
                                                <i class="la la-search"></i>
                                            </span>
                                            <input type="search" id="cash-inquiry-search-input" class="form-control form-control-sm" placeholder="<?php esc_attr_e('Search by car name...', 'autopuzzle'); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Filter Controls -->
                                <div class="row g-3 align-items-end mt-1">
                                    <div class="col-6 col-lg-2">
                                        <label class="form-label"><?php esc_html_e('Status:', 'autopuzzle'); ?></label>
                                        <select id="cash-inquiry-status-filter" class="form-select form-select-sm">
                                            <option value=""><?php esc_html_e('All Statuses', 'autopuzzle'); ?></option>
                                            <?php foreach (Autopuzzle_CPT_Handler::get_all_cash_inquiry_statuses() as $key => $label) : ?>
                                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-6 col-lg-2">
                                        <label class="form-label"><?php esc_html_e('Sort:', 'autopuzzle'); ?></label>
                                        <select id="cash-inquiry-sort-filter" class="form-select form-select-sm">
                                            <option value="default"><?php esc_html_e('Default (Newest First)', 'autopuzzle'); ?></option>
                                            <option value="date_desc"><?php esc_html_e('Newest', 'autopuzzle'); ?></option>
                                            <option value="date_asc"><?php esc_html_e('Oldest', 'autopuzzle'); ?></option>
                                            <option value="status"><?php esc_html_e('By Status', 'autopuzzle'); ?></option>
                                        </select>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="col-12 col-lg-2">
                                        <div class="row g-2">
                                            <div class="col-6 col-lg-6">
                                                <button type="button" id="cash-inquiry-apply-filters" class="btn btn-primary btn-sm w-100">
                                                    <i class="la la-filter me-1"></i>
                                                    <?php esc_html_e('Apply', 'autopuzzle'); ?>
                                                </button>
                                            </div>
                                            <div class="col-6 col-lg-6">
                                                <button type="button" id="cash-inquiry-reset-filters" class="btn btn-outline-secondary btn-sm w-100">
                                                    <i class="la la-refresh me-1"></i>
                                                    <?php esc_html_e('Clear', 'autopuzzle'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            </div>
                        </div>

                        <!-- Loading Indicator -->
                        <div id="cash-inquiry-loading" class="text-center py-4" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden"><?php esc_html_e('Loading...', 'autopuzzle'); ?></span>
                            </div>
                            <p class="mt-2 text-muted"><?php esc_html_e('Loading inquiries...', 'autopuzzle'); ?></p>
                        </div>

                        <div class="table-responsive">
                            <table class="table text-nowrap table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php esc_html_e('ID', 'autopuzzle'); ?></th>
                                        <th scope="col"><?php esc_html_e('Car', 'autopuzzle'); ?></th>
                                        <th scope="col"><?php esc_html_e('Status', 'autopuzzle'); ?></th>
                                        <th scope="col"><?php esc_html_e('Date', 'autopuzzle'); ?></th>
                                        <th scope="col"><?php esc_html_e('Actions', 'autopuzzle'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="autopuzzle-cash-inquiry-list-tbody">
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
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

                        <div id="cash-inquiry-list-loader" style="display: none; text-align:center; padding: 40px;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden"><?php esc_html_e('Loading...', 'autopuzzle'); ?></span>
                            </div>
                        </div>
                        
                        <div class="autopuzzle-cash-pagination-wrapper mt-3 text-center"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Direct initialization check for cash inquiries
        console.log('🔴 TEMPLATE: cash-inquiries.php script loaded (Customer View)');

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

        // CRITICAL: Initialize autopuzzleInquiryLists immediately if not already set
        if (typeof autopuzzleInquiryLists === 'undefined') {
            console.warn('🔴 TEMPLATE: autopuzzleInquiryLists is undefined! Creating fallback...');
            window.autopuzzleInquiryLists = {
                ajax_url: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
                nonces: {
                    cash_filter: '<?php echo esc_js(wp_create_nonce("autopuzzle_cash_inquiry_filter_nonce")); ?>',
                    cash_details: '<?php echo esc_js(wp_create_nonce("autopuzzle_cash_inquiry_details_nonce")); ?>',
                    cash_update: '<?php echo esc_js(wp_create_nonce("autopuzzle_cash_inquiry_update_nonce")); ?>',
                    cash_set_downpayment: '<?php echo esc_js(wp_create_nonce("autopuzzle_cash_set_downpayment_nonce")); ?>',
                    cash_assign_expert: '<?php echo esc_js(wp_create_nonce("autopuzzle_cash_inquiry_assign_expert_nonce")); ?>'
                },
                experts: [],
                text: {
                    error: <?php echo wp_json_encode(esc_html__('Error!', 'autopuzzle')); ?>,
                    success: <?php echo wp_json_encode(esc_html__('Success!', 'autopuzzle')); ?>,
                    server_error: <?php echo wp_json_encode(esc_html__('Server error. Please try again.', 'autopuzzle')); ?>,
                    unknown_error: <?php echo wp_json_encode(esc_html__('Unknown error', 'autopuzzle')); ?>
                },
                cash_rejection_reasons: <?php 
                    $options = get_option('autopuzzle_inquiry_all_options', []);
                    $reasons = array_filter(array_map('trim', explode("\n", $options['cash_inquiry_rejection_reasons'] ?? '')));
                    echo json_encode(array_values($reasons)); 
                ?>
            };
        }

const htmlLangAttr = (document.documentElement.getAttribute('lang') || '').toLowerCase();
const htmlDirAttr = (document.documentElement.getAttribute('dir') || '').toLowerCase();
const fallbackLocale = htmlLangAttr || (htmlDirAttr === 'rtl' ? 'fa' : (htmlDirAttr === 'ltr' ? 'en' : ''));

if (typeof autopuzzleInquiryLists.locale === 'undefined' && fallbackLocale) {
    autopuzzleInquiryLists.locale = fallbackLocale;
}

if (typeof autopuzzleInquiryLists.use_persian_digits === 'undefined') {
    const localeSource = autopuzzleInquiryLists.locale || fallbackLocale || '';
    autopuzzleInquiryLists.use_persian_digits = localeSource.toLowerCase().indexOf('fa') === 0;
}

        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔴 TEMPLATE: DOM ready for customer cash inquiries');
            // Auto-load list after 500ms
            setTimeout(function() {
                if (typeof jQuery !== 'undefined' && jQuery('#autopuzzle-cash-inquiry-list-tbody').length > 0 && typeof autopuzzleInquiryLists !== 'undefined') {
                    console.log('🔴 TEMPLATE: Auto-loading customer cash inquiries list');
                    jQuery.ajax({
                        url: autopuzzleInquiryLists.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'autopuzzle_filter_cash_inquiries_ajax',
                            nonce: autopuzzleInquiryLists.nonces.cash_filter,
                            _ajax_nonce: autopuzzleInquiryLists.nonces.cash_filter,
                            page: 1,
                            search: '',
                            status: '',
                            sort: 'default'
                        },
                        success: function(response) {
                            console.log('🔴 TEMPLATE: Auto-load success:', response);
                            if (response && response.success && response.data && response.data.html) {
                                jQuery('#autopuzzle-cash-inquiry-list-tbody').html(response.data.html);
                                var rowCount = jQuery('#autopuzzle-cash-inquiry-list-tbody tr.crm-contact').length;
                                jQuery('#cash-inquiry-count-badge').text(formatNumberForLocale(rowCount));
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

