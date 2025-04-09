document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const themeToggleMobile = document.getElementById('theme-toggle-mobile');
    
    function initializeThemeToggle(toggle) {
        if (toggle) {
            // Set initial state based on localStorage or system preference
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                toggle.checked = true;
                document.body.classList.add('dark-mode');
            }
        }
    }

    function handleThemeChange(isChecked) {
        if (isChecked) {
            document.body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
        } else {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
        }
        
        // Sync the other toggle
        if (themeToggle) themeToggle.checked = isChecked;
        if (themeToggleMobile) themeToggleMobile.checked = isChecked;
    }

    // Initialize both toggles
    initializeThemeToggle(themeToggle);
    initializeThemeToggle(themeToggleMobile);

    // Add event listeners for both toggles
    if (themeToggle) {
        themeToggle.addEventListener('change', function() {
            handleThemeChange(this.checked);
        });
    }

    if (themeToggleMobile) {
        themeToggleMobile.addEventListener('change', function() {
            handleThemeChange(this.checked);
        });
    }

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (!localStorage.getItem('theme')) {
            const isChecked = e.matches;
            handleThemeChange(isChecked);
        }
    });
}); 