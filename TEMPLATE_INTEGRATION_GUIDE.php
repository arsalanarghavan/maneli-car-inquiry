<?php
/**
 * Template Integration Guide for AutoPuzzle Dynamic Branding
 * 
 * This file demonstrates how to integrate AutoPuzzle Branding Helper
 * into your templates for dynamic brand customization.
 * 
 * @package AutoPuzzle
 * @since 1.2.0
 */

/**
 * =============================================================================
 * PART 1: HELPER FUNCTIONS (These are automatically available in templates)
 * =============================================================================
 * 
 * The following functions are now available in all templates and should be
 * used instead of hardcoded brand names:
 * 
 * 1. autopuzzle_brand_name($locale = '')
 *    - Returns the brand name in the current or specified locale
 *    - Examples:
 *      autopuzzle_brand_name()           → 'اتوپازل' (if Persian) or 'AutoPuzzle' (if English)
 *      autopuzzle_brand_name('fa_IR')    → 'اتوپازل'
 *      autopuzzle_brand_name('en_US')    → 'AutoPuzzle'
 * 
 * 2. autopuzzle_logo($type = 'main')
 *    - Returns the logo URL
 *    - Types: 'main', 'light', 'dark'
 *    - Example:
 *      <img src="<?php echo esc_url(autopuzzle_logo('main')); ?>" alt="Logo">
 * 
 * 3. autopuzzle_color($type = 'primary')
 *    - Returns brand color hex code
 *    - Types: 'primary', 'secondary', 'accent'
 *    - Example:
 *      <div style="background-color: <?php echo esc_attr(autopuzzle_color('primary')); ?>">
 * 
 * 4. autopuzzle_copyright()
 *    - Returns copyright text (localized)
 *    - Example:
 *      <footer><?php echo autopuzzle_copyright(); ?></footer>
 * 
 * 5. autopuzzle_get_branding($key, $default = '')
 *    - Generic getter for any branding setting
 *    - Example:
 *      <?php echo autopuzzle_get_branding('email', 'info@autopuzzle.com'); ?>
 * 
 */

?>

<!-- ===================================================================== -->
<!-- EXAMPLE 1: Logo in Header -->
<!-- ===================================================================== -->
<header class="site-header">
    <div class="logo-section">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="logo-link">
            <img src="<?php echo esc_url(autopuzzle_logo('main')); ?>" 
                 alt="<?php echo esc_attr(autopuzzle_brand_name()); ?>" 
                 class="logo-img">
            <span class="brand-name"><?php echo esc_html(autopuzzle_brand_name()); ?></span>
        </a>
    </div>
</header>

<!-- ===================================================================== -->
<!-- EXAMPLE 2: Dynamic Styling with Brand Colors -->
<!-- ===================================================================== -->
<style>
    :root {
        --brand-primary: <?php echo esc_attr(autopuzzle_color('primary')); ?>;
        --brand-secondary: <?php echo esc_attr(autopuzzle_color('secondary')); ?>;
        --brand-accent: <?php echo esc_attr(autopuzzle_color('accent')); ?>;
    }
    
    .btn-primary {
        background-color: var(--brand-primary);
    }
    
    .btn-secondary {
        background-color: var(--brand-secondary);
    }
</style>

<!-- ===================================================================== -->
<!-- EXAMPLE 3: Page Title with Brand Name -->
<!-- ===================================================================== -->
<h1><?php printf(esc_html__('Welcome to %s', 'autopuzzle'), autopuzzle_brand_name()); ?></h1>

<!-- ===================================================================== -->
<!-- EXAMPLE 4: Footer with Copyright -->
<!-- ===================================================================== -->
<footer class="site-footer">
    <div class="footer-content">
        <p><?php echo wp_kses_post(autopuzzle_copyright()); ?></p>
    </div>
</footer>

