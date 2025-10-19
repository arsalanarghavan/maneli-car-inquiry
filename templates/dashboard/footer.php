
        </div>
        <!-- End::app-content -->

        <!-- Start::Footer -->
        <footer class="footer mt-auto py-3 bg-white text-center">
            <div class="container">
                <span class="text-muted"> <span id="year"></span>
                    طراحی با  <span class="bi bi-heart-fill text-danger"></span> توسط 
                    <span class="fw-medium text-primary">منلی کار</span>
                </span>
            </div>
        </footer>
        <!-- End::Footer -->

    </div>

    <!-- Start::main-scripts -->
    
     <!-- Scroll To Top -->
     <div class="scrollToTop">
        <span class="arrow"><i class="ti ti-arrow-narrow-up fs-20"></i></span>
     </div>
     <div id="responsive-overlay"></div>
     <!-- Scroll To Top -->

     <!-- Popper JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/@popperjs/core/umd/popper.min.js"></script>

     <!-- Bootstrap JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

     <!-- Node Waves JS-->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/node-waves/waves.min.js"></script>

     <!-- Simplebar JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/simplebar/simplebar.min.js"></script>
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/simplebar.js"></script>

     <!-- Auto Complete JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/@tarekraafat/autocomplete.js/autoComplete.min.js"></script>

     <!-- Color Picker JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/@simonwep/pickr/pickr.es5.min.js"></script>

     <!-- Date & Time Picker JS -->
     <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/flatpickr/flatpickr.min.js"></script>
    <!-- End::main-scripts -->

    <!-- Apex Charts JS -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/apexcharts/apexcharts.min.js"></script>

    <!-- Sales Dashboard --> 
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/sales-dashboard.js"></script>

    <!-- Sticky JS -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/sticky.js"></script>

    <!-- Defaultmenu JS -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/defaultmenu.min.js"></script>

    <!-- Custom JS -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/custom.js"></script>

    <!-- Custom-Switcher JS -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/custom-switcher.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
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
    
</body>

</html>
