<?php 
include 'includes/header.php'; 
include 'includes/navbar.php'; 
require_once 'config/database.php'; 

$database = new Database();
$db = $database->getConnection();
?>

<div class="container py-5">
    <h1 class="mb-4">Mes réservations</h1>
    
    <div class="card shadow mb-4">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="bookingsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="true">À venir</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab" aria-controls="past" aria-selected="false">Passées</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled" type="button" role="tab" aria-controls="cancelled" aria-selected="false">Annulées</button>
                </li>
            </ul>
        </div>
        
        <div class="card-body">
            <div class="tab-content" id="bookingsTabContent">
                <!-- Réservations à venir -->
                <div class="tab-pane fade show active" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Trajet</th>
                                    <th>Date & Heure</th>
                                    <th>Places</th>
                                    <th>Prix total</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Récupérer les réservations "à venir" pour le passager connecté
                                $query = "SELECT b.*, r.price, r.departure, r.destination, r.departure_time, r.driver_id 
                                          FROM bookings b 
                                          JOIN rides r ON b.ride_id = r.id 
                                          WHERE b.passenger_id = :user_id AND b.status IN ('pending', 'accepted')";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $count_upcoming = 0;
                                $now = new DateTime();

                                foreach ($bookings as $row) {
                                    // Convertir la date et l'heure depuis departure_time (format 'Y-m-d H:i:s')
                                    $rideDateTime = new DateTime($row['departure_time']);
                                    $date_string = $rideDateTime->format('d/m/Y');
                                    $time = $rideDateTime->format('H:i');

                                    // Filtrer les réservations à venir : le départ est dans le futur
                                    if ($rideDateTime > $now) {
                                        $count_upcoming++;
                                        $departure = $row['departure'] ?? 'Inconnu';
                                        $destination = $row['destination'] ?? 'Inconnu';
                                        
                                        // Calculer le prix total en tenant compte de l'abonnement du conducteur
                                        require_once 'models/Subscription.php';
                                        require_once 'models/Commission.php';
                                        $subscription = new Subscription($db);
                                        $commission = new Commission($db);
                                        
                                        $driverSubscription = $subscription->getActiveSubscription($row['driver_id']);
                                        $subscriptionType = $driverSubscription ? $driverSubscription['plan_type'] : 'eco';
                                        
                                        $commissionInfo = $commission->calculateCommission($row['price'], $subscriptionType);
                                        
                                        // Pour les conducteurs ProTrajet, on ajoute la commission au prix affiché
                                        $pricePerSeat = $row['price'];
                                        if ($subscriptionType === 'pro') {
                                            $pricePerSeat = $row['price'] + $commissionInfo['amount'];
                                        }
                                        
                                        $total_price = $pricePerSeat * $row['seats'];
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($departure) ?></div>
                                                <div class="text-muted small">→ <?= htmlspecialchars($destination) ?></div>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($date_string) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($time) ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($row['seats']) ?></td>
                                            <td><?= htmlspecialchars($total_price) ?> FCFA</td>
                                            <td>
                                                <?php if ($row['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">En attente</span>
                                                <?php elseif ($row['status'] === 'accepted'): ?>
                                                    <span class="badge bg-success">Confirmée</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="index.php?page=booking-details&id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-outline-primary" title="Voir détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($row['status'] === 'pending' || $row['status'] === 'accepted'): ?>
                                                        <a href="index.php?page=update-booking-status&id=<?= htmlspecialchars($row['id']) ?>&status=cancelled&return=my-bookings" class="btn btn-sm btn-outline-danger" title="Annuler" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                if ($count_upcoming === 0) {
                                    echo '<tr><td colspan="6" class="text-center py-3">Aucune réservation à venir</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Réservations passées -->
                <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Trajet</th>
                                    <th>Date & Heure</th>
                                    <th>Places</th>
                                    <th>Prix total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Récupérer les réservations passées pour le passager connecté
                                $query = "SELECT b.*, r.price, r.departure, r.destination, r.departure_time, r.driver_id 
                                          FROM bookings b 
                                          JOIN rides r ON b.ride_id = r.id 
                                          WHERE b.passenger_id = :user_id AND b.status IN ('accepted', 'completed')";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $count_past = 0;

                                foreach ($bookings as $row) {
                                    $rideDateTime = new DateTime($row['departure_time']);
                                    $date_string = $rideDateTime->format('d/m/Y');
                                    $time = $rideDateTime->format('H:i');

                                    // Réservation passée si le départ est antérieur ou égal à maintenant
                                    if ($rideDateTime <= $now) {
                                        $count_past++;
                                        $departure = $row['departure'] ?? 'Inconnu';
                                        $destination = $row['destination'] ?? 'Inconnu';

                                        // Vérifier si un avis a déjà été laissé
                                        $review_query = "SELECT id FROM reviews WHERE booking_id = ? AND author_id = ?";
                                        $review_stmt = $db->prepare($review_query);
                                        $review_stmt->bindParam(1, $row['id']);
                                        $review_stmt->bindParam(2, $_SESSION['user_id']);
                                        $review_stmt->execute();
                                        $has_reviewed = $review_stmt->rowCount() > 0;
                                        
                                        // Calculer le prix total en tenant compte de l'abonnement du conducteur
                                        $driver_id = $row['driver_id'];
                                        
                                        $driverSubscription = $subscription->getActiveSubscription($driver_id);
                                        $subscriptionType = $driverSubscription ? $driverSubscription['plan_type'] : 'eco';
                                        
                                        $commissionInfo = $commission->calculateCommission($row['price'], $subscriptionType);
                                        
                                        // Pour les conducteurs ProTrajet, on ajoute la commission au prix affiché
                                        $pricePerSeat = $row['price'];
                                        if ($subscriptionType === 'pro') {
                                            $pricePerSeat = $row['price'] + $commissionInfo['amount'];
                                        }
                                        
                                        $total_price = $pricePerSeat * $row['seats'];
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($departure) ?></div>
                                                <div class="text-muted small">→ <?= htmlspecialchars($destination) ?></div>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($date_string) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($time) ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($row['seats']) ?></td>
                                            <td><?= htmlspecialchars($total_price) ?> FCFA</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="index.php?page=booking-details&id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-outline-primary" title="Voir détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (!$has_reviewed && $driver_id): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-success" title="Laisser un avis" onclick="openReviewModal(<?= htmlspecialchars($row['id']) ?>, <?= htmlspecialchars($driver_id) ?>, '<?= htmlspecialchars($departure) ?>', '<?= htmlspecialchars($destination) ?>', '<?= htmlspecialchars($date_string) ?>', '<?= htmlspecialchars($time) ?>')">
                                                            <i class="fas fa-star"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($row['status'] === 'completed'): ?>
                                                    <span class="badge bg-info mt-2">Terminée</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                if ($count_past === 0) {
                                    echo '<tr><td colspan="5" class="text-center py-3">Aucune réservation passée</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Réservations annulées -->
                <div class="tab-pane fade" id="cancelled" role="tabpanel" aria-labelledby="cancelled-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Trajet</th>
                                    <th>Date & Heure</th>
                                    <th>Places</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Récupérer les réservations annulées ou rejetées pour le passager connecté
                                $query = "SELECT b.*, r.departure, r.destination, r.departure_time 
                                          FROM bookings b 
                                          JOIN rides r ON b.ride_id = r.id 
                                          WHERE b.passenger_id = :user_id AND b.status IN ('cancelled', 'rejected')";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $count_cancelled = 0;

                                foreach ($bookings as $row) {
                                    $count_cancelled++;
                                    $departure = $row['departure'] ?? 'Inconnu';
                                    $destination = $row['destination'] ?? 'Inconnu';
                                    $rideDateTime = new DateTime($row['departure_time']);
                                    $date_string = $rideDateTime->format('d/m/Y');
                                    $time = $rideDateTime->format('H:i');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($departure) ?></div>
                                            <div class="text-muted small">→ <?= htmlspecialchars($destination) ?></div>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($date_string) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($time) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($row['seats']) ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'cancelled'): ?>
                                                <span class="badge bg-danger">Annulée</span>
                                            <?php elseif ($row['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger">Rejetée</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="index.php?page=booking-details&id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-outline-primary" title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                if ($count_cancelled === 0) {
                                    echo '<tr><td colspan="5" class="text-center py-3">Aucune réservation annulée</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- Conteneur pour les toasts -->
<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<!-- Modal pour l'avis -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="reviewModalLabel">
                    <i class="fas fa-star me-2"></i>Évaluer votre trajet
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <div class="mb-3">
                    <p class="mb-1"><strong>Trajet:</strong> <span id="rideDetails"></span></p>
                    <p class="mb-1"><strong>Date:</strong> <span id="rideDate"></span></p>
                    <p class="mb-0"><strong>Heure:</strong> <span id="rideTime"></span></p>
                </div>
                
                <form id="reviewForm" method="POST" action="index.php?page=create-review" class="needs-validation" novalidate>
                    <input type="hidden" name="booking_id" id="bookingId">
                    <input type="hidden" name="recipient_id" id="recipientId">
                    
                    <div class="mb-4 text-center">
                        <label class="form-label fw-bold mb-3">Comment s'est passé votre trajet ?</label>
                        <div class="rating">
                            <?php for($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                        <div class="invalid-feedback">
                            Veuillez attribuer une note
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="comment" class="form-label fw-bold">Partagez votre expérience</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" 
                                placeholder="Comment s'est passé votre trajet ? Le conducteur était-il ponctuel et agréable ?"></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: center;
    gap: 0.5rem;
    margin: 1rem 0;
}

.rating input {
    display: none;
}

.rating label {
    cursor: pointer;
    font-size: 2em;
    color: #dee2e6;
    transition: color 0.2s ease-in-out;
}

.rating input:checked ~ label,
.rating label:hover,
.rating label:hover ~ label {
    color: #ffc107;
}

.rating label:hover,
.rating label:hover ~ label {
    transform: scale(1.1);
}
</style>

<script>
function openReviewModal(bookingId, recipientId, departure, destination, date, time) {
    // Remplir les champs du modal
    document.getElementById('bookingId').value = bookingId;
    document.getElementById('recipientId').value = recipientId;
    document.getElementById('rideDetails').textContent = departure + ' → ' + destination;
    document.getElementById('rideDate').textContent = date;
    document.getElementById('rideTime').textContent = time;
    
    // Réinitialiser le formulaire
    document.getElementById('reviewForm').reset();
    
    // Afficher le modal
    const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
    reviewModal.show();
}

// Validation du formulaire et soumission AJAX
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reviewForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        
        const formData = new FormData(this);
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi en cours...';
        
        fetch('index.php?page=create-review', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès
                const toastContainer = document.querySelector('.toast-container');
                const toastElement = document.createElement('div');
                toastElement.className = 'toast';
                toastElement.setAttribute('role', 'alert');
                toastElement.setAttribute('aria-live', 'assertive');
                toastElement.setAttribute('aria-atomic', 'true');
                toastElement.innerHTML = `
                    <div class="toast-header bg-success text-white">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong class="me-auto">Succès</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${data.message}
                    </div>
                `;
                toastContainer.appendChild(toastElement);
                const toast = new bootstrap.Toast(toastElement);
                toast.show();
                
                // Supprimer le toast après qu'il soit caché
                toastElement.addEventListener('hidden.bs.toast', function() {
                    toastElement.remove();
                });
                
                // Fermer le modal
                const reviewModal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
                reviewModal.hide();
                
                // Recharger la page après un court délai
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                // Afficher un message d'erreur
                const toastContainer = document.querySelector('.toast-container');
                const toastElement = document.createElement('div');
                toastElement.className = 'toast';
                toastElement.setAttribute('role', 'alert');
                toastElement.setAttribute('aria-live', 'assertive');
                toastElement.setAttribute('aria-atomic', 'true');
                toastElement.innerHTML = `
                    <div class="toast-header bg-danger text-white">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong class="me-auto">Erreur</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${data.message}
                    </div>
                `;
                toastContainer.appendChild(toastElement);
                const toast = new bootstrap.Toast(toastElement);
                toast.show();
                
                // Supprimer le toast après qu'il soit caché
                toastElement.addEventListener('hidden.bs.toast', function() {
                    toastElement.remove();
                });
                
                // Réactiver le bouton
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Envoyer';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Afficher un message d'erreur
            const toastContainer = document.querySelector('.toast-container');
            const toastElement = document.createElement('div');
            toastElement.className = 'toast';
            toastElement.setAttribute('role', 'alert');
            toastElement.setAttribute('aria-live', 'assertive');
            toastElement.setAttribute('aria-atomic', 'true');
            toastElement.innerHTML = `
                <div class="toast-header bg-danger text-white">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong class="me-auto">Erreur</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    Une erreur est survenue lors de l'envoi de l'avis.
                </div>
            `;
            toastContainer.appendChild(toastElement);
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Supprimer le toast après qu'il soit caché
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
            
            // Réactiver le bouton
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Envoyer';
        });
    });
});
</script>
