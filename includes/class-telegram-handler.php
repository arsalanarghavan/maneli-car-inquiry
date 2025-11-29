<?php
/**
 * Telegram Handler
 * Handles sending messages via Telegram Bot API
 *
 * @package Maneli_Car_Inquiry/Includes
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Telegram_Handler {

    /**
     * Plugin options array.
     * @var array
     */
    private $options;

    public function __construct() {
        $this->options = Maneli_Options_Helper::get_all_options();
    }

    /**
     * Send message via Telegram
     *
     * @param string|array $chat_ids Chat ID(s) - can be single ID or array of IDs
     * @param string $message Message to send
     * @return bool|array True on success, false on failure, or array with results for bulk
     */
    public function send_message($chat_ids, $message) {
        $bot_token = $this->options['telegram_bot_token'] ?? '';
        
        if (empty($bot_token)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli Telegram Error: Bot token is not configured.');
            }
            return false;
        }

        if (empty($message)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli Telegram Error: Message is empty.');
            }
            return false;
        }

        // If single chat ID, convert to array
        if (!is_array($chat_ids)) {
            $chat_ids = [$chat_ids];
        }

        $api_url = 'https://api.telegram.org/bot' . $bot_token . '/sendMessage';
        $results = [];

        foreach ($chat_ids as $chat_id) {
            $chat_id = trim($chat_id);
            if (empty($chat_id)) {
                continue;
            }

            try {
                $response = wp_remote_post($api_url, [
                    'body' => [
                        'chat_id' => $chat_id,
                        'text' => $message,
                        'parse_mode' => 'HTML',
                    ],
                    'timeout' => 30,
                    'sslverify' => true,
                ]);

                if (is_wp_error($response)) {
                    $error_message = $response->get_error_message();
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Maneli Telegram Error: ' . $error_message);
                    }
                    $results[$chat_id] = [
                        'success' => false,
                        'error' => $error_message
                    ];
                    continue;
                }

                $response_code = wp_remote_retrieve_response_code($response);
                $response_body = wp_remote_retrieve_body($response);
                $response_data = json_decode($response_body, true);

                if ($response_code === 200 && isset($response_data['ok']) && $response_data['ok'] === true) {
                    $results[$chat_id] = [
                        'success' => true,
                        'message_id' => $response_data['result']['message_id'] ?? null
                    ];
                } else {
                    $error_msg = $response_data['description'] ?? 'Unknown error';
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Maneli Telegram API Error: ' . $error_msg);
                    }
                    $results[$chat_id] = [
                        'success' => false,
                        'error' => $error_msg
                    ];
                }
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Telegram Exception: ' . $e->getMessage());
                }
                $results[$chat_id] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        // If single chat ID, return single result
        if (count($chat_ids) === 1) {
            $first_result = reset($results);
            return $first_result['success'] ?? false;
        }

        // Return all results for bulk
        return $results;
    }

    /**
     * Get bot information
     *
     * @return array|false Bot info on success, false on failure
     */
    public function get_bot_info() {
        $bot_token = $this->options['telegram_bot_token'] ?? '';
        
        if (empty($bot_token)) {
            return false;
        }

        $api_url = 'https://api.telegram.org/bot' . $bot_token . '/getMe';

        try {
            $response = wp_remote_get($api_url, [
                'timeout' => 30,
                'sslverify' => true,
            ]);

            if (is_wp_error($response)) {
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $response_data = json_decode($response_body, true);

            if ($response_code === 200 && isset($response_data['ok']) && $response_data['ok'] === true) {
                return $response_data['result'];
            }

            return false;
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli Telegram Exception (getBotInfo): ' . $e->getMessage());
            }
            return false;
        }
    }
}

