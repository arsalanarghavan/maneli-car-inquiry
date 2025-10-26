<?php
/**
 * Calendar Demo Page
 * This page demonstrates how to use the new calendar meetings page
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
        <!-- Header -->
        <div class="card custom-card mb-4">
            <div class="card-header bg-primary-transparent">
                <div class="card-title">
                    <i class="la la-calendar-alt me-2"></i>
                    تقویم جلسات - راهنمای استفاده
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="text-primary mb-3">ویژگی‌های تقویم جلسات</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="la la-check-circle text-success me-2"></i>
                                <strong>تقویم کامل:</strong> نمایش روزانه، هفتگی و ماهانه
                            </li>
                            <li class="mb-2">
                                <i class="la la-check-circle text-success me-2"></i>
                                <strong>تاریخ شمسی:</strong> تمام تاریخ‌ها به صورت شمسی نمایش داده می‌شوند
                            </li>
                            <li class="mb-2">
                                <i class="la la-check-circle text-success me-2"></i>
                                <strong>شروع از شنبه:</strong> هفته از روز شنبه شروع می‌شود
                            </li>
                            <li class="mb-2">
                                <i class="la la-check-circle text-success me-2"></i>
                                <strong>جدول جلسات:</strong> لیست مرتب شده بر اساس نزدیک‌ترین زمان
                            </li>
                            <li class="mb-2">
                                <i class="la la-check-circle text-success me-2"></i>
                                <strong>طراحی زیبا:</strong> رابط کاربری مدرن و واکنش‌گرا
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="la la-calendar" style="font-size: 80px; color: var(--primary-color);"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Instructions -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card mb-4">
                    <div class="card-header bg-info-transparent">
                        <div class="card-title">
                            <i class="la la-info-circle me-2"></i>
                            نحوه استفاده از تقویم
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="text-primary">1. تغییر نمای تقویم</h6>
                            <p class="text-muted">از دکمه‌های بالای تقویم می‌توانید بین نمای روزانه، هفتگی و ماهانه تغییر دهید.</p>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-primary">2. مشاهده جزئیات جلسه</h6>
                            <p class="text-muted">روی هر جلسه در تقویم کلیک کنید تا جزئیات آن را مشاهده کنید.</p>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-primary">3. جدول جلسات</h6>
                            <p class="text-muted">در جدول پایین، جلسات به ترتیب نزدیک‌ترین زمان مرتب شده‌اند.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card mb-4">
                    <div class="card-header bg-success-transparent">
                        <div class="card-title">
                            <i class="la la-palette me-2"></i>
                            رنگ‌بندی جلسات
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-3">
                                    <span class="badge" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); width: 20px; height: 20px; border-radius: 4px;"></span>
                                </div>
                                <span>جلسات نقدی</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-3">
                                    <span class="badge" style="background: linear-gradient(135deg, #007bff 0%, #6610f2 100%); width: 20px; height: 20px; border-radius: 4px;"></span>
                                </div>
                                <span>جلسات اقساطی</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="badge" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); width: 20px; height: 20px; border-radius: 4px;"></span>
                                </div>
                                <span>جلسات رزرو شده</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access -->
        <div class="card custom-card">
            <div class="card-header bg-warning-transparent">
                <div class="card-title">
                    <i class="la la-rocket me-2"></i>
                    دسترسی سریع
                </div>
            </div>
            <div class="card-body text-center">
                <p class="text-muted mb-4">برای مشاهده تقویم جلسات، روی دکمه زیر کلیک کنید:</p>
                <a href="<?php echo home_url('/dashboard/calendar-meetings'); ?>" class="btn btn-primary btn-lg">
                    <i class="la la-calendar me-2"></i>
                    مشاهده تقویم جلسات
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Demo Page Styles */
.custom-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.custom-card:hover {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.card-header.bg-primary-transparent {
    background: linear-gradient(135deg, rgba(var(--primary-rgb, 0, 123, 255), 0.1) 0%, rgba(var(--primary-rgb, 0, 123, 255), 0.05) 100%);
    border-bottom: 1px solid rgba(var(--primary-rgb, 0, 123, 255), 0.2);
}

.card-header.bg-info-transparent {
    background: linear-gradient(135deg, rgba(13, 202, 240, 0.1) 0%, rgba(13, 202, 240, 0.05) 100%);
    border-bottom: 1px solid rgba(13, 202, 240, 0.2);
}

.card-header.bg-success-transparent {
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.1) 0%, rgba(25, 135, 84, 0.05) 100%);
    border-bottom: 1px solid rgba(25, 135, 84, 0.2);
}

.card-header.bg-warning-transparent {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
    border-bottom: 1px solid rgba(255, 193, 7, 0.2);
}

.card-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-color, #007bff);
    margin: 0;
    display: flex;
    align-items: center;
}

.card-title i {
    margin-left: 0.5rem;
    font-size: 1.5rem;
}

.list-unstyled li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.list-unstyled li:last-child {
    border-bottom: none;
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(var(--primary-rgb, 0, 123, 255), 0.4);
}
</style>
