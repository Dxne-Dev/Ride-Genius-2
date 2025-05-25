<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php
// views/reviews/driver_reviews.php - Page spéciale pour les passagers

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

require_once 'models/Review.php';
require_once 'models/User.php';
require_once 'models/Booking.php';
require_once 'models/Ride.php';

// Initialiser les modèles
$reviewModel = new Review($db);
$userModel = new User($db);
$bookingModel = new Booking($db);
$rideModel = new Ride($db);

// Récupérer l'ID du passager connecté
$passengerId = $_SESSION['user_id'];

// Récupérer les réservations du passager
$bookingModel->passenger_id = $passengerId;
$bookings = $bookingModel->readPassengerBookings();

// Tableau pour stocker les conducteurs uniques
$drivers = [];
$reviewedDrivers = [];

// Récupérer les avis déjà donnés par ce passager
$stmt = $db->prepare("SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as driver_name, 
                      r.rating, r.comment, r.created_at
                      FROM reviews r 
                      JOIN users u ON r.recipient_id = u.id
                      WHERE r.author_id = ?");
$stmt->execute([$passengerId]);
$existingReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les avis par ID de conducteur
$reviewsByDriverId = [];
foreach ($existingReviews as $review) {
    $reviewsByDriverId[$review['recipient_id']] = $review;
    $reviewedDrivers[$review['recipient_id']] = true;
}

// Récupérer les conducteurs des réservations
while ($booking = $bookings->fetch(PDO::FETCH_ASSOC)) {
    // Récupérer les informations du trajet
    $rideModel->id = $booking['ride_id'];
    $rideFound = $rideModel->readOne();
    
    if ($rideFound) {
        // Récupérer les informations du conducteur
        $userModel->id = $rideModel->driver_id;
        $userModel->readOne();
        
        // Ajouter le conducteur au tableau s'il n'y est pas déjà
        if (!isset($drivers[$rideModel->driver_id])) {
            $drivers[$rideModel->driver_id] = [
                'id' => $rideModel->driver_id,
                'name' => $rideModel->driver_name,
                'rides' => [],
                'has_review' => isset($reviewedDrivers[$rideModel->driver_id]),
                'review' => $reviewsByDriverId[$rideModel->driver_id] ?? null
            ];
        }
        
        // Ajouter le trajet au conducteur
        $drivers[$rideModel->driver_id]['rides'][] = [
            'id' => $rideModel->id,
            'booking_id' => $booking['id'],
            'departure' => $rideModel->departure,
            'destination' => $rideModel->destination,
            'departure_time' => $rideModel->departure_time,
            'status' => $booking['status'],
            'can_review' => ($booking['status'] === 'completed')
        ];
    }
}
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2 mb-0">Avis Conducteurs</h1>
            <p class="text-muted">Évaluez les conducteurs avec qui vous avez voyagé</p>
        </div>
    </div>
    
    <?php if (empty($drivers)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-car-side text-muted mb-3" style="font-size: 3rem;"></i>
                <h3 class="h5">Aucun trajet effectué</h3>
                <p class="text-muted mb-0">Vous n'avez pas encore effectué de trajet avec un conducteur. Réservez un trajet pour pouvoir laisser un avis.</p>
                <div class="mt-4">
                    <a href="index.php?page=rides" class="btn btn-primary">Trouver un trajet</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Conducteurs à évaluer -->
            <div class="col-lg-7 mb-4 mb-lg-0">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Conducteurs à évaluer</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php 
                            // Afficher tous les conducteurs
                            $hasDrivers = false;
                            foreach ($drivers as $driver): 
                                $hasDrivers = true;
                                $canReviewAnyRide = false;
                                $completedRides = array_filter($driver['rides'], function($ride) {
                                    return $ride['can_review'];
                                });
                                
                                if (count($completedRides) > 0) {
                                    $canReviewAnyRide = true;
                                }
                            ?>
                                <div class="list-group-item p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <?php echo strtoupper(substr($driver['name'], 0, 2)); ?>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($driver['name']); ?></h5>
                                            <p class="mb-0 text-muted small">
                                                <?php 
                                                    echo count($driver['rides']) . ' trajet' . (count($driver['rides']) > 1 ? 's' : '') . ' réservé' . (count($driver['rides']) > 1 ? 's' : '');
                                                    
                                                    // Afficher le nombre de trajets complétés
                                                    if (count($completedRides) > 0) {
                                                        echo ' (' . count($completedRides) . ' terminé' . (count($completedRides) > 1 ? 's' : '') . ')';
                                                    }
                                                ?>
                                            </p>
                                        </div>
                                        <div class="ms-auto">
                                            <?php if ($canReviewAnyRide && !$driver['has_review']): ?>
                                                <button type="button" class="btn btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#reviewModal" 
                                                        data-driver-id="<?php echo $driver['id']; ?>"
                                                        data-driver-name="<?php echo htmlspecialchars($driver['name']); ?>"
                                                        data-booking-id="<?php echo $completedRides[array_key_first($completedRides)]['booking_id']; ?>">
                                                    <i class="fas fa-star me-1"></i> Évaluer
                                                </button>
                                            <?php elseif ($driver['has_review']): ?>
                                                <span class="badge bg-success p-2"><i class="fas fa-check me-1"></i> Déjà évalué</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary p-2"><i class="fas fa-clock me-1"></i> En attente de fin de trajet</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php 
                            endforeach; 
                            
                            if (!$hasDrivers):
                            ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-car-side text-muted mb-3" style="font-size: 3rem;"></i>
                                    <h3 class="h5">Aucun conducteur trouvé</h3>
                                    <p class="text-muted mb-0">Vous n'avez pas encore de réservations avec des conducteurs.</p>
                                    <div class="mt-4">
                                        <a href="index.php?page=rides" class="btn btn-primary">Trouver un trajet</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Avis déjà donnés -->
            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Vos avis</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php 
                            $hasReviews = false;
                            foreach ($drivers as $driver): 
                                if ($driver['has_review']):
                                    $hasReviews = true;
                                    $review = $driver['review'];
                            ?>
                                <div class="list-group-item p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <?php echo strtoupper(substr($driver['name'], 0, 2)); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <h5 class="mb-0"><?php echo htmlspecialchars($driver['name']); ?></h5>
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
                                                <span class="text-muted ms-2 small">
                                                    <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if(!empty($review['comment'])): ?>
                                        <p class="mb-0 ps-5 ms-3"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                    <?php else: ?>
                                        <p class="text-muted fst-italic mb-0 ps-5 ms-3">Aucun commentaire</p>
                                    <?php endif; ?>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            
                            if (!$hasReviews):
                            ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-comment-slash text-muted mb-3" style="font-size: 3rem;"></i>
                                    <h3 class="h5">Aucun avis donné</h3>
                                    <p class="text-muted mb-0">Vous n'avez pas encore donné d'avis aux conducteurs.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal pour laisser un avis -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Évaluer <span id="driverName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reviewForm" method="POST" action="index.php?page=create-review">
                <div class="modal-body">
                    <input type="hidden" id="recipient_id" name="recipient_id" value="">
                    <input type="hidden" id="booking_id" name="booking_id" value="">
                    
                    <div class="mb-3 text-center">
                        <label class="form-label">Note</label>
                        <div class="rating-stars">
                            <div class="star-rating">
                                <?php for($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="rating-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo ($i == 5) ? 'checked' : ''; ?>>
                                <label for="rating-<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label">Commentaire (optionnel)</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" placeholder="Partagez votre expérience avec ce conducteur..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Envoyer l'avis</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Styles pour les étoiles de notation */
.rating-stars {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    font-size: 1.5rem;
    justify-content: center;
    align-items: center;
}

.star-rating input {
    display: none;
}

.star-rating label {
    color: #ddd;
    cursor: pointer;
    padding: 0 0.2rem;
    transition: color 0.3s;
}

.star-rating input:checked ~ label {
    color: #FFD700;
}

.star-rating label:hover,
.star-rating label:hover ~ label {
    color: #FFD700;
}

/* Animation pour les cartes */
.list-group-item {
    transition: all 0.3s ease;
}

.list-group-item:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Style pour le bouton d'évaluation */
.btn-primary {
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le modal pour laisser un avis
    const reviewModal = document.getElementById('reviewModal');
    if (reviewModal) {
        reviewModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const driverId = button.getAttribute('data-driver-id');
            const driverName = button.getAttribute('data-driver-name');
            const bookingId = button.getAttribute('data-booking-id');
            
            document.getElementById('driverName').textContent = driverName;
            document.getElementById('recipient_id').value = driverId;
            document.getElementById('booking_id').value = bookingId;
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
