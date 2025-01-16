jQuery(document).ready(function ($) {
    $('.settings-tab').on('click', function (e) {
        e.preventDefault();
        $('.settings-tab').removeClass('settings-tab-active');
        $(this).addClass('settings-tab-active');
        var settings = $(this).data('settings');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'switch_settings_tab',
                settings: settings
            },
            beforeSend: function () {
                $('#loader').show;
                $('.settings-content').html('');
            },
            success: function (response) {
                $('#loader').hide();
                $('.settings-content').html(response); 
            },
            error: function () {
                $('#loader').hide();
                $('.settings-content').html('<p>Error loading content.</p>'); 
            }
        });
    });
});
