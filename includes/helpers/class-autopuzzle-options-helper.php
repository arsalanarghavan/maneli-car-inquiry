<?php
/**
 * Options Helper Class with In-Memory Caching
 *
 * This class provides a centralized way to get plugin options with in-memory caching
 * to prevent multiple database queries for the same option in a single request.
 *
 * @package Autopuzzle_Car_Inquiry/Includes/Helpers
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Options_Helper {

    /**
     * In-memory cache for options
     *
     * @var array
     */
    private static $options_cache = [];

    /**
     * Get all plugin options with caching
     *
     * @return array All plugin options
     */
    public static function get_all_options() {
        // Check cache first
        if (isset(self::$options_cache['all_options'])) {
            return self::$options_cache['all_options'];
        }

        // Get from database
        $options = get_option('autopuzzle_inquiry_all_options', []);

        // Cache it
        self::$options_cache['all_options'] = $options;

        return $options;
    }

    /**
     * Get a specific option value
     *
     * @param string $key Option key
     * @param mixed $default Default value if option doesn't exist
     * @return mixed Option value or default
     */
    public static function get_option($key, $default = null) {
        $options = self::get_all_options();
        return isset($options[$key]) ? $options[$key] : $default;
    }

    /**
     * Check if an option is enabled (supports various truthy values)
     *
     * @param string $key Option key
     * @param bool $default Default value
     * @return bool True if enabled, false otherwise
     */
    public static function is_option_enabled($key, $default = false) {
        $options = self::get_all_options();

        if (!array_key_exists($key, $options)) {
            return $default;
        }

        $value = $options[$key];

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return intval($value) === 1;
        }

        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Clear the options cache (useful after updating options)
     */
    public static function clear_cache() {
        self::$options_cache = [];
    }

    /**
     * Clear cache for a specific option key
     *
     * @param string $key Option key to clear
     */
    public static function clear_option_cache($key = null) {
        if ($key === null) {
            self::clear_cache();
        } else {
            unset(self::$options_cache[$key]);
        }
    }
}

