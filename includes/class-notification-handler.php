<?php
/**
 * Notification Handler
 *
 * Handles creating, reading, and managing notifications for users
 *
 * @package Autopuzzle_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Notification_Handler {

    /**
     * Create a notification
     *
     * @param array $data Notification data
     * @return int|bool Notification ID on success, false on failure
     */
    public static function create_notification($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'autopuzzle_notifications';

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
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Notification: Missing required fields. user_id: ' . $data['user_id'] . ', type: ' . $data['type'] . ', title: ' . $data['title']);
            }
            return false;
        }

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
        if (!$table_exists) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Notification: Table does not exist: ' . $table);
            }
            return false;
        }

        $result = $wpdb->insert($table, array(
            'user_id' => (int)$data['user_id'],
            'type' => sanitize_text_field($data['type']),
            'title' => sanitize_text_field($data['title']),
            'message' => wp_kses_post($data['message']),
            'link' => esc_url_raw($data['link']),
            'related_id' => (int)$data['related_id'],
            'is_read' => (int)$data['is_read'],
        ), array('%d', '%s', '%s', '%s', '%s', '%d', '%d'));

        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Notification: Insert failed. Error: ' . $wpdb->last_error);
            }
            return false;
        }

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
        $table = $wpdb->prefix . 'autopuzzle_notifications';

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

        // Validate user_id
        if (empty($args['user_id']) || $args['user_id'] <= 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Notification: get_notifications called with invalid user_id: ' . $args['user_id']);
            }
            return array();
        }

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
        if (!$table_exists) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Notification: Table does not exist: ' . $table);
            }
            return array();
        }

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

        $results = $wpdb->get_results($wpdb->prepare($query, $prepare));
        
        if ($wpdb->last_error && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AutoPuzzle Notification: get_notifications query error: ' . $wpdb->last_error);
        }

        return $results ? $results : array();
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

        if (!$user_id || $user_id <= 0) {
            return 0;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'autopuzzle_notifications';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
        if (!$table_exists) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Notification: Table does not exist for unread count: ' . $table);
            }
            return 0;
        }

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0",
            $user_id
        ));

        if ($wpdb->last_error && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AutoPuzzle Notification: get_unread_count query error: ' . $wpdb->last_error);
        }

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
        $table = $wpdb->prefix . 'autopuzzle_notifications';

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
        $table = $wpdb->prefix . 'autopuzzle_notifications';

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
        $table = $wpdb->prefix . 'autopuzzle_notifications';

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
        $table = $wpdb->prefix . 'autopuzzle_notifications';

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
        $inquiry = Autopuzzle_Database::get_inquiry($inquiry_id);
        if (!$inquiry) {
            return false;
        }

        // Get all managers and admins
        $managers = get_users(array(
            'role__in' => array('administrator', 'autopuzzle_admin'),
            'fields' => 'ids'
        ));

        foreach ($managers as $manager_id) {
            $inquiry_type_label = $inquiry->type === 'cash' ? esc_html__('Cash', 'autopuzzle') : esc_html__('Installment', 'autopuzzle');
            
            self::create_notification(array(
                'user_id' => $manager_id,
                'type' => 'inquiry_new',
                'title' => sprintf(esc_html__('New %s inquiry', 'autopuzzle'), $inquiry_type_label),
                'message' => sprintf(esc_html__('A new %s inquiry has been registered', 'autopuzzle'), $inquiry_type_label),
                'link' => admin_url('admin.php?page=autopuzzle_inquiries&inquiry_id=' . $inquiry_id),
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
        $inquiry = Autopuzzle_Database::get_inquiry($inquiry_id);
        if (!$inquiry) {
            return false;
        }

        $inquiry_type_label = $inquiry->type === 'cash' ? esc_html__('Cash', 'autopuzzle') : esc_html__('Installment', 'autopuzzle');

        return self::create_notification(array(
            'user_id' => $expert_id,
            'type' => 'inquiry_assigned',
            'title' => sprintf(esc_html__('%s inquiry has been assigned to you', 'autopuzzle'), $inquiry_type_label),
            'message' => sprintf(esc_html__('A %s inquiry has been assigned to you. Please review it.', 'autopuzzle'), $inquiry_type_label),
            'link' => admin_url('admin.php?page=autopuzzle_inquiries&inquiry_id=' . $inquiry_id),
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
        $inquiry = Autopuzzle_Database::get_inquiry($inquiry_id);
        if (!$inquiry) {
            return false;
        }

        $status_labels = array(
            'pending' => esc_html__('Pending', 'autopuzzle'),
            'under_review' => esc_html__('Under Review', 'autopuzzle'),
            'approved' => esc_html__('Approved', 'autopuzzle'),
            'rejected' => esc_html__('Rejected', 'autopuzzle'),
            'completed' => esc_html__('Completed', 'autopuzzle'),
            'cancelled' => esc_html__('Cancelled', 'autopuzzle'),
        );

        $old_label = isset($status_labels[$old_status]) ? $status_labels[$old_status] : $old_status;
        $new_label = isset($status_labels[$new_status]) ? $status_labels[$new_status] : $new_status;

        return self::create_notification(array(
            'user_id' => $inquiry->user_id,
            'type' => 'inquiry_status_changed',
            'title' => esc_html__('Your request status has changed', 'autopuzzle'),
            'message' => sprintf(esc_html__('Your request status has changed from "%s" to "%s".', 'autopuzzle'), $old_label, $new_label),
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
                esc_html__('Follow-up for %s inquiry is due in %d day(s)', 'autopuzzle'),
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
        $table = $wpdb->prefix . 'autopuzzle_notifications';
        
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
            return esc_html__('⚠️ Overdue Follow-up', 'autopuzzle');
        } elseif ($days_diff === 0) {
            return esc_html__('🔥 Follow-up Due Today', 'autopuzzle');
        } elseif ($days_diff === 1) {
            return esc_html__('⚠️ Follow-up Tomorrow', 'autopuzzle');
        } elseif ($days_diff === 2) {
            return esc_html__('⚠️ Follow-up in 2 Days', 'autopuzzle');
        } elseif ($days_diff === 3) {
            return esc_html__('ℹ️ Follow-up in 3 Days', 'autopuzzle');
        }
        
        return esc_html__('Follow-up Reminder', 'autopuzzle');
    }

    /**
     * Notify users about cash inquiry status change
     *
     * @param int $inquiry_id Cash inquiry post ID
     * @param string $old_status Old status
     * @param string $new_status New status
     * @return bool Success status
     */
    public static function notify_cash_status_change($inquiry_id, $old_status, $new_status) {
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            return false;
        }

        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-cpt-handler.php';
        
        $post = get_post($inquiry_id);
        if (!$post) {
            return false;
        }

        $customer_id = $post->post_author;
        $assigned_expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $product_name = get_the_title($product_id);
        $customer_first_name = get_post_meta($inquiry_id, 'cash_first_name', true);
        $customer_last_name = get_post_meta($inquiry_id, 'cash_last_name', true);
        $customer_name = trim($customer_first_name . ' ' . $customer_last_name);

        $old_label = Autopuzzle_CPT_Handler::get_cash_inquiry_status_label($old_status);
        $new_label = Autopuzzle_CPT_Handler::get_cash_inquiry_status_label($new_status);
        
        $cash_inquiry_url = add_query_arg('cash_inquiry_id', $inquiry_id, home_url('/dashboard/cash-inquiries'));

        // Notify customer
        if ($customer_id) {
            self::create_notification([
                'user_id' => $customer_id,
                'type' => 'cash_status_changed',
                'title' => esc_html__('Your cash request status has changed', 'autopuzzle'),
                'message' => sprintf(
                    esc_html__('Your cash request for %s status changed from "%s" to "%s"', 'autopuzzle'),
                    $product_name,
                    $old_label,
                    $new_label
                ),
                'link' => $cash_inquiry_url,
                'related_id' => $inquiry_id,
            ]);
        }

        // Notify assigned expert
        if ($assigned_expert_id) {
            self::create_notification([
                'user_id' => $assigned_expert_id,
                'type' => 'cash_status_changed',
                'title' => sprintf(esc_html__('Cash request status changed', 'autopuzzle')),
                'message' => sprintf(
                    esc_html__('Cash request from %s for %s status changed from "%s" to "%s"', 'autopuzzle'),
                    $customer_name,
                    $product_name,
                    $old_label,
                    $new_label
                ),
                'link' => $cash_inquiry_url,
                'related_id' => $inquiry_id,
            ]);
        }

        // Notify all admins and managers for important status changes
        $important_statuses = ['approved', 'rejected', 'completed'];
        if (in_array($new_status, $important_statuses, true)) {
            $managers = get_users([
                'role__in' => ['administrator', 'autopuzzle_admin'],
                'fields' => 'ids'
            ]);

            foreach ($managers as $manager_id) {
                self::create_notification([
                    'user_id' => $manager_id,
                    'type' => 'cash_status_changed',
                    'title' => sprintf(esc_html__('Cash request status changed', 'autopuzzle')),
                    'message' => sprintf(
                        esc_html__('Cash request from %s for %s status changed to "%s"', 'autopuzzle'),
                        $customer_name,
                        $product_name,
                        $new_label
                    ),
                    'link' => $cash_inquiry_url,
                    'related_id' => $inquiry_id,
                ]);
            }
        }

        return true;
    }

    /**
     * Notify users about installment inquiry status change
     *
     * @param int $inquiry_id Installment inquiry post ID
     * @param string $old_status Old status (can be inquiry_status or tracking_status)
     * @param string $new_status New status
     * @param string $status_type Type: 'inquiry_status' or 'tracking_status'
     * @return bool Success status
     */
    public static function notify_installment_status_change($inquiry_id, $old_status, $new_status, $status_type = 'tracking_status') {
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'inquiry') {
            return false;
        }

        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-cpt-handler.php';
        
        $post = get_post($inquiry_id);
        if (!$post) {
            return false;
        }

        $customer_id = $post->post_author;
        $assigned_expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $product_name = get_the_title($product_id);
        
        $customer_first_name = get_post_meta($inquiry_id, 'first_name', true);
        $customer_last_name = get_post_meta($inquiry_id, 'last_name', true);
        if (empty($customer_first_name)) {
            $user = get_userdata($customer_id);
            if ($user) {
                $customer_first_name = $user->first_name;
                $customer_last_name = $user->last_name;
            }
        }
        $customer_name = trim($customer_first_name . ' ' . $customer_last_name);

        // Get appropriate status labels based on status type
        if ($status_type === 'tracking_status') {
            $old_label = Autopuzzle_CPT_Handler::get_tracking_status_label($old_status);
            $new_label = Autopuzzle_CPT_Handler::get_tracking_status_label($new_status);
            $notification_type = 'installment_tracking_changed';
        } else {
            $old_label = Autopuzzle_CPT_Handler::get_status_label($old_status);
            $new_label = Autopuzzle_CPT_Handler::get_status_label($new_status);
            $notification_type = 'installment_status_changed';
        }
        
        $installment_inquiry_url = add_query_arg('inquiry_id', $inquiry_id, home_url('/dashboard/installment-inquiries'));

        // Notify customer
        if ($customer_id) {
            self::create_notification([
                'user_id' => $customer_id,
                'type' => $notification_type,
                'title' => esc_html__('Your installment request status has changed', 'autopuzzle'),
                'message' => sprintf(
                    esc_html__('Your installment request for %s status changed from "%s" to "%s"', 'autopuzzle'),
                    $product_name,
                    $old_label,
                    $new_label
                ),
                'link' => $installment_inquiry_url,
                'related_id' => $inquiry_id,
            ]);
        }

        // Notify assigned expert
        if ($assigned_expert_id) {
            self::create_notification([
                'user_id' => $assigned_expert_id,
                'type' => $notification_type,
                'title' => sprintf(esc_html__('Installment request status changed', 'autopuzzle')),
                'message' => sprintf(
                    esc_html__('Installment request from %s for %s status changed from "%s" to "%s"', 'autopuzzle'),
                    $customer_name,
                    $product_name,
                    $old_label,
                    $new_label
                ),
                'link' => $installment_inquiry_url,
                'related_id' => $inquiry_id,
            ]);
        }

        // Notify all admins and managers for important status changes
        $important_statuses = ['approved', 'rejected', 'completed', 'user_confirmed'];
        if (in_array($new_status, $important_statuses, true)) {
            $managers = get_users([
                'role__in' => ['administrator', 'autopuzzle_admin'],
                'fields' => 'ids'
            ]);

            foreach ($managers as $manager_id) {
                self::create_notification([
                    'user_id' => $manager_id,
                    'type' => $notification_type,
                    'title' => sprintf(esc_html__('Installment request status changed', 'autopuzzle')),
                    'message' => sprintf(
                        esc_html__('Installment request from %s for %s status changed to "%s"', 'autopuzzle'),
                        $customer_name,
                        $product_name,
                        $new_label
                    ),
                    'link' => $installment_inquiry_url,
                    'related_id' => $inquiry_id,
                ]);
            }
        }

        return true;
    }

    /**
     * Notify expert/admin when customer uploads a document
     *
     * @param int $inquiry_id Inquiry post ID (cash_inquiry or inquiry)
     * @param string $document_name Document name
     * @return bool Success status
     */
    public static function notify_document_uploaded($inquiry_id, $document_name) {
        if (!$inquiry_id) {
            return false;
        }

        $post_type = get_post_type($inquiry_id);
        if (!in_array($post_type, ['cash_inquiry', 'inquiry'])) {
            return false;
        }

        $post = get_post($inquiry_id);
        if (!$post) {
            return false;
        }

        $customer_id = $post->post_author;
        $assigned_expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $product_name = get_the_title($product_id);

        // Get customer name
        if ($post_type === 'cash_inquiry') {
            $customer_first_name = get_post_meta($inquiry_id, 'cash_first_name', true);
            $customer_last_name = get_post_meta($inquiry_id, 'cash_last_name', true);
            $inquiry_url = add_query_arg('cash_inquiry_id', $inquiry_id, home_url('/dashboard/cash-inquiries'));
            $inquiry_type = esc_html__('Cash', 'autopuzzle');
        } else {
            $customer_first_name = get_post_meta($inquiry_id, 'first_name', true);
            $customer_last_name = get_post_meta($inquiry_id, 'last_name', true);
            if (empty($customer_first_name)) {
                $user = get_userdata($customer_id);
                if ($user) {
                    $customer_first_name = $user->first_name;
                    $customer_last_name = $user->last_name;
                }
            }
            $inquiry_url = add_query_arg('inquiry_id', $inquiry_id, home_url('/dashboard/installment-inquiries'));
            $inquiry_type = esc_html__('Installment', 'autopuzzle');
        }
        $customer_name = trim($customer_first_name . ' ' . $customer_last_name);

        // Notify assigned expert
        if ($assigned_expert_id) {
            self::create_notification([
                'user_id' => $assigned_expert_id,
                'type' => 'document_uploaded',
                'title' => sprintf(esc_html__('New document uploaded', 'autopuzzle')),
                'message' => sprintf(
                    esc_html__('%s request from %s: Document "%s" has been uploaded', 'autopuzzle'),
                    $inquiry_type,
                    $customer_name,
                    $document_name
                ),
                'link' => $inquiry_url,
                'related_id' => $inquiry_id,
            ]);
        }

        // Notify all admins and managers
        $managers = get_users([
            'role__in' => ['administrator', 'autopuzzle_admin'],
            'fields' => 'ids'
        ]);

        foreach ($managers as $manager_id) {
            self::create_notification([
                'user_id' => $manager_id,
                'type' => 'document_uploaded',
                'title' => sprintf(esc_html__('New document uploaded', 'autopuzzle')),
                'message' => sprintf(
                    esc_html__('%s request from %s: Document "%s" has been uploaded', 'autopuzzle'),
                    $inquiry_type,
                    $customer_name,
                    $document_name
                ),
                'link' => $inquiry_url,
                'related_id' => $inquiry_id,
            ]);
        }

        return true;
    }

    /**
     * Notify customer when their document is approved
     *
     * @param int $user_id Customer user ID
     * @param string $document_name Document name
     * @param int|null $inquiry_id Optional inquiry ID if related to an inquiry
     * @return bool Success status
     */
    public static function notify_document_approved($user_id, $document_name, $inquiry_id = null) {
        if (!$user_id) {
            return false;
        }

        $link = home_url('/dashboard/profile-settings');
        if ($inquiry_id) {
            $post_type = get_post_type($inquiry_id);
            if ($post_type === 'cash_inquiry') {
                $link = add_query_arg('cash_inquiry_id', $inquiry_id, home_url('/dashboard/cash-inquiries'));
            } elseif ($post_type === 'inquiry') {
                $link = add_query_arg('inquiry_id', $inquiry_id, home_url('/dashboard/installment-inquiries'));
            }
        }

        return self::create_notification([
            'user_id' => $user_id,
            'type' => 'document_approved',
            'title' => esc_html__('Document approved', 'autopuzzle'),
            'message' => sprintf(
                esc_html__('Your document "%s" has been approved', 'autopuzzle'),
                $document_name
            ),
            'link' => $link,
            'related_id' => $inquiry_id ? $inquiry_id : 0,
        ]);
    }

    /**
     * Notify customer when their document is rejected
     *
     * @param int $user_id Customer user ID
     * @param string $document_name Document name
     * @param string|null $rejection_reason Optional rejection reason
     * @param int|null $inquiry_id Optional inquiry ID if related to an inquiry
     * @return bool Success status
     */
    public static function notify_document_rejected($user_id, $document_name, $rejection_reason = null, $inquiry_id = null) {
        if (!$user_id) {
            return false;
        }

        $link = home_url('/dashboard/profile-settings');
        if ($inquiry_id) {
            $post_type = get_post_type($inquiry_id);
            if ($post_type === 'cash_inquiry') {
                $link = add_query_arg('cash_inquiry_id', $inquiry_id, home_url('/dashboard/cash-inquiries'));
            } elseif ($post_type === 'inquiry') {
                $link = add_query_arg('inquiry_id', $inquiry_id, home_url('/dashboard/installment-inquiries'));
            }
        }

        $message = sprintf(
            esc_html__('Your document "%s" has been rejected', 'autopuzzle'),
            $document_name
        );
        
        if ($rejection_reason) {
            $message .= '. ' . esc_html__('Reason:', 'autopuzzle') . ' ' . esc_html($rejection_reason);
        }

        return self::create_notification([
            'user_id' => $user_id,
            'type' => 'document_rejected',
            'title' => esc_html__('Document rejected', 'autopuzzle'),
            'message' => $message,
            'link' => $link,
            'related_id' => $inquiry_id ? $inquiry_id : 0,
        ]);
    }

    /**
     * Notify customer when a document is requested
     *
     * @param int $user_id Customer user ID
     * @param string $document_name Name of the requested document
     * @param int|null $inquiry_id Optional inquiry ID
     * @return bool Success status
     */
    public static function notify_document_requested($user_id, $document_name, $inquiry_id = null) {
        if (!$user_id || !$document_name) {
            return false;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $mobile = get_user_meta($user_id, 'mobile_number', true);
        if (!$mobile) {
            return false;
        }

        // Determine inquiry type and get URLs
        $link = home_url('/dashboard/profile-settings');
        if ($inquiry_id) {
            $post_type = get_post_type($inquiry_id);
            if ($post_type === 'cash_inquiry') {
                $link = add_query_arg('cash_inquiry_id', $inquiry_id, home_url('/dashboard/cash-inquiries'));
            } else {
                $link = add_query_arg('view_inquiry', $inquiry_id, home_url('/dashboard/installment-inquiries'));
            }
        }

        // Send SMS notification
        $message = sprintf(
            esc_html__('Please upload the document "%s" in your profile.', 'autopuzzle'),
            $document_name
        );
        self::send_sms_notification($mobile, $message);

        // Create in-app notification
        self::create_notification($user_id, [
            'type' => 'document_requested',
            'title' => esc_html__('Document requested', 'autopuzzle'),
            'message' => sprintf(
                esc_html__('Please upload the document "%s".', 'autopuzzle'),
                $document_name
            ),
            'link' => $link,
            'related_id' => $inquiry_id ? $inquiry_id : 0,
        ]);
    }

    /**
     * Notify expert and customer when expert is assigned to an inquiry
     *
     * @param int $inquiry_id Inquiry post ID (cash_inquiry or inquiry)
     * @param int $expert_id Expert user ID
     * @return bool Success status
     */
    public static function notify_expert_assigned($inquiry_id, $expert_id) {
        if (!$inquiry_id || !$expert_id) {
            return false;
        }

        $post_type = get_post_type($inquiry_id);
        if (!in_array($post_type, ['cash_inquiry', 'inquiry'])) {
            return false;
        }

        $post = get_post($inquiry_id);
        if (!$post) {
            return false;
        }

        $customer_id = $post->post_author;
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $product_name = get_the_title($product_id);
        $expert = get_userdata($expert_id);
        $expert_name = $expert ? $expert->display_name : esc_html__('Expert', 'autopuzzle');

        // Get customer name
        if ($post_type === 'cash_inquiry') {
            $customer_first_name = get_post_meta($inquiry_id, 'cash_first_name', true);
            $customer_last_name = get_post_meta($inquiry_id, 'cash_last_name', true);
            $expert_url = add_query_arg('cash_inquiry_id', $inquiry_id, home_url('/dashboard/cash-inquiries'));
            $customer_url = add_query_arg('cash_inquiry_id', $inquiry_id, home_url('/dashboard/cash-inquiries'));
            $inquiry_type = esc_html__('Cash', 'autopuzzle');
        } else {
            $customer_first_name = get_post_meta($inquiry_id, 'first_name', true);
            $customer_last_name = get_post_meta($inquiry_id, 'last_name', true);
            if (empty($customer_first_name)) {
                $user = get_userdata($customer_id);
                if ($user) {
                    $customer_first_name = $user->first_name;
                    $customer_last_name = $user->last_name;
                }
            }
            $expert_url = add_query_arg('inquiry_id', $inquiry_id, home_url('/dashboard/installment-inquiries'));
            $customer_url = add_query_arg('inquiry_id', $inquiry_id, home_url('/dashboard/installment-inquiries'));
            $inquiry_type = esc_html__('Installment', 'autopuzzle');
        }
        $customer_name = trim($customer_first_name . ' ' . $customer_last_name);

        // Notify expert
        self::create_notification([
            'user_id' => $expert_id,
            'type' => 'inquiry_assigned',
            'title' => sprintf(esc_html__('%s request assigned to you', 'autopuzzle'), $inquiry_type),
            'message' => sprintf(
                esc_html__('A %s request from %s for %s has been assigned to you', 'autopuzzle'),
                $inquiry_type,
                $customer_name,
                $product_name
            ),
            'link' => $expert_url,
            'related_id' => $inquiry_id,
        ]);

        // Notify customer
        if ($customer_id) {
            self::create_notification([
                'user_id' => $customer_id,
                'type' => 'expert_assigned',
                'title' => esc_html__('Expert assigned to your request', 'autopuzzle'),
                'message' => sprintf(
                    esc_html__('Expert %s has been assigned to your %s request for %s', 'autopuzzle'),
                    $expert_name,
                    strtolower($inquiry_type),
                    $product_name
                ),
                'link' => $customer_url,
                'related_id' => $inquiry_id,
            ]);
        }

        return true;
    }
}

