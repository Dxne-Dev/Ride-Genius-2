<?php 
include 'includes/header.php'; 
include 'includes/navbar.php'; 
require_once 'config/database.php'; 

$database = new Database();
$db = $database->getConnection();
?>

<div class="container py-5">
    <h1 class="mb-4">Mes réservations</h1>
    
    <div class="card shadow mb-4">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="bookingsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="true">À venir</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab" aria-controls="past" aria-selected="false">Passées</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled" type="button" role="tab" aria-controls="cancelled" aria-selected="false">Annulées</button>
                </li>
            </ul>
        </div>
        
        <div class="card-body">
            <div class="tab-content" id="bookingsTabContent">
                <!-- Réservations à venir -->
                <div class="tab-pane fade show active" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Trajet</th>
                                    <th>Date & Heure</th>
                                    <th>Places</th>
                                    <th>Prix total</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Récupérer les réservations "à venir" pour le passager connecté
                                $query = "SELECT b.*, r.price, r.departure, r.destination, r.departure_time 
                                          FROM bookings b 
                                          JOIN rides r ON b.ride_id = r.id 
                                          WHERE b.passenger_id = :user_id AND b.status IN ('pending', 'accepted')";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $count_upcoming = 0;
                                $now = new DateTime();

                                foreach ($bookings as $row) {
                                    // Convertir la date et l'heure depuis departure_time (format 'Y-m-d H:i:s')
                                    $rideDateTime = new DateTime($row['departure_time']);
                                    $date_string = $rideDateTime->format('d/m/Y');
                                    $time = $rideDateTime->format('H:i');

                                    // Filtrer les réservations à venir : le départ est dans le futur
                                    if ($rideDateTime > $now) {
                                        $count_upcoming++;
                                        $departure = $row['departure'] ?? 'Inconnu';
                                        $destination = $row['destination'] ?? 'Inconnu';
                                        $total_price = $row['price'] * $row['seats'];
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($departure) ?></div>
                                                <div class="text-muted small">→ <?= htmlspecialchars($destination) ?></div>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($date_string) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($time) ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($row['seats']) ?></td>
                                            <td><?= htmlspecialchars($total_price) ?> €</td>
                                            <td>
                                                <?php if ($row['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">En attente</span>
                                                <?php elseif ($row['status'] === 'accepted'): ?>
                                                    <span class="badge bg-success">Confirmée</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="index.php?page=booking-details&id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-outline-primary" title="Voir détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($row['status'] === 'pending' || $row['status'] === 'accepted'): ?>
                                                        <a href="index.php?page=update-booking-status&id=<?= htmlspecialchars($row['id']) ?>&status=cancelled&return=my-bookings" class="btn btn-sm btn-outline-danger" title="Annuler" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                if ($count_upcoming === 0) {
                                    echo '<tr><td colspan="6" class="text-center py-3">Aucune réservation à venir</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Réservations passées -->
                <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Trajet</th>
                                    <th>Date & Heure</th>
                                    <th>Places</th>
                                    <th>Prix total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Récupérer les réservations passées pour le passager connecté
                                $query = "SELECT b.*, r.price, r.departure, r.destination, r.departure_time 
                                          FROM bookings b 
                                          JOIN rides r ON b.ride_id = r.id 
                                          WHERE b.passenger_id = :user_id AND b.status = 'accepted'";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $count_past = 0;

                                foreach ($bookings as $row) {
                                    $rideDateTime = new DateTime($row['departure_time']);
                                    $date_string = $rideDateTime->format('d/m/Y');
                                    $time = $rideDateTime->format('H:i');

                                    // Réservation passée si le départ est antérieur ou égal à maintenant
                                    if ($rideDateTime <= $now) {
                                        $count_past++;
                                        $departure = $row['departure'] ?? 'Inconnu';
                                        $destination = $row['destination'] ?? 'Inconnu';

                                        // Vérifier si un avis a déjà été laissé
                                        $review_query = "SELECT id FROM reviews WHERE booking_id = ? AND author_id = ?";
                                        $review_stmt = $db->prepare($review_query);
                                        $review_stmt->bindParam(1, $row['id']);
                                        $review_stmt->bindParam(2, $_SESSION['user_id']);
                                        $review_stmt->execute();
                                        $has_reviewed = $review_stmt->rowCount() > 0;
                                        
                                        // Récupérer l'ID du conducteur depuis la table rides
                                        $driver_query = "SELECT driver_id FROM rides WHERE id = ?";
                                        $driver_stmt = $db->prepare($driver_query);
                                        $driver_stmt->bindParam(1, $row['ride_id']);
                                        $driver_stmt->execute();
                                        $driver_row = $driver_stmt->fetch(PDO::FETCH_ASSOC);
                                        $driver_id = $driver_row ? $driver_row['driver_id'] : null;
                                        
                                        $total_price = $row['price'] * $row['seats'];
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($departure) ?></div>
                                                <div class="text-muted small">→ <?= htmlspecialchars($destination) ?></div>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($date_string) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($time) ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($row['seats']) ?></td>
                                            <td><?= htmlspecialchars($total_price) ?> €</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="index.php?page=booking-details&id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-outline-primary" title="Voir détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (!$has_reviewed && $driver_id): ?>
                                                        <a href="index.php?page=create-review&booking_id=<?= htmlspecialchars($row['id']) ?>&recipient_id=<?= htmlspecialchars($driver_id) ?>" class="btn btn-sm btn-outline-success" title="Laisser un avis">
                                                            <i class="fas fa-star"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                if ($count_past === 0) {
                                    echo '<tr><td colspan="5" class="text-center py-3">Aucune réservation passée</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Réservations annulées -->
                <div class="tab-pane fade" id="cancelled" role="tabpanel" aria-labelledby="cancelled-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Trajet</th>
                                    <th>Date & Heure</th>
                                    <th>Places</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Récupérer les réservations annulées ou rejetées pour le passager connecté
                                $query = "SELECT b.*, r.departure, r.destination, r.departure_time 
                                          FROM bookings b 
                                          JOIN rides r ON b.ride_id = r.id 
                                          WHERE b.passenger_id = :user_id AND b.status IN ('cancelled', 'rejected')";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $count_cancelled = 0;

                                foreach ($bookings as $row) {
                                    $count_cancelled++;
                                    $departure = $row['departure'] ?? 'Inconnu';
                                    $destination = $row['destination'] ?? 'Inconnu';
                                    $rideDateTime = new DateTime($row['departure_time']);
                                    $date_string = $rideDateTime->format('d/m/Y');
                                    $time = $rideDateTime->format('H:i');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($departure) ?></div>
                                            <div class="text-muted small">→ <?= htmlspecialchars($destination) ?></div>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($date_string) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($time) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($row['seats']) ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'cancelled'): ?>
                                                <span class="badge bg-danger">Annulée</span>
                                            <?php elseif ($row['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger">Rejetée</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="index.php?page=booking-details&id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-outline-primary" title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                if ($count_cancelled === 0) {
                                    echo '<tr><td colspan="5" class="text-center py-3">Aucune réservation annulée</td></tr>';
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
