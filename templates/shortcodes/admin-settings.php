<?php
/**
 * Template for the frontend settings panel, rendered by the [maneli_settings] shortcode.
 *
 * This template receives the settings structure from the shortcode class and renders the tabs and content.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes
 * @author  Gemini
 * @version 1.0.0
 *
 * @var bool                  $settings_updated      Whether the settings were just updated.
 * @var array                 $all_settings_tabs     The structured array of all settings tabs, sections, and fields.
 * @var Maneli_Settings_Page  $settings_page_handler An instance of the settings page class to render fields.
 */

if (!defined('ABSPATH')) {
    exit;
}

$first_tab_key = !empty($all_settings_tabs) ? array_key_first($all_settings_tabs) : '';
?>

<div class="maneli-inquiry-wrapper">
    <!-- Settings Header -->
    <div class="maneli-settings-header">
        <h2 class="maneli-settings-title">
            <i class="fas fa-cog"></i>
            <?php esc_html_e('Plugin Settings', 'maneli-car-inquiry'); ?>
        </h2>
        <p class="maneli-settings-subtitle"><?php esc_html_e('Configure all aspects of the Maneli Car Inquiry plugin from this centralized settings panel.', 'maneli-car-inquiry'); ?></p>
    </div>

    <?php if ($settings_updated) : ?>
        <div class="status-box status-approved">
            <p><?php esc_html_e('Settings saved successfully.', 'maneli-car-inquiry'); ?></p>
        </div>
    <?php endif; ?>

    <div class="maneli-settings-container">
        <div class="maneli-settings-sidebar">
            <ul class="maneli-settings-tabs">
                <?php foreach ($all_settings_tabs as $tab_key => $tab_data) : ?>
                    <li>
                        <a href="#<?php echo esc_attr($tab_key); ?>" class="maneli-tab-link <?php echo ($tab_key === $first_tab_key) ? 'active' : ''; ?>">
                            <i class="<?php echo esc_attr($tab_data['icon'] ?? 'fas fa-cog'); ?>"></i>
                            <?php echo esc_html($tab_data['title']); ?>
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
                
                <?php foreach ($all_settings_tabs as $tab_key => $tab_data) : ?>
                    <div id="<?php echo esc_attr($tab_key); ?>" class="maneli-tab-pane <?php echo ($tab_key === $first_tab_key) ? 'active' : ''; ?>">
                        <?php
                        // This special section is for display only and doesn't have standard fields
                        if ($tab_key === 'experts') :
                            $section = $tab_data['sections']['maneli_experts_list_section'];
                            ?>
                            <h3 class='maneli-settings-section-title'><?php echo esc_html($section['title']); ?></h3>
                            <p><?php echo wp_kses_post($section['desc']); ?></p>
                            <?php
                            $expert_users = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name']);
                            if (!empty($expert_users)) :
                                ?>
                                <table class="shop_table shop_table_responsive expert-list-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Expert Name', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('Email', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expert_users as $expert) : ?>
                                            <tr>
                                                <td data-title="<?php esc_attr_e('Name', 'maneli-car-inquiry'); ?>"><?php echo esc_html($expert->display_name); ?></td>
                                                <td data-title="<?php esc_attr_e('Email', 'maneli-car-inquiry'); ?>"><?php echo esc_html($expert->user_email); ?></td>
                                                <td data-title="<?php esc_attr_e('Mobile', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_user_meta($expert->ID, 'mobile_number', true) ?: esc_html__('Not set', 'maneli-car-inquiry')); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else : ?>
                                <p><?php esc_html_e('No experts are currently registered.', 'maneli-car-inquiry'); ?></p>
                            <?php endif; ?>
                        <?php else : ?>
                            <?php
                            // Render standard sections and fields
                            if (!empty($tab_data['sections'])) :
                                foreach($tab_data['sections'] as $section) :
                                    ?>
                                    <h3 class='maneli-settings-section-title'><?php echo esc_html($section['title']); ?></h3>
                                    <?php if (!empty($section['desc'])) : ?>
                                        <p><?php echo wp_kses_post($section['desc']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($section['fields'])) : ?>
                                        <table class="form-table">
                                            <?php foreach ($section['fields'] as $field) : ?>
                                                <tr>
                                                    <th scope="row">
                                                        <label for="<?php echo esc_attr($settings_page_handler->get_options_name() . '_' . $field['name']); ?>">
                                                            <?php echo esc_html($field['label']); ?>
                                                        </label>
                                                    </th>
                                                    <td>
                                                        <?php $settings_page_handler->render_field_html($field); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <!-- Save Button - Always Visible -->
                <div class="maneli-settings-save-container">
                    <p class="submit">
                        <button type="submit" name="submit" id="submit" class="maneli-settings-save-btn">
                            <i class="fas fa-save"></i> <?php esc_html_e('Save Changes', 'maneli-car-inquiry'); ?>
                        </button>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>