<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Tableau de bord administrateur</h1>
    
    <div class="row mb-4">
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card bg-primary text-white shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Utilisateurs</h5>
                            <p class="display-4 mb-0"><?php echo $user_count; ?></p>
                        </div>
                        <div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                    <a href="index.php?page=admin-users" class="text-white d-block mt-3">Voir tous les utilisateurs <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card bg-success text-white shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Trajets</h5>
                            <p class="display-4 mb-0"><?php echo $ride_count; ?></p>
                        </div>
                        <div>
                            <i class="fas fa-route fa-3x opacity-50"></i>
                        </div>
                    </div>
                    <a href="index.php?page=admin-rides" class="text-white d-block mt-3">Voir tous les trajets <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-info text-white shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Réservations</h5>
                            <p class="display-4 mb-0"><?php echo $booking_count; ?></p>
                        </div>
                        <div>
                            <i class="fas fa-ticket-alt fa-3x opacity-50"></i>
                        </div>
                    </div>
                    <span class="d-block mt-3">Total des réservations</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Derniers utilisateurs inscrits</h5>
                        <a href="index.php?page=admin-users" class="btn btn-sm btn-outline-primary">Voir tous</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Date d'inscription</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $recent_users_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php 
                                                switch($user['role']) {
                                                    case 'passager':
                                                        echo '<span class="badge bg-info">Passager</span>';
                                                        break;
                                                    case 'conducteur':
                                                        echo '<span class="badge bg-success">Conducteur</span>';
                                                        break;
                                                    case 'admin':
                                                        echo '<span class="badge bg-danger">Admin</span>';
                                                        break;
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Derniers trajets créés</h5>
                        <a href="index.php?page=admin-rides" class="btn btn-sm btn-outline-primary">Voir tous</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Trajet</th>
                                    <th>Conducteur</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($ride = $recent_rides_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($ride['departure']); ?></div>
                                            <div class="text-muted small">→ <?php echo htmlspecialchars($ride['destination']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($ride['driver_name']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($ride['departure_time'])); ?></td>
                                        <td>
                                            <?php 
                                                switch($ride['status']) {
                                                    case 'active':
                                                        echo '<span class="badge bg-success">Actif</span>';
                                                        break;
                                                    case 'completed':
                                                        echo '<span class="badge bg-info">Terminé</span>';
                                                        break;
                                                    case 'cancelled':
                                                        echo '<span class="badge bg-danger">Annulé</span>';
                                                        break;
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Derniers avis -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Derniers avis</h5>
                    <a href="index.php?page=admin-reviews" class="btn btn-sm btn-outline-primary">Voir tous</a>
                </div>
            </div>
            <div class="card-body">
                <div id="reviews-container">
                    <!-- Les avis seront chargés ici progressivement -->
                </div>
                <div id="loading-reviews" class="text-center py-3 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Script pour l'affichage progressif des avis -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données des avis (seront remplacées par les données réelles)
    const reviewsData = <?php 
        $reviewsArray = [];
        while($row = $reviews->fetch(PDO::FETCH_ASSOC)) {
            $reviewsArray[] = $row;
        }
        echo json_encode($reviewsArray);
    ?>;
    
    const reviewsContainer = document.getElementById('reviews-container');
    const loadingIndicator = document.getElementById('loading-reviews');
    const batchSize = 3; // Nombre d'avis à afficher à la fois
    let currentIndex = 0;
    
    // Fonction pour créer un élément d'avis
    function createReviewElement(review) {
        const reviewElement = document.createElement('div');
        reviewElement.className = 'mb-3';
        
        // Créer les étoiles
        let starsHtml = '';
        for(let i = 1; i <= 5; i++) {
            if(i <= review.rating) {
                starsHtml += '<i class="fas fa-star text-warning"></i>';
            } else {
                starsHtml += '<i class="far fa-star text-warning"></i>';
            }
        }
        
        // Formater la date
        const date = new Date(review.created_at);
        const formattedDate = date.toLocaleDateString('fr-FR');
        
        reviewElement.innerHTML = `
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <strong>${review.author_name}</strong> a évalué <strong>${review.recipient_name}</strong>
                    <div class="text-warning">
                        ${starsHtml}
                    </div>
                </div>
                <div class="text-muted small">
                    ${formattedDate}
                </div>
            </div>
            <p class="mb-0">
                ${review.comment || '<span class="text-muted">Aucun commentaire</span>'}
            </p>
        `;
        
        return reviewElement;
    }
    
    // Fonction pour charger un lot d'avis
    function loadNextBatch() {
        if(currentIndex >= reviewsData.length) {
            // Tous les avis ont été affichés, recommencer depuis le début
            currentIndex = 0;
            reviewsContainer.innerHTML = '';
        }
        
        // Afficher l'indicateur de chargement
        loadingIndicator.classList.remove('d-none');
        
        // Simuler un délai de chargement
        setTimeout(() => {
            // Masquer l'indicateur de chargement
            loadingIndicator.classList.add('d-none');
            
            // Ajouter le prochain lot d'avis
            for(let i = 0; i < batchSize && currentIndex < reviewsData.length; i++) {
                const review = reviewsData[currentIndex];
                reviewsContainer.appendChild(createReviewElement(review));
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
