<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_CPT_Handler {

    public function __construct() {
        add_action('init', [$this, 'register_inquiry_post_type']);
        add_filter('manage_inquiry_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_inquiry_posts_custom_column', [$this, 'render_custom_columns'], 10, 2);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_inquiry', [$this, 'save_status_and_send_sms'], 10, 2);
    }

    public function register_inquiry_post_type() {
        $labels = [
            'name'               => 'استعلام‌ها',
            'singular_name'      => 'استعلام',
            'menu_name'          => 'استعلامات بانکی',
            'name_admin_bar'     => 'استعلام',
            'all_items'          => 'همه استعلام‌ها',
            'add_new_item'       => 'افزودن استعلام',
            'add_new'            => 'افزودن',
            'new_item'           => 'استعلام جدید',
            'edit_item'          => 'ویرایش استعلام',
            'view_item'          => 'مشاهده استعلام',
            'search_items'       => 'جستجوی استعلام‌ها',
            'not_found'          => 'استعلامی یافت نشد',
            'not_found_in_trash' => 'استعلامی در زباله‌دان یافت نشد',
        ];
        $args = [
            'labels'             => $labels,
            'supports'           => ['title', 'editor', 'author'],
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'capability_type'    => 'post',
            'menu_icon'          => 'dashicons-clipboard',
            'show_in_admin_bar'  => false,
            'publicly_queryable' => false,
            'rewrite'            => false,
        ];
        register_post_type('inquiry', $args);
    }

    public function set_custom_columns($columns) {
        unset($columns['author'], $columns['date'], $columns['title']);
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = 'موضوع استعلام';
        $new_columns['inquiry_user'] = 'کاربر';
        $new_columns['assigned_expert'] = 'کارشناس مسئول';
        $new_columns['inquiry_status'] = 'وضعیت';
        $new_columns['inquiry_date'] = 'تاریخ ثبت';
        $new_columns['actions'] = 'عملیات';
        return $new_columns;
    }

    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'inquiry_user':
                $user_id = get_post_field('post_author', $post_id);
                $user = get_userdata($user_id);
                echo $user ? esc_html($user->display_name) : 'کاربر یافت نشد';
                break;
            case 'assigned_expert':
                $expert_name = get_post_meta($post_id, 'assigned_expert_name', true);
                echo $expert_name ? esc_html($expert_name) : '—';
                break;
            case 'inquiry_status':
                $status = get_post_meta($post_id, 'inquiry_status', true);
                echo '<span class="status-' . esc_attr($status) . '">' . esc_html($this->get_status_label($status)) . '</span>';
                break;
            case 'inquiry_date':
                echo get_the_date('Y/m/d H:i', $post_id);
                break;
            case 'actions':
                $report_url = admin_url('edit.php?post_type=inquiry&page=maneli-credit-report&inquiry_id=' . $post_id);
                printf('<a href="%s" class="button button-primary">مشاهده گزارش</a>', esc_url($report_url));
                break;
        }
    }

    public function add_meta_boxes() {
        add_meta_box('inquiry_status_box', 'مدیریت وضعیت استعلام', [$this, 'render_status_meta_box'], 'inquiry', 'side', 'high');
        add_meta_box('inquiry_personal_info_box', 'اطلاعات فردی ثبت شده', [$this, 'render_personal_info_box'], 'inquiry', 'normal', 'high');
    }

    public function render_personal_info_box($post) {
        $post_meta = get_post_meta($post->ID);
        
        echo '<h4>اطلاعات خریدار</h4>';
        $buyer_fields = ['first_name' => 'نام','last_name' => 'نام خانوادگی','national_code' => 'کد ملی','father_name' => 'نام پدر','birth_date' => 'تاریخ تولد','mobile_number' => 'شماره موبایل'];
        $field_pairs = array_chunk($buyer_fields, 2, true);
        echo '<table class="form-table"><tbody>';
        foreach ($field_pairs as $pair) {
            echo '<tr>';
            $i = 0;
            foreach($pair as $key => $label) {
                $value = $post_meta[$key][0] ?? '';
                echo '<th scope="row" style="width: 15%;"><label>' . esc_html($label) . '</label></th>';
                echo '<td style="width: 35%;">' . esc_html($value) . '</td>';
                $i++;
            }
            if ($i == 1) { echo '<th></th><td></td>'; }
            echo '</tr>';
        }
        echo '</tbody></table>';

        $issuer_type = $post_meta['issuer_type'][0] ?? 'self';
        if ($issuer_type === 'other') {
            echo '<h3 style="margin-top:20px; border-top:1px solid #ddd; padding-top:20px;">اطلاعات صادر کننده چک</h3>';
            $issuer_fields = ['issuer_first_name' => 'نام','issuer_last_name' => 'نام خانوادگی','issuer_national_code' => 'کد ملی','issuer_father_name'   => 'نام پدر','issuer_birth_date'    => 'تاریخ تولد','issuer_mobile_number' => 'شماره موبایل'];
            $issuer_pairs = array_chunk($issuer_fields, 2, true);
            echo '<table class="form-table"><tbody>';
            foreach ($issuer_pairs as $pair) {
                 echo '<tr>'; $i = 0;
                 foreach($pair as $key => $label) {
                     $value = $post_meta[$key][0] ?? '';
                     echo '<th scope="row" style="width: 15%;"><label>' . esc_html($label) . '</label></th>';
                     echo '<td style="width: 35%;">' . esc_html($value) . '</td>';
                     $i++;
                 }
                 if ($i == 1) { echo '<th></th><td></td>'; }
                 echo '</tr>';
            }
            echo '</tbody></table>';
        }
        
        echo '<hr><p><strong>خودروی درخواستی:</strong> ' . esc_html(get_the_title($post_meta['product_id'][0] ?? '')) . '</p>';
    }

    public function render_status_meta_box($post) {
        wp_nonce_field('save_inquiry_status_nonce', 'inquiry_status_nonce');
        $status = get_post_meta($post->ID, 'inquiry_status', true);
        echo '<select name="inquiry_status" id="inquiry_status" style="width: 100%;">';
        foreach ($this->get_all_statuses() as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($status, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select><p class="description">وضعیت را تغییر داده و پست را به‌روزرسانی کنید تا پیامک ارسال شود.</p>';
    }

    public function save_status_and_send_sms($post_id, $post) {
        if (!isset($_POST['inquiry_status_nonce']) || !wp_verify_nonce($_POST['inquiry_status_nonce'], 'save_inquiry_status_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if ($post->post_type != 'inquiry') return;

        if (isset($_POST['inquiry_status'])) {
            $new_status = sanitize_text_field($_POST['inquiry_status']);
            $old_status = get_post_meta($post_id, 'inquiry_status', true);
            
            if ($new_status !== $old_status) {
                update_post_meta($post_id, 'inquiry_status', $new_status);
                
                $options = get_option('maneli_inquiry_all_options', []);
                $user_id = $post->post_author;
                $user_info = get_userdata($user_id);
                $user_name = $user_info->display_name ?? '';
                $mobile_number = get_user_meta($user_id, 'mobile_number', true);
                $car_name = get_the_title(get_post_meta($post_id, 'product_id', true)) ?? '';
                $sms_handler = new Maneli_SMS_Handler();
                
                $pattern_id = null;
                $params = [];

                if ($new_status === 'user_confirmed') {
                    // This is triggered if admin changes status to 'approved'
                    // SMS to expert and customer is handled in form handler.
                    // This section is for changes from the edit screen meta box.
                    $pattern_id = $options['sms_pattern_approved'] ?? 0;
                    $params = [$user_name, $car_name];
                } elseif ($new_status === 'rejected') {
                    $pattern_id = $options['sms_pattern_rejected'] ?? 0;
                    // Note: rejection reason cannot be set from the meta box, only the report page.
                    $params = [$user_name, $car_name, 'عدم تایید کارشناس'];
                } elseif ($new_status === 'more_docs') {
                    $pattern_id = $options['sms_pattern_more_docs'] ?? 0;
                    $params = [$user_name, $car_name];
                }

                if (!empty($pattern_id) && !empty($mobile_number) && $sms_handler) {
                    $sms_handler->send_pattern($pattern_id, $mobile_number, $params);
                }
            }
        }
    }

    public function get_all_statuses() {
        return [
            'pending'        => 'در حال بررسی',
            'user_confirmed' => 'تایید و ارجاع شده',
            'more_docs'      => 'نیازمend مدارک',
            'rejected'       => 'رد شده',
            'failed'         => 'استعلام ناموفق',
        ];
    }
    
    public function get_status_label($status_key) {
        $statuses = $this->get_all_statuses();
        return $statuses[$status_key] ?? 'نامشخص';
    }
}