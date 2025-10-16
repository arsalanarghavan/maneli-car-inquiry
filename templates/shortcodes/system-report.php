<?php
/**
 * Template for the System Report page, rendered by the [maneli_system_report] shortcode.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// بارگذاری Dashicons - CRITICAL برای نمایش آیکون‌ها
if (!wp_style_is('dashicons', 'enqueued')) {
    wp_enqueue_style('dashicons');
}
?>

<link rel="stylesheet" href="<?php echo includes_url('css/dashicons.min.css'); ?>" type="text/css" media="all" />

<div class="maneli-system-report-shortcode">
    <!-- هدر و فیلترها -->
    <div class="report-header">
        <div class="header-title">
            <span class="dashicons dashicons-chart-bar"></span>
            <h2><?php echo $is_expert ? 'گزارش عملکرد من' : 'گزارشات سیستم'; ?></h2>
        </div>
        
        <!-- فیلترهای پیشرفته -->
        <div class="report-filters">
            <div class="filter-group">
                <label>
                    <span class="dashicons dashicons-calendar"></span>
                    بازه زمانی:
                </label>
                <select id="maneli-date-preset" class="filter-input">
                    <option value="today">امروز</option>
                    <option value="yesterday">دیروز</option>
                    <option value="week">هفته گذشته</option>
                    <option value="month" <?php echo $days == 30 ? 'selected' : ''; ?>>ماه گذشته</option>
                    <option value="3months">3 ماه گذشته</option>
                    <option value="6months">6 ماه گذشته</option>
                    <option value="year">سال گذشته</option>
                    <option value="custom">سفارشی</option>
                </select>
            </div>
            
            <div class="filter-group custom-dates" style="display: none;">
                <label>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    از تاریخ:
                </label>
                <input type="date" id="maneli-start-date" class="filter-input" value="<?php echo esc_attr($start_date); ?>">
                
                <label>تا تاریخ:</label>
                <input type="date" id="maneli-end-date" class="filter-input" value="<?php echo esc_attr($end_date); ?>">
            </div>
            
            <?php if (current_user_can('manage_options') && !$is_expert): ?>
            <div class="filter-group">
                <label>
                    <span class="dashicons dashicons-admin-users"></span>
                    کارشناس:
                </label>
                <select id="maneli-expert-filter" class="filter-input">
                    <option value="">همه کارشناسان</option>
                    <?php
                    $experts = get_users(['role__in' => ['maneli_expert', 'maneli_admin', 'administrator']]);
                    foreach ($experts as $expert) {
                        echo '<option value="' . esc_attr($expert->ID) . '">' . esc_html($expert->display_name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="filter-group">
                <button type="button" id="maneli-apply-filter" class="btn-primary">
                    <span class="dashicons dashicons-filter"></span>
                    اعمال فیلتر
                </button>
                <button type="button" id="maneli-refresh-data" class="btn-secondary">
                    <span class="dashicons dashicons-update"></span>
                    بروزرسانی
                </button>
            </div>
        </div>
        
        <div class="report-period-info">
            <span class="dashicons dashicons-info"></span>
            <span id="period-text">بازه زمانی: <strong><?php echo esc_html($days); ?> روز گذشته</strong></span>
            <?php if ($is_expert): ?>
                <span class="separator">|</span>
                کارشناس: <strong><?php echo esc_html($current_user->display_name); ?></strong>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Loading -->
    <div id="maneli-loading" class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <span class="dashicons dashicons-update"></span>
            <p>در حال بارگذاری...</p>
        </div>
    </div>
    
    <!-- محتوای اصلی -->
    <div id="maneli-report-content">
        <!-- کارت‌های آماری -->
        <div class="stats-cards-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <span class="dashicons dashicons-list-view"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="total_inquiries"><?php echo number_format($stats['total_inquiries']); ?></div>
                    <div class="stat-label">کل استعلام‌ها</div>
                    <div class="stat-meta">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        امروز: <span data-stat="new_today"><?php echo number_format($stats['new_today']); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card cash">
                <div class="stat-icon">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="cash_inquiries"><?php echo number_format($stats['cash_inquiries']); ?></div>
                    <div class="stat-label">استعلام نقدی</div>
                </div>
            </div>
            
            <div class="stat-card installment">
                <div class="stat-icon">
                    <span class="dashicons dashicons-calculator"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="installment_inquiries"><?php echo number_format($stats['installment_inquiries']); ?></div>
                    <div class="stat-label">استعلام اقساطی</div>
                </div>
            </div>
            
            <div class="stat-card approved">
                <div class="stat-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="approved"><?php echo number_format($stats['approved']); ?></div>
                    <div class="stat-label">تایید شده</div>
                </div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="pending"><?php echo number_format($stats['pending']); ?></div>
                    <div class="stat-label">در انتظار</div>
                </div>
            </div>
            
            <div class="stat-card rejected">
                <div class="stat-icon">
                    <span class="dashicons dashicons-dismiss"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="rejected"><?php echo number_format($stats['rejected']); ?></div>
                    <div class="stat-label">رد شده</div>
                </div>
            </div>
            
            <div class="stat-card following">
                <div class="stat-icon">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="following"><?php echo number_format($stats['following']); ?></div>
                    <div class="stat-label">در حال پیگیری</div>
                </div>
            </div>
            
            <div class="stat-card next-followup">
                <div class="stat-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="next_followup"><?php echo number_format($stats['next_followup']); ?></div>
                    <div class="stat-label">پیگیری آینده</div>
                </div>
            </div>
            
            <div class="stat-card revenue">
                <div class="stat-icon">
                    <span class="dashicons dashicons-cart"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="revenue"><?php echo number_format($stats['revenue']); ?></div>
                    <div class="stat-label">درآمد (تومان)</div>
                </div>
            </div>
        </div>
        
        <?php if ($show_charts && !empty($daily_stats)): ?>
        <!-- نمودار روند -->
        <div class="chart-section">
            <h3>
                <span class="dashicons dashicons-chart-line"></span>
                روند استعلام‌های روزانه
            </h3>
            <div class="chart-container">
                <canvas id="shortcode-daily-trend-chart"></canvas>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- محصولات پرطرفدار -->
        <?php if (!empty($popular_products)): ?>
        <div class="popular-products-section">
            <h3>
                <span class="dashicons dashicons-star-filled"></span>
                محصولات پرطرفدار
            </h3>
            <div class="products-list" id="products-list">
                <?php foreach ($popular_products as $index => $product): ?>
                <div class="product-item">
                    <div class="product-rank"><?php echo $index + 1; ?></div>
                    <div class="product-info">
                        <div class="product-name"><?php echo esc_html($product->product_name ?: 'نامشخص'); ?></div>
                        <div class="product-count">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php echo number_format($product->inquiry_count); ?> استعلام
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- آمار کارشناسان -->
        <?php if ($show_experts && !empty($experts_stats)): ?>
        <div class="experts-section">
            <h3>
                <span class="dashicons dashicons-groups"></span>
                عملکرد کارشناسان
            </h3>
            <div class="experts-table-wrapper">
                <table class="experts-table" id="experts-table">
                    <thead>
                        <tr>
                            <th>
                                <span class="dashicons dashicons-admin-users"></span>
                                کارشناس
                            </th>
                            <th>
                                <span class="dashicons dashicons-list-view"></span>
                                کل استعلام‌ها
                            </th>
                            <th>
                                <span class="dashicons dashicons-money-alt"></span>
                                نقدی
                            </th>
                            <th>
                                <span class="dashicons dashicons-calculator"></span>
                                اقساطی
                            </th>
                            <th>
                                <span class="dashicons dashicons-yes-alt"></span>
                                تایید شده
                            </th>
                            <th>
                                <span class="dashicons dashicons-dismiss"></span>
                                رد شده
                            </th>
                            <th>
                                <span class="dashicons dashicons-groups"></span>
                                مشتریان
                            </th>
                            <th>
                                <span class="dashicons dashicons-cart"></span>
                                درآمد
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($experts_stats as $expert): ?>
                        <tr>
                            <td class="expert-name"><?php echo esc_html($expert['expert_name']); ?></td>
                            <td><?php echo number_format($expert['total_inquiries']); ?></td>
                            <td><?php echo number_format($expert['cash_inquiries']); ?></td>
                            <td><?php echo number_format($expert['installment_inquiries']); ?></td>
                            <td><span class="badge approved"><?php echo number_format($expert['approved']); ?></span></td>
                            <td><span class="badge rejected"><?php echo number_format($expert['rejected']); ?></span></td>
                            <td><?php echo number_format($expert['total_customers']); ?></td>
                            <td><?php echo number_format($expert['revenue']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    const isExpert = <?php echo $is_expert ? 'true' : 'false'; ?>;
    const expertId = <?php echo $is_expert ? $current_user->ID : 'null'; ?>;
    const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    const nonce = '<?php echo wp_create_nonce('maneli_reports_shortcode_nonce'); ?>';
    
    // مدیریت تغییر بازه زمانی
    $('#maneli-date-preset').on('change', function() {
        if ($(this).val() === 'custom') {
            $('.custom-dates').slideDown();
        } else {
            $('.custom-dates').slideUp();
        }
    });
    
    // اعمال فیلتر
    $('#maneli-apply-filter, #maneli-refresh-data').on('click', function() {
        loadReportData();
    });
    
    // بارگذاری داده‌ها
    function loadReportData() {
        const preset = $('#maneli-date-preset').val();
        let startDate, endDate;
        
        if (preset === 'custom') {
            startDate = $('#maneli-start-date').val();
            endDate = $('#maneli-end-date').val();
        } else {
            const dates = getDateRangeFromPreset(preset);
            startDate = dates.start;
            endDate = dates.end;
        }
        
        const selectedExpertId = isExpert ? expertId : ($('#maneli-expert-filter').val() || null);
        
        // نمایش loading
        $('#maneli-loading').fadeIn();
        
        // درخواست Ajax برای آمار کلی
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_shortcode_get_stats',
                nonce: nonce,
                start_date: startDate,
                end_date: endDate,
                expert_id: selectedExpertId
            },
            success: function(response) {
                if (response.success) {
                    updateStatsCards(response.data.stats);
                    
                    if (response.data.daily_stats) {
                        updateChart(response.data.daily_stats);
                    }
                    
                    if (response.data.popular_products) {
                        updatePopularProducts(response.data.popular_products);
                    }
                    
                    if (response.data.experts_stats) {
                        updateExpertsTable(response.data.experts_stats);
                    }
                    
                    // بروزرسانی متن دوره
                    updatePeriodText(startDate, endDate);
                }
                $('#maneli-loading').fadeOut();
            },
            error: function() {
                alert('خطا در بارگذاری داده‌ها');
                $('#maneli-loading').fadeOut();
            }
        });
    }
    
    // بروزرسانی کارت‌های آماری
    function updateStatsCards(stats) {
        $('[data-stat="total_inquiries"]').text(formatNumber(stats.total_inquiries));
        $('[data-stat="cash_inquiries"]').text(formatNumber(stats.cash_inquiries));
        $('[data-stat="installment_inquiries"]').text(formatNumber(stats.installment_inquiries));
        $('[data-stat="approved"]').text(formatNumber(stats.approved));
        $('[data-stat="pending"]').text(formatNumber(stats.pending));
        $('[data-stat="rejected"]').text(formatNumber(stats.rejected));
        $('[data-stat="following"]').text(formatNumber(stats.following));
        $('[data-stat="next_followup"]').text(formatNumber(stats.next_followup));
        $('[data-stat="new_today"]').text(formatNumber(stats.new_today));
        $('[data-stat="revenue"]').text(formatNumber(stats.revenue));
        
        // انیمیشن
        $('.stat-value').addClass('updated');
        setTimeout(function() {
            $('.stat-value').removeClass('updated');
        }, 500);
    }
    
    // بروزرسانی نمودار
    let dailyChart = null;
    function updateChart(dailyStats) {
        const ctx = document.getElementById('shortcode-daily-trend-chart');
        if (!ctx) return;
        
        if (dailyChart) {
            dailyChart.destroy();
        }
        
        const labels = dailyStats.map(item => item.date);
        const cashData = dailyStats.map(item => item.cash);
        const installmentData = dailyStats.map(item => item.installment);
        
        dailyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'نقدی',
                        data: cashData,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'اقساطی',
                        data: installmentData,
                        borderColor: '#8c6d1f',
                        backgroundColor: 'rgba(140, 109, 31, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                        rtl: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // بروزرسانی محصولات پرطرفدار
    function updatePopularProducts(products) {
        const container = $('#products-list');
        let html = '';
        
        products.forEach(function(product, index) {
            html += `
                <div class="product-item">
                    <div class="product-rank">${index + 1}</div>
                    <div class="product-info">
                        <div class="product-name">${product.product_name || 'نامشخص'}</div>
                        <div class="product-count">
                            <span class="dashicons dashicons-chart-line"></span>
                            ${formatNumber(product.inquiry_count)} استعلام
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.html(html);
    }
    
    // بروزرسانی جدول کارشناسان
    function updateExpertsTable(experts) {
        const tbody = $('#experts-table tbody');
        let html = '';
        
        experts.forEach(function(expert) {
            html += `
                <tr>
                    <td class="expert-name">${expert.expert_name}</td>
                    <td>${formatNumber(expert.total_inquiries)}</td>
                    <td>${formatNumber(expert.cash_inquiries)}</td>
                    <td>${formatNumber(expert.installment_inquiries)}</td>
                    <td><span class="badge approved">${formatNumber(expert.approved)}</span></td>
                    <td><span class="badge rejected">${formatNumber(expert.rejected)}</span></td>
                    <td>${formatNumber(expert.total_customers)}</td>
                    <td>${formatNumber(expert.revenue)}</td>
                </tr>
            `;
        });
        
        tbody.html(html);
    }
    
    // بروزرسانی متن دوره
    function updatePeriodText(startDate, endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        $('#period-text').html(`بازه زمانی: <strong>${days} روز</strong> (${startDate} تا ${endDate})`);
    }
    
    // محاسبه بازه زمانی از پیش‌فرض
    function getDateRangeFromPreset(preset) {
        const today = new Date();
        let start, end;
        
        end = formatDate(today);
        
        switch(preset) {
            case 'today':
                start = end;
                break;
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                start = end = formatDate(yesterday);
                break;
            case 'week':
                const weekAgo = new Date(today);
                weekAgo.setDate(weekAgo.getDate() - 7);
                start = formatDate(weekAgo);
                break;
            case 'month':
                const monthAgo = new Date(today);
                monthAgo.setMonth(monthAgo.getMonth() - 1);
                start = formatDate(monthAgo);
                break;
            case '3months':
                const threeMonthsAgo = new Date(today);
                threeMonthsAgo.setMonth(threeMonthsAgo.getMonth() - 3);
                start = formatDate(threeMonthsAgo);
                break;
            case '6months':
                const sixMonthsAgo = new Date(today);
                sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);
                start = formatDate(sixMonthsAgo);
                break;
            case 'year':
                const yearAgo = new Date(today);
                yearAgo.setFullYear(yearAgo.getFullYear() - 1);
                start = formatDate(yearAgo);
                break;
            default:
                const defaultStart = new Date(today);
                defaultStart.setMonth(defaultStart.getMonth() - 1);
                start = formatDate(defaultStart);
        }
        
        return { start, end };
    }
    
    // فرمت تاریخ
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // فرمت اعداد
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    // رسم نمودار اولیه
    <?php if ($show_charts && !empty($daily_stats)): ?>
    const initialDailyData = <?php echo json_encode($daily_stats); ?>;
    updateChart(initialDailyData);
    <?php endif; ?>
    
    // انیمیشن ورود
    setTimeout(function() {
        $('.stat-card').each(function(index) {
            $(this).css({
                'opacity': '0',
                'transform': 'translateY(20px)'
            });
            setTimeout(function() {
                $('.stat-card').eq(index).css({
                    'opacity': '1',
                    'transform': 'translateY(0)',
                    'transition': 'all 0.3s ease'
                });
            }, index * 50);
        });
    }, 100);
});
</script>

<style>
.maneli-system-report-shortcode {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'IRANSans', sans-serif;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
    direction: rtl;
    text-align: right;
}

/* هدر */
.report-header {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.header-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.header-title h2 {
    margin: 0;
    font-size: 24px;
    color: #333;
}

.header-title .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #2271b1;
}

