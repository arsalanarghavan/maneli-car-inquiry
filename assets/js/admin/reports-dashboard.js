/**
 * اسکریپت داشبورد گزارشات
 * 
 * @package Maneli_Car_Inquiry
 */

(function($) {
    'use strict';
    
    // متغیرهای سراسری
    let currentPage = 1;
    let currentFilters = {
        start_date: null,
        end_date: null,
        expert_id: null
    };
    
    // نمودارها
    let dailyTrendChart = null;
    let statusPieChart = null;
    let typeDoughnutChart = null;
    let monthlyPerformanceChart = null;
    let expertsComparisonChart = null;
    
    /**
     * اجرا بعد از لود شدن صفحه
     */
    $(document).ready(function() {
        initializeDateRange();
        initializeTabNavigation();
        initializeFilters();
        loadOverallStats();
        loadDailyTrend();
        loadPopularProducts();
    });
    
    /**
     * مقداردهی اولیه بازه زمانی
     */
    function initializeDateRange() {
        const today = new Date();
        const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
        
        $('#start-date').val(formatDate(lastMonth));
        $('#end-date').val(formatDate(today));
        
        currentFilters.start_date = formatDate(lastMonth);
        currentFilters.end_date = formatDate(today);
    }
    
    /**
     * تنظیم ناوبری تب‌ها
     */
    function initializeTabNavigation() {
        $('.tab-link').on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            
            // تغییر تب فعال
            $('.tab-link').removeClass('active');
            $(this).addClass('active');
            
            $('.tab-content').removeClass('active');
            $(target).addClass('active');
            
            // بارگذاری داده‌های تب
            loadTabData(target);
        });
    }
    
    /**
     * بارگذاری داده‌های تب
     */
    function loadTabData(tabId) {
        switch(tabId) {
            case '#tab-overview':
                // قبلاً بارگذاری شده
                break;
            case '#tab-experts':
                loadExpertsStats();
                break;
            case '#tab-details':
                loadInquiriesDetails();
                break;
            case '#tab-charts':
                loadAllCharts();
                break;
        }
    }
    
    /**
     * تنظیم فیلترها
     */
    function initializeFilters() {
        // تغییر پیش‌فرض بازه زمانی
        $('#date-range-preset').on('change', function() {
            const preset = $(this).val();
            
            if (preset === 'custom') {
                $('.custom-date-range').show();
            } else {
                $('.custom-date-range').hide();
                const dates = getDateRangeFromPreset(preset);
                $('#start-date').val(dates.start);
                $('#end-date').val(dates.end);
            }
        });
        
        // دکمه اعمال فیلتر
        $('#apply-filters').on('click', function() {
            currentFilters.start_date = $('#start-date').val();
            currentFilters.end_date = $('#end-date').val();
            currentFilters.expert_id = $('#expert-filter').val() || null;
            
            refreshAllData();
        });
        
        // دکمه بروزرسانی
        $('#refresh-data').on('click', function() {
            refreshAllData();
        });
        
        // فیلترهای جزئیات
        $('#details-type, #details-status').on('change', function() {
            currentPage = 1;
            loadInquiriesDetails();
        });
        
        // صفحه‌بندی
        $('#prev-page').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadInquiriesDetails();
            }
        });
        
        $('#next-page').on('click', function() {
            currentPage++;
            loadInquiriesDetails();
        });
        
        // دانلود CSV
        $('#export-csv').on('click', function() {
            exportToCSV();
        });
    }
    
    /**
     * دریافت بازه زمانی از پیش‌فرض
     */
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
    
    /**
     * فرمت تاریخ
     */
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    /**
     * بروزرسانی تمام داده‌ها
     */
    function refreshAllData() {
        const activeTab = $('.tab-link.active').attr('href');
        
        // نمای کلی
        loadOverallStats();
        loadDailyTrend();
        loadPopularProducts();
        
        // تب‌های دیگر
        if (activeTab === '#tab-experts') {
            loadExpertsStats();
        } else if (activeTab === '#tab-details') {
            loadInquiriesDetails();
        } else if (activeTab === '#tab-charts') {
            loadAllCharts();
        }
    }
    
    /**
     * بارگذاری آمار کلی
     */
    function loadOverallStats() {
        $.ajax({
            url: maneliReports.ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_get_overall_stats',
                nonce: maneliReports.nonce,
                start_date: currentFilters.start_date,
                end_date: currentFilters.end_date,
                expert_id: currentFilters.expert_id
            },
            success: function(response) {
                if (response.success) {
                    updateStatsCards(response.data);
                }
            }
        });
    }
    
    /**
     * بروزرسانی کارت‌های آماری
     */
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
        $('.stat-value').each(function() {
            $(this).addClass('updated');
            setTimeout(() => {
                $(this).removeClass('updated');
            }, 500);
        });
    }
    
    /**
     * بارگذاری روند روزانه
     */
    function loadDailyTrend() {
        $.ajax({
            url: maneliReports.ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_get_daily_stats',
                nonce: maneliReports.nonce,
                start_date: currentFilters.start_date,
                end_date: currentFilters.end_date,
                expert_id: currentFilters.expert_id
            },
            success: function(response) {
                if (response.success) {
                    renderDailyTrendChart(response.data);
                }
            }
        });
    }
    
    /**
     * رندر نمودار روند روزانه
     */
    function renderDailyTrendChart(data) {
        const ctx = document.getElementById('daily-trend-chart');
        if (!ctx) return;
        
        // نابودی نمودار قبلی
        if (dailyTrendChart) {
            dailyTrendChart.destroy();
        }
        
        const labels = data.map(item => item.date);
        const cashData = data.map(item => item.cash);
        const installmentData = data.map(item => item.installment);
        
        dailyTrendChart = new Chart(ctx, {
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
                        rtl: true,
                        labels: {
                            font: {
                                family: 'IRANSans, sans-serif'
                            }
                        }
                    },
                    tooltip: {
                        rtl: true,
                        titleFont: {
                            family: 'IRANSans, sans-serif'
                        },
                        bodyFont: {
                            family: 'IRANSans, sans-serif'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                family: 'IRANSans, sans-serif'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'IRANSans, sans-serif'
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * بارگذاری محصولات پرطرفدار
     */
    function loadPopularProducts() {
        $.ajax({
            url: maneliReports.ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_get_popular_products',
                nonce: maneliReports.nonce,
                start_date: currentFilters.start_date,
                end_date: currentFilters.end_date,
                expert_id: currentFilters.expert_id,
                limit: 10
            },
            success: function(response) {
                if (response.success) {
                    renderPopularProducts(response.data);
                }
            }
        });
    }
    
    /**
     * رندر محصولات پرطرفدار
     */
    function renderPopularProducts(products) {
        const container = $('#popular-products-list');
        
        if (products.length === 0) {
            container.html('<div class="no-data"><p>داده‌ای یافت نشد</p></div>');
            return;
        }
        
        let html = '';
        products.forEach((product, index) => {
            html += `
                <div class="product-item">
                    <div class="product-info">
                        <div class="product-rank">${index + 1}</div>
                        <div class="product-name">${product.product_name || 'نامشخص'}</div>
                    </div>
                    <div class="product-count">
                        <span class="dashicons dashicons-chart-line"></span>
                        ${formatNumber(product.inquiry_count)} استعلام
                    </div>
                </div>
            `;
        });
        
        container.html(html);
    }
    
    /**
     * بارگذاری آمار کارشناسان
     */
    function loadExpertsStats() {
        $('#experts-stats-table').html('<div class="loading">در حال بارگذاری...</div>');
        
        $.ajax({
            url: maneliReports.ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_get_experts_stats',
                nonce: maneliReports.nonce,
                start_date: currentFilters.start_date,
                end_date: currentFilters.end_date
            },
            success: function(response) {
                if (response.success) {
                    renderExpertsTable(response.data);
                }
            }
        });
    }
    
    /**
     * رندر جدول کارشناسان
     */
    function renderExpertsTable(experts) {
        if (experts.length === 0) {
            $('#experts-stats-table').html('<div class="no-data"><p>کارشناسی یافت نشد</p></div>');
            return;
        }
        
        let html = `
            <table>
                <thead>
                    <tr>
                        <th>کارشناس</th>
                        <th>کل استعلام‌ها</th>
                        <th>نقدی</th>
                        <th>اقساطی</th>
                        <th>تایید شده</th>
                        <th>رد شده</th>
                        <th>در انتظار</th>
                        <th>کل مشتریان</th>
                        <th>مشتریان جدید</th>
                        <th>درآمد</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        experts.forEach(expert => {
            html += `
                <tr>
                    <td class="expert-name">${expert.expert_name}</td>
                    <td>${formatNumber(expert.total_inquiries)}</td>
                    <td>${formatNumber(expert.cash_inquiries)}</td>
                    <td>${formatNumber(expert.installment_inquiries)}</td>
                    <td><span class="stat-badge approved">${formatNumber(expert.approved)}</span></td>
                    <td><span class="stat-badge rejected">${formatNumber(expert.rejected)}</span></td>
                    <td><span class="stat-badge pending">${formatNumber(expert.pending)}</span></td>
                    <td>${formatNumber(expert.total_customers)}</td>
                    <td>${formatNumber(expert.new_customers)}</td>
                    <td>${formatNumber(expert.revenue)} تومان</td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        $('#experts-stats-table').html(html);
    }
    
    /**
     * بارگذاری جزئیات استعلام‌ها
     */
    function loadInquiriesDetails() {
        $('#inquiries-details-table').html('<div class="loading">در حال بارگذاری...</div>');
        
        $.ajax({
            url: maneliReports.ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_get_inquiries_details',
                nonce: maneliReports.nonce,
                start_date: currentFilters.start_date,
                end_date: currentFilters.end_date,
                expert_id: currentFilters.expert_id,
                type: $('#details-type').val(),
                status: $('#details-status').val(),
                limit: 50,
                offset: (currentPage - 1) * 50
            },
            success: function(response) {
                if (response.success) {
                    renderInquiriesTable(response.data);
                }
            }
        });
    }
    
    /**
     * رندر جدول جزئیات استعلام‌ها
     */
    function renderInquiriesTable(data) {
        if (data.inquiries.length === 0) {
            $('#inquiries-details-table').html('<div class="no-data"><p>استعلامی یافت نشد</p></div>');
            return;
        }
        
        let html = `
            <table>
                <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>تاریخ</th>
                        <th>نوع</th>
                        <th>نام مشتری</th>
                        <th>تلفن</th>
                        <th>محصول</th>
                        <th>وضعیت</th>
                        <th>مبلغ</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.inquiries.forEach(inquiry => {
            const type = inquiry.post_type === 'cash_inquiry' ? 'نقدی' : 'اقساطی';
            const typeClass = inquiry.post_type === 'cash_inquiry' ? 'cash' : 'installment';
            
            html += `
                <tr>
                    <td>${inquiry.ID}</td>
                    <td>${inquiry.post_date}</td>
                    <td><span class="type-badge ${typeClass}">${type}</span></td>
                    <td>${inquiry.customer_name || '-'}</td>
                    <td>${inquiry.customer_phone || '-'}</td>
                    <td>${inquiry.product_name || '-'}</td>
                    <td>${getStatusBadge(inquiry.status)}</td>
                    <td>${formatNumber(inquiry.amount || 0)} تومان</td>
                    <td>
                        <a href="post.php?post=${inquiry.ID}&action=edit" class="button button-small">مشاهده</a>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        $('#inquiries-details-table').html(html);
        
        // بروزرسانی صفحه‌بندی
        updatePagination(data.pages);
    }
    
    /**
     * بروزرسانی صفحه‌بندی
     */
    function updatePagination(totalPages) {
        $('#page-info').text(`صفحه ${currentPage} از ${totalPages}`);
        
        $('#prev-page').prop('disabled', currentPage <= 1);
        $('#next-page').prop('disabled', currentPage >= totalPages);
    }
    
    /**
     * دریافت برچسب وضعیت
     */
    function getStatusBadge(status) {
        const labels = {
            'pending': 'در انتظار',
            'approved': 'تایید شده',
            'rejected': 'رد شده',
            'following': 'در حال پیگیری'
        };
        
        const label = labels[status] || status;
        return `<span class="status-badge ${status}">${label}</span>`;
    }
    
    /**
     * بارگذاری تمام نمودارها
     */
    function loadAllCharts() {
        loadStatusPieChart();
        loadTypeDoughnutChart();
        loadMonthlyPerformanceChart();
        loadExpertsComparisonChart();
    }
    
    /**
     * نمودار دایره‌ای وضعیت‌ها
     */
    function loadStatusPieChart() {
        $.ajax({
            url: maneliReports.ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_get_overall_stats',
                nonce: maneliReports.nonce,
                start_date: currentFilters.start_date,
                end_date: currentFilters.end_date,
                expert_id: currentFilters.expert_id
            },
            success: function(response) {
                if (response.success) {
                    renderStatusPieChart(response.data);
                }
            }
        });
    }
    
    /**
     * رندر نمودار دایره‌ای وضعیت
     */
    function renderStatusPieChart(stats) {
        const ctx = document.getElementById('status-pie-chart');
        if (!ctx) return;
        
        if (statusPieChart) {
            statusPieChart.destroy();
        }
        
        statusPieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['در انتظار', 'تایید شده', 'رد شده', 'در حال پیگیری'],
                datasets: [{
                    data: [stats.pending, stats.approved, stats.rejected, stats.following],
                    backgroundColor: ['#f0b849', '#00a32a', '#d63638', '#8c6d1f']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            font: {
                                family: 'IRANSans, sans-serif'
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * نمودار دونات نوع استعلام
     */
    function loadTypeDoughnutChart() {
        $.ajax({
            url: maneliReports.ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_get_overall_stats',
                nonce: maneliReports.nonce,
                start_date: currentFilters.start_date,
                end_date: currentFilters.end_date,
                expert_id: currentFilters.expert_id
            },
            success: function(response) {
                if (response.success) {
                    renderTypeDoughnutChart(response.data);
                }
            }
        });
    }
    
    /**
     * رندر نمودار دونات
     */
    function renderTypeDoughnutChart(stats) {
        const ctx = document.getElementById('type-doughnut-chart');
        if (!ctx) return;
        
        if (typeDoughnutChart) {
            typeDoughnutChart.destroy();
        }
        
        typeDoughnutChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['نقدی', 'اقساطی'],
                datasets: [{
                    data: [stats.cash_inquiries, stats.installment_inquiries],
                    backgroundColor: ['#00a32a', '#8c6d1f']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            font: {
                                family: 'IRANSans, sans-serif'
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * نمودار عملکرد ماهانه
     */
    function loadMonthlyPerformanceChart() {
        $.ajax({
            url: maneliReports.ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_get_monthly_performance',
                nonce: maneliReports.nonce,
                months: 6,
                expert_id: currentFilters.expert_id
            },
            success: function(response) {
                if (response.success) {
                    renderMonthlyPerformanceChart(response.data);
                }
            }
        });
    }
    
    /**
     * رندر نمودار عملکرد ماهانه
     */
    function renderMonthlyPerformanceChart(data) {
        const ctx = document.getElementById('monthly-performance-chart');
        if (!ctx) return;
        
        if (monthlyPerformanceChart) {
            monthlyPerformanceChart.destroy();
        }
        
        const labels = data.map(item => item.month_persian);
        const cashData = data.map(item => item.cash);
        const installmentData = data.map(item => item.installment);
        
        monthlyPerformanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'نقدی',
                        data: cashData,
                        backgroundColor: '#00a32a'
                    },
                    {
                        label: 'اقساطی',
                        data: installmentData,
                        backgroundColor: '#8c6d1f'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        rtl: true,
                        labels: {
                            font: {
                                family: 'IRANSans, sans-serif'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true,
                        ticks: {
                            font: {
                                family: 'IRANSans, sans-serif'
                            }
                        }
                    },
                    x: {
                        stacked: true,
                        ticks: {
                            font: {
                                family: 'IRANSans, sans-serif'
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * نمودار مقایسه کارشناسان
     */
    function loadExpertsComparisonChart() {
        $.ajax({
            url: maneliReports.ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_get_experts_stats',
                nonce: maneliReports.nonce,
                start_date: currentFilters.start_date,
                end_date: currentFilters.end_date
            },
            success: function(response) {
                if (response.success) {
                    renderExpertsComparisonChart(response.data);
                }
            }
        });
    }
    
    /**
     * رندر نمودار مقایسه کارشناسان
     */
    function renderExpertsComparisonChart(experts) {
        const ctx = document.getElementById('experts-comparison-chart');
        if (!ctx) return;
        
        if (expertsComparisonChart) {
            expertsComparisonChart.destroy();
        }
        
        const labels = experts.map(e => e.expert_name);
        const totalData = experts.map(e => e.total_inquiries);
        const approvedData = experts.map(e => e.approved);
        const rejectedData = experts.map(e => e.rejected);
        
        expertsComparisonChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'کل استعلام‌ها',
                        data: totalData,
                        backgroundColor: '#2271b1'
                    },
                    {
                        label: 'تایید شده',
                        data: approvedData,
                        backgroundColor: '#00a32a'
                    },
                    {
                        label: 'رد شده',
                        data: rejectedData,
                        backgroundColor: '#d63638'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        rtl: true,
                        labels: {
                            font: {
                                family: 'IRANSans, sans-serif'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                family: 'IRANSans, sans-serif'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'IRANSans, sans-serif'
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * صادرات به CSV
     */
    function exportToCSV() {
        const params = new URLSearchParams({
            action: 'maneli_export_inquiries_csv',
            nonce: maneliReports.nonce,
            start_date: currentFilters.start_date,
            end_date: currentFilters.end_date,
            expert_id: currentFilters.expert_id || '',
            type: $('#details-type').val(),
            status: $('#details-status').val()
        });
        
        window.location.href = maneliReports.ajaxUrl + '?' + params.toString();
    }
    
    /**
     * فرمت اعداد با جداکننده
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
})(jQuery);

