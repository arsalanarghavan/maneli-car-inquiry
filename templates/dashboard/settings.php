<!-- Start::row -->
<?php
// Permission check - Only Admin can access
if (!current_user_can('manage_maneli_inquiries')) {
    echo '<div class="alert alert-danger">شما دسترسی به این صفحه را ندارید.</div>';
    return;
}

$settings_page_handler = new Maneli_Settings_Page();
$all_settings = $settings_page_handler->get_all_settings_public();
$options = get_option('maneli_inquiry_all_options', []);
?>

<div class="row">
    <div class="col-xl-12">
        <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') : ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="la la-check-circle fs-20 me-2"></i>
                <div class="flex-grow-1">
                    <strong>موفقیت!</strong> تنظیمات با موفقیت ذخیره شد.
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title d-flex align-items-center">
                    <i class="la la-cogs me-2 fs-20"></i>
                    <span>تنظیمات سیستم مانلی خودرو</span>
                </div>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="settings-form">
                    <input type="hidden" name="action" value="maneli_save_frontend_settings">
                    <?php wp_nonce_field('maneli_save_frontend_settings_nonce'); ?>
                    <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                    
                    <!-- Tabs Navigation - Modern Style -->
                    <ul class="nav nav-tabs nav-tabs-header mb-0 border-bottom" id="settingsTabs" role="tablist">
                        <?php 
                        $first = true;
                        $tab_icons = [
                            'theme' => 'la-palette',
                            'general' => 'la-cog',
                            'payment' => 'la-credit-card',
                            'sms' => 'la-sms',
                            'cash_inquiry' => 'la-dollar-sign',
                            'api' => 'la-plug',
                            'loan' => 'la-calculator',
                            'expert' => 'la-user-tie',
                            'rejection' => 'la-times-circle'
                        ];
                        foreach ($all_settings as $tab_key => $tab_data) : 
                            $icon = $tab_icons[$tab_key] ?? ($tab_data['icon'] ?? 'la-cog');
                        ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo $first ? 'active' : ''; ?>" 
                                        id="<?php echo esc_attr($tab_key); ?>-tab" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#<?php echo esc_attr($tab_key); ?>" 
                                        type="button" 
                                        role="tab">
                                    <i class="la <?php echo esc_attr($icon); ?> me-2"></i>
                                    <?php echo esc_html($tab_data['title']); ?>
                                </button>
                            </li>
                        <?php 
                        $first = false;
                        endforeach; 
                        ?>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content p-4 bg-white" id="settingsTabsContent" style="min-height: 400px;">
                        <?php 
                        $first_tab = true;
                        foreach ($all_settings as $tab_key => $tab_data) : 
                        ?>
                            <div class="tab-pane fade <?php echo $first_tab ? 'show active' : ''; ?>" 
                                 id="<?php echo esc_attr($tab_key); ?>" 
                                 role="tabpanel">
                                
                                <?php if (!empty($tab_data['sections'])) : ?>
                                    <?php foreach ($tab_data['sections'] as $section_key => $section) : ?>
                                        <div class="card border mb-4 shadow-sm">
                                            <div class="card-header bg-light border-bottom">
                                                <div class="d-flex align-items-center">
                                                    <i class="la la-folder-open text-primary me-2 fs-18"></i>
                                                    <div class="flex-grow-1">
                                                        <h5 class="card-title mb-0 fw-semibold"><?php echo esc_html($section['title']); ?></h5>
                                                        <?php if (!empty($section['desc'])) : ?>
                                                            <p class="text-muted mb-0 mt-1 fs-12"><?php echo wp_kses_post($section['desc']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body bg-white">
                                                <div class="row g-4">
                                                    <?php if (!empty($section['fields'])) : ?>
                                                        <?php foreach ($section['fields'] as $field) : ?>
                                                            <div class="col-lg-<?php echo ($field['type'] === 'textarea') ? '12' : '6'; ?> col-md-<?php echo ($field['type'] === 'textarea') ? '12' : '12'; ?>">
                                                                <div class="form-group-modern">
                                                                    <?php 
                                                                    // Render field using helper
                                                                    $settings_page_handler->render_field_html($field, $options);
                                                                    ?>
                                                                </div>
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

                    <!-- Action Buttons - Sticky Footer -->
                    <div class="card-footer bg-light border-top sticky-bottom">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="text-muted">
                                <i class="la la-info-circle me-1"></i>
                                <small>تغییرات خود را ذخیره کنید تا اعمال شوند.</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="reset" class="btn btn-light btn-wave">
                                    <i class="la la-undo me-1"></i>
                                    بازنشانی
                                </button>
                                <button type="submit" class="btn btn-primary btn-wave px-4">
                                    <i class="la la-save me-1"></i>
                                    ذخیره تمام تنظیمات
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->

<style>
/* ═══════════════════════════════════════════════════════════
   Modern Settings Page Styles
   ═══════════════════════════════════════════════════════════ */

/* Tab Navigation */
.nav-tabs-header {
    background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
    padding: 10px 15px 0;
    border-radius: 8px 8px 0 0;
}

.nav-tabs-header .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    color: #6c757d;
    padding: 12px 20px;
    margin: 0 5px;
    transition: all 0.3s ease;
    background: transparent;
    border-radius: 8px 8px 0 0;
    font-weight: 500;
}

