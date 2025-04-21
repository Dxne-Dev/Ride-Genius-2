<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Gestion des trajets</h1>
    
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Trajet</th>
                            <th>Conducteur</th>
                            <th>Date & Heure</th>
                            <th>Places</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($row['departure']); ?></div>
                                    <div class="text-muted small">→ <?php echo htmlspecialchars($row['destination']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($row['driver_name']); ?></td>
                                <td>
                                    <?php 
                                        $departure_time = new DateTime($row['departure_time']);
                                        echo $departure_time->format('d/m/Y H:i');
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['available_seats']); ?></td>
                                <td><?php echo htmlspecialchars($row['price']); ?> FCFA</td>
                                <td>
                                    <?php 
                                        switch($row['status']) {
                                            case 'active':
                                                echo '<span class="badge bg-success">Actif</span>';
                                                break;
                                            case 'completed':
                                                echo '<span class="badge bg-info">Terminé</span>';
                                                break;
                                            case 'cancelled':
                                                echo '<span class="badge bg-danger">Annulé</span>';
                                                break;
                                        }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="index.php?page=ride-details&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="index.php?page=ride-bookings&ride_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info" title="Voir réservations">
                                            <i class="fas fa-list"></i>
                                        </a>
                                        <a href="index.php?page=admin-rides&action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce trajet ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