<!-- ===================================================================== -->
<!-- EXAMPLE 5: Contact Information -->
<!-- ===================================================================== -->
<div class="contact-section">
    <p><?php 
        printf(
            esc_html__('Contact %s:', 'autopuzzle'),
            autopuzzle_brand_name()
        );
    ?></p>
    <p><?php echo esc_html(autopuzzle_get_branding('email', 'info@autopuzzle.com')); ?></p>
    <p><?php echo esc_html(autopuzzle_get_branding('phone', '+1-234-567-8900')); ?></p>
</div>

<!-- ===================================================================== -->
<!-- EXAMPLE 6: Role Names with Dynamic Branding -->
<!-- ===================================================================== -->
<?php
// Instead of hardcoding "AutoPuzzle Expert", use:
$expert_role_name = sprintf(esc_html__('%s Expert', 'autopuzzle'), autopuzzle_brand_name());
$admin_role_name = sprintf(esc_html__('%s Manager', 'autopuzzle'), autopuzzle_brand_name());
?>

<!-- ===================================================================== -->
<!-- HOW TO MIGRATE EXISTING TEMPLATES -->
<!-- ===================================================================== -->
<?php
/*

BEFORE (Hardcoded):
====================
<h1>مانلی خودرو</h1>
<img src="/images/logo.png" alt="AutoPuzzle">
<div style="color: #007bff;">Dashboard</div>
<footer>© 2025 مانلی خودرو</footer>

AFTER (Dynamic):
================
<h1><?php echo esc_html(autopuzzle_brand_name()); ?></h1>
<img src="<?php echo esc_url(autopuzzle_logo()); ?>" alt="<?php echo esc_attr(autopuzzle_brand_name()); ?>">
<div style="color: <?php echo esc_attr(autopuzzle_color('primary')); ?>;">Dashboard</div>
<footer><?php echo wp_kses_post(autopuzzle_copyright()); ?></footer>

STEPS:
======
1. Find all hardcoded brand names: 'مانلی', 'AutoPuzzle', 'MANELI'
2. Replace with: autopuzzle_brand_name() or autopuzzle_brand_name('fa_IR')
3. Find all hardcoded logos
4. Replace with: autopuzzle_logo() or autopuzzle_logo('light')/('dark')
5. Find all hardcoded colors
6. Replace with: autopuzzle_color('primary'/'secondary'/'accent')
7. Find all hardcoded copyright
8. Replace with: autopuzzle_copyright()
9. Test in WordPress with different branding settings

*/
?>

<!-- ===================================================================== -->
<!-- INTEGRATION CHECKLIST -->
<!-- ===================================================================== -->

<?php
/*

PRIORITY TEMPLATES FOR INTEGRATION (Phase 5):
==============================================

HIGH PRIORITY (Contains brand name/logo):
- templates/elementor/home-page.php              → Hero section, branding
- templates/dashboard.php                         → Dashboard title
- templates/dashboard/dashboard-home.php          → Home banner
- templates/login.php                             → Brand name in title
- templates/dashboard-base.html                   → HTML structure
- templates/dashboard/sidebar-menu.php            → Sidebar branding
- templates/partials/sms-history-modal.php        → Modal headers

MEDIUM PRIORITY (Role names, permissions):
- templates/dashboard/experts.php                 → Expert list titles
- templates/dashboard/users.php                   → User management
- templates/dashboard/settings.php                → Settings titles
- templates/shortcodes/admin-settings.php         → Admin labels

LOW PRIORITY (Can use defaults):
- templates/shortcodes/inquiry-form/               → Form labels
- templates/shortcodes/inquiry-lists/              → Table headers
- templates/dashboard/calendar.php                 → Calendar

ESTIMATED TIME: 2-3 hours for all HIGH priority templates

NEXT STEPS:
===========
1. Edit HIGH priority templates first
2. Test each template after editing
3. Verify branding changes from White Label settings
4. Document any custom integrations
5. Create migration script for client deployments

*/
?>
