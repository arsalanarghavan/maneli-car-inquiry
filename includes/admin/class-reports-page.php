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
            'گزارشات و آمار',
            '📊 گزارشات',
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
        
        // Chart.js برای نمودارها
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );
        
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
                'pending' => 'در انتظار',
                'approved' => 'تایید شده',
                'rejected' => 'رد شده',
                'following' => 'در حال پیگیری',
                'cash' => 'نقدی',
                'installment' => 'اقساطی',
            ]
        ]);
    }
    
    /**
     * رندر صفحه گزارشات
     */
    public function render_page() {
        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_die('شما دسترسی به این بخش ندارید.');
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
                    <label>بازه زمانی:</label>
                    <select id="date-range-preset">
                        <option value="today">امروز</option>
                        <option value="yesterday">دیروز</option>
                        <option value="week">هفته گذشته</option>
                        <option value="month" selected>ماه گذشته</option>
                        <option value="3months">3 ماه گذشته</option>
                        <option value="6months">6 ماه گذشته</option>
                        <option value="year">سال گذشته</option>
                        <option value="custom">سفارشی</option>
                    </select>
                </div>
                
                <div class="filter-group custom-date-range" style="display: none;">
                    <label>از تاریخ:</label>
                    <input type="date" id="start-date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                    
                    <label>تا تاریخ:</label>
                    <input type="date" id="end-date" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="filter-group">
                    <label>کارشناس:</label>
                    <select id="expert-filter">
                        <option value="">همه کارشناسان</option>
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
                <a href="#tab-overview" class="tab-link active">نمای کلی</a>
                <a href="#tab-experts" class="tab-link">گزارش کارشناسان</a>
                <a href="#tab-details" class="tab-link">جزئیات استعلام‌ها</a>
                <a href="#tab-charts" class="tab-link">نمودارها</a>
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
                            <h3>درآمد</h3>
                            <div class="stat-value" data-stat="revenue">-</div>
                            <small>تومان</small>
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
                        <option value="all">همه انواع</option>
                        <option value="cash">نقدی</option>
                        <option value="installment">اقساطی</option>
                    </select>
                    
                    <select id="details-status">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="pending">در انتظار</option>
                        <option value="approved">تایید شده</option>
                        <option value="rejected">رد شده</option>
                        <option value="following">در حال پیگیری</option>
                    </select>
                    
                    <button type="button" id="export-csv" class="button">
                        <span class="dashicons dashicons-download"></span>
                        دانلود CSV
                    </button>
                </div>
                
                <div id="inquiries-details-table" class="details-table">
                    <div class="loading">در حال بارگذاری...</div>
                </div>
                
                <div class="table-pagination">
                    <button id="prev-page" class="button" disabled>قبلی</button>
                    <span id="page-info">صفحه 1 از 1</span>
                    <button id="next-page" class="button" disabled>بعدی</button>
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

