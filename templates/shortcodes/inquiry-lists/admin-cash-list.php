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
                    لیست استعلامات نقدی
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
                                <input type="search" id="cash-inquiry-search-input" class="form-control" placeholder="جستجو بر اساس نام مشتری، نام خودرو یا شماره موبایل...">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Controls -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">وضعیت:</label>
                            <select id="cash-inquiry-status-filter" class="form-select">
                                <option value="">همه وضعیت‌ها</option>
                                <?php foreach (Maneli_CPT_Handler::get_all_cash_inquiry_statuses() as $key => $label) : ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if (current_user_can('manage_maneli_inquiries') && !empty($experts)) : ?>
                            <div class="col-md-4">
                                <label class="form-label">کارشناس:</label>
                                <select id="cash-expert-filter" class="form-select maneli-select2">
                                    <option value="">همه کارشناسان</option>
                                    <?php foreach ($experts as $expert) : ?>
                                        <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="col-md-4">
                            <label class="form-label">مرتب‌سازی:</label>
                            <select id="cash-inquiry-sort-filter" class="form-select">
                                <option value="default">پیش‌فرض (جدیدترین قدیمی)</option>
                                <option value="date_desc">جدیدترین</option>
                                <option value="date_asc">قدیمی‌ترین</option>
                                <option value="status">بر اساس وضعیت</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Filter Actions -->
                    <div class="row g-3 mb-3">
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

                <!-- Loading Indicator -->
                <div id="cash-inquiry-loading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">در حال بارگذاری...</span>
                    </div>
                    <p class="mt-2 text-muted">در حال بارگذاری استعلامات...</p>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>شناسه</th>
                                <th>مشتری</th>
                                <th>موبایل</th>
                                <th>خودرو</th>
                                <th>وضعیت</th>
                                <th>کارشناس</th>
                                <th>تاریخ</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody id="maneli-cash-inquiry-list-tbody">
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="la la-spinner la-spin fs-24 text-muted"></i>
                                    <p class="mt-2 text-muted">در حال بارگذاری...</p>
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
                        <span class="visually-hidden">در حال بارگذاری...</span>
                    </div>
                </div>
                
                <div class="maneli-cash-pagination-wrapper mt-3 text-center"></div>
            </div>
        </div>
    </div>
</div>
