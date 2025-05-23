<div class="booking-details">
    <h3>Détails de la réservation</h3>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Prix du trajet</h5>
            <div class="row">
                <div class="col-7">
                    <p class="card-text">Prix de base :</p>
                </div>
                <div class="col-5 text-end">
                    <p class="card-text"><?php echo number_format($ride['price'], 2); ?> FCFA</p>
                </div>
            </div>
            <?php if ($driverSubscription === 'eco'): ?>
                <div class="row">
                    <div class="col-7">
                        <p class="card-text text-danger">Commission :</p>
                    </div>
                    <div class="col-5 text-end">
                        <p class="card-text text-danger"><?php echo number_format($commission['amount'], 2); ?> FCFA (<?php echo $commission['rate']; ?>%)</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-7">
                        <p class="card-text">Prix total :</p>
                    </div>
                    <div class="col-5 text-end">
                        <p class="card-text fw-bold"><?php echo number_format($ride['price'], 2); ?> FCFA</p>
                    </div>
                </div>
                <small class="text-muted">
                    Note : La commission sera déduite du solde du conducteur
                </small>
            <?php elseif ($driverSubscription === 'pro'): ?>
                <div class="row">
                    <div class="col-7">
                        <p class="card-text text-info">Commission :</p>
                    </div>
                    <div class="col-5 text-end">
                        <p class="card-text text-info"><?php echo number_format($commission['amount'], 2); ?> FCFA (<?php echo $commission['rate']; ?>%)</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-7">
                        <p class="card-text">Prix total :</p>
                    </div>
                    <div class="col-5 text-end">
                        <p class="card-text fw-bold"><?php echo number_format($totalPrice, 2); ?> FCFA</p>
                    </div>
                </div>
                <small class="text-muted">
                    Note : La commission est incluse dans le prix total
                </small>
            <?php else: ?>
                <div class="row">
                    <div class="col-7">
                        <p class="card-text text-success">Prix total :</p>
                    </div>
                    <div class="col-5 text-end">
                        <p class="card-text text-success fw-bold"><?php echo number_format($ride['price'], 2); ?> FCFA (sans commission)</p>
                    </div>
                </div>
                <small class="text-muted">
                    Note : Aucune commission n'est appliquée pour les conducteurs BusinessTrajet
                </small>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Votre solde</h5>
            <div class="row">
                <div class="col-7">
                    <p class="card-text">Solde disponible :</p>
                </div>
                <div class="col-5 text-end">
                    <p class="card-text"><?php echo number_format($passengerBalance, 2); ?> FCFA</p>
                </div>
            </div>
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