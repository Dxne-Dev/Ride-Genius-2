<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container-fluid p-0">
    <!-- Hero Section -->
    <div class="bg-primary text-white py-5 mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold">Voyagez intelligemment</h1>
                    <p class="fs-5">Trouvez facilement des trajets de covoiturage ou proposez les vôtres. Économisez de l'argent, réduisez votre empreinte carbone et rencontrez de nouvelles personnes.</p>
                    <div class="mt-4">
                        <a href="index.php?page=search-rides" class="btn btn-light btn-lg me-2">Trouver un trajet</a>
                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'conducteur'): ?>
                            <a href="index.php?page=create-ride" class="btn btn-outline-light btn-lg">Proposer un trajet</a>
                        <?php elseif(!isset($_SESSION['user_id'])): ?>
                            <a href="index.php?page=register" class="btn btn-outline-light btn-lg">S'inscrire</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 d-none d-md-block">
                    <img src="assets/images/carpool-illustration.jpeg" alt="Covoiturage" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

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
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if($count >= 6) break; // Limiter à 6 trajets
                
                $departure_time = new DateTime($row['departure_time']);
                $now = new DateTime();
                
                // Ne montrer que les trajets futurs
                if($departure_time > $now && $row['status'] === 'active') {
                    $count++;
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['departure']); ?> → <?php echo htmlspecialchars($row['destination']); ?></h5>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($row['price']); ?> €</span>
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
                            <a href="index.php?page=ride-details&id=<?php echo $row['id']; ?>" class="btn btn-outline-primary stretched-link">Voir détails</a>
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

    <div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5">Ce que disent nos utilisateurs</h2>
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex mb-3">
                            <div class="me-3">
                                <img src="assets/images/user01.png" alt="User" class="rounded-circle" width="60" height="60">
                            </div>
                            <div>
                                <h5 class="mb-1">Sophie Martin</h5>
                                <div class="text-warning mb-1">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                <p class="text-muted small mb-0">Passagère</p>
                            </div>
                        </div>
                        <p class="card-text">"J'utilise RideGenius depuis 6 mois et je suis ravie ! J'économise beaucoup sur mes déplacements et j'ai rencontré des gens formidables."</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex mb-3">
                            <div class="me-3">
                                <img src="assets/images/user03.png" alt="User" class="rounded-circle" width="60" height="60">
                            </div>
                            <div>
                                <h5 class="mb-1">Thomas Dupont</h5>
                                <div class="text-warning mb-1">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                                <p class="text-muted small mb-0">Conducteur</p>
                            </div>
                        </div>
                        <p class="card-text">"En tant que conducteur régulier, RideGenius m'aide à réduire mes frais de trajet. L'interface est simple et les passagers sont toujours à l'heure."</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex mb-3">
                            <div class="me-3">
                                <img src="assets/images/user02.png" alt="User" class="rounded-circle" width="60" height="60">
                            </div>
                            <div>
                                <h5 class="mb-1">Émilie Bernard</h5>
                                <div class="text-warning mb-1">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                <p class="text-muted small mb-0">Passagère & Conductrice</p>
                            </div>
                        </div>
                        <p class="card-text">"Je suis à la fois passagère et conductrice. RideGenius est de loin la meilleure plateforme de covoiturage que j'ai utilisée. Très fiable et sécurisée."</p>
                    </div>
                </div>
            
                
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex mb-3">
                            <div class="me-3">
                                <img src="assets/images/user04.png" alt="User" class="rounded-circle" width="60" height="60">
                            </div>
                            <div>
                                <h5 class="mb-1">Léa Durand</h5>
                                <div class="text-warning mb-1">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                <p class="text-muted small mb-0">Passagère</p>
                            </div>
                        </div>
                        <p class="card-text">"RideGenius est une plateforme très pratique pour trouver des trajets en covoiturage. Je l'ai utilisée plusieurs fois et je suis toujours satisfaite."</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex mb-3">
                            <div class="me-3">
                                <img src="assets/images/user05.png" alt="User" class="rounded-circle" width="60" height="60">
                            </div>
                            <div>
                                <h5 class="mb-1">Alexandre Leroy</h5>
                                <div class="text-warning mb-1">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                                <p class="text-muted small mb-0">Conducteur</p>
                            </div>
                        </div>
                        <p class="card-text">"En tant que conducteur, RideGenius m'aide à trouver des passagers pour mes trajets. L'application est facile à utiliser et les passagers sont toujours sympas."</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex mb-3">
                            <div class="me-3">
                                <img src="assets/images/user06.png" alt="User" class="rounded-circle" width="60" height="60">
                            </div>
                            <div>
                                <h5 class="mb-1">Julie Garnier</h5>
                                <div class="text-warning mb-1">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                <p class="text-muted small mb-0">Passagère & Conductrice</p>
                            </div>
                        </div>
                        <p class="card-text">"Je suis très satisfaite de RideGenius. L'application est facile à utiliser et les trajets sont toujours bien organisés."</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- CTA Section -->
    <div class="container my-5 text-center">
        <div class="py-5">
            <h2 class="display-5 fw-bold mb-3">Prêt à rejoindre notre communauté ?</h2>
            <p class="fs-5 text-muted mb-4">Inscrivez-vous gratuitement et commencez à voyager intelligemment dès aujourd'hui.</p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="index.php?page=register" class="btn btn-primary btn-lg px-4 me-sm-3">S'inscrire</a>
                    <a href="index.php?page=login" class="btn btn-outline-secondary btn-lg px-4">Se connecter</a>
                <?php else: ?>
                    <a href="index.php?page=search-rides" class="btn btn-primary btn-lg px-4 me-sm-3">Rechercher un trajet</a>
                    <?php if($_SESSION['user_role'] === 'conducteur'): ?>
                        <a href="index.php?page=create-ride" class="btn btn-outline-secondary btn-lg px-4">Proposer un trajet</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>