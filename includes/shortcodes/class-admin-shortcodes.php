<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Admin_Shortcodes {

    public function __construct() {
        add_shortcode('maneli_settings', [$this, 'render_settings_shortcode']);
    }

    public function render_settings_shortcode() {
        if (!current_user_can('manage_maneli_inquiries')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p></div>';
        }

        $settings_page = new Maneli_Settings_Page();
        $all_settings = $settings_page->get_all_settings_public();
        $first_tab = array_key_first($all_settings);

        ob_start();

        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
            echo '<div class="status-box status-approved"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
        }
        ?>
        <div class="maneli-settings-container">
            <div class="maneli-settings-sidebar">
                <ul class="maneli-settings-tabs">
                    <?php foreach ($all_settings as $tab_key => $tab_data) : ?>
                        <?php
                        $tab_title = $tab_data['title'] ?? ucfirst($tab_key);
                        $tab_icon = $tab_data['icon'] ?? 'fas fa-cog';
                        ?>
                        <li>
                            <a href="#<?php echo esc_attr($tab_key); ?>" class="maneli-tab-link <?php echo ($tab_key === $first_tab) ? 'active' : ''; ?>">
                                <i class="<?php echo esc_attr($tab_icon); ?>"></i>
                                <?php echo esc_html($tab_title); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="maneli-settings-content">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="maneli_save_frontend_settings">
                    <?php wp_nonce_field('maneli_save_frontend_settings_nonce'); ?>
                    <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url(remove_query_arg('settings-updated')); ?>">
                    
                    <?php foreach ($all_settings as $tab_key => $tab_data) : ?>
                        <div id="<?php echo esc_attr($tab_key); ?>" class="maneli-tab-pane <?php echo ($tab_key === $first_tab) ? 'active' : ''; ?>">
                            <?php $settings_page->manually_render_settings_tab_public($tab_key); ?>
                        </div>
                    <?php endforeach; ?>

                    <p class="submit">
                        <button type="submit" name="submit" id="submit" class="maneli-settings-save-btn">
                            <i class="fas fa-save"></i> ذخیره تغییرات
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabLinks = document.querySelectorAll('.maneli-tab-link');
            const tabPanes = document.querySelectorAll('.maneli-tab-pane');

            function switchTab(e) {
                e.preventDefault();
                tabLinks.forEach(link => link.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));
                this.classList.add('active');
                const targetPane = document.querySelector(this.getAttribute('href'));
                if (targetPane) {
                    targetPane.classList.add('active');
                }
            }

            tabLinks.forEach(link => {
                link.addEventListener('click', switchTab);
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}