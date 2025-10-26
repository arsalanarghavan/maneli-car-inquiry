
        </div>
        <!-- End::app-content -->

        <!-- Start::Footer -->
        <footer class="footer mt-auto py-3 bg-white text-center">
            <div class="container">
                <span class="text-muted"> <span id="year"><?php echo date('Y'); ?></span>
                    <?php 
                    $theme_handler = Maneli_Frontend_Theme_Handler::instance();
                    echo $theme_handler->get_footer_text();
                    ?>
                </span>
            </div>
        </footer>            
        <!-- End::Footer -->

    </div>

    <!-- Start::main-scripts -->
    
     <!-- Scroll To Top -->
     <div class="scrollToTop">
        <span class="arrow"><i class="la la-arrow-up fs-20"></i></span>
     </div>
     <div id="responsive-overlay"></div>
     <!-- Scroll To Top -->

     <!-- Popper JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/@popperjs/core/umd/popper.min.js"></script>

     <!-- Bootstrap JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
     
     <!-- Choices JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/choices.js/public/assets/scripts/choices.min.js"></script>

     <!-- Node Waves JS-->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/node-waves/waves.min.js"></script>

     <!-- Simplebar JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/simplebar/simplebar.min.js"></script>
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/simplebar.js"></script>
     
     <!-- Maneli Custom Fix - باید قبل از custom.js لود شود -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/maneli-custom-fix.js"></script>

     <!-- Auto Complete JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/@tarekraafat/autocomplete.js/autoComplete.min.js"></script>

     <!-- Color Picker JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/@simonwep/pickr/pickr.es5.min.js"></script>

     <!-- Date & Time Picker JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/flatpickr/flatpickr.min.js"></script>
    <!-- End::main-scripts -->

    <!-- Sticky JS -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/sticky.js"></script>

    <!-- Defaultmenu JS -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/defaultmenu.min.js"></script>

    <!-- Custom JS -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/custom.js"></script>

    <!-- Custom-Switcher JS -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/custom-switcher.min.js"></script>
    
    <!-- Persian Datepicker -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/persianDatepicker.min.js"></script>

    <script>
        // Set current year
        document.getElementById('year').innerHTML = new Date().getFullYear();
        
        $(document).ready(function() {
            // Initialize Persian Datepicker if element exists
            if ($('#daterange').length) {
                $('#daterange').persianDatepicker({
                    onShow: function() {},
                    onSelect: function () {}
                });
            }
        });
    </script>
    
    <?php
    // مطمئن شدن که Admin Bar نمایش داده نمی‌شه
    add_filter('show_admin_bar', '__return_false', 9999);
    
    // WordPress footer hook - allows plugins to add scripts
    if (function_exists('do_action')) {
        do_action('wp_footer');
    }
    ?>
    
</body>

</html>
