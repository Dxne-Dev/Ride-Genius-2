<div class="booking-details">
    <h3>Détails de la réservation</h3>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Prix du trajet</h5>
            <p class="card-text">
                Prix de base : <?php echo number_format($ride['price'], 2); ?>€
            </p>
            <?php if ($driverSubscription === 'eco'): ?>
                <p class="card-text text-danger">
                    Commission : <?php echo number_format($commission['amount'], 2); ?>€ (<?php echo $commission['rate']; ?>%)
                </p>
                <p class="card-text">
                    Prix total : <?php echo number_format($ride['price'], 2); ?>€
                </p>
                <small class="text-muted">
                    Note : La commission sera déduite du solde du conducteur
                </small>
            <?php elseif ($driverSubscription === 'pro'): ?>
                <p class="card-text text-info">
                    Commission : <?php echo number_format($commission['amount'], 2); ?>€ (<?php echo $commission['rate']; ?>%)
                </p>
                <p class="card-text">
                    Prix total : <?php echo number_format($totalPrice, 2); ?>€
                </p>
                <small class="text-muted">
                    Note : La commission est incluse dans le prix total
                </small>
            <?php else: ?>
                <p class="card-text text-success">
                    Prix total : <?php echo number_format($ride['price'], 2); ?>€ (sans commission)
                </p>
                <small class="text-muted">
                    Note : Aucune commission n'est appliquée pour les conducteurs BusinessTrajet
                </small>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Votre solde</h5>
            <p class="card-text">
                Solde disponible : <?php echo number_format($passengerBalance, 2); ?>€
            </p>
            <?php if ($passengerBalance < $totalPrice): ?>
                <div class="alert alert-danger">
                    Solde insuffisant. Veuillez recharger votre wallet.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <form action="index.php?page=booking&action=confirm" method="POST" class="mt-4">
        <input type="hidden" name="ride_id" value="<?php echo $ride['id']; ?>">
        <input type="hidden" name="driver_id" value="<?php echo $ride['driver_id']; ?>">
        <input type="hidden" name="amount" value="<?php echo $totalPrice; ?>">
        
        <div class="d-grid gap-2">
            <?php if ($passengerBalance >= $totalPrice): ?>
                <button type="submit" class="btn btn-primary">
                    Confirmer la réservation
                </button>
            <?php else: ?>
                <a href="index.php?page=wallet" class="btn btn-warning">
                    Recharger mon wallet
                </a>
            <?php endif; ?>
        </div>
    </form>
</div> 