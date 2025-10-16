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
        
        // آمار استعلام‌های اقساطی  
        $installment_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        if ($expert_id) {
            $installment_params[] = $expert_id;
        }
        $installment_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                pm.meta_value as status,
                COUNT(*) as count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'inquiry_status'
            $expert_join
            WHERE p.post_type = 'inquiry'
            AND p.post_status = 'publish'
            $date_query
            $expert_where
            GROUP BY pm.meta_value",
            $installment_params
        ));
        
        // پردازش آمار
        $statistics = [
            'total_inquiries' => 0,
            'cash_inquiries' => 0,
            'installment_inquiries' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'following' => 0,
            'next_followup' => 0,
            'new_today' => 0,
            'revenue' => 0,
        ];
        
        foreach ($cash_stats as $stat) {
            $statistics['cash_inquiries'] += $stat->count;
            $statistics['total_inquiries'] += $stat->count;
            
            switch ($stat->status) {
                case 'pending':
                    $statistics['pending'] += $stat->count;
                    break;
                case 'approved':
                    $statistics['approved'] += $stat->count;
                    break;
                case 'rejected':
                    $statistics['rejected'] += $stat->count;
                    break;
                case 'following':
                    $statistics['following'] += $stat->count;
                    break;
            }
        }
        
        foreach ($installment_stats as $stat) {
            $statistics['installment_inquiries'] += $stat->count;
            $statistics['total_inquiries'] += $stat->count;
            
            switch ($stat->status) {
                case 'pending':
                    $statistics['pending'] += $stat->count;
                    break;
                case 'approved':
                    $statistics['approved'] += $stat->count;
                    break;
                case 'rejected':
                    $statistics['rejected'] += $stat->count;
                    break;
                case 'following':
                    $statistics['following'] += $stat->count;
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
            "SELECT COUNT(DISTINCT pm.meta_value)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'customer_national_id'
            INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id AND pm_expert.meta_key = 'assigned_expert_id'
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND pm_expert.meta_value = %d
            AND pm.meta_value != ''",
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
            "SELECT COUNT(DISTINCT pm.meta_value)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'customer_national_id'
            INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id AND pm_expert.meta_key = 'assigned_expert_id'
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND pm_expert.meta_value = %d
            AND p.post_date >= %s AND p.post_date <= %s
            AND pm.meta_value != ''",
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
            
            if ($row->post_type === 'cash_inquiry') {
                $result[$date]['cash'] = intval($row->count);
            } else {
                $result[$date]['installment'] = intval($row->count);
            }
            $result[$date]['total'] += intval($row->count);
        }
        
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
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'selected_product'
            LEFT JOIN {$wpdb->posts} prod ON pm.meta_value = prod.ID
            $expert_join
            WHERE p.post_type IN ('cash_inquiry', 'inquiry')
            AND p.post_status = 'publish'
            AND p.post_date >= %s AND p.post_date <= %s
            $expert_where
            AND pm.meta_value IS NOT NULL
            GROUP BY pm.meta_value
            ORDER BY inquiry_count DESC
            LIMIT %d",
            $params
        ));
        
        return $products;
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
            '01' => 'فروردین', '02' => 'اردیبهشت', '03' => 'خرداد',
            '04' => 'تیر', '05' => 'مرداد', '06' => 'شهریور',
            '07' => 'مهر', '08' => 'آبان', '09' => 'آذر',
            '10' => 'دی', '11' => 'بهمن', '12' => 'اسفند'
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
        
        // سرستون‌ها
        fputcsv($output, [
            'شناسه',
            'تاریخ',
            'نوع',
            'نام مشتری',
            'تلفن',
            'کد ملی',
            'محصول',
            'وضعیت',
            'مبلغ',
            'کارشناس'
        ]);
        
        // داده‌ها
        foreach ($data['inquiries'] as $inquiry) {
            $expert = get_userdata($inquiry->post_author);
            
            fputcsv($output, [
                $inquiry->ID,
                $inquiry->post_date,
                $inquiry->post_type === 'cash_inquiry' ? 'نقدی' : 'اقساطی',
                $inquiry->customer_name,
                $inquiry->customer_phone,
                $inquiry->customer_national_id,
                $inquiry->product_name,
                self::get_status_label($inquiry->status),
                number_format($inquiry->amount) . ' تومان',
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
            'pending' => 'در انتظار',
            'approved' => 'تایید شده',
            'rejected' => 'رد شده',
            'following' => 'در حال پیگیری',
        ];
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
}

