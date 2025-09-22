<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Admin_Dashboard_Widgets {

    /**
     * Renders the common CSS for all stat widgets.
     */
    private static function render_widget_styles() {
        return "
        <style>
            .maneli-stats-container {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
                font-family: inherit;
            }
            .maneli-stat-box {
                background-color: #fff;
                padding: 25px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 20px;
                border: 1px solid #e0e0e0;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            .maneli-stat-box .icon {
                font-size: 28px;
                width: 60px;
                height: 60px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff !important; /* Icon color set to white */
            }
            .maneli-stat-box .icon.total-users { background-color: #2D89BE; }
            .maneli-stat-box .icon.customers { background-color: #5cb85c; }
            .maneli-stat-box .icon.experts { background-color: #f0ad4e; }

            .maneli-stat-box .icon.total-inquiries { background-color: #2D89BE; }
            .maneli-stat-box .icon.pending { background-color: #f0ad4e; }
            .maneli-stat-box .icon.approved { background-color: #5cb85c; }
            .maneli-stat-box .icon.rejected { background-color: #d9534f; }

            .maneli-stat-box .info .value {
                font-size: 24px;
                font-weight: 700;
                color: #333;
            }
            .maneli-stat-box .info .label {
                font-size: 14px;
                color: #777;
            }
        </style>
        ";
    }

    /**
     * Renders the user-related statistics widgets.
     */
    public static function render_user_statistics_widgets() {
        $user_counts = count_users();
        $total_users = $user_counts['total_users'];
        $customer_count = $user_counts['avail_roles']['customer'] ?? 0;
        $expert_count = $user_counts['avail_roles']['maneli_expert'] ?? 0;
        $admin_count = ($user_counts['avail_roles']['maneli_admin'] ?? 0) + ($user_counts['avail_roles']['administrator'] ?? 0);
        $employee_count = $expert_count + $admin_count;

        ob_start();
        echo self::render_widget_styles();
        ?>
        <div class="maneli-stats-container">
            <div class="maneli-stat-box">
                <div class="icon total-users"><i class="fas fa-users"></i></div>
                <div class="info">
                    <div class="value"><?php echo number_format_i18n($total_users); ?></div>
                    <div class="label">کل کاربران</div>
                </div>
            </div>
             <div class="maneli-stat-box">
                <div class="icon customers"><i class="fas fa-user-tag"></i></div>
                <div class="info">
                    <div class="value"><?php echo number_format_i18n($customer_count); ?></div>
                    <div class="label">تعداد مشتریان</div>
                </div>
            </div>
             <div class="maneli-stat-box">
                <div class="icon experts"><i class="fas fa-user-shield"></i></div>
                <div class="info">
                    <div class="value"><?php echo number_format_i18n($employee_count); ?></div>
                    <div class="label">تعداد کارمندان</div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renders the inquiry-related statistics widgets.
     */
    public static function render_inquiry_statistics_widgets() {
        $total_inquiries = wp_count_posts('inquiry')->publish;
        $pending_inquiries = new WP_Query(['post_type' => 'inquiry', 'post_status' => 'publish', 'meta_key' => 'inquiry_status', 'meta_value' => 'pending']);
        $approved_inquiries = new WP_Query(['post_type' => 'inquiry', 'post_status' => 'publish', 'meta_key' => 'inquiry_status', 'meta_value' => 'user_confirmed']);
        $rejected_inquiries = new WP_Query(['post_type' => 'inquiry', 'post_status' => 'publish', 'meta_key' => 'inquiry_status', 'meta_value' => 'rejected']);

        ob_start();
        echo self::render_widget_styles();
        ?>
        <div class="maneli-stats-container">
            <div class="maneli-stat-box">
                <div class="icon total-inquiries"><i class="fas fa-file-alt"></i></div>
                <div class="info">
                    <div class="value"><?php echo number_format_i18n($total_inquiries); ?></div>
                    <div class="label">کل استعلام‌ها</div>
                </div>
            </div>
            <div class="maneli-stat-box">
                <div class="icon pending"><i class="fas fa-hourglass-half"></i></div>
                <div class="info">
                    <div class="value"><?php echo number_format_i18n($pending_inquiries->found_posts); ?></div>
                    <div class="label">در حال بررسی</div>
                </div>
            </div>
            <div class="maneli-stat-box">
                <div class="icon approved"><i class="fas fa-check-circle"></i></div>
                <div class="info">
                    <div class="value"><?php echo number_format_i18n($approved_inquiries->found_posts); ?></div>
                    <div class="label">تایید شده</div>
                </div>
            </div>
            <div class="maneli-stat-box">
                <div class="icon rejected"><i class="fas fa-times-circle"></i></div>
                <div class="info">
                    <div class="value"><?php echo number_format_i18n($rejected_inquiries->found_posts); ?></div>
                    <div class="label">رد شده</div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}