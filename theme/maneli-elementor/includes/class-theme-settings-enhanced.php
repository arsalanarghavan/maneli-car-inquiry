<?php
/**
 * Maneli Theme - Enhanced Settings Manager
 * Complete theme customization dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Theme_Settings_Enhanced {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_menu'], 99); // Run late to find parent menu
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('wp_ajax_maneli_save_theme_option', [__CLASS__, 'ajax_save_option']);
        add_action('wp_ajax_maneli_reset_theme_options', [__CLASS__, 'ajax_reset_options']);
    }

    /**
     * Register admin menu - as submenu of Maneli plugin if available
     */
    public static function register_menu() {
        global $menu;

        // Look for existing Maneli plugin menu
        $parent_menu_slug = 'maneli-settings'; // Default parent

        // Check if Maneli plugin has registered its menu
        foreach ($menu as $item) {
            if (isset($item[5]) && stripos($item[5], 'maneli') !== false) {
                $parent_menu_slug = $item[2]; // Get the plugin's menu slug
                break;
            }
        }

        // If no Maneli menu found, create theme menu as top-level under "Puzzlinco" brand
        // Otherwise make it a submenu
        $is_submenu = $parent_menu_slug !== 'maneli-settings';

        if ($is_submenu) {
            // Add as submenu of Maneli plugin
            add_submenu_page(
                $parent_menu_slug,
                __('Theme Settings', 'maneli-elementor'),
                __('⚙️ Theme Settings', 'maneli-elementor'),
                'manage_options',
                'maneli-theme-settings',
                [__CLASS__, 'render_page']
            );
        } else {
            // Create as main menu
            add_menu_page(
                __('Maneli Theme', 'maneli-elementor'),
                __('⚙️ Maneli Theme', 'maneli-elementor'),
                'manage_options',
                'maneli-theme-settings',
                [__CLASS__, 'render_page'],
                'dashicons-admin-appearance',
                25
            );
        }

        // Submenu tabs
        $submenus = [
            ['page' => 'maneli-theme-settings', 'title' => __('General', 'maneli-elementor')],
            ['page' => 'maneli-theme-colors', 'title' => __('Colors & Typography', 'maneli-elementor')],
            ['page' => 'maneli-theme-header', 'title' => __('Header', 'maneli-elementor')],
            ['page' => 'maneli-theme-footer', 'title' => __('Footer', 'maneli-elementor')],
            ['page' => 'maneli-theme-advanced', 'title' => __('Advanced', 'maneli-elementor')],
        ];

        foreach ($submenus as $submenu_item) {
            if ($submenu_item['page'] !== 'maneli-theme-settings') {
                add_submenu_page(
                    'maneli-theme-settings',
                    $submenu_item['title'],
                    $submenu_item['title'],
                    'manage_options',
                    $submenu_item['page'],
                    [$submenu_item['page'] => [__CLASS__, 'render_' . str_replace('-', '_', $submenu_item['page'])]]
                );
            }
        }
    }

    /**
     * Enqueue scripts and styles
     */
    public static function enqueue_scripts($hook) {
        if (strpos($hook, 'maneli-theme-') === false && $hook !== 'toplevel_page_maneli-theme-settings') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_enqueue_style('maneli-theme-settings', get_template_directory_uri() . '/assets/css/theme-settings.css', [], '1.0.0');
        wp_enqueue_script('maneli-theme-settings', get_template_directory_uri() . '/assets/js/theme-settings.js', ['jquery', 'wp-color-picker'], '1.0.0', true);

        wp_localize_script('maneli-theme-settings', 'maneli_theme_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('maneli_theme_nonce'),
            'i18n' => [
                'save_success' => __('✅ Settings saved successfully!', 'maneli-elementor'),
                'save_error' => __('❌ Error saving settings', 'maneli-elementor'),
                'reset_confirm' => __('Are you sure you want to reset ALL theme settings to default?', 'maneli-elementor'),
                'reset_success' => __('✅ Settings reset successfully!', 'maneli-elementor'),
            ]
        ]);
    }

    /**
     * Main settings page
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Access Denied', 'maneli-elementor'));
        }

        $options = self::get_all_options();
        self::render_page_wrapper('general', [
            'title' => __('General Settings', 'maneli-elementor'),
            'content' => function() use ($options) {
                ?>
                <div class="setting-group">
                    <label for="site_logo">
                        <strong><?php esc_html_e('Site Logo', 'maneli-elementor'); ?></strong>
                    </label>
                    <div class="logo-upload">
                        <img id="logo-preview" src="<?php echo esc_url($options['site_logo'] ?? ''); ?>" style="max-width: 200px; <?php echo empty($options['site_logo']) ? 'display:none;' : ''; ?>">
                        <input type="hidden" id="site_logo" class="theme-option" data-option="site_logo" value="<?php echo esc_attr($options['site_logo'] ?? ''); ?>">
                        <button type="button" class="button button-primary upload-logo-btn"><?php esc_html_e('Upload Logo', 'maneli-elementor'); ?></button>
                        <?php if (!empty($options['site_logo'])) : ?>
                            <button type="button" class="button button-secondary remove-logo-btn"><?php esc_html_e('Remove', 'maneli-elementor'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="setting-group">
                    <label for="site_tagline_en"><strong><?php esc_html_e('Site Tagline (English)', 'maneli-elementor'); ?></strong></label>
                    <input type="text" id="site_tagline_en" class="theme-option" data-option="site_tagline_en" value="<?php echo esc_attr($options['site_tagline_en'] ?? 'MANELI AUTO'); ?>">
                </div>

                <div class="setting-group">
                    <label for="site_tagline_fa"><strong><?php esc_html_e('Site Tagline (Persian)', 'maneli-elementor'); ?></strong></label>
                    <input type="text" id="site_tagline_fa" class="theme-option" data-option="site_tagline_fa" value="<?php echo esc_attr($options['site_tagline_fa'] ?? 'مانلی خودرو'); ?>">
                </div>

                <div class="setting-group">
                    <label for="support_phone"><strong><?php esc_html_e('Support Phone', 'maneli-elementor'); ?></strong></label>
                    <input type="tel" id="support_phone" class="theme-option" data-option="support_phone" value="<?php echo esc_attr($options['support_phone'] ?? '021-99812129'); ?>">
                </div>

                <div class="setting-group">
                    <label for="support_email"><strong><?php esc_html_e('Support Email', 'maneli-elementor'); ?></strong></label>
                    <input type="email" id="support_email" class="theme-option" data-option="support_email" value="<?php echo esc_attr($options['support_email'] ?? 'info@maneli.com'); ?>">
                </div>

                <div class="setting-group">
                    <label for="facebook_url"><strong><?php esc_html_e('Facebook URL', 'maneli-elementor'); ?></strong></label>
                    <input type="url" id="facebook_url" class="theme-option" data-option="facebook_url" value="<?php echo esc_attr($options['facebook_url'] ?? ''); ?>" placeholder="https://facebook.com/...">
                </div>

                <div class="setting-group">
                    <label for="instagram_url"><strong><?php esc_html_e('Instagram URL', 'maneli-elementor'); ?></strong></label>
                    <input type="url" id="instagram_url" class="theme-option" data-option="instagram_url" value="<?php echo esc_attr($options['instagram_url'] ?? ''); ?>" placeholder="https://instagram.com/...">
                </div>

                <div class="setting-group">
                    <label for="twitter_url"><strong><?php esc_html_e('Twitter/X URL', 'maneli-elementor'); ?></strong></label>
                    <input type="url" id="twitter_url" class="theme-option" data-option="twitter_url" value="<?php echo esc_attr($options['twitter_url'] ?? ''); ?>" placeholder="https://twitter.com/...">
                </div>

                <div class="setting-group">
                    <label for="show_social_icons">
                        <input type="checkbox" id="show_social_icons" class="theme-option" data-option="show_social_icons" <?php checked(!empty($options['show_social_icons'])); ?>>
                        <strong><?php esc_html_e('Show Social Icons in Header', 'maneli-elementor'); ?></strong>
                    </label>
                </div>

                <div class="setting-group button-group">
                    <button type="button" class="button button-primary save-theme-settings"><?php esc_html_e('💾 Save All Changes', 'maneli-elementor'); ?></button>
                    <button type="button" class="button button-secondary reset-theme-settings"><?php esc_html_e('🔄 Reset to Default', 'maneli-elementor'); ?></button>
                </div>
                <?php
            }
        ]);
    }

    /**
     * Page wrapper with tabs
     */
    private static function render_page_wrapper($current_page, $page_config) {
        $pages = [
            'general' => __('General', 'maneli-elementor'),
            'colors' => __('Colors & Typography', 'maneli-elementor'),
            'header' => __('Header', 'maneli-elementor'),
            'footer' => __('Footer', 'maneli-elementor'),
            'advanced' => __('Advanced', 'maneli-elementor'),
        ];

        $page_slugs = [
            'general' => 'maneli-theme-settings',
            'colors' => 'maneli-theme-colors',
            'header' => 'maneli-theme-header',
            'footer' => 'maneli-theme-footer',
            'advanced' => 'maneli-theme-advanced',
        ];
        ?>
        <div class="wrap maneli-theme-wrap">
            <div class="maneli-theme-header">
                <h1><?php echo isset($page_config['title']) ? esc_html($page_config['title']) : __('Maneli Theme Settings', 'maneli-elementor'); ?></h1>
            </div>

            <div class="maneli-theme-tabs">
                <?php foreach ($pages as $page => $label) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $page_slugs[$page])); ?>" class="tab-button <?php echo $current_page === $page ? 'active' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="maneli-settings-container">
                <div class="maneli-settings-panel">
                    <?php if (isset($page_config['content']) && is_callable($page_config['content'])) {
                        call_user_func($page_config['content']);
                    } ?>
                </div>
            </div>
        </div>
        <?php
    }

    // Color & Typography pages
    public static function render_maneli_theme_colors() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Access Denied', 'maneli-elementor'));
        }

        $options = self::get_all_options();
        self::render_page_wrapper('colors', [
            'title' => __('Colors & Typography', 'maneli-elementor'),
            'content' => function() use ($options) {
                ?>
                <h2><?php esc_html_e('Color Scheme', 'maneli-elementor'); ?></h2>

                <div class="setting-group">
                    <label for="primary_color"><strong><?php esc_html_e('Primary Color', 'maneli-elementor'); ?></strong></label>
                    <input type="text" id="primary_color" class="theme-option color-picker" data-option="primary_color" value="<?php echo esc_attr($options['primary_color'] ?? '#0052CC'); ?>">
                </div>

                <div class="setting-group">
                    <label for="secondary_color"><strong><?php esc_html_e('Secondary Color', 'maneli-elementor'); ?></strong></label>
                    <input type="text" id="secondary_color" class="theme-option color-picker" data-option="secondary_color" value="<?php echo esc_attr($options['secondary_color'] ?? '#1e293b'); ?>">
                </div>

                <div class="setting-group">
                    <label for="accent_color"><strong><?php esc_html_e('Accent Color', 'maneli-elementor'); ?></strong></label>
                    <input type="text" id="accent_color" class="theme-option color-picker" data-option="accent_color" value="<?php echo esc_attr($options['accent_color'] ?? '#f97316'); ?>">
                </div>

                <h2><?php esc_html_e('Typography', 'maneli-elementor'); ?></h2>

                <div class="setting-group">
                    <label for="body_font"><strong><?php esc_html_e('Body Font Family', 'maneli-elementor'); ?></strong></label>
                    <select id="body_font" class="theme-option" data-option="body_font">
                        <option value="Tahoma" <?php selected($options['body_font'] ?? '', 'Tahoma'); ?>>Tahoma</option>
                        <option value="Arial" <?php selected($options['body_font'] ?? '', 'Arial'); ?>>Arial</option>
                        <option value="Verdana" <?php selected($options['body_font'] ?? '', 'Verdana'); ?>>Verdana</option>
                        <option value="Georgia" <?php selected($options['body_font'] ?? '', 'Georgia'); ?>>Georgia</option>
                    </select>
                </div>

                <div class="setting-group">
                    <label for="heading_font"><strong><?php esc_html_e('Heading Font Family', 'maneli-elementor'); ?></strong></label>
                    <select id="heading_font" class="theme-option" data-option="heading_font">
                        <option value="Tahoma" <?php selected($options['heading_font'] ?? '', 'Tahoma'); ?>>Tahoma</option>
                        <option value="Arial" <?php selected($options['heading_font'] ?? '', 'Arial'); ?>>Arial</option>
                        <option value="Georgia" <?php selected($options['heading_font'] ?? '', 'Georgia'); ?>>Georgia</option>
                    </select>
                </div>

                <div class="setting-group button-group">
                    <button type="button" class="button button-primary save-theme-settings"><?php esc_html_e('💾 Save All Changes', 'maneli-elementor'); ?></button>
                </div>
                <?php
            }
        ]);
    }

    // Header page
    public static function render_maneli_theme_header() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Access Denied', 'maneli-elementor'));
        }

        $options = self::get_all_options();
        self::render_page_wrapper('header', [
            'title' => __('Header Settings', 'maneli-elementor'),
            'content' => function() use ($options) {
                ?>
                <div class="setting-group">
                    <label for="header_style"><strong><?php esc_html_e('Header Style', 'maneli-elementor'); ?></strong></label>
                    <select id="header_style" class="theme-option" data-option="header_style">
                        <option value="default" <?php selected($options['header_style'] ?? '', 'default'); ?>><?php esc_html_e('Default', 'maneli-elementor'); ?></option>
                        <option value="minimal" <?php selected($options['header_style'] ?? '', 'minimal'); ?>><?php esc_html_e('Minimal', 'maneli-elementor'); ?></option>
                        <option value="centered" <?php selected($options['header_style'] ?? '', 'centered'); ?>><?php esc_html_e('Centered', 'maneli-elementor'); ?></option>
                    </select>
                </div>

                <div class="setting-group">
                    <label for="header_bg_color"><strong><?php esc_html_e('Header Background', 'maneli-elementor'); ?></strong></label>
                    <input type="text" id="header_bg_color" class="theme-option color-picker" data-option="header_bg_color" value="<?php echo esc_attr($options['header_bg_color'] ?? '#0052CC'); ?>">
                </div>

                <div class="setting-group">
                    <label for="header_text_color"><strong><?php esc_html_e('Header Text Color', 'maneli-elementor'); ?></strong></label>
                    <input type="text" id="header_text_color" class="theme-option color-picker" data-option="header_text_color" value="<?php echo esc_attr($options['header_text_color'] ?? '#ffffff'); ?>">
                </div>

                <div class="setting-group">
                    <label for="sticky_header">
                        <input type="checkbox" id="sticky_header" class="theme-option" data-option="sticky_header" <?php checked(!empty($options['sticky_header'])); ?>>
                        <strong><?php esc_html_e('Enable Sticky Header', 'maneli-elementor'); ?></strong>
                    </label>
                </div>

                <div class="setting-group button-group">
                    <button type="button" class="button button-primary save-theme-settings"><?php esc_html_e('💾 Save All Changes', 'maneli-elementor'); ?></button>
                </div>
                <?php
            }
        ]);
    }

    // Footer page
    public static function render_maneli_theme_footer() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Access Denied', 'maneli-elementor'));
        }

        $options = self::get_all_options();
        self::render_page_wrapper('footer', [
            'title' => __('Footer Settings', 'maneli-elementor'),
            'content' => function() use ($options) {
                ?>
                <div class="setting-group">
                    <label for="footer_bg_color"><strong><?php esc_html_e('Footer Background', 'maneli-elementor'); ?></strong></label>
                    <input type="text" id="footer_bg_color" class="theme-option color-picker" data-option="footer_bg_color" value="<?php echo esc_attr($options['footer_bg_color'] ?? '#1e293b'); ?>">
                </div>

                <div class="setting-group">
                    <label for="footer_text_color"><strong><?php esc_html_e('Footer Text Color', 'maneli-elementor'); ?></strong></label>
                    <input type="text" id="footer_text_color" class="theme-option color-picker" data-option="footer_text_color" value="<?php echo esc_attr($options['footer_text_color'] ?? '#ffffff'); ?>">
                </div>

                <div class="setting-group">
                    <label for="footer_copyright"><strong><?php esc_html_e('Copyright Text', 'maneli-elementor'); ?></strong></label>
                    <textarea id="footer_copyright" class="theme-option" data-option="footer_copyright" rows="3"><?php echo esc_textarea($options['footer_copyright'] ?? '© 2025 Maneli Auto. All rights reserved.'); ?></textarea>
                </div>

                <div class="setting-group button-group">
                    <button type="button" class="button button-primary save-theme-settings"><?php esc_html_e('💾 Save All Changes', 'maneli-elementor'); ?></button>
                </div>
                <?php
            }
        ]);
    }

    // Advanced page
    public static function render_maneli_theme_advanced() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Access Denied', 'maneli-elementor'));
        }

        $options = self::get_all_options();
        self::render_page_wrapper('advanced', [
            'title' => __('Advanced Settings', 'maneli-elementor'),
            'content' => function() use ($options) {
                ?>
                <div class="setting-group">
                    <label for="custom_css"><strong><?php esc_html_e('Custom CSS', 'maneli-elementor'); ?></strong></label>
                    <textarea id="custom_css" class="theme-option" data-option="custom_css" rows="10" style="font-family: monospace;"><?php echo esc_textarea($options['custom_css'] ?? ''); ?></textarea>
                    <p class="description"><?php esc_html_e('Add custom CSS code here. It will be loaded on all pages.', 'maneli-elementor'); ?></p>
                </div>

                <div class="setting-group">
                    <label for="enable_animations">
                        <input type="checkbox" id="enable_animations" class="theme-option" data-option="enable_animations" <?php checked(!empty($options['enable_animations'])); ?>>
                        <strong><?php esc_html_e('Enable Animations & Effects', 'maneli-elementor'); ?></strong>
                    </label>
                </div>

                <div class="setting-group">
                    <label for="enable_lazy_load">
                        <input type="checkbox" id="enable_lazy_load" class="theme-option" data-option="enable_lazy_load" <?php checked(!empty($options['enable_lazy_load'])); ?>>
                        <strong><?php esc_html_e('Enable Lazy Loading for Images', 'maneli-elementor'); ?></strong>
                    </label>
                </div>

                <div class="setting-group button-group">
                    <button type="button" class="button button-primary save-theme-settings"><?php esc_html_e('💾 Save All Changes', 'maneli-elementor'); ?></button>
                    <button type="button" class="button button-secondary reset-theme-settings"><?php esc_html_e('🔄 Reset to Default', 'maneli-elementor'); ?></button>
                </div>
                <?php
            }
        ]);
    }

    /**
     * Get all options
     */
    public static function get_all_options() {
        $defaults = self::get_defaults();
        $options = [];

        foreach ($defaults as $key => $value) {
            $options[$key] = get_option('maneli_theme_' . $key, $value);
        }

        return $options;
    }

    /**
     * Get defaults
     */
    public static function get_defaults() {
        return [
            'site_logo' => '',
            'site_tagline_en' => 'MANELI AUTO',
            'site_tagline_fa' => 'مانلی خودرو',
            'support_phone' => '021-99812129',
            'support_email' => 'info@maneli.com',
            'facebook_url' => '',
            'instagram_url' => '',
            'twitter_url' => '',
            'show_social_icons' => 1,
            'primary_color' => '#0052CC',
            'secondary_color' => '#1e293b',
            'accent_color' => '#f97316',
            'body_font' => 'Tahoma',
            'heading_font' => 'Tahoma',
            'header_style' => 'default',
            'header_bg_color' => '#0052CC',
            'header_text_color' => '#ffffff',
            'sticky_header' => 1,
            'footer_bg_color' => '#1e293b',
            'footer_text_color' => '#ffffff',
            'footer_copyright' => '© 2025 Maneli Auto. All rights reserved.',
            'custom_css' => '',
            'enable_animations' => 1,
            'enable_lazy_load' => 1,
        ];
    }

    /**
     * AJAX: Save option
     */
    public static function ajax_save_option() {
        check_ajax_referer('maneli_theme_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Access Denied', 'maneli-elementor')]);
        }

        $option = isset($_POST['option']) ? sanitize_key(wp_unslash($_POST['option'])) : '';
        $value = isset($_POST['value']) ? wp_unslash($_POST['value']) : '';

        if (empty($option)) {
            wp_send_json_error(['message' => __('Option not specified', 'maneli-elementor')]);
        }

        // Sanitize
        switch ($option) {
            case 'primary_color':
            case 'secondary_color':
            case 'accent_color':
            case 'header_bg_color':
            case 'header_text_color':
            case 'footer_bg_color':
            case 'footer_text_color':
                $value = sanitize_hex_color($value);
                break;
            case 'support_email':
                $value = sanitize_email($value);
                break;
            case 'facebook_url':
            case 'instagram_url':
            case 'twitter_url':
                $value = esc_url_raw($value);
                break;
            case 'custom_css':
                $value = wp_kses_post($value);
                break;
            case 'site_logo':
                $value = intval($value) > 0 ? intval($value) : '';
                break;
            default:
                $value = sanitize_text_field($value);
        }

        update_option('maneli_theme_' . $option, $value);

        wp_send_json_success(['message' => __('✅ Saved', 'maneli-elementor')]);
    }

    /**
     * AJAX: Reset options
     */
    public static function ajax_reset_options() {
        check_ajax_referer('maneli_theme_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Access Denied', 'maneli-elementor')]);
        }

        $defaults = self::get_defaults();

        foreach ($defaults as $key => $value) {
            update_option('maneli_theme_' . $key, $value);
        }

        wp_send_json_success(['message' => __('✅ All settings reset', 'maneli-elementor')]);
    }
}

// Initialize
Maneli_Theme_Settings_Enhanced::init();
