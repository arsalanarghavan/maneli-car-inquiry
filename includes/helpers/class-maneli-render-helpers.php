<?php
/**
 * A helper class with static methods for rendering reusable HTML components,
 * such as table rows, to avoid code duplication.
 *
 * @package Maneli_Car_Inquiry/Includes/Helpers
 * @author  Gemini
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Render_Helpers {

    /**
     * Renders a single table row for the user list in the user management shortcode.
     *
     * @param WP_User $user        The user object to render.
     * @param string  $current_url The base URL for generating the edit link.
     */
    public static function render_user_list_row($user, $current_url) {
        $role_names = array_map(function($role) {
            global $wp_roles;
            return $wp_roles->roles[$role]['name'] ?? $role;
        }, (array) $user->roles);
        
        $edit_link = add_query_arg('edit_user', $user->ID, $current_url);
        ?>
        <tr>
            <td data-title="<?php esc_attr_e('Display Name', 'maneli-car-inquiry'); ?>"><?php echo esc_html($user->display_name); ?></td>
            <td data-title="<?php esc_attr_e('Username', 'maneli-car-inquiry'); ?>"><?php echo esc_html($user->user_login); ?></td>
            <td data-title="<?php esc_attr_e('Email', 'maneli-car-inquiry'); ?>"><?php echo esc_html($user->user_email); ?></td>
            <td data-title="<?php esc_attr_e('Role', 'maneli-car-inquiry'); ?>"><?php echo esc_html(implode(', ', $role_names)); ?></td>
            <td data-title="<?php esc_attr_e('Actions', 'maneli-car-inquiry'); ?>">
                <a href="<?php echo esc_url($edit_link); ?>" class="button view"><?php esc_html_e('Edit', 'maneli-car-inquiry'); ?></a>
                <button class="button delete-user-btn" data-user-id="<?php echo esc_attr($user->ID); ?>"><?php esc_html_e('Delete', 'maneli-car-inquiry'); ?></button>
            </td>
        </tr>
        <?php
    }

    /**
     * Renders a single table row for the product editor interface.
     *
     * @param WC_Product $product The product object to render.
     */
    public static function render_product_editor_row($product) {
        $product_id = $product->get_id();
        ?>
        <tr>
            <td data-title="<?php esc_attr_e('Car Name', 'maneli-car-inquiry'); ?>">
                <strong><a href="<?php echo get_edit_post_link($product_id); ?>" target="_blank" rel="noopener"><?php echo esc_html($product->get_name()); ?></a></strong>
            </td>
            <td data-title="<?php esc_attr_e('Cash Price (Toman)', 'maneli-car-inquiry'); ?>">
                <input type="text" class="manli-data-input manli-price-input" style="width: 100%;" data-product-id="<?php echo esc_attr($product_id); ?>" data-field-type="regular_price" value="<?php echo esc_attr($product->get_regular_price()); ?>" placeholder="<?php esc_attr_e('Cash Price', 'maneli-car-inquiry'); ?>">
            </td>
            <td data-title="<?php esc_attr_e('Installment Price (Toman)', 'maneli-car-inquiry'); ?>">
                <input type="text" class="manli-data-input manli-price-input" style="width: 100%;" data-product-id="<?php echo esc_attr($product_id); ?>" data-field-type="installment_price" value="<?php echo esc_attr(get_post_meta($product_id, 'installment_price', true)); ?>" placeholder="<?php esc_attr_e('Installment Price', 'maneli-car-inquiry'); ?>">
            </td>
            <td data-title="<?php esc_attr_e('Min. Down Payment (Toman)', 'maneli-car-inquiry'); ?>">
                <input type="text" class="manli-data-input manli-price-input" style="width: 100%;" data-product-id="<?php echo esc_attr($product_id); ?>" data-field-type="min_downpayment" value="<?php echo esc_attr(get_post_meta($product_id, 'min_downpayment', true)); ?>" placeholder="<?php esc_attr_e('Down Payment Amount', 'maneli-car-inquiry'); ?>">
            </td>
            <td data-title="<?php esc_attr_e('Available Colors', 'maneli-car-inquiry'); ?>">
                 <input type="text" class="manli-data-input" style="width: 100%;" data-product-id="<?php echo esc_attr($product_id); ?>" data-field-type="car_colors" value="<?php echo esc_attr(get_post_meta($product_id, '_maneli_car_colors', true)); ?>" placeholder="<?php esc_attr_e('e.g., White, Black', 'maneli-car-inquiry'); ?>">
            </td>
            <td data-title="<?php esc_attr_e('Sales Status', 'maneli-car-inquiry'); ?>">
                <select class="manli-data-input" style="width: 100%;" data-product-id="<?php echo esc_attr($product_id); ?>" data-field-type="car_status">
                    <option value="special_sale" <?php selected(get_post_meta($product_id, '_maneli_car_status', true), 'special_sale'); ?>><?php esc_html_e('Special Sale (Active)', 'maneli-car-inquiry'); ?></option>
                    <option value="unavailable" <?php selected(get_post_meta($product_id, '_maneli_car_status', true), 'unavailable'); ?>><?php esc_html_e('Unavailable (Show in site)', 'maneli-car-inquiry'); ?></option>
                    <option value="disabled" <?php selected(get_post_meta($product_id, '_maneli_car_status', true), 'disabled'); ?>><?php esc_html_e('Disabled (Hide from site)', 'maneli-car-inquiry'); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Renders a single table row for the installment inquiry list.
     *
     * @param int    $inquiry_id The ID of the inquiry post.
     * @param string $base_url   The base URL for generating action links.
     */
    public static function render_inquiry_row($inquiry_id, $base_url) {
        $customer = get_userdata(get_post_field('post_author', $inquiry_id));
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $status = get_post_meta($inquiry_id, 'inquiry_status', true);
        $expert_name = get_post_meta($inquiry_id, 'assigned_expert_name', true);
        $report_url = add_query_arg('inquiry_id', $inquiry_id, $base_url);
        ?>
        <tr>
            <td data-title="<?php esc_attr_e('ID', 'maneli-car-inquiry'); ?>">#<?php echo esc_html($inquiry_id); ?></td>
            <td data-title="<?php esc_attr_e('Customer', 'maneli-car-inquiry'); ?>"><?php echo esc_html($customer->display_name ?? __('N/A', 'maneli-car-inquiry')); ?></td>
            <td data-title="<?php esc_attr_e('Car', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_title($product_id)); ?></td>
            <td data-title="<?php esc_attr_e('Status', 'maneli-car-inquiry'); ?>"><?php echo esc_html(Maneli_CPT_Handler::get_status_label($status)); ?></td>
            <?php if (current_user_can('manage_maneli_inquiries')) : ?>
                <td data-title="<?php esc_attr_e('Assigned', 'maneli-car-inquiry'); ?>">
                    <?php if ($expert_name) : ?>
                        <?php echo esc_html($expert_name); ?>
                    <?php else: ?>
                        <button class="button assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment"><?php esc_html_e('Assign', 'maneli-car-inquiry'); ?></button>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
            <td data-title="<?php esc_attr_e('Date', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_date('Y/m/d', $inquiry_id)); ?></td>
            <td class="woocommerce-orders-table__cell-order-actions">
                <a href="<?php echo esc_url($report_url); ?>" class="button view"><?php esc_html_e('View Details', 'maneli-car-inquiry'); ?></a>
            </td>
        </tr>
        <?php
    }

    /**
     * Renders a single table row for the cash inquiry list.
     *
     * @param int    $inquiry_id The ID of the cash inquiry post.
     * @param string $base_url   The base URL for generating action links.
     */
    public static function render_cash_inquiry_row($inquiry_id, $base_url) {
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $customer_name = get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true);
		$status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
        $expert_name = get_post_meta($inquiry_id, 'assigned_expert_name', true);
        $report_url = add_query_arg('cash_inquiry_id', $inquiry_id, $base_url);
        ?>
        <tr>
            <td data-title="<?php esc_attr_e('ID', 'maneli-car-inquiry'); ?>">#<?php echo esc_html($inquiry_id); ?></td>
            <td data-title="<?php esc_attr_e('Customer', 'maneli-car-inquiry'); ?>"><?php echo esc_html($customer_name); ?></td>
            <td data-title="<?php esc_attr_e('Mobile', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_post_meta($inquiry_id, 'mobile_number', true)); ?></td>
            <td data-title="<?php esc_attr_e('Car', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_title($product_id)); ?></td>
            <td data-title="<?php esc_attr_e('Status', 'maneli-car-inquiry'); ?>"><?php echo esc_html(Maneli_CPT_Handler::get_cash_inquiry_status_label($status)); ?></td>
            <td data-title="<?php esc_attr_e('Assigned', 'maneli-car-inquiry'); ?>">
                <?php if ($expert_name) : ?>
                    <?php echo esc_html($expert_name); ?>
                <?php else: ?>
                    <button class="button assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash"><?php esc_html_e('Assign', 'maneli-car-inquiry'); ?></button>
                <?php endif; ?>
            </td>
            <td data-title="<?php esc_attr_e('Date', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_date('Y/m/d', $inquiry_id)); ?></td>
			<td data-title="<?php esc_attr_e('Actions', 'maneli-car-inquiry'); ?>" class="cash-inquiry-actions">
				<a href="<?php echo esc_url($report_url); ?>" class="button view"><?php esc_html_e('View Details', 'maneli-car-inquiry'); ?></a>
			</td>
        </tr>
        <?php
    }
}