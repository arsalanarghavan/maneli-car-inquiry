<?php
/**
 * گزارشات پیشرفته و سیستم نظارتی
 * 
 * @package Maneli_Car_Inquiry
 */

defined('ABSPATH') || exit;

class Maneli_Reports_Dashboard {
    
    /**
     * دریافت آمار کلی سیستم
     *
     * @param string $start_date تاریخ شروع (Y-m-d)
     * @param string $end_date تاریخ پایان (Y-m-d)
     * @param int $expert_id شناسه کارشناس (اختیاری)
     * @return array
     */
    public static function get_overall_statistics($start_date = null, $end_date = null, $expert_id = null) {
        global $wpdb;
        
        // تنظیم تاریخ پیش‌فرض (30 روز گذشته)
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $date_query = "AND post_date >= %s AND post_date <= %s";
        $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        
        // فیلتر بر اساس کارشناس (assigned_expert_id)
        $expert_join = '';
        $expert_where = '';
        if ($expert_id) {
            $expert_join = "INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id AND pm_expert.meta_key = 'assigned_expert_id'";
            $expert_where = "AND pm_expert.meta_value = %d";
            $params[] = $expert_id;
        }
        
        // آمار استعلام‌های نقدی
        $cash_params = $params;
        $cash_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                pm.meta_value as status,
                COUNT(*) as count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'cash_inquiry_status'
            $expert_join
            WHERE p.post_type = 'cash_inquiry'
            AND p.post_status = 'publish'
            $date_query
            $expert_where
            GROUP BY pm.meta_value",
            $cash_params
        ));
        
        // آمار استعلام‌های اقساطی (با tracking_status)
        $installment_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        if ($expert_id) {
            $installment_params[] = $expert_id;
        }
        $installment_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                pm.meta_value as status,
                COUNT(*) as count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'tracking_status'
            $expert_join
            WHERE p.post_type = 'inquiry'
            AND p.post_status = 'publish'
            $date_query
            $expert_where
            GROUP BY pm.meta_value",
            $installment_params
        ));
        
        // پردازش آمار با وضعیت‌های جدید
        $statistics = [
            'total_inquiries' => 0,
            'cash_inquiries' => 0,
            'installment_inquiries' => 0,
            'new' => 0,                      // جدید (منتظر ارجاع)
            'referred' => 0,                 // ارجاع داده شده
            'in_progress' => 0,              // در حال پیگیری
            'completed' => 0,                // تکمیل شده
            'rejected' => 0,                 // رد شده/لغو شده
            'followup_scheduled' => 0,       // پیگیری بعدی
            'new_today' => 0,
            'revenue' => 0,
        ];
        
        foreach ($cash_stats as $stat) {
            $statistics['cash_inquiries'] += $stat->count;
            $statistics['total_inquiries'] += $stat->count;
            
            switch ($stat->status) {
                case 'new':
                    $statistics['new'] += $stat->count;
                    break;
                case 'referred':
                    $statistics['referred'] += $stat->count;
                    break;
                case 'in_progress':
                    $statistics['in_progress'] += $stat->count;
                    break;
                case 'follow_up_scheduled':
                    $statistics['followup_scheduled'] += $stat->count;
                    break;
                case 'completed':
                case 'approved':
                    $statistics['completed'] += $stat->count;
                    break;
                case 'rejected':
                    $statistics['rejected'] += $stat->count;
                    break;
            }
        }
        
        foreach ($installment_stats as $stat) {
            $statistics['installment_inquiries'] += $stat->count;
            $statistics['total_inquiries'] += $stat->count;
            
            switch ($stat->status) {
                case 'new':
                    $statistics['new'] += $stat->count;
                    break;
                case 'referred':
                    $statistics['referred'] += $stat->count;
                    break;
                case 'in_progress':
                    $statistics['in_progress'] += $stat->count;
                    break;
                case 'follow_up_scheduled':
                    $statistics['followup_scheduled'] += $stat->count;
                    break;
                case 'completed':
                    $statistics['completed'] += $stat->count;
                    break;
                case 'rejected':
                case 'cancelled':
                    $statistics['rejected'] += $stat->count;
                    break;
            }
        }
        
        // استعلام‌های امروز
        $today_params = [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59'];
        if ($expert_id) {
            $today_params[] = $expert_id;
        }
        
        $statistics['new_today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
            $expert_join
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND p.post_date >= %s AND p.post_date <= %s
            $expert_where",
            $today_params
        ));
        
        // پیگیری‌های آتی
        $followup_params = [date('Y-m-d H:i:s')];
        if ($expert_id) {
            $followup_params[] = $expert_id;
        }
        
        $statistics['next_followup'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'followup_date'
            $expert_join
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND pm.meta_value >= %s
            $expert_where",
            $followup_params
        ));
        
        // محاسبه درآمد
        $revenue_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        if ($expert_id) {
            $revenue_params[] = $expert_id;
        }
        
        $statistics['revenue'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'payment_amount'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'payment_status'
            $expert_join
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND pm2.meta_value = 'completed'
            AND p.post_date >= %s AND p.post_date <= %s
            $expert_where",
            $revenue_params
        ));
        
        $statistics['revenue'] = floatval($statistics['revenue']);
        
        return $statistics;
    }
    
    /**
     * دریافت آمار تمام کارشناسان
     *
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public static function get_experts_statistics($start_date = null, $end_date = null) {
        // دریافت تمام کارشناسان
        $experts = get_users([
            'role__in' => ['maneli_expert', 'maneli_admin', 'administrator'],
            'orderby' => 'display_name',
            'order' => 'ASC',
        ]);
        $expert_role_users = array_filter($experts, function ($user) {
            return in_array('maneli_expert', (array) $user->roles, true);
        });
        
        $experts_stats = [];
        
        foreach ($experts as $expert) {
            $stats = self::get_overall_statistics($start_date, $end_date, $expert->ID);
            $stats['expert_id'] = $expert->ID;
            $stats['expert_name'] = $expert->display_name;
            $stats['expert_email'] = $expert->user_email;
            
            // تعداد مشتریان هر کارشناس
            $stats['total_customers'] = self::get_expert_customers_count($expert->ID);
            $stats['new_customers'] = self::get_expert_new_customers_count($expert->ID, $start_date, $end_date);
            
            $experts_stats[] = $stats;
        }
        
        return $experts_stats;
    }
    
    /**
     * تعداد کل مشتریان یک کارشناس
     *
     * @param int $expert_id
     * @return int
     */
    public static function get_expert_customers_count($expert_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.post_author)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id AND pm_expert.meta_key = 'assigned_expert_id'
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND pm_expert.meta_value = %d
            AND p.post_author > 0",
            $expert_id
        ));
    }
    
    /**
     * تعداد مشتریان جدید یک کارشناس در بازه زمانی
     *
     * @param int $expert_id
     * @param string $start_date
     * @param string $end_date
     * @return int
     */
    public static function get_expert_new_customers_count($expert_id, $start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.post_author)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id AND pm_expert.meta_key = 'assigned_expert_id'
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND pm_expert.meta_value = %d
            AND p.post_date >= %s AND p.post_date <= %s
            AND p.post_author > 0",
            $expert_id,
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
    }
    
    /**
     * آمار روزانه برای نمودار
     *
     * @param string $start_date
     * @param string $end_date
     * @param int $expert_id
     * @return array
     */
    public static function get_daily_statistics($start_date = null, $end_date = null, $expert_id = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $expert_join = '';
        $expert_where = '';
        $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        
        if ($expert_id) {
            $expert_join = "INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id AND pm_expert.meta_key = 'assigned_expert_id'";
            $expert_where = "AND pm_expert.meta_value = %d";
            $params[] = $expert_id;
        }
        
        $daily_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(p.post_date) as date,
                p.post_type,
                COUNT(*) as count
            FROM {$wpdb->posts} p
            $expert_join
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND p.post_date >= %s AND p.post_date <= %s
            $expert_where
            GROUP BY DATE(p.post_date), p.post_type
            ORDER BY date ASC",
            $params
        ));
        
        // سازماندهی داده‌ها
        $result = [];
        foreach ($daily_data as $row) {
            $date = $row->date;
            if (!isset($result[$date])) {
                $result[$date] = [
                    'date' => $date,
                    'cash' => 0,
                    'installment' => 0,
                    'total' => 0,
                ];
            }
            
            // Ensure post_type is string for comparison
            $post_type = strval($row->post_type);
            if ($post_type === 'cash_inquiry') {
                $result[$date]['cash'] = intval($row->count);
            } else if ($post_type === 'inquiry') {
                $result[$date]['installment'] = intval($row->count);
            }
            // Recalculate total to ensure accuracy
            $result[$date]['total'] = intval($result[$date]['cash']) + intval($result[$date]['installment']);
        }
        
        // پر کردن تمام روزها در بازه (حتی اگر استعلامی نداشته باشند)
        $current_date = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        while ($current_date <= $end_timestamp) {
            $date_key = date('Y-m-d', $current_date);
            if (!isset($result[$date_key])) {
                $result[$date_key] = [
                    'date' => $date_key,
                    'cash' => 0,
                    'installment' => 0,
                    'total' => 0,
                ];
            }
            $current_date = strtotime('+1 day', $current_date);
        }
        
        // مرتب کردن بر اساس تاریخ
        ksort($result);
        
        return array_values($result);
    }
    
    /**
     * آمار محصولات پرطرفدار
     *
     * @param string $start_date
     * @param string $end_date
     * @param int $expert_id
     * @param int $limit
     * @return array
     */
    public static function get_popular_products($start_date = null, $end_date = null, $expert_id = null, $limit = 10) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $expert_join = '';
        $expert_where = '';
        $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        
        if ($expert_id) {
            $expert_join = "INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id AND pm_expert.meta_key = 'assigned_expert_id'";
            $expert_where = "AND pm_expert.meta_value = %d";
            $params[] = $expert_id;
        }
        
        $params[] = $limit;
        
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                pm.meta_value as product_id,
                COUNT(*) as inquiry_count,
                prod.post_title as product_name
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'product_id'
            LEFT JOIN {$wpdb->posts} prod ON pm.meta_value = prod.ID
            $expert_join
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND p.post_date >= %s AND p.post_date <= %s
            $expert_where
            AND pm.meta_value IS NOT NULL
            AND pm.meta_value != ''
            GROUP BY pm.meta_value
            ORDER BY inquiry_count DESC
            LIMIT %d",
            $params
        ));
        
        // Format results
        $formatted_products = [];
        foreach ($products as $product) {
            $formatted_products[] = [
                'id' => $product->product_id,
                'name' => $product->product_name ?: esc_html__('N/A', 'maneli-car-inquiry'),
                'count' => (int)$product->inquiry_count
            ];
        }
        
        return $formatted_products;
    }
    
    /**
     * آمار عملکرد ماهانه
     *
     * @param int $months تعداد ماه گذشته
     * @param int $expert_id
     * @return array
     */
    public static function get_monthly_performance($months = 6, $expert_id = null) {
        global $wpdb;
        
        $expert_join = '';
        $expert_where = '';
        $params = [];
        
        if ($expert_id) {
            $expert_join = "INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id AND pm_expert.meta_key = 'assigned_expert_id'";
            $expert_where = "AND pm_expert.meta_value = %d";
            $params[] = $expert_id;
        }
        
        $monthly_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(p.post_date, '%Y-%m') as month,
                p.post_type,
                COUNT(*) as count
            FROM {$wpdb->posts} p
            $expert_join
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL $months MONTH)
            $expert_where
            GROUP BY month, p.post_type
            ORDER BY month ASC",
            $params
        ));
        
        // سازماندهی داده‌ها
        $result = [];
        foreach ($monthly_data as $row) {
            $month = $row->month;
            if (!isset($result[$month])) {
                $result[$month] = [
                    'month' => $month,
                    'month_persian' => self::convert_to_persian_month($month),
                    'cash' => 0,
                    'installment' => 0,
                    'total' => 0,
                ];
            }
            
            if ($row->post_type === 'cash_inquiry') {
                $result[$month]['cash'] = intval($row->count);
            } else {
                $result[$month]['installment'] = intval($row->count);
            }
            $result[$month]['total'] += intval($row->count);
        }
        
        return array_values($result);
    }
    
    /**
     * تبدیل تاریخ میلادی به ماه فارسی
     *
     * @param string $date
     * @return string
     */
    private static function convert_to_persian_month($date) {
        $months = [
            '01' => esc_html__('Farvardin', 'maneli-car-inquiry'),
            '02' => esc_html__('Ordibehesht', 'maneli-car-inquiry'),
            '03' => esc_html__('Khordad', 'maneli-car-inquiry'),
            '04' => esc_html__('Tir', 'maneli-car-inquiry'),
            '05' => esc_html__('Mordad', 'maneli-car-inquiry'),
            '06' => esc_html__('Shahrivar', 'maneli-car-inquiry'),
            '07' => esc_html__('Mehr', 'maneli-car-inquiry'),
            '08' => esc_html__('Aban', 'maneli-car-inquiry'),
            '09' => esc_html__('Azar', 'maneli-car-inquiry'),
            '10' => esc_html__('Dey', 'maneli-car-inquiry'),
            '11' => esc_html__('Bahman', 'maneli-car-inquiry'),
            '12' => esc_html__('Esfand', 'maneli-car-inquiry')
        ];
        
        list($year, $month) = explode('-', $date);
        
        // تقریبی برای نمایش
        return $months[$month] . ' ' . ($year - 621);
    }
    
    /**
     * جزئیات استعلام‌ها برای جدول
     *
     * @param array $args
     * @return array
     */
    public static function get_inquiries_details($args = []) {
        $defaults = [
            'start_date' => date('Y-m-d', strtotime('-30 days')),
            'end_date' => date('Y-m-d'),
            'expert_id' => null,
            'status' => null,
            'type' => 'all', // all, cash, installment
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        
        $args = wp_parse_args($args, $defaults);
        global $wpdb;
        
        // ساخت کوئری
        $where = ["p.post_status = 'publish'"];
        $params = [];
        
        // نوع استعلام
        if ($args['type'] === 'cash') {
            $where[] = "p.post_type = 'cash_inquiry'";
        } elseif ($args['type'] === 'installment') {
            $where[] = "p.post_type = 'inquiry'";
        } else {
            $where[] = "p.post_type IN ('cash_inquiry', 'inquiry')";
        }
        
        // بازه زمانی
        $where[] = "p.post_date >= %s";
        $params[] = $args['start_date'] . ' 00:00:00';
        $where[] = "p.post_date <= %s";
        $params[] = $args['end_date'] . ' 23:59:59';
        
        // کارشناس
        if ($args['expert_id']) {
            $where[] = "p.post_author = %d";
            $params[] = $args['expert_id'];
        }
        
        // وضعیت
        if ($args['status']) {
            $where[] = "pm_status.meta_value = %s";
            $params[] = $args['status'];
        }
        
        $where_sql = implode(' AND ', $where);
        
        // ترتیب
        $orderby_map = [
            'date' => 'p.post_date',
            'customer' => 'pm_name.meta_value',
            'status' => 'pm_status.meta_value',
        ];
        $orderby = isset($orderby_map[$args['orderby']]) ? $orderby_map[$args['orderby']] : 'p.post_date';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // کوئری اصلی
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        $inquiries = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                p.ID,
                p.post_date,
                p.post_type,
                p.post_author,
                pm_status.meta_value as status,
                pm_name.meta_value as customer_name,
                pm_phone.meta_value as customer_phone,
                pm_national.meta_value as customer_national_id,
                pm_product.meta_value as product_id,
                prod.post_title as product_name,
                pm_amount.meta_value as amount
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'inquiry_status'
            LEFT JOIN {$wpdb->postmeta} pm_name ON p.ID = pm_name.post_id AND pm_name.meta_key = 'customer_name'
            LEFT JOIN {$wpdb->postmeta} pm_phone ON p.ID = pm_phone.post_id AND pm_phone.meta_key = 'customer_phone'
            LEFT JOIN {$wpdb->postmeta} pm_national ON p.ID = pm_national.post_id AND pm_national.meta_key = 'customer_national_id'
            LEFT JOIN {$wpdb->postmeta} pm_product ON p.ID = pm_product.post_id AND pm_product.meta_key = 'selected_product'
            LEFT JOIN {$wpdb->postmeta} pm_amount ON p.ID = pm_amount.post_id AND pm_amount.meta_key = 'payment_amount'
            LEFT JOIN {$wpdb->posts} prod ON pm_product.meta_value = prod.ID
            WHERE $where_sql
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d",
            $params
        ));
        
        // تعداد کل
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'inquiry_status'
            WHERE $where_sql",
            array_slice($params, 0, -2)
        ));
        
        return [
            'inquiries' => $inquiries,
            'total' => intval($total),
            'pages' => ceil($total / $args['limit']),
        ];
    }
    
    /**
     * صادرات داده‌ها به CSV
     *
     * @param array $args
     * @return void
     */
    public static function export_to_csv($args = []) {
        $data = self::get_inquiries_details($args);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=inquiries-report-' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // BOM برای UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // CSV headers
        fputcsv($output, [
            esc_html__('ID', 'maneli-car-inquiry'),
            esc_html__('Date', 'maneli-car-inquiry'),
            esc_html__('Type', 'maneli-car-inquiry'),
            esc_html__('Customer Name', 'maneli-car-inquiry'),
            esc_html__('Phone', 'maneli-car-inquiry'),
            esc_html__('National ID', 'maneli-car-inquiry'),
            esc_html__('Product', 'maneli-car-inquiry'),
            esc_html__('Status', 'maneli-car-inquiry'),
            esc_html__('Amount', 'maneli-car-inquiry'),
            esc_html__('Expert', 'maneli-car-inquiry')
        ]);
        
        // داده‌ها
        foreach ($data['inquiries'] as $inquiry) {
            $expert = get_userdata($inquiry->post_author);
            
            fputcsv($output, [
                $inquiry->ID,
                $inquiry->post_date,
                $inquiry->post_type === 'cash_inquiry' ? esc_html__('Cash', 'maneli-car-inquiry') : esc_html__('Installment', 'maneli-car-inquiry'),
                $inquiry->customer_name,
                $inquiry->customer_phone,
                $inquiry->customer_national_id,
                $inquiry->product_name,
                self::get_status_label($inquiry->status),
                number_format($inquiry->amount) . ' ' . esc_html__('Toman', 'maneli-car-inquiry'),
                $expert ? $expert->display_name : '-'
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * برچسب وضعیت به فارسی
     *
     * @param string $status
     * @return string
     */
    private static function get_status_label($status) {
        $labels = [
            'pending' => esc_html__('Pending Review', 'maneli-car-inquiry'),
            'approved' => esc_html__('Approved and Referred', 'maneli-car-inquiry'),
            'rejected' => esc_html__('Rejected', 'maneli-car-inquiry'),
            'following' => esc_html__('Follow-up in Progress', 'maneli-car-inquiry'),
        ];
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
    
    /**
     * محاسبه سود کارشناس
     * @param int $expert_id
     * @param string $start_date
     * @param string $end_date
     * @return float
     */
    public static function get_expert_profit($expert_id, $start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        // سود = درآمد - هزینه‌ها (می‌تواند بر اساس درصد یا مقدار ثابت باشد)
        $revenue = self::get_overall_statistics($start_date, $end_date, $expert_id);
        $revenue_amount = floatval($revenue['revenue'] ?? 0);
        
        // محاسبه هزینه‌ها (می‌تواند یک درصد ثابت یا منطق پیچیده‌تری باشد)
        // به عنوان مثال: 30% از درآمد را به عنوان هزینه در نظر می‌گیریم
        $cost_percentage = 0.30; // می‌تواند از تنظیمات خوانده شود
        $costs = $revenue_amount * $cost_percentage;
        
        return $revenue_amount - $costs;
    }
    
    /**
     * دریافت آمار برای بازه زمانی خاص
     * @param string $period daily, weekly, monthly, yearly, custom, all
     * @param string $custom_start_date
     * @param string $custom_end_date
     * @return array با start_date و end_date
     */
    public static function get_period_dates($period = 'monthly', $custom_start_date = null, $custom_end_date = null) {
        $today = date('Y-m-d');
        
        switch ($period) {
            case 'today':
                return [
                    'start_date' => $today,
                    'end_date' => $today,
                    'label' => esc_html__('Today', 'maneli-car-inquiry')
                ];
                
            case 'yesterday':
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                return [
                    'start_date' => $yesterday,
                    'end_date' => $yesterday,
                    'label' => esc_html__('Yesterday', 'maneli-car-inquiry')
                ];
                
            case 'weekly':
                return [
                    'start_date' => date('Y-m-d', strtotime('-7 days')),
                    'end_date' => $today,
                    'label' => 'هفته گذشته'
                ];
                
            case 'monthly':
                return [
                    'start_date' => date('Y-m-d', strtotime('-30 days')),
                    'end_date' => $today,
                    'label' => 'ماه گذشته'
                ];
                
            case 'yearly':
                return [
                    'start_date' => date('Y-m-d', strtotime('-365 days')),
                    'end_date' => $today,
                    'label' => 'سال گذشته'
                ];
                
            case 'all':
                // از اولین استعلام تا امروز
                global $wpdb;
                $first_date = $wpdb->get_var(
                    "SELECT MIN(post_date) 
                    FROM {$wpdb->posts} 
                    WHERE post_type IN ('cash_inquiry', 'inquiry') 
                    AND post_status = 'publish'"
                );
                
                if ($first_date) {
                    return [
                        'start_date' => date('Y-m-d', strtotime($first_date)),
                        'end_date' => $today,
                        'label' => 'کل'
                    ];
                }
                // Fallback
                return [
                    'start_date' => date('Y-m-d', strtotime('-1 year')),
                    'end_date' => $today,
                    'label' => 'کل'
                ];
                
            case 'custom':
                return [
                    'start_date' => $custom_start_date ?: date('Y-m-d', strtotime('-30 days')),
                    'end_date' => $custom_end_date ?: $today,
                    'label' => 'بازه دلخواه'
                ];
                
            default:
                return [
                    'start_date' => date('Y-m-d', strtotime('-30 days')),
                    'end_date' => $today,
                    'label' => 'ماه گذشته'
                ];
        }
    }
    
    /**
     * دریافت آمار کامل کسب و کار
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public static function get_business_statistics($start_date = null, $end_date = null) {
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        global $wpdb;
        
        // آمار کلی
        $overall = self::get_overall_statistics($start_date, $end_date);
        
        // آمار کارشناسان
        $experts = get_users([
            'role__in' => ['maneli_expert', 'maneli_admin', 'administrator'],
            'orderby' => 'display_name',
            'order' => 'ASC',
        ]);
        $expert_role_users = array_filter($experts, static function ($user) {
            return in_array('maneli_expert', (array) $user->roles, true);
        });
        
        $experts_detailed = [];
        foreach ($experts as $expert) {
            $expert_stats = self::get_overall_statistics($start_date, $end_date, $expert->ID);
            $expert_profit = self::get_expert_profit($expert->ID, $start_date, $end_date);
            
            // محاسبه نرخ موفقیت
            $success_rate = 0;
            if ($expert_stats['total_inquiries'] > 0) {
                $success_rate = round(($expert_stats['completed'] / $expert_stats['total_inquiries']) * 100, 2);
            }
            
            $experts_detailed[] = [
                'id' => $expert->ID,
                'name' => $expert->display_name,
                'email' => $expert->user_email,
                'total_inquiries' => $expert_stats['total_inquiries'],
                'cash_inquiries' => $expert_stats['cash_inquiries'],
                'installment_inquiries' => $expert_stats['installment_inquiries'],
                'completed' => $expert_stats['completed'],
                'rejected' => $expert_stats['rejected'],
                'pending' => $expert_stats['new'] + $expert_stats['in_progress'],
                'revenue' => floatval($expert_stats['revenue']),
                'profit' => $expert_profit,
                'success_rate' => $success_rate,
                'total_customers' => self::get_expert_customers_count($expert->ID),
                'new_customers' => self::get_expert_new_customers_count($expert->ID, $start_date, $end_date),
            ];
        }
        
        // محاسبه آمار محصولات
        $popular_products = self::get_popular_products($start_date, $end_date, null, 10);
        
        // محاسبه آمار روزانه
        $daily_stats = self::get_daily_statistics($start_date, $end_date);
        
        // محاسبه آمار ماهانه
        $monthly_stats = self::get_monthly_performance(6);
        
        // محاسبه کل سود کسب و کار
        $total_profit = array_sum(array_column($experts_detailed, 'profit'));
        
        return [
            'overall' => $overall,
            'experts' => $experts_detailed,
            'popular_products' => $popular_products,
            'daily' => $daily_stats,
            'monthly' => $monthly_stats,
            'total_profit' => $total_profit,
            'total_experts' => count($expert_role_users),
            'total_customers' => count_users()['avail_roles']['customer'] ?? 0,
        ];
    }
    
    /**
     * دریافت آمار پیشرفته یک کارشناس
     * @param int $expert_id
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public static function get_expert_detailed_statistics($expert_id, $start_date = null, $end_date = null) {
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $stats = self::get_overall_statistics($start_date, $end_date, $expert_id);
        $profit = self::get_expert_profit($expert_id, $start_date, $end_date);
        
        // محاسبه نرخ موفقیت
        $success_rate = 0;
        if ($stats['total_inquiries'] > 0) {
            $success_rate = round(($stats['completed'] / $stats['total_inquiries']) * 100, 2);
        }
        
        // محاسبه متوسط زمان پاسخ
        $avg_response_time = self::calculate_avg_response_time($expert_id, $start_date, $end_date);
        
        // آمار روزانه کارشناس
        $daily = self::get_daily_statistics($start_date, $end_date, $expert_id);
        
        return [
            'basic' => $stats,
            'profit' => $profit,
            'success_rate' => $success_rate,
            'total_customers' => self::get_expert_customers_count($expert_id),
            'new_customers' => self::get_expert_new_customers_count($expert_id, $start_date, $end_date),
            'avg_response_time' => $avg_response_time,
            'daily' => $daily,
        ];
    }
    
    /**
     * محاسبه متوسط زمان پاسخ کارشناس
     * @param int $expert_id
     * @param string $start_date
     * @param string $end_date
     * @return float
     */
    private static function calculate_avg_response_time($expert_id, $start_date, $end_date) {
        global $wpdb;
        
        // این یک محاسبه ساده است - می‌تواند پیچیده‌تر باشد
        // زمان بین ایجاد استعلام تا اولین بروزرسانی توسط کارشناس
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                p.ID,
                p.post_date as inquiry_date,
                MIN(pm_date.meta_value) as first_update_date
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id 
                AND pm_expert.meta_key = 'assigned_expert_id'
                AND pm_expert.meta_value = %d
            LEFT JOIN {$wpdb->postmeta} pm_date ON p.ID = pm_date.post_id 
                AND pm_date.meta_key LIKE 'expert_update_date_%%'
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND p.post_date >= %s AND p.post_date <= %s
            GROUP BY p.ID",
            $expert_id,
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
        
        if (empty($results)) {
            return 0;
        }
        
        $total_hours = 0;
        $count = 0;
        
        foreach ($results as $result) {
            if ($result->first_update_date) {
                $inquiry_time = strtotime($result->inquiry_date);
                $update_time = strtotime($result->first_update_date);
                $hours = ($update_time - $inquiry_time) / 3600;
                if ($hours > 0 && $hours < 8760) { // معقول بودن (کمتر از یک سال)
                    $total_hours += $hours;
                    $count++;
                }
            }
        }
        
        return $count > 0 ? round($total_hours / $count, 2) : 0;
    }
    
    /**
     * محاسبه رشد نسبت به دوره قبل
     * @param string $start_date
     * @param string $end_date
     * @param int $expert_id
     * @return array
     */
    public static function get_growth_statistics($start_date, $end_date, $expert_id = null) {
        // محاسبه دوره قبل
        $period_days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
        $prev_end = date('Y-m-d', strtotime($start_date . ' -1 day'));
        $prev_start = date('Y-m-d', strtotime($prev_end . ' -' . $period_days . ' days'));
        
        $current = self::get_overall_statistics($start_date, $end_date, $expert_id);
        $previous = self::get_overall_statistics($prev_start, $prev_end, $expert_id);
        
        $calculate_growth = function($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? 100 : 0;
            }
            return round((($current - $previous) / $previous) * 100, 1);
        };
        
        return [
            'current' => $current,
            'previous' => $previous,
            'total_inquiries_growth' => $calculate_growth($current['total_inquiries'], $previous['total_inquiries']),
            'revenue_growth' => $calculate_growth($current['revenue'], $previous['revenue']),
            'completed_growth' => $calculate_growth($current['completed'], $previous['completed']),
            'cash_inquiries_growth' => $calculate_growth($current['cash_inquiries'], $previous['cash_inquiries']),
            'installment_inquiries_growth' => $calculate_growth($current['installment_inquiries'], $previous['installment_inquiries']),
        ];
    }
    
    /**
     * دریافت کارشناسان برتر
     * @param string $start_date
     * @param string $end_date
     * @param int $limit
     * @param string $sort_by profit, revenue, success_rate, total_inquiries
     * @return array
     */
    public static function get_top_experts($start_date = null, $end_date = null, $limit = 5, $sort_by = 'profit') {
        $business_stats = self::get_business_statistics($start_date, $end_date);
        $experts = $business_stats['experts'];
        
        $sort_keys = [
            'profit' => 'profit',
            'revenue' => 'revenue',
            'success_rate' => 'success_rate',
            'total_inquiries' => 'total_inquiries',
            'completed' => 'completed'
        ];
        
        $key = $sort_keys[$sort_by] ?? 'profit';
        
        usort($experts, function($a, $b) use ($key) {
            return $b[$key] <=> $a[$key];
        });
        
        return array_slice($experts, 0, $limit);
    }
    
    /**
     * دریافت مشتریان VIP (بیشترین استعلام)
     * @param string $start_date
     * @param string $end_date
     * @param int $limit
     * @return array
     */
    public static function get_vip_customers($start_date = null, $end_date = null, $limit = 10) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                pm.meta_value as national_id,
                COUNT(*) as inquiry_count,
                SUM(CAST(pm_amount.meta_value AS DECIMAL(10,2))) as total_amount
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'customer_national_id'
            LEFT JOIN {$wpdb->postmeta} pm_amount ON p.ID = pm_amount.post_id AND pm_amount.meta_key = 'payment_amount'
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND p.post_date >= %s AND p.post_date <= %s
            AND pm.meta_value != '' AND pm.meta_value IS NOT NULL
            GROUP BY pm.meta_value
            ORDER BY inquiry_count DESC, total_amount DESC
            LIMIT %d",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59',
            $limit
        ));
        
        return $results;
    }
    
    /**
     * دریافت توزیع وضعیت‌ها
     * @param string $start_date
     * @param string $end_date
     * @param int $expert_id
     * @return array
     */
    public static function get_status_distribution($start_date = null, $end_date = null, $expert_id = null) {
        $stats = self::get_overall_statistics($start_date, $end_date, $expert_id);
        
        return [
            ['status' => 'تکمیل شده', 'count' => $stats['completed'], 'color' => '#10b981'],
            ['status' => 'در انتظار', 'count' => $stats['new'] + $stats['in_progress'], 'color' => '#f59e0b'],
            ['status' => 'رد شده', 'count' => $stats['rejected'], 'color' => '#ef4444'],
            ['status' => 'ارجاع داده شده', 'count' => $stats['referred'], 'color' => '#3b82f6'],
            ['status' => 'پیگیری برنامه‌ریزی', 'count' => $stats['followup_scheduled'], 'color' => '#8b5cf6'],
        ];
    }
    
    /**
     * دریافت آمار کلی با اعمال فیلترهای پیشرفته
     *
     * @param string $start_date تاریخ شروع
     * @param string $end_date تاریخ پایان
     * @param int $expert_id شناسه کارشناس
     * @param string $filter_status فیلتر وضعیت
     * @param string $filter_type فیلتر نوع (all, cash, installment)
     * @param int $filter_product فیلتر محصول
     * @return array
     */
    public static function get_overall_statistics_with_filters($start_date = null, $end_date = null, $expert_id = null, $filter_status = '', $filter_type = 'all', $filter_product = null) {
        global $wpdb;
        
        // تنظیم تاریخ پیش‌فرض
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        // ساخت WHERE clause
        $where = ["p.post_status = 'publish'"];
        
        // بازه زمانی
        $where[] = "p.post_date >= %s";
        $where[] = "p.post_date <= %s";
        
        // فیلتر نوع استعلام
        if ($filter_type === 'cash') {
            $where[] = "p.post_type = 'cash_inquiry'";
        } elseif ($filter_type === 'installment') {
            $where[] = "p.post_type = 'inquiry'";
        } else {
            $where[] = "p.post_type IN ('cash_inquiry', 'inquiry')";
        }
        
        // فیلتر کارشناس
        $expert_join = '';
        $expert_where = '';
        if ($expert_id) {
            $expert_join = "INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id AND pm_expert.meta_key = 'assigned_expert_id'";
            $expert_where = "AND pm_expert.meta_value = %d";
        }
        
        // فیلتر محصول
        $product_join = '';
        $product_where = '';
        if ($filter_product) {
            $product_join = "INNER JOIN {$wpdb->postmeta} pm_product ON p.ID = pm_product.post_id AND pm_product.meta_key = 'product_id'";
            $product_where = "AND pm_product.meta_value = %d";
        }
        
        $where_sql = implode(' AND ', $where);
        
        // ساخت JOIN برای status
        $status_join_cash = "LEFT JOIN {$wpdb->postmeta} pm_status_cash ON p.ID = pm_status_cash.post_id AND pm_status_cash.meta_key = 'cash_inquiry_status'";
        $status_join_installment = "LEFT JOIN {$wpdb->postmeta} pm_status_inst ON p.ID = pm_status_inst.post_id AND pm_status_inst.meta_key = 'tracking_status'";
        
        // فیلتر وضعیت
        $status_where = '';
        if ($filter_status) {
            $status_where = "AND pm_status_cash.meta_value = %s";
        }
        
        // آمار استعلام‌های نقدی
        $cash_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        if ($expert_id) {
            $cash_params[] = $expert_id;
        }
        if ($filter_product) {
            $cash_params[] = $filter_product;
        }
        if ($filter_status) {
            $cash_params[] = $filter_status;
        }
        
        $cash_query = "SELECT 
            COALESCE(pm_status_cash.meta_value, 'new') as status,
            COUNT(*) as count
        FROM {$wpdb->posts} p
        $status_join_cash
        $expert_join
        $product_join
        WHERE $where_sql
        AND p.post_type = 'cash_inquiry'
        $expert_where
        $product_where
        $status_where
        GROUP BY pm_status_cash.meta_value";
        
        $cash_stats = $wpdb->get_results($wpdb->prepare($cash_query, $cash_params));
        
        // آمار استعلام‌های اقساطی
        $status_where_inst = '';
        if ($filter_status) {
            $status_where_inst = "AND pm_status_inst.meta_value = %s";
        }
        
        $installment_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        if ($expert_id) {
            $installment_params[] = $expert_id;
        }
        if ($filter_product) {
            $installment_params[] = $filter_product;
        }
        if ($filter_status) {
            $installment_params[] = $filter_status;
        }
        
        $installment_query = "SELECT 
            COALESCE(pm_status_inst.meta_value, 'new') as status,
            COUNT(*) as count
        FROM {$wpdb->posts} p
        $status_join_installment
        $expert_join
        $product_join
        WHERE $where_sql
        AND p.post_type = 'inquiry'
        $expert_where
        $product_where
        $status_where_inst
        GROUP BY pm_status_inst.meta_value";
        
        $installment_stats = $wpdb->get_results($wpdb->prepare($installment_query, $installment_params));
        
        // پردازش آمار
        $statistics = [
            'total_inquiries' => 0,
            'cash_inquiries' => 0,
            'installment_inquiries' => 0,
            'new' => 0,
            'referred' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'rejected' => 0,
            'followup_scheduled' => 0,
            'new_today' => 0,
            'revenue' => 0,
        ];
        
        foreach ($cash_stats as $stat) {
            $statistics['cash_inquiries'] += intval($stat->count);
            $statistics['total_inquiries'] += intval($stat->count);
            
            switch ($stat->status) {
                case 'new':
                    $statistics['new'] += intval($stat->count);
                    break;
                case 'referred':
                    $statistics['referred'] += intval($stat->count);
                    break;
                case 'in_progress':
                    $statistics['in_progress'] += intval($stat->count);
                    break;
                case 'follow_up_scheduled':
                    $statistics['followup_scheduled'] += intval($stat->count);
                    break;
                case 'completed':
                case 'approved':
                    $statistics['completed'] += intval($stat->count);
                    break;
                case 'rejected':
                    $statistics['rejected'] += intval($stat->count);
                    break;
            }
        }
        
        foreach ($installment_stats as $stat) {
            $statistics['installment_inquiries'] += intval($stat->count);
            $statistics['total_inquiries'] += intval($stat->count);
            
            switch ($stat->status) {
                case 'new':
                    $statistics['new'] += intval($stat->count);
                    break;
                case 'referred':
                    $statistics['referred'] += intval($stat->count);
                    break;
                case 'in_progress':
                    $statistics['in_progress'] += intval($stat->count);
                    break;
                case 'follow_up_scheduled':
                    $statistics['followup_scheduled'] += intval($stat->count);
                    break;
                case 'completed':
                    $statistics['completed'] += intval($stat->count);
                    break;
                case 'rejected':
                case 'cancelled':
                    $statistics['rejected'] += intval($stat->count);
                    break;
            }
        }
        
        // استعلام‌های امروز (با فیلترها)
        $today_params = [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59'];
        if ($filter_type === 'cash') {
            $today_where = "p.post_type = 'cash_inquiry'";
        } elseif ($filter_type === 'installment') {
            $today_where = "p.post_type = 'inquiry'";
        } else {
            $today_where = "p.post_type IN ('cash_inquiry', 'inquiry')";
        }
        
        if ($expert_id) {
            $today_params[] = $expert_id;
        }
        if ($filter_product) {
            $today_params[] = $filter_product;
        }
        
        $today_query = "SELECT COUNT(*) FROM {$wpdb->posts} p";
        if ($expert_id) {
            $today_query .= " INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id AND pm_expert.meta_key = 'assigned_expert_id' AND pm_expert.meta_value = %d";
        }
        if ($filter_product) {
            $today_query .= " INNER JOIN {$wpdb->postmeta} pm_product ON p.ID = pm_product.post_id AND pm_product.meta_key = 'product_id' AND pm_product.meta_value = %d";
        }
        $today_query .= " WHERE p.post_status = 'publish' AND $today_where AND p.post_date >= %s AND p.post_date <= %s";
        
        $statistics['new_today'] = intval($wpdb->get_var($wpdb->prepare($today_query, $today_params)));
        
        return $statistics;
    }
    
    /**
     * دریافت استعلامات نیازمند توجه (عقب‌افتاده، بدون کارشناس)
     * @return array
     */
    public static function get_attention_required_inquiries() {
        global $wpdb;
        $today = date('Y-m-d');
        
        // استعلامات عقب‌افتاده
        $overdue = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_type, p.post_date
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'followup_date'
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND pm.meta_value < %s
            AND pm.meta_value != ''
            ORDER BY pm.meta_value ASC
            LIMIT 20",
            $today
        ));
        
        // استعلامات بدون کارشناس
        $unassigned = $wpdb->get_results(
            "SELECT p.ID, p.post_type, p.post_date
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'assigned_expert_id'
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND (pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = '0')
            ORDER BY p.post_date DESC
            LIMIT 20"
        );
        
        return [
            'overdue' => $overdue,
            'unassigned' => $unassigned,
        ];
    }
}

