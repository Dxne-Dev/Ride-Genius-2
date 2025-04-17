<?php
include 'includes/header.php';
include 'includes/navbar.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit();
}

// Vérifier si l'ID de la réservation est fourni
if (!isset($_GET['booking_id'])) {
    header("Location: index.php?page=my-bookings");
    exit();
}

$booking_id = $_GET['booking_id'];

// Récupérer les détails de la réservation
$query = "SELECT b.*, r.departure, r.destination, r.departure_time, r.driver_id,
          CONCAT(u.first_name, ' ', u.last_name) as driver_name
          FROM bookings b
          JOIN rides r ON b.ride_id = r.id
          JOIN users u ON r.driver_id = u.id
          WHERE b.id = ? AND b.passenger_id = ? AND b.status = 'completed'";
$stmt = $db->prepare($query);
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: index.php?page=my-bookings");
    exit();
}
?>

<div class="container py-5">
    <!-- Modal pour l'avis -->
    <div class="modal fade" id="reviewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="reviewModalLabel">
                        <i class="fas fa-star me-2"></i>Évaluer votre trajet avec <?php echo htmlspecialchars($booking['driver_name']); ?>
                    </h5>
                </div>
                <div class="modal-body px-4 py-4">
                    <form id="reviewForm" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
                        <input type="hidden" name="recipient_id" value="<?php echo htmlspecialchars($booking['driver_id']); ?>">
                        
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
                                    placeholder="Comment s'est passé votre trajet ? Le conducteur était-il ponctuel et agréable ?" required></textarea>
                            <div class="invalid-feedback">
                                Veuillez laisser un commentaire
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-secondary" onclick="skipReview()">
                                <i class="fas fa-times me-2"></i>Passer
                            </button>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-paper-plane me-2"></i>Envoyer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast pour les notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="reviewToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-info-circle me-2"></i>
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
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

.modal-content {
    border: none;
}

.form-control:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>

<script>
function skipReview() {
    if (confirm('Êtes-vous sûr de vouloir passer l\'évaluation ?')) {
        window.location.href = 'index.php?page=my-bookings';
    }
}

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Configuration de Toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-bottom-right",
        "timeOut": "3000"
    };

    // Afficher le modal automatiquement
    const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'), {
        backdrop: 'static',
        keyboard: false
    });
    reviewModal.show();
    
    // Validation du formulaire
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
                toastr.success('Merci pour votre avis !');
                setTimeout(() => {
                    window.location.href = 'index.php?page=my-bookings';
                }, 2000);
            } else {
                toastr.error(data.message || 'Une erreur est survenue');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Envoyer';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('Une erreur est survenue');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Envoyer';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?> 