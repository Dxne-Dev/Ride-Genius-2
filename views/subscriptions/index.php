<?php
include 'includes/header.php';
include 'includes/navbar.php';

// Vérifier si l'utilisateur est connecté et est un conducteur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header('Location: index.php');
    exit;
}

// Instancier les modèles nécessaires
$subscriptionModel = new Subscription();
$walletModel = new Wallet();

// Récupérer la souscription active de l'utilisateur
$activeSubscription = $subscriptionModel->getActiveSubscription($_SESSION['user_id']);
$walletBalance = $walletModel->getBalance($_SESSION['user_id']);

// Définir les plans de souscription
$plans = [
    'eco' => [
        'name' => 'Eco',
        'price' => 29.99,
        'features' => [
            'Accès aux trajets de base',
            'Support client standard',
            'Commission réduite de 10%'
        ]
    ],
    'pro' => [
        'name' => 'ProTrajet',
        'price' => 49.99,
        'features' => [
            'Accès aux trajets premium',
            'Support client prioritaire',
            'Commission réduite de 15%',
            'Badge Pro sur votre profil'
        ]
    ],
    'business' => [
        'name' => 'BusinessTrajet',
        'price' => 99.99,
        'features' => [
            'Accès à tous les types de trajets',
            'Support client VIP 24/7',
            'Commission réduite de 20%',
            'Badge Business sur votre profil',
            'Visibilité accrue dans les résultats'
        ]
    ]
];
?>

<div class="container py-5">
    <h1 class="text-center mb-5">Gestion de votre abonnement</h1>

    <?php if ($activeSubscription): ?>
        <div class="alert alert-info mb-4">
            <h4 class="alert-heading">Votre abonnement actuel</h4>
            <p>Plan : <?php echo $plans[$activeSubscription['plan_type']]['name']; ?></p>
            <p>Date de fin : <?php echo date('d/m/Y', strtotime($activeSubscription['end_date'])); ?></p>
            <p>Renouvellement automatique : <?php echo $activeSubscription['auto_renew'] ? 'Activé' : 'Désactivé'; ?></p>
            <div class="mt-3">
                <button class="btn btn-warning me-2" onclick="toggleAutoRenew(<?php echo $activeSubscription['id']; ?>)">
                    <?php echo $activeSubscription['auto_renew'] ? 'Désactiver' : 'Activer'; ?> le renouvellement automatique
                </button>
                <button class="btn btn-danger" onclick="cancelSubscription(<?php echo $activeSubscription['id']; ?>)">
                    Annuler l'abonnement
                </button>
            </div>
        </div>
    <?php endif; ?>

    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <?php foreach ($plans as $type => $plan): ?>
        <div class="col">
            <div class="card h-100 <?php echo $activeSubscription && $activeSubscription['plan_type'] === $type ? 'border-primary' : ''; ?>">
                <div class="card-header text-center">
                    <h3><?php echo $plan['name']; ?></h3>
                </div>
                <div class="card-body">
                    <h4 class="card-title text-center mb-4"><?php echo number_format($plan['price'], 2); ?> €/mois</h4>
                    <ul class="list-unstyled">
                        <?php foreach ($plan['features'] as $feature): ?>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo $feature; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <?php if ($activeSubscription && $activeSubscription['plan_type'] === $type): ?>
                        <button class="btn btn-secondary" disabled>Plan actuel</button>
                    <?php else: ?>
                        <button class="btn btn-primary" onclick="subscribe('<?php echo $type; ?>', <?php echo $plan['price']; ?>)"
                                <?php echo $walletBalance < $plan['price'] ? 'disabled' : ''; ?>>
                            Souscrire
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($walletBalance < min(array_column($plans, 'price'))): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Solde insuffisant dans votre portefeuille. Veuillez recharger votre compte pour souscrire à un abonnement.
        </div>
    <?php endif; ?>
</div>

<script>
function subscribe(planType, price) {
    if (!confirm(`Êtes-vous sûr de vouloir souscrire au plan ${planType} pour ${price}€ par mois ?`)) {
        return;
    }

    fetch('index.php?page=subscription-subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `plan_type=${planType}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
            setTimeout(() => window.location.reload(), 2000);
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        toastr.error('Une erreur est survenue. Veuillez réessayer.');
    });
}

function cancelSubscription(subscriptionId) {
    if (!confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?')) {
        return;
    }

    fetch('index.php?page=subscription-cancel', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `subscription_id=${subscriptionId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
            setTimeout(() => window.location.reload(), 2000);
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        toastr.error('Une erreur est survenue. Veuillez réessayer.');
    });
}

function toggleAutoRenew(subscriptionId) {
    fetch('index.php?page=subscription-toggle-auto-renew', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `subscription_id=${subscriptionId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
            setTimeout(() => window.location.reload(), 2000);
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        toastr.error('Une erreur est survenue. Veuillez réessayer.');
    });
}
</script>

<?php include 'includes/footer.php'; ?> 