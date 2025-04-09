document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const themeToggleMobile = document.getElementById('theme-toggle-mobile');
    const body = document.body;

    // Fonction pour mettre à jour l'état du thème
    function updateThemeState(isDark) {
        // Vérifier si body existe
        if (!body) {
            console.warn('Body element not found');
            return;
        }

        try {
            // Mettre à jour la classe du body
            body.classList.toggle('dark-mode', isDark);
            
            // Mettre à jour les deux boutons s'ils existent
            if (themeToggle) {
                themeToggle.checked = isDark;
            }
            if (themeToggleMobile) {
                themeToggleMobile.checked = isDark;
            }
            
            // Sauvegarder la préférence
            localStorage.setItem('theme', isDark ? 'dark' : 'light');

            // Mettre à jour l'attribut data-theme sur html
            const html = document.documentElement;
            if (html) {
                html.setAttribute('data-theme', isDark ? 'dark' : 'light');
            }
        } catch (error) {
            console.error('Error updating theme state:', error);
        }
    }

    // Initialiser l'état du thème
    function initializeTheme() {
        try {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = savedTheme === 'dark' || (!savedTheme && prefersDark);
            
            updateThemeState(isDark);
        } catch (error) {
            console.error('Error initializing theme:', error);
        }
    }

    // Ajouter les écouteurs d'événements pour les deux boutons
    function setupThemeToggle(toggle) {
        if (!toggle) {
            return;
        }

        try {
            toggle.addEventListener('change', function(e) {
                e.preventDefault();
                updateThemeState(this.checked);
            });
        } catch (error) {
            console.error('Error setting up theme toggle:', error);
        }
    }

    // Initialiser les deux boutons
    setupThemeToggle(themeToggle);
    setupThemeToggle(themeToggleMobile);

    // Initialiser le thème
    initializeTheme();

    // Écouter les changements de préférence système
    try {
        const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const mediaQueryCallback = function(e) {
            if (!localStorage.getItem('theme')) {
                updateThemeState(e.matches);
            }
        };

        if (darkModeMediaQuery.addEventListener) {
            darkModeMediaQuery.addEventListener('change', mediaQueryCallback);
        } else if (darkModeMediaQuery.addListener) {
            // Fallback pour les navigateurs plus anciens
            darkModeMediaQuery.addListener(mediaQueryCallback);
        }
    } catch (error) {
        console.error('Error setting up media query listener:', error);
    }
}); 