.nav-tabs-header .nav-link:hover {
    color: var(--primary-color, #007cba);
    background: rgba(var(--primary-rgb, 0, 124, 186), 0.05);
    border-bottom-color: rgba(var(--primary-rgb, 0, 124, 186), 0.3);
}

.nav-tabs-header .nav-link.active {
    color: var(--primary-color, #007cba);
    background: white;
    border-bottom-color: var(--primary-color, #007cba);
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
}

.nav-tabs-header .nav-link i {
    transition: transform 0.3s ease;
}

.nav-tabs-header .nav-link:hover i,
.nav-tabs-header .nav-link.active i {
    transform: scale(1.1);
}

/* Tab Content */
.tab-content {
    border-radius: 0 0 8px 8px;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Section Cards */
.card.border.shadow-sm {
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.card.border.shadow-sm:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    transform: translateY(-2px);
}

.card-header.bg-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
}

/* Form Fields Modern Style */
.form-group-modern {
    position: relative;
}

.form-group-modern label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.form-group-modern label i {
    color: var(--primary-color, #007cba);
    margin-left: 5px;
}

.form-group-modern input[type="text"],
.form-group-modern input[type="number"],
.form-group-modern input[type="email"],
.form-group-modern input[type="url"],
.form-group-modern textarea,
.form-group-modern select {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: white;
}

.form-group-modern input:focus,
.form-group-modern textarea:focus,
.form-group-modern select:focus {
    outline: none;
    border-color: var(--primary-color, #007cba);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb, 0, 124, 186), 0.1);
    background: #fff;
}

.form-group-modern textarea {
    min-height: 100px;
    resize: vertical;
}

.form-group-modern .form-text {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: #6c757d;
    line-height: 1.4;
}

.form-group-modern .form-text i {
    color: #007cba;
    margin-left: 3px;
}

/* Checkbox/Radio Styling */
.form-group-modern input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: var(--primary-color, #007cba);
}

.form-group-modern label.checkbox-label {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    margin-bottom: 0;
}

/* Number Input Arrows */
.form-group-modern input[type="number"] {
    -moz-appearance: textfield;
}

.form-group-modern input[type="number"]::-webkit-outer-spin-button,
.form-group-modern input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Sticky Footer */
.sticky-bottom {
    position: sticky;
    bottom: 0;
    z-index: 100;
    margin: 0 -1.5rem -1.5rem;
    padding: 15px 25px;
    background: linear-gradient(to top, #ffffff 0%, #f8f9fa 100%);
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
}

/* Submit Button Enhancement */
.btn-wave {
    position: relative;
    overflow: hidden;
}

.btn-wave::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-wave:hover::before {
    width: 300px;
    height: 300px;
}

/* Responsive Tabs */
@media (max-width: 768px) {
    .nav-tabs-header .nav-link {
        padding: 10px 12px;
        font-size: 13px;
        margin: 0 2px;
    }
    
    .nav-tabs-header .nav-link i {
        display: none;
    }
    
    .sticky-bottom {
        margin: 0;
        padding: 12px 15px;
    }
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 64px;
    color: #dee2e6;
    margin-bottom: 20px;
}

/* Field Groups */
.form-group-modern + .form-group-modern {
    margin-top: 0;
}

/* Input Group Enhancement */
.input-group-modern {
    position: relative;
}

.input-group-modern .input-group-text {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #e9ecef;
    color: #495057;
}

/* Card Section Animation */
.card.border.mb-4 {
    animation: slideInUp 0.4s ease;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Tooltips */
[data-bs-toggle="tooltip"] {
    cursor: help;
}

/* Color Inputs */
.form-group-modern input[type="color"] {
    height: 45px;
    padding: 5px;
    cursor: pointer;
}

/* File Upload */
.form-group-modern input[type="file"] {
    padding: 8px;
    cursor: pointer;
}

.form-group-modern input[type="file"]::-webkit-file-upload-button {
    background: var(--primary-color, #007cba);
    color: white;
    border: none;
    padding: 6px 15px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.form-group-modern input[type="file"]::-webkit-file-upload-button:hover {
    background: var(--primary-hover, #006ba1);
}

/* Section Icons */
.card-header i.la-folder-open {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

/* Help Text Enhancement */
.form-text.text-muted {
    background: #f8f9fa;
    padding: 8px 12px;
    border-radius: 4px;
    border-right: 3px solid var(--primary-color, #007cba);
    margin-top: 8px;
}

/* Select Dropdown */
.form-group-modern select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: left 0.75rem center;
    background-size: 16px 12px;
    padding-left: 2.5rem;
}

/* Success/Error States */
.form-group-modern.has-success input,
.form-group-modern.has-success textarea,
.form-group-modern.has-success select {
    border-color: #28a745;
}

.form-group-modern.has-error input,
.form-group-modern.has-error textarea,
.form-group-modern.has-error select {
    border-color: #dc3545;
}

/* Loading State */
.form-control.loading {
    background-image: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>
