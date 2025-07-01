const toggle = document.getElementById('fullscreenToggle');

if(toggle) {
    toggle.addEventListener('click', () => {
        const userDashboardPanel = document.getElementById('user-dashboard-panel');
        if(userDashboardPanel) {
            if(userDashboardPanel.classList.contains('user-dashboard-panel--fullscreen')) {
                userDashboardPanel.classList.remove('user-dashboard-panel--fullscreen');
            } else {
                userDashboardPanel.classList.add('user-dashboard-panel--fullscreen');
            }
        }
    });
}

