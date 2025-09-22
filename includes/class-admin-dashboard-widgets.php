<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Admin_Dashboard_Widgets {

    /**
     * Renders the statistics widgets HTML.
     */
    public static function render_statistics_widgets() {
        // Calculate statistics
        $total_users = count_users()['total_users'];
        $total_inquiries = wp_count_posts('inquiry')->publish;

        $pending_inquiries_args = [
            'post_type' => 'inquiry',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'inquiry_status',
                    'value' => ['pending', 'more_docs'],
                    'compare' => 'IN'
                ]
            ]
        ];
        $pending_inquiries = new WP_Query($pending_inquiries_args);

        $approved_inquiries_args = [
            'post_type' => 'inquiry',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'inquiry_status',
                    'value' => 'user_confirmed',
                    'compare' => '='
                ]
            ]
        ];
        $approved_inquiries = new WP_Query($approved_inquiries_args);

        ob_start();
        ?>
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
                color: #fff;
            }
            .maneli-stat-box .icon.users { background-color: #2D89BE; }
            .maneli-stat-box .icon.inquiries { background-color: #5cb85c; }
            .maneli-stat-box .icon.pending { background-color: #f0ad4e; }
            .maneli-stat-box .icon.approved { background-color: #5bc0de; }
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
        <div class="maneli-stats-container">
            <div class="maneli-stat-box">
                <div class="icon users"><i class="fas fa-users"></i></div>
                <div class="info">
                    <div class="value"><?php echo number_format_i18n($total_users); ?></div>
                    <div class="label">کل کاربران</div>
                </div>
            </div>
             <div class="maneli-stat-box">
                <div class="icon inquiries"><i class="fas fa-file-alt"></i></div>
                <div class="info">
                    <div class="value"><?php echo number_format_i18n($total_inquiries); ?></div>
                    <div class="label">کل استعلام‌ها</div>
                </div>
            </div>
             <div class="maneli-stat-box">
                <div class="icon pending"><i class="fas fa-hourglass-half"></i></div>
                <div class="info">
                    <div class="value"><?php echo number_format_i18n($pending_inquiries->found_posts); ?></div>
                    <div class="label">استعلام در حال بررسی</div>
                </div>
            </div>
            <div class="maneli-stat-box">
                <div class="icon approved"><i class="fas fa-check-circle"></i></div>
                <div class="info">
                    <div class="value"><?php echo number_format_i18n($approved_inquiries->found_posts); ?></div>
                    <div class="label">استعلام تایید شده</div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}