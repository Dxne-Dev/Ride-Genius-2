/**
 * Dropdown Fix pour RideGenius
 * Ce script s'assure que les menus déroulants Bootstrap fonctionnent correctement
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser tous les menus déroulants Bootstrap
    const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.forEach(function(dropdownToggleEl) {
        // Vérifier si l'élément existe
        if (dropdownToggleEl) {
            try {
                // Créer une instance de dropdown Bootstrap
                new bootstrap.Dropdown(dropdownToggleEl);
            } catch (error) {
                console.error('Erreur lors de l\'initialisation du dropdown:', error);
            }
        }
    });

    // Ajouter un gestionnaire d'événements spécifique pour le menu utilisateur
    const userDropdown = document.getElementById('userDropdown');
    if (userDropdown) {
        userDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = bootstrap.Dropdown.getInstance(userDropdown) || new bootstrap.Dropdown(userDropdown);
            dropdown.toggle();
        });
    }
});
