<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<?php
// Vérification de l'existence de la clé 'user_role' dans $_SESSION
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = null; // Définir une valeur par défaut si elle n'existe pas
}

// Initialisation des modèles nécessaires
$subscription = new Subscription($db);
$wallet = new Wallet($db);

// Vérifier si l'utilisateur est connecté et est un conducteur
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'conducteur') {
    $activeSubscription = $subscription->getActiveSubscription($_SESSION['user_id']);
    $walletBalance = $wallet->getBalance($_SESSION['user_id']);
}
?>

<head>
    <!-- Floating Notifications -->
    <link rel="stylesheet" href="assets/css/floating-notifications.css">
    <script src="assets/js/floating-notifications.js"></script>
    <script src="assets/js/subscription.js"></script>
    
    <!-- Chart.js pour le graphique des commissions -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/commission-chart.js"></script>
</head>

<div class="container-fluid p-0">
    <!-- Hero Section -->
    <div class="bg-primary text-white py-5 mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold">Voyagez intelligemment</h1>
                    <p class="fs-5">Trouvez facilement des trajets de covoiturage ou proposez les vôtres. Économisez de l'argent, réduisez votre empreinte carbone et rencontrez de nouvelles personnes.</p>
                    <div class="mt-4">
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <a href="index.php?page=search-rides" class="btn btn-light btn-lg me-2">Trouver un trajet</a>
                            <a href="index.php?page=register" class="btn btn-outline-light btn-lg">S'inscrire</a>
                        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'conducteur'): ?>
                            <a href="index.php?page=search-rides" class="btn btn-light btn-lg me-2">Trouver un trajet</a>
                            <a href="index.php?page=create-ride" class="btn btn-outline-light btn-lg">Proposer un trajet</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 d-none d-md-block">
                    <img src="assets/images/carpool-illustration.jpeg" alt="Covoiturage" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <!-- Section Admin -->
        <div class="container py-5">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card bg-dark text-white">
                        <div class="card-body p-4">
                            <h2 class="card-title">Bienvenue sur votre espace administrateur</h2>
                            <p class="card-text">Gérez votre plateforme de covoiturage en toute simplicité.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Statistiques rapides -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-primary">
                        <div class="card-body text-center">
                            <i class="fas fa-wallet fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Commission Wallet</h5>
                            <h3 class="text-primary">
                                <?php
                                $balance = $wallet->getBalance($_SESSION['user_id']);
                                echo number_format($balance, 2) . ' FCFA';
                                ?>
                            </h3>
                            <p class="text-muted">Total des commissions</p>
                        </div>
                    </div>
                </div>

                <!-- Dernières commissions -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-success">
                        <div class="card-body text-center">
                            <i class="fas fa-percentage fa-3x text-success mb-3"></i>
                            <h5 class="card-title">Commissions du jour</h5>
                            <h3 class="text-success">
                                <?php
                                $commission = new Commission($db);
                                $todayCommissions = $commission->getTodayTotal();
                                echo number_format($todayCommissions, 2) . ' FCFA';
                                ?>
                            </h3>
                            <p class="text-muted">Gains aujourd'hui</p>
                        </div>
                    </div>
                </div>

                <!-- Trajets actifs -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-info">
                        <div class="card-body text-center">
                            <i class="fas fa-route fa-3x text-info mb-3"></i>
                            <h5 class="card-title">Trajets actifs</h5>
                            <h3 class="text-info">
                                <?php
                                $ride = new Ride($db);
                                $activeRides = $ride->countByStatus('active');
                                echo $activeRides;
                                ?>
                            </h3>
                            <p class="text-muted">En cours actuellement</p>
                        </div>
                    </div>
                </div>

                <!-- Réservations en attente -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                            <h5 class="card-title">Réservations en attente</h5>
                            <h3 class="text-warning">
                                <?php
                                $booking = new Booking($db);
                                $pendingBookings = $booking->countByStatus('pending');
                                echo $pendingBookings;
                                ?>
                            </h3>
                            <p class="text-muted">À traiter</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Actions rapides</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="index.php?page=admin-dashboard" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard complet
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="index.php?page=admin-users" class="btn btn-outline-success w-100">
                                        <i class="fas fa-users me-2"></i>Gérer les utilisateurs
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="index.php?page=admin-rides" class="btn btn-outline-info w-100">
                                        <i class="fas fa-car me-2"></i>Gérer les trajets
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="index.php?page=wallet" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-wallet me-2"></i>Gérer le wallet
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphique des commissions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Aperçu des commissions</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="commissionsChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search Form Section -->
    <div class="container mb-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">Rechercher un trajet</h2>
                <form action="index.php" method="GET" class="row g-3">
                    <input type="hidden" name="page" value="search-rides">
                    
                    <div class="col-md-4">
                        <label for="departure" class="form-label">Départ</label>
                        <input type="text" class="form-control" id="departure" name="departure" placeholder="Ville de départ" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="destination" class="form-label">Destination</label>
                        <input type="text" class="form-control" id="destination" name="destination" placeholder="Ville de destination" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Recent Rides Section -->
    <div class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Trajets récents</h2>
            <a href="index.php?page=rides" class="btn btn-outline-primary">Voir tous les trajets</a>
        </div>
        <div class="row">
            <?php
            // Obtenir les trajets récents
            $database = new Database();
            $db = $database->getConnection();
            $ride = new Ride($db);
            $stmt = $ride->read();
            $count = 0;
            
            // Préparer les informations sur les prix pour chaque trajet
            require_once 'models/Commission.php';
            $commission = new Commission($db);
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if($count >= 6) break; // Limiter à 6 trajets
                
                $departure_time = new DateTime($row['departure_time']);
                $now = new DateTime();
                
                // Ne montrer que les trajets futurs
                if($departure_time > $now && $row['status'] === 'active') {
                    $count++;
                    
                    // Calculer le prix avec commission selon l'abonnement du conducteur
                    $driverSubscription = $subscription->getActiveSubscription($row['driver_id']);
                    $subscriptionType = $driverSubscription ? $driverSubscription['plan_type'] : 'eco';
                    
                    $commissionInfo = $commission->calculateCommission($row['price'], $subscriptionType);
                    
                    // Pour les conducteurs ProTrajet, on ajoute la commission au prix affiché
                    $displayPrice = $row['price'];
                    if ($subscriptionType === 'pro') {
                        $displayPrice = $row['price'] + $commissionInfo['amount'];
                    }
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm glass-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['departure']); ?> → <?php echo htmlspecialchars($row['destination']); ?></h5>
                                <span class="badge bg-primary" style="min-width: 70px; text-align: right;"><?php echo number_format($displayPrice, 2); ?> FCFA</span>
                            </div>
                            <p class="card-text text-muted mb-1">
                                <i class="fas fa-calendar-alt me-2"></i><?php echo $departure_time->format('d/m/Y'); ?>
                            </p>
                            <p class="card-text text-muted mb-1">
                                <i class="fas fa-clock me-2"></i><?php echo $departure_time->format('H:i'); ?>
                            </p>
                            <p class="card-text text-muted mb-3">
                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($row['driver_name']); ?>
                            </p>
                            <p class="card-text mb-3">
                                <span class="text-success"><?php echo htmlspecialchars($row['available_seats']); ?> place(s) disponible(s)</span>
                            </p>
                            <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <a href="index.php?page=ride-details&id=<?php echo $row['id']; ?>" class="btn btn-outline-primary stretched-link">Voir détails</a>
                            <?php else: ?>
                                <a href="index.php?page=ride-details&id=<?php echo $row['id']; ?>" class="btn btn-outline-primary stretched-link">Voir détails</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php
                }
            }
            
            if($count === 0) {
                echo '<div class="col-12 text-center"><p>Aucun trajet disponible pour le moment.</p></div>';
            }
            ?>
        </div>
    </div>

    <!-- Features Section -->
    <div class="container mb-5">
        <h2 class="text-center mb-4">Pourquoi choisir RideGenius</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-euro-sign fa-2x"></i>
                        </div>
                        <h4>Économisez de l'argent</h4>
                        <p class="text-muted">Partagez les frais de transport et réduisez vos dépenses de déplacement.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-leaf fa-2x"></i>
                        </div>
                        <h4>Protégez l'environnement</h4>
                        <p class="text-muted">Réduisez votre empreinte carbone en partageant votre trajet avec d'autres voyageurs.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                        <h4>Rencontrez de nouvelles personnes</h4>
                        <p class="text-muted">Élargissez votre réseau social et rendez vos voyages plus agréables.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- How it Works Section -->
    <div class="bg-light py-5 mb-5">
        <div class="container">
            <h2 class="text-center mb-5">Comment ça marche</h2>
            <div class="row">
                <div class="col-md-4 text-center mb-4 mb-md-0">
                    <div class="bg-white rounded-circle shadow d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-search fa-2x text-primary"></i>
                    </div>
                    <h4 class="mt-3">1. Recherchez</h4>
                    <p class="text-muted">Entrez votre lieu de départ, votre destination et la date souhaitée.</p>
                </div>
                <div class="col-md-4 text-center mb-4 mb-md-0">
                    <div class="bg-white rounded-circle shadow d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-check-circle fa-2x text-primary"></i>
                    </div>
                    <h4 class="mt-3">2. Réservez</h4>
                    <p class="text-muted">Choisissez le trajet qui vous convient le mieux et réservez votre place.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="bg-white rounded-circle shadow d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-car fa-2x text-primary"></i>
                    </div>
                    <h4 class="mt-3">3. Voyagez</h4>
                    <p class="text-muted">Rencontrez votre conducteur au point de rendez-vous et profitez du voyage.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscription Plans Section -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'conducteur'): ?>
        <!-- Section Abonnement -->
        <div class="container py-5" id="subscription-section">
            <?php if ($activeSubscription): ?>
                <!-- Affichage des détails de l'abonnement actif -->
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h3 class="mb-0">Votre abonnement actif</h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h4 class="alert-heading">Plan <?php echo ucfirst($activeSubscription['plan_type']); ?></h4>
                                    <p>Vous êtes actuellement abonné au plan <strong><?php echo ucfirst($activeSubscription['plan_type']); ?></strong>.</p>
                                    <p>Date d'expiration: <strong><?php echo date('d/m/Y', strtotime($activeSubscription['end_date'])); ?></strong></p>
                                    <hr>
                                    <p class="mb-0">
                                        <a href="index.php?page=subscription-details" class="btn btn-outline-primary">Gérer mon abonnement</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Affichage des plans d'abonnement disponibles -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h2 class="text-center mb-4">Nos Plans d'Abonnement</h2>
                        <p class="text-center text-muted">Choisissez le plan qui correspond à vos besoins</p>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Plan Eco -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center p-4">
                                <h3 class="card-title">Eco</h3>
                                <div class="pricing mb-4">
                                    <h4 class="text-primary mb-0">Gratuit</h4>
                                    <p class="text-muted">Commission de 15% par trajet</p>
                                </div>
                                <ul class="list-unstyled mb-4">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Accès aux fonctionnalités de base</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Support par email</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Profil conducteur standard</li>
                                </ul>
                                <a href="index.php?page=subscribe&plan=eco" class="btn btn-primary w-100">Choisir ce plan</a>
                            </div>
                        </div>
                    </div>

                    <!-- Plan ProTrajet -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center p-4">
                                <h3 class="card-title">ProTrajet</h3>
                                <div class="pricing mb-4">
                                    <h4 class="text-primary mb-0">9.99 FCFA</h4>
                                    <p class="text-muted">Commission réduite à 10%</p>
                                </div>
                                <ul class="list-unstyled mb-4">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Support prioritaire</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Statistiques avancées</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Badge Pro sur votre profil</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Accès aux fonctionnalités premium</li>
                                </ul>
                                <a href="index.php?page=subscribe&plan=protrajet" class="btn <?php echo $walletBalance >= 9.99 ? 'btn-primary' : 'btn-secondary disabled'; ?> w-100">
                                    <?php echo $walletBalance < 9.99 ? 'Solde insuffisant' : 'Choisir ce plan'; ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Plan BusinessTrajet -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center p-4">
                                <h3 class="card-title">BusinessTrajet</h3>
                                <div class="pricing mb-4">
                                    <h4 class="text-primary mb-0">29.99 FCFA</h4>
                                    <p class="text-muted">Commission minimale de 5%</p>
                                </div>
                                <ul class="list-unstyled mb-4">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Support VIP 24/7</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Tableau de bord personnalisé</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Badge Business Elite</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Accès prioritaire aux nouvelles fonctionnalités</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Statistiques détaillées et rapports</li>
                                </ul>
                                <a href="index.php?page=subscribe&plan=businesstrajet" class="btn <?php echo $walletBalance >= 29.99 ? 'btn-primary' : 'btn-secondary disabled'; ?> w-100">
                                    <?php echo $walletBalance < 29.99 ? 'Solde insuffisant' : 'Choisir ce plan'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Testimonials Section -->
    <div class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">Ce que disent nos utilisateurs</h2>
            <div class="row" id="testimonials-container">
                <!-- Les avis seront chargés ici progressivement -->
            </div>
            <div id="loading-testimonials" class="text-center py-3 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <!-- Script pour le graphique -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Données pour le graphique (à remplacer par des données dynamiques)
        const ctx = document.getElementById('commissionsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                datasets: [{
                    label: 'Commissions (FCFA)',
                    data: [
                        <?php
                        $commission = new Commission($db);
                        $weeklyData = $commission->getWeeklyData();
                        echo implode(',', $weeklyData);
                        ?>
                    ],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Commissions de la semaine'
                    }
                }
            }
        });
    </script>
