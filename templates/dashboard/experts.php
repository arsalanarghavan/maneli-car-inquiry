<!-- Start::row -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">مدیریت کارشناسان</div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpertModal">
                        <i class="la la-user-plus me-1"></i>
                        افزودن کارشناس جدید
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="la la-search"></i>
                            </span>
                            <input type="search" id="expert-search-input" class="form-control" placeholder="جستجوی نام، ایمیل یا موبایل...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="expert-status-filter">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="active">فعال</option>
                            <option value="inactive">غیرفعال</option>
                        </select>
                    </div>
                    <div class="col-md-5 text-end">
                        <span class="text-muted">
                            <i class="la la-users me-1"></i>
                            تعداد کارشناسان: <strong><?php echo count(get_users(['role' => 'maneli_expert'])); ?></strong>
                        </span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap" id="experts-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 25%;">نام کارشناس</th>
                                <th style="width: 20%;">ایمیل</th>
                                <th style="width: 15%;">موبایل</th>
                                <th style="width: 15%;">تعداد استعلامات</th>
                                <th style="width: 10%;">وضعیت</th>
                                <th style="width: 10%;">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $experts = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name']);
                            $counter = 1;
                            
                            if (!empty($experts)) {
                                foreach ($experts as $expert) {
                                    $mobile = get_user_meta($expert->ID, 'mobile_number', true);
                                    $is_active = get_user_meta($expert->ID, 'expert_active', true) !== 'no';
                                    
                                    // شمارش استعلامات کارشناس
                                    $inquiry_count = count(get_posts([
                                        'post_type' => ['inquiry', 'cash_inquiry'],
                                        'post_status' => 'any',
                                        'author' => $expert->ID,
                                        'posts_per_page' => -1
                                    ]));
                                    ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php echo get_avatar($expert->ID, 32, '', '', ['class' => 'avatar avatar-sm rounded-circle me-2']); ?>
                                                <span class="fw-medium"><?php echo esc_html($expert->display_name); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo esc_html($expert->user_email); ?></td>
                                        <td>
                                            <?php if ($mobile) : ?>
                                                <i class="la la-mobile me-1"></i><?php echo esc_html($mobile); ?>
                                            <?php else : ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-transparent">
                                                <?php echo $inquiry_count; ?> استعلام
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($is_active) : ?>
                                                <span class="badge bg-success">فعال</span>
                                            <?php else : ?>
                                                <span class="badge bg-danger">غیرفعال</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo get_edit_user_link($expert->ID); ?>" 
                                                   class="btn btn-sm btn-primary-light" 
                                                   title="ویرایش">
                                                    <i class="la la-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-info-light" 
                                                        onclick="viewExpertStats(<?php echo $expert->ID; ?>)"
                                                        title="آمار">
                                                    <i class="la la-chart-bar"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-<?php echo $is_active ? 'warning' : 'success'; ?>-light" 
                                                        onclick="toggleExpertStatus(<?php echo $expert->ID; ?>, <?php echo $is_active ? 'false' : 'true'; ?>)"
                                                        title="<?php echo $is_active ? 'غیرفعال کردن' : 'فعال کردن'; ?>">
                                                    <i class="la la-toggle-<?php echo $is_active ? 'on' : 'off'; ?>"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="alert alert-info mb-0">
                                            <i class="la la-info-circle me-2"></i>
                                            هیچ کارشناسی ثبت نشده است.
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->

<!-- Add Expert Modal -->
<div class="modal fade" id="addExpertModal" tabindex="-1" aria-labelledby="addExpertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addExpertModalLabel">افزودن کارشناس جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="add-expert-form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="expert-username" class="form-label">نام کاربری *</label>
                            <input type="text" class="form-control" id="expert-username" required>
                        </div>
                        <div class="col-md-6">
                            <label for="expert-email" class="form-label">ایمیل *</label>
                            <input type="email" class="form-control" id="expert-email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="expert-first-name" class="form-label">نام *</label>
                            <input type="text" class="form-control" id="expert-first-name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="expert-last-name" class="form-label">نام خانوادگی *</label>
                            <input type="text" class="form-control" id="expert-last-name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="expert-mobile" class="form-label">شماره موبایل</label>
                            <input type="text" class="form-control" id="expert-mobile" placeholder="09xxxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label for="expert-password" class="form-label">رمز عبور *</label>
                            <input type="password" class="form-control" id="expert-password" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="send-credentials" checked>
                                <label class="form-check-label" for="send-credentials">
                                    ارسال اطلاعات ورود به ایمیل کارشناس
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-primary" onclick="saveExpert()">
                    <i class="la la-save me-1"></i>
                    ذخیره کارشناس
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Search experts
    $('#expert-search-input').on('keyup', function() {
        const search = $(this).val().toLowerCase();
        $('#experts-table tbody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(search));
        });
    });

    // Filter by status
    $('#expert-status-filter').on('change', function() {
        const status = $(this).val();
        $('#experts-table tbody tr').each(function() {
            if (!status) {
                $(this).show();
            } else {
                const badge = $(this).find('.badge');
                const isActive = badge.hasClass('bg-success');
                if ((status === 'active' && isActive) || (status === 'inactive' && !isActive)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            }
        });
    });
});

// Save expert
function saveExpert() {
    const form = document.getElementById('add-expert-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    Swal.fire({
        title: 'در حال ذخیره...',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'maneli_add_expert',
            username: jQuery('#expert-username').val(),
            email: jQuery('#expert-email').val(),
            first_name: jQuery('#expert-first-name').val(),
            last_name: jQuery('#expert-last-name').val(),
            mobile: jQuery('#expert-mobile').val(),
            password: jQuery('#expert-password').val(),
            send_credentials: jQuery('#send-credentials').is(':checked'),
            nonce: '<?php echo wp_create_nonce('maneli_add_expert'); ?>'
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'موفق!',
                    text: 'کارشناس با موفقیت افزوده شد.',
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خطا!',
                    text: response.data.message || 'خطا در افزودن کارشناس'
                });
            }
        }
    });
}

// Toggle expert status
function toggleExpertStatus(userId, activate) {
    Swal.fire({
        title: activate ? 'فعال‌سازی کارشناس؟' : 'غیرفعال‌سازی کارشناس؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'بله',
        cancelButtonText: 'خیر'
    }).then((result) => {
        if (result.isConfirmed) {
            jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'maneli_toggle_expert_status',
                user_id: userId,
                active: activate,
                nonce: '<?php echo wp_create_nonce('maneli_toggle_expert'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    Swal.fire('خطا', response.data.message, 'error');
                }
            });
        }
    });
}

// View expert stats
function viewExpertStats(userId) {
    Swal.fire({
        title: 'در حال بارگذاری...',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'maneli_get_expert_stats',
        user_id: userId,
        nonce: '<?php echo wp_create_nonce('maneli_expert_stats'); ?>'
    }, function(response) {
        if (response.success) {
            Swal.fire({
                title: 'آمار کارشناس',
                html: response.data.html,
                width: 600,
                showCloseButton: true
            });
        } else {
            Swal.fire('خطا', 'خطا در بارگذاری آمار', 'error');
        }
    });
}
</script>

