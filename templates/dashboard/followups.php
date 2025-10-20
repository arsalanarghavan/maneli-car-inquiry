<?php
/**
 * Dashboard Followups Page
 * List and manage follow-up tasks
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="row">
    <div class="col-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    لیست پیگیری‌ها
                </div>
                <div>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFollowupModal">
                        <i class="ri-add-line me-2"></i>
                        افزودن پیگیری جدید
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table text-nowrap table-hover">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">مشتری</th>
                                <th scope="col">محصول</th>
                                <th scope="col">نوع</th>
                                <th scope="col">تاریخ پیگیری</th>
                                <th scope="col">وضعیت</th>
                                <th scope="col">توضیحات</th>
                                <th scope="col">عملیات</th>
                            </tr>
                        </thead>
                        <tbody id="followups-table-body">
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">در حال بارگذاری...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-end mb-0" id="pagination">
                        <!-- Pagination will be loaded here -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

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
                            html += '<button class="btn btn-sm btn-info-light edit-followup" data-id="' + followup.id + '"><i class="ri-pencil-line"></i></button> ';
                            html += '<button class="btn btn-sm btn-danger-light delete-followup" data-id="' + followup.id + '"><i class="ri-delete-bin-line"></i></button>';
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

