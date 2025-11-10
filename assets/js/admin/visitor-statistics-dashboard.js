/**
 * Visitor Statistics Dashboard JavaScript
 * مدیریت نمودارها و آمارهای صفحه آمار بازدیدکنندگان
 * 
 * @package Maneli_Car_Inquiry
 */

(function() {
    'use strict';
    
    // Wait for jQuery to be available
    function initVisitorStats() {
        console.log('initVisitorStats called');
        console.log('jQuery available:', typeof jQuery !== 'undefined');
        console.log('ApexCharts available:', typeof ApexCharts !== 'undefined');
        console.log('maneliVisitorStats available:', typeof maneliVisitorStats !== 'undefined');
        
        if (typeof jQuery === 'undefined') {
            console.warn('jQuery not available, retrying in 100ms...');
            setTimeout(initVisitorStats, 100);
            return;
        }
        
        var $ = jQuery;
        
        // Check if ApexCharts is available
        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts library is not loaded');
            return;
        }
        
        // Check if localization data exists
        if (typeof maneliVisitorStats === 'undefined') {
            console.error('maneliVisitorStats object is not defined');
            return;
        }
        
        console.log('All dependencies loaded, initializing visitor stats...');
        console.log('maneliVisitorStats object:', maneliVisitorStats);
        
        var charts = {};
        var translations = maneliVisitorStats.translations || {};
        var unknownLabel = translations.unknown || 'Unknown';
        var deviceLabels = {
            desktop: translations.deviceDesktop || 'Desktop',
            mobile: translations.deviceMobile || 'Mobile',
            tablet: translations.deviceTablet || 'Tablet',
            unknown: unknownLabel
        };
        var countryNames = maneliVisitorStats.countryNames || {};
        var countryFlags = maneliVisitorStats.countryFlags || {};
        var browserNames = maneliVisitorStats.browserNames || {};
        var osNames = maneliVisitorStats.osNames || {};
        var deviceNames = maneliVisitorStats.deviceNames || {};
        var dailyStatsData = maneliVisitorStats.dailyStats || [];
        
        /**
         * Convert English digits to Persian digits
         */
        function toPersianDigits(str) {
            if (!str) return '';
            const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            let result = String(str);
            for (let i = 0; i < 10; i++) {
                result = result.split(english[i]).join(persian[i]);
            }
            return result;
        }
    
        /**
         * Format number with Persian digits
         */
        function formatPersianNumber(num) {
            return toPersianDigits(num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','));
        }
        
        /**
         * Initialize all charts
         */
        function initCharts() {
            initTrafficTrendChart();
            initBrowsersChart();
            initOSChart();
            initDeviceTypesChart();
            loadOnlineVisitors();
            
            // Convert all numbers on page to Persian
            $('.fs-21, .fs-16, td, .badge').each(function() {
                var $el = $(this);
                var text = $el.text();
                // Only convert if it's a number (avoid converting already Persian numbers)
                if (/^\d+([,\d]*)?$/.test(text.trim())) {
                    $el.text(toPersianDigits(text));
                }
            });
        }
        
        /**
         * Initialize Traffic Trend Chart
         */
        function initTrafficTrendChart() {
            console.log('Initializing traffic trend chart with data:', dailyStatsData);
            var dates = [];
            var visits = [];
            var uniqueVisitors = [];
            
            if (!dailyStatsData || dailyStatsData.length === 0) {
                console.warn('No daily stats data available');
                $('#traffic-trend-chart').html('<div class="text-center text-muted p-4">' + (maneliVisitorStats.translations.noData || 'No data available') + '</div>');
                return;
            }
            
            dailyStatsData.forEach(function(stat) {
                // Convert date to Jalali format if needed (assuming dates come in YYYY-MM-DD format)
                var displayDate = stat.date;
                if (stat.date && stat.date.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    // Date is in Gregorian format, but we'll display it as is for now
                    // The server should convert it to Jalali
                    displayDate = stat.date;
                }
                // Replace hyphen with slash for better readability and convert digits
                displayDate = displayDate.replace(/-/g, '/');
                dates.push(toPersianDigits(displayDate));
                visits.push(parseInt(stat.visits) || 0);
                uniqueVisitors.push(parseInt(stat.unique_visitors) || 0);
            });
            
            var options = {
                series: [{
                    name: maneliVisitorStats.translations.visits,
                    data: visits
                }, {
                    name: maneliVisitorStats.translations.uniqueVisitors,
                    data: uniqueVisitors
                }],
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: false,
                            reset: true
                        }
                    },
                    fontFamily: 'IRANSans, Arial, sans-serif'
                },
                colors: ['#589bff', '#af6ded'],
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 4,
                    hover: {
                        size: 6
                    }
                },
                xaxis: {
                    categories: dates,
                    labels: {
                        style: {
                            colors: '#8c9097',
                            fontSize: '11px',
                            fontFamily: 'IRANSans, Arial, sans-serif'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#8c9097',
                            fontSize: '11px',
                            fontFamily: 'IRANSans, Arial, sans-serif'
                        },
                        formatter: function(val) {
                            return formatPersianNumber(Math.round(val));
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontFamily: 'IRANSans, Arial, sans-serif',
                    fontSize: '13px'
                },
                grid: {
                    borderColor: '#f2f5f7'
                },
                tooltip: {
                    theme: 'light',
                    fontFamily: 'IRANSans, Arial, sans-serif'
                }
            };
            
            var chart = new ApexCharts(document.querySelector("#traffic-trend-chart"), options);
            chart.render();
            charts.trafficTrend = chart;
        }
        
        /**
         * Initialize Browsers Chart
         */
        function initBrowsersChart() {
            console.log('Loading browser stats...', {
                ajaxUrl: maneliVisitorStats.ajaxUrl,
                startDate: maneliVisitorStats.startDate,
                endDate: maneliVisitorStats.endDate
            });
            
            $.ajax({
                url: maneliVisitorStats.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_get_browser_stats',
                    nonce: maneliVisitorStats.nonce,
                    start_date: maneliVisitorStats.startDate,
                    end_date: maneliVisitorStats.endDate
                },
                success: function(response) {
                    console.log('Browser stats response:', response);
                    if (response.success && response.data) {
                        var labels = [];
                        var series = [];
                        
                        response.data.forEach(function(item) {
                            var browserLabel = item.browser || 'unknown';
                            var browserKey = browserLabel.toLowerCase();
                            var localizedBrowser = browserNames[browserKey] || browserNames[item.browser] || browserNames[browserLabel] || unknownLabel;
                            labels.push(localizedBrowser);
                            series.push(parseInt(item.visit_count) || 0);
                        });
                        
                        var options = {
                            series: series,
                            chart: {
                                type: 'pie',
                                height: 300,
                                fontFamily: 'IRANSans, Arial, sans-serif'
                            },
                            labels: labels,
                            colors: ['#589bff', '#af6ded', '#f76565', '#ffc107', '#51d28c', '#ff9800', '#9c27b0'],
                            legend: {
                                position: 'bottom',
                                fontFamily: 'IRANSans, Arial, sans-serif',
                                fontSize: '12px'
                            },
                            tooltip: {
                                theme: 'light',
                                fontFamily: 'IRANSans, Arial, sans-serif'
                            },
                            dataLabels: {
                                enabled: true,
                                formatter: function(val) {
                                    return Math.round(val) + '%';
                                }
                            }
                        };
                        
                        var chart = new ApexCharts(document.querySelector("#browsers-chart"), options);
                        chart.render();
                        charts.browsers = chart;
                    } else {
                        console.warn('Browser stats: No data or failed response', response);
                        $('#browsers-chart').html('<div class="text-center text-muted p-4">' + (maneliVisitorStats.translations.noData || 'No data available') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Browser stats AJAX error:', status, error, xhr);
                    $('#browsers-chart').html('<div class="text-center text-muted p-4">' + maneliVisitorStats.translations.error + '</div>');
                }
            });
        }
        
        /**
         * Initialize OS Chart
         */
        function initOSChart() {
            console.log('Loading OS stats...');
            $.ajax({
                url: maneliVisitorStats.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_get_os_stats',
                    nonce: maneliVisitorStats.nonce,
                    start_date: maneliVisitorStats.startDate,
                    end_date: maneliVisitorStats.endDate
                },
                success: function(response) {
                    console.log('OS stats response:', response);
                    if (response.success && response.data) {
                        var labels = [];
                        var series = [];
                        
                        response.data.forEach(function(item) {
                            var osLabel = item.os || 'unknown';
                            var osKey = osLabel.toLowerCase();
                            var localizedOS = osNames[osKey] || osNames[item.os] || osNames[osLabel] || unknownLabel;
                            labels.push(localizedOS);
                            series.push(parseInt(item.visit_count) || 0);
                        });
                        
                        var options = {
                            series: series,
                            chart: {
                                type: 'donut',
                                height: 300,
                                fontFamily: 'IRANSans, Arial, sans-serif'
                            },
                            labels: labels,
                            colors: ['#589bff', '#af6ded', '#f76565', '#ffc107', '#51d28c'],
                            legend: {
                                position: 'bottom',
                                fontFamily: 'IRANSans, Arial, sans-serif',
                                fontSize: '12px'
                            },
                            tooltip: {
                                theme: 'light',
                                fontFamily: 'IRANSans, Arial, sans-serif'
                            },
                            dataLabels: {
                                enabled: true,
                                formatter: function(val) {
                                    return Math.round(val) + '%';
                                }
                            }
                        };
                        
                        var chart = new ApexCharts(document.querySelector("#os-chart"), options);
                        chart.render();
                        charts.os = chart;
                    } else {
                        console.warn('OS stats: No data or failed response', response);
                        $('#os-chart').html('<div class="text-center text-muted p-4">' + (maneliVisitorStats.translations.noData || 'No data available') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('OS stats AJAX error:', status, error, xhr);
                    $('#os-chart').html('<div class="text-center text-muted p-4">' + maneliVisitorStats.translations.error + '</div>');
                }
            });
        }
        
        /**
         * Initialize Device Types Chart
         */
        function initDeviceTypesChart() {
            console.log('Loading device stats...');
            $.ajax({
                url: maneliVisitorStats.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_get_device_stats',
                    nonce: maneliVisitorStats.nonce,
                    start_date: maneliVisitorStats.startDate,
                    end_date: maneliVisitorStats.endDate
                },
                success: function(response) {
                    console.log('Device stats response:', response);
                    if (response.success && response.data) {
                        var labels = [];
                        var series = [];
                        
                        response.data.forEach(function(item) {
                            var deviceType = (item.device_type || 'unknown').toLowerCase();
                            if (!deviceLabels.hasOwnProperty(deviceType)) {
                                deviceType = 'unknown';
                            }
                            var localizedDevice = deviceNames[deviceType] || deviceLabels[deviceType];
                            labels.push(localizedDevice);
                            series.push(parseInt(item.visit_count) || 0);
                        });
                        
                        var options = {
                            series: series,
                            chart: {
                                type: 'bar',
                                height: 300,
                                fontFamily: 'IRANSans, Arial, sans-serif',
                                horizontal: true
                            },
                            plotOptions: {
                                bar: {
                                    borderRadius: 4,
                                    horizontal: true
                                }
                            },
                            dataLabels: {
                                enabled: true,
                                formatter: function(val) {
                                    return Math.round(val);
                                }
                            },
                            xaxis: {
                                categories: labels,
                                labels: {
                                    style: {
                                        colors: '#8c9097',
                                        fontSize: '11px',
                                        fontFamily: 'IRANSans, Arial, sans-serif'
                                    }
                                }
                            },
                            yaxis: {
                                labels: {
                                    style: {
                                        colors: '#8c9097',
                                        fontSize: '11px',
                                        fontFamily: 'IRANSans, Arial, sans-serif'
                                    }
                                }
                            },
                            colors: ['#589bff', '#af6ded', '#f76565'],
                            tooltip: {
                                theme: 'light',
                                fontFamily: 'IRANSans, Arial, sans-serif'
                            }
                        };
                        
                        var chart = new ApexCharts(document.querySelector("#device-types-chart"), options);
                        chart.render();
                        charts.deviceTypes = chart;
                    } else {
                        console.warn('Device stats: No data or failed response', response);
                        $('#device-types-chart').html('<div class="text-center text-muted p-4">' + (maneliVisitorStats.translations.noData || 'No data available') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Device stats AJAX error:', status, error, xhr);
                    $('#device-types-chart').html('<div class="text-center text-muted p-4">' + maneliVisitorStats.translations.error + '</div>');
                }
            });
        }
        
        /**
         * Load online visitors (refresh every 30 seconds)
         */
        function loadOnlineVisitors() {
            console.log('Loading online visitors...');
            $.ajax({
                url: maneliVisitorStats.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_get_online_visitors',
                    nonce: maneliVisitorStats.nonce
                },
                success: function(response) {
                    console.log('Online visitors response:', response);
                    if (response.success && response.data) {
                        var tbody = $('#online-visitors-table');
                        tbody.empty();
                        
                        if (response.data.length === 0) {
                            tbody.html('<tr><td colspan="7" class="text-center text-muted">' + maneliVisitorStats.translations.noData + '</td></tr>');
                        } else {
                            response.data.forEach(function(visitor) {
                                var countryCode = (visitor.country_code || '').toUpperCase();
                                var countryName = visitor.country || countryNames[countryCode] || unknownLabel;
                                if (countryName === 'Unknown') {
                                    countryName = unknownLabel;
                                }
                                var flagClass = visitor.country_flag || countryFlags[countryCode] || 'flag-icon flag-icon-un';
                                var browserKey = (visitor.browser || '').toLowerCase();
                                var browserName = visitor.browser || browserNames[browserKey] || unknownLabel;
                                if (browserName === 'Unknown') {
                                    browserName = unknownLabel;
                                }
                                var osKey = (visitor.os || '').toLowerCase();
                                var osName = visitor.os || osNames[osKey] || unknownLabel;
                                if (osName === 'Unknown') {
                                    osName = unknownLabel;
                                }
                                var deviceTypeKey = (visitor.device_type || '').toLowerCase();
                                var deviceTypeLabel = visitor.device_type_label || deviceNames[deviceTypeKey] || deviceLabels[deviceTypeKey] || deviceLabels.unknown;
                                
                                var timeAgo = visitor.time_ago || getTimeAgo(visitor.visit_date);
                                if (!timeAgo) {
                                    timeAgo = translations.momentsAgo || ('0 ' + (translations.unitSecond || 'second') + ' ' + (translations.ago || 'ago'));
                                }
                                timeAgo = toPersianDigits(timeAgo);
                                
                                var row = '<tr>' +
                                    '<td>' + escapeHtml(visitor.ip_address) + '</td>' +
                                    '<td><span class="' + escapeHtml(flagClass) + ' me-2"></span>' + escapeHtml(countryName) + '</td>' +
                                    '<td>' + escapeHtml(browserName) + '</td>' +
                                    '<td>' + escapeHtml(osName) + '</td>' +
                                    '<td>' + escapeHtml(deviceTypeLabel) + '</td>' +
                                    '<td><div class="text-truncate" style="max-width: 200px;" title="' + escapeHtml(visitor.page_url || '') + '">' + 
                                        escapeHtml(visitor.page_title || visitor.page_url || '') + '</div></td>' +
                                    '<td>' + timeAgo + '</td>' +
                                    '</tr>';
                                tbody.append(row);
                            });
                        }
                        
                        // Update online count with Persian digits
                        $('#online-visitors').text(toPersianDigits(response.data.length));
                    } else {
                        console.warn('Online visitors: No data or failed response', response);
                        $('#online-visitors-table').html('<tr><td colspan="7" class="text-center text-muted">' + (maneliVisitorStats.translations.noData || 'No data available') + '</td></tr>');
                        $('#online-visitors').text(toPersianDigits(0));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Online visitors AJAX error:', status, error, xhr);
                }
            });
            
            // Refresh every 30 seconds
            setTimeout(loadOnlineVisitors, 30000);
        }
        
        /**
         * Get time ago from date
         */
        function getTimeAgo(dateString) {
            var now = new Date();
            var date = new Date(dateString);
            var diff = Math.floor((now - date) / 1000);
            
            var suffix = translations.ago || 'ago';
            var labels = {
                second: translations.unitSecond || 'second',
                minute: translations.unitMinute || 'minute',
                hour: translations.unitHour || 'hour',
                day: translations.unitDay || 'day'
            };
            
            if (diff < 60) {
                return diff + ' ' + labels.second + ' ' + suffix;
            }
            if (diff < 3600) {
                var minutes = Math.floor(diff / 60);
                return minutes + ' ' + labels.minute + ' ' + suffix;
            }
            if (diff < 86400) {
                var hours = Math.floor(diff / 3600);
                return hours + ' ' + labels.hour + ' ' + suffix;
            }
            var days = Math.floor(diff / 86400);
            return days + ' ' + labels.day + ' ' + suffix;
        }
        
        /**
         * Escape HTML
         */
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
        }
        
        /**
         * Export statistics to Excel
         */
        window.exportStatistics = function() {
            // This would require a server-side export function
            alert('Export functionality will be implemented');
        };
        
        // Initialize charts when DOM is ready
        $(document).ready(function() {
            console.log('Document ready, initializing charts...');
            initCharts();
        });
    }
    
    // Start initialization
    console.log('Visitor Statistics script loaded, document readyState:', document.readyState);
    if (document.readyState === 'loading') {
        console.log('Document still loading, waiting for DOMContentLoaded...');
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOMContentLoaded fired, initializing visitor stats...');
            initVisitorStats();
        });
    } else {
        console.log('Document already loaded, initializing visitor stats immediately...');
        initVisitorStats();
    }
})();

