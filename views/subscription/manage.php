<?php
// Vérification de la session et du rôle
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

// Vérifier si l'utilisateur est un conducteur
if ($_SESSION['user_role'] !== 'conducteur') {
    $_SESSION['error'] = "Cette page n'est accessible qu'aux conducteurs";
    header('Location: index.php');
    exit();
}

// Initialisation de la connexion à la base de données
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Récupération des informations de l'utilisateur
require_once __DIR__ . '/../../models/User.php';
$user = new User($db);
$userData = $user->findById($_SESSION['user_id']);

// Récupération des informations de l'abonnement
require_once __DIR__ . '/../../models/Subscription.php';
$subscription = new Subscription($db);
$activeSubscription = $subscription->getActiveSubscription($_SESSION['user_id']);

// Récupération des informations du wallet
require_once __DIR__ . '/../../models/Wallet.php';
$wallet = new Wallet($db);
$balance = $wallet->getBalance($_SESSION['user_id']);

// Inclusion du header et de la navbar
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="container mt-5 pt-5">
    <div class="row">
        <div class="col-md-3">
            <!-- Menu latéral du profil -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Mon compte</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="index.php?page=profile" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Mon profil
                    </a>
                    <a href="index.php?page=my-rides" class="list-group-item list-group-item-action">
                        <i class="fas fa-car me-2"></i> Mes trajets
                    </a>
                    <a href="index.php?page=my-bookings" class="list-group-item list-group-item-action">
                        <i class="fas fa-ticket-alt me-2"></i> Mes réservations
                    </a>
                    <a href="index.php?page=wallet" class="list-group-item list-group-item-action">
                        <i class="fas fa-wallet me-2"></i> Mon wallet
                    </a>
                    <?php if($_SESSION['user_role'] === 'conducteur'): ?>
                    <a href="index.php?page=subscription/manage" class="list-group-item list-group-item-action active">
                        <i class="fas fa-crown me-2"></i> Mon abonnement
                    </a>
                    <?php endif; ?>
                    <a href="index.php?page=messages" class="list-group-item list-group-item-action">
                        <i class="fas fa-envelope me-2"></i> Mes messages
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-crown me-2"></i>Gestion de mon abonnement</h4>
                </div>
                <div class="card-body">
                    <?php if ($activeSubscription): ?>
                        <!-- Affichage de l'abonnement actif -->
                        <div class="alert alert-info">
                            <h5>Abonnement actif: <?php echo strtoupper($activeSubscription['plan_type']); ?></h5>
                            <p>Date de début: <?php echo date('d/m/Y', strtotime($activeSubscription['start_date'])); ?></p>
                            <p>Date de fin: <?php echo date('d/m/Y', strtotime($activeSubscription['end_date'])); ?></p>
                            <p>Renouvellement automatique: <?php echo $activeSubscription['auto_renew'] ? 'Activé' : 'Désactivé'; ?></p>
                            <p>Prix: <span class="price-display"><?php echo number_format($activeSubscription['price'], 2); ?> FCFA</span></p>
                            
                            <div class="mt-3">
                                <button class="btn btn-danger" id="cancelSubscriptionBtn">
                                    <i class="fas fa-times-circle me-2"></i>Annuler l'abonnement
                                </button>
                                
                                <?php if ($activeSubscription['auto_renew']): ?>
                                    <button class="btn btn-warning ms-2" id="disableAutoRenewBtn">
                                        <i class="fas fa-ban me-2"></i>Désactiver le renouvellement automatique
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success ms-2" id="enableAutoRenewBtn">
                                        <i class="fas fa-check-circle me-2"></i>Activer le renouvellement automatique
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Affichage des plans d'abonnement disponibles -->
                        <div class="alert alert-warning">
                            <h5>Vous n'avez pas d'abonnement actif</h5>
                            <p>Découvrez nos formules d'abonnement pour profiter de tous les avantages de RideGenius.</p>
                        </div>
                        
                        <div class="row g-4 mt-2">
                            <!-- Formule Eco -->
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body d-flex flex-column position-relative">
                                        <h4 class="card-title">🚗 EcoTrajet</h4>
                                        <p class="card-text">Pour les voyageurs occasionnels</p>
                                        <h5 class="card-price">Gratuit</h5>
                                        <ul class="list-unstyled subscription-details">
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>2 trajets/mois</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Recherche basique</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Messagerie standard</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Évaluation des conducteurs</li>
                                            <li><i class="fas fa-times-circle me-2 text-secondary"></i>Pas de trajets prioritaires</li>
                                        </ul>
                                        <a href="#" class="btn btn-outline-primary mt-auto subscribe-btn subscription-btn" data-plan="eco">Choisir cette formule</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Formule Pro -->
                            <div class="col-md-4">
                                <div class="card h-100 border-primary">
                                    <div class="card-body d-flex flex-column position-relative">
                                        <span class="badge bg-primary position-absolute top-0 start-50 translate-middle">LE PLUS CHOISI</span>
                                        <h4 class="card-title">🚙 ProTrajet</h4>
                                        <p class="card-text">Pour les navetteurs réguliers</p>
                                        <h5 class="card-price">7,90 FCFA <small class="text-muted">/mois</small></h5>
                                        <ul class="list-unstyled subscription-details">
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Trajets illimités</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Recherche avancée</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Messagerie instantanée</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Trajets prioritaires</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Badge "Conducteur vérifié"</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Support en 24h</li>
                                        </ul>
                                        <a href="#" class="btn btn-primary mt-auto subscribe-btn subscription-btn" data-plan="pro">S'abonner</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Formule Business -->
                            <div class="col-md-4">
                                <div class="card h-100 border-warning">
                                    <div class="card-body d-flex flex-column position-relative">
                                        <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">ÉCONOMISEZ 20%</span>
                                        <h4 class="card-title">🚘 BusinessTrajet</h4>
                                        <p class="card-text">Pour les professionnels de la route</p>
                                        <h5 class="card-price">14,90 FCFA <small class="text-muted">/mois</small></h5>
                                        <ul class="list-unstyled subscription-details">
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Tous les avantages ProTrajet</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Choix des passagers</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Itinéraires premium</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Statistiques détaillées</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>Support prioritaire 24/7</li>
                                            <li><i class="fas fa-check-circle me-2 text-success"></i>0% de commission</li>
                                        </ul>
                                        <a href="#" class="btn btn-warning mt-auto subscribe-btn subscription-btn" data-plan="business">Essai gratuit 7 jours</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Historique des abonnements -->
                    <div class="mt-5">
                        <h5>Historique des abonnements</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Plan</th>
                                        <th>Date de début</th>
                                        <th>Date de fin</th>
                                        <th>Statut</th>
                                        <th>Prix</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Récupérer l'historique des abonnements
                                    $query = "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (count($subscriptions) > 0):
                                        foreach ($subscriptions as $sub):
                                    ?>
                                        <tr>
                                            <td><?php echo strtoupper($sub['plan_type']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($sub['start_date'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($sub['end_date'])); ?></td>
                                            <td>
                                                <?php if ($sub['status'] == 'active'): ?>
                                                    <span class="badge bg-success">Actif</span>
                                                <?php elseif ($sub['status'] == 'cancelled'): ?>
                                                    <span class="badge bg-danger">Annulé</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Expiré</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo number_format($sub['price'], 2); ?> FCFA</td>
                                        </tr>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Aucun abonnement trouvé</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast pour les notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto" id="toastTitle">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage">
            Message de notification
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Fonction pour afficher les notifications
    function showNotification(message, type = 'info') {
        const toast = $('#notificationToast');
        const toastTitle = $('#toastTitle');
        const toastMessage = $('#toastMessage');
        
        // Définir le titre et le message en fonction du type
        switch (type) {
            case 'success':
                toastTitle.text('Succès');
                toast.removeClass('bg-danger bg-warning').addClass('bg-success');
                break;
            case 'error':
                toastTitle.text('Erreur');
                toast.removeClass('bg-success bg-warning').addClass('bg-danger');
                break;
            case 'warning':
                toastTitle.text('Attention');
                toast.removeClass('bg-success bg-danger').addClass('bg-warning');
                break;
            default:
                toastTitle.text('Information');
                toast.removeClass('bg-success bg-danger bg-warning');
                break;
        }
        
        toastMessage.text(message);
        
        // Afficher le toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }
    
    // Annuler un abonnement
    $('#cancelSubscriptionBtn').on('click', function() {
        if (confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?')) {
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
    });
    
    // Désactiver le renouvellement automatique
    $('#disableAutoRenewBtn').on('click', function() {
        $.ajax({
            url: 'api/subscription_api.php',
            method: 'POST',
            data: { 
                action: 'updateAutoRenew',
                auto_renew: 0
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Renouvellement automatique désactivé', 'success');
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
    });
    
    // Activer le renouvellement automatique
    $('#enableAutoRenewBtn').on('click', function() {
        $.ajax({
            url: 'api/subscription_api.php',
            method: 'POST',
            data: { 
                action: 'updateAutoRenew',
                auto_renew: 1
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Renouvellement automatique activé', 'success');
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
    });
    
    // Souscrire à un abonnement
    $('.subscribe-btn').on('click', function(e) {
        e.preventDefault();
        const planType = $(this).data('plan');
        
        // Confirmer la souscription
        if (confirm('Êtes-vous sûr de vouloir souscrire à cet abonnement ?')) {
            $.ajax({
                url: 'api/subscription_api.php',
                method: 'POST',
                data: {
                    action: 'subscribe',
                    plan_type: planType,
                    auto_renew: 1
                },
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
    });
});
</script> 