<?php
/**
 * Notification Handler
 *
 * Handles creating, reading, and managing notifications for users
 *
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Notification_Handler {

    /**
     * Create a notification
     *
     * @param array $data Notification data
     * @return int|bool Notification ID on success, false on failure
     */
    public static function create_notification($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notifications';

        $defaults = array(
            'user_id' => 0,
            'type' => '',
            'title' => '',
            'message' => '',
            'link' => '',
            'related_id' => 0,
            'is_read' => 0,
        );

        $data = wp_parse_args($data, $defaults);

        // Validate required fields
        if (empty($data['user_id']) || empty($data['type']) || empty($data['title'])) {
            return false;
        }

        $result = $wpdb->insert($table, $data);

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get notifications for a user
     *
     * @param array $args Query arguments
     * @return array Array of notification objects
     */
    public static function get_notifications($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notifications';

        $defaults = array(
            'user_id' => get_current_user_id(),
            'is_read' => '', // empty string means all
            'type' => '',
            'limit' => 50,
            'offset' => 0,
            'order_by' => 'created_at',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('user_id = %d');
        $prepare = array($args['user_id']);

        if ($args['is_read'] !== '') {
            $where[] = 'is_read = %d';
            $prepare[] = $args['is_read'];
        }

        if (!empty($args['type'])) {
            $where[] = 'type = %s';
            $prepare[] = $args['type'];
        }

        $where_clause = implode(' AND ', $where);

        $order_by = sanitize_sql_orderby($args['order_by'] . ' ' . $args['order']);
        if (!$order_by) {
            $order_by = 'created_at DESC';
        }

        $query = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$order_by} LIMIT %d OFFSET %d";
        $prepare[] = $args['limit'];
        $prepare[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($query, $prepare));
    }

    /**
     * Get unread notification count for a user
     *
     * @param int $user_id User ID
     * @return int Unread count
     */
    public static function get_unread_count($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return 0;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notifications';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0",
            $user_id
        ));

        return intval($count);
    }

    /**
     * Mark notification as read
     *
     * @param int $notification_id Notification ID
     * @param int $user_id User ID (for security)
     * @return bool Success status
     */
    public static function mark_as_read($notification_id, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notifications';

        return $wpdb->update(
            $table,
            array('is_read' => 1),
            array(
                'id' => $notification_id,
                'user_id' => $user_id
            ),
            array('%d'),
            array('%d', '%d')
        ) !== false;
    }

    /**
     * Mark all notifications as read for a user
     *
     * @param int $user_id User ID
     * @return bool Success status
     */
    public static function mark_all_as_read($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notifications';

        return $wpdb->update(
            $table,
            array('is_read' => 1),
            array('user_id' => $user_id, 'is_read' => 0),
            array('%d'),
            array('%d', '%d')
        ) !== false;
    }

    /**
     * Delete notification
     *
     * @param int $notification_id Notification ID
     * @param int $user_id User ID (for security)
     * @return bool Success status
     */
    public static function delete_notification($notification_id, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notifications';

        return $wpdb->delete(
            $table,
            array(
                'id' => $notification_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        ) !== false;
    }

    /**
     * Delete all read notifications for a user
     *
     * @param int $user_id User ID
     * @return bool Success status
     */
    public static function delete_all_read($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notifications';

        return $wpdb->delete(
            $table,
            array(
                'user_id' => $user_id,
                'is_read' => 1
            ),
            array('%d', '%d')
        ) !== false;
    }

    /**
     * Create notification for new inquiry (for managers)
     *
     * @param int $inquiry_id Inquiry ID
     * @return int|bool Notification ID or false
     */
    public static function notify_new_inquiry($inquiry_id) {
        $inquiry = Maneli_Database::get_inquiry($inquiry_id);
        if (!$inquiry) {
            return false;
        }

        // Get all managers and admins
        $managers = get_users(array(
            'role__in' => array('administrator', 'maneli_admin'),
            'fields' => 'ids'
        ));

        foreach ($managers as $manager_id) {
            $inquiry_type_label = $inquiry->type === 'cash' ? esc_html__('Cash', 'maneli-car-inquiry') : esc_html__('Installment', 'maneli-car-inquiry');
            
            self::create_notification(array(
                'user_id' => $manager_id,
                'type' => 'inquiry_new',
                'title' => sprintf(esc_html__('New %s inquiry', 'maneli-car-inquiry'), $inquiry_type_label),
                'message' => sprintf(esc_html__('A new %s inquiry has been registered', 'maneli-car-inquiry'), $inquiry_type_label),
                'link' => admin_url('admin.php?page=maneli_inquiries&inquiry_id=' . $inquiry_id),
                'related_id' => $inquiry_id,
            ));
        }

        return true;
    }

    /**
     * Create notification for inquiry assignment (for experts)
     *
     * @param int $inquiry_id Inquiry ID
     * @param int $expert_id Expert ID
     * @return int|bool Notification ID or false
     */
    public static function notify_inquiry_assigned($inquiry_id, $expert_id) {
        $inquiry = Maneli_Database::get_inquiry($inquiry_id);
        if (!$inquiry) {
            return false;
        }

        $inquiry_type_label = $inquiry->type === 'cash' ? esc_html__('Cash', 'maneli-car-inquiry') : esc_html__('Installment', 'maneli-car-inquiry');

        return self::create_notification(array(
            'user_id' => $expert_id,
            'type' => 'inquiry_assigned',
            'title' => sprintf(esc_html__('%s inquiry has been assigned to you', 'maneli-car-inquiry'), $inquiry_type_label),
            'message' => sprintf(esc_html__('A %s inquiry has been assigned to you. Please review it.', 'maneli-car-inquiry'), $inquiry_type_label),
            'link' => admin_url('admin.php?page=maneli_inquiries&inquiry_id=' . $inquiry_id),
            'related_id' => $inquiry_id,
        ));
    }

    /**
     * Create notification for inquiry status change (for customers)
     *
     * @param int $inquiry_id Inquiry ID
     * @param string $old_status Old status
     * @param string $new_status New status
     * @return int|bool Notification ID or false
     */
    public static function notify_status_change($inquiry_id, $old_status, $new_status) {
        $inquiry = Maneli_Database::get_inquiry($inquiry_id);
        if (!$inquiry) {
            return false;
        }

        $status_labels = array(
            'pending' => esc_html__('Pending', 'maneli-car-inquiry'),
            'under_review' => esc_html__('Under Review', 'maneli-car-inquiry'),
            'approved' => esc_html__('Approved', 'maneli-car-inquiry'),
            'rejected' => esc_html__('Rejected', 'maneli-car-inquiry'),
            'completed' => esc_html__('Completed', 'maneli-car-inquiry'),
            'cancelled' => esc_html__('Cancelled', 'maneli-car-inquiry'),
        );

        $old_label = isset($status_labels[$old_status]) ? $status_labels[$old_status] : $old_status;
        $new_label = isset($status_labels[$new_status]) ? $status_labels[$new_status] : $new_status;

        return self::create_notification(array(
            'user_id' => $inquiry->user_id,
            'type' => 'inquiry_status_changed',
            'title' => esc_html__('Your request status has changed', 'maneli-car-inquiry'),
            'message' => sprintf(esc_html__('Your request status has changed from "%s" to "%s".', 'maneli-car-inquiry'), $old_label, $new_label),
            'link' => home_url('/dashboard?page=my-inquiries&inquiry_id=' . $inquiry_id),
            'related_id' => $inquiry_id,
        ));
    }

    /**
     * Schedule followup reminder notifications
     * This should be called daily via WordPress cron
     *
     * @return int Number of notifications created
     */
    public static function schedule_followup_notifications() {
        $today = current_time('Y-m-d');
        $notifications_created = 0;

        // Check both cash and installment inquiries with scheduled followups
        $followup_inquiries = get_posts(array(
            'post_type' => array('inquiry', 'cash_inquiry'),
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'followup_date',
                    'value' => '',
                    'compare' => '!='
                ),
                array(
                    'key' => 'tracking_status',
                    'value' => 'follow_up_scheduled',
                    'compare' => '='
                ),
                array(
                    'key' => 'cash_inquiry_status',
                    'value' => 'follow_up_scheduled',
                    'compare' => '='
                )
            )
        ));

        foreach ($followup_inquiries as $inquiry) {
            $followup_date = get_post_meta($inquiry->ID, 'followup_date', true);
            
            if (!$followup_date) {
                continue;
            }

            // Calculate days until followup
            $days_diff = (strtotime($followup_date) - strtotime($today)) / (24 * 60 * 60);
            
            // Get assigned expert
            $assigned_expert_id = get_post_meta($inquiry->ID, 'assigned_expert_id', true);
            
            if (!$assigned_expert_id) {
                continue;
            }

            // Check if notification already exists for this inquiry and day
            $notification_type = self::get_notification_type_for_day($days_diff);
            if (!$notification_type) {
                continue;
            }

            // Check if notification already sent
            if (self::notification_already_sent($inquiry->ID, $assigned_expert_id, $notification_type)) {
                continue;
            }

            // Create notification
            $inquiry_type = ($inquiry->post_type === 'cash_inquiry') ? 'cash' : 'installment';
            $product_id = get_post_meta($inquiry->ID, 'product_id', true);
            $product_name = get_the_title($product_id);
            
            $title = self::get_followup_title($days_diff);
            $message = sprintf(
                esc_html__('Follow-up for %s inquiry is due in %d day(s)', 'maneli-car-inquiry'),
                $product_name,
                abs($days_diff)
            );
            
            $link = ($inquiry_type === 'cash') 
                ? add_query_arg('cash_inquiry_id', $inquiry->ID, home_url('/dashboard/cash-inquiries'))
                : add_query_arg('inquiry_id', $inquiry->ID, home_url('/dashboard/installment-inquiries'));

            self::create_notification(array(
                'user_id' => $assigned_expert_id,
                'type' => $notification_type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'related_id' => $inquiry->ID,
            ));
            
            $notifications_created++;
        }

        return $notifications_created;
    }

    /**
     * Get notification type based on days until followup
     *
     * @param int $days_diff Days until followup (can be negative for overdue)
     * @return string|false Notification type or false if no notification needed
     */
    private static function get_notification_type_for_day($days_diff) {
        if ($days_diff < -1) {
            return 'followup_overdue'; // More than 1 day overdue
        } elseif ($days_diff === -1) {
            return 'followup_overdue'; // 1 day overdue
        } elseif ($days_diff === 0) {
            return 'followup_today'; // Today
        } elseif ($days_diff === 1) {
            return 'followup_reminder_1day'; // 1 day before
        } elseif ($days_diff === 2) {
            return 'followup_reminder_2days'; // 2 days before
        } elseif ($days_diff === 3) {
            return 'followup_reminder_3days'; // 3 days before
        }
        
        return false;
    }

    /**
     * Check if notification already sent
     *
     * @param int $inquiry_id Inquiry ID
     * @param int $expert_id Expert ID
     * @param string $type Notification type
     * @return bool True if already sent
     */
    private static function notification_already_sent($inquiry_id, $expert_id, $type) {
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notifications';
        
        // Check if notification was created today
        $today = current_time('Y-m-d 00:00:00');
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
            WHERE user_id = %d 
            AND related_id = %d 
            AND type = %s 
            AND created_at >= %s",
            $expert_id,
            $inquiry_id,
            $type,
            $today
        ));
        
        return $count > 0;
    }

    /**
     * Get followup notification title based on days
     *
     * @param int $days_diff Days until followup
     * @return string Title
     */
    private static function get_followup_title($days_diff) {
        if ($days_diff < 0) {
            return esc_html__('⚠️ Overdue Follow-up', 'maneli-car-inquiry');
        } elseif ($days_diff === 0) {
            return esc_html__('🔥 Follow-up Due Today', 'maneli-car-inquiry');
        } elseif ($days_diff === 1) {
            return esc_html__('⚠️ Follow-up Tomorrow', 'maneli-car-inquiry');
        } elseif ($days_diff === 2) {
            return esc_html__('⚠️ Follow-up in 2 Days', 'maneli-car-inquiry');
        } elseif ($days_diff === 3) {
            return esc_html__('ℹ️ Follow-up in 3 Days', 'maneli-car-inquiry');
        }
        
        return esc_html__('Follow-up Reminder', 'maneli-car-inquiry');
    }
}

