/**
 * Theme Toggle Functionality
 * Controls light/dark mode switching for RideGenius
 */

// Theme Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const html = document.documentElement;
    
    // Function to show theme notification
    function showThemeNotification(isDark) {
        const notification = document.createElement('div');
        notification.className = 'theme-notification';
        notification.textContent = isDark ? 'Dark Mode Enabled' : 'Light Mode Enabled';
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }
    
    // Function to apply dark mode
    function applyDarkMode() {
        document.body.classList.add('dark-mode');
        html.classList.add('theme-dark');
        html.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
        showThemeNotification(true);
    }
    
    // Function to apply light mode
    function applyLightMode() {
        document.body.classList.remove('dark-mode');
        html.classList.remove('theme-dark');
        html.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
        showThemeNotification(false);
    }
    
    // Check for saved theme preference or use system preference
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        applyDarkMode();
    } else {
        applyLightMode();
    }
    
    // Toggle theme on button click
    themeToggle.addEventListener('click', function() {
        if (document.body.classList.contains('dark-mode')) {
            applyLightMode();
        } else {
            applyDarkMode();
        }
    });
    
    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (!localStorage.getItem('theme')) {
            if (e.matches) {
                applyDarkMode();
            } else {
                applyLightMode();
            }
        }
    });
}); 