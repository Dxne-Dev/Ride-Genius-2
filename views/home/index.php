<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Genius - Accueil</title>
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Section Héro -->
    <section class="hero text-white text-center">
        <div class="container">
            <h1 class="display-4 mb-4">Bienvenue sur Ride Genius</h1>
            <p class="lead mb-4">La plateforme de covoiturage intelligente qui vous simplifie la vie</p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form action="index.php?page=search" method="GET" class="card shadow-lg">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="departure" placeholder="Départ" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="destination" placeholder="Destination" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="date" class="form-control" name="date" required>
                                </div>
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-search"></i> Rechercher un trajet
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistiques -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm glass-card text-center">
                        <div class="card-body">
                            <i class="fas fa-car fa-3x text-primary mb-3"></i>
                            <h3 class="card-title"><?php echo $stats['total_rides']; ?></h3>
                            <p class="card-text">Trajets disponibles</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm glass-card text-center">
                        <div class="card-body">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h3 class="card-title"><?php echo $stats['total_users']; ?></h3>
                            <p class="card-text">Utilisateurs inscrits</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm glass-card text-center">
                        <div class="card-body">
                            <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                            <h3 class="card-title"><?php echo $stats['active_rides']; ?></h3>
                            <p class="card-text">Trajets en cours</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trajets récents -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Trajets récents</h2>
            <div class="row">
                <?php foreach ($recent_rides as $ride): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm glass-card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($ride['departure']); ?> → 
                                <?php echo htmlspecialchars($ride['destination']); ?>
                            </h5>
                            <p class="card-text">
                                <i class="fas fa-calendar"></i> 
                                <?php echo date('d/m/Y H:i', strtotime($ride['departure_time'])); ?>
                            </p>
                            <p class="card-text">
                                <i class="fas fa-user"></i> 
                                <?php echo htmlspecialchars($ride['driver_name']); ?>
                            </p>
                            <p class="card-text">
                                <i class="fas fa-chair"></i> 
                                <?php echo $ride['available_seats']; ?> places disponibles
                            </p>
                            <p class="card-text">
                                <i class="fas fa-euro-sign"></i> 
                                <?php echo $ride['price']; ?>€ par personne
                            </p>
                            <a href="index.php?page=ride-details&id=<?php echo $ride['id']; ?>" 
                               class="btn btn-primary">
                                Voir les détails
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Fonctionnalités -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">Pourquoi choisir Ride Genius ?</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <h4>Sécurité</h4>
                        <p>Utilisateurs vérifiés et trajets sécurisés</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-euro-sign fa-3x text-primary mb-3"></i>
                        <h4>Économique</h4>
                        <p>Partagez les frais de transport</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-leaf fa-3x text-primary mb-3"></i>
                        <h4>Écologique</h4>
                        <p>Réduisez votre impact environnemental</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                        <h4>Ponctuel</h4>
                        <p>Trajets ponctuels et fiables</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 