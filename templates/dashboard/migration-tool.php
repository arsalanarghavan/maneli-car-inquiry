<!-- Start::row -->
<?php
/**
 * Status Migration Tool Page
 * Only accessible by Administrators
 */

// Permission check - Only Admin can access
if (!current_user_can('manage_maneli_inquiries')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/admin/class-status-migration.php';

// Check if migration should be run
$migration_run = false;
$migration_results = null;
if (isset($_POST['run_migration']) && check_admin_referer('maneli_run_migration', 'migration_nonce')) {
    $migration_run = true;
    $migration_results = Maneli_Status_Migration::migrate_all_statuses();
}

// Get current statistics
$stats = Maneli_Status_Migration::get_migration_stats();
?>

<div class="container-fluid">
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto"><?php esc_html_e('Status Migration Tool', 'maneli-car-inquiry'); ?></h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header bg-warning-transparent">
                    <div class="card-title">
                        <i class="la la-exchange-alt me-2"></i>
                        <?php esc_html_e('Convert Old Statuses to New Structure', 'maneli-car-inquiry'); ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="la la-info-circle me-2"></i>
                        <?php esc_html_e('This tool will convert all old inquiry statuses to the new status structure. This includes:', 'maneli-car-inquiry'); ?>
                        <ul class="mt-2 mb-0">
                            <li><?php esc_html_e('Converting "cancelled" to "rejected"', 'maneli-car-inquiry'); ?></li>
                            <li><?php esc_html_e('Converting "pending" (cash) to "new"', 'maneli-car-inquiry'); ?></li>
                            <li><?php esc_html_e('Setting empty statuses to "new"', 'maneli-car-inquiry'); ?></li>
                            <li><?php esc_html_e('Converting any unknown statuses to "new"', 'maneli-car-inquiry'); ?></li>
                        </ul>
                    </div>

                    <!-- Statistics Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header bg-primary-transparent">
                                    <h5 class="card-title mb-0"><?php esc_html_e('Installment Inquiries', 'maneli-car-inquiry'); ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-1"><strong><?php esc_html_e('Total:', 'maneli-car-inquiry'); ?></strong> <?php echo number_format_i18n($stats['installment']['total']); ?></p>
                                    <p class="mb-1 text-danger"><strong><?php esc_html_e('Invalid Statuses:', 'maneli-car-inquiry'); ?></strong> <?php echo number_format_i18n($stats['installment']['invalid']); ?></p>
                                    <p class="mb-1 text-warning"><strong><?php esc_html_e('Empty Statuses:', 'maneli-car-inquiry'); ?></strong> <?php echo number_format_i18n($stats['installment']['empty']); ?></p>
                                    <?php if ($stats['installment']['cancelled'] > 0): ?>
                                        <p class="mb-0 text-danger"><strong><?php esc_html_e('Cancelled:', 'maneli-car-inquiry'); ?></strong> <?php echo number_format_i18n($stats['installment']['cancelled']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($stats['installment']['new_with_expert'] > 0): ?>
                                        <p class="mb-0 text-warning"><strong><?php esc_html_e('New with Assigned Expert (should be Referred):', 'maneli-car-inquiry'); ?></strong> <?php echo number_format_i18n($stats['installment']['new_with_expert']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($stats['installment']['other_invalid'])): ?>
                                        <div class="mt-2">
                                            <strong><?php esc_html_e('Other Invalid Statuses:', 'maneli-car-inquiry'); ?></strong>
                                            <ul class="mb-0">
                                                <?php foreach ($stats['installment']['other_invalid'] as $status => $count): ?>
                                                    <li><?php echo esc_html($status); ?>: <?php echo number_format_i18n($count); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header bg-success-transparent">
                                    <h5 class="card-title mb-0"><?php esc_html_e('Cash Inquiries', 'maneli-car-inquiry'); ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-1"><strong><?php esc_html_e('Total:', 'maneli-car-inquiry'); ?></strong> <?php echo number_format_i18n($stats['cash']['total']); ?></p>
                                    <p class="mb-1 text-danger"><strong><?php esc_html_e('Invalid Statuses:', 'maneli-car-inquiry'); ?></strong> <?php echo number_format_i18n($stats['cash']['invalid']); ?></p>
                                    <p class="mb-1 text-warning"><strong><?php esc_html_e('Empty Statuses:', 'maneli-car-inquiry'); ?></strong> <?php echo number_format_i18n($stats['cash']['empty']); ?></p>
                                    <?php if ($stats['cash']['pending'] > 0): ?>
                                        <p class="mb-0 text-warning"><strong><?php esc_html_e('Pending:', 'maneli-car-inquiry'); ?></strong> <?php echo number_format_i18n($stats['cash']['pending']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($stats['cash']['new_with_expert'] > 0): ?>
                                        <p class="mb-0 text-warning"><strong><?php esc_html_e('New with Assigned Expert (should be Referred):', 'maneli-car-inquiry'); ?></strong> <?php echo number_format_i18n($stats['cash']['new_with_expert']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($stats['cash']['other_invalid'])): ?>
                                        <div class="mt-2">
                                            <strong><?php esc_html_e('Other Invalid Statuses:', 'maneli-car-inquiry'); ?></strong>
                                            <ul class="mb-0">
                                                <?php foreach ($stats['cash']['other_invalid'] as $status => $count): ?>
                                                    <li><?php echo esc_html($status); ?>: <?php echo number_format_i18n($count); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Migration Results -->
                    <?php if ($migration_run && $migration_results): ?>
                        <div class="alert alert-success">
                            <h5><?php esc_html_e('Migration Completed!', 'maneli-car-inquiry'); ?></h5>
                            <hr>
                            <h6><?php esc_html_e('Installment Inquiries:', 'maneli-car-inquiry'); ?></h6>
                            <ul>
                                <li><?php esc_html_e('Total:', 'maneli-car-inquiry'); ?> <?php echo number_format_i18n($migration_results['installment']['total']); ?></li>
                                <li><?php esc_html_e('Updated:', 'maneli-car-inquiry'); ?> <?php echo number_format_i18n($migration_results['installment']['updated']); ?></li>
                                <li><?php esc_html_e('Skipped:', 'maneli-car-inquiry'); ?> <?php echo number_format_i18n($migration_results['installment']['skipped']); ?></li>
                                <?php if (isset($migration_results['referred_fixes']['installment']['fixed']) && $migration_results['referred_fixes']['installment']['fixed'] > 0): ?>
                                    <li class="text-success"><strong><?php esc_html_e('Fixed to Referred:', 'maneli-car-inquiry'); ?> <?php echo number_format_i18n($migration_results['referred_fixes']['installment']['fixed']); ?></strong></li>
                                <?php endif; ?>
                            </ul>
                            <h6><?php esc_html_e('Cash Inquiries:', 'maneli-car-inquiry'); ?></h6>
                            <ul>
                                <li><?php esc_html_e('Total:', 'maneli-car-inquiry'); ?> <?php echo number_format_i18n($migration_results['cash']['total']); ?></li>
                                <li><?php esc_html_e('Updated:', 'maneli-car-inquiry'); ?> <?php echo number_format_i18n($migration_results['cash']['updated']); ?></li>
                                <li><?php esc_html_e('Skipped:', 'maneli-car-inquiry'); ?> <?php echo number_format_i18n($migration_results['cash']['skipped']); ?></li>
                                <?php if (isset($migration_results['referred_fixes']['cash']['fixed']) && $migration_results['referred_fixes']['cash']['fixed'] > 0): ?>
                                    <li class="text-success"><strong><?php esc_html_e('Fixed to Referred:', 'maneli-car-inquiry'); ?> <?php echo number_format_i18n($migration_results['referred_fixes']['cash']['fixed']); ?></strong></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Migration Form -->
                    <form method="post" action="" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to run the migration? This action cannot be undone.', 'maneli-car-inquiry'); ?>');">
                        <?php wp_nonce_field('maneli_run_migration', 'migration_nonce'); ?>
                        <div class="text-center">
                            <button type="submit" name="run_migration" value="1" class="btn btn-primary btn-lg">
                                <i class="la la-play me-2"></i>
                                <?php esc_html_e('Run Migration', 'maneli-car-inquiry'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
