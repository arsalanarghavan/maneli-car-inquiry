<?php
/**
 * صفحه گزارشات مدیریتی
 * 
 * @package Maneli_Car_Inquiry
 */

defined('ABSPATH') || exit;

class Maneli_Reports_Page {
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page'], 25);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * افزودن منو به پنل مدیریت
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=cash_inquiry',
            esc_html__('Reports and Statistics', 'maneli-car-inquiry'),
            '📊 ' . esc_html__('Reports', 'maneli-car-inquiry'),
            'manage_options',
            'maneli-reports',
            [$this, 'render_page']
        );
    }
    
    /**
     * بارگذاری استایل و اسکریپت
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'cash_inquiry_page_maneli-reports') {
            return;
        }
        
        // استایل
        wp_enqueue_style(
            'maneli-reports',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/reports-dashboard.css',
            [],
            '1.0.0'
        );
        
        // Chart.js برای نمودارها - Use local version if available
        $chartjs_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/chart.js/chart.umd.js';
        if (file_exists($chartjs_path)) {
            wp_enqueue_script(
                'chartjs',
                MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/chart.js/chart.umd.js',
                [],
                '4.4.0',
                true
            );
        } else {
            // Fallback to CDN
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                [],
                '4.4.0',
                true
            );
        }
        
        // اسکریپت اصلی
        wp_enqueue_script(
            'maneli-reports',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/admin/reports-dashboard.js',
            ['jquery', 'chartjs'],
            '1.0.0',
            true
        );
        
        // داده‌ها برای JavaScript
        wp_localize_script('maneli-reports', 'maneliReports', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('maneli_reports_nonce'),
            'labels' => [
                'pending' => esc_html__('Pending Review', 'maneli-car-inquiry'),
                'approved' => esc_html__('Approved and Referred', 'maneli-car-inquiry'),
                'rejected' => esc_html__('Rejected', 'maneli-car-inquiry'),
                'following' => esc_html__('Follow-up in Progress', 'maneli-car-inquiry'),
                'cash' => esc_html__('Cash', 'maneli-car-inquiry'),
                'installment' => esc_html__('Installment', 'maneli-car-inquiry'),
            ]
        ]);
    }
    
    /**
     * رندر صفحه گزارشات
     */
    public function render_page() {
        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'maneli-car-inquiry'));
        }
        
        ?>
        <div class="wrap maneli-reports-dashboard">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-chart-bar"></span>
                گزارشات و آمار سیستم
            </h1>
            
            <!-- فیلترهای بالای صفحه -->
            <div class="maneli-reports-filters">
                <div class="filter-group">
                    <label><?php esc_html_e('Time Period:', 'maneli-car-inquiry'); ?></label>
                    <select id="date-range-preset">
                        <option value="today"><?php esc_html_e('Today', 'maneli-car-inquiry'); ?></option>
                        <option value="yesterday"><?php esc_html_e('Yesterday', 'maneli-car-inquiry'); ?></option>
                        <option value="week"><?php esc_html_e('Last Week', 'maneli-car-inquiry'); ?></option>
                        <option value="month" selected><?php esc_html_e('Last Month', 'maneli-car-inquiry'); ?></option>
                        <option value="3months"><?php esc_html_e('Last 3 Months', 'maneli-car-inquiry'); ?></option>
                        <option value="6months"><?php esc_html_e('Last 6 Months', 'maneli-car-inquiry'); ?></option>
                        <option value="year"><?php esc_html_e('Last Year', 'maneli-car-inquiry'); ?></option>
                        <option value="custom"><?php esc_html_e('Custom Range', 'maneli-car-inquiry'); ?></option>
                    </select>
                </div>
                
                <div class="filter-group custom-date-range" style="display: none;">
                    <label><?php esc_html_e('From Date:', 'maneli-car-inquiry'); ?></label>
                    <input type="date" id="start-date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                    
                    <label><?php esc_html_e('To Date:', 'maneli-car-inquiry'); ?></label>
                    <input type="date" id="end-date" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="filter-group">
                    <label><?php esc_html_e('Expert:', 'maneli-car-inquiry'); ?></label>
                    <select id="expert-filter">
                        <option value=""><?php esc_html_e('All Experts', 'maneli-car-inquiry'); ?></option>
                        <?php
                        $experts = get_users(['role__in' => ['expert', 'administrator']]);
                        foreach ($experts as $expert) {
                            echo '<option value="' . esc_attr($expert->ID) . '">' . esc_html($expert->display_name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="button" id="apply-filters" class="button button-primary">
                        اعمال فیلتر
                    </button>
                    <button type="button" id="refresh-data" class="button">
                        <span class="dashicons dashicons-update"></span>
                        بروزرسانی
                    </button>
                </div>
            </div>
            
            <!-- تب‌های نمایش -->
            <div class="maneli-reports-tabs">
                <a href="#tab-overview" class="tab-link active"><?php esc_html_e('Overview', 'maneli-car-inquiry'); ?></a>
                <a href="#tab-experts" class="tab-link"><?php esc_html_e('Expert Reports', 'maneli-car-inquiry'); ?></a>
                <a href="#tab-details" class="tab-link"><?php esc_html_e('Inquiry Details', 'maneli-car-inquiry'); ?></a>
                <a href="#tab-charts" class="tab-link"><?php esc_html_e('Charts', 'maneli-car-inquiry'); ?></a>
            </div>
            
            <!-- محتوای تب نمای کلی -->
            <div id="tab-overview" class="tab-content active">
                <div class="maneli-stats-cards">
                    <div class="stat-card total-inquiries">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-list-view"></span>
                        </div>
                        <div class="stat-info">
                            <h3>کل استعلام‌ها</h3>
                            <div class="stat-value" data-stat="total_inquiries">-</div>
                            <div class="stat-change">
                                <span class="change-label">امروز:</span>
                                <span class="change-value" data-stat="new_today">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card cash-inquiries">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-money-alt"></span>
                        </div>
                        <div class="stat-info">
                            <h3>استعلام نقدی</h3>
                            <div class="stat-value" data-stat="cash_inquiries">-</div>
                        </div>
                    </div>
                    
                    <div class="stat-card installment-inquiries">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-calculator"></span>
                        </div>
                        <div class="stat-info">
                            <h3>استعلام اقساطی</h3>
                            <div class="stat-value" data-stat="installment_inquiries">-</div>
                        </div>
                    </div>
                    
                    <div class="stat-card approved">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <div class="stat-info">
                            <h3>تایید شده</h3>
                            <div class="stat-value" data-stat="approved">-</div>
                        </div>
                    </div>
                    
                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="stat-info">
                            <h3>در انتظار</h3>
                            <div class="stat-value" data-stat="pending">-</div>
                        </div>
                    </div>
                    
                    <div class="stat-card rejected">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-dismiss"></span>
                        </div>
                        <div class="stat-info">
                            <h3>رد شده</h3>
                            <div class="stat-value" data-stat="rejected">-</div>
                        </div>
                    </div>
                    
                    <div class="stat-card following">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-visibility"></span>
                        </div>
                        <div class="stat-info">
                            <h3>در حال پیگیری</h3>
                            <div class="stat-value" data-stat="following">-</div>
                        </div>
                    </div>
                    
                    <div class="stat-card next-followup">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <div class="stat-info">
                            <h3>پیگیری آینده</h3>
                            <div class="stat-value" data-stat="next_followup">-</div>
                        </div>
                    </div>
                    
                    <div class="stat-card revenue">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-cart"></span>
                        </div>
                        <div class="stat-info">
                            <h3><?php esc_html_e('Revenue', 'maneli-car-inquiry'); ?></h3>
                            <div class="stat-value" data-stat="revenue">-</div>
                            <small><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></small>
                        </div>
                    </div>
                </div>
                
                <!-- نمودار روند روزانه -->
                <div class="maneli-chart-container">
                    <h2>روند استعلام‌های روزانه</h2>
                    <canvas id="daily-trend-chart"></canvas>
                </div>
                
                <!-- محصولات پرطرفدار -->
                <div class="maneli-popular-products">
                    <h2>محصولات پرطرفدار</h2>
                    <div id="popular-products-list" class="products-list">
                        <div class="loading">در حال بارگذاری...</div>
                    </div>
                </div>
            </div>
            
            <!-- محتوای تب گزارش کارشناسان -->
            <div id="tab-experts" class="tab-content">
                <h2>عملکرد کارشناسان</h2>
                <div id="experts-stats-table" class="experts-table">
                    <div class="loading">در حال بارگذاری...</div>
                </div>
            </div>
            
            <!-- محتوای تب جزئیات -->
            <div id="tab-details" class="tab-content">
                <div class="details-filters">
                    <select id="details-type">
                        <option value="all"><?php esc_html_e('All Types', 'maneli-car-inquiry'); ?></option>
                        <option value="cash"><?php esc_html_e('Cash', 'maneli-car-inquiry'); ?></option>
                        <option value="installment"><?php esc_html_e('Installment', 'maneli-car-inquiry'); ?></option>
                    </select>
                    
                    <select id="details-status">
                        <option value=""><?php esc_html_e('All Statuses', 'maneli-car-inquiry'); ?></option>
                        <option value="pending"><?php esc_html_e('Pending', 'maneli-car-inquiry'); ?></option>
                        <option value="approved"><?php esc_html_e('Approved', 'maneli-car-inquiry'); ?></option>
                        <option value="rejected"><?php esc_html_e('Rejected', 'maneli-car-inquiry'); ?></option>
                        <option value="following"><?php esc_html_e('In Progress', 'maneli-car-inquiry'); ?></option>
                    </select>
                    
                    <button type="button" id="export-csv" class="button">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Download CSV', 'maneli-car-inquiry'); ?>
                    </button>
                </div>
                
                <div id="inquiries-details-table" class="details-table">
                    <div class="loading">در حال بارگذاری...</div>
                </div>
                
                <div class="table-pagination">
                    <button id="prev-page" class="button" disabled><?php esc_html_e('Previous', 'maneli-car-inquiry'); ?></button>
                    <span id="page-info"><?php printf(esc_html__('Page %1$s of %2$s', 'maneli-car-inquiry'), '1', '1'); ?></span>
                    <button id="next-page" class="button" disabled><?php esc_html_e('Next', 'maneli-car-inquiry'); ?></button>
                </div>
            </div>
            
            <!-- محتوای تب نمودارها -->
            <div id="tab-charts" class="tab-content">
                <div class="charts-grid">
                    <div class="chart-box">
                        <h3>توزیع وضعیت استعلام‌ها</h3>
                        <canvas id="status-pie-chart"></canvas>
                    </div>
                    
                    <div class="chart-box">
                        <h3>نسبت نقدی به اقساطی</h3>
                        <canvas id="type-doughnut-chart"></canvas>
                    </div>
                    
                    <div class="chart-box full-width">
                        <h3>عملکرد ماهانه</h3>
                        <canvas id="monthly-performance-chart"></canvas>
                    </div>
                    
                    <div class="chart-box full-width">
                        <h3>مقایسه کارشناسان</h3>
                        <canvas id="experts-comparison-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// ایجاد نمونه از کلاس
new Maneli_Reports_Page();

