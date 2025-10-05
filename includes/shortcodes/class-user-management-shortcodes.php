<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_User_Management_Shortcodes {

    public function __construct() {
        add_shortcode('maneli_user_list', [$this, 'render_user_list_shortcode']);
        add_action('wp_ajax_maneli_delete_user_ajax', [$this, 'handle_delete_user_ajax']);
        add_action('wp_ajax_maneli_filter_users_ajax', [$this, 'handle_filter_users_ajax']);
    }

    public function handle_filter_users_ajax() {
        check_ajax_referer('maneli_user_filter_nonce');

        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }

        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $filter_role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'display_name';
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'ASC';
        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;

        $query_args = [
            'orderby' => $orderby,
            'order'   => $order,
            'number'  => 50,
            'paged'   => $paged,
        ];

        if (!empty($filter_role)) {
            $query_args['role'] = $filter_role;
        }

        if (!empty($search_term)) {
            $query_args['search'] = '*' . esc_attr($search_term) . '*';
            $query_args['search_columns'] = ['user_login', 'user_email', 'display_name'];
            $query_args['meta_query'] = [
                'relation' => 'OR',
                ['key' => 'first_name', 'value' => $search_term, 'compare' => 'LIKE'],
                ['key' => 'last_name', 'value' => $search_term, 'compare' => 'LIKE'],
                ['key' => 'mobile_number', 'value' => $search_term, 'compare' => 'LIKE'],
                ['key' => 'national_code', 'value' => $search_term, 'compare' => 'LIKE'],
            ];
        }

        $user_query = new WP_User_Query($query_args);
        $all_users = $user_query->get_results();
        
        $current_user_id = get_current_user_id();
        $filtered_users = array_filter($all_users, function($user) use ($current_user_id) {
            return $user->ID !== $current_user_id;
        });

        ob_start();
        if (!empty($filtered_users)) {
            foreach ($filtered_users as $user) {
                $role_names = array_map(
                    function($role) {
                        global $wp_roles;
                        return $wp_roles->roles[$role]['name'] ?? $role;
                    },
                    $user->roles
                );
                $edit_link = add_query_arg('edit_user', $user->ID, $_POST['current_url']);
                ?>
                <tr>
                    <td data-title="نام نمایشی"><?php echo esc_html($user->display_name); ?></td>
                    <td data-title="نام کاربری"><?php echo esc_html($user->user_login); ?></td>
                    <td data-title="ایمیل"><?php echo esc_html($user->user_email); ?></td>
                    <td data-title="نقش"><?php echo esc_html(implode(', ', $role_names)); ?></td>
                    <td data-title="عملیات">
                        <a href="<?php echo esc_url($edit_link); ?>" class="button view">ویرایش</a>
                        <button class="button delete-user-btn" data-user-id="<?php echo esc_attr($user->ID); ?>">حذف</button>
                    </td>
                </tr>
                <?php
            }
        } else {
            ?>
            <tr>
                <td colspan="5" style="text-align:center;">هیچ کاربری با معیارهای جستجوی شما یافت نشد.</td>
            </tr>
            <?php
        }
        $html = ob_get_clean();

        $total_users = $user_query->get_total();
        $total_pages = ceil($total_users / 50);

        $pagination_html = paginate_links([
            'base' => '#',
            'format' => '?paged=%#%',
            'current' => $paged,
            'total' => $total_pages,
            'prev_text' => '« قبلی',
            'next_text' => 'بعدی »',
            'type'  => 'plain'
        ]);

        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }


    public function render_user_list_shortcode() {
        if (!current_user_can('manage_maneli_inquiries')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p></div>';
        }
    
        if (isset($_GET['add_user'])) {
            return $this->render_user_add_form();
        }
    
        if (isset($_GET['edit_user'])) {
            $user_id_to_edit = intval($_GET['edit_user']);
            return $this->render_user_edit_form($user_id_to_edit);
        }
        
        ob_start();

        echo Maneli_Admin_Dashboard_Widgets::render_user_statistics_widgets();

        if (isset($_GET['user-updated']) && $_GET['user-updated'] == 'true') {
            echo '<div class="status-box status-approved"><p>اطلاعات کاربر با موفقیت به‌روزرسانی شد.</p></div>';
        }
        if (isset($_GET['user-created']) && $_GET['user-created'] == 'true') {
            echo '<div class="status-box status-approved"><p>کاربر جدید با موفقیت ایجاد شد.</p></div>';
        }
        if (isset($_GET['user-deleted']) && $_GET['user-deleted'] == 'true') {
            echo '<div class="status-box status-approved"><p>کاربر با موفقیت حذف شد.</p></div>';
        }
        if (isset($_GET['error'])) {
            echo '<div class="status-box status-failed"><p>خطا: ' . esc_html(urldecode($_GET['error'])) . '</p></div>';
        }
        
        $current_url = remove_query_arg(['edit_user', 'user-updated', 'add_user', 'user-created', 'user-deleted', 'error'], $_SERVER['REQUEST_URI']);
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $user_query = new WP_User_Query([
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'number'  => 50,
            'paged'   => $paged
        ]);
        $all_users = $user_query->get_results();
        ?>
        <div class="maneli-inquiry-wrapper">
             <div class="user-list-header">
                <h3>لیست کامل کاربران</h3>
                <a href="<?php echo esc_url(add_query_arg('add_user', 'true', $current_url)); ?>" class="button button-primary">افزودن کاربر جدید</a>
            </div>
            
            <div class="user-list-filters">
                <form id="maneli-user-filter-form" onsubmit="return false;">
                    <div class="filter-row search-row">
                        <input type="search" id="user-search-input" name="s" class="search-input" placeholder="جستجو بر اساس نام، ایمیل، موبایل یا کد ملی...">
                    </div>
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="role-filter">نقش کاربری:</label>
                            <select name="role" id="role-filter">
                                <option value="">همه نقش‌ها</option>
                                <option value="customer">مشتری</option>
                                <option value="maneli_expert">کارشناس مانلی</option>
                                <option value="maneli_admin">مدیریت مانلی</option>
                                <option value="administrator">مدیر کل</option>
                            </select>
                        </div>
                         <div class="filter-group">
                            <label for="orderby-filter">مرتب‌سازی بر اساس:</label>
                            <select name="orderby" id="orderby-filter">
                                <option value="display_name">نام نمایشی</option>
                                <option value="user_registered">تاریخ ثبت‌نام</option>
                                <option value="user_login">نام کاربری</option>
                            </select>
                        </div>
                        <div class="filter-group">
                             <label for="order-filter">ترتیب:</label>
                            <select name="order" id="order-filter">
                                <option value="ASC">صعودی</option>
                                <option value="DESC">نزولی</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <table class="shop_table shop_table_responsive">
                <thead>
                    <tr>
                        <th>نام نمایشی</th>
                        <th>نام کاربری</th>
                        <th>ایمیل</th>
                        <th>نقش</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody id="maneli-user-list-tbody">
                    <?php if (!empty($all_users)): ?>
                        <?php foreach ($all_users as $user): 
                            if ($user->ID === get_current_user_id()) continue;
                            $role_names = array_map(function($role) { global $wp_roles; return $wp_roles->roles[$role]['name'] ?? $role; }, $user->roles);
                            $edit_link = add_query_arg('edit_user', $user->ID, $current_url);
                        ?>
                        <tr>
                            <td data-title="نام نمایشی"><?php echo esc_html($user->display_name); ?></td>
                            <td data-title="نام کاربری"><?php echo esc_html($user->user_login); ?></td>
                            <td data-title="ایمیل"><?php echo esc_html($user->user_email); ?></td>
                            <td data-title="نقش"><?php echo esc_html(implode(', ', $role_names)); ?></td>
                            <td data-title="عملیات">
                                <a href="<?php echo esc_url($edit_link); ?>" class="button view">ویرایش</a>
                                <button class="button delete-user-btn" data-user-id="<?php echo esc_attr($user->ID); ?>">حذف</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">هیچ کاربری یافت نشد.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
             <div id="user-list-loader" style="display:none; text-align:center; padding: 40px;"><div class="spinner is-active" style="float:none;"></div></div>
             <div class="maneli-pagination-wrapper" style="margin-top: 20px; text-align: center;">
                <?php
                $total_users = $user_query->get_total();
                $total_pages = ceil($total_users / 50);
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '?paged=%#%',
                    'current' => $paged,
                    'total' => $total_pages,
                    'prev_text' => '« قبلی',
                    'next_text' => 'بعدی »',
                ]);
                ?>
             </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var xhr;
            var searchTimeout;
            var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
            var filter_nonce = '<?php echo wp_create_nonce("maneli_user_filter_nonce"); ?>';
            var delete_nonce = '<?php echo wp_create_nonce("maneli_delete_user_nonce"); ?>';

            function fetch_users(page = 1) {
                if (xhr && xhr.readyState !== 4) {
                    xhr.abort();
                }

                $('#user-list-loader').show();
                $('#maneli-user-list-tbody').css('opacity', 0.5);

                var formData = {
                    action: 'maneli_filter_users_ajax',
                    _ajax_nonce: filter_nonce,
                    search: $('#user-search-input').val(),
                    role: $('#role-filter').val(),
                    orderby: $('#orderby-filter').val(),
                    order: $('#order-filter').val(),
                    page: page,
                    current_url: '<?php echo esc_url($current_url); ?>'
                };

                xhr = $.ajax({
                    url: ajax_url,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#maneli-user-list-tbody').html(response.data.html);
                            $('.maneli-pagination-wrapper').html(response.data.pagination_html);
                        } else {
                             $('#maneli-user-list-tbody').html('<tr><td colspan="5" style="text-align:center;">خطایی رخ داد.</td></tr>');
                             $('.maneli-pagination-wrapper').html('');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        if (textStatus !== 'abort') {
                             $('#maneli-user-list-tbody').html('<tr><td colspan="5" style="text-align:center;">خطای ارتباط با سرور.</td></tr>');
                        }
                    },
                    complete: function() {
                        $('#user-list-loader').hide();
                        $('#maneli-user-list-tbody').css('opacity', 1);
                    }
                });
            }
            
            $('#user-search-input').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    fetch_users(1);
                }, 500);
            });

            $('#role-filter, #orderby-filter, #order-filter').on('change', function() {
                fetch_users(1);
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
                fetch_users(pageNum);
            });

            $('#maneli-user-list-tbody').on('click', '.delete-user-btn', function(e) {
                e.preventDefault();
                if (confirm('آیا از حذف این کاربر اطمینان دارید؟ این عمل غیرقابل بازگشت است.')) {
                    var userId = $(this).data('user-id');
                    var button = $(this);
                    button.text('در حال حذف...');

                    $.ajax({
                        url: ajax_url,
                        type: 'POST',
                        data: {
                            action: 'maneli_delete_user_ajax',
                            user_id: userId,
                            _ajax_nonce: delete_nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                button.closest('tr').fadeOut(300, function() { $(this).remove(); });
                            } else {
                                alert('خطا در حذف کاربر: ' + response.data.message);
                                button.text('حذف');
                            }
                        },
                        error: function() {
                            alert('خطای ارتباط با سرور.');
                            button.text('حذف');
                        }
                    });
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private function render_user_add_form() {
        $back_link = remove_query_arg('add_user', $_SERVER['REQUEST_URI']);
        ob_start();
        ?>
         <div class="maneli-inquiry-wrapper">
            <h3>افزودن کاربر جدید</h3>
            <p>کاربر جدید با نقش پیش‌فرض «مشتری» ساخته می‌شود. نام کاربری و ایمیل به صورت خودکار بر اساس شماره موبایل ایجاد می‌گردند.</p>
            <form id="admin-add-user-form" class="maneli-inquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="maneli_admin_create_user">
                <?php wp_nonce_field('maneli_admin_create_user_nonce'); ?>
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url($back_link); ?>">
                
                <div class="form-grid">
                     <div class="form-row">
                        <div class="form-group"><label>شماره موبایل (الزامی):</label><input type="text" name="mobile_number" required></div>
                        <div class="form-group"><label>رمز عبور (الزامی):</label><input type="password" name="password" required></div>
                    </div>
                    <p class="form-section-title">اطلاعات تکمیلی</p>
                     <div class="form-row">
                        <div class="form-group"><label>نام:</label><input type="text" name="first_name"></div>
                        <div class="form-group"><label>نام خانوادگی:</label><input type="text" name="last_name"></div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="loan-action-btn">ایجاد کاربر</button>
                    <a href="<?php echo esc_url($back_link); ?>" style="margin-right: 15px;">انصراف</a>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_user_edit_form($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>کاربر مورد نظر یافت نشد.</p></div>';
        }
        
        $back_link = remove_query_arg('edit_user', $_SERVER['REQUEST_URI']);

        ob_start();
        ?>
        <div class="maneli-inquiry-wrapper">
            <h3>ویرایش کاربر: <?php echo esc_html($user->display_name); ?></h3>
            <form id="admin-edit-user-form" class="maneli-inquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="maneli_admin_update_user">
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
                <?php wp_nonce_field('maneli_admin_update_user', 'maneli_update_user_nonce'); ?>
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url($back_link); ?>">

                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group"><label>نام:</label><input type="text" name="first_name" value="<?php echo esc_attr($user->first_name); ?>"></div>
                        <div class="form-group"><label>نام خانوادگی:</label><input type="text" name="last_name" value="<?php echo esc_attr($user->last_name); ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>ایمیل:</label><input type="email" name="email" value="<?php echo esc_attr($user->user_email); ?>"></div>
                        <div class="form-group"><label>تلفن همراه:</label><input type="text" name="mobile_number" value="<?php echo esc_attr(get_user_meta($user->ID, 'mobile_number', true)); ?>"></div>
                    </div>
                     <div class="form-row">
                        <div class="form-group"><label>نام پدر:</label><input type="text" name="father_name" value="<?php echo esc_attr(get_user_meta($user->ID, 'father_name', true)); ?>"></div>
                        <div class="form-group"><label>تاریخ تولد:</label><input type="text" name="birth_date" class="maneli-date-picker" value="<?php echo esc_attr(get_user_meta($user->ID, 'birth_date', true)); ?>" placeholder="مثال: ۱۳۶۵/۰۴/۱۵" autocomplete="off"></div>
                    </div>
                     <div class="form-row">
                        <div class="form-group"><label>کد ملی:</label><input type="text" name="national_code" value="<?php echo esc_attr(get_user_meta($user->ID, 'national_code', true)); ?>" placeholder="کد ملی ۱۰ رقمی"></div>
                        <div class="form-group">
                            <label>نقش کاربری:</label>
                            <select name="user_role" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; background-color: #f9f9f9;">
                                <option value="customer" <?php selected(in_array('customer', $user->roles)); ?>>مشتری</option>
                                <option value="maneli_expert" <?php selected(in_array('maneli_expert', $user->roles)); ?>>کارشناس مانلی</option>
                                <option value="maneli_admin" <?php selected(in_array('maneli_admin', $user->roles)); ?>>مدیریت مانلی</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="loan-action-btn">ذخیره تغییرات</button>
                    <a href="<?php echo esc_url($back_link); ?>" style="margin-right: 15px;">انصراف</a>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handle_delete_user_ajax() {
        check_ajax_referer('maneli_delete_user_nonce');
    
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم برای این کار را ندارید.']);
        }
    
        $user_id_to_delete = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id_to_delete) {
            wp_send_json_error(['message' => 'شناسه کاربر مشخص نشده است.']);
        }
        
        if ($user_id_to_delete === get_current_user_id()) {
            wp_send_json_error(['message' => 'شما نمی‌توانید حساب کاربری خود را حذف کنید.']);
        }
    
        require_once(ABSPATH.'wp-admin/includes/user.php');
        if (wp_delete_user($user_id_to_delete)) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => 'خطایی در هنگام حذف کاربر رخ داد.']);
        }
    }
}