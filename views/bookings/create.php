<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Réserver un trajet</h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5>Détails du trajet</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>De:</strong> <?php echo htmlspecialchars($ride->departure); ?></p>
                                <p class="mb-1"><strong>À:</strong> <?php echo htmlspecialchars($ride->destination); ?></p>
                                <?php $departure_time = new DateTime($ride->departure_time); ?>
                                <p class="mb-0"><strong>Le:</strong> <?php echo $departure_time->format('d/m/Y à H:i'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Conducteur:</strong> <?php echo htmlspecialchars($ride->driver_name); ?></p>
                                <?php if ($driverSubscription === 'eco'): ?>
                                    <p class="mb-1"><strong>Prix par place:</strong> <?php echo number_format($totalPrice, 2); ?> FCFA</p>
                                    <small class="text-muted">
                                        (Prix de base: <?php echo number_format($ride->price, 2); ?> FCFA + Commission: <?php echo number_format($commission['amount'], 2); ?> FCFA)
                                    </small>
                                <?php elseif ($driverSubscription === 'pro'): ?>
                                    <p class="mb-1"><strong>Prix par place:</strong> <?php echo number_format($totalPrice, 2); ?> FCFA</p>
                                    <small class="text-muted">
                                        (Prix de base: <?php echo number_format($ride->price, 2); ?> FCFA + Commission: <?php echo number_format($commission['amount'], 2); ?> FCFA)
                                    </small>
                                <?php else: // business ?>
                                    <p class="mb-1"><strong>Prix par place:</strong> <?php echo number_format($totalPrice, 2); ?> FCFA</p>
                                    <?php if($_SESSION['user_role'] === 'conducteur' || $_SESSION['user_role'] === 'admin'): ?>
                                    <small class="text-muted commission-info">
                                        (Prix de base: <?php echo number_format($ride->price, 2); ?> FCFA + Commission: <?php echo number_format($commission['amount'], 2); ?> FCFA)
                                        <br>
                                        Le conducteur recevra <?php echo number_format($ride->price + ($commission['amount'] * 0.01), 2); ?> FCFA (incluant 1% de la commission)
                                    </small>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <p class="mb-0"><strong>Places disponibles:</strong> <?php echo htmlspecialchars($ride->available_seats); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if(isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="index.php?page=book-ride&ride_id=<?php echo $ride->id; ?>">
                        <div class="mb-3">
                            <label for="seats" class="form-label">Nombre de places à réserver</label>
                            <input type="number" class="form-control" id="seats" name="seats" value="1" min="1" max="<?php echo htmlspecialchars($ride->available_seats); ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Récapitulatif de la réservation</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Prix par place</span>
                                        <span><?php echo number_format($totalPrice, 2); ?> FCFA</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Nombre de places</span>
                                        <span id="seatsCount">1</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total</span>
                                        <span id="totalPrice"><?php echo number_format($totalPrice, 2); ?> FCFA</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            En confirmant cette réservation, vous acceptez les conditions d'utilisation et la politique de confidentialité de RideGenius.
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php?page=ride-details&id=<?php echo $ride->id; ?>" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Confirmer la réservation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script pour mettre à jour le récapitulatif en temps réel
document.addEventListener('DOMContentLoaded', function() {
    const seatsInput = document.getElementById('seats');
    const seatsCount = document.getElementById('seatsCount');
    const totalPrice = document.getElementById('totalPrice');
    const pricePerSeat = <?php echo $totalPrice; ?>;
    
    seatsInput.addEventListener('input', function() {
        const seats = parseInt(this.value) || 0;
        seatsCount.textContent = seats;
        const total = (seats * pricePerSeat).toFixed(2);
        totalPrice.textContent = total + ' FCFA';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
