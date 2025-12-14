<?php
/**
 * Maneli Admin Notices
 * Display setup completion messages
 * 
 * @package Maneli_Car_Inquiry/Includes/Elementor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Admin_Notices {

    /**
     * Initialize admin notices
     */
    public static function init() {
        add_action('admin_notices', [__CLASS__, 'show_setup_notice']);
        add_action('wp_ajax_maneli_dismiss_notice', [__CLASS__, 'dismiss_notice']);
    }

    /**
     * Show setup complete notice
     */
    public static function show_setup_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if notice was dismissed
        if (get_transient('maneli_setup_notice_dismissed')) {
            return;
        }

        // Check if setup was completed
        $homepage = get_option('page_on_front');
        if (!$homepage) {
            return;
        }

        $header_template = self::get_template_by_type('header');
        $footer_template = self::get_template_by_type('footer');

        if (!$header_template || !$footer_template) {
            return;
        }

        ?>
        <div class="notice notice-success is-dismissible" id="maneli-setup-notice">
            <p>
                <strong>✅ مانلی خودرو - نصب شد!</strong><br>
                صفحه اصلی، هدر و فوتر خودکار اضافه شدند.
            </p>
            <ul style="margin: 10px 0 0 20px;">
                <li>
                    ✅ <strong>صفحه اصلی:</strong>
                    <a href="<?php echo esc_url(get_edit_post_link($homepage)); ?>">
                        <?php echo get_the_title($homepage); ?>
                    </a>
                    <a href="<?php echo esc_url(get_permalink($homepage)); ?>" target="_blank" rel="noopener noreferrer">
                        (مشاهده)
                    </a>
                </li>
                <li>
                    ✅ <strong>هدر:</strong>
                    <a href="<?php echo esc_url(get_edit_post_link($header_template->ID)); ?>">
                        ویرایش در Elementor
                    </a>
                </li>
                <li>
                    ✅ <strong>فوتر:</strong>
                    <a href="<?php echo esc_url(get_edit_post_link($footer_template->ID)); ?>">
                        ویرایش در Elementor
                    </a>
                </li>
            </ul>
            <p style="margin: 10px 0;">
                <a href="https://elementor.com/help/" target="_blank" class="button button-primary">
                    راهنمای Elementor
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=elementor')); ?>" class="button">
                    Elementor Settings
                </a>
            </p>
        </div>
        <script>
            document.getElementById('maneli-setup-notice').addEventListener('click', function(e) {
                if (e.target.classList.contains('notice-dismiss')) {
                    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                        method: 'POST',
                        body: new URLSearchParams({
                            action: 'maneli_dismiss_notice',
                            nonce: '<?php echo wp_create_nonce('maneli_dismiss'); ?>'
                        })
                    });
                }
            });
        </script>
        <?php
    }

    /**
     * Dismiss notice via AJAX
     */
    public static function dismiss_notice() {
        check_ajax_referer('maneli_dismiss');
        
        // Set transient for 30 days
        set_transient('maneli_setup_notice_dismissed', true, 30 * DAY_IN_SECONDS);
        
        wp_die();
    }

    /**
     * Get template by type
     */
    private static function get_template_by_type($type) {
        $args = [
            'post_type' => 'elementor_library',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => $type,
                ]
            ]
        ];
        $templates = get_posts($args);
        return !empty($templates) ? $templates[0] : null;
    }
}

// Initialize
Maneli_Admin_Notices::init();
