<?php
/**
 * Calendar Test Page - Simple test to check if calendar loads
 */

// Check permission
if (!current_user_can('manage_maneli_inquiries') && !in_array('maneli_expert', wp_get_current_user()->roles, true)) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="la la-exclamation-triangle me-2"></i>
                <strong>دسترسی محدود!</strong> شما به این صفحه دسترسی ندارید.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php
    return;
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header bg-primary-transparent">
                <div class="card-title">
                    <i class="la la-calendar-alt me-2"></i>
                    تست تقویم جلسات
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h5>وضعیت سیستم:</h5>
                    <ul class="mb-0">
                        <li><strong>تاریخ فعلی:</strong> <?php echo current_time('Y-m-d H:i:s'); ?></li>
                        <li><strong>تاریخ شمسی:</strong> <?php echo function_exists('maneli_gregorian_to_jalali') ? maneli_gregorian_to_jalali(date('Y'), date('m'), date('d'), 'Y/m/d') : 'تابع موجود نیست'; ?></li>
                        <li><strong>jQuery:</strong> <span id="jquery-test">در حال بررسی...</span></li>
                        <li><strong>FullCalendar:</strong> <span id="fullcalendar-test">در حال بررسی...</span></li>
                    </ul>
                </div>
                
                <div class="text-center">
                    <button class="btn btn-primary" onclick="testCalendar()">
                        <i class="la la-play me-2"></i>
                        تست تقویم
                    </button>
                </div>
                
                <div id="calendar-test-result" class="mt-4" style="display: none;">
                    <div class="alert alert-success">
                        <h5>نتیجه تست:</h5>
                        <p id="test-result-text"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test jQuery
    if (typeof jQuery !== 'undefined') {
        document.getElementById('jquery-test').innerHTML = '<span class="text-success">✓ موجود</span>';
    } else {
        document.getElementById('jquery-test').innerHTML = '<span class="text-danger">✗ موجود نیست</span>';
    }
    
    // Test FullCalendar
    if (typeof FullCalendar !== 'undefined') {
        document.getElementById('fullcalendar-test').innerHTML = '<span class="text-success">✓ موجود</span>';
    } else {
        document.getElementById('fullcalendar-test').innerHTML = '<span class="text-danger">✗ موجود نیست</span>';
    }
});

function testCalendar() {
    const resultDiv = document.getElementById('calendar-test-result');
    const resultText = document.getElementById('test-result-text');
    
    let result = '<strong>نتایج تست:</strong><br>';
    
    // Test jQuery
    if (typeof jQuery !== 'undefined') {
        result += '✓ jQuery بارگذاری شده<br>';
    } else {
        result += '✗ jQuery بارگذاری نشده<br>';
    }
    
    // Test FullCalendar
    if (typeof FullCalendar !== 'undefined') {
        result += '✓ FullCalendar بارگذاری شده<br>';
    } else {
        result += '✗ FullCalendar بارگذاری نشده<br>';
    }
    
    // Test Bootstrap
    if (typeof bootstrap !== 'undefined') {
        result += '✓ Bootstrap بارگذاری شده<br>';
    } else {
        result += '✗ Bootstrap بارگذاری نشده<br>';
    }
    
    resultText.innerHTML = result;
    resultDiv.style.display = 'block';
}
</script>
