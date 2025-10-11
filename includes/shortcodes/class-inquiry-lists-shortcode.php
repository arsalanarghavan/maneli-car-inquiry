<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Inquiry_Lists_Shortcode {

    public function __construct() {
        add_shortcode('maneli_inquiry_list', [$this, 'render_inquiry_list']);
        add_shortcode('maneli_cash_inquiry_list', [$this, 'render_cash_inquiry_list']);
        add_shortcode('maneli_frontend_credit_report', [$this, 'render_frontend_credit_report']);

        // Backward compatibility for old shortcode
        add_shortcode('maneli_expert_inquiry_list', [$this, 'render_inquiry_list']);
    }

    public function render_inquiry_list() {
        if (isset($_GET['inquiry_id']) && !empty($_GET['inquiry_id'])) {
            return $this->render_frontend_credit_report();
        }

        if (!is_user_logged_in()) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>برای مشاهده لیست استعلام‌ها، لطفاً ابتدا وارد شوید.</p></div>';
        }
    
        $current_user = wp_get_current_user();
        $is_admin_or_expert = current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', $current_user->roles);

        if (!$is_admin_or_expert) {
            return $this->render_customer_inquiry_list($current_user->ID);
        }
        
        $js_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/inquiry-actions.js';
        if (file_exists($js_path)) {
            wp_enqueue_script('maneli-inquiry-actions', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/inquiry-actions.js', ['jquery', 'sweetalert2'], '1.0.8', true);
            wp_localize_script('maneli-inquiry-actions', 'maneli_inquiry_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'assign_nonce' => wp_create_nonce('maneli_inquiry_assign_expert_nonce'),
            ]);
        }

        ob_start();
        ?>
        <div class="maneli-full-width-container">
            <div class="maneli-inquiry-wrapper">

                <?php echo Maneli_Admin_Dashboard_Widgets::render_inquiry_statistics_widgets(); ?>
                
                <h3 style="margin-top:40px;">لیست کامل استعلام‌ها</h3>
                
                <div class="user-list-filters">
                    <form id="maneli-inquiry-filter-form" onsubmit="return false;">
                        <div class="filter-row search-row">
                            <input type="search" id="inquiry-search-input" class="search-input" placeholder="جستجو بر اساس نام مشتری، نام خودرو، کد ملی یا شماره موبایل...">
                        </div>
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="status-filter">وضعیت:</label>
                                <select id="status-filter">
                                    <option value="">همه وضعیت‌ها</option>
                                    <?php foreach (Maneli_CPT_Handler::get_all_statuses() as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (current_user_can('manage_maneli_inquiries')): 
                                $experts = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
                            ?>
                             <div class="filter-group">
                                <label for="expert-filter">کارشناس مسئول:</label>
                                <select id="expert-filter" class="maneli-select2">
                                    <option value="">همه کارشناسان</option>
                                    <?php foreach ($experts as $expert) : ?>
                                        <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="maneli-table-wrapper">
                    <table class="shop_table shop_table_responsive">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>مشتری</th>
                                <th>خودرو</th>
                                <th>وضعیت</th>
                                <?php if (current_user_can('manage_maneli_inquiries')) echo '<th>ارجاع</th>'; ?>
                                <th>تاریخ ثبت</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="maneli-inquiry-list-tbody">
                        </tbody>
                    </table>
                </div>
                <div id="inquiry-list-loader" style="display: none; text-align:center; padding: 40px;"><div class="spinner is-active" style="float:none;"></div></div>
                <div class="maneli-pagination-wrapper" style="margin-top: 20px; text-align: center;"></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            if (typeof $.fn.select2 === 'function' && $('.maneli-select2').length) {
                $('.maneli-select2').select2({
                     placeholder: "یک کارشناس انتخاب کنید",
                     allowClear: true,
                     width: '100%'
                });
            }

            var xhr;
            var searchTimeout;

            function fetch_inquiries(page = 1) {
                if (xhr && xhr.readyState !== 4) {
                    xhr.abort();
                }

                $('#inquiry-list-loader').show();
                $('#maneli-inquiry-list-tbody').css('opacity', 0.5);
                
                var filters = {
                    action: 'maneli_filter_inquiries_ajax',
                    _ajax_nonce: '<?php echo wp_create_nonce("maneli_inquiry_filter_nonce"); ?>',
                    page: page,
                    search: $('#inquiry-search-input').val(),
                    status: $('#status-filter').val(),
                    expert: $('#expert-filter').length ? $('#expert-filter').val() : '',
                    base_url: '<?php echo esc_url(remove_query_arg("inquiry_id")); ?>'
                };

                xhr = $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: filters,
                    success: function(response) {
                        if (response.success) {
                            $('#maneli-inquiry-list-tbody').html(response.data.html);
                             $('.maneli-pagination-wrapper').html(response.data.pagination_html);
                        } else {
                            $('#maneli-inquiry-list-tbody').html('<tr><td colspan="7" style="text-align:center;">' + (response.data.message || 'خطایی رخ داد.') + '</td></tr>');
                             $('.maneli-pagination-wrapper').html('');
                        }
                    },
                    error: function() {
                        $('#maneli-inquiry-list-tbody').html('<tr><td colspan="7" style="text-align:center;">خطای ارتباط با سرور.</td></tr>');
                    },
                    complete: function() {
                        $('#inquiry-list-loader').hide();
                        $('#maneli-inquiry-list-tbody').css('opacity', 1);
                    }
                });
            }

            fetch_inquiries(1);

            $('#inquiry-search-input').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    fetch_inquiries(1);
                }, 500);
            });

            $('#status-filter, #expert-filter').on('change', function() {
                fetch_inquiries(1);
            });

            $('.maneli-pagination-wrapper').on('click', 'a.page-numbers', function(e) {
                e.preventDefault();
                var pageUrl = $(this).attr('href');
                let pageNum = 1;
                
                const regex = /paged=(\d+)/;
                const matches = pageUrl.match(regex);

                if (matches) {
                    pageNum = parseInt(matches[1]);
                } else if (!$(this).hasClass('prev') && !$(this).hasClass('next')) {
                    pageNum = parseInt($(this).text());
                } else {
                    let currentPage = parseInt($('.maneli-pagination-wrapper .page-numbers.current').text());
                    currentPage = isNaN(currentPage) ? 1 : currentPage;
                    if ($(this).hasClass('prev')) {
                        pageNum = Math.max(1, currentPage - 1);
                    } else {
                        pageNum = currentPage + 1;
                    }
                }

                fetch_inquiries(pageNum);
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
	
	public function render_cash_inquiry_list() {
        if (isset($_GET['cash_inquiry_id']) && !empty($_GET['cash_inquiry_id'])) {
            $user_id = get_current_user_id();
            $inquiry_id = intval($_GET['cash_inquiry_id']);
            $inquiry_author_id = get_post_field('post_author', $inquiry_id);

            if (current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles)) {
                 return $this->render_single_cash_inquiry_page();
            }
            if ($user_id == $inquiry_author_id) {
                return $this->render_single_customer_cash_inquiry($inquiry_id);
            }
        }

        $options = get_option('maneli_inquiry_all_options', []);
        $rejection_reasons_raw = $options['cash_inquiry_rejection_reasons'] ?? '';
        $rejection_reasons = array_filter(array_map('trim', explode("\n", $rejection_reasons_raw)));

        $js_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/inquiry-actions.js';
        if (file_exists($js_path)) {
            wp_enqueue_script('maneli-inquiry-actions', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/inquiry-actions.js', ['jquery', 'sweetalert2'], '1.0.8', true);
            wp_localize_script('maneli-inquiry-actions', 'maneli_inquiry_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'details_nonce' => wp_create_nonce('maneli_inquiry_details_nonce'),
                'cash_details_nonce' => wp_create_nonce('maneli_cash_inquiry_details_nonce'),
                'cash_update_nonce' => wp_create_nonce('maneli_cash_inquiry_update_nonce'),
                'cash_delete_nonce' => wp_create_nonce('maneli_cash_inquiry_delete_nonce'),
                'cash_set_downpayment_nonce' => wp_create_nonce('maneli_cash_set_downpayment_nonce'),
                'cash_assign_expert_nonce' => wp_create_nonce('maneli_cash_inquiry_assign_expert_nonce'),
                'assign_nonce' => wp_create_nonce('maneli_inquiry_assign_expert_nonce'),
                'cash_rejection_reasons' => $rejection_reasons,
            ]);
        }
        
        if (!is_user_logged_in()) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>برای مشاهده این بخش، لطفاً ابتدا وارد شوید.</p></div>';
        }

        if (current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles)) {
            return $this->render_admin_cash_inquiry_list();
        } else {
            return $this->render_customer_cash_inquiry_list(get_current_user_id());
        }
    }

    private function render_admin_cash_inquiry_list() {
        ob_start();
        ?>
        <div class="maneli-full-width-container">
            <div class="maneli-inquiry-wrapper">
                <?php echo Maneli_Admin_Dashboard_Widgets::render_cash_inquiry_statistics_widgets(); ?>
                <h3 style="margin-top:40px;">لیست درخواست‌های خرید نقدی</h3>
                <div class="user-list-filters">
                    <form id="maneli-cash-inquiry-filter-form" onsubmit="return false;">
                        <div class="filter-row search-row">
                            <input type="search" id="cash-inquiry-search-input" class="search-input" placeholder="جستجو بر اساس نام مشتری، نام خودرو یا شماره موبایل...">
                        </div>
						<div class="filter-row">
                            <div class="filter-group">
                                <label for="cash-inquiry-status-filter">وضعیت:</label>
                                <select id="cash-inquiry-status-filter">
                                    <option value="">همه وضعیت‌ها</option>
                                    <option value="pending">در حال پیگیری</option>
                                    <option value="approved">ارجاع شده</option>
                                    <option value="rejected">رد شده</option>
                                    <option value="awaiting_payment">در انتظار پرداخت</option>
                                    <option value="completed">تکمیل شده</option>
                                </select>
                            </div>
                            <?php if (current_user_can('manage_maneli_inquiries')): 
                                $experts = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
                            ?>
                             <div class="filter-group">
                                <label for="cash-expert-filter">کارشناس مسئول:</label>
                                <select id="cash-expert-filter" class="maneli-select2">
                                    <option value="">همه کارشناسان</option>
                                    <?php foreach ($experts as $expert) : ?>
                                        <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="maneli-table-wrapper">
                    <table class="shop_table shop_table_responsive">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>مشتری</th>
                                <th>موبایل</th>
                                <th>خودرو</th>
                                <th>وضعیت</th>
                                <th>ارجاع</th>
                                <th>تاریخ ثبت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody id="maneli-cash-inquiry-list-tbody"></tbody>
                    </table>
                </div>
                <div id="cash-inquiry-list-loader" style="display: none; text-align:center; padding: 40px;"><div class="spinner is-active" style="float:none;"></div></div>
                <div class="maneli-cash-pagination-wrapper" style="margin-top: 20px; text-align: center;"></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            if (typeof $.fn.select2 === 'function' && $('.maneli-select2').length) {
                $('.maneli-select2').select2({
                     placeholder: "یک کارشناس انتخاب کنید",
                     allowClear: true,
                     width: '100%'
                });
            }

            var xhr;
            var searchTimeout;

            function fetch_cash_inquiries(page = 1) {
                if (xhr && xhr.readyState !== 4) xhr.abort();
                $('#cash-inquiry-list-loader').show();
                $('#maneli-cash-inquiry-list-tbody').css('opacity', 0.5);
                
                var ajaxData = {
                    action: 'maneli_filter_cash_inquiries_ajax',
                    _ajax_nonce: '<?php echo wp_create_nonce("maneli_cash_inquiry_filter_nonce"); ?>',
                    page: page,
                    search: $('#cash-inquiry-search-input').val(),
                    status: $('#cash-inquiry-status-filter').val(),
                    base_url: '<?php echo esc_url(remove_query_arg("cash_inquiry_id")); ?>'
                };

                if ($('#cash-expert-filter').length) {
                    ajaxData.expert = $('#cash-expert-filter').val();
                }

                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: ajaxData,
                    success: function(response) {
                        if (response.success) {
                            $('#maneli-cash-inquiry-list-tbody').html(response.data.html);
                            $('.maneli-cash-pagination-wrapper').html(response.data.pagination_html);
                        } else {
                            $('#maneli-cash-inquiry-list-tbody').html('<tr><td colspan="8" style="text-align:center;">' + (response.data.message || 'خطایی رخ داد.') + '</td></tr>');
                            $('.maneli-cash-pagination-wrapper').html('');
                        }
                    },
                    error: function() {
                        $('#maneli-cash-inquiry-list-tbody').html('<tr><td colspan="8" style="text-align:center;">خطای ارتباط با سرور.</td></tr>');
                    },
                    complete: function() {
                        $('#cash-inquiry-list-loader').hide();
                        $('#maneli-cash-inquiry-list-tbody').css('opacity', 1);
                    }
                });
            }

            fetch_cash_inquiries(1);

            $('#cash-inquiry-search-input').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => fetch_cash_inquiries(1), 500);
            });
			
			$('#cash-inquiry-status-filter, #cash-expert-filter').on('change', function() {
                fetch_cash_inquiries(1);
            });

            $('.maneli-cash-pagination-wrapper').on('click', 'a.page-numbers', function(e) {
                e.preventDefault();
                var pageUrl = $(this).attr('href');
                let pageNum = 1;
                const matches = pageUrl.match(/paged=(\d+)/);
                if (matches) pageNum = parseInt(matches[1]);
                fetch_cash_inquiries(pageNum);
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private function render_customer_cash_inquiry_list($user_id) {
        if (isset($_GET['payment_status'])) {
            // We need access to the display_payment_message method, which is now in the form shortcode class.
            // For now, let's create an instance.
            $form_shortcode = new Maneli_Inquiry_Form_Shortcode();
            $form_shortcode->display_payment_message(sanitize_text_field($_GET['payment_status']));
        }
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $args = [
            'post_type'      => 'cash_inquiry',
            'posts_per_page' => 50,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'author'         => $user_id
        ];
        $inquiries_query = new WP_Query($args);
        $inquiries = $inquiries_query->get_posts();
        $current_url = remove_query_arg('cash_inquiry_id');

        ob_start();
        ?>
        <div class="maneli-inquiry-wrapper">
            <h3>لیست درخواست‌های خرید نقدی شما</h3>
            <div class="status-box status-pending" style="margin-bottom:20px;"><p>قیمت‌ اعلام شده تقریبی بوده و با توجه به نوسانات بازار، تا پیش از نهایی شدن خرید، قابل تغییر است.</p></div>
            <?php if (empty($inquiries)): ?>
                <div class="status-box status-pending"><p>تاکنون هیچ درخواست خرید نقدی برای شما ثبت نشده است.</p></div>
            <?php else: ?>
                <table class="shop_table shop_table_responsive my_account_orders">
                    <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>خودرو</th>
                            <th>وضعیت</th>
                            <th>تاریخ ثبت</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $inquiry):
                            $inquiry_id = $inquiry->ID;
                            $product_id = get_post_meta($inquiry_id, 'product_id', true);
                            $status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
                            $report_url = add_query_arg('cash_inquiry_id', $inquiry_id, $current_url);
                            $gregorian_date = get_the_date('Y-m-d', $inquiry_id);
                            list($y, $m, $d) = explode('-', $gregorian_date);
                        ?>
                        <tr>
                            <td data-title="شناسه">#<?php echo esc_html($inquiry_id); ?></td>
                            <td data-title="خودرو"><?php echo esc_html(get_the_title($product_id)); ?></td>
                            <td data-title="وضعیت"><?php echo esc_html(Maneli_Admin_Dashboard_Widgets::get_cash_inquiry_status_label($status)); ?></td>
                            <td data-title="تاریخ"><?php echo esc_html(maneli_gregorian_to_jalali($y, $m, $d, 'Y/m/d')); ?></td>
                            <td class="woocommerce-orders-table__cell-order-actions">
                                <a href="<?php echo esc_url($report_url); ?>" class="button view">مشاهده جزئیات</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                 <div class="maneli-pagination-wrapper" style="margin-top: 20px; text-align: center;">
                    <?php
                    echo paginate_links([
                        'base' => get_permalink() . '%_%',
                        'format' => '?paged=%#%',
                        'current' => $paged,
                        'total' => $inquiries_query->max_num_pages,
                        'prev_text' => '« قبلی',
                        'next_text' => 'بعدی »',
                        'type'  => 'plain'
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_customer_inquiry_list($user_id) {
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $args = [
            'post_type'      => 'inquiry',
            'posts_per_page' => 50,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'author'         => $user_id
        ];
        $inquiries_query = new WP_Query($args);
        $inquiries = $inquiries_query->get_posts();
    
        ob_start();
        echo '<div class="maneli-inquiry-wrapper">';
    
        if (empty($inquiries)) {
            echo '<div class="status-box status-pending"><p>تاکنون هیچ استعلامی برای شما ثبت نشده است.</p></div>';
        } else {
            $current_url = remove_query_arg(['inquiry_id']); 
    
            echo '<h3>لیست استعلام‌های شما</h3>';
            echo '<table class="shop_table shop_table_responsive my_account_orders">';
            echo '<thead><tr>';
            echo '<th><span class="nobr">شناسه</span></th>';
            echo '<th><span class="nobr">خودرو</span></th>';
            echo '<th><span class="nobr">وضعیت</span></th>';
            echo '<th><span class="nobr">تاریخ ثبت</span></th>';
            echo '<th></th>';
            echo '</tr></thead>';
            echo '<tbody>';
    
            foreach ($inquiries as $inquiry) {
                $inquiry_id = $inquiry->ID;
                $product_id = get_post_meta($inquiry_id, 'product_id', true);
                $status = get_post_meta($inquiry_id, 'inquiry_status', true);
                $report_url = add_query_arg('inquiry_id', $inquiry_id, $current_url); 
                $gregorian_date = get_the_date('Y-m-d', $inquiry_id);
                list($y, $m, $d) = explode('-', $gregorian_date);
                
                echo '<tr>';
                echo '<td data-title="شناسه">#' . esc_html($inquiry_id) . '</td>';
                echo '<td data-title="خودرو">' . esc_html(get_the_title($product_id)) . '</td>';
                echo '<td data-title="وضعیت">' . esc_html(Maneli_CPT_Handler::get_status_label($status)) . '</td>';
                echo '<td data-title="تاریخ">' . esc_html(maneli_gregorian_to_jalali($y, $m, $d, 'Y/m/d')) . '</td>';
                echo '<td class="woocommerce-orders-table__cell-order-actions"><a href="' . esc_url($report_url) . '" class="button view">مشاهده جزئیات</a></td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';

            echo '<div class="maneli-pagination-wrapper" style="margin-top: 20px; text-align: center;">';
            echo paginate_links([
                'base' => get_permalink() . '%_%',
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => $inquiries_query->max_num_pages,
                'prev_text' => '« قبلی',
                'next_text' => 'بعدی »',
                'type'  => 'plain'
            ]);
            echo '</div>';
        }
        echo '</div>';
    
        return ob_get_clean();
    }
    
    private function render_inquiry_row($inquiry_id, $base_url) {
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $status = get_post_meta($inquiry_id, 'inquiry_status', true);
        $report_url = add_query_arg('inquiry_id', $inquiry_id, $base_url);
        $gregorian_date = get_the_date('Y-m-d', $inquiry_id);
        list($y, $m, $d) = explode('-', $gregorian_date);
        $customer = get_userdata(get_post_field('post_author', $inquiry_id));
        ?>
        <tr>
            <td data-title="شناسه">#<?php echo esc_html($inquiry_id); ?></td>
            <td data-title="مشتری"><?php echo esc_html($customer->display_name); ?></td>
            <td data-title="خودرو"><?php echo esc_html(get_the_title($product_id)); ?></td>
            <td data-title="وضعیت"><?php echo esc_html(Maneli_CPT_Handler::get_status_label($status)); ?></td>
            <?php if (current_user_can('manage_maneli_inquiries')): 
                $expert_name = get_post_meta($inquiry_id, 'assigned_expert_name', true);
            ?>
                <td data-title="ارجاع">
                    <?php if ($expert_name): ?>
                        <?php echo esc_html($expert_name); ?>
                    <?php else: ?>
                        <button class="button assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">ارجاع</button>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
            <td data-title="تاریخ"><?php echo esc_html(maneli_gregorian_to_jalali($y, $m, $d, 'Y/m/d')); ?></td>
            <td class="woocommerce-orders-table__cell-order-actions">
                <a href="<?php echo esc_url($report_url); ?>" class="button view">مشاهده جزئیات</a>
            </td>
        </tr>
        <?php
    }
	
	private function render_cash_inquiry_row($inquiry_id, $base_url) {
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $gregorian_date = get_the_date('Y-m-d', $inquiry_id);
        list($y, $m, $d) = explode('-', $gregorian_date);
        $customer_name = get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true);
		$status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
        $status_label = Maneli_Admin_Dashboard_Widgets::get_cash_inquiry_status_label($status);
        $report_url = add_query_arg('cash_inquiry_id', $inquiry_id, $base_url);
        $expert_name = get_post_meta($inquiry_id, 'assigned_expert_name', true);
        ?>
        <tr>
            <td data-title="شناسه">#<?php echo esc_html($inquiry_id); ?></td>
            <td data-title="مشتری"><?php echo esc_html($customer_name); ?></td>
            <td data-title="موبایل"><?php echo esc_html(get_post_meta($inquiry_id, 'mobile_number', true)); ?></td>
            <td data-title="خودرو"><?php echo esc_html(get_the_title($product_id)); ?></td>
            <td data-title="وضعیت"><?php echo esc_html($status_label); ?></td>
            <td data-title="ارجاع">
                <?php if ($expert_name): ?>
                    <?php echo esc_html($expert_name); ?>
                <?php else: ?>
                    <button class="button assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash">ارجاع</button>
                <?php endif; ?>
            </td>
            <td data-title="تاریخ"><?php echo esc_html(maneli_gregorian_to_jalali($y, $m, $d, 'Y/m/d')); ?></td>
			<td data-title="عملیات" class="cash-inquiry-actions">
				<a href="<?php echo esc_url($report_url); ?>" class="button view">مشاهده جزئیات</a>
			</td>
        </tr>
        <?php
    }

    public function render_frontend_credit_report() {
        if (!is_user_logged_in()) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای مشاهده این محتوا را ندارید.</p></div>';
        }
        if (!isset($_GET['inquiry_id'])) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شناسه استعلام مشخص نشده است.</p></div>';
        }
        
        $inquiry_id = intval($_GET['inquiry_id']);
        $inquiry = get_post($inquiry_id);
        
        if (!$inquiry || $inquiry->post_type !== 'inquiry') {
            return '<div class="maneli-inquiry-wrapper error-box"><p>استعلام یافت نشد.</p></div>';
        }

        $current_user = wp_get_current_user();
        $inquiry_author_id = (int)$inquiry->post_author;
        $assigned_expert_id = (int)get_post_meta($inquiry_id, 'assigned_expert_id', true);
        
        $can_view_as_admin = current_user_can('manage_maneli_inquiries');
        $can_view_as_expert = in_array('maneli_expert', $current_user->roles) && $assigned_expert_id === $current_user->ID;
        $can_view_as_customer = $inquiry_author_id === $current_user->ID;

        if (!$can_view_as_admin && !$can_view_as_expert && !$can_view_as_customer) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما اجازه مشاهده این گزارش را ندارید.</p></div>';
        }
        
        if ($can_view_as_customer && !$can_view_as_admin && !$can_view_as_expert) {
             // We need access to this method from the form shortcode class
             $form_shortcode = new Maneli_Inquiry_Form_Shortcode();
             return $form_shortcode->render_customer_report_html($inquiry_id);
        }

        $post_meta = get_post_meta($inquiry_id);
        $finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
        $cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;
        $product_id = $post_meta['product_id'][0] ?? 0;
        $status = $post_meta['inquiry_status'][0] ?? 'pending';
        $back_link = remove_query_arg('inquiry_id');


        $status_map = [
            'user_confirmed' => ['label' => 'تایید و ارجاع شده', 'class' => 'status-bg-approved'],
            'rejected'       => ['label' => 'رد شده', 'class' => 'status-bg-rejected'],
            'more_docs'      => ['label' => 'نیازمند مدارک', 'class' => 'status-bg-pending'],
            'pending'        => ['label' => 'در حال بررسی', 'class' => 'status-bg-pending'],
            'failed'         => ['label' => 'استعلام ناموفق', 'class' => 'status-bg-rejected'],
        ];
        $status_info = $status_map[$status] ?? ['label' => 'نامشخص', 'class' => ''];

        // Helper function for rendering fields in the frontend report
        $render_fields_grid = function($fields, $post_meta) {
            echo '<div class="form-grid">';
            $field_pairs = array_chunk($fields, 2, true);
            foreach ($field_pairs as $pair) {
                echo '<div class="form-row">';
                foreach($pair as $key => $label) {
                    $value = $post_meta[$key][0] ?? '—';
                    if (strpos($key, 'residency_status') !== false) {
                        $value = ($value === 'owner') ? 'مالک' : (($value === 'tenant') ? 'مستاجر' : $value);
                    }
                    if (strpos($key, 'workplace_status') !== false) {
                        $statuses = ['permanent' => 'رسمی', 'contract' => 'قراردادی', 'freelance' => 'آزاد'];
                        $value = $statuses[$value] ?? $value;
                    }
                    echo '<div class="form-group"><label>' . esc_html($label) . '</label><div class="detail-value-box">' . esc_html($value) . '</div></div>';
                }
                if (count($pair) < 2) { echo '<div class="form-group"></div>'; }
                echo '</div>';
            }
            echo '</div>';
        };

        ob_start();
        ?>
        <div class="maneli-inquiry-wrapper frontend-expert-report">
            <h2 class="report-main-title">گزارش کامل اعتبار <small>(برای استعلام #<?php echo esc_html($inquiry_id); ?>)</small></h2>

            <div class="report-status-box <?php echo esc_attr($status_info['class']); ?>">
                <strong>وضعیت فعلی:</strong> <?php echo esc_html($status_info['label']); ?>
            </div>
            
            <div class="report-box">
                <h3 class="report-box-title">اطلاعات خودرو و شرایط اقساط</h3>
                <div class="report-car-image">
                    <?php
                    if ($product_id && has_post_thumbnail($product_id)) {
                        echo get_the_post_thumbnail($product_id, 'medium');
                    }
                    ?>
                </div>
                <table class="summary-table">
                    <tbody>
                        <tr><th>خودروی انتخابی</th><td><?php echo get_the_title($product_id); ?></td></tr>
                        <tr><th>قیمت کل</th><td><?php printf('%s <span>تومان</span>', number_format_i18n((int)($post_meta['maneli_inquiry_total_price'][0] ?? 0))); ?></td></tr>
                        <tr><th>مقدار پیش پرداخت</th><td><?php printf('%s <span>تومان</span>', number_format_i18n((int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0))); ?></td></tr>
                        <tr><th>تعداد اقساط</th><td><?php printf('%s <span>ماهه</span>', esc_html($post_meta['maneli_inquiry_term_months'][0] ?? 0)); ?></td></tr>
                        <tr><th>مبلغ هر قسط</th><td><?php printf('%s <span>تومان</span>', number_format_i18n((int)($post_meta['maneli_inquiry_installment'][0] ?? 0))); ?></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="report-box">
                <h3 class="report-box-title">مشخصات مراجعه کننده</h3>
                 <?php
                $buyer_fields = [
                    'first_name' => 'نام', 'last_name' => 'نام خانوادگی', 'father_name' => 'نام پدر', 'national_code' => 'کد ملی',
                    'occupation' => 'شغل', 'income_level' => 'میزان درآمد', 'mobile_number' => 'شماره همراه', 'phone_number' => 'تلفن ثابت',
                    'residency_status' => 'وضعیت محل سکونت', 'workplace_status' => 'وضعیت محل کار', 'address' => 'آدرس', 'birth_date' => 'تاریخ تولد',
                    'bank_name' => 'نام بانک', 'account_number' => 'شماره حساب', 'branch_code' => 'کد شعبه', 'branch_name' => 'نام شعبه'
                ];
                $render_fields_grid($buyer_fields, $post_meta);
                ?>
            </div>
            
            <?php
            $issuer_type = $post_meta['issuer_type'][0] ?? 'self';
            if ($issuer_type === 'other'):
            ?>
            <div class="report-box">
                <h3 class="report-box-title">اطلاعات صادر کننده چک</h3>
                <?php
                $issuer_fields = [
                    'issuer_full_name' => 'نام صادر کننده', 'issuer_national_code' => 'کد ملی صادر کننده', 'issuer_bank_name' => 'نام بانک',
                    'issuer_account_number' => 'شماره حساب', 'issuer_branch_code' => 'کد شعبه', 'issuer_branch_name' => 'نام شعبه',
                    'issuer_residency_status' => 'وضعیت سکونت', 'issuer_workplace_status' => 'وضعیت شغلی',
                    'issuer_father_name' => 'نام پدر', 'issuer_occupation' => 'شغل', 'issuer_phone_number' => 'شماره تماس', 'issuer_address' => 'آدرس'
                ];
                $render_fields_grid($issuer_fields, $post_meta);
                ?>
            </div>
            <?php endif; ?>

            <div class="report-box">
                <h3 class="report-box-title">نتیجه استعلام وضعیت چک (صیادی)</h3>

                <?php 
                $finotex_skipped = (empty($finotex_data) || (isset($finotex_data['status']) && $finotex_data['status'] === 'SKIPPED'));
                if ($finotex_skipped): 
                ?>
                     <table class="summary-table right-aligned-table" style="margin-top: 20px;">
                        <tbody>
                             <tr>
                                <th>وضعیت اعتباری</th>
                                <td>نامشخص</td>
                            </tr>
                            <tr>
                                <th>توضیح</th>
                                <td>
                                    <?php if ($can_view_as_admin): ?>
                                        استعلام فینوتک در تنظیمات غیرفعال است.
                                    <?php else: ?>
                                        استعلام بانکی انجام نشده است.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php if ($can_view_as_admin): ?>
                        <div class="admin-notice" style="margin-top: 20px;">
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                 <input type="hidden" name="action" value="maneli_admin_retry_finotex">
                                 <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                                 <?php wp_nonce_field('maneli_retry_finotex_nonce'); ?>
                                 <button type="submit" class="action-btn approve">انجام مجدد استعلام فینوتک</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="maneli-status-bar">
                        <?php
                        $colors = [ 1 => ['name' => 'سفید', 'class' => 'white'], 2 => ['name' => 'زرد', 'class' => 'yellow'], 3 => ['name' => 'نارنجی', 'class' => 'orange'], 4 => ['name' => 'قهوه‌ای', 'class' => 'brown'], 5 => ['name' => 'قرمز', 'class' => 'red'] ];
                        foreach ($colors as $code => $color) {
                            $active_class = ((string)$code === (string)$cheque_color_code) ? 'active' : '';
                            echo "<div class='bar-segment segment-{$color['class']} {$active_class}'><span>" . esc_html($color['name']) . "</span></div>";
                        }
                        ?>
                    </div>
                    <table class="summary-table right-aligned-table" style="margin-top: 20px;">
                        <tbody>
                            <?php
                            $cheque_color_map = [
                                '1' => ['text' => 'سفید', 'desc' => 'فاقد هرگونه سابقه چک برگشتی.'],'2' => ['text' => 'زرد', 'desc' => 'یک فقره چک برگشتی یا حداکثر مبلغ 50 میلیون ریال تعهد برگشتی.'],'3' => ['text' => 'نارنجی', 'desc' => 'دو الی چهار فقره چک برگشتی یا حداکثر مبلغ 200 میلیون ریال تعهد برگشتی.'],'4' => ['text' => 'قهوه‌ای', 'desc' => 'پنج تا ده فقره چک برگشتی یا حداکثر مبلغ 500 میلیون ریال تعهد برگشتی.'],'5' => ['text' => 'قرمز', 'desc' => 'بیش از ده فقره چک برگشتی یا بیش از مبلغ 500 میلیون ریال تعهد برگشتی.'], 0  => ['text' => 'نامشخص', 'desc' => 'اطلاعاتی از فینوتک دریافت نشد یا استعلام ناموفق بود.']];
                            $color_info = $cheque_color_map[$cheque_color_code] ?? $cheque_color_map[0];
                            ?>
                            <tr><th>وضعیت اعتباری</th><td><strong class="cheque-color-<?php echo esc_attr($cheque_color_code); ?>"><?php echo esc_html($color_info['text']); ?></strong></td></tr>
                            <tr><th>توضیح</th><td><?php echo esc_html($color_info['desc']); ?></td></tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <?php if ($can_view_as_admin): ?>
            <div class="admin-actions-box">
                <h3 class="report-box-title">تصمیم نهایی</h3>
                <p>پس از بررسی اطلاعات بالا، وضعیت نهایی این درخواست را مشخص کنید.</p>
                <form id="admin-action-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="maneli_admin_update_status">
                    <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                    <input type="hidden" id="final-status-input" name="new_status" value="">
                    <input type="hidden" id="rejection-reason-input" name="rejection_reason" value="">
                    <?php wp_nonce_field('maneli_admin_update_status_nonce'); ?>

                    <div class="action-button-group">
                        <div class="approve-section">
                            <label for="assigned_expert_id_frontend">ارجاع به کارشناس:</label>
                             <?php
                                $experts = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
                                if (!empty($experts)):
                            ?>
                            <select name="assigned_expert_id" id="assigned_expert_id_frontend">
                                <option value="auto">-- انتساب خودکار (گردشی) --</option>
                                <?php foreach ($experts as $expert) : ?>
                                    <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                            <button type="button" id="approve-btn" class="action-btn approve">
                                &#10004; تایید و ارجاع
                            </button>
                        </div>
                        <button type="button" id="reject-btn" class="action-btn reject">
                            &#10006; رد نهایی درخواست
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="report-back-button-wrapper">
                <a href="<?php echo esc_url($back_link); ?>" class="loan-action-btn">بازگشت به لیست استعلام‌ها</a>
            </div>
        </div>
        
        <?php if ($can_view_as_admin): ?>
        <div id="rejection-modal" class="maneli-modal-frontend" style="display:none;">
            <div class="modal-content">
                <span class="modal-close">&times;</span><h3>دلیل رد درخواست</h3>
                <p>لطفاً دلیل رد این درخواست را مشخص کنید. این دلیل به کاربر پیامک خواهد شد.</p>
                <div class="form-group"><label for="rejection-reason-select">انتخاب دلیل:</label><select id="rejection-reason-select" style="width: 100%;"><option value="">-- یک دلیل انتخاب کنید --</option><option value="متاسفانه در حال حاضر امکان خرید با این مبلغ پیش‌پرداخت وجود ندارد.">مبلغ پیش‌پرداخت کافی نیست.</option><option value="متاسفانه سابقه اعتباری شما برای خرید این خودرو مورد تایید قرار نگرفت.">سابقه اعتباری مورد تایید نیست.</option><option value="مدارک ارسالی شما ناقص یا نامعتبر است. لطفاً با پشتیبانی تماس بگیرید.">مدارک ناقص یا نامعتبر.</option><option value="custom">دلیل دیگر (در کادر زیر بنویسید)</option></select></div>
                <div class="form-group" id="custom-reason-wrapper" style="display:none;"><label for="rejection-reason-custom">متن سفارشی:</label><textarea id="rejection-reason-custom" rows="3" style="width: 100%;"></textarea></div>
                <button type="button" id="confirm-rejection-btn" class="button button-primary">ثبت دلیل و رد کردن درخواست</button>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const adminActionForm = document.getElementById('admin-action-form');
            if (!adminActionForm) return;

            const finalStatusInput = document.getElementById('final-status-input');
            const approveBtn = document.getElementById('approve-btn');
            
            if (approveBtn) {
                approveBtn.addEventListener('click', function() {
                    finalStatusInput.value = 'approved';
                    adminActionForm.submit();
                });
            }

            const modal = document.getElementById('rejection-modal');
            const rejectBtn = document.getElementById('reject-btn');
            const closeModalBtn = document.querySelector('.modal-close');
            const confirmRejectionBtn = document.getElementById('confirm-rejection-btn');
            const reasonSelect = document.getElementById('rejection-reason-select');
            const customReasonWrapper = document.getElementById('custom-reason-wrapper');
            const customReasonText = document.getElementById('rejection-reason-custom');
            const finalReasonInput = document.getElementById('rejection-reason-input');

            if (rejectBtn) {
                rejectBtn.addEventListener('click', function() {
                    modal.style.display = 'block';
                });
            }

            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            }
            
            window.addEventListener('click', function(e) {
                if (e.target == modal) {
                    modal.style.display = 'none';
                }
            });

            if (reasonSelect) {
                reasonSelect.addEventListener('change', function() {
                    if (this.value === 'custom') {
                        customReasonWrapper.style.display = 'block';
                    } else {
                        customReasonWrapper.style.display = 'none';
                    }
                });
            }

            if (confirmRejectionBtn) {
                confirmRejectionBtn.addEventListener('click', function() {
                    let reason = reasonSelect.value;
                    if (reason === 'custom') {
                        reason = customReasonText.value;
                    }

                    if (!reason) {
                        alert('لطفاً یک دلیل برای رد درخواست انتخاب یا وارد کنید.');
                        return;
                    }

                    finalReasonInput.value = reason;
                    finalStatusInput.value = 'rejected';
                    adminActionForm.submit();
                });
            }
        });
        </script>
        <?php endif; ?>

        <?php
        return ob_get_clean();
    }
    
    private function render_single_cash_inquiry_page() {
        if (!current_user_can('manage_maneli_inquiries')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p></div>';
        }

        $inquiry_id = intval($_GET['cash_inquiry_id']);
        $inquiry = get_post($inquiry_id);

        if (!$inquiry || $inquiry->post_type !== 'cash_inquiry') {
            return '<div class="maneli-inquiry-wrapper error-box"><p>درخواست مورد نظر یافت نشد.</p></div>';
        }

        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
        $status_label = Maneli_Admin_Dashboard_Widgets::get_cash_inquiry_status_label($status);
        $expert_name = get_post_meta($inquiry_id, 'assigned_expert_name', true);
        $back_link = remove_query_arg('cash_inquiry_id');

        ob_start();
        ?>
        <div class="maneli-inquiry-wrapper frontend-expert-report" id="cash-inquiry-details" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>">
            <h2 class="report-main-title">جزئیات درخواست خرید نقدی <small>(#<?php echo esc_html($inquiry_id); ?>)</small></h2>
            
            <div class="report-status-box status-bg-pending">
                <strong>وضعیت فعلی:</strong> <?php echo esc_html($status_label); ?>
                <?php if ($expert_name): ?>
                    <br><strong>کارشناس مسئول:</strong> <?php echo esc_html($expert_name); ?>
                <?php endif; ?>
            </div>

            <div class="report-box">
                <h3 class="report-box-title">اطلاعات درخواست</h3>
                <table class="summary-table">
                    <tbody>
                        <tr><th>مشتری</th><td><?php echo esc_html(get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true)); ?></td></tr>
                        <tr><th>شماره موبایل</th><td><?php echo esc_html(get_post_meta($inquiry_id, 'mobile_number', true)); ?></td></tr>
                        <tr><th>خودرو</th><td><?php echo esc_html(get_the_title($product_id)); ?></td></tr>
                        <tr><th>رنگ درخواستی</th><td><?php echo esc_html(get_post_meta($inquiry_id, 'cash_car_color', true)); ?></td></tr>
                        <?php 
                        $down_payment = get_post_meta($inquiry_id, 'cash_down_payment', true);
                        if (!empty($down_payment)): ?>
                            <tr><th>مبلغ پیش‌پرداخت</th><td><?php echo number_format_i18n($down_payment) . ' تومان'; ?></td></tr>
                        <?php endif; ?>
                         <?php 
                        $rejection_reason = get_post_meta($inquiry_id, 'cash_rejection_reason', true);
                        if (!empty($rejection_reason)): ?>
                            <tr><th>دلیل رد</th><td><?php echo esc_html($rejection_reason); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="admin-actions-box">
                <h3 class="report-box-title">عملیات</h3>
                <div class="action-button-group" style="justify-content: center;">
                    <?php if (!$expert_name): ?>
                        <button type="button" class="action-btn assign-expert-btn" style="background-color: #17a2b8;" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash">ارجاع به کارشناس</button>
                    <?php endif; ?>
                    <button type="button" class="action-btn" id="edit-cash-inquiry-btn" style="background-color: #ffc107; color: #212529;">ویرایش اطلاعات</button>
                    <button type="button" class="action-btn" id="delete-cash-inquiry-btn" style="background-color: #dc3545;">حذف درخواست</button>

                    <?php if ($status === 'pending'): ?>
                        <button type="button" class="action-btn approve" id="set-downpayment-btn">تعیین پیش‌پرداخت</button>
                        <button type="button" class="action-btn reject" id="reject-cash-inquiry-btn">رد کردن درخواست</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="report-back-button-wrapper">
                <a href="<?php echo esc_url($back_link); ?>" class="loan-action-btn">بازگشت به لیست</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_single_customer_cash_inquiry($inquiry_id) {
        $inquiry = get_post($inquiry_id);
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $status_key = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
        $status_label = Maneli_Admin_Dashboard_Widgets::get_cash_inquiry_status_label($status_key);
        $back_link = remove_query_arg('cash_inquiry_id');
        
        $status_classes = [
            'pending' => 'status-bg-pending',
            'approved' => 'status-bg-approved',
            'rejected' => 'status-bg-rejected',
            'awaiting_payment' => 'status-bg-awaiting-payment',
            'completed' => 'status-bg-completed',
        ];
        $status_class = $status_classes[$status_key] ?? 'status-bg-pending';

        ob_start();
        ?>
        <div class="maneli-inquiry-wrapper customer-cash-report">
            <h2 class="report-main-title">جزئیات درخواست خرید نقدی <small>(#<?php echo esc_html($inquiry_id); ?>)</small></h2>
            
            <div class="status-box status-pending" style="margin-bottom:20px;"><p>قیمت درج‌شده حدودی است و به دلیل نوسانات بازار، قیمت قطعی پس از واریز پیش‌پرداخت مشخص و نهایی می‌شود.</p></div>

            <div class="report-status-box <?php echo esc_attr($status_class); ?>">
                <strong>وضعیت فعلی:</strong> <?php echo esc_html($status_label); ?>
            </div>

            <div class="report-box">
                <h3 class="report-box-title">اطلاعات درخواست</h3>
                 <div class="report-car-image">
                    <?php if ($product_id && has_post_thumbnail($product_id)): ?>
                        <?php echo get_the_post_thumbnail($product_id, 'medium'); ?>
                    <?php endif; ?>
                </div>
                <table class="summary-table">
                    <tbody>
                        <tr><th>خودرو</th><td><?php echo esc_html(get_the_title($product_id)); ?></td></tr>
                        <tr><th>رنگ درخواستی</th><td><?php echo esc_html(get_post_meta($inquiry_id, 'cash_car_color', true)); ?></td></tr>
                        <?php 
                        $down_payment = get_post_meta($inquiry_id, 'cash_down_payment', true);
                        if (!empty($down_payment)): ?>
                            <tr><th>مبلغ پیش‌پرداخت</th><td><?php echo number_format_i18n($down_payment) . ' تومان'; ?></td></tr>
                        <?php endif; ?>
                         <?php 
                        $rejection_reason = get_post_meta($inquiry_id, 'cash_rejection_reason', true);
                        if (!empty($rejection_reason)): ?>
                            <tr><th>توضیحات</th><td><?php echo esc_html($rejection_reason); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($status_key === 'awaiting_payment'): ?>
            <div class="admin-actions-box">
                <h3 class="report-box-title">اقدام مورد نیاز</h3>
                 <p style="font-weight: bold;">لطفا توجه فرمایید: قیمت‌ها به صورت تقریبی اعلام شده‌اند و قیمت نهایی خودرو بر اساس نرخ روز در زمان پرداخت پیش‌پرداخت مشخص خواهد شد.</p>
                 <p>پیش پرداخت شما تعیین شده است. برای نهایی کردن خرید خود، لطفاً از طریق دکمه زیر وارد درگاه پرداخت شوید.</p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="text-align:center;">
                    <input type="hidden" name="action" value="maneli_start_cash_payment">
                    <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                    <?php wp_nonce_field('maneli_start_cash_payment_nonce'); ?>
                    <button type="submit" class="loan-action-btn">ورود به درگاه پرداخت</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="report-back-button-wrapper">
                <a href="<?php echo esc_url($back_link); ?>" class="loan-action-btn">بازگشت به لیست درخواست‌ها</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}