<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_User_Management_Shortcodes {

    public function __construct() {
        add_shortcode('maneli_user_list', [$this, 'render_user_list_shortcode']);
        add_action('wp_ajax_maneli_delete_user_ajax', [$this, 'handle_delete_user_ajax']);
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

        // Display statistics widgets at the top
        echo Maneli_Admin_Dashboard_Widgets::render_statistics_widgets();

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
    
        $all_users = get_users(['orderby' => 'display_name']);
        $current_url = remove_query_arg(['edit_user', 'user-updated', 'add_user', 'user-created', 'user-deleted', 'error'], $_SERVER['REQUEST_URI']);
    
        ?>
        <div class="maneli-inquiry-wrapper">
             <div class="user-list-header">
                <h3>لیست کامل کاربران</h3>
                <a href="<?php echo esc_url(add_query_arg('add_user', 'true', $current_url)); ?>" class="button button-primary">افزودن کاربر جدید</a>
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
                <tbody>
                    <?php foreach ($all_users as $user): 
                        if ($user->ID === get_current_user_id()) continue;
                        $role_names = array_map(
                            function($role) {
                                global $wp_roles;
                                if ($role === 'customer') return 'مشتری';
                                return $wp_roles->roles[$role]['name'] ?? $role;
                            },
                            $user->roles
                        );
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
                </tbody>
            </table>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.delete-user-btn').on('click', function(e) {
                e.preventDefault();
                if (confirm('آیا از حذف این کاربر اطمینان دارید؟ این عمل غیرقابل بازگشت است.')) {
                    var userId = $(this).data('user-id');
                    $.ajax({
                        url: maneli_user_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'maneli_delete_user_ajax',
                            user_id: userId,
                            _ajax_nonce: maneli_user_ajax.delete_nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                window.location.href = "<?php echo esc_url(add_query_arg('user-deleted', 'true', $current_url)); ?>";
                            } else {
                                alert('خطا در حذف کاربر: ' + response.data.message);
                            }
                        }
                    });
                }
            });
        });
        </script>
        <style>
            .user-list-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            td[data-title="عملیات"] .button { 
                box-sizing: border-box;
                display: inline-block;
                text-align: center;
                padding: 5px 10px !important;
                font-size: 13px !important;
                line-height: 1.5 !important;
                min-width: 60px;
                height: auto !important;
            }
            .button.delete-user-btn { 
                background-color: #dc3545 !important; 
                color: white !important; 
                border-color: #dc3545 !important; 
                margin-right: 5px; 
            }
        </style>
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