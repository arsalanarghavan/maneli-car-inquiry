<?php
/**
 * Notification Center Handler
 * Central handler for all notification types (SMS, Telegram, Email, In-app)
 *
 * @package Maneli_Car_Inquiry/Includes
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Notification_Center_Handler {

    /**
     * Send notification via specified channel(s)
     *
     * @param string|array $channels Channel(s) to use: 'sms', 'telegram', 'email', 'notification', or array of channels
     * @param string|array $recipient Recipient(s) - phone, chat_id, email, or user_id
     * @param string $message Message content
     * @param array $options Additional options (subject, pattern_id, scheduled_at, etc.)
     * @return array Results for each channel
     */
    public static function send($channels, $recipient, $message, $options = []) {
        // Normalize channels to array
        if (!is_array($channels)) {
            $channels = [$channels];
        }

        $results = [];
        $log_data = [
            'message' => $message,
            'related_id' => $options['related_id'] ?? null,
            'user_id' => $options['user_id'] ?? get_current_user_id(),
            'scheduled_at' => $options['scheduled_at'] ?? null,
        ];

        foreach ($channels as $channel) {
            $channel = strtolower(trim($channel));
            
            switch ($channel) {
                case 'sms':
                    $result = self::send_sms($recipient, $message, $options);
                    break;
                case 'telegram':
                    $result = self::send_telegram($recipient, $message, $options);
                    break;
                case 'email':
                    $result = self::send_email($recipient, $message, $options);
                    break;
                case 'notification':
                    $result = self::send_in_app_notification($recipient, $message, $options);
                    break;
                default:
                    $result = [
                        'success' => false,
                        'error' => 'Unknown channel: ' . $channel
                    ];
            }

            // Log the notification
            $log_data['type'] = $channel;
            $log_data['recipient'] = is_array($recipient) ? implode(',', $recipient) : $recipient;
            $log_data['status'] = $result['success'] ?? false ? 'sent' : 'failed';
            $log_data['error_message'] = $result['error'] ?? null;
            $log_data['sent_at'] = $result['success'] ?? false ? current_time('mysql') : null;

            Maneli_Database::log_notification($log_data);

            $results[$channel] = $result;
        }

        return $results;
    }

    /**
     * Send SMS
     */
    private static function send_sms($recipient, $message, $options = []) {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-sms-handler.php';
        
        $sms_handler = new Maneli_SMS_Handler();
        $pattern_id = $options['pattern_id'] ?? null;
        $parameters = $options['pattern_parameters'] ?? [];

        // Normalize recipient
        if (is_array($recipient)) {
            $recipient = $recipient[0]; // Use first recipient for SMS
        }

        // Reset error code before sending
        $sms_handler->reset_error_code();

        // If pattern ID is provided, use pattern SMS
        if (!empty($pattern_id) && !empty($parameters)) {
            $result = $sms_handler->send_pattern((int)$pattern_id, $recipient, $parameters);
        } else {
            // Use simple SMS
            $result = $sms_handler->send_sms($recipient, $message);
        }

        // Handle result - can be array with success/message_id or false
        $success = false;
        $message_id = null;
        
        if (is_array($result) && isset($result['success'])) {
            $success = $result['success'];
            $message_id = $result['message_id'] ?? null;
        } elseif ($result === true || $result === false) {
            // Backward compatibility for old code that might return bool
            $success = $result;
        }

        // Get error code if available
        $error_code = $sms_handler->get_last_error_code();
        $error_message = 'SMS sending failed';
        
        if (!$success && $error_code !== null) {
            $error_messages = [
                1 => esc_html__('Invalid username/password', 'maneli-car-inquiry'),
                2 => esc_html__('User is restricted/limited', 'maneli-car-inquiry'),
                3 => esc_html__('Credit insufficient', 'maneli-car-inquiry'),
                4 => esc_html__('Invalid pattern/bodyId', 'maneli-car-inquiry'),
                5 => esc_html__('Invalid recipient number', 'maneli-car-inquiry'),
                11 => esc_html__('Rate limit exceeded or service temporarily unavailable', 'maneli-car-inquiry'),
            ];
            $error_message = $error_messages[$error_code] ?? esc_html__('SMS sending failed', 'maneli-car-inquiry') . ' (Error code: ' . $error_code . ')';
        }

        return [
            'success' => $success,
            'message_id' => $message_id,
            'error' => $success ? null : $error_message
        ];
    }

    /**
     * Send Telegram
     */
    private static function send_telegram($recipient, $message, $options = []) {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-telegram-handler.php';
        
        $telegram_handler = new Maneli_Telegram_Handler();
        
        // Get chat IDs from options or use recipient
        $chat_ids = $options['chat_ids'] ?? $recipient;
        
        $result = $telegram_handler->send_message($chat_ids, $message);
        
        if (is_array($result)) {
            // Bulk result
            $success_count = count(array_filter($result, function($r) { return $r['success'] ?? false; }));
            return [
                'success' => $success_count > 0,
                'results' => $result,
                'error' => $success_count === 0 ? 'All Telegram messages failed' : null
            ];
        }
        
        return [
            'success' => $result,
            'error' => $result ? null : 'Telegram sending failed'
        ];
    }

    /**
     * Send Email
     */
    private static function send_email($recipient, $message, $options = []) {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-email-handler.php';
        
        $email_handler = new Maneli_Email_Handler();
        
        $subject = $options['subject'] ?? __('Notification', 'maneli-car-inquiry');
        $headers = $options['headers'] ?? [];
        
        $result = $email_handler->send_email($recipient, $subject, $message, $headers);
        
        if (is_array($result)) {
            // Bulk result
            $success_count = count(array_filter($result, function($r) { return $r['success'] ?? false; }));
            return [
                'success' => $success_count > 0,
                'results' => $result,
                'error' => $success_count === 0 ? 'All emails failed' : null
            ];
        }
        
        return [
            'success' => $result,
            'error' => $result ? null : 'Email sending failed'
        ];
    }

    /**
     * Send in-app notification
     */
    private static function send_in_app_notification($recipient, $message, $options = []) {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        
        // Recipient should be user_id or array of user_ids
        $user_ids = is_array($recipient) ? $recipient : [$recipient];
        
        $results = [];
        foreach ($user_ids as $user_id) {
            $notification_id = Maneli_Notification_Handler::create_notification([
                'user_id' => (int)$user_id,
                'type' => $options['notification_type'] ?? 'general',
                'title' => $options['title'] ?? __('Notification', 'maneli-car-inquiry'),
                'message' => $message,
                'link' => $options['link'] ?? '',
                'related_id' => $options['related_id'] ?? null,
            ]);
            
            $results[$user_id] = [
                'success' => $notification_id !== false,
                'notification_id' => $notification_id
            ];
        }
        
        $success_count = count(array_filter($results, function($r) { return $r['success']; }));
        
        return [
            'success' => $success_count > 0,
            'results' => $results,
            'error' => $success_count === 0 ? 'All notifications failed' : null
        ];
    }

    /**
     * Send bulk notifications
     *
     * @param string|array $channels
     * @param array $recipients Array of recipients
     * @param string $message
     * @param array $options
     * @return array Results
     */
    public static function send_bulk($channels, $recipients, $message, $options = []) {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $result = self::send($channels, $recipient, $message, $options);
            $results[] = [
                'recipient' => $recipient,
                'channels' => $result
            ];
        }
        
        return $results;
    }

    /**
     * Schedule notification for later
     *
     * @param string|array $channels
     * @param string|array $recipient
     * @param string $message
     * @param string $scheduled_at DateTime string
     * @param array $options
     * @return int|false Log ID on success
     */
    public static function schedule($channels, $recipient, $message, $scheduled_at, $options = []) {
        $options['scheduled_at'] = $scheduled_at;
        
        // Log as pending
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-database.php';
        
        $channels_array = is_array($channels) ? $channels : [$channels];
        $log_ids = [];
        
        foreach ($channels_array as $channel) {
            $log_data = [
                'type' => $channel,
                'recipient' => is_array($recipient) ? implode(',', $recipient) : $recipient,
                'message' => $message,
                'status' => 'pending',
                'scheduled_at' => $scheduled_at,
                'related_id' => $options['related_id'] ?? null,
                'user_id' => $options['user_id'] ?? get_current_user_id(),
            ];
            
            $log_id = Maneli_Database::log_notification($log_data);
            if ($log_id) {
                $log_ids[] = $log_id;
            }
        }
        
        return !empty($log_ids) ? $log_ids : false;
    }

    /**
     * Process scheduled notifications
     * Should be called by cron job
     */
    public static function process_scheduled() {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-database.php';
        
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_logs';
        
        // Get pending scheduled notifications that are due
        $now = current_time('mysql');
        $scheduled = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table 
            WHERE status = 'pending' 
            AND scheduled_at IS NOT NULL 
            AND scheduled_at <= %s
            ORDER BY scheduled_at ASC
            LIMIT 100
        ", $now));
        
        foreach ($scheduled as $notification) {
            $options = [
                'related_id' => $notification->related_id,
                'user_id' => $notification->user_id,
            ];
            
            // Parse recipient
            $recipient = $notification->recipient;
            if (strpos($recipient, ',') !== false) {
                $recipient = explode(',', $recipient);
            }
            
            // Send notification
            $result = self::send($notification->type, $recipient, $notification->message, $options);
            
            // Extract result for this specific channel
            $channel_result = $result[$notification->type] ?? $result;
            $success = $channel_result['success'] ?? false;
            $error = $channel_result['error'] ?? null;
            
            // Update log
            $update_data = [
                'status' => $success ? 'sent' : 'failed',
                'error_message' => $error,
                'sent_at' => current_time('mysql'),
            ];
            
            Maneli_Database::update_notification_log($notification->id, $update_data);
        }
        
        return count($scheduled);
    }
}

