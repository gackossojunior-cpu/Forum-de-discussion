const permissionButton = document.querySelector('[data-browser-notification-permission]');

if (permissionButton) {
    const label = permissionButton.querySelector('[data-browser-notification-label]');
    updatePermissionButton();
    permissionButton.addEventListener('click', async () => {
        if (!('Notification' in window)) {
            showPermissionMessage('Les alertes navigateur ne sont pas prises en charge sur cet appareil.', true);
            return;
        }

        try {
            const permission = await Notification.requestPermission();
            updatePermissionButton();
            if (permission === 'granted') {
                showPermissionMessage('Les alertes navigateur sont activées.');
            } else if (permission === 'denied') {
                showPermissionMessage('Les alertes sont bloquées dans les réglages du navigateur.', true);
            }
        } catch (error) {
            showPermissionMessage(error.message || 'Impossible d’activer les alertes navigateur.', true);
        }
    });

    function updatePermissionButton() {
        if (!('Notification' in window)) {
            label.textContent = 'Alertes navigateur indisponibles';
            permissionButton.disabled = true;
            return;
        }
        if (Notification.permission === 'granted') {
            label.textContent = 'Alertes navigateur activées';
            permissionButton.setAttribute('aria-pressed', 'true');
        } else if (Notification.permission === 'denied') {
            label.textContent = 'Alertes bloquées dans le navigateur';
            permissionButton.disabled = true;
        } else {
            label.textContent = 'Autoriser les alertes navigateur';
        }
    }

    function showPermissionMessage(message, isError = false) {
        const feedback = document.querySelector('[data-notification-feedback]');
        if (!feedback) return;
        feedback.textContent = message;
        feedback.className = `notification-feedback${isError ? ' is-error' : ''}`;
        feedback.hidden = false;
    }
}
