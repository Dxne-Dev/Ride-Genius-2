// Script pour faire disparaître les alertes automatiquement après 6 secondes
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner toutes les alertes
    const alerts = document.querySelectorAll('.alert');
    
    // Pour chaque alerte, configurer un timer pour la faire disparaître
    alerts.forEach(function(alert) {
        setTimeout(function() {
            // Créer un objet bootstrap alert et le cacher
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 6000); // 6000 ms = 6 secondes
    });
});
