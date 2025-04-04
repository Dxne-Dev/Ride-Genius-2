$(document).ready(function() {
    // Fonction pour formater les montants
    function formatAmount(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }

    // Fonction pour afficher les notifications
    function showNotification(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        const container = document.getElementById('toastContainer') || createToastContainer();
        container.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Supprimer le toast après qu'il soit caché
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }

    // Vérifier si l'utilisateur a un abonnement actif
    function checkActiveSubscription() {
        $.ajax({
            url: 'api/subscription_api.php',
            method: 'POST',
            data: { action: 'getActiveSubscription' },
            success: function(response) {
                if (response.success && response.subscription) {
                    // Mettre à jour l'interface pour afficher l'abonnement actif
                    updateSubscriptionUI(response.subscription);
                }
            }
        });
    }

    // Mettre à jour l'interface en fonction de l'abonnement actif
    function updateSubscriptionUI(subscription) {
        const subscriptionInfo = document.getElementById('subscriptionInfo');
        const subscriptionSection = document.querySelector('.subscription-section');
        
        if (subscription) {
            // Masquer la section des abonnements
            if (subscriptionSection) {
                subscriptionSection.style.display = 'none';
            }
            
            // Afficher les informations de l'abonnement actif
            subscriptionInfo.innerHTML = `
                <div class="alert alert-success">
                    <h4 class="alert-heading">Abonnement actif : ${subscription.plan_type.toUpperCase()}</h4>
                    <p>Date de début : ${new Date(subscription.start_date).toLocaleDateString()}</p>
                    <p>Date de fin : ${new Date(subscription.end_date).toLocaleDateString()}</p>
                    <p>Renouvellement automatique : ${subscription.auto_renew ? 'Activé' : 'Désactivé'}</p>
                    <hr>
                    <button class="btn btn-danger" onclick="cancelSubscription()">Annuler l'abonnement</button>
                    <button class="btn btn-secondary" onclick="toggleAutoRenew()">
                        ${subscription.auto_renew ? 'Désactiver' : 'Activer'} le renouvellement automatique
                    </button>
                </div>
            `;
        } else {
            // Afficher la section des abonnements
            if (subscriptionSection) {
                subscriptionSection.style.display = 'block';
            }
            
            // Masquer les informations d'abonnement
            subscriptionInfo.innerHTML = '';
        }
    }

    // Annuler un abonnement
    function cancelSubscription() {
        $.ajax({
            url: 'api/subscription_api.php',
            method: 'POST',
            data: { action: 'cancelSubscription' },
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    // Recharger la page pour mettre à jour l'interface
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('Erreur lors de la communication avec le serveur', 'error');
            }
        });
    }

    // Souscrire à un abonnement
    function subscribe(planType) {
        if (!isUserLoggedIn()) {
            showNotification('Veuillez vous connecter pour souscrire à un abonnement', 'warning');
            return;
        }
        
        $.ajax({
            url: 'api/subscription_api.php',
            method: 'POST',
            data: {
                action: 'subscribe',
                plan_type: planType
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Abonnement souscrit avec succès !', 'success');
                    checkActiveSubscription();
                } else {
                    showNotification(response.message || 'Erreur lors de la souscription', 'error');
                }
            },
            error: function() {
                showNotification('Erreur lors de la communication avec le serveur', 'error');
            }
        });
    }

    // Gestionnaires pour les boutons d'abonnement
    $('.subscribe-btn').on('click', function(e) {
        e.preventDefault();
        const planType = $(this).data('plan');
        
        // Confirmer la souscription
        if (confirm('Êtes-vous sûr de vouloir souscrire à cet abonnement ?')) {
            subscribe(planType);
        }
    });

    // Vérifier si l'utilisateur est connecté
    function isUserLoggedIn() {
        // Cette fonction devrait être adaptée à votre système d'authentification
        // Pour l'instant, nous supposons que l'utilisateur est connecté si le bouton de connexion n'est pas visible
        return $('.login-btn').length === 0;
    }

    // Vérifier l'abonnement actif au chargement de la page
    checkActiveSubscription();
}); 