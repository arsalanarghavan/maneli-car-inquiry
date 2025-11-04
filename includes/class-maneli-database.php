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
        
        $wpdb->insert($table, $data);
        
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
        
        return $wpdb->update($table, $data, array('id' => $id));
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
        
        return $result ? $wpdb->insert_id : false;
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
        
        return $wpdb->update($table, $data, array('id' => $id));
    }

    /**
     * Get notification logs
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
        
        return $wpdb->get_results($query);
    }

    /**
     * Get notification logs count
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
        
        return (int)$wpdb->get_var($query);
    }

    /**
     * Get notification statistics
     */
    public static function get_notification_stats($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_logs';
        
        $defaults = array(
            'date_from' => '',
            'date_to' => '',
            'type' => '',
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
        
        $stats = $wpdb->get_row($wpdb->prepare("
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
        "));
        
        return $stats;
    }
}
