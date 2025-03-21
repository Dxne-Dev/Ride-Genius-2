<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Modifier mon profil</h4>
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
                                <label for="first_name" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user->first_name); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user->last_name); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user->email); ?>" disabled>
                            <small class="text-muted">L'email ne peut pas être modifié</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user->phone ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Type d'utilisateur</label>
                            <input type="text" class="form-control" value="<?php 
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
                            ?>" disabled>
                            <small class="text-muted">Le type d'utilisateur ne peut pas être modifié</small>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php?page=profile" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>