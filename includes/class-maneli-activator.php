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

        // Store current database schema version
        $current_version = defined('MANELI_DB_VERSION') ? MANELI_DB_VERSION : MANELI_VERSION;
        update_option('maneli_db_version', $current_version);
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

        // Notification logs table
        $table_notification_logs = $wpdb->prefix . 'maneli_notification_logs';
        $sql_notification_logs = "CREATE TABLE IF NOT EXISTS $table_notification_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL COMMENT 'sms, telegram, email, notification',
            recipient varchar(255) NOT NULL,
            message text NOT NULL,
            status varchar(50) DEFAULT 'pending' COMMENT 'pending, sent, failed',
            error_message text DEFAULT NULL,
            scheduled_at datetime DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            related_id bigint(20) DEFAULT NULL COMMENT 'ID of related inquiry, user, or other item',
            user_id bigint(20) DEFAULT NULL COMMENT 'User who triggered the notification',
            PRIMARY KEY (id),
            KEY type (type),
            KEY status (status),
            KEY recipient (recipient(100)),
            KEY scheduled_at (scheduled_at),
            KEY created_at (created_at),
            KEY related_id (related_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Notification templates table
        $table_notification_templates = $wpdb->prefix . 'maneli_notification_templates';
        $sql_notification_templates = "CREATE TABLE IF NOT EXISTS $table_notification_templates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL COMMENT 'sms, telegram, email, notification',
            name varchar(255) NOT NULL,
            subject varchar(500) DEFAULT NULL COMMENT 'Subject line for email',
            message text NOT NULL,
            variables text DEFAULT NULL COMMENT 'JSON array of available variables',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY is_active (is_active),
            KEY name (name(100))
        ) $charset_collate;";

        // Visitors table - برای ذخیره اطلاعات بازدیدکنندگان
        $table_visitors = $wpdb->prefix . 'maneli_visitors';
        $sql_visitors = "CREATE TABLE IF NOT EXISTS $table_visitors (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL COMMENT 'IPv4 or IPv6 address',
            user_agent text,
            country varchar(100) DEFAULT NULL,
            country_code varchar(2) DEFAULT NULL,
            browser varchar(100) DEFAULT NULL,
            browser_version varchar(50) DEFAULT NULL,
            os varchar(100) DEFAULT NULL,
            os_version varchar(50) DEFAULT NULL,
            device_type varchar(50) DEFAULT NULL COMMENT 'desktop, mobile, tablet',
            device_model varchar(255) DEFAULT NULL,
            first_visit datetime DEFAULT CURRENT_TIMESTAMP,
            last_visit datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            visit_count int(11) DEFAULT 1,
            is_bot tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY ip_address (ip_address(45)),
            KEY country_code (country_code),
            KEY browser (browser(50)),
            KEY os (os(50)),
            KEY device_type (device_type),
            KEY first_visit (first_visit),
            KEY last_visit (last_visit),
            KEY is_bot (is_bot)
        ) $charset_collate;";

        // Visits table - برای ذخیره هر بازدید
        $table_visits = $wpdb->prefix . 'maneli_visits';
        $sql_visits = "CREATE TABLE IF NOT EXISTS $table_visits (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            visitor_id bigint(20) NOT NULL,
            page_url varchar(500) NOT NULL,
            page_title varchar(255) DEFAULT NULL,
            referrer varchar(500) DEFAULT NULL,
            referrer_domain varchar(255) DEFAULT NULL,
            search_engine varchar(50) DEFAULT NULL COMMENT 'google, bing, yahoo, etc.',
            search_keyword text DEFAULT NULL,
            visit_date datetime DEFAULT CURRENT_TIMESTAMP,
            session_id varchar(100) DEFAULT NULL,
            product_id bigint(20) DEFAULT NULL COMMENT 'اگر صفحه مربوط به محصول باشد',
            PRIMARY KEY (id),
            KEY visitor_id (visitor_id),
            KEY page_url (page_url(255)),
            KEY visit_date (visit_date),
            KEY session_id (session_id),
            KEY product_id (product_id),
            KEY referrer_domain (referrer_domain(100)),
            KEY search_engine (search_engine)
        ) $charset_collate;";

        // Pages table - آمار صفحات
        $table_pages = $wpdb->prefix . 'maneli_pages';
        $sql_pages = "CREATE TABLE IF NOT EXISTS $table_pages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            page_url varchar(500) NOT NULL,
            page_title varchar(255) DEFAULT NULL,
            visit_count int(11) DEFAULT 0,
            unique_visitors int(11) DEFAULT 0,
            last_visit datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY page_url (page_url(255)),
            KEY visit_count (visit_count),
            KEY last_visit (last_visit)
        ) $charset_collate;";

        // Search engines table - آمار موتورهای جستجو
        $table_search_engines = $wpdb->prefix . 'maneli_search_engines';
        $sql_search_engines = "CREATE TABLE IF NOT EXISTS $table_search_engines (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            engine_name varchar(50) NOT NULL,
            keyword text,
            visit_count int(11) DEFAULT 0,
            last_visit datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY engine_name (engine_name),
            KEY visit_count (visit_count),
            KEY last_visit (last_visit)
        ) $charset_collate;";

        // Referrers table - آمار ارجاع‌دهندگان
        $table_referrers = $wpdb->prefix . 'maneli_referrers';
        $sql_referrers = "CREATE TABLE IF NOT EXISTS $table_referrers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            referrer_url varchar(500) NOT NULL,
            referrer_domain varchar(255) NOT NULL,
            visit_count int(11) DEFAULT 0,
            unique_visitors int(11) DEFAULT 0,
            last_visit datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY referrer_domain (referrer_domain(100)),
            KEY visit_count (visit_count),
            KEY last_visit (last_visit)
        ) $charset_collate;";

        // System logs table - لاگ سیستم
        $table_system_logs = $wpdb->prefix . 'maneli_system_logs';
        $sql_system_logs = "CREATE TABLE IF NOT EXISTS $table_system_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            log_type varchar(50) NOT NULL COMMENT 'error, debug, console, button_error',
            severity varchar(20) DEFAULT 'info' COMMENT 'info, warning, error, critical',
            message text NOT NULL,
            context longtext DEFAULT NULL COMMENT 'JSON data',
            file varchar(500) DEFAULT NULL,
            line int(11) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY log_type (log_type),
            KEY severity (severity),
            KEY user_id (user_id),
            KEY created_at (created_at),
            KEY ip_address (ip_address(45))
        ) $charset_collate;";

        // User logs table - لاگ کاربر
        $table_user_logs = $wpdb->prefix . 'maneli_user_logs';
        $sql_user_logs = "CREATE TABLE IF NOT EXISTS $table_user_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action_type varchar(100) NOT NULL COMMENT 'button_click, form_submit, ajax_call, etc.',
            action_description varchar(500) NOT NULL,
            target_type varchar(100) DEFAULT NULL COMMENT 'inquiry, user, product, etc.',
            target_id bigint(20) DEFAULT NULL,
            metadata longtext DEFAULT NULL COMMENT 'JSON data',
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY target_type (target_type),
            KEY target_id (target_id),
            KEY created_at (created_at),
            KEY ip_address (ip_address(45))
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_inquiries);
        dbDelta($sql_followups);
        dbDelta($sql_notifications);
        dbDelta($sql_notification_logs);
        dbDelta($sql_notification_templates);
        dbDelta($sql_visitors);
        dbDelta($sql_visits);
        dbDelta($sql_pages);
        dbDelta($sql_search_engines);
        dbDelta($sql_referrers);
        dbDelta($sql_system_logs);
        dbDelta($sql_user_logs);
        
        // Schedule cron jobs
        if (!wp_next_scheduled('maneli_send_meeting_reminders')) {
            wp_schedule_event(time(), 'hourly', 'maneli_send_meeting_reminders');
        }
        
        if (!wp_next_scheduled('maneli_process_scheduled_notifications')) {
            wp_schedule_event(time(), 'hourly', 'maneli_process_scheduled_notifications');
        }
    }

    /**
     * Ensure tables exist without triggering other activation side effects
     */
    public static function ensure_tables() {
        self::create_tables();
    }

    /**
     * Maybe run database migrations or create newly introduced tables
     */
    public static function maybe_run_updates() {
        global $wpdb;

        $current_version = defined('MANELI_DB_VERSION') ? MANELI_DB_VERSION : MANELI_VERSION;
        $stored_version = get_option('maneli_db_version');

        // Check if any of the visitor statistics tables are missing
        $tables_to_check = [
            $wpdb->prefix . 'maneli_visitors',
            $wpdb->prefix . 'maneli_visits',
            $wpdb->prefix . 'maneli_pages',
            $wpdb->prefix . 'maneli_search_engines',
            $wpdb->prefix . 'maneli_referrers',
        ];

        $missing_tables = false;
        foreach ($tables_to_check as $table_name) {
            $result = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
            if ($result !== $table_name) {
                $missing_tables = true;
                break;
            }
        }

        if ($missing_tables || empty($stored_version) || version_compare($stored_version, $current_version, '<')) {
            self::create_tables();
            update_option('maneli_db_version', $current_version);
        }
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
