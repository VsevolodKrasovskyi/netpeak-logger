// Tooltip positioning
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('.tooltip');
    tooltips.forEach(function(tooltip) {
        const content = tooltip.querySelector('.tooltip-content');
        tooltip.addEventListener('mouseenter', function() {
            const bounding = tooltip.getBoundingClientRect();
            const windowWidth = window.innerWidth;
            if (bounding.right > windowWidth / 2) {
                content.style.transform = 'translateX(-100%)';
            } else {
                content.style.transform = 'translateX(0)';
            }
            content.style.display = 'block';
        });
        tooltip.addEventListener('mouseleave', function() {
            content.style.display = 'none';
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    show_image_full();
});

function show_image_full() {
    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('tooltip-image')) {
            const image = event.target;
            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            overlay.style.zIndex = '9999';
            overlay.style.display = 'flex';
            overlay.style.justifyContent = 'center';
            overlay.style.alignItems = 'center';

            const popupImage = document.createElement('img');
            popupImage.src = image.src;
            popupImage.style.maxWidth = '90%';
            popupImage.style.maxHeight = '90%';
            popupImage.style.objectFit = 'contain';

            overlay.appendChild(popupImage);
            document.body.appendChild(overlay);

            overlay.addEventListener('click', function () {
                overlay.remove();
            });
        }
    });
}
