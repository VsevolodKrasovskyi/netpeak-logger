document.addEventListener('DOMContentLoaded', function () {
    ajax_load_settings_tab();
    updateSettings();
});

function ajax_load_settings_tab() {
    const tabs = document.querySelectorAll('.settings-tab');
    const loader = document.getElementById('loader');
    const settingsContent = document.querySelector('.settings-content');

    tabs.forEach( (tab) => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();

            tabs.forEach( (tab) =>{
                tab.classList.remove('settings-tab-active');
            });

            tab.classList.add('settings-tab-active');

            const settings = tab.getAttribute('data-settings');

            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'switch_settings_tab',
                    settings: settings
                })
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(function (data) {
                loader.style.display = 'none';
                settingsContent.innerHTML = data;
                updateSettings();
            })
            .catch(function () {
                loader.style.display = 'none';
                settingsContent.innerHTML = '<p>Error loading content.</p>';
            });
            loader.style = 'display: block !important; position: absolute; top: 25%; left: 50%;';
            settingsContent.innerHTML = '';
        });
    });
};

//Update Settings
function updateSettings() {
    const form = document.querySelector('#setting-form');
    if (!form) {
        return;
    }
    else {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(form);

        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(server => {
            if (!server.ok) {
                throw new Error(`HTTP error! Status: ${server.status}`);
            }
            return server.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.data.message);
                // window.location.reload();
            } else {
                alert('Error saving settings');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Network error');
        });
    });
    }
};



