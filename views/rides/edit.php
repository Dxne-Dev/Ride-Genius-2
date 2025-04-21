<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Modifier le trajet</h4>
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
                                <input type="text" class="form-control" id="departure" name="departure" value="<?php echo htmlspecialchars($ride->departure); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="destination" class="form-label">Destination</label>
                                <input type="text" class="form-control" id="destination" name="destination" value="<?php echo htmlspecialchars($ride->destination); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="departure_time" class="form-label">Date et heure de départ</label>
                                <input type="datetime-local" class="form-control" id="departure_time" name="departure_time" value="<?php echo date('Y-m-d\TH:i', strtotime($ride->departure_time)); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="available_seats" class="form-label">Nombre de places disponibles</label>
                                <input type="number" class="form-control" id="available_seats" name="available_seats" value="<?php echo htmlspecialchars($ride->available_seats); ?>" min="1" max="8" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Prix par place (FCFA)</label>
                            <input type="number" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($ride->price); ?>" min="1" step="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (optionnel)</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($ride->description ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?php echo $ride->status === 'active' ? 'selected' : ''; ?>>Actif</option>
                                <option value="completed" <?php echo $ride->status === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                                <option value="cancelled" <?php echo $ride->status === 'cancelled' ? 'selected' : ''; ?>>Annulé</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="index.php?page=my-rides" class="btn btn-outline-secondary me-2">Annuler</a>
                                <a href="index.php?page=delete-ride&id=<?php echo $ride->id; ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce trajet ?')">Supprimer</a>
                            </div>
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
