<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des réservations - Administration</title>
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des réservations</h1>
            <a href="index.php?page=admin-dashboard" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>
        </div>

        <!-- Filtres -->
        <div class="card mb-4 shadow-sm glass-card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Rechercher</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                               placeholder="Nom, email...">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Statut</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Tous les statuts</option>
                            <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>En attente</option>
                            <option value="confirmed" <?php echo ($_GET['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Confirmé</option>
                            <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Annulé</option>
                            <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des réservations -->
        <div class="card shadow-sm glass-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Trajet</th>
                                <th>Passager</th>
                                <th>Date</th>
                                <th>Places</th>
                                <th>Prix</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['id']; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($booking['departure']); ?></div>
                                    <div class="text-muted">→ <?php echo htmlspecialchars($booking['destination']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($booking['passenger_name']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($booking['passenger_email']); ?></div>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($booking['departure_time'])); ?></td>
                                <td><?php echo $booking['seats']; ?></td>
                                <td><?php echo $booking['price']; ?> FCFA</td>
                                <td>
                                    <span class="badge bg-<?php 
                                        switch($booking['status']) {
                                            case 'pending': echo 'warning'; break;
                                            case 'confirmed': echo 'success'; break;
                                            case 'cancelled': echo 'danger'; break;
                                            case 'completed': echo 'info'; break;
                                        }
                                    ?>">
                                        <?php 
                                        switch($booking['status']) {
                                            case 'pending': echo 'En attente'; break;
                                            case 'confirmed': echo 'Confirmé'; break;
                                            case 'cancelled': echo 'Annulé'; break;
                                            case 'completed': echo 'Terminé'; break;
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($booking['status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'confirmed')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'cancelled')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Navigation des pages" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $current_page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=admin-bookings&p=<?php echo $i; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&status=<?php echo urlencode($_GET['status'] ?? ''); ?>&date=<?php echo urlencode($_GET['date'] ?? ''); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal des détails de la réservation -->
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de la réservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="bookingDetailsContent"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewBookingDetails(bookingId) {
            fetch('index.php?page=admin-booking-details&booking_id=' + bookingId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const booking = data.booking;
                        const content = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Informations du trajet</h6>
                                    <p><strong>Départ:</strong> ${booking.departure}</p>
                                    <p><strong>Destination:</strong> ${booking.destination}</p>
                                    <p><strong>Date:</strong> ${new Date(booking.departure_time).toLocaleString()}</p>
                                    <p><strong>Places réservées:</strong> ${booking.seats}</p>
                                    <p><strong>Prix total:</strong> ${booking.price} FCFA</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Informations du passager</h6>
                                    <p><strong>Nom:</strong> ${booking.passenger_name}</p>
                                    <p><strong>Email:</strong> ${booking.passenger_email}</p>
                                    <p><strong>Téléphone:</strong> ${booking.passenger_phone || 'Non renseigné'}</p>
                                    <p><strong>Date de réservation:</strong> ${new Date(booking.created_at).toLocaleString()}</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <h6>Informations du conducteur</h6>
                                <p><strong>Nom:</strong> ${booking.driver_name}</p>
                                <p><strong>Email:</strong> ${booking.driver_email}</p>
                                <p><strong>Téléphone:</strong> ${booking.driver_phone || 'Non renseigné'}</p>
                            </div>
                        `;
                        document.getElementById('bookingDetailsContent').innerHTML = content;
                        new bootstrap.Modal(document.getElementById('bookingDetailsModal')).show();
                    } else {
                        alert('Une erreur est survenue lors du chargement des détails.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors du chargement des détails.');
                });
        }

        function updateBookingStatus(bookingId, status) {
            if (confirm('Êtes-vous sûr de vouloir modifier le statut de cette réservation ?')) {
                fetch('index.php?page=admin-update-booking-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        booking_id: bookingId,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Une erreur est survenue lors de la modification du statut.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la modification du statut.');
                });
            }
        }
    </script>
</body>
</html> 