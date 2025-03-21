<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Mes trajets</h1>
        <a href="index.php?page=create-ride" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Proposer un nouveau trajet
        </a>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="ridesTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="true">À venir</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab" aria-controls="past" aria-selected="false">Passés</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled" type="button" role="tab" aria-controls="cancelled" aria-selected="false">Annulés</button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="ridesTabContent">
                <!-- À venir -->
                <div class="tab-pane fade show active" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Trajet</th>
                                    <th>Date & Heure</th>
                                    <th>Places</th>
                                    <th>Prix</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $count_upcoming = 0;
                                $stmt->execute(); // Reset the cursor
                                
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $departure_time = new DateTime($row['departure_time']);
                                    $now = new DateTime();
                                    
                                    if($departure_time > $now && $row['status'] === 'active') {
                                        $count_upcoming++;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($row['departure']); ?></div>
                                            <div class="text-muted small">→ <?php echo htmlspecialchars($row['destination']); ?></div>
                                        </td>
                                        <td>
                                            <div><?php echo $departure_time->format('d/m/Y'); ?></div>
                                            <div class="text-muted small"><?php echo $departure_time->format('H:i'); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['available_seats']); ?></td>
                                        <td><?php echo htmlspecialchars($row['price']); ?> €</td>
                                        <td><span class="badge bg-success">Actif</span></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="index.php?page=ride-details&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="index.php?page=edit-ride&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="index.php?page=ride-bookings&ride_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info" title="Voir réservations">
                                                    <i class="fas fa-list"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                    }
                                }
                                
                                if($count_upcoming === 0) {
                                    echo '<tr><td colspan="6" class="text-center py-3">Aucun trajet à venir</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Passés -->
                <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Trajet</th>
                                    <th>Date & Heure</th>
                                    <th>Places</th>
                                    <th>Prix</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $count_past = 0;
                                $stmt->execute(); // Reset the cursor
                                
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $departure_time = new DateTime($row['departure_time']);
                                    $now = new DateTime();
                                    
                                    if($departure_time <= $now && $row['status'] !== 'cancelled') {
                                        $count_past++;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($row['departure']); ?></div>
                                            <div class="text-muted small">→ <?php echo htmlspecialchars($row['destination']); ?></div>
                                        </td>
                                        <td>
                                            <div><?php echo $departure_time->format('d/m/Y'); ?></div>
                                            <div class="text-muted small"><?php echo $departure_time->format('H:i'); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['available_seats']); ?></td>
                                        <td><?php echo htmlspecialchars($row['price']); ?> €</td>
                                        <td>
                                            <?php if($row['status'] === 'completed'): ?>
                                                <span class="badge bg-info">Terminé</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Passé</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="index.php?page=ride-details&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="index.php?page=ride-bookings&ride_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info" title="Voir réservations">
                                                    <i class="fas fa-list"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                    }
                                }
                                
                                if($count_past === 0) {
                                    echo '<tr><td colspan="6" class="text-center py-3">Aucun trajet passé</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Annulés -->
                <div class="tab-pane fade" id="cancelled" role="tabpanel" aria-labelledby="cancelled-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Trajet</th>
                                    <th>Date & Heure</th>
                                    <th>Places</th>
                                    <th>Prix</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $count_cancelled = 0;
                                $stmt->execute(); // Reset the cursor
                                
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    if($row['status'] === 'cancelled') {
                                        $count_cancelled++;
                                        $departure_time = new DateTime($row['departure_time']);
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($row['departure']); ?></div>
                                            <div class="text-muted small">→ <?php echo htmlspecialchars($row['destination']); ?></div>
                                        </td>
                                        <td>
                                            <div><?php echo $departure_time->format('d/m/Y'); ?></div>
                                            <div class="text-muted small"><?php echo $departure_time->format('H:i'); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['available_seats']); ?></td>
                                        <td><?php echo htmlspecialchars($row['price']); ?> €</td>
                                        <td><span class="badge bg-danger">Annulé</span></td>
                                        <td>
                                            <a href="index.php?page=ride-details&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php
                                    }
                                }
                                
                                if($count_cancelled === 0) {
                                    echo '<tr><td colspan="6" class="text-center py-3">Aucun trajet annulé</td></tr>';
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

<?php include 'includes/footer.php'; ?>
