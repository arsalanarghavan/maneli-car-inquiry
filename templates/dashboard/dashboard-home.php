<!-- Start:: row-1 -->
<div class="row">
    <div class="col-xl-8">
        <div class="row">
            <div class="col-xxl-3 col-xl-6">
                <div class="card custom-card overflow-hidden main-content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <span class="text-muted d-block mb-1">مجموع استعلامات</span>
                                <h4 class="fw-medium mb-0">854</h4>
                            </div>
                            <div class="lh-1">
                                <span class="avatar avatar-md avatar-rounded bg-primary">
                                    <i class="la la-file-alt fs-5"></i>
                                </span>
                            </div>
                        </div>
                        <div class="text-muted fs-13">افزایش یافته <span class="text-success">2.56%<i class="la la-arrow-up fs-16"></i></span></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6">
                <div class="card custom-card overflow-hidden main-content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <span class="text-muted d-block mb-1">کل درآمد</span>
                                <h4 class="fw-medium mb-0">3,241,000</h4>
                            </div>
                            <div class="lh-1">
                                <span class="avatar avatar-md avatar-rounded bg-primary2">
                                    <i class="la la-dollar fs-5"></i>
                                </span>
                            </div>
                        </div>
                        <div class="text-muted fs-13">افزایش یافته <span class="text-success">7.66%<i class="la la-arrow-up fs-16"></i></span></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6">
                <div class="card custom-card overflow-hidden main-content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <span class="text-muted d-block mb-1">استعلامات موفق</span>
                                <h4 class="fw-medium mb-0">1,76,586</h4>
                            </div>
                            <div class="lh-1">
                                <span class="avatar avatar-md avatar-rounded bg-primary3">
                                    <i class="la la-check fs-5"></i>
                                </span>
                            </div>
                        </div>
                        <div class="text-muted fs-13">افزایش یافته <span class="text-success">1.27%<i class="la la-arrow-up fs-16"></i></span></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6">
                <div class="card custom-card overflow-hidden main-content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <span class="text-muted d-block mb-1">در انتظار</span>
                                <h4 class="fw-medium mb-0">482</h4>
                            </div>
                            <div class="lh-1">
                                <span class="avatar avatar-md avatar-rounded bg-secondary">
                                    <i class="la la-clock fs-5"></i>
                                </span>
                            </div>
                        </div>
                        <div class="text-muted fs-13">کاهش یافته <span class="text-danger">1.46%<i class="la la-arrow-down fs-16"></i></span></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            نمودار استعلامات
                        </div>
                        <div class="d-flex flex-wrap gap-2"> 
                            <div> <input class="form-control form-control-sm" type="text" placeholder="انتخاب تاریخ" aria-label=".form-control-sm example" id="daterange"> </div>
                            <div class="dropdown"> 
                                <a href="javascript:void(0);" class="btn btn-sm btn-primary-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"> مرتب‌سازی بر اساس </a> 
                                <ul class="dropdown-menu" role="menu"> 
                                    <li><a class="dropdown-item" href="javascript:void(0);">هفته جاری</a></li> 
                                    <li><a class="dropdown-item" href="javascript:void(0);">هفته گذشته</a></li> 
                                    <li><a class="dropdown-item" href="javascript:void(0);">ماه جاری</a></li> 
                                </ul> 
                            </div> 
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="crm-revenue-analytics"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            سفارشات اخیر
                        </div>
                        <div class="d-flex flex-wrap gap-2"> 
                            <div> <input class="form-control form-control-sm" type="text" placeholder="جستجو" aria-label=".form-control-sm example"> </div>
                            <div class="dropdown"> 
                                <a href="javascript:void(0);" class="btn btn-primary btn-sm btn-wave waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false"> مرتب‌سازی بر اساس<i class="la la-angle-down align-middle ms-1 d-inline-block"></i> </a> 
                                <ul class="dropdown-menu" role="menu"> 
                                    <li><a class="dropdown-item" href="javascript:void(0);">جدیدترین</a></li> 
                                    <li><a class="dropdown-item" href="javascript:void(0);">تاریخ سفارش</a></li> 
                                    <li><a class="dropdown-item" href="javascript:void(0);">قیمت</a></li> 
                                </ul> 
                            </div> 
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table text-nowrap">
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-center">
                                            <input class="form-check-input" type="checkbox" id="checkboxNoLabel" value="" aria-label="..." checked="">
                                        </th>
                                        <th scope="col">مشتری</th>
                                        <th scope="col">محصول</th>
                                        <th scope="col" class="text-center">تعداد</th>
                                        <th scope="col" class="text-center">قیمت</th>
                                        <th scope="col">وضعیت</th>
                                        <th scope="col">تاریخ</th>
                                        <th scope="col">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center">
                                            <input class="form-check-input" type="checkbox" id="checkboxNoLabel22" value="" aria-label="..." checked="">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="lh-1">
                                                    <span class="avatar avatar-sm">
                                                        <img src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/images/faces/11.jpg" alt="">
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="d-block fw-medium">علی محمدی</span>
                                                    <span class="d-block fs-11 text-muted">ali@example.com</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            پژو 206
                                        </td>
                                        <td class="text-center">
                                            1
                                        </td>
                                        <td class="text-center">
                                            250,000,000
                                        </td>
                                        <td>
                                            <span class="badge bg-primary2-transparent">موفق</span>
                                        </td>
                                        <td>
                                            03 مهر 1403
                                        </td>
                                        <td>
                                            <div class="btn-list">
                                                <button class="btn btn-sm btn-icon btn-success-light"><i class="la la-pencil"></i></button>
                                                <button class="btn btn-sm btn-icon btn-primary-light"><i class="la la-eye"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">
                                            <input class="form-check-input" type="checkbox" id="checkboxNoLabel12" value="" aria-label="...">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="lh-1">
                                                    <span class="avatar avatar-sm">
                                                        <img src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/images/faces/1.jpg" alt="">
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="d-block fw-medium">زهرا احمدی</span>
                                                    <span class="d-block fs-11 text-muted">zahra@example.com</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            سمند
                                        </td>
                                        <td class="text-center">
                                            1
                                        </td>
                                        <td class="text-center">
                                            180,000,000
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-transparent">در حال انجام</span>
                                        </td>
                                        <td>
                                            02 مهر 1403
                                        </td>
                                        <td>
                                            <div class="btn-list">
                                                <button class="btn btn-sm btn-icon btn-success-light"><i class="la la-pencil"></i></button>
                                                <button class="btn btn-sm btn-icon btn-primary-light"><i class="la la-eye"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">
                                            <input class="form-check-input" type="checkbox" id="checkboxNoLabel42" value="" aria-label="..." checked="">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="lh-1">
                                                    <span class="avatar avatar-sm">
                                                        <img src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/images/faces/6.jpg" alt="">
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="d-block fw-medium">حسن رضایی</span>
                                                    <span class="d-block fs-11 text-muted">hasan@example.com</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            پراید
                                        </td>
                                        <td class="text-center">
                                            1
                                        </td>
                                        <td class="text-center">
                                            120,000,000
                                        </td>
                                        <td>
                                            <span class="badge bg-primary2-transparent">موفق</span>
                                        </td>
                                        <td>
                                            01 مهر 1403
                                        </td>
                                        <td>
                                            <div class="btn-list">
                                                <button class="btn btn-sm btn-icon btn-success-light"><i class="la la-pencil"></i></button>
                                                <button class="btn btn-sm btn-icon btn-primary-light"><i class="la la-eye"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="avatar avatar-md bg-primary-transparent">
                                <i class="la la-chart-line fs-5"></i>
                            </div>
                            <div class="flex-fill d-flex align-items-start justify-content-between">
                                <div>
                                    <span class="fs-11 mb-1 d-block fw-medium">کل استعلامات</span> 
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h4 class="mb-0 d-flex align-items-center">3,736<span class="text-success fs-12 ms-2 op-1"><i class="la la-chart-line align-middle me-1"></i>۰٫۵۷٪</span></h4>
                                    </div>
                                </div>
                                <a href="javascript:void(0);" class="text-success fs-12 text-decoration-underline">جزئیات</a>
                            </div>

                        </div>
                        <div id="orders" class="my-2"></div>
                    </div>
                    <div class="card-footer border-top border-block-start-dashed">
                        <div class="d-grid">
                            <button class="btn btn-primary-ghost btn-wave fw-medium waves-effect waves-light">
                                آمار کامل
                                <i class="la la-arrow-left ms-2 fs-16 d-inline-block align-middle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            دسته‌های پرفروش
                        </div>
                        <div class="dropdown"> 
                            <a href="javascript:void(0);" class="btn btn-sm btn-light text-muted dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="true"> مرتب‌سازی بر اساس</a> 
                            <ul class="dropdown-menu" role="menu" data-popper-placement="bottom-end"> 
                                <li><a class="dropdown-item" href="javascript:void(0);"> هفته جاری</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">هفته گذشته</a></li> 
                                <li><a class="dropdown-item" href="javascript:void(0);"> ماه جاری</a></li> 
                            </ul> 
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 pb-0">
                            <div class="progress-stacked progress-sm mb-2 gap-1">
                                <div class="progress-bar" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="progress-bar bg-primary1" role="progressbar" style="width: 15%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="progress-bar bg-primary2" role="progressbar" style="width: 15%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="progress-bar bg-primary3" role="progressbar" style="width: 25%" aria-valuenow="35" aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="progress-bar bg-secondary" role="progressbar" style="width: 20%" aria-valuenow="35" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div>مجموع فروش</div>
                                <div class="h6 mb-0"><span class="text-success me-2 fs-11">2.74%<i class="la la-arrow-up"></i></span>1,25,875</div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table text-nowrap">
                                <tbody>
                                    <tr>
                                        <td>
                                            <span class="fw-medium">سواری</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">31,245</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-muted fs-12">25% ناخالص</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-success">0.45% <i class="la la-chart-line"></i></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="fw-medium">وانت</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">29,553</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-muted fs-12">16% ناخالص</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-warning">0.27% <i class="la la-chart-line"></i></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="fw-medium">SUV</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">24,577</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-muted fs-12">22% ناخالص</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-secondary">0.63% <i class="la la-chart-line"></i></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="fw-medium">سایر</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">19,278</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-muted fs-12">18% ناخالص</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-primary1">1.14% <i class="la la-chart-line-down"></i></span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End:: row-1 -->
