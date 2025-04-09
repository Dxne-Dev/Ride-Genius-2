document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        // Set initial state based on localStorage or system preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            themeToggle.checked = true;
            document.body.classList.add('dark-mode');
        }
        
        // Add event listener for the toggle
        themeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            }
        });

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem('theme')) {
                themeToggle.checked = e.matches;
                document.body.classList.toggle('dark-mode', e.matches);
            }
        });
    }
}); 