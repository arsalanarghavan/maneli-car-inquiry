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
}
