<?php
/**
 * Database operations
 *
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Database {

    /**
     * Get inquiries
     */
    public static function get_inquiries($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'user_id' => '',
            'expert_id' => '',
            'status' => '',
            'type' => '',
            'limit' => 20,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $wpdb->prefix . 'maneli_inquiries';
        $where = array('1=1');
        
        if ($args['user_id']) {
            $where[] = $wpdb->prepare("user_id = %d", $args['user_id']);
        }
        
        if ($args['expert_id']) {
            $where[] = $wpdb->prepare("expert_id = %d", $args['expert_id']);
        }
        
        if ($args['status']) {
            $where[] = $wpdb->prepare("status = %s", $args['status']);
        }
        
        if ($args['type']) {
            $where[] = $wpdb->prepare("type = %s", $args['type']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT * FROM $table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $query = $wpdb->prepare($query, $args['limit'], $args['offset']);
        
        return $wpdb->get_results($query);
    }

    /**
     * Get inquiry by ID
     */
    public static function get_inquiry($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_inquiries';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Create inquiry
     */
    public static function create_inquiry($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_inquiries';
        
        $defaults = array(
            'user_id' => 0,
            'type' => 'cash',
            'product_id' => 0,
            'status' => 'pending',
            'total_amount' => 0,
            'down_payment' => 0,
            'expert_id' => 0,
            'meta' => '',
        );
        
        $data = wp_parse_args($data, $defaults);
        $data['meta'] = maybe_serialize($data['meta']);
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli Database Error (create_inquiry): ' . $wpdb->last_error);
            }
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Update inquiry
     */
    public static function update_inquiry($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_inquiries';
        
        if (isset($data['meta'])) {
            $data['meta'] = maybe_serialize($data['meta']);
        }
        
        $result = $wpdb->update($table, $data, array('id' => $id));
        
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli Database Error (update_inquiry): ' . $wpdb->last_error);
            }
            return false;
        }
        
        return $result;
    }

    /**
     * Log notification
     */
    public static function log_notification($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_logs';
        
        $defaults = array(
            'type' => 'sms',
            'recipient' => '',
            'message' => '',
            'status' => 'pending',
            'error_message' => null,
            'scheduled_at' => null,
            'sent_at' => null,
            'related_id' => null,
            'user_id' => get_current_user_id(),
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert($table, array(
            'type' => sanitize_text_field($data['type']),
            'recipient' => sanitize_text_field($data['recipient']),
            'message' => wp_kses_post($data['message']),
            'status' => sanitize_text_field($data['status']),
            'error_message' => !empty($data['error_message']) ? sanitize_textarea_field($data['error_message']) : null,
            'scheduled_at' => $data['scheduled_at'] ? date('Y-m-d H:i:s', strtotime($data['scheduled_at'])) : null,
            'sent_at' => $data['sent_at'] ? date('Y-m-d H:i:s', strtotime($data['sent_at'])) : null,
            'related_id' => $data['related_id'] ? (int)$data['related_id'] : null,
            'user_id' => $data['user_id'] ? (int)$data['user_id'] : null,
        ), array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'));
        
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli Database Error (log_notification): ' . $wpdb->last_error);
            }
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Update notification log
     */
    public static function update_notification_log($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_logs';
        
        if (isset($data['sent_at']) && !is_string($data['sent_at'])) {
            $data['sent_at'] = date('Y-m-d H:i:s', strtotime($data['sent_at']));
        }
        
        $result = $wpdb->update($table, $data, array('id' => $id));
        
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli Database Error (update_notification_log): ' . $wpdb->last_error);
            }
            return false;
        }
        
        return $result;
    }

    /**
     * Get notification logs (includes migration from sms_history if needed)
     */
    public static function get_notification_logs($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_logs';
        
        $defaults = array(
            'type' => '',
            'status' => '',
            'recipient' => '',
            'related_id' => '',
            'user_id' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
            'limit' => 50,
            'offset' => 0,
            'order_by' => 'created_at',
            'order' => 'DESC',
            'include_sms_history' => true, // Include SMS history from post_meta
        );
        
        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        
        if (!empty($args['type'])) {
            $where[] = $wpdb->prepare("type = %s", $args['type']);
        }
        
        if ($args['status'] !== '') {
            $where[] = $wpdb->prepare("status = %s", $args['status']);
        }
        
        if (!empty($args['recipient'])) {
            $where[] = $wpdb->prepare("recipient LIKE %s", '%' . $wpdb->esc_like($args['recipient']) . '%');
        }
        
        if (!empty($args['related_id'])) {
            $where[] = $wpdb->prepare("related_id = %d", $args['related_id']);
        }
        
        if (!empty($args['user_id'])) {
            $where[] = $wpdb->prepare("user_id = %d", $args['user_id']);
        }
        
        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare("created_at >= %s", $args['date_from'] . ' 00:00:00');
        }
        
        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare("created_at <= %s", $args['date_to'] . ' 23:59:59');
        }
        
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = $wpdb->prepare("(message LIKE %s OR recipient LIKE %s)", $search_term, $search_term);
        }
        
        $where_clause = implode(' AND ', $where);
        $order_by = sanitize_sql_orderby($args['order_by'] . ' ' . $args['order']);
        if (!$order_by) {
            $order_by = 'created_at DESC';
        }
        
        $query = "SELECT * FROM $table WHERE $where_clause ORDER BY $order_by LIMIT %d OFFSET %d";
        $query = $wpdb->prepare($query, $args['limit'], $args['offset']);
        
        $logs = $wpdb->get_results($query);
        
        // If type is SMS and include_sms_history is true, merge with SMS history from post_meta
        if ($args['include_sms_history'] && ($args['type'] === 'sms' || empty($args['type']))) {
            $logs = self::merge_sms_history_with_logs($logs, $args);
        }
        
        return $logs;
    }
    
    /**
     * Merge SMS history from post_meta with notification logs
     */
    private static function merge_sms_history_with_logs($logs, $args) {
        global $wpdb;
        
        // Get all inquiries with SMS history
        $post_types = ['cash_inquiry', 'inquiry'];
        $meta_query = [
            'key' => 'sms_history',
            'compare' => 'EXISTS'
        ];
        
        $inquiry_args = [
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [$meta_query],
        ];
        
        // Apply date filters if provided
        if (!empty($args['date_from']) || !empty($args['date_to'])) {
            $inquiry_args['date_query'] = [];
            if (!empty($args['date_from'])) {
                $inquiry_args['date_query']['after'] = $args['date_from'];
                $inquiry_args['date_query']['inclusive'] = true;
            }
            if (!empty($args['date_to'])) {
                $inquiry_args['date_query']['before'] = $args['date_to'] . ' 23:59:59';
                $inquiry_args['date_query']['inclusive'] = true;
            }
        }
        
        $inquiries = get_posts($inquiry_args);
        
        $history_logs = [];
        foreach ($inquiries as $inquiry) {
            $sms_history = get_post_meta($inquiry->ID, 'sms_history', true);
            if (!is_array($sms_history)) {
                continue;
            }
            
            foreach ($sms_history as $sms) {
                // Skip if already in logs (check by recipient and message and date)
                $skip = false;
                if (!empty($logs)) {
                    foreach ($logs as $log) {
                        if ($log->recipient === ($sms['recipient'] ?? '') &&
                            $log->message === ($sms['message'] ?? '') &&
                            strtotime($log->created_at) === strtotime($sms['sent_at'] ?? '')) {
                            $skip = true;
                            break;
                        }
                    }
                }
                
                if ($skip) {
                    continue;
                }
                
                // Apply filters
                if ($args['status'] !== '') {
                    $sms_status = ($sms['success'] ?? false) ? 'sent' : 'failed';
                    if ($sms_status !== $args['status']) {
                        continue;
                    }
                }
                
                if (!empty($args['search'])) {
                    $search_lower = strtolower($args['search']);
                    if (stripos($sms['message'] ?? '', $args['search']) === false &&
                        stripos($sms['recipient'] ?? '', $args['search']) === false) {
                        continue;
                    }
                }
                
                if (!empty($args['date_from']) && isset($sms['sent_at'])) {
                    if (strtotime($sms['sent_at']) < strtotime($args['date_from'])) {
                        continue;
                    }
                }
                
                if (!empty($args['date_to']) && isset($sms['sent_at'])) {
                    if (strtotime($sms['sent_at']) > strtotime($args['date_to'] . ' 23:59:59')) {
                        continue;
                    }
                }
                
                // Convert SMS history entry to log format
                $log_obj = (object)[
                    'id' => 'history_' . $inquiry->ID . '_' . md5($sms['sent_at'] . $sms['recipient']),
                    'type' => 'sms',
                    'recipient' => $sms['recipient'] ?? '',
                    'message' => $sms['message'] ?? '',
                    'status' => ($sms['success'] ?? false) ? 'sent' : 'failed',
                    'error_message' => $sms['error'] ?? null,
                    'scheduled_at' => null,
                    'sent_at' => $sms['sent_at'] ?? null,
                    'created_at' => $sms['sent_at'] ?? current_time('mysql'),
                    'related_id' => $inquiry->ID,
                    'user_id' => $sms['user_id'] ?? 0,
                ];
                
                $history_logs[] = $log_obj;
            }
        }
        
        // Merge and sort
        $all_logs = array_merge($logs, $history_logs);
        
        // Sort by created_at
        usort($all_logs, function($a, $b) use ($args) {
            $a_time = strtotime($a->created_at);
            $b_time = strtotime($b->created_at);
            if ($args['order'] === 'ASC') {
                return $a_time <=> $b_time;
            }
            return $b_time <=> $a_time;
        });
        
        // Apply limit and offset
        $all_logs = array_slice($all_logs, $args['offset'], $args['limit']);
        
        return $all_logs;
    }

    /**
     * Get notification logs count (includes SMS history if needed)
     */
    public static function get_notification_logs_count($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_logs';
        
        $defaults = array(
            'type' => '',
            'status' => '',
            'recipient' => '',
            'related_id' => '',
            'user_id' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
            'include_sms_history' => true,
        );
        
        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        
        if (!empty($args['type'])) {
            $where[] = $wpdb->prepare("type = %s", $args['type']);
        }
        
        if ($args['status'] !== '') {
            $where[] = $wpdb->prepare("status = %s", $args['status']);
        }
        
        if (!empty($args['recipient'])) {
            $where[] = $wpdb->prepare("recipient LIKE %s", '%' . $wpdb->esc_like($args['recipient']) . '%');
        }
        
        if (!empty($args['related_id'])) {
            $where[] = $wpdb->prepare("related_id = %d", $args['related_id']);
        }
        
        if (!empty($args['user_id'])) {
            $where[] = $wpdb->prepare("user_id = %d", $args['user_id']);
        }
        
        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare("created_at >= %s", $args['date_from'] . ' 00:00:00');
        }
        
        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare("created_at <= %s", $args['date_to'] . ' 23:59:59');
        }
        
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = $wpdb->prepare("(message LIKE %s OR recipient LIKE %s)", $search_term, $search_term);
        }
        
        $where_clause = implode(' AND ', $where);
        $query = "SELECT COUNT(*) FROM $table WHERE $where_clause";
        
        $count = (int)$wpdb->get_var($query);
        
        // If type is SMS and include_sms_history is true, add count from SMS history
        if ($args['include_sms_history'] && ($args['type'] === 'sms' || empty($args['type']))) {
            $history_count = self::count_sms_history($args);
            $count += $history_count;
        }
        
        return $count;
    }
    
    /**
     * Count SMS history entries from post_meta
     */
    private static function count_sms_history($args) {
        $post_types = ['cash_inquiry', 'inquiry'];
        $meta_query = [
            'key' => 'sms_history',
            'compare' => 'EXISTS'
        ];
        
        $inquiry_args = [
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [$meta_query],
            'fields' => 'ids',
        ];
        
        // Apply date filters if provided
        if (!empty($args['date_from']) || !empty($args['date_to'])) {
            $inquiry_args['date_query'] = [];
            if (!empty($args['date_from'])) {
                $inquiry_args['date_query']['after'] = $args['date_from'];
                $inquiry_args['date_query']['inclusive'] = true;
            }
            if (!empty($args['date_to'])) {
                $inquiry_args['date_query']['before'] = $args['date_to'] . ' 23:59:59';
                $inquiry_args['date_query']['inclusive'] = true;
            }
        }
        
        $inquiry_ids = get_posts($inquiry_args);
        
        $count = 0;
        foreach ($inquiry_ids as $inquiry_id) {
            $sms_history = get_post_meta($inquiry_id, 'sms_history', true);
            if (!is_array($sms_history)) {
                continue;
            }
            
            foreach ($sms_history as $sms) {
                // Apply filters
                if ($args['status'] !== '') {
                    $sms_status = ($sms['success'] ?? false) ? 'sent' : 'failed';
                    if ($sms_status !== $args['status']) {
                        continue;
                    }
                }
                
                if (!empty($args['search'])) {
                    if (stripos($sms['message'] ?? '', $args['search']) === false &&
                        stripos($sms['recipient'] ?? '', $args['search']) === false) {
                        continue;
                    }
                }
                
                if (!empty($args['date_from']) && isset($sms['sent_at'])) {
                    if (strtotime($sms['sent_at']) < strtotime($args['date_from'])) {
                        continue;
                    }
                }
                
                if (!empty($args['date_to']) && isset($sms['sent_at'])) {
                    if (strtotime($sms['sent_at']) > strtotime($args['date_to'] . ' 23:59:59')) {
                        continue;
                    }
                }
                
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get notification statistics (includes SMS history if needed)
     */
    public static function get_notification_stats($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_logs';
        
        $defaults = array(
            'date_from' => '',
            'date_to' => '',
            'type' => '',
            'include_sms_history' => true,
        );
        
        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        
        if (!empty($args['type'])) {
            $where[] = $wpdb->prepare("type = %s", $args['type']);
        }
        
        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare("created_at >= %s", $args['date_from'] . ' 00:00:00');
        }
        
        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare("created_at <= %s", $args['date_to'] . ' 23:59:59');
        }
        
        $where_clause = implode(' AND ', $where);
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN type = 'sms' AND status = 'sent' THEN 1 ELSE 0 END) as sms_sent,
                SUM(CASE WHEN type = 'telegram' AND status = 'sent' THEN 1 ELSE 0 END) as telegram_sent,
                SUM(CASE WHEN type = 'email' AND status = 'sent' THEN 1 ELSE 0 END) as email_sent,
                SUM(CASE WHEN type = 'notification' AND status = 'sent' THEN 1 ELSE 0 END) as notification_sent
            FROM $table 
            WHERE $where_clause
        ");
        
        // If type is SMS and include_sms_history is true, add stats from SMS history
        if ($args['include_sms_history'] && ($args['type'] === 'sms' || empty($args['type']))) {
            $history_stats = self::get_sms_history_stats($args);
            
            // Merge stats
            $stats->total = (int)($stats->total ?? 0) + (int)($history_stats['total'] ?? 0);
            $stats->sent = (int)($stats->sent ?? 0) + (int)($history_stats['sent'] ?? 0);
            $stats->failed = (int)($stats->failed ?? 0) + (int)($history_stats['failed'] ?? 0);
            $stats->pending = (int)($stats->pending ?? 0) + (int)($history_stats['pending'] ?? 0);
            
            if ($args['type'] === 'sms' || empty($args['type'])) {
                $stats->sms_sent = (int)($stats->sms_sent ?? 0) + (int)($history_stats['sent'] ?? 0);
            }
        }
        
        return $stats;
    }
    
    /**
     * Get SMS history statistics from post_meta
     */
    private static function get_sms_history_stats($args) {
        $post_types = ['cash_inquiry', 'inquiry'];
        $meta_query = [
            'key' => 'sms_history',
            'compare' => 'EXISTS'
        ];
        
        $inquiry_args = [
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [$meta_query],
            'fields' => 'ids',
        ];
        
        // Apply date filters if provided
        if (!empty($args['date_from']) || !empty($args['date_to'])) {
            $inquiry_args['date_query'] = [];
            if (!empty($args['date_from'])) {
                $inquiry_args['date_query']['after'] = $args['date_from'];
                $inquiry_args['date_query']['inclusive'] = true;
            }
            if (!empty($args['date_to'])) {
                $inquiry_args['date_query']['before'] = $args['date_to'] . ' 23:59:59';
                $inquiry_args['date_query']['inclusive'] = true;
            }
        }
        
        $inquiry_ids = get_posts($inquiry_args);
        
        $stats = [
            'total' => 0,
            'sent' => 0,
            'failed' => 0,
            'pending' => 0,
        ];
        
        foreach ($inquiry_ids as $inquiry_id) {
            $sms_history = get_post_meta($inquiry_id, 'sms_history', true);
            if (!is_array($sms_history)) {
                continue;
            }
            
            foreach ($sms_history as $sms) {
                // Apply date filters
                if (!empty($args['date_from']) && isset($sms['sent_at'])) {
                    if (strtotime($sms['sent_at']) < strtotime($args['date_from'])) {
                        continue;
                    }
                }
                
                if (!empty($args['date_to']) && isset($sms['sent_at'])) {
                    if (strtotime($sms['sent_at']) > strtotime($args['date_to'] . ' 23:59:59')) {
                        continue;
                    }
                }
                
                $stats['total']++;
                if ($sms['success'] ?? false) {
                    $stats['sent']++;
                } else {
                    $stats['failed']++;
                }
            }
        }
        
        return $stats;
    }

    /**
     * Get notification timeline data for charts (includes SMS history if needed)
     */
    public static function get_notification_timeline($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_logs';
        
        $defaults = array(
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d'),
            'type' => '',
            'include_sms_history' => true,
        );
        
        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        
        if (!empty($args['type'])) {
            $where[] = $wpdb->prepare("type = %s", $args['type']);
        }
        
        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare("DATE(created_at) >= %s", $args['date_from']);
        }
        
        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare("DATE(created_at) <= %s", $args['date_to']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get daily counts grouped by date
        $query = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM $table 
            WHERE $where_clause
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) ASC
        ";
        
        $results = $wpdb->get_results($query);
        
        // If type is SMS and include_sms_history is true, add SMS history data
        if ($args['include_sms_history'] && ($args['type'] === 'sms' || empty($args['type']))) {
            $history_results = self::get_sms_history_timeline($args);
            
            // Merge results by date
            $merged_results = [];
            foreach ($results as $row) {
                $merged_results[$row->date] = $row;
            }
            
            foreach ($history_results as $row) {
                if (isset($merged_results[$row->date])) {
                    // Merge with existing data
                    $merged_results[$row->date]->total += (int)$row->total;
                    $merged_results[$row->date]->sent += (int)$row->sent;
                    $merged_results[$row->date]->failed += (int)$row->failed;
                    $merged_results[$row->date]->pending += (int)$row->pending;
                } else {
                    // Add new date
                    $merged_results[$row->date] = $row;
                }
            }
            
            $results = array_values($merged_results);
            // Sort by date
            usort($results, function($a, $b) {
                return strcmp($a->date, $b->date);
            });
        }
        
        // Generate date range
        $start = new DateTime($args['date_from']);
        $end = new DateTime($args['date_to']);
        $interval = new DateInterval('P1D');
        $date_range = new DatePeriod($start, $interval, $end->modify('+1 day'));
        
        // Create labels and data arrays
        $labels = [];
        $data = [];
        $data_failed = [];
        $data_pending = [];
        
        // Build a map of existing data
        $data_map = [];
        foreach ($results as $row) {
            $data_map[$row->date] = [
                'sent' => (int)$row->sent,
                'failed' => (int)$row->failed,
                'pending' => (int)$row->pending,
                'total' => (int)$row->total
            ];
        }
        
        // Fill in dates (even if no data)
        foreach ($date_range as $date) {
            $date_str = $date->format('Y-m-d');
            $labels[] = $date->format('Y/m/d');
            
            if (isset($data_map[$date_str])) {
                $data[] = $data_map[$date_str]['sent'];
                $data_failed[] = $data_map[$date_str]['failed'];
                $data_pending[] = $data_map[$date_str]['pending'];
            } else {
                $data[] = 0;
                $data_failed[] = 0;
                $data_pending[] = 0;
            }
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'data_failed' => $data_failed,
            'data_pending' => $data_pending
        ];
    }
    
    /**
     * Get SMS history timeline data from post_meta
     */
    private static function get_sms_history_timeline($args) {
        $post_types = ['cash_inquiry', 'inquiry'];
        $meta_query = [
            'key' => 'sms_history',
            'compare' => 'EXISTS'
        ];
        
        $inquiry_args = [
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [$meta_query],
            'fields' => 'ids',
        ];
        
        // Apply date filters if provided
        if (!empty($args['date_from']) || !empty($args['date_to'])) {
            $inquiry_args['date_query'] = [];
            if (!empty($args['date_from'])) {
                $inquiry_args['date_query']['after'] = $args['date_from'];
                $inquiry_args['date_query']['inclusive'] = true;
            }
            if (!empty($args['date_to'])) {
                $inquiry_args['date_query']['before'] = $args['date_to'] . ' 23:59:59';
                $inquiry_args['date_query']['inclusive'] = true;
            }
        }
        
        $inquiry_ids = get_posts($inquiry_args);
        
        $daily_counts = [];
        
        foreach ($inquiry_ids as $inquiry_id) {
            $sms_history = get_post_meta($inquiry_id, 'sms_history', true);
            if (!is_array($sms_history)) {
                continue;
            }
            
            foreach ($sms_history as $sms) {
                if (empty($sms['sent_at'])) {
                    continue;
                }
                
                $date = date('Y-m-d', strtotime($sms['sent_at']));
                
                // Apply date filters
                if (!empty($args['date_from']) && strtotime($date) < strtotime($args['date_from'])) {
                    continue;
                }
                
                if (!empty($args['date_to']) && strtotime($date) > strtotime($args['date_to'])) {
                    continue;
                }
                
                if (!isset($daily_counts[$date])) {
                    $daily_counts[$date] = [
                        'date' => $date,
                        'total' => 0,
                        'sent' => 0,
                        'failed' => 0,
                        'pending' => 0,
                    ];
                }
                
                $daily_counts[$date]['total']++;
                if ($sms['success'] ?? false) {
                    $daily_counts[$date]['sent']++;
                } else {
                    $daily_counts[$date]['failed']++;
                }
            }
        }
        
        // Convert to objects
        $results = [];
        foreach ($daily_counts as $count) {
            $results[] = (object)$count;
        }
        
        return $results;
    }

    /**
     * Get notification templates
     */
    public static function get_notification_templates($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_templates';
        
        $defaults = array(
            'type' => '',
            'is_active' => '',
            'search' => '',
            'limit' => 100,
            'offset' => 0,
            'order_by' => 'created_at',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        
        if (!empty($args['type'])) {
            $where[] = $wpdb->prepare("type = %s", $args['type']);
        }
        
        if ($args['is_active'] !== '') {
            $where[] = $wpdb->prepare("is_active = %d", (int)$args['is_active']);
        }
        
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = $wpdb->prepare("(name LIKE %s OR message LIKE %s)", $search_term, $search_term);
        }
        
        $where_clause = implode(' AND ', $where);
        $order_by = sanitize_sql_orderby($args['order_by'] . ' ' . $args['order']);
        if (!$order_by) {
            $order_by = 'created_at DESC';
        }
        
        $query = "SELECT * FROM $table WHERE $where_clause ORDER BY $order_by LIMIT %d OFFSET %d";
        $query = $wpdb->prepare($query, $args['limit'], $args['offset']);
        
        return $wpdb->get_results($query);
    }

    /**
     * Get notification template by ID
     */
    public static function get_notification_template($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_templates';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Create notification template
     */
    public static function create_notification_template($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_templates';
        
        $defaults = array(
            'type' => 'sms',
            'name' => '',
            'subject' => '',
            'message' => '',
            'variables' => null,
            'is_active' => 1,
        );
        
        $data = wp_parse_args($data, $defaults);
        
        if (is_array($data['variables'])) {
            // Validate JSON encoding and limit size
            $json_variables = json_encode($data['variables'], JSON_UNESCAPED_UNICODE);
            if ($json_variables === false) {
                // Log JSON encoding error
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Database Error: JSON encoding failed for variables. JSON error: ' . json_last_error_msg());
                }
                $data['variables'] = json_encode([]); // Fallback to empty array
            } elseif (strlen($json_variables) > 5000) {
                // Limit JSON size to 5KB for notification templates
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Database Warning: Variables JSON too large (' . strlen($json_variables) . ' bytes), truncating.');
                }
                $data['variables'] = substr($json_variables, 0, 5000);
            } else {
                $data['variables'] = $json_variables;
            }
        }
        
        $result = $wpdb->insert($table, array(
            'type' => sanitize_text_field($data['type']),
            'name' => sanitize_text_field($data['name']),
            'subject' => !empty($data['subject']) ? sanitize_text_field($data['subject']) : null,
            'message' => wp_kses_post($data['message']),
            'variables' => $data['variables'],
            'is_active' => (int)$data['is_active'],
        ), array('%s', '%s', '%s', '%s', '%s', '%d'));
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update notification template
     */
    public static function update_notification_template($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_templates';
        
        if (isset($data['variables']) && is_array($data['variables'])) {
            // Validate JSON encoding and limit size
            $json_variables = json_encode($data['variables'], JSON_UNESCAPED_UNICODE);
            if ($json_variables === false) {
                // Log JSON encoding error
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Database Error: JSON encoding failed for variables. JSON error: ' . json_last_error_msg());
                }
                $data['variables'] = json_encode([]); // Fallback to empty array
            } elseif (strlen($json_variables) > 5000) {
                // Limit JSON size to 5KB for notification templates
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Database Warning: Variables JSON too large (' . strlen($json_variables) . ' bytes), truncating.');
                }
                $data['variables'] = substr($json_variables, 0, 5000);
            } else {
                $data['variables'] = $json_variables;
            }
        }
        
        $update_data = array();
        $format = array();
        
        if (isset($data['type'])) {
            $update_data['type'] = sanitize_text_field($data['type']);
            $format[] = '%s';
        }
        
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }
        
        if (isset($data['subject'])) {
            $update_data['subject'] = !empty($data['subject']) ? sanitize_text_field($data['subject']) : null;
            $format[] = '%s';
        }
        
        if (isset($data['message'])) {
            $update_data['message'] = wp_kses_post($data['message']);
            $format[] = '%s';
        }
        
        if (isset($data['variables'])) {
            $update_data['variables'] = $data['variables'];
            $format[] = '%s';
        }
        
        if (isset($data['is_active'])) {
            $update_data['is_active'] = (int)$data['is_active'];
            $format[] = '%d';
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        return $wpdb->update($table, $update_data, array('id' => $id), $format, array('%d'));
    }

    /**
     * Delete notification template
     */
    public static function delete_notification_template($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_templates';
        return $wpdb->delete($table, array('id' => $id), array('%d'));
    }

    /**
     * Log system log
     */
    public static function log_system_log($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_system_logs';
        
        $session_user_id = self::get_session_user_id();
        $options = Maneli_Options_Helper::get_all_options();

        $defaults = array(
            'log_type' => 'debug',
            'severity' => 'info',
            'message' => '',
            'context' => null,
            'file' => null,
            'line' => null,
            'user_id' => get_current_user_id() ?: ($session_user_id ?: null),
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : null,
        );
        
        $data = wp_parse_args($data, $defaults);
        
        if (is_array($data['context'])) {
            // Validate JSON encoding and limit size
            $json_context = json_encode($data['context'], JSON_UNESCAPED_UNICODE);
            if ($json_context === false) {
                // Log JSON encoding error
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Database Error: JSON encoding failed for context. JSON error: ' . json_last_error_msg());
                }
                $data['context'] = json_encode([]); // Fallback to empty array
            } elseif (strlen($json_context) > 10000) {
                // Limit JSON size to 10KB to prevent database issues
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Database Warning: Context JSON too large (' . strlen($json_context) . ' bytes), truncating.');
                }
                $data['context'] = substr($json_context, 0, 10000);
            } else {
                $data['context'] = $json_context;
            }
        }

        $log_type = sanitize_text_field($data['log_type']);
        $severity = sanitize_text_field($data['severity']);
        $message = wp_kses_post($data['message']);
        $file_value = !empty($data['file']) ? sanitize_text_field($data['file']) : null;
        $line_value = !empty($data['line']) ? (int)$data['line'] : null;
        $context_value = $data['context'];
        $user_id_value = $data['user_id'] ? (int)$data['user_id'] : null;
        $ip_value = $data['ip_address'];
        $user_agent_value = $data['user_agent'];

        $dedupe_window_minutes = isset($options['log_dedup_window_minutes']) ? max(0, intval($options['log_dedup_window_minutes'])) : 5;
        if ($dedupe_window_minutes > 0) {
            $dedupe_cutoff = gmdate('Y-m-d H:i:s', time() - ($dedupe_window_minutes * MINUTE_IN_SECONDS));
            $duplicate_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} 
                 WHERE log_type = %s 
                   AND severity = %s 
                   AND message = %s 
                   AND COALESCE(file, '') = %s 
                   AND COALESCE(line, 0) = %d 
                   AND COALESCE(context, '') = %s
                   AND created_at >= %s
                 LIMIT 1",
                $log_type,
                $severity,
                $message,
                $file_value ? $file_value : '',
                $line_value !== null ? $line_value : 0,
                $context_value !== null ? $context_value : '',
                $dedupe_cutoff
            ));

            if (!empty($duplicate_id)) {
                return (int) $duplicate_id;
            }
        }
        
        $result = $wpdb->insert($table, array(
            'log_type' => $log_type,
            'severity' => $severity,
            'message' => $message,
            'context' => $context_value,
            'file' => $file_value,
            'line' => $line_value,
            'user_id' => $user_id_value,
            'ip_address' => $ip_value,
            'user_agent' => $user_agent_value,
        ), array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s'));
        
        if ($result) {
            self::maybe_prune_system_logs($options);
            return $wpdb->insert_id;
        }

        return false;
    }

    private static function maybe_prune_system_logs($options) {
        global $wpdb;

        $table = $wpdb->prefix . 'maneli_system_logs';
        $max_size_mb = isset($options['log_max_size_mb']) ? max(1, (int)$options['log_max_size_mb']) : 10;
        $max_records = isset($options['log_max_records']) ? max(1000, (int)$options['log_max_records']) : 50000;

        $current_size = self::get_table_size_mb($table);
        if ($current_size > $max_size_mb) {
            $iterations = 0;
            $max_iterations = 10;
            while ($iterations < $max_iterations && $current_size > $max_size_mb) {
                $deleted = self::delete_old_system_logs($table, 500);
                if (!$deleted) {
                    break;
                }
                $current_size = self::get_table_size_mb($table);
                $iterations++;
            }
        }

        if ($max_records > 0) {
            $total_records = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            if ($total_records > $max_records) {
                $to_remove = $total_records - $max_records;
                self::delete_old_system_logs($table, $to_remove);
            }
        }
    }

    private static function get_table_size_mb($table) {
        global $wpdb;

        if (!defined('DB_NAME') || empty(DB_NAME)) {
            return 0;
        }

        $size = $wpdb->get_var($wpdb->prepare(
            "SELECT ROUND((data_length + index_length) / 1048576, 4) AS total_mb
             FROM information_schema.TABLES
             WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $table
        ));

        return $size ? (float) $size : 0;
    }

    private static function delete_old_system_logs($table, $limit) {
        global $wpdb;

        $limit = (int) $limit;
        if ($limit <= 0) {
            return 0;
        }

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$table} ORDER BY created_at ASC LIMIT %d",
            $limit
        ));

        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE id IN ($placeholders)",
            $ids
        ));
    }

    /**
     * Log user log
     */
    public static function log_user_log($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_user_logs';
        
        $session_user_id = self::get_session_user_id();

        $defaults = array(
            'user_id' => get_current_user_id() ?: $session_user_id,
            'action_type' => 'unknown',
            'action_description' => '',
            'target_type' => null,
            'target_id' => null,
            'metadata' => null,
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : null,
        );
        
        $data = wp_parse_args($data, $defaults);
        
        if (!$data['user_id']) {
            return false; // User ID is required
        }
        
        if (is_array($data['metadata'])) {
            // Validate JSON encoding and limit size
            $json_metadata = json_encode($data['metadata'], JSON_UNESCAPED_UNICODE);
            if ($json_metadata === false) {
                // Log JSON encoding error
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Database Error: JSON encoding failed for metadata. JSON error: ' . json_last_error_msg());
                }
                $data['metadata'] = json_encode([]); // Fallback to empty array
            } elseif (strlen($json_metadata) > 10000) {
                // Limit JSON size to 10KB to prevent database issues
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Database Warning: Metadata JSON too large (' . strlen($json_metadata) . ' bytes), truncating.');
                }
                $data['metadata'] = substr($json_metadata, 0, 10000);
            } else {
                $data['metadata'] = $json_metadata;
            }
        }
        
        $result = $wpdb->insert($table, array(
            'user_id' => (int)$data['user_id'],
            'action_type' => sanitize_text_field($data['action_type']),
            'action_description' => sanitize_text_field($data['action_description']),
            'target_type' => !empty($data['target_type']) ? sanitize_text_field($data['target_type']) : null,
            'target_id' => !empty($data['target_id']) ? (int)$data['target_id'] : null,
            'metadata' => $data['metadata'],
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'],
        ), array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s'));
        
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli Database Error (log_user_log): ' . $wpdb->last_error);
            }
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Get system logs
     */
    public static function get_system_logs($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_system_logs';
        
        $defaults = array(
            'log_type' => '',
            'severity' => '',
            'user_id' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
            'limit' => 50,
            'offset' => 0,
            'order_by' => 'created_at',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        
        if (!empty($args['log_type'])) {
            $where[] = $wpdb->prepare("log_type = %s", $args['log_type']);
        }
        
        if (!empty($args['severity'])) {
            $where[] = $wpdb->prepare("severity = %s", $args['severity']);
        }
        
        if (!empty($args['user_id'])) {
            $where[] = $wpdb->prepare("user_id = %d", $args['user_id']);
        }
        
        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare("created_at >= %s", $args['date_from'] . ' 00:00:00');
        }
        
        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare("created_at <= %s", $args['date_to'] . ' 23:59:59');
        }
        
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = $wpdb->prepare("message LIKE %s", $search_term);
        }
        
        $where_clause = implode(' AND ', $where);
        $order_by = sanitize_sql_orderby($args['order_by'] . ' ' . $args['order']);
        if (!$order_by) {
            $order_by = 'created_at DESC';
        }
        
        $query = "SELECT * FROM $table WHERE $where_clause ORDER BY $order_by LIMIT %d OFFSET %d";
        $query = $wpdb->prepare($query, $args['limit'], $args['offset']);
        
        return $wpdb->get_results($query);
    }

    /**
     * Get system logs count
     */
    public static function get_system_logs_count($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_system_logs';
        
        $defaults = array(
            'log_type' => '',
            'severity' => '',
            'user_id' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
        );
        
        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        
        if (!empty($args['log_type'])) {
            $where[] = $wpdb->prepare("log_type = %s", $args['log_type']);
        }
        
        if (!empty($args['severity'])) {
            $where[] = $wpdb->prepare("severity = %s", $args['severity']);
        }
        
        if (!empty($args['user_id'])) {
            $where[] = $wpdb->prepare("user_id = %d", $args['user_id']);
        }
        
        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare("created_at >= %s", $args['date_from'] . ' 00:00:00');
        }
        
        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare("created_at <= %s", $args['date_to'] . ' 23:59:59');
        }
        
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = $wpdb->prepare("message LIKE %s", $search_term);
        }
        
        $where_clause = implode(' AND ', $where);
        $query = "SELECT COUNT(*) FROM $table WHERE $where_clause";
        
        return (int)$wpdb->get_var($query);
    }

    /**
     * Get user logs
     */
    public static function get_user_logs($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_user_logs';
        
        $defaults = array(
            'user_id' => '',
            'action_type' => '',
            'target_type' => '',
            'target_id' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
            'limit' => 50,
            'offset' => 0,
            'order_by' => 'created_at',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        
        if (!empty($args['user_id'])) {
            $where[] = $wpdb->prepare("user_id = %d", $args['user_id']);
        }
        
        if (!empty($args['action_type'])) {
            $where[] = $wpdb->prepare("action_type = %s", $args['action_type']);
        }
        
        if (!empty($args['target_type'])) {
            $where[] = $wpdb->prepare("target_type = %s", $args['target_type']);
        }
        
        if (!empty($args['target_id'])) {
            $where[] = $wpdb->prepare("target_id = %d", $args['target_id']);
        }
        
        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare("created_at >= %s", $args['date_from'] . ' 00:00:00');
        }
        
        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare("created_at <= %s", $args['date_to'] . ' 23:59:59');
        }
        
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = $wpdb->prepare("action_description LIKE %s", $search_term);
        }
        
        $where_clause = implode(' AND ', $where);
        $order_by = sanitize_sql_orderby($args['order_by'] . ' ' . $args['order']);
        if (!$order_by) {
            $order_by = 'created_at DESC';
        }
        
        $query = "SELECT * FROM $table WHERE $where_clause ORDER BY $order_by LIMIT %d OFFSET %d";
        $query = $wpdb->prepare($query, $args['limit'], $args['offset']);
        
        return $wpdb->get_results($query);
    }

    /**
     * Get user logs count
     */
    public static function get_user_logs_count($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_user_logs';
        
        $defaults = array(
            'user_id' => '',
            'action_type' => '',
            'target_type' => '',
            'target_id' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
        );
        
        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        
        if (!empty($args['user_id'])) {
            $where[] = $wpdb->prepare("user_id = %d", $args['user_id']);
        }
        
        if (!empty($args['action_type'])) {
            $where[] = $wpdb->prepare("action_type = %s", $args['action_type']);
        }
        
        if (!empty($args['target_type'])) {
            $where[] = $wpdb->prepare("target_type = %s", $args['target_type']);
        }
        
        if (!empty($args['target_id'])) {
            $where[] = $wpdb->prepare("target_id = %d", $args['target_id']);
        }
        
        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare("created_at >= %s", $args['date_from'] . ' 00:00:00');
        }
        
        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare("created_at <= %s", $args['date_to'] . ' 23:59:59');
        }
        
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = $wpdb->prepare("action_description LIKE %s", $search_term);
        }
        
        $where_clause = implode(' AND ', $where);
        $query = "SELECT COUNT(*) FROM $table WHERE $where_clause";
        
        return (int)$wpdb->get_var($query);
    }

    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * Retrieve the user ID stored in the plugin session if available
     */
    private static function get_session_user_id() {
        if (class_exists('Maneli_Session')) {
            $session = new Maneli_Session();
            if (session_status() === PHP_SESSION_NONE) {
                $session->start_session();
            }
            $user_id = $session->get_user_id();
            if ($user_id) {
                return (int) $user_id;
            }
        }

        if (isset($_SESSION['maneli']['user_id']) && !empty($_SESSION['maneli']['user_id'])) {
            return (int) $_SESSION['maneli']['user_id'];
        }

        return null;
    }

    /**
     * Delete all system logs
     */
    public static function delete_system_logs() {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_system_logs';

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($total === 0) {
            return 0;
        }

        $deleted = $wpdb->query("DELETE FROM {$table}");

        if ($deleted === false) {
            return false;
        }

        return $deleted;
    }
}
