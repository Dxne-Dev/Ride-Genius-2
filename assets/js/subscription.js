$(document).ready(function() {
    // Fonction pour formater les montants
    function formatAmount(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }

    // Fonction pour afficher une notification flottante
    function showNotification(message, type = 'success', title = null) {
        if (typeof showFloatingNotification === 'function') {
            showFloatingNotification(message, type, title);
        } else {
            // Fallback si la fonction n'est pas disponible
            alert(message);
        }
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
            timeout: 5000, // 5 secondes de timeout
            success: function(response) {
                if (response.success && response.subscription) {
                    // Mettre à jour l'interface pour afficher l'abonnement actif
                    updateSubscriptionUI(response.subscription);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Erreur lors de la vérification de l'abonnement:", textStatus, errorThrown);
                // Ne pas afficher de notification d'erreur ici pour éviter de perturber l'utilisateur
            }
        });
    }

    // Fonction pour mettre à jour l'interface utilisateur après une souscription
    function updateSubscriptionUI(subscription) {
        // Cacher la section des abonnements
        $('.subscription-section').hide();
        
        // Afficher les informations de l'abonnement actif
        const subscriptionInfo = $('#subscriptionInfo');
        subscriptionInfo.html(`
            <div class="container">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title">Votre abonnement actif</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Type d'abonnement:</strong> ${subscription.plan_type}</p>
                                <p><strong>Date de début:</strong> ${new Date(subscription.start_date).toLocaleDateString()}</p>
                                <p><strong>Date de fin:</strong> ${new Date(subscription.end_date).toLocaleDateString()}</p>
                                <p><strong>Renouvellement automatique:</strong> ${subscription.auto_renew ? 'Activé' : 'Désactivé'}</p>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-outline-danger me-2" onclick="cancelSubscription(${subscription.id})">
                                        <i class="fas fa-times-circle"></i> Annuler l'abonnement
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="toggleAutoRenew(${subscription.id}, ${subscription.auto_renew ? 0 : 1})">
                                        <i class="fas fa-sync"></i> ${subscription.auto_renew ? 'Désactiver' : 'Activer'} le renouvellement
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
        subscriptionInfo.show();
    }

    // Fonction pour vérifier si l'utilisateur est connecté
    function isLoggedIn() {
        return new Promise((resolve) => {
            $.ajax({
                url: 'api/auth_api.php',
                method: 'POST',
                data: { action: 'checkAuth' },
                success: function(response) {
                    resolve(response.isLoggedIn);
                },
                error: function() {
                    resolve(false);
                }
            });
        });
    }

    // Fonction pour souscrire à un abonnement
    async function subscribeToPlan(planType) {
        // Vérifier si l'utilisateur est connecté
        const loggedIn = await isLoggedIn();
        if (!loggedIn) {
            showNotification('Vous devez être connecté pour souscrire à un abonnement', 'error', 'Erreur');
            return;
        }
        
        // Afficher une notification de chargement
        showNotification('Traitement de votre abonnement en cours...', 'info', 'Chargement');
        
        // Appel AJAX pour souscrire à l'abonnement
        $.ajax({
            url: 'api/subscription_api.php',
            method: 'POST',
            data: {
                action: 'subscribe',
                plan_type: planType
            },
            timeout: 10000, // 10 secondes de timeout
            success: function(response) {
                if (response.success) {
                    // Afficher la notification de succès
                    showNotification(
                        'Votre abonnement ' + planType + ' est maintenant actif jusqu\'au ' + new Date(response.subscription.end_date).toLocaleDateString(), 
                        'success', 
                        'Abonnement confirmé'
                    );
                    
                    // Mettre à jour l'interface utilisateur
                    updateSubscriptionUI(response.subscription);
                } else {
                    // Afficher la notification d'erreur
                    showNotification(
                        response.message || 'Une erreur est survenue lors de la souscription', 
                        'error', 
                        'Erreur de souscription'
                    );
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Erreur AJAX:", textStatus, errorThrown);
                
                // Vérifier si l'erreur est due à un timeout
                if (textStatus === "timeout") {
                    showNotification(
                        'Le serveur met trop de temps à répondre. Veuillez rafraîchir la page pour vérifier si votre abonnement a été créé.', 
                        'warning', 
                        'Délai d\'attente dépassé'
                    );
                } else {
                    showNotification(
                        'Impossible de se connecter au serveur. Veuillez rafraîchir la page pour vérifier si votre abonnement a été créé.', 
                        'error', 
                        'Erreur de connexion'
                    );
                }
                
                // Vérifier si l'abonnement a été créé malgré l'erreur
                setTimeout(function() {
                    checkActiveSubscription();
                }, 2000);
            }
        });
    }

    // Fonction pour annuler un abonnement
    function cancelSubscription(subscriptionId) {
        if (confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?')) {
            // Afficher une notification de chargement
            showNotification('Traitement de l\'annulation en cours...', 'info', 'Chargement');
            
            $.ajax({
                url: 'api/subscription_api.php',
                method: 'POST',
                data: {
                    action: 'cancelSubscription',
                    subscription_id: subscriptionId
                },
                timeout: 10000, // 10 secondes de timeout
                success: function(response) {
                    if (response.success) {
                        showNotification('Votre abonnement a été annulé avec succès', 'success', 'Abonnement annulé');
                        location.reload(); // Recharger la page pour afficher les plans disponibles
                    } else {
                        showNotification(response.message || 'Une erreur est survenue lors de l\'annulation', 'error', 'Erreur');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Erreur AJAX:", textStatus, errorThrown);
                    
                    if (textStatus === "timeout") {
                        showNotification(
                            'Le serveur met trop de temps à répondre. Veuillez rafraîchir la page pour vérifier si votre abonnement a été annulé.', 
                            'warning', 
                            'Délai d\'attente dépassé'
                        );
                    } else {
                        showNotification(
                            'Impossible de se connecter au serveur. Veuillez rafraîchir la page pour vérifier si votre abonnement a été annulé.', 
                            'error', 
                            'Erreur de connexion'
                        );
                    }
                }
            });
        }
    }

    // Fonction pour activer/désactiver le renouvellement automatique
    function toggleAutoRenew(subscriptionId, autoRenew) {
        // Afficher une notification de chargement
        showNotification('Mise à jour des paramètres en cours...', 'info', 'Chargement');
        
        $.ajax({
            url: 'api/subscription_api.php',
            method: 'POST',
            data: {
                action: 'updateAutoRenew',
                subscription_id: subscriptionId,
                auto_renew: autoRenew
            },
            timeout: 10000, // 10 secondes de timeout
            success: function(response) {
                if (response.success) {
                    showNotification(
                        'Le renouvellement automatique a été ' + (autoRenew ? 'activé' : 'désactivé'), 
                        'success', 
                        'Paramètres mis à jour'
                    );
                    location.reload(); // Recharger la page pour mettre à jour l'interface
                } else {
                    showNotification(response.message || 'Une erreur est survenue', 'error', 'Erreur');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Erreur AJAX:", textStatus, errorThrown);
                
                if (textStatus === "timeout") {
                    showNotification(
                        'Le serveur met trop de temps à répondre. Veuillez rafraîchir la page pour vérifier si vos paramètres ont été mis à jour.', 
                        'warning', 
                        'Délai d\'attente dépassé'
                    );
                } else {
                    showNotification(
                        'Impossible de se connecter au serveur. Veuillez rafraîchir la page pour vérifier si vos paramètres ont été mis à jour.', 
                        'error', 
                        'Erreur de connexion'
                    );
                }
            }
        });
    }

    // Initialisation des gestionnaires d'événements
    $('.subscribe-btn').on('click', function(e) {
        e.preventDefault();
        const planType = $(this).data('plan');
        subscribeToPlan(planType);
    });

    // Vérifier l'abonnement actif au chargement de la page
    checkActiveSubscription();
}); 