jQuery(document).ready(function ($) {
    $('.edit-commit').on('click', function (e) {
        e.preventDefault();

        const editId = $(this).data('edit-id');

        $.ajax({
            url: ajaxurl,
            method: 'GET',
            data: {
                action: 'render_edit_form',
                edit_id: editId,
            },
            success: function (response) {
                if (response.success) {
                    const popupContainer = $('#edit-form-container');
                    popupContainer.html(response.data.html); 
                    popupContainer.css('display', 'block'); 

                }
            },
            error: function (xhr) {
                console.error('AJAX Error:', xhr.responseText);
            },
        });
    });

    // Закрытие попапа при клике на область или кнопку закрытия
    $(document).on('click', '.netpeak-popup-close, #edit-form-container', function (e) {
        if (e.target === this) {
            $('#edit-form-container').css('display', 'none');
        }
    });
});
