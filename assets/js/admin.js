jQuery(document).ready(function ($) {
    $('#netpeak-commit-form').on('submit', function (e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.post(ajaxurl, formData, function (response) {
            if (response.success) {
                window.location.href = response.data.redirect_url;
            } else {
                alert(response.data.message || 'An error occurred');
            }
        }).fail(function () {
            alert('AJAX request failed.');
        });
    });
});



