<?php
if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Migration {
    const OPTION_FLAG = 'autopuzzle_migration_aliases_v1';

    public static function run() {
        // Only run once per site (idempotent). Use a flag with versioning for future migrations.
        $done = get_option(self::OPTION_FLAG);
        if ($done) {
            return;
        }

        global $wpdb;
        $options_table = $wpdb->options;

        // Migrate all options with maneli_ prefix to autopuzzle_ prefix if the new key doesn't exist yet.
        $prefixed_options = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value, autoload FROM {$options_table} WHERE option_name LIKE %s",
                $wpdb->esc_like('maneli_') . '%'
            ),
            ARRAY_A
        );

        if (!empty($prefixed_options)) {
            foreach ($prefixed_options as $row) {
                $old_name = $row['option_name'];
                $new_name = preg_replace('/^maneli_/', 'autopuzzle_', $old_name);
                if (!$new_name || $new_name === $old_name) {
                    continue;
                }

                // If the new option is not set, copy value over preserving autoload behavior
                $existing = get_option($new_name, null);
                if ($existing === null) {
                    $value = maybe_unserialize($row['option_value']);
                    $autoload = ($row['autoload'] === 'yes') ? 'yes' : 'no';
                    add_option($new_name, $value, '', $autoload);
                }
            }
        }

        // Mark migration complete
        update_option(self::OPTION_FLAG, time(), false);
    }
}
