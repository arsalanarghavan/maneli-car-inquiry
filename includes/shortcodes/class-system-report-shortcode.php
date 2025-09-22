<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_System_Report_Shortcode {

    public function __construct() {
        add_shortcode('maneli_system_report', [$this, 'render_system_report']);
    }

    public function render_system_report() {
        if (!current_user_can('manage_maneli_inquiries')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p></div>';
        }

        // Get latest 10 inquiries
        $latest_inquiries = get_posts([
            'post_type' => 'inquiry',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        // Get latest 10 users
        $latest_users = get_users([
            'number' => 10,
            'orderby' => 'user_registered',
            'order' => 'DESC'
        ]);

        ob_start();
        ?>
        <style>
            .maneli-report-section { margin-bottom: 40px; }
            .maneli-report-section h3 { font-size: 20px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        </style>
        <div class="maneli-inquiry-wrapper">
            <h1>گزارشات کلی سیستم</h1>
            
            <div class="maneli-report-section">
                <?php echo Maneli_Admin_Dashboard_Widgets::render_inquiry_statistics_widgets(); ?>
            </div>

            <div class="maneli-report-section">
                <h3>آخرین استعلام‌های ثبت شده</h3>
                <?php if (empty($latest_inquiries)): ?>
                    <p>هیچ استعلامی یافت نشد.</p>
                <?php else: ?>
                    <table class="shop_table shop_table_responsive">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>مشتری</th>
                                <th>خودرو</th>
                                <th>وضعیت</th>
                                <th>تاریخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latest_inquiries as $inquiry):
                                $customer = get_userdata($inquiry->post_author);
                                $product_id = get_post_meta($inquiry->ID, 'product_id', true);
                                $status = get_post_meta($inquiry->ID, 'inquiry_status', true);
                            ?>
                            <tr>
                                <td>#<?php echo $inquiry->ID; ?></td>
                                <td><?php echo esc_html($customer->display_name); ?></td>
                                <td><?php echo esc_html(get_the_title($product_id)); ?></td>
                                <td><?php echo esc_html(Maneli_CPT_Handler::get_status_label($status)); ?></td>
                                <td><?php echo esc_html(get_the_date('Y/m/d H:i', $inquiry)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="maneli-report-section">
                <h3>آخرین کاربران ثبت شده</h3>
                <?php if (empty($latest_users)): ?>
                    <p>هیچ کاربری یافت نشد.</p>
                <?php else: ?>
                    <table class="shop_table shop_table_responsive">
                        <thead>
                            <tr>
                                <th>نام نمایشی</th>
                                <th>نام کاربری</th>
                                <th>ایمیل</th>
                                <th>نقش</th>
                                <th>تاریخ ثبت‌نام</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latest_users as $user): 
                                $role_names = array_map(function($role) { global $wp_roles; return $wp_roles->roles[$role]['name'] ?? $role; }, $user->roles);
                            ?>
                            <tr>
                                <td><?php echo esc_html($user->display_name); ?></td>
                                <td><?php echo esc_html($user->user_login); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo esc_html(implode(', ', $role_names)); ?></td>
                                <td><?php echo esc_html(get_date_from_gmt($user->user_registered, 'Y/m/d H:i')); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}