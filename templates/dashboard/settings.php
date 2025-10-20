<!-- Start::row -->
<?php
$settings_page_handler = new Maneli_Settings_Page();
$all_settings = $settings_page_handler->get_all_settings_public();
$options = get_option('maneli_inquiry_all_options', []);
?>

<div class="row">
    <div class="col-xl-12">
        <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="la la-check-circle me-2"></i>
                تنظیمات با موفقیت ذخیره شد.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">تنظیمات سیستم مانلی خودرو</div>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="settings-form">
                    <input type="hidden" name="action" value="maneli_save_frontend_settings">
                    <?php wp_nonce_field('maneli_save_frontend_settings_nonce'); ?>
                    <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                    
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                        <?php 
                        $first = true;
                        foreach ($all_settings as $tab_key => $tab_data) : 
                        ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo $first ? 'active' : ''; ?>" 
                                        id="<?php echo esc_attr($tab_key); ?>-tab" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#<?php echo esc_attr($tab_key); ?>" 
                                        type="button" 
                                        role="tab">
                                    <i class="<?php echo esc_attr($tab_data['icon'] ?? 'la la-cog'); ?> me-2"></i>
                                    <?php echo esc_html($tab_data['title']); ?>
                                </button>
                            </li>
                        <?php 
                        $first = false;
                        endforeach; 
                        ?>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content" id="settingsTabsContent">
                        <?php 
                        $first_tab = true;
                        foreach ($all_settings as $tab_key => $tab_data) : 
                        ?>
                            <div class="tab-pane fade <?php echo $first_tab ? 'show active' : ''; ?>" 
                                 id="<?php echo esc_attr($tab_key); ?>" 
                                 role="tabpanel">
                                
                                <?php if (!empty($tab_data['sections'])) : ?>
                                    <?php foreach ($tab_data['sections'] as $section_key => $section) : ?>
                                        <div class="card border mb-4">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0"><?php echo esc_html($section['title']); ?></h5>
                                                <?php if (!empty($section['desc'])) : ?>
                                                    <p class="text-muted mb-0 mt-2 fs-13"><?php echo wp_kses_post($section['desc']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <?php if (!empty($section['fields'])) : ?>
                                                        <?php foreach ($section['fields'] as $field) : ?>
                                                            <div class="col-md-<?php echo ($field['type'] === 'textarea') ? '12' : '6'; ?>">
                                                                <?php 
                                                                // Render field using helper
                                                                $settings_page_handler->render_field_html($field, $options);
                                                                ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php 
                        $first_tab = false;
                        endforeach; 
                        ?>
                    </div>

                    <div class="text-end pt-3 border-top">
                        <button type="submit" class="btn btn-primary btn-wave">
                            <i class="la la-save me-2"></i>
                            ذخیره تمام تنظیمات
                        </button>
                        <button type="reset" class="btn btn-light btn-wave ms-2">
                            <i class="la la-sync me-2"></i>
                            بازنشانی
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->


