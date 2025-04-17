<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Réservations pour le trajet</h1>
        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="index.php?page=rides" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour aux trajets
            </a>
        <?php else: ?>
            <a href="index.php?page=my-rides" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour à mes trajets
            </a>
        <?php endif; ?>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Détails du trajet</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($ride_details)): ?>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Départ:</strong> <?= htmlspecialchars($ride_details['departure'] ?? 'Non spécifié') ?></p>
                        <p class="mb-2"><strong>Destination:</strong> <?= htmlspecialchars($ride_details['destination'] ?? 'Non spécifié') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2">
                            <strong>Date et heure:</strong> 
                            <?php if(!empty($ride_details['departure_time'])): ?>
                                <?= (new DateTime($ride_details['departure_time']))->format('d/m/Y à H:i') ?>
                            <?php else: ?>
                                Non spécifié
                            <?php endif; ?>
                        </p>
                        <p class="mb-2"><strong>Places disponibles:</strong> <?= $ride_details['available_seats'] ?? 0 ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">Les détails du trajet sont indisponibles</div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card shadow">
        <div class="card-header bg-white">
            <h5 class="mb-0">Liste des réservations</h5>
        </div>
        <div class="card-body">
            <?php if($stmt->rowCount() > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Passager</th>
                                <th>Contact</th>
                                <th>Places</th>
                                <th>Date de réservation</th>
                                <th>Statut</th>
                                <?php if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['passenger_name']); ?></td>
                                    <td>
                                        <?php if($row['status'] === 'accepted' || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                                            <p class="mb-0"><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($row['passenger_email']); ?></p>
                                            <?php if($row['passenger_phone']): ?>
                                                <p class="mb-0"><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($row['passenger_phone']); ?></p>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <small class="text-muted">Visible après acceptation</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['seats']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <?php if($row['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">En attente</span>
                                        <?php elseif($row['status'] === 'accepted'): ?>
                                            <span class="badge bg-success">Acceptée</span>
                                        <?php elseif($row['status'] === 'rejected'): ?>
                                            <span class="badge bg-danger">Rejetée</span>
                                        <?php elseif($row['status'] === 'cancelled'): ?>
                                            <span class="badge bg-danger">Annulée</span>
                                        <?php elseif($row['status'] === 'completed'): ?>
                                            <span class="badge bg-info">Terminée</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
                                        <td>
                                            <?php if($row['status'] === 'pending'): ?>
                                                <div class="btn-group" role="group">
                                                    <a href="index.php?page=update-booking-status&id=<?php echo $row['id']; ?>&status=accepted&return=ride-bookings" class="btn btn-sm btn-success" title="Accepter">
                                                        <i class="fas fa-check"></i> Accepter
                                                    </a>
                                                    <a href="index.php?page=update-booking-status&id=<?php echo $row['id']; ?>&status=rejected&return=ride-bookings" class="btn btn-sm btn-danger" title="Rejeter" onclick="return confirm('Êtes-vous sûr de vouloir rejeter cette réservation ?')">
                                                        <i class="fas fa-times"></i> Rejeter
                                                    </a>
                                                </div>
                                            <?php elseif($row['status'] === 'accepted'): ?>
                                                <a href="index.php?page=update-booking-status&id=<?php echo $row['id']; ?>&status=completed&return=ride-bookings" class="btn btn-sm btn-info" title="Marquer comme terminé">
                                                    <i class="fas fa-flag-checkered"></i> Terminer
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled>Aucune action</button>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                    <p>Aucune réservation pour ce trajet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
