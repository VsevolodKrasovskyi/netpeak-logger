function showPopupMessage(element) {
    const fullMessage = element.getAttribute('data-full-message');
    
    let popupContainer = document.querySelector('#popup-message-container');
    if (!popupContainer) {
        popupContainer = document.createElement('div');
        popupContainer.id = 'popup-message-container';
        popupContainer.style.position = 'fixed';
        popupContainer.style.top = '0';
        popupContainer.style.left = '0';
        popupContainer.style.width = '100%';
        popupContainer.style.height = '100%';
        popupContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        popupContainer.style.display = 'flex';
        popupContainer.style.justifyContent = 'center';
        popupContainer.style.alignItems = 'center';
        popupContainer.style.zIndex = '1000';

        const popupContent = document.createElement('div');
        popupContent.style.backgroundColor = '#fff';
        popupContent.style.padding = '20px';
        popupContent.style.borderRadius = '8px';
        popupContent.style.maxWidth = '400px';
        popupContent.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.2)';
        popupContent.style.textAlign = 'center';
        popupContent.style.position = 'relative';

        const messageText = document.createElement('p');
        messageText.textContent = fullMessage;

        const closeButton = document.createElement('button');
        closeButton.textContent = 'Close';
        closeButton.style.position = 'absolute';
        closeButton.style.top = '10px';
        closeButton.style.right = '10px';
        closeButton.style.background = 'none';
        closeButton.style.border = 'none';
        closeButton.style.fontSize = '16px';
        closeButton.style.cursor = 'pointer';
        closeButton.onclick = () => {
            popupContainer.style.display = 'none';
        };

        popupContent.appendChild(messageText);
        popupContent.appendChild(closeButton);
        popupContainer.appendChild(popupContent);
        document.body.appendChild(popupContainer);
    }

    popupContainer.querySelector('p').textContent = fullMessage;
    popupContainer.style.display = 'flex';
}
