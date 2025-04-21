<?php
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Détails de votre abonnement</h3>
                </div>
                <div class="card-body">
                    <?php if ($activeSubscription): ?>
                        <div class="alert alert-success">
                            <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Abonnement actif</h4>
                            <p>Vous êtes actuellement abonné au plan <strong><?php echo ucfirst($activeSubscription['plan_type']); ?></strong>.</p>
                            <p>Date d'expiration: <strong><?php echo date('d/m/Y', strtotime($activeSubscription['end_date'])); ?></strong></p>
                            <hr>
                            <p class="mb-0">
                                <a href="index.php?page=cancel-subscription" class="btn btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?')">
                                    Annuler l'abonnement
                                </a>
                                <a href="index.php?page=toggle-auto-renew" class="btn btn-outline-primary ms-2">
                                    <?php echo $activeSubscription['auto_renew'] ? 'Désactiver' : 'Activer'; ?> le renouvellement automatique
                                </a>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Aucun abonnement actif</h4>
                            <p>Vous n'avez pas d'abonnement actif. Pour proposer des trajets, vous devez souscrire à un plan.</p>
                            <hr>
                            <p class="mb-0">
                                <a href="index.php" class="btn btn-primary">Voir les plans d'abonnement</a>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <h4>Solde de votre portefeuille</h4>
                        <p class="h2 text-primary"><?php echo number_format($walletBalance, 2); ?> FCFA</p>
                        <a href="index.php?page=wallet" class="btn btn-outline-primary">Recharger mon portefeuille</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 