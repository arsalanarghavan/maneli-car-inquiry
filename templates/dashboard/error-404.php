<?php
/**
 * Error 404 Template
 * Page Not Found
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Start::app-content -->
<div class="main-content app-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">
            
            <!-- Start::row -->
            <div class="row">
                <div class="col-12">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="text-center">
                                <p class="error-text mb-4">۴۰۴</p>
                                <p class="fs-4 fw-normal mb-2"><?php esc_html_e('Oops, the page you\'re looking for doesn\'t exist?', 'maneli-car-inquiry'); ?></p>
                                <p class="fs-15 mb-5 text-muted"><?php esc_html_e('The requested page is not available. It may have been moved, deleted, or never existed.', 'maneli-car-inquiry'); ?></p>
                                <a href="<?php echo home_url('/dashboard'); ?>" class="btn btn-primary"><i class="ri-arrow-right-line align-middle me-1 d-inline-block"></i> <?php esc_html_e('Return to Main Page', 'maneli-car-inquiry'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End::row -->

        </div>
    </div>
</div>
<!-- End::app-content -->

