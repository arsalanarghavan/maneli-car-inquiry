<!-- Start::row -->
<?php
/**
 * Settings Page
 * Only accessible by Administrators
 * Redesigned to match profile-settings.html style
 */

// Helper function to convert numbers to Persian
if (!function_exists('persian_numbers')) {
    function persian_numbers($str) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, $str);
    }
}

// Permission check - Only Admin can access
if (!current_user_can('manage_maneli_inquiries')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

$settings_page_handler = new Maneli_Settings_Page();
$all_settings = $settings_page_handler->get_all_settings_public();
$options = get_option('maneli_inquiry_all_options', []);

// Tab mapping to Persian names
$tab_persian_names = [
    'finance' => esc_html__('Finance & Calculator', 'maneli-car-inquiry'),
    'gateways' => esc_html__('Payment Gateways', 'maneli-car-inquiry'),
    'authentication' => esc_html__('Authentication', 'maneli-car-inquiry'),
    'sms' => esc_html__('SMS', 'maneli-car-inquiry'),
    'telegram' => esc_html__('Telegram', 'maneli-car-inquiry'),
    'email' => esc_html__('Email', 'maneli-car-inquiry'),
    'cash_inquiry' => esc_html__('Cash Inquiry', 'maneli-car-inquiry'),
    'installment' => esc_html__('Installment Inquiry', 'maneli-car-inquiry'),
    'experts' => esc_html__('Experts', 'maneli-car-inquiry'),
    'finotex' => esc_html__('Finotex', 'maneli-car-inquiry'),
    'meetings' => esc_html__('Meetings & Calendar', 'maneli-car-inquiry'),
    'documents' => esc_html__('Documents', 'maneli-car-inquiry'),
    'availability' => esc_html__('Availability', 'maneli-car-inquiry'),
    'notifications' => esc_html__('Notifications', 'maneli-car-inquiry')
];
?>
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Pages', 'maneli-car-inquiry'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('System Settings', 'maneli-car-inquiry'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Maneli Car System Settings', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- Page Header Close -->

        <!-- Start::row -->
        <div class="row gap-3 justify-content-center">
            <div class="col-xl-9">
                <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') : ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
                        <i class="la la-check-circle fs-20 me-2"></i>
                        <div class="flex-grow-1">
                            <strong><?php esc_html_e('Success!', 'maneli-car-inquiry'); ?></strong> <?php esc_html_e('Settings saved successfully.', 'maneli-car-inquiry'); ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card custom-card">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="settings-form">
                        <input type="hidden" name="action" value="maneli_save_frontend_settings">
                        <?php wp_nonce_field('maneli_save_frontend_settings_nonce'); ?>
                        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                        
                        <!-- Tabs Navigation -->
                        <ul class="nav nav-tabs tab-style-8 scaleX rounded m-3 profile-settings-tab gap-2" id="settingsTabs" role="tablist">
                            <?php 
                            $first = true;
                            $tab_icons = [
                                'finance' => 'la-calculator',
                                'gateways' => 'la-credit-card',
                                'authentication' => 'la-shield-alt',
                                'sms' => 'la-sms',
                                'telegram' => 'la-telegram',
                                'email' => 'la-envelope',
                                'cash_inquiry' => 'la-dollar-sign',
                                'installment' => 'la-car',
                                'experts' => 'la-user-tie',
                                'finotex' => 'la-university',
                                'meetings' => 'la-calendar',
                                'documents' => 'la-file-alt',
                                'availability' => 'la-box-open',
                                'notifications' => 'la-bell'
                            ];
                            foreach ($all_settings as $tab_key => $tab_data) : 
                                $icon = $tab_icons[$tab_key] ?? 'la-cog';
                                $persian_title = isset($tab_persian_names[$tab_key]) ? $tab_persian_names[$tab_key] : $tab_data['title'];
                            ?>
                                <li class="nav-item me-1" role="presentation">
                                    <button class="nav-link px-4 bg-primary-transparent <?php echo $first ? 'active' : ''; ?>" 
                                            id="<?php echo esc_attr($tab_key); ?>-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#<?php echo esc_attr($tab_key); ?>-pane" 
                                            type="button" 
                                            role="tab" 
                                            aria-controls="<?php echo esc_attr($tab_key); ?>-pane" 
                                            aria-selected="<?php echo $first ? 'true' : 'false'; ?>">
                                        <i class="la <?php echo esc_attr($icon); ?> me-2"></i>
                                        <?php echo esc_html($persian_title); ?>
                                    </button>
                                </li>
                            <?php 
                            $first = false;
                            endforeach; 
                            ?>
                        </ul>

                        <!-- Tabs Content -->
                        <div class="p-3 border-bottom border-top border-block-end-dashed tab-content">
                            <?php 
                            $first_tab = true;
                            foreach ($all_settings as $tab_key => $tab_data) : 
                                $persian_title = isset($tab_persian_names[$tab_key]) ? $tab_persian_names[$tab_key] : $tab_data['title'];
                            ?>
                                <div class="tab-pane <?php echo $first_tab ? 'show active' : ''; ?> overflow-hidden p-0 border-0" 
                                     id="<?php echo esc_attr($tab_key); ?>-pane" 
                                     role="tabpanel" 
                                     aria-labelledby="<?php echo esc_attr($tab_key); ?>-tab" 
                                     tabindex="0">
                                    <div class="p-4">
                                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                                            <div class="fw-semibold d-block fs-15"><?php echo esc_html($persian_title); ?>:</div>
                                            <button type="reset" class="btn btn-primary btn-sm">
                                                <i class="la la-undo me-1"></i>بازگردانی تغییرات
                                            </button>
                                        </div>
                                        
                                        <?php if (!empty($tab_data['sections'])) : ?>
                                            <?php foreach ($tab_data['sections'] as $section_key => $section) : ?>
                                                <?php if (!empty($section['fields']) || !empty($section['desc'])): ?>
                                                    <div class="mb-4">
                                                        <?php if (!empty($section['title'])): ?>
                                                            <h6 class="fw-semibold mb-3 pb-2 border-bottom">
                                                                <i class="la la-folder-open me-2 text-primary"></i>
                                                                <?php echo esc_html($section['title']); ?>
                                                            </h6>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($section['desc'])) : ?>
                                                            <p class="text-muted fs-12 mb-3"><?php echo wp_kses_post($section['desc']); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($section['fields'])) : ?>
                                                            <div class="row gy-3">
                                                                <?php foreach ($section['fields'] as $field) : ?>
                                                                    <?php if ($field['type'] === 'switch'): ?>
                                                                        <!-- Switch field with toggle style from profile-settings -->
                                                                        <div class="col-xl-12">
                                                                            <div class="d-flex align-items-top justify-content-between mt-3">
                                                                                <div class="mail-notification-settings">
                                                                                    <p class="fs-14 mb-1 fw-medium"><?php echo esc_html($field['label']); ?></p>
                                                                                    <?php if (!empty($field['desc'])): ?>
                                                                                        <p class="fs-12 mb-0 text-muted"><?php echo wp_kses_post($field['desc']); ?></p>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                <?php 
                                                                                $value = $options[$field['name']] ?? ($field['default'] ?? '0');
                                                                                $field_name = "maneli_inquiry_all_options[" . $field['name'] . "]";
                                                                                $toggle_class = ($value == '1') ? 'toggle on toggle-success' : 'toggle toggle-success';
                                                                                ?>
                                                                                <div class="toggle-wrapper">
                                                                                    <input type="hidden" name="<?php echo esc_attr($field_name); ?>" value="0">
                                                                                    <input type="checkbox" 
                                                                                           name="<?php echo esc_attr($field_name); ?>" 
                                                                                           id="<?php echo esc_attr($settings_page_handler->get_options_name() . '_' . $field['name']); ?>" 
                                                                                           value="1" 
                                                                                           class="toggle-checkbox d-none" 
                                                                                           <?php checked('1', $value); ?>>
                                                                                    <label for="<?php echo esc_attr($settings_page_handler->get_options_name() . '_' . $field['name']); ?>" 
                                                                                           class="<?php echo esc_attr($toggle_class); ?> mb-0 float-sm-end" 
                                                                                           style="cursor: pointer;">
                                                                                        <span></span>
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <!-- Regular field - exactly like profile-settings.html -->
                                                                        <div class="col-xl-<?php echo ($field['type'] === 'textarea') ? '12' : '6'; ?>">
                                                                            <label for="<?php echo esc_attr($settings_page_handler->get_options_name() . '_' . $field['name']); ?>" class="form-label"><?php echo esc_html($field['label']); ?> :</label>
                                                                            <?php 
                                                                            // Render field using handler method (handles desc internally)
                                                                            $settings_page_handler->render_field_html($field);
                                                                            ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php elseif ($tab_key === 'experts' && $section_key === 'maneli_experts_list_section'): ?>
                                                            <!-- Experts List -->
                                                            <?php 
                                                            $experts_list = get_users([
                                                                'role' => 'maneli_expert',
                                                                'orderby' => 'display_name',
                                                                'order' => 'ASC'
                                                            ]);
                                                            ?>
                                                            <?php if (!empty($experts_list)): ?>
                                                                <div class="table-responsive">
                                                                    <table class="table table-hover table-bordered">
                                                                        <thead class="table-light">
                                                                            <tr>
                                                                                <th><?php esc_html_e('Expert Name', 'maneli-car-inquiry'); ?></th>
                                                                                <th><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?></th>
                                                                                <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                                                                <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php foreach ($experts_list as $expert): 
                                                                                $mobile_number = get_user_meta($expert->ID, 'mobile_number', true);
                                                                                $is_active = get_user_meta($expert->ID, 'expert_active', true) !== 'no';
                                                                            ?>
                                                                                <tr>
                                                                                    <td>
                                                                                        <span class="fw-medium"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($expert->display_name)) : esc_html($expert->display_name); ?></span>
                                                                                    </td>
                                                                                    <td>
                                                                                        <?php if (!empty($mobile_number)): ?>
                                                                                            <a href="tel:<?php echo esc_attr($mobile_number); ?>" class="text-primary text-decoration-none">
                                                                                                <?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($mobile_number)) : esc_html($mobile_number); ?>
                                                                                            </a>
                                                                                        <?php else: ?>
                                                                                            <span class="text-muted">-</span>
                                                                                        <?php endif; ?>
                                                                                    </td>
                                                                                    <td>
                                                                                        <?php if ($is_active): ?>
                                                                                            <span class="badge bg-success"><?php esc_html_e('Active', 'maneli-car-inquiry'); ?></span>
                                                                                        <?php else: ?>
                                                                                            <span class="badge bg-danger"><?php esc_html_e('Inactive', 'maneli-car-inquiry'); ?></span>
                                                                                        <?php endif; ?>
                                                                                    </td>
                                                                                    <td>
                                                                                        <a href="<?php echo esc_url(home_url('/dashboard/experts?view_expert=' . $expert->ID)); ?>" class="btn btn-sm btn-primary-light" title="<?php esc_attr_e('View', 'maneli-car-inquiry'); ?>">
                                                                                            <i class="la la-eye"></i>
                                                                                        </a>
                                                                                    </td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="alert alert-info">
                                                                    <i class="la la-info-circle me-2"></i>
                                                                    <?php esc_html_e('No experts are currently registered.', 'maneli-car-inquiry'); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php 
                            $first_tab = false;
                            endforeach; 
                            ?>
                        </div>

                        <!-- Footer with Save Button -->
                        <div class="card-footer border-top-0">
                            <div class="btn-list float-end">
                                <button type="reset" class="btn btn-light btn-wave">
                                    <i class="la la-undo me-1"></i>
                                    بازنشانی تغییرات
                                </button>
                                <button type="submit" class="btn btn-primary btn-wave">
                                    <i class="la la-save me-1"></i>
                                    ذخیره تغییرات
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- End::row -->
    </div>
