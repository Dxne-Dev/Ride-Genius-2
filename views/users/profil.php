<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle" style="width: 100px; height: 100px;">
                            <span class="display-4"><?php echo strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)); ?></span>
                        </div>
                    </div>
                    <h3><?php echo htmlspecialchars($user->first_name . ' ' . $user->last_name); ?></h3>
                    <p class="text-muted">
                        <?php 
                            switch($user->role) {
                                case 'passager':
                                    echo '<span class="badge bg-info">Passager</span>';
                                    break;
                                case 'conducteur':
                                    echo '<span class="badge bg-success">Conducteur</span>';
                                    break;
                                case 'admin':
                                    echo '<span class="badge bg-danger">Administrateur</span>';
                                    break;
                            }
                        ?>
                    </p>
                    
                    <?php if(isset($rating_data) && $rating_data['total_reviews'] > 0): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-center">
                                <?php 
                                    $average = round($rating_data['average_rating']);
                                    for($i = 1; $i <= 5; $i++) {
                                        if($i <= $average) {
                                            echo '<i class="fas fa-star text-warning me-1"></i>';
                                        } else {
                                            echo '<i class="far fa-star text-warning me-1"></i>';
                                        }
                                    }
                                ?>
                                <span class="ms-2">(<?php echo $rating_data['total_reviews']; ?> avis)</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2">
                        <?php if(!isset($_GET['id'])): ?>
                            <a href="index.php?page=edit-profile" class="btn btn-outline-primary">Modifier profil</a>
                            <a href="index.php?page=change-password" class="btn btn-outline-secondary">Changer mot de passe</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Informations personnelles</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Nom complet</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php echo htmlspecialchars($user->first_name . ' ' . $user->last_name); ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Email</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php echo htmlspecialchars($user->email); ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Téléphone</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php echo $user->phone ? htmlspecialchars($user->phone) : '<span class="text-muted">Non renseigné</span>'; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Type d'utilisateur</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php 
                                switch($user->role) {
                                    case 'passager':
                                        echo 'Passager';
                                        break;
                                    case 'conducteur':
                                        echo 'Conducteur';
                                        break;
                                    case 'admin':
                                        echo 'Administrateur';
                                        break;
                                }
                            ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-sm-3">
                            <strong>Inscrit depuis</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php 
                                $date = new DateTime($user->created_at);
                                echo $date->format('d/m/Y');
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if($user->role === 'passager'): ?>
            <!-- Avis-conducteur (pour les passagers) -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Avis-conducteur</h4>
                </div>
                <div class="card-body">
                    <?php
                    // Récupérer les avis donnés par ce passager
                    $reviewsGiven = [];
                    if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user->id) {
                        require_once 'models/Review.php';
                        $db = new Database();
                        $conn = $db->getConnection();
                        
                        $stmt = $conn->prepare("SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as driver_name, 
                                              r.rating, r.comment, r.created_at
                                              FROM reviews r 
                                              JOIN users u ON r.recipient_id = u.id
                                              WHERE r.author_id = ?
                                              ORDER BY r.created_at DESC");
                        $stmt->execute([$user->id]);
                        $reviewsGiven = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                    
                    $count = count($reviewsGiven);
                    foreach($reviewsGiven as $index => $review) {
                    ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($review['driver_name']); ?></strong>
                                    <div class="text-warning">
                                        <?php 
                                            for($i = 1; $i <= 5; $i++) {
                                                if($i <= $review['rating']) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                        ?>
                                    </div>
                                </div>
                                <div class="text-muted small">
                                    <?php 
                                        $date = new DateTime($review['created_at']);
                                        echo $date->format('d/m/Y');
                                    ?>
                                </div>
                            </div>
                            <p class="mb-0">
                                <?php echo $review['comment'] ? htmlspecialchars($review['comment']) : '<span class="text-muted">Aucun commentaire</span>'; ?>
                            </p>
                        </div>
                        <?php if($index < $count - 1): ?>
                            <hr>
                        <?php endif; ?>
                    <?php
                    }
                    
                    if($count === 0) {
                        echo '<p class="text-center">Vous n\'avez pas encore évalué de conducteurs.</p>';
                        echo '<div class="text-center mt-3"><a href="index.php?page=driver-reviews" class="btn btn-primary">Évaluer un conducteur</a></div>';
                    }
                    ?>
                </div>
            </div>
            <?php else: ?>
            <!-- Avis reçus (pour les conducteurs et admins) -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Avis reçus</h4>
                </div>
                <div class="card-body">
                    <?php
                    // Charger tous les avis reçus dans un tableau pour la pagination
                    $reviews_arr = [];
                    while($row = $reviews->fetch(PDO::FETCH_ASSOC)) {
                        $reviews_arr[] = $row;
                    }
                    $page_avis = isset($_GET['page_avis']) && is_numeric($_GET['page_avis']) ? (int)$_GET['page_avis'] : 1;
                    $per_page_avis = 4;
                    $total_avis = count($reviews_arr);
                    $total_pages_avis = ceil($total_avis / $per_page_avis);
                    $start_avis = ($page_avis - 1) * $per_page_avis;
                    $reviews_page = array_slice($reviews_arr, $start_avis, $per_page_avis);

                    if($total_avis > 0) {
                        foreach($reviews_page as $idx => $row) {
                    ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($row['author_name']); ?></strong>
                                    <div class="text-warning">
                                        <?php 
                                            for($i = 1; $i <= 5; $i++) {
                                                if($i <= $row['rating']) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                        ?>
                                    </div>
                                </div>
                                <div class="text-muted small">
                                    <?php 
                                        $date = new DateTime($row['created_at']);
                                        echo $date->format('d/m/Y');
                                    ?>
                                </div>
                            </div>
                            <p class="mb-0">
                                <?php echo $row['comment'] ? html_entity_decode(htmlspecialchars($row['comment'], ENT_QUOTES | ENT_HTML5)) : '<span class="text-muted">Aucun commentaire</span>'; ?>
                            </p>
                        </div>
                        <?php if($idx < count($reviews_page) - 1): ?>
                            <hr>
                        <?php endif; ?>
                    <?php }
                    // Pagination
                    ?>
                    <?php if($total_pages_avis > 1): ?>
                    <nav aria-label="Pagination avis" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item<?php if($page_avis <= 1) echo ' disabled'; ?>">
                                <a class="page-link" href="?page_avis=<?php echo $page_avis-1; ?>" tabindex="-1">Précédent</a>
                            </li>
                            <?php for($i = 1; $i <= $total_pages_avis; $i++): ?>
                            <li class="page-item<?php if($i == $page_avis) echo ' active'; ?>">
                                <a class="page-link" href="?page_avis=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item<?php if($page_avis >= $total_pages_avis) echo ' disabled'; ?>">
                                <a class="page-link" href="?page_avis=<?php echo $page_avis+1; ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    <?php
                    } else {
                        echo '<p class="text-center">Aucun avis reçu pour le moment.</p>';
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>