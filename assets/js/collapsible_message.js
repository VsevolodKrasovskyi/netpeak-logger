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
        popupContent.style.borderRadius = '8px';
        popupContent.style.width = '40%';
        popupContent.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.2)';
        popupContent.style.textAlign = 'left';
        popupContent.style.position = 'relative';

        const popupTitle = document.createElement('h2');
        popupTitle.textContent = 'Message';
        popupTitle.style.backgroundColor = '#00b3e3';
        popupTitle.style.color = '#fff';
        popupTitle.style.fontSize = '20px';
        popupTitle.style.margin = '0';
        popupTitle.style.padding = '15px';
        popupTitle.style.textAlign = 'center';
        popupTitle.style.borderRadius = '8px 8px 0 0';

        const messageText = document.createElement('div');
        messageText.className = 'popup-message-content';
        messageText.style.padding = '20px';

        const styleElement = document.createElement('style');
        styleElement.textContent = `
            .popup-message-content ul {
                padding-left: 20px;
                margin: 10px 0;
                list-style-type: disc;
            }

            .popup-message-content li {
                margin-bottom: 8px;
                font-size: 16px;
                color: #333;
            }

            .popup-message-content a {
                color: #0073aa;
            }

        `;
        document.head.appendChild(styleElement);

        const closeButton = document.createElement('button');
        closeButton.textContent = 'X';
        closeButton.style.position = 'absolute';
        closeButton.style.top = '10px';
        closeButton.style.right = '10px';
        closeButton.style.background = 'none';
        closeButton.style.border = 'none';
        closeButton.style.color = '#fff';
        closeButton.style.fontWeight = '600';
        closeButton.style.fontSize = '20px';
        closeButton.style.cursor = 'pointer';
        closeButton.onclick = () => {
            popupContainer.style.display = 'none';
        };

        popupContent.appendChild(popupTitle);
        popupContent.appendChild(messageText);
        popupContent.appendChild(closeButton);
        popupContainer.appendChild(popupContent);
        document.body.appendChild(popupContainer);
    }

    popupContainer.querySelector('.popup-message-content').innerHTML = fullMessage;
    popupContainer.style.display = 'flex';
}
