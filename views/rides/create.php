<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Proposer un nouveau trajet</h4>
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
                    
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="departure" class="form-label">Lieu de départ</label>
                                <input type="text" class="form-control" id="departure" name="departure" value="<?php echo isset($_POST['departure']) ? htmlspecialchars($_POST['departure']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="destination" class="form-label">Destination</label>
                                <input type="text" class="form-control" id="destination" name="destination" value="<?php echo isset($_POST['destination']) ? htmlspecialchars($_POST['destination']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="departure_time" class="form-label">Date et heure de départ</label>
                                <input type="datetime-local" class="form-control" id="departure_time" name="departure_time" value="<?php echo isset($_POST['departure_time']) ? htmlspecialchars($_POST['departure_time']) : ''; ?>" min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="available_seats" class="form-label">Nombre de places disponibles</label>
                                <input type="number" class="form-control" id="available_seats" name="available_seats" value="<?php echo isset($_POST['available_seats']) ? htmlspecialchars($_POST['available_seats']) : '1'; ?>" min="1" max="8" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Prix par place (FCFA)</label>
                            <input type="number" class="form-control" id="price" name="price" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" min="1" step="0.01" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="form-label">Description (optionnel)</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <small class="text-muted">Ajoutez des informations supplémentaires sur votre trajet, comme les points d'arrêt possibles, les conditions particulières, etc.</small>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php?page=my-rides" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Publier le trajet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
