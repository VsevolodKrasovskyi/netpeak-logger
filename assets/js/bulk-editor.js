document.addEventListener('DOMContentLoaded', function () {
    setupBulkAction();
    setupLogActionButtons('archive-log', 'archive', 'Log archived successfully.');
    setupLogActionButtons('unarchive-log', 'unarchive', 'Log unarchived successfully.');
});



/**
 * Initializes bulk action functionality for log entries.
 *
 * Sets up event listeners for the "Select All" checkbox to toggle all log checkboxes,
 * and for the "Apply Bulk Action" button to perform the selected bulk action on checked logs.
 * Validates that a bulk action is selected and logs are checked before proceeding.
 * Displays alerts for missing selections and a confirmation prompt before sending the request.
 */

function setupBulkAction() {
    const selectAllCheckbox = document.querySelector('#select-all-logs');
    const logCheckboxes = document.querySelectorAll('.select-log');
    const bulkActionSelector = document.querySelector('#bulk-action-selector');
    const applyBulkActionButton = document.querySelector('#apply-bulk-action');
    const form = document.querySelector('#bulk-action-form');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            logCheckboxes.forEach(checkbox => checkbox.checked = selectAllCheckbox.checked);
        });
    }

    if (applyBulkActionButton) {
        applyBulkActionButton.addEventListener('click', function (event) {
            event.preventDefault();

            const action = bulkActionSelector.value;
            if (!action) {
                alert('Please select a bulk action.');
                return;
            }

            const selectedLogs = Array.from(logCheckboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => ({
                    id: checkbox.value,
                    type: checkbox.dataset.logType
                }));

            if (selectedLogs.length === 0) {
                alert(WP.logsActionAlert);
                return;
            }

            if (!confirm(WP.logsActionConfirm)) {
                return;
            }

            sendBulkEditLogsRequest(form, action, selectedLogs, 'Bulk action completed successfully.');
        });
    }
}


/**
 * Sets up log action buttons to handle click events for specific actions.
 *
 * @param {string} buttonClass - The CSS class of the buttons to attach the event listeners to.
 * @param {string} action - The action to perform when a button is clicked (e.g., 'archive', 'unarchive').

 * @param {string} successMessage - The message to display upon successful completion of the action.
 */

function setupLogActionButtons(buttonClass, action, successMessage) {
    document.querySelectorAll(`.${buttonClass}`).forEach(button => {
        button.addEventListener('click', function () {
            const logId = this.dataset.logId;
            const logType = this.dataset.logType;

            if (!confirm(WP.logsActionConfirm || 'Are you sure you want to proceed?')) {
                return;
            }

            sendBulkEditLogsRequest(null, action, [{ id: logId, type: logType }], successMessage);
        });
    });
}


/**
 * Sends a bulk edit logs request to the server.
 *
 * @param {HTMLFormElement|null} form The form element to append the logs to, or null if no form is needed.
 * @param {string} action The bulk action to perform (e.g. archive or unarchive).
 * @param {Object[]} logs The logs to bulk edit. Each log is an object with an 'id' and 'type' property.
 * @param {string} successMessage The success message to display if the request is successful.
 */
function sendBulkEditLogsRequest(form, action, logs, successMessage) {
    const formData = new FormData();

    if (form) {
        const logsInput = document.createElement('input');
        logsInput.type = 'hidden';
        logsInput.name = 'logs';
        logsInput.value = JSON.stringify(logs);
        form.appendChild(logsInput);
    }

    formData.append('action', 'bulk_edit_logs');
    formData.append('bulk_action', action);
    formData.append('logs', JSON.stringify(logs));

    fetch(WP.ajaxurl, {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(successMessage || WP.logsActionSuccess);
            window.location.reload();
        } else {
            alert('An error occurred: ' + (data.message || 'Unknown error.'));
        }
    })
    .catch(() => {
        alert('An unexpected error occurred. Please try again.');
    });
}
