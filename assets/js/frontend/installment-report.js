/**
 * Installment Inquiry Report - Expert Status Management
 * 
 * Handles all expert/admin actions on single installment inquiry report pages:
 * - Start Progress
 * - Schedule Meeting
 * - Schedule Follow-up
 * - Cancel
 * - Complete
 * - Reject
 * - Save Expert Notes
 */

(function($) {
    'use strict';

    // بررسی که در صفحه گزارش اقساطی هستیم
    if (!$('.frontend-expert-report[data-inquiry-type="installment"]').length) {
        return;
    }

    const inquiryId = $('#installment-inquiry-details').data('inquiry-id');
    
    if (!inquiryId) {
        console.error('Installment Inquiry ID not found');
        return;
    }

    // Helper: ارسال AJAX برای تغییر وضعیت
    function updateInquiryStatus(action, data = {}) {
        return $.ajax({
            url: maneliInstallmentReport.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_update_installment_status',
                nonce: maneliInstallmentReport.nonces.update_status,
                inquiry_id: inquiryId,
                action_type: action,
                ...data
            }
        });
    }

    // 1. شروع پیگیری
    $(document).on('click', '.installment-status-btn[data-action="start_progress"]', function() {
        Swal.fire({
            title: 'شروع پیگیری',
            text: 'آیا مطمئن هستید که می‌خواهید پیگیری این استعلام را شروع کنید؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'بله، شروع کن',
            cancelButtonText: 'لغو',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                updateInquiryStatus('start_progress')
                    .done(function(response) {
                        Swal.fire({
                            title: 'موفق!',
                            text: response.data.message || 'وضعیت به "در حال پیگیری" تغییر یافت.',
                            icon: 'success',
                            confirmButtonText: 'باشه'
                        }).then(() => location.reload());
                    })
                    .fail(function(xhr) {
                        Swal.fire({
                            title: 'خطا!',
                            text: xhr.responseJSON?.data?.message || 'خطایی رخ داد.',
                            icon: 'error',
                            confirmButtonText: 'باشه'
                        });
                    });
            }
        });
    });

    // 2. ثبت جلسه حضوری
    $(document).on('click', '.installment-status-btn[data-action="schedule_meeting"]', function() {
        Swal.fire({
            title: 'ثبت جلسه حضوری',
            html: `
                <div class="text-start">
                    <label class="form-label">تاریخ جلسه:</label>
                    <input type="text" id="swal-meeting-date" class="form-control mb-3 maneli-datepicker" placeholder="انتخاب تاریخ">
                    
                    <label class="form-label">ساعت جلسه:</label>
                    <input type="time" id="swal-meeting-time" class="form-control">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'ثبت جلسه',
            cancelButtonText: 'لغو',
            confirmButtonColor: '#28a745',
            didOpen: () => {
                // راه‌اندازی تاریخ‌یار فارسی
                if (typeof $.fn.persianDatepicker !== 'undefined') {
                    $('#swal-meeting-date').persianDatepicker({
                        format: 'YYYY/MM/DD',
                        initialValue: false,
                        autoClose: true
                    });
                }
            },
            preConfirm: () => {
                const meetingDate = $('#swal-meeting-date').val();
                const meetingTime = $('#swal-meeting-time').val();
                
                if (!meetingDate || !meetingTime) {
                    Swal.showValidationMessage('لطفاً تاریخ و ساعت جلسه را وارد کنید');
                    return false;
                }
                
                return { meeting_date: meetingDate, meeting_time: meetingTime };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                updateInquiryStatus('schedule_meeting', result.value)
                    .done(function(response) {
                        Swal.fire({
                            title: 'موفق!',
                            text: 'جلسه حضوری با موفقیت ثبت شد.',
                            icon: 'success',
                            confirmButtonText: 'باشه'
                        }).then(() => location.reload());
                    })
                    .fail(function(xhr) {
                        Swal.fire({
                            title: 'خطا!',
                            text: xhr.responseJSON?.data?.message || 'خطایی رخ داد.',
                            icon: 'error',
                            confirmButtonText: 'باشه'
                        });
                    });
            }
        });
    });

    // 3. ثبت پیگیری بعدی
    $(document).on('click', '.installment-status-btn[data-action="schedule_followup"]', function() {
        Swal.fire({
            title: 'ثبت پیگیری بعدی',
            html: `
                <div class="text-start">
                    <label class="form-label">تاریخ پیگیری بعدی:</label>
                    <input type="text" id="swal-followup-date" class="form-control mb-3 maneli-datepicker" placeholder="انتخاب تاریخ">
                    
                    <label class="form-label">یادداشت (اختیاری):</label>
                    <textarea id="swal-followup-note" class="form-control" rows="3" placeholder="یادداشت خود را وارد کنید..."></textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'ثبت پیگیری',
            cancelButtonText: 'لغو',
            confirmButtonColor: '#ffc107',
            width: '600px',
            didOpen: () => {
                // راه‌اندازی تاریخ‌یار فارسی
                if (typeof $.fn.persianDatepicker !== 'undefined') {
                    $('#swal-followup-date').persianDatepicker({
                        format: 'YYYY/MM/DD',
                        initialValue: false,
                        autoClose: true
                    });
                }
            },
            preConfirm: () => {
                const followupDate = $('#swal-followup-date').val();
                const followupNote = $('#swal-followup-note').val();
                
                if (!followupDate) {
                    Swal.showValidationMessage('لطفاً تاریخ پیگیری بعدی را وارد کنید');
                    return false;
                }
                
                return { followup_date: followupDate, followup_note: followupNote };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                updateInquiryStatus('schedule_followup', result.value)
                    .done(function(response) {
                        Swal.fire({
                            title: 'موفق!',
                            text: 'پیگیری بعدی با موفقیت ثبت شد.',
                            icon: 'success',
                            confirmButtonText: 'باشه'
                        }).then(() => location.reload());
                    })
                    .fail(function(xhr) {
                        Swal.fire({
                            title: 'خطا!',
                            text: xhr.responseJSON?.data?.message || 'خطایی رخ داد.',
                            icon: 'error',
                            confirmButtonText: 'باشه'
                        });
                    });
            }
        });
    });

    // 4. لغو استعلام
    $(document).on('click', '.installment-status-btn[data-action="cancel"]', function() {
        Swal.fire({
            title: 'لغو استعلام',
            html: `
                <div class="text-start">
                    <label class="form-label">دلیل لغو:</label>
                    <textarea id="swal-cancel-reason" class="form-control" rows="4" placeholder="لطفاً دلیل لغو را وارد کنید..."></textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'لغو استعلام',
            cancelButtonText: 'انصراف',
            confirmButtonColor: '#dc3545',
            width: '600px',
            preConfirm: () => {
                const cancelReason = $('#swal-cancel-reason').val();
                
                if (!cancelReason || cancelReason.trim().length < 10) {
                    Swal.showValidationMessage('لطفاً دلیل لغو را با حداقل 10 کاراکتر وارد کنید');
                    return false;
                }
                
                return { cancel_reason: cancelReason };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                updateInquiryStatus('cancel', result.value)
                    .done(function(response) {
                        Swal.fire({
                            title: 'لغو شد!',
                            text: 'استعلام با موفقیت لغو شد.',
                            icon: 'success',
                            confirmButtonText: 'باشه'
                        }).then(() => location.reload());
                    })
                    .fail(function(xhr) {
                        Swal.fire({
                            title: 'خطا!',
                            text: xhr.responseJSON?.data?.message || 'خطایی رخ داد.',
                            icon: 'error',
                            confirmButtonText: 'باشه'
                        });
                    });
            }
        });
    });

    // 5. تکمیل استعلام
    $(document).on('click', '.installment-status-btn[data-action="complete"]', function() {
        Swal.fire({
            title: 'تکمیل استعلام',
            text: 'آیا مطمئن هستید که استعلام تکمیل شده است؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'بله، تکمیل شد',
            cancelButtonText: 'لغو',
            confirmButtonColor: '#28a745'
        }).then((result) => {
            if (result.isConfirmed) {
                updateInquiryStatus('complete')
                    .done(function(response) {
                        Swal.fire({
                            title: 'موفق!',
                            text: 'استعلام با موفقیت تکمیل شد.',
                            icon: 'success',
                            confirmButtonText: 'باشه'
                        }).then(() => location.reload());
                    })
                    .fail(function(xhr) {
                        Swal.fire({
                            title: 'خطا!',
                            text: xhr.responseJSON?.data?.message || 'خطایی رخ داد.',
                            icon: 'error',
                            confirmButtonText: 'باشه'
                        });
                    });
            }
        });
    });

    // 6. رد استعلام
    $(document).on('click', '.installment-status-btn[data-action="reject"]', function() {
        Swal.fire({
            title: 'رد استعلام',
            html: `
                <div class="text-start">
                    <label class="form-label">دلیل رد:</label>
                    <textarea id="swal-rejection-reason" class="form-control" rows="4" placeholder="لطفاً دلیل رد را وارد کنید..."></textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'رد استعلام',
            cancelButtonText: 'انصراف',
            confirmButtonColor: '#dc3545',
            width: '600px',
            preConfirm: () => {
                const rejectionReason = $('#swal-rejection-reason').val();
                
                if (!rejectionReason || rejectionReason.trim().length < 10) {
                    Swal.showValidationMessage('لطفاً دلیل رد را با حداقل 10 کاراکتر وارد کنید');
                    return false;
                }
                
                return { rejection_reason: rejectionReason };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                updateInquiryStatus('reject', result.value)
                    .done(function(response) {
                        Swal.fire({
                            title: 'رد شد!',
                            text: 'استعلام با موفقیت رد شد.',
                            icon: 'success',
                            confirmButtonText: 'باشه'
                        }).then(() => location.reload());
                    })
                    .fail(function(xhr) {
                        Swal.fire({
                            title: 'خطا!',
                            text: xhr.responseJSON?.data?.message || 'خطایی رخ داد.',
                            icon: 'error',
                            confirmButtonText: 'باشه'
                        });
                    });
            }
        });
    });

    // 7. ذخیره یادداشت کارشناس
    $('#installment-expert-note-form').on('submit', function(e) {
        e.preventDefault();
        
        const note = $('#installment-expert-note-input').val().trim();
        
        if (!note) {
            Swal.fire({
                title: 'توجه!',
                text: 'لطفاً یادداشت خود را وارد کنید.',
                icon: 'warning',
                confirmButtonText: 'باشه'
            });
            return;
        }
        
        $.ajax({
            url: maneliInstallmentReport.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_save_installment_note',
                nonce: maneliInstallmentReport.nonces.save_note,
                inquiry_id: inquiryId,
                note: note
            }
        }).done(function(response) {
            Swal.fire({
                title: 'موفق!',
                text: 'یادداشت با موفقیت ذخیره شد.',
                icon: 'success',
                confirmButtonText: 'باشه',
                timer: 1500
            }).then(() => location.reload());
        }).fail(function(xhr) {
            Swal.fire({
                title: 'خطا!',
                text: xhr.responseJSON?.data?.message || 'خطایی رخ داد.',
                icon: 'error',
                confirmButtonText: 'باشه'
            });
        });
    });

})(jQuery);

