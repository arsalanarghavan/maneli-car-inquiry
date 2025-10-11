jQuery(document).ready(function($) {
    'use strict';

    // --- Assign Expert Button (Works for both list and single page) ---
    $(document.body).on('click', '.assign-expert-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        const inquiryType = button.data('inquiry-type');
        
        const expertFilterId = (inquiryType === 'cash') ? '#cash-expert-filter' : '#expert-filter';
        const expertOptions = $(expertFilterId).clone().prop('id', 'swal-expert-filter').prepend('<option value="auto">-- انتساب خودکار (گردشی) --</option>').val('auto').get(0).outerHTML;

        let ajaxAction, nonce;
        if (inquiryType === 'cash') {
            ajaxAction = 'maneli_assign_expert_to_cash_inquiry';
            nonce = maneli_inquiry_ajax.cash_assign_expert_nonce;
        } else {
            ajaxAction = 'maneli_assign_expert_to_inquiry';
            nonce = maneli_inquiry_ajax.assign_nonce;
        }

        Swal.fire({
            title: `ارجاع درخواست #${inquiryId}`,
            html: `
                <div style="text-align: right; font-family: inherit;">
                    <label for="swal-expert-filter" style="display: block; margin-bottom: 10px;">یک کارشناس را برای این درخواست انتخاب کنید:</label>
                    ${expertOptions}
                </div>
            `,
            confirmButtonText: 'ارجاع بده',
            showCancelButton: true,
            cancelButtonText: 'انصراف',
            didOpen: () => {
                 $('#swal-expert-filter').select2({
                     placeholder: "انتخاب کارشناس",
                     allowClear: false,
                     width: '100%'
                });
            },
            preConfirm: () => {
                return $('#swal-expert-filter').val();
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                button.prop('disabled', true).text('...');
                $.post(maneli_inquiry_ajax.ajax_url, {
                    action: ajaxAction,
                    nonce: nonce,
                    inquiry_id: inquiryId,
                    expert_id: result.value
                }, function(response) {
                    if (response.success) {
                        Swal.fire('موفق', response.data.message, 'success').then(() => {
                           if (button.closest('#cash-inquiry-details').length > 0) {
                               location.reload();
                           } else {
                               button.closest('td').text(response.data.expert_name);
                               if(response.data.new_status_label) {
                                   button.closest('tr').find('td[data-title="وضعیت"]').text(response.data.new_status_label);
                               }
                           }
                        });
                    } else {
                        Swal.fire('خطا', response.data.message, 'error');
                        button.prop('disabled', false).text('ارجاع');
                    }
                });
            }
        });
    });
});