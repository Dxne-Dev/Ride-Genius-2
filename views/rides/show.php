<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h1 class="card-title h3 mb-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <span><?php echo htmlspecialchars($ride->departure); ?> → <?php echo htmlspecialchars($ride->destination); ?></span>
                            <span class="badge bg-primary fs-5"><?php echo htmlspecialchars($totalPrice); ?> €</span>
                        </div>
                    </h1>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                <strong>Date: </strong>
                                <?php 
                                    $departure_time = new DateTime($ride->departure_time);
                                    echo $departure_time->format('d/m/Y');
                                ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-clock me-2 text-primary"></i>
                                <strong>Heure: </strong>
                                <?php echo $departure_time->format('H:i'); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-user me-2 text-primary"></i>
                                <strong>Conducteur: </strong>
                                <?php echo htmlspecialchars($ride->driver_name); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <i class="fas fa-car me-2 text-primary"></i>
                                <strong>Places disponibles: </strong>
                                <?php echo htmlspecialchars($ride->available_seats); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-tag me-2 text-primary"></i>
                                <strong>Statut: </strong>
                                <span class="badge bg-success">Actif</span>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                <strong>Créé le: </strong>
                                <?php 
                                    $created_at = new DateTime($ride->created_at);
                                    echo $created_at->format('d/m/Y');
                                ?>
                            </p>
                        </div>
                    </div>

                    <?php if(!empty($ride->description)): ?>
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p><?php echo nl2br(htmlspecialchars($ride->description)); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="index.php?page=rides" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Retour aux trajets
                        </a>
                        
                        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <div>
                                <a href="index.php?page=ride-bookings&ride_id=<?php echo $ride->id; ?>" class="btn btn-info me-2">
                                    <i class="fas fa-list me-1"></i> Voir les réservations
                                </a>
                                <a href="index.php?page=edit-ride&id=<?php echo $ride->id; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit me-1"></i> Modifier
                                </a>
                            </div>
                        <?php elseif(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $ride->driver_id): ?>
                            <?php if($has_booked): ?>
                                <button class="btn btn-success" disabled>
                                    <i class="fas fa-check me-1"></i> Déjà réservé
                                </button>
                            <?php else: ?>
                                <a href="index.php?page=book-ride&ride_id=<?php echo $ride->id; ?>" class="btn btn-primary">
                                    <i class="fas fa-ticket-alt me-1"></i> Réserver
                                </a>
                            <?php endif; ?>
                            <?php if ($has_booked && isset($booking_details['status']) && $booking_details['status'] === 'completed'): ?>
                                <a href="index.php?page=leave-review&ride_id=<?php echo $ride->id; ?>" class="btn btn-primary">Laisser un avis</a>
                            <?php endif; ?>
                        
                            <div>
                                <a href="index.php?page=ride-bookings&ride_id=<?php echo $ride->id; ?>" class="btn btn-info me-2">
                                    <i class="fas fa-list me-1"></i> Voir les réservations
                                </a>
                                <a href="index.php?page=edit-ride&id=<?php echo $ride->id; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit me-1"></i> Modifier
                                </a>
                            </div>
                        <?php elseif(!isset($_SESSION['user_id'])): ?>
                            <a href="index.php?page=login" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-1"></i> Connectez-vous pour réserver
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title mb-4">Itinéraire</h5>
                    <div class="d-flex flex-column align-items-center mb-4">
                        <div class="d-flex align-items-center w-100">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="ms-3 border-bottom pb-3 w-100">
                                <h6 class="mb-0">Départ</h6>
                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($ride->departure); ?></p>
                            </div>
                        </div>
                        
                        <div class="my-1 text-center text-primary">
                            <i class="fas fa-ellipsis-v"></i>
                            <i class="fas fa-ellipsis-v"></i>
                            <i class="fas fa-ellipsis-v"></i>
                        </div>
                        
                        <div class="d-flex align-items-center w-100">
                            <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-flag-checkered"></i>
                            </div>
                            <div class="ms-3 w-100">
                                <h6 class="mb-0">Arrivée</h6>
                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($ride->destination); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ajouter ici une carte si nécessaire -->
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Informations du conducteur</h5>
                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <div class="mb-3">
                            <p class="mb-2">
                                <i class="fas fa-user me-2 text-primary"></i>
                                <strong>Nom: </strong>
                                <?php echo htmlspecialchars($ride->driver_name); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-envelope me-2 text-primary"></i>
                                <strong>Email: </strong>
                                <?php echo isset($ride->driver_email) ? htmlspecialchars($ride->driver_email) : 'Non renseigné'; ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-phone me-2 text-primary"></i>
                                <strong>Téléphone: </strong>
                                <?php echo isset($ride->driver_phone) ? htmlspecialchars($ride->driver_phone) : 'Non renseigné'; ?>
                            </p>
                        </div>
                    <?php elseif(isset($_SESSION['user_id']) && $ride->driver_id != $_SESSION['user_id']): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Les coordonnées du conducteur seront disponibles après confirmation de votre réservation.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title mb-4">Détails de la réservation</h5>
                    <h5 class="mb-3 border-bottom pb-2">Prix et disponibilité</h5>
                    <div class="row mb-3">
                        <div class="col-7">
                            <p class="mb-1">
                                <strong>Prix:</strong>
                            </p>
                        </div>
                        <div class="col-5 text-end">
                            <p class="mb-1">
                                <?php echo number_format($totalPrice, 2); ?> €
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-7">
                            <p class="mb-1">
                                <strong>Places disponibles:</strong>
                            </p>
                        </div>
                        <div class="col-5 text-end">
                            <p class="mb-1">
                                <?php echo htmlspecialchars($ride->available_seats); ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <div class="row mb-3">
                            <div class="col-7">
                                <p class="mb-1">
                                    <strong>Commission:</strong>
                                </p>
                            </div>
                            <div class="col-5 text-end">
                                <p class="mb-1">
                                    <?php echo number_format($commission['amount'], 2); ?> € (<?php echo $commission['rate']; ?>%)
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