</div>

<style>
.tab-style-8 .nav-link.active {
    background-color: var(--primary-color) !important;
    color: white !important;
}

.tab-style-8 .nav-link {
    transition: all 0.3s ease;
}

.tab-style-8 .nav-link:hover:not(.active) {
    background-color: rgba(var(--primary-rgb), 0.1) !important;
}

/* Form Label - exactly like profile-settings.html */
.form-label {
    margin-bottom: 0.5rem;
    font-weight: 400;
    color: #495057;
}

/* Form Controls - exactly like profile-settings.html */
.form-control, textarea, select.form-control {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus, textarea:focus, select.form-control:focus {
    color: #495057;
    background-color: #fff;
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.form-control::placeholder {
    color: #6c757d;
    opacity: 1;
}

textarea.form-control {
    min-height: calc(1.5em + 0.75rem + 2px);
    resize: vertical;
}

/* Description text style */
.description {
    margin-top: 0.25rem;
    margin-bottom: 0;
    font-size: 0.75rem;
    color: #6c757d;
}

/* Toggle Switch Styles */
.toggle-wrapper .toggle-checkbox {
    display: none;
}

.toggle-wrapper .toggle-checkbox:checked + label.toggle {
    background-color: var(--success-color, #10b981);
}

.toggle-wrapper .toggle-checkbox:checked + label.toggle.on {
    background-color: var(--success-color, #10b981);
}

.toggle-wrapper label.toggle {
    cursor: pointer;
    user-select: none;
}


.border-bottom {
    border-bottom: 2px solid #e9ecef !important;
}

.card-footer {
    background-color: #f8f9fa;
    padding: 1rem 1.5rem 1.5rem 1.5rem;
    margin-bottom: 1.5rem;
}

.btn-wave {
    position: relative;
    overflow: hidden;
}

.btn-list {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle toggle switches - sync checkbox with toggle label
    $('.toggle-wrapper label.toggle').on('click', function(e) {
        e.preventDefault();
        var checkbox = $(this).prev('.toggle-checkbox');
        var isChecked = checkbox.prop('checked');
        checkbox.prop('checked', !isChecked);
        
        // Update toggle visual state
        if (!isChecked) {
            $(this).addClass('on');
        } else {
            $(this).removeClass('on');
        }
    });
    
    // Handle form reset
    $('button[type="reset"]').on('click', function(e) {
        if (!confirm(<?php echo wp_json_encode(esc_html__('Are you sure you want to revert changes?', 'maneli-car-inquiry')); ?>)) {
            e.preventDefault();
            return false;
        }
        // Reset form to original values by reloading
        location.reload();
    });
});
</script>