/* فیلترها */
.report-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
    margin-bottom: 15px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    font-weight: 600;
    color: #555;
}

.filter-group label .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.filter-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    font-family: inherit;
}

.custom-dates {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.btn-primary,
.btn-secondary {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary {
    background: #2271b1;
    color: #fff;
}

.btn-primary:hover {
    background: #135e96;
}

.btn-secondary {
    background: #f0f0f0;
    color: #333;
}

.btn-secondary:hover {
    background: #e0e0e0;
}

.btn-primary .dashicons,
.btn-secondary .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.report-period-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    background: #fff3cd;
    border-radius: 4px;
    font-size: 13px;
    color: #666;
}

.report-period-info .dashicons {
    color: #f0b849;
}

.separator {
    margin: 0 5px;
    color: #999;
}

/* Loading */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-spinner {
    text-align: center;
}

.loading-spinner .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #2271b1;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* کارت‌های آماری */
.stats-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
    border-right: 4px solid currentColor;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.stat-card.total { color: #2271b1; }
.stat-card.cash { color: #00a32a; }
.stat-card.installment { color: #8c6d1f; }
.stat-card.approved { color: #00a32a; }
.stat-card.pending { color: #f0b849; }
.stat-card.rejected { color: #d63638; }
.stat-card.following { color: #8c6d1f; }
.stat-card.next-followup { color: #8c6d1f; }
.stat-card.revenue { color: #2271b1; }

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: currentColor;
    opacity: 0.15;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: currentColor;
    opacity: 1;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: currentColor;
    direction: ltr;
    text-align: right;
}

.stat-value.updated {
    animation: pulse 0.5s ease;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.stat-label {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

.stat-meta {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

.stat-meta .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* نمودار */
.chart-section,
.popular-products-section,
.experts-section {
    background: #fff;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.chart-section h3,
.popular-products-section h3,
.experts-section h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 20px 0;
    font-size: 18px;
    color: #333;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.chart-section h3 .dashicons,
.popular-products-section h3 .dashicons,
.experts-section h3 .dashicons {
    color: #2271b1;
}

.chart-container {
    max-height: 300px;
}

/* محصولات پرطرفدار */
.products-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 15px;
    background: #f9f9f9;
    border-radius: 6px;
    transition: all 0.3s;
}

.product-item:hover {
    background: #f0f0f0;
    transform: translateX(-5px);
}

.product-rank {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #2271b1;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 16px;
    flex-shrink: 0;
}

.product-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.product-name {
    font-weight: 500;
    color: #333;
}

.product-count {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #2271b1;
    font-weight: bold;
}

.product-count .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* جدول کارشناسان */
.experts-table-wrapper {
    overflow-x: auto;
}

.experts-table {
    width: 100%;
    border-collapse: collapse;
}

.experts-table th {
    background: #f5f5f5;
    padding: 12px 15px;
    text-align: right;
    font-weight: 600;
    font-size: 13px;
    color: #555;
    border-bottom: 2px solid #e0e0e0;
    white-space: nowrap;
}

.experts-table th .dashicons {
    margin-left: 5px;
    vertical-align: middle;
}

.experts-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    color: #333;
    font-size: 13px;
}

.experts-table tr:hover {
    background: #f9f9f9;
}

.expert-name {
    font-weight: 600;
    color: #2271b1;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge.approved {
    background: #d4edda;
    color: #155724;
}

.badge.rejected {
    background: #f8d7da;
    color: #721c24;
}

/* ریسپانسیو */
@media (max-width: 768px) {
    .stats-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .report-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .custom-dates {
        flex-direction: column;
    }
    
    .experts-table {
        font-size: 11px;
    }
    
    .experts-table th,
    .experts-table td {
        padding: 8px;
    }
}
</style>
