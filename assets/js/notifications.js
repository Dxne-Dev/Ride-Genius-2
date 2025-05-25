/**
 * Gestion des notifications en temps réel
 */
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si l'utilisateur est connecté (le dropdown de notifications existe)
    const notificationDropdown = document.getElementById('notification-dropdown');
    if (!notificationDropdown) return;

    const notificationBadge = document.querySelector('.notification-badge');
    const notificationList = document.getElementById('notification-list');
    
    // Fonction pour charger les notifications
    function loadNotifications() {
        fetch('index.php?page=get-unread-notifications')
            .then(response => response.json())
            .then(data => {
                // Mettre à jour le badge
                if (data.count > 0) {
                    notificationBadge.textContent = data.count;
                    notificationBadge.classList.remove('d-none');
                } else {
                    notificationBadge.classList.add('d-none');
                }
                
                // Mettre à jour la liste des notifications
                if (notificationList) {
                    let html = '';
                    
                    if (data.notifications.length === 0) {
                        html = `
                            <li class="text-center py-3">
                                <span class="text-muted">Aucune notification non lue</span>
                            </li>
                        `;
                    } else {
                        data.notifications.forEach(notification => {
                            const date = new Date(notification.created_at);
                            const formattedDate = date.toLocaleDateString('fr-FR') + ' à ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                            
                            let actionButton = '';
                            if (notification.type === 'booking_completed') {
                                actionButton = `
                                    <a href="index.php?page=driver-reviews" class="btn btn-primary btn-sm mt-2">
                                        <i class="fas fa-star me-1"></i> Évaluer
                                    </a>
                                `;
                            }
                            
                            html += `
                                <li class="dropdown-item-text p-2 border-bottom">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0 me-2">
                                            ${notification.type === 'booking_completed' 
                                                ? '<i class="fas fa-check-circle text-success"></i>' 
                                                : '<i class="fas fa-bell text-primary"></i>'}
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1">${notification.message}</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">${formattedDate}</small>
                                                <a href="index.php?page=mark-notification-read&id=${notification.id}&redirect=index.php" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </div>
                                            ${actionButton}
                                        </div>
                                    </div>
                                </li>
                            `;
                        });
                    }
                    
                    notificationList.innerHTML = html;
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des notifications:', error);
            });
    }
    
    // Charger les notifications au chargement de la page
    loadNotifications();
    
    // Rafraîchir les notifications toutes les 60 secondes
    setInterval(loadNotifications, 60000);
    
    // Charger les notifications lorsque le dropdown est ouvert
    notificationDropdown.addEventListener('show.bs.dropdown', loadNotifications);
});