<?php endif; ?>

<!-- Script pour l'affichage progressif des avis -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données des avis (seront remplacées par les données réelles)
    const testimonialsData = [
        {
            name: "Sophie Martin",
            role: "Passagère",
            rating: 5,
            comment: "J'utilise RideGenius depuis 6 mois et je suis ravie ! J'économise beaucoup sur mes déplacements et j'ai rencontré des gens formidables.",
            image: "assets/images/user01.png"
        },
        {
            name: "Thomas Dupont",
            role: "Conducteur",
            rating: 4.5,
            comment: "En tant que conducteur régulier, RideGenius m'aide à réduire mes frais de trajet. L'interface est simple et les passagers sont toujours à l'heure.",
            image: "assets/images/user03.png"
        },
        {
            name: "Émilie Bernard",
            role: "Passagère & Conductrice",
            rating: 5,
            comment: "Je suis à la fois passagère et conductrice. RideGenius est de loin la meilleure plateforme de covoiturage que j'ai utilisée. Très fiable et sécurisée.",
            image: "assets/images/user02.png"
        },
        {
            name: "Lucas Petit",
            role: "Passager",
            rating: 5,
            comment: "Je voyage régulièrement pour mon travail et RideGenius m'a permis de réduire considérablement mes frais de transport. Les conducteurs sont toujours ponctuels.",
            image: "assets/images/user04.png"
        },
        {
            name: "Julie Moreau",
            role: "Conductrice",
            rating: 4,
            comment: "J'apprécie la simplicité de l'application et la qualité des passagers. C'est un excellent moyen de rentabiliser mes trajets quotidiens.",
            image: "assets/images/user05.png"
        },
        {
            name: "Nicolas Dubois",
            role: "Passager",
            rating: 5,
            comment: "RideGenius a révolutionné mes déplacements. Plus besoin de prendre le train, je trouve facilement des covoiturages pour mes déplacements professionnels.",
            image: "assets/images/user06.png"
        },
        {
            name: "Camille Leroy",
            role: "Conductrice",
            rating: 4.5,
            comment: "En tant que conductrice, j'apprécie la flexibilité et la sécurité de la plateforme. Les paiements sont simples et les commissions raisonnables.",
            image: "assets/images/user07.png"
        },
        {
            name: "Antoine Roux",
            role: "Passager & Conducteur",
            rating: 5,
            comment: "Je suis un utilisateur régulier, à la fois comme passager et conducteur. RideGenius offre une expérience utilisateur exceptionnelle.",
            image: "assets/images/user08.png"
        },
        {
            name: "Sarah Blanc",
            role: "Passagère",
            rating: 4,
            comment: "J'ai découvert RideGenius il y a quelques mois et je ne peux plus m'en passer. C'est économique, écologique et j'ai fait de belles rencontres.",
            image: "assets/images/user09.png"
        }
    ];
    
    const testimonialsContainer = document.getElementById('testimonials-container');
    const loadingIndicator = document.getElementById('loading-testimonials');
    const batchSize = 3; // Nombre d'avis à afficher à la fois
    let currentIndex = 0;
    
    // Fonction pour créer un élément d'avis
    function createTestimonialElement(testimonial) {
        const colElement = document.createElement('div');
        colElement.className = 'col-md-4 mb-4 mb-md-0';
        
        // Créer les étoiles
        let starsHtml = '';
        for(let i = 1; i <= 5; i++) {
            if(i <= testimonial.rating) {
                starsHtml += '<i class="fas fa-star"></i>';
            } else if(i - 0.5 <= testimonial.rating) {
                starsHtml += '<i class="fas fa-star-half-alt"></i>';
            } else {
                starsHtml += '<i class="far fa-star"></i>';
            }
        }
        
        colElement.innerHTML = `
            <div class="card h-100 shadow-sm glass-card">
                <div class="card-body p-4">
                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <img src="${testimonial.image}" alt="User" class="rounded-circle" width="60" height="60">
                        </div>
                        <div>
                            <h5 class="mb-1">${testimonial.name}</h5>
                            <div class="text-warning mb-1">
                                ${starsHtml}
                            </div>
                            <p class="text-muted small mb-0">${testimonial.role}</p>
                        </div>
                    </div>
                    <p class="card-text">"${testimonial.comment}"</p>
                </div>
            </div>
        `;
        
        return colElement;
    }
    
    // Fonction pour charger un lot d'avis
    function loadNextBatch() {
        if(currentIndex >= testimonialsData.length) {
            // Tous les avis ont été affichés, recommencer depuis le début
            currentIndex = 0;
        }
        
        // Afficher l'indicateur de chargement
        loadingIndicator.classList.remove('d-none');
        
        // Simuler un délai de chargement
        setTimeout(() => {
            // Masquer l'indicateur de chargement
            loadingIndicator.classList.add('d-none');
            
            // Vider le conteneur avant d'ajouter les nouveaux avis
            testimonialsContainer.innerHTML = '';
            
            // Ajouter le prochain lot d'avis
            for(let i = 0; i < batchSize && currentIndex < testimonialsData.length; i++) {
                const testimonial = testimonialsData[currentIndex];
                testimonialsContainer.appendChild(createTestimonialElement(testimonial));
                currentIndex++;
            }
        }, 500); // Délai de 500ms pour l'animation
    }
    
    // Charger le premier lot d'avis
    loadNextBatch();
    
    // Configurer l'intervalle pour charger les avis toutes les 20 secondes
    setInterval(loadNextBatch, 20000);
});
</script>

<!-- Script pour le défilement vers la section d'abonnement -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['redirect_to_subscription']) && $_SESSION['redirect_to_subscription']): ?>
        // Supprimer le flag de redirection
        <?php unset($_SESSION['redirect_to_subscription']); ?>
        
        // Faire défiler jusqu'à la section d'abonnement avec une animation
        const subscriptionSection = document.getElementById('subscription-section');
        if (subscriptionSection) {
            setTimeout(function() {
                subscriptionSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                // Ajouter une classe pour mettre en évidence la section
                subscriptionSection.classList.add('highlight-section');
                
                // Supprimer la classe après l'animation
                setTimeout(function() {
                    subscriptionSection.classList.remove('highlight-section');
                }, 2000);
            }, 500);
        }
    <?php endif; ?>
});
</script>

<style>
.highlight-section {
    animation: highlight 2s ease-in-out;
}

@keyframes highlight {
    0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7); }
    50% { box-shadow: 0 0 20px 10px rgba(0, 123, 255, 0.3); }
    100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
}
</style>

<?php include 'includes/footer.php'; ?>