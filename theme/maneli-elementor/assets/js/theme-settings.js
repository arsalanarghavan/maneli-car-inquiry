jQuery(document).ready(function($) {
    'use strict';

    // Initialize color pickers
    $('.color-picker').wpColorPicker();

    // Save individual setting
    $(document).on('change', '.theme-option', function() {
        var option = $(this).data('option');
        var value = $(this).val();

        if ($(this).is(':checkbox')) {
            value = $(this).is(':checked') ? '1' : '0';
        }

        $.ajax({
            url: maneli_theme_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_save_theme_option',
                option: option,
                value: value,
                nonce: maneli_theme_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', maneli_theme_vars.i18n.save_success);
                } else {
                    showNotice('error', response.data.message || maneli_theme_vars.i18n.save_error);
                }
            },
            error: function() {
                showNotice('error', maneli_theme_vars.i18n.save_error);
            }
        });
    });

    // Save all settings
    $(document).on('click', '.save-theme-settings', function() {
        $('.theme-option').each(function() {
            $(this).trigger('change');
        });
    });

    // Reset settings
    $(document).on('click', '.reset-theme-settings', function() {
        if (!confirm(maneli_theme_vars.i18n.reset_confirm)) {
            return;
        }

        $.ajax({
            url: maneli_theme_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_reset_theme_options',
                nonce: maneli_theme_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', maneli_theme_vars.i18n.reset_success);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice('error', response.data.message);
                }
            }
        });
    });

    // Logo upload
    var file_frame;

    $(document).on('click', '.upload-logo-btn', function(e) {
        e.preventDefault();

        if (file_frame) {
            file_frame.open();
            return;
        }

        file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select Logo',
            button: { text: 'Use This Image' },
            multiple: false
        });

        file_frame.on('select', function() {
            var attachment = file_frame.state().get('selection').first().toJSON();
            $('#site_logo').val(attachment.id);
            $('#logo-preview').attr('src', attachment.url).show();
            $('#site_logo').trigger('change');
        });

        file_frame.open();
    });

    $(document).on('click', '.remove-logo-btn', function(e) {
        e.preventDefault();
        $('#site_logo').val('');
        $('#logo-preview').hide();
        $('#site_logo').trigger('change');
    });

    // Show notice
    function showNotice(type, message) {
        var notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap').prepend(notice);

        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
});
