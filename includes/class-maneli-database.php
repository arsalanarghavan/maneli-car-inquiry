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
            $data['variables'] = json_encode($data['variables']);
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
            $data['variables'] = json_encode($data['variables']);
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
        
        $defaults = array(
            'log_type' => 'debug',
            'severity' => 'info',
            'message' => '',
            'context' => null,
            'file' => null,
            'line' => null,
            'user_id' => get_current_user_id() ?: null,
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
        );
        
        $data = wp_parse_args($data, $defaults);
        
        if (is_array($data['context'])) {
            $data['context'] = json_encode($data['context'], JSON_UNESCAPED_UNICODE);
        }
        
        $result = $wpdb->insert($table, array(
            'log_type' => sanitize_text_field($data['log_type']),
            'severity' => sanitize_text_field($data['severity']),
            'message' => wp_kses_post($data['message']),
            'context' => $data['context'],
            'file' => !empty($data['file']) ? sanitize_text_field($data['file']) : null,
            'line' => !empty($data['line']) ? (int)$data['line'] : null,
            'user_id' => $data['user_id'] ? (int)$data['user_id'] : null,
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'],
        ), array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s'));
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Log user log
     */
    public static function log_user_log($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_user_logs';
        
        $defaults = array(
            'user_id' => get_current_user_id(),
            'action_type' => 'unknown',
            'action_description' => '',
            'target_type' => null,
            'target_id' => null,
            'metadata' => null,
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
        );
        
        $data = wp_parse_args($data, $defaults);
        
        if (!$data['user_id']) {
            return false; // User ID is required
        }
        
        if (is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata'], JSON_UNESCAPED_UNICODE);
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
        
        return $result ? $wpdb->insert_id : false;
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
}
