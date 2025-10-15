<?php
/**
 * Template for the main User List view, rendered by the [maneli_user_list] shortcode.
 *
 * This template displays statistical widgets, filter controls, and the table of users.
 * The table body is populated initially and then updated via AJAX.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/UserManagement
 * @author  Gemini
 * @version 1.0.0
 *
 * @var string    $user_stats_widgets_html HTML for the user statistics widgets.
 * @var WP_User_Query $initial_user_query  The initial WP_User_Query object.
 * @var string    $current_url             The base URL for generating action links.
 * @var array     $feedback_messages       An array of success/error messages to display.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="maneli-inquiry-wrapper">

    <?php echo $user_stats_widgets_html; // Already escaped in the generating function ?>

    <?php if (!empty($feedback_messages['success'])) : ?>
        <div class="status-box status-approved">
            <?php foreach ($feedback_messages['success'] as $message) : ?>
                <p><?php echo esc_html($message); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($feedback_messages['error'])) : ?>
        <div class="status-box status-failed">
            <?php foreach ($feedback_messages['error'] as $message) : ?>
                <p><?php echo esc_html($message); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="user-list-header">
        <h3><?php esc_html_e('Complete User List', 'maneli-car-inquiry'); ?></h3>
        <a href="<?php echo esc_url(add_query_arg('add_user', 'true', $current_url)); ?>" class="button button-primary">
            <?php esc_html_e('Add New User', 'maneli-car-inquiry'); ?>
        </a>
    </div>
    
    <div class="user-list-filters">
        <form id="maneli-user-filter-form" onsubmit="return false;">
            <div class="filter-row search-row">
                <input type="search" id="user-search-input" name="s" class="search-input" placeholder="<?php esc_attr_e('Search by name, email, mobile, or national ID...', 'maneli-car-inquiry'); ?>">
            </div>
            <div class="filter-row">
                <div class="filter-group">
                    <label for="role-filter"><?php esc_html_e('User Role:', 'maneli-car-inquiry'); ?></label>
                    <select name="role" id="role-filter">
                        <option value=""><?php esc_html_e('All Roles', 'maneli-car-inquiry'); ?></option>
                        <option value="customer"><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></option>
                        <option value="maneli_expert"><?php esc_html_e('Maneli Expert', 'maneli-car-inquiry'); ?></option>
                        <option value="maneli_admin"><?php esc_html_e('Maneli Manager', 'maneli-car-inquiry'); ?></option>
                        <option value="administrator"><?php esc_html_e('General Manager', 'maneli-car-inquiry'); ?></option>
                    </select>
                </div>
                 <div class="filter-group">
                    <label for="orderby-filter"><?php esc_html_e('Order By:', 'maneli-car-inquiry'); ?></label>
                    <select name="orderby" id="orderby-filter">
                        <option value="display_name"><?php esc_html_e('Display Name', 'maneli-car-inquiry'); ?></option>
                        <option value="user_registered"><?php esc_html_e('Registration Date', 'maneli-car-inquiry'); ?></option>
                        <option value="user_login"><?php esc_html_e('Username', 'maneli-car-inquiry'); ?></option>
                    </select>
                </div>
                <div class="filter-group">
                     <label for="order-filter"><?php esc_html_e('Order:', 'maneli-car-inquiry'); ?></label>
                    <select name="order" id="order-filter">
                        <option value="ASC"><?php esc_html_e('Ascending', 'maneli-car-inquiry'); ?></option>
                        <option value="DESC"><?php esc_html_e('Descending', 'maneli-car-inquiry'); ?></option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <div class="maneli-table-wrapper">
        <table class="shop_table shop_table_responsive">
            <thead>
                <tr>
                    <th><?php esc_html_e('Display Name', 'maneli-car-inquiry'); ?></th>
                    <th><?php esc_html_e('Username', 'maneli-car-inquiry'); ?></th>
                    <th><?php esc_html_e('Email', 'maneli-car-inquiry'); ?></th>
                    <th><?php esc_html_e('Role', 'maneli-car-inquiry'); ?></th>
                    <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                </tr>
            </thead>
            <tbody id="maneli-user-list-tbody">
                <?php
                $all_users = $initial_user_query->get_results();
                if (!empty($all_users)) :
                    foreach ($all_users as $user) :
                        if ($user->ID === get_current_user_id()) continue; // Don't show the current user in the list
                        Maneli_Render_Helpers::render_user_list_row($user, $current_url);
                    endforeach;
                else :
                    ?>
                    <tr><td colspan="5" style="text-align:center;"><?php esc_html_e('No users found.', 'maneli-car-inquiry'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div id="user-list-loader" style="display:none; text-align:center; padding: 40px;">
        <div class="spinner is-active" style="float:none;"></div>
    </div>
    
    <div class="maneli-pagination-wrapper" style="margin-top: 20px; text-align: center;">
        <?php
        $total_users = $initial_user_query->get_total();
        $total_pages = ceil($total_users / 50);
        echo paginate_links([
            'base'      => '#', // Handled by JS
            'format'    => '?paged=%#%',
            'current'   => max(1, get_query_var('paged')),
            'total'     => $total_pages,
            'prev_text' => esc_html__('&laquo; Previous', 'maneli-car-inquiry'),
            'next_text' => esc_html__('Next &raquo;', 'maneli-car-inquiry'),
            'type'      => 'plain',
        ]);
        ?>
     </div>
</div>