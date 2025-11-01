<?php
/**
 * Fired during plugin activation
 *
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Create default options
        self::create_default_options();
        
        // Set flag for rewrite flush
        update_option('maneli_flush_rewrite_rules', false);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Inquiries table
        $table_inquiries = $wpdb->prefix . 'maneli_inquiries';
        $sql_inquiries = "CREATE TABLE IF NOT EXISTS $table_inquiries (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL COMMENT 'cash or installment',
            product_id bigint(20) NOT NULL,
            status varchar(50) DEFAULT 'pending',
            total_amount decimal(10,2) DEFAULT NULL,
            down_payment decimal(10,2) DEFAULT NULL,
            expert_id bigint(20) DEFAULT NULL,
            meta longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY expert_id (expert_id),
            KEY status (status)
        ) $charset_collate;";

        // Followups table
        $table_followups = $wpdb->prefix . 'maneli_followups';
        $sql_followups = "CREATE TABLE IF NOT EXISTS $table_followups (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            inquiry_id bigint(20) NOT NULL,
            expert_id bigint(20) NOT NULL,
            note text,
            next_followup_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY inquiry_id (inquiry_id),
            KEY expert_id (expert_id)
        ) $charset_collate;";

        // Notifications table
        $table_notifications = $wpdb->prefix . 'maneli_notifications';
        $sql_notifications = "CREATE TABLE IF NOT EXISTS $table_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL COMMENT 'inquiry_new, inquiry_assigned, inquiry_status_changed, etc.',
            title varchar(255) NOT NULL,
            message text,
            link varchar(500) DEFAULT NULL,
            related_id bigint(20) DEFAULT NULL COMMENT 'ID of related inquiry or other item',
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY type (type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_inquiries);
        dbDelta($sql_followups);
        dbDelta($sql_notifications);
    }

    /**
     * Create default options
     */
    private static function create_default_options() {
        $defaults = array(
            'maneli_theme_mode' => 'light',
            'maneli_primary_color' => '#5e72e4',
            'maneli_logo' => '',
            'loan_interest_rate' => '0.035', // نرخ سود ماهانه 3.5% (previously maneli_interest_rate was wrong)
            'maneli_payment_gateway' => 'zarinpal',
            'maneli_login_method' => 'both',
            'maneli_session_timeout' => '3600',
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}
