<?php
/**
 * Template for the System Report page, rendered by the [autopuzzle_system_report] shortcode.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}?>

<?php
// بارگذاری Dashicons - CRITICAL برای نمایش آیکون‌ها
if (!wp_style_is('dashicons', 'enqueued')) {
    wp_enqueue_style('dashicons');
}
?>

<div class="autopuzzle-system-report-shortcode">
    <!-- هدر و فیلترها -->
    <div class="report-header">
        <div class="header-title">
            <span class="la la-chart-bar"></span>
            <h2><?php echo esc_html($is_expert ? esc_html__('My Performance Report', 'autopuzzle') : esc_html__('System Reports', 'autopuzzle')); ?></h2>
        </div>
        
        <!-- فیلترهای پیشرفته -->
        <div class="report-filters">
            <div class="filter-group">
                <label>
                    <span class="la la-calendar"></span>
                    <?php esc_html_e('Time Period:', 'autopuzzle'); ?>
                </label>
                <select id="autopuzzle-date-preset" class="filter-input">
                    <option value="today"><?php esc_html_e('Today', 'autopuzzle'); ?></option>
                    <option value="yesterday"><?php esc_html_e('Yesterday', 'autopuzzle'); ?></option>
                    <option value="week"><?php esc_html_e('Last Week', 'autopuzzle'); ?></option>
                    <option value="month" <?php selected($days, 30); ?>><?php esc_html_e('Last Month', 'autopuzzle'); ?></option>
                    <option value="3months"><?php esc_html_e('Last 3 Months', 'autopuzzle'); ?></option>
                    <option value="6months"><?php esc_html_e('Last 6 Months', 'autopuzzle'); ?></option>
                    <option value="year"><?php esc_html_e('Last Year', 'autopuzzle'); ?></option>
                    <option value="custom"><?php esc_html_e('Custom', 'autopuzzle'); ?></option>
                </select>
            </div>
            
            <div class="filter-group custom-dates autopuzzle-initially-hidden">
                <label>
                    <span class="la la-calendar-alt"></span>
                    <?php esc_html_e('From Date:', 'autopuzzle'); ?>
                </label>
                <input type="date" id="autopuzzle-start-date" class="filter-input" value="<?php echo esc_attr($start_date); ?>">
                
                <label><?php esc_html_e('To Date:', 'autopuzzle'); ?></label>
                <input type="date" id="autopuzzle-end-date" class="filter-input" value="<?php echo esc_attr($end_date); ?>">
            </div>
            
            <?php if (current_user_can('manage_options') && !$is_expert): ?>
            <div class="filter-group">
                <label>
                    <span class="la la-users-cog"></span>
                    <?php esc_html_e('Expert:', 'autopuzzle'); ?>
                </label>
                <select id="autopuzzle-expert-filter" class="filter-input">
                    <option value=""><?php esc_html_e('All Experts', 'autopuzzle'); ?></option>
                    <?php
                    $experts = get_users(['role__in' => ['autopuzzle_expert', 'autopuzzle_admin', 'administrator']]);
                    foreach ($experts as $expert) {
                        echo '<option value="' . esc_attr($expert->ID) . '">' . esc_html($expert->display_name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="filter-group">
                <button type="button" id="autopuzzle-apply-filter" class="btn-primary">
                    <span class="la la-filter"></span>
                    <?php esc_html_e('Apply Filter', 'autopuzzle'); ?>
                </button>
                <button type="button" id="autopuzzle-refresh-data" class="btn-secondary">
                    <span class="la la-sync"></span>
                    <?php esc_html_e('Refresh', 'autopuzzle'); ?>
                </button>
            </div>
        </div>
        
        <div class="report-period-info">
            <span class="la la-info-circle"></span>
            <span id="period-text"><?php printf(esc_html__('Time Period: %s days ago', 'autopuzzle'), '<strong>' . esc_html($days) . '</strong>'); ?></span>
            <?php if ($is_expert): ?>
                <span class="separator">|</span>
                <?php printf(esc_html__('Expert: %s', 'autopuzzle'), '<strong>' . esc_html($current_user->display_name) . '</strong>'); ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Loading -->
    <div id="autopuzzle-loading" class="loading-overlay autopuzzle-loading-overlay">
        <div class="loading-spinner">
            <span class="la la-sync"></span>
            <p><?php esc_html_e('Loading...', 'autopuzzle'); ?></p>
        </div>
    </div>
    
    <!-- محتوای اصلی -->
    <div id="autopuzzle-report-content">
        <!-- کارت‌های آماری -->
        <div class="stats-cards-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <span class="la la-list"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="total_inquiries"><?php echo esc_html(number_format($stats['total_inquiries'])); ?></div>
                    <div class="stat-label"><?php esc_html_e('Total Inquiries', 'autopuzzle'); ?></div>
                    <div class="stat-meta">
                        <span class="la la-calendar-alt"></span>
                        <?php esc_html_e('Today:', 'autopuzzle'); ?> <span data-stat="new_today"><?php echo esc_html(number_format($stats['new_today'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card cash">
                <div class="stat-icon">
                    <span class="la la-dollar-sign"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="cash_inquiries"><?php echo esc_html(number_format($stats['cash_inquiries'])); ?></div>
                    <div class="stat-label"><?php esc_html_e('Cash Inquiries', 'autopuzzle'); ?></div>
                </div>
            </div>
            
            <div class="stat-card installment">
                <div class="stat-icon">
                    <span class="la la-calculator"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="installment_inquiries"><?php echo esc_html(number_format($stats['installment_inquiries'])); ?></div>
                    <div class="stat-label"><?php esc_html_e('Installment Inquiries', 'autopuzzle'); ?></div>
                </div>
            </div>
            
            <div class="stat-card approved">
                <div class="stat-icon">
                    <span class="la la-check-circle"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="approved"><?php echo esc_html(number_format($stats['approved'])); ?></div>
                    <div class="stat-label"><?php esc_html_e('Approved', 'autopuzzle'); ?></div>
                </div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon">
                    <span class="la la-clock"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="pending"><?php echo esc_html(number_format($stats['pending'])); ?></div>
                    <div class="stat-label"><?php esc_html_e('Pending', 'autopuzzle'); ?></div>
                </div>
            </div>
            
            <div class="stat-card rejected">
                <div class="stat-icon">
                    <span class="la la-times-circle"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="rejected"><?php echo esc_html(number_format($stats['rejected'])); ?></div>
                    <div class="stat-label"><?php esc_html_e('Rejected', 'autopuzzle'); ?></div>
                </div>
            </div>
            
            <div class="stat-card following">
                <div class="stat-icon">
                    <span class="la la-eye"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="following"><?php echo esc_html(number_format($stats['following'])); ?></div>
                    <div class="stat-label"><?php esc_html_e('In Progress', 'autopuzzle'); ?></div>
                </div>
            </div>
            
            <div class="stat-card next-followup">
                <div class="stat-icon">
                    <span class="la la-calendar-alt"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="next_followup"><?php echo esc_html(number_format($stats['next_followup'])); ?></div>
                    <div class="stat-label"><?php esc_html_e('Future Followup', 'autopuzzle'); ?></div>
                </div>
            </div>
            
            <div class="stat-card revenue">
                <div class="stat-icon">
                    <span class="la la-shopping-cart"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="revenue"><?php echo esc_html(number_format($stats['revenue'])); ?></div>
                    <div class="stat-label"><?php esc_html_e('Revenue (Toman)', 'autopuzzle'); ?></div>
                </div>
            </div>
        </div>
        
        <?php if ($show_charts && !empty($daily_stats)): ?>
        <!-- نمودار روند -->
        <div class="chart-section">
            <h3>
                <span class="la la-chart-line"></span>
                <?php esc_html_e('Daily Inquiry Trend', 'autopuzzle'); ?>
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
                <span class="la la-star"></span>
                <?php esc_html_e('Popular Products', 'autopuzzle'); ?>
            </h3>
            <div class="products-list" id="products-list">
                <?php foreach ($popular_products as $index => $product): ?>
                <div class="product-item">
                    <div class="product-rank"><?php echo esc_html($index + 1); ?></div>
                    <div class="product-info">
                        <div class="product-name"><?php echo esc_html($product->product_name ?: esc_html__('Unknown', 'autopuzzle')); ?></div>
                        <div class="product-count">
                            <span class="la la-chart-line"></span>
                            <?php echo esc_html(number_format($product->inquiry_count)); ?> <?php esc_html_e('inquiries', 'autopuzzle'); ?>
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
                <span class="la la-users"></span>
                <?php esc_html_e('Expert Performance', 'autopuzzle'); ?>
            </h3>
            <div class="experts-table-wrapper">
                <table class="experts-table" id="experts-table">
                    <thead>
                        <tr>
                            <th>
                                <span class="la la-users-cog"></span>
                                <?php esc_html_e('Expert', 'autopuzzle'); ?>
                            </th>
                            <th>
                                <span class="la la-list"></span>
                                <?php esc_html_e('Total Inquiries', 'autopuzzle'); ?>
                            </th>
                            <th>
                                <span class="la la-dollar-sign"></span>
                                <?php esc_html_e('Cash', 'autopuzzle'); ?>
                            </th>
                            <th>
                                <span class="la la-calculator"></span>
                                <?php esc_html_e('Installment', 'autopuzzle'); ?>
                            </th>
                            <th>
                                <span class="la la-check-circle"></span>
                                <?php esc_html_e('Approved', 'autopuzzle'); ?>
                            </th>
                            <th>
                                <span class="la la-times-circle"></span>
                                <?php esc_html_e('Rejected', 'autopuzzle'); ?>
                            </th>
                            <th>
                                <span class="la la-users"></span>
                                <?php esc_html_e('Customers', 'autopuzzle'); ?>
                            </th>
                            <th>
                                <span class="la la-shopping-cart"></span>
                                <?php esc_html_e('Revenue', 'autopuzzle'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($experts_stats as $expert): ?>
                        <tr>
                            <td class="expert-name"><?php echo esc_html($expert['expert_name']); ?></td>
                            <td><?php echo esc_html(number_format($expert['total_inquiries'])); ?></td>
                            <td><?php echo esc_html(number_format($expert['cash_inquiries'])); ?></td>
                            <td><?php echo esc_html(number_format($expert['installment_inquiries'])); ?></td>
                            <td><span class="badge approved"><?php echo esc_html(number_format($expert['approved'])); ?></span></td>
                            <td><span class="badge rejected"><?php echo esc_html(number_format($expert['rejected'])); ?></span></td>
                            <td><?php echo esc_html(number_format($expert['total_customers'])); ?></td>
                            <td><?php echo esc_html(number_format($expert['revenue'])); ?></td>
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
    const expertId = <?php echo $is_expert ? (int)$current_user->ID : 'null'; ?>;
    const ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
    const nonce = <?php echo wp_json_encode(wp_create_nonce('autopuzzle_reports_shortcode_nonce')); ?>;
    
    // Translation object
    const translations = {
        cash: <?php echo json_encode(esc_html__('Cash', 'autopuzzle')); ?>,
        installment: <?php echo json_encode(esc_html__('Installment', 'autopuzzle')); ?>,
        unknown: <?php echo json_encode(esc_html__('Unknown', 'autopuzzle')); ?>,
        inquiries: <?php echo json_encode(esc_html__('inquiries', 'autopuzzle')); ?>,
        timePeriod: <?php echo json_encode(esc_html__('Time Period:', 'autopuzzle')); ?>,
        days: <?php echo json_encode(esc_html__('days', 'autopuzzle')); ?>,
        to: <?php echo json_encode(esc_html__('to', 'autopuzzle')); ?>
    };
    
    // مدیریت تغییر بازه زمانی
    $('#autopuzzle-date-preset').on('change', function() {
        if ($(this).val() === 'custom') {
            $('.custom-dates').slideDown();
        } else {
            $('.custom-dates').slideUp();
        }
    });
    
    // اعمال فیلتر
    $('#autopuzzle-apply-filter, #autopuzzle-refresh-data').on('click', function() {
        loadReportData();
    });
    
    // بارگذاری داده‌ها
    function loadReportData() {
        const preset = $('#autopuzzle-date-preset').val();
        let startDate, endDate;
        
        if (preset === 'custom') {
            startDate = $('#autopuzzle-start-date').val();
            endDate = $('#autopuzzle-end-date').val();
        } else {
            const dates = getDateRangeFromPreset(preset);
            startDate = dates.start;
            endDate = dates.end;
        }
        
        const selectedExpertId = isExpert ? expertId : ($('#autopuzzle-expert-filter').val() || null);
        
        // نمایش loading
        $('#autopuzzle-loading').fadeIn();
        
        // درخواست Ajax برای آمار کلی
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'autopuzzle_shortcode_get_stats',
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
                $('#autopuzzle-loading').fadeOut();
            },
            error: function() {
                alert(<?php echo json_encode(esc_html__('Error loading data', 'autopuzzle')); ?>);
                $('#autopuzzle-loading').fadeOut();
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
                        label: translations.cash,
                        data: cashData,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: translations.installment,
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
                        <div class="product-name">${product.product_name || translations.unknown}</div>
                        <div class="product-count">
                            <span class="la la-chart-line"></span>
                            ${formatNumber(product.inquiry_count)} ${translations.inquiries}
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
        $('#period-text').html(`${translations.timePeriod} <strong>${days} ${translations.days}</strong> (${startDate} ${translations.to} ${endDate})`);
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
    const initialDailyData = <?php echo wp_json_encode($daily_stats); ?>;
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
/* Dashicons Font Face - برای اطمینان از بارگذاری */
@font-face {
    font-family: dashicons;
    src: url(<?php echo esc_url(includes_url('fonts/dashicons.woff2')); ?>) format("woff2"),
         url(<?php echo esc_url(includes_url('fonts/dashicons.woff')); ?>) format("woff"),
         url(<?php echo esc_url(includes_url('fonts/dashicons.ttf')); ?>) format("truetype");
    font-weight: 400;
    font-style: normal;
}

.dashicons,
.dashicons-before:before {
    font-family: dashicons !important;
    font-size: 20px;
    font-style: normal;
    font-weight: 400;
    line-height: 1;
    text-align: center;
    text-decoration: inherit;
    text-transform: none;
    vertical-align: top;
    speak: never;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    width: 20px;
    height: 20px;
    display: inline-block;
}

.autopuzzle-system-report-shortcode {
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
