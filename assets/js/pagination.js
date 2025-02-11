document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('#pagination-form');
    const pageField = document.querySelector('#pagination-page');
    const recordsPerPageDropdown = document.querySelector('#records-per-page');
    const prevPageButton = document.querySelector('#prev-page');
    const nextPageButton = document.querySelector('#next-page');
    const totalPages = parseInt(form.dataset.totalPages, 10); 

    let currentPage = parseInt(pageField.value, 10);

    recordsPerPageDropdown.addEventListener('change', function () {
        pageField.value = 1;  
        form.submit();
    });

    prevPageButton.addEventListener('click', function () {
        if (currentPage > 1) {
            currentPage--;
            pageField.value = currentPage;
            form.submit();
        }
    });

    nextPageButton.addEventListener('click', function () {
        if (currentPage < totalPages) {
            currentPage++;
            pageField.value = currentPage;
            form.submit();
        }
    });
    updatePaginationInfo();

    function updatePaginationInfo() {
        document.querySelector('#current-page-info').textContent = `Page ${currentPage} of ${totalPages}`;
        prevPageButton.disabled = currentPage <= 1;
        nextPageButton.disabled = currentPage >= totalPages;
    }
});
