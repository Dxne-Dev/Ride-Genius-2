<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Trajets disponibles</h1>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <form action="index.php" method="GET" class="d-flex">
                <input type="hidden" name="page" value="search-rides">
                <input type="text" class="form-control me-2" name="departure" placeholder="Départ">
                <input type="text" class="form-control me-2" name="destination" placeholder="Destination">
                <input type="date" class="form-control me-2" name="date" min="<?php echo date('Y-m-d'); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'conducteur'): ?>
                <a href="index.php?page=create-ride" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Proposer un trajet
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row">
        <?php
        $count = 0;
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $departure_time = new DateTime($row['departure_time']);
            $now = new DateTime();
            
            // Ne montrer que les trajets futurs et actifs
            if($departure_time > $now && $row['status'] === 'active') {
                $count++;
        ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['departure']); ?> → <?php echo htmlspecialchars($row['destination']); ?></h5>
                            <span class="badge bg-primary"><?php echo htmlspecialchars($row['price']); ?> €</span>
                        </div>
                        <p class="card-text text-muted mb-1">
                            <i class="fas fa-calendar-alt me-2"></i><?php echo $departure_time->format('d/m/Y'); ?>
                        </p>
                        <p class="card-text text-muted mb-1">
                            <i class="fas fa-clock me-2"></i><?php echo $departure_time->format('H:i'); ?>
                        </p>
                        <p class="card-text text-muted mb-3">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($row['driver_name']); ?>
                        </p>
                        <p class="card-text mb-3">
                            <span class="text-success"><?php echo htmlspecialchars($row['available_seats']); ?> place(s) disponible(s)</span>
                        </p>
                        <a href="index.php?page=ride-details&id=<?php echo $row['id']; ?>" class="btn btn-outline-primary stretched-link">Voir détails</a>
                    </div>
                </div>
            </div>
        <?php
            }
        }
        
        if($count === 0) {
            echo '<div class="col-12"><div class="alert alert-info">Aucun trajet disponible pour le moment.</div></div>';
        }
        ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
