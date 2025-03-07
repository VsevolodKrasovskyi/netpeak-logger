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

//Clear empty query string 
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".netpeak-filter-form");

    if (!form) {
        return;
    }

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        const formData = new FormData(form);
        const params = new URLSearchParams();

        formData.forEach((value, key) => {
            if (value.trim() !== "") { 
                params.append(key, value);
            }
        });

        const actionUrl = form.getAttribute("action") || window.location.pathname;
        const newUrl = actionUrl + (params.toString() ? "?" + params.toString() : "");

        window.location.href = newUrl;
    });
});

//Clear filters
document.addEventListener("DOMContentLoaded", function () {
    const clearButton = document.getElementById("clear-filters");

    if (clearButton) {
        clearButton.addEventListener("click", function () {
            const newUrl = window.location.pathname + "?page=netpeak-logs&tab=logs";
            window.history.replaceState(null, "", newUrl);
            window.location.href = newUrl;
        });
    }
});


