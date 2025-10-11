<?php
/**
 * Template for the System Report page, rendered by the [maneli_system_report] shortcode.
 *
 * This template displays statistical widgets and lists of the latest inquiries and users.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes
 * @author  Gemini
 * @version 1.0.0
 *
 * @var string $inquiry_stats_widgets_html HTML for the inquiry statistics widgets.
 * @var WP_Post[] $latest_inquiries         Array of the latest inquiry post objects.
 * @var WP_User[] $latest_users             Array of the latest user objects.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wp_roles;
?>

<div class="maneli-inquiry-wrapper">
    <h1><?php esc_html_e('General System Reports', 'maneli-car-inquiry'); ?></h1>
    
    <div class="maneli-report-section">
        <?php echo $inquiry_stats_widgets_html; // Already escaped in the generating function ?>
    </div>

    <div class="maneli-report-section">
        <h3><?php esc_html_e('Latest Registered Inquiries', 'maneli-car-inquiry'); ?></h3>
        <?php if (empty($latest_inquiries)) : ?>
            <p><?php esc_html_e('No inquiries found.', 'maneli-car-inquiry'); ?></p>
        <?php else : ?>
            <div class="maneli-table-wrapper">
                <table class="shop_table shop_table_responsive">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                            <th><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th>
                            <th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                            <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                            <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latest_inquiries as $inquiry) :
                            $customer = get_userdata($inquiry->post_author);
                            $product_id = get_post_meta($inquiry->ID, 'product_id', true);
                            $status = get_post_meta($inquiry->ID, 'inquiry_status', true);
                        ?>
                        <tr>
                            <td data-title="<?php esc_attr_e('ID', 'maneli-car-inquiry'); ?>">#<?php echo esc_html($inquiry->ID); ?></td>
                            <td data-title="<?php esc_attr_e('Customer', 'maneli-car-inquiry'); ?>"><?php echo esc_html($customer->display_name ?? __('N/A', 'maneli-car-inquiry')); ?></td>
                            <td data-title="<?php esc_attr_e('Car', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_title($product_id)); ?></td>
                            <td data-title="<?php esc_attr_e('Status', 'maneli-car-inquiry'); ?>"><?php echo esc_html(Maneli_CPT_Handler::get_status_label($status)); ?></td>
                            <td data-title="<?php esc_attr_e('Date', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_date('Y/m/d H:i', $inquiry)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="maneli-report-section">
        <h3><?php esc_html_e('Latest Registered Users', 'maneli-car-inquiry'); ?></h3>
        <?php if (empty($latest_users)) : ?>
            <p><?php esc_html_e('No users found.', 'maneli-car-inquiry'); ?></p>
        <?php else : ?>
            <div class="maneli-table-wrapper">
                <table class="shop_table shop_table_responsive">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Display Name', 'maneli-car-inquiry'); ?></th>
                            <th><?php esc_html_e('Username', 'maneli-car-inquiry'); ?></th>
                            <th><?php esc_html_e('Email', 'maneli-car-inquiry'); ?></th>
                            <th><?php esc_html_e('Role', 'maneli-car-inquiry'); ?></th>
                            <th><?php esc_html_e('Registration Date', 'maneli-car-inquiry'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latest_users as $user) : 
                            $role_names = array_map(function($role) use ($wp_roles) {
                                return $wp_roles->roles[$role]['name'] ?? $role;
                            }, (array) $user->roles);
                        ?>
                        <tr>
                            <td data-title="<?php esc_attr_e('Display Name', 'maneli-car-inquiry'); ?>"><?php echo esc_html($user->display_name); ?></td>
                            <td data-title="<?php esc_attr_e('Username', 'maneli-car-inquiry'); ?>"><?php echo esc_html($user->user_login); ?></td>
                            <td data-title="<?php esc_attr_e('Email', 'maneli-car-inquiry'); ?>"><?php echo esc_html($user->user_email); ?></td>
                            <td data-title="<?php esc_attr_e('Role', 'maneli-car-inquiry'); ?>"><?php echo esc_html(implode(', ', $role_names)); ?></td>
                            <td data-title="<?php esc_attr_e('Registration Date', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_date_from_gmt($user->user_registered, 'Y/m/d H:i')); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>