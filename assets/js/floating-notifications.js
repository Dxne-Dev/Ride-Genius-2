/**
 * Système de notifications flottantes
 * Affiche des notifications sous forme de badges/bulles flottantes dans le coin supérieur droit
 */

// Créer le conteneur de notifications s'il n'existe pas
function createNotificationContainer() {
  let container = document.getElementById('floating-notifications');
  if (!container) {
    container = document.createElement('div');
    container.id = 'floating-notifications';
    container.className = 'floating-notifications-container';
    document.body.appendChild(container);
  }
  return container;
}

/**
 * Affiche une notification flottante
 * @param {string} message - Le message à afficher
 * @param {string} type - Le type de notification (success, error, warning, info)
 * @param {string} title - Le titre de la notification (optionnel)
 * @param {number} duration - Durée d'affichage en ms (0 pour ne pas fermer automatiquement)
 * @returns {HTMLElement} - L'élément de notification créé
 */
function showFloatingNotification(message, type = 'success', title = null, duration = 5000) {
  // Créer le conteneur de notifications
  const container = createNotificationContainer();
  
  // Créer l'élément de notification
  const notification = document.createElement('div');
  notification.className = `floating-notification ${type}`;
  
  // Icône selon le type
  let iconClass = 'fas fa-info-circle';
  if (type === 'success') iconClass = 'fas fa-check-circle';
  if (type === 'error') iconClass = 'fas fa-exclamation-circle';
  if (type === 'warning') iconClass = 'fas fa-exclamation-triangle';
  
  // Contenu de la notification
  notification.innerHTML = `
    <div class="floating-notification-icon">
      <i class="${iconClass}"></i>
    </div>
    <div class="floating-notification-content">
      ${title ? `<div class="floating-notification-title">${title}</div>` : ''}
      <div class="floating-notification-message">${message}</div>
    </div>
    <button class="floating-notification-close" aria-label="Fermer">
      <i class="fas fa-times"></i>
    </button>
  `;
  
  // Ajouter la notification au conteneur
  container.appendChild(notification);
  
  // Gérer la fermeture manuelle
  const closeButton = notification.querySelector('.floating-notification-close');
  closeButton.addEventListener('click', () => {
    hideNotification(notification);
  });
  
  // Fermeture automatique après la durée spécifiée
  if (duration > 0) {
    setTimeout(() => {
      hideNotification(notification);
    }, duration);
  }
  
  return notification;
}

/**
 * Cache une notification
 * @param {HTMLElement} notification - L'élément de notification à cacher
 */
function hideNotification(notification) {
  notification.classList.add('hiding');
  setTimeout(() => {
    if (notification.parentNode) {
      notification.parentNode.removeChild(notification);
    }
  }, 300);
}

// Exporter les fonctions pour les utiliser dans d'autres fichiers
window.showFloatingNotification = showFloatingNotification;
window.hideNotification = hideNotification; 