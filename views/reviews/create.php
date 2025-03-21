<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Laisser un avis</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <h5>Détails de la réservation</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Trajet:</strong> <?php echo htmlspecialchars($booking_details['departure']); ?> → <?php echo htmlspecialchars($booking_details['destination']); ?></p>
                                <p class="mb-1"><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($booking_details['departure_time'])); ?></p>
                                <p class="mb-0"><strong>Heure:</strong> <?php echo date('H:i', strtotime($booking_details['departure_time'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>
                                        <?php echo $_GET['recipient_id'] == $booking_details['driver_id'] ? 'Conducteur:' : 'Passager:'; ?>
                                    </strong> 
                                    <?php echo $_GET['recipient_id'] == $booking_details['driver_id'] ? htmlspecialchars($booking_details['driver_name']) : htmlspecialchars($booking_details['passenger_name']); ?>
                                </p>
                                <p class="mb-0"><strong>Places réservées:</strong> <?php echo htmlspecialchars($booking_details['seats']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label">Note</label>
                            <div class="rating">
                                <div class="btn-group" role="group">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" class="btn-check" name="rating" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo (isset($_POST['rating']) && $_POST['rating'] == $i) ? 'checked' : ''; ?> required>
                                        <label class="btn btn-outline-warning" for="rating<?php echo $i; ?>">
                                            <?php echo $i; ?> <i class="fas fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="comment" class="form-label">Commentaire (optionnel)</label>
                            <textarea class="form-control" id="comment" name="comment" rows="4"><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php?page=my-bookings" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Envoyer l'avis</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
