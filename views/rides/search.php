<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Rechercher un trajet</h1>
    
    <div class="card shadow mb-5">
        <div class="card-body p-4">
            <form action="index.php" method="GET" class="row g-3">
                <input type="hidden" name="page" value="search-rides">
                
                <div class="col-md-4">
                    <label for="departure" class="form-label">Départ</label>
                    <input type="text" class="form-control" id="departure" name="departure" placeholder="Ville de départ" value="<?php echo isset($_GET['departure']) ? htmlspecialchars($_GET['departure']) : ''; ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label for="destination" class="form-label">Destination</label>
                    <input type="text" class="form-control" id="destination" name="destination" placeholder="Ville de destination" value="<?php echo isset($_GET['destination']) ? htmlspecialchars($_GET['destination']) : ''; ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d'); ?>" required>
                </div>
                
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if($searched): ?>
        <h2 class="mb-4">Résultats de recherche</h2>
        
        <?php if($results->rowCount() > 0): ?>
            <div class="row">
                <?php while($row = $results->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <h5 class="card-title"><?php echo htmlspecialchars($row['departure']); ?> → <?php echo htmlspecialchars($row['destination']); ?></h5>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($row['price']); ?> €</span>
                                </div>
                                
                                <?php $departure_time = new DateTime($row['departure_time']); ?>
                                
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
                                <a href="index.php?page=ride-details&id=<?php echo $row['id']; ?>" class="btn btn-outline-primary stretched-link">Voir détails</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Aucun trajet ne correspond à votre recherche. Essayez avec d'autres critères.
            </div>
            
            <h3 class="mt-5 mb-4">Suggestions</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Conseils pour trouver un trajet</h5>
                            <ul class="mb-0">
                                <li>Essayez avec des dates différentes</li>
                                <li>Élargissez votre recherche à des villes proches</li>
                                <li>Utilisez des noms de villes sans fautes d'orthographe</li>
                                <li>Vérifiez régulièrement, de nouveaux trajets sont ajoutés chaque jour</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Créez une alerte</h5>
                            <p>Recevez une notification dès qu'un trajet correspondant à vos critères est disponible.</p>
                            <form action="#" method="POST" class="mt-3">
                                <div class="mb-3">
                                    <input type="email" class="form-control" placeholder="Votre email" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Créer une alerte</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Utilisez le formulaire ci-dessus pour rechercher un trajet.
        </div>
        
        <h3 class="mt-5 mb-4">Destinations populaires</h3>
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="assets/images/paris.jpg" class="card-img-top" alt="Paris">
                    <div class="card-body text-center">
                        <h5 class="card-title">Paris</h5>
                        <a href="index.php?page=search-rides&departure=&destination=Paris&date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary btn-sm">Rechercher</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="assets/images/lyon.jpg" class="card-img-top" alt="Lyon">
                    <div class="card-body text-center">
                        <h5 class="card-title">Lyon</h5>
                        <a href="index.php?page=search-rides&departure=&destination=Lyon&date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary btn-sm">Rechercher</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="assets/images/marseille.jpg" class="card-img-top" alt="Marseille">
                    <div class="card-body text-center">
                        <h5 class="card-title">Marseille</h5>
                        <a href="index.php?page=search-rides&departure=&destination=Marseille&date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary btn-sm">Rechercher</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="assets/images/bordeaux.jpg" class="card-img-top" alt="Bordeaux">
                    <div class="card-body text-center">
                        <h5 class="card-title">Bordeaux</h5>
                        <a href="index.php?page=search-rides&departure=&destination=Bordeaux&date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary btn-sm">Rechercher</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
