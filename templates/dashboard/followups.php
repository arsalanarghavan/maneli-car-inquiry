<!-- Start::row -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">مدیریت پیگیری‌ها</div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFollowupModal">
                    <i class="la la-plus me-1"></i>
                    پیگیری جدید
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <select class="form-select" id="followup-status-filter">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="pending">در انتظار</option>
                            <option value="completed">انجام شده</option>
                            <option value="cancelled">لغو شده</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="followup-type-filter">
                            <option value="">همه انواع</option>
                            <option value="cash">نقدی</option>
                            <option value="installment">اقساطی</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="followup-search" placeholder="جستجوی پیگیری...">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="filterFollowups()">
                            <i class="la la-search me-1"></i> فیلتر
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="followups-table" class="table table-bordered text-nowrap w-100">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>مشتری</th>
                                <th>محصول</th>
                                <th>نوع استعلام</th>
                                <th>تاریخ پیگیری</th>
                                <th>وضعیت</th>
                                <th>توضیحات</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get followups from database
                            global $wpdb;

                            $followups = $wpdb->get_results("
                                SELECT fm.*, p.post_title as inquiry_title, pm1.meta_value as customer_name, pm2.meta_value as product_name, pm3.meta_value as inquiry_type
                                FROM {$wpdb->prefix}maneli_followups fm
                                INNER JOIN {$wpdb->posts} p ON fm.inquiry_id = p.ID
                                LEFT JOIN {$wpdb->postmeta} pm1 ON fm.inquiry_id = pm1.post_id AND pm1.meta_key = 'inquiry_name'
                                LEFT JOIN {$wpdb->postmeta} pm2 ON fm.inquiry_id = pm2.post_id AND pm2.meta_key = 'inquiry_car_type'
                                LEFT JOIN {$wpdb->postmeta} pm3 ON fm.inquiry_id = pm3.post_id AND pm3.meta_key = 'inquiry_type'
                                ORDER BY fm.followup_date DESC
                                LIMIT 20
                            ");

                            if (empty($followups)) {
                                ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <div class="py-4">
                                            <i class="la la-inbox fs-1 text-muted mb-3 d-block"></i>
                                            <p class="text-muted">هیچ پیگیری‌ای یافت نشد.</p>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFollowupModal">
                                                <i class="la la-plus me-1"></i>
                                                ایجاد اولین پیگیری
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            } else {
                                foreach ($followups as $followup) {
                                    $status_badge = '';
                                    switch ($followup->status) {
                                        case 'completed':
                                            $status_badge = '<span class="badge bg-success">انجام شده</span>';
                                            break;
                                        case 'cancelled':
                                            $status_badge = '<span class="badge bg-danger">لغو شده</span>';
                                            break;
                                        default:
                                            $status_badge = '<span class="badge bg-warning">در انتظار</span>';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $followup->id; ?></td>
                                        <td><?php echo esc_html($followup->customer_name ?: 'نامشخص'); ?></td>
                                        <td><?php echo esc_html($followup->product_name ?: 'نامشخص'); ?></td>
                                        <td><?php echo esc_html($followup->inquiry_type === 'cash' ? 'نقدی' : ($followup->inquiry_type === 'installment' ? 'اقساطی' : 'نامشخص')); ?></td>
                                        <td><?php echo esc_html($followup->followup_date); ?></td>
                                        <td><?php echo $status_badge; ?></td>
                                        <td>
                                            <span class="text-truncate d-block" style="max-width: 200px;" title="<?php echo esc_attr($followup->notes); ?>">
                                                <?php echo esc_html(wp_trim_words($followup->notes, 5)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-list">
                                                <button class="btn btn-sm btn-primary-light" onclick="editFollowup(<?php echo $followup->id; ?>)">
                                                    <i class="la la-edit"></i> ویرایش
                                                </button>
                                                <button class="btn btn-sm btn-danger-light" onclick="deleteFollowup(<?php echo $followup->id; ?>)">
                                                    <i class="la la-trash"></i> حذف
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
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

<!-- Add Followup Modal -->
<div class="modal fade" id="addFollowupModal" tabindex="-1" aria-labelledby="addFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFollowupModalLabel">افزودن پیگیری جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="add-followup-form">
                    <div class="mb-3">
                        <label for="followup_inquiry_id" class="form-label">انتخاب استعلام</label>
                        <select class="form-select" id="followup_inquiry_id" name="inquiry_id" required>
                            <option value="">در حال بارگذاری...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="followup_date" class="form-label">تاریخ پیگیری</label>
                        <input type="text" class="form-control persian-datepicker" id="followup_date" name="followup_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="followup_status" class="form-label">وضعیت</label>
                        <select class="form-select" id="followup_status" name="status" required>
                            <option value="pending">در انتظار</option>
                            <option value="completed">انجام شده</option>
                            <option value="cancelled">لغو شده</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="followup_notes" class="form-label">توضیحات</label>
                        <textarea class="form-control" id="followup_notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-primary" id="save-followup-btn">ذخیره</button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var currentPage = 1;

    // Load followups
    function loadFollowups(page = 1) {
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'maneli_get_followups',
                page: page
            },
            success: function(response) {
                if (response.success && response.data.followups) {
                    var html = '';
                    if (response.data.followups.length === 0) {
                        html = '<tr><td colspan="8" class="text-center">هیچ پیگیری‌ای یافت نشد.</td></tr>';
                    } else {
                        response.data.followups.forEach(function(followup, index) {
                            var statusBadge = '';
                            switch(followup.status) {
                                case 'completed':
                                    statusBadge = '<span class="badge bg-success">انجام شده</span>';
                                    break;
                                case 'cancelled':
                                    statusBadge = '<span class="badge bg-danger">لغو شده</span>';
                                    break;
                                default:
                                    statusBadge = '<span class="badge bg-warning">در انتظار</span>';
                            }

                            html += '<tr>';
                            html += '<td>' + ((page - 1) * 20 + index + 1) + '</td>';
                            html += '<td>' + (followup.customer_name || '-') + '</td>';
                            html += '<td>' + (followup.product_name || '-') + '</td>';
                            html += '<td>' + (followup.inquiry_type === 'cash' ? 'نقدی' : 'اقساطی') + '</td>';
                            html += '<td>' + (followup.followup_date || '-') + '</td>';
                            html += '<td>' + statusBadge + '</td>';
                            html += '<td>' + (followup.notes || '-') + '</td>';
                            html += '<td>';
                            html += '<button class="btn btn-sm btn-info-light edit-followup" data-id="' + followup.id + '"><i class="la la-pencil"></i></button> ';
                            html += '<button class="btn btn-sm btn-danger-light delete-followup" data-id="' + followup.id + '"><i class="la la-trash"></i></button>';
                            html += '</td>';
                            html += '</tr>';
                        });
                    }
                    $('#followups-table-body').html(html);
                }
            }
        });
    }

    // Load inquiries for dropdown
    $.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'maneli_get_inquiries_list'
        },
        success: function(response) {
            if (response.success && response.data.inquiries) {
                var html = '<option value="">انتخاب استعلام...</option>';
                response.data.inquiries.forEach(function(inquiry) {
                    html += '<option value="' + inquiry.id + '">' + inquiry.customer_name + ' - ' + inquiry.product_name + '</option>';
                });
                $('#followup_inquiry_id').html(html);
            }
        }
    });

    // Initialize Persian Datepicker
    if ($('.persian-datepicker').length) {
        $('.persian-datepicker').persianDatepicker({
            format: 'YYYY/MM/DD'
        });
    }

    // Save followup
    $('#save-followup-btn').on('click', function() {
        var formData = $('#add-followup-form').serialize();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData + '&action=maneli_add_followup&nonce=<?php echo wp_create_nonce('maneli_add_followup'); ?>',
            success: function(response) {
                if (response.success) {
                    $('#addFollowupModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'موفق!',
                        text: 'پیگیری با موفقیت افزوده شد.',
                        timer: 2000
                    });
                    loadFollowups(currentPage);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا!',
                        text: response.data.message || 'خطا در افزودن پیگیری'
                    });
                }
            }
        });
    });

    // Load initial data
    loadFollowups(1);
});
</script>

