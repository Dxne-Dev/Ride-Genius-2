<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="h2 mb-4">Notifications</h1>
            
            <?php if(empty($notifications)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-bell-slash text-muted mb-3" style="font-size: 3rem;"></i>
                        <h3 class="h5">Aucune notification</h3>
                        <p class="text-muted mb-0">Vous n'avez pas de notifications non lues pour le moment.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="list-group list-group-flush">
                        <?php foreach($notifications as $notification): ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <?php if($notification['type'] === 'booking_completed'): ?>
                                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-bell"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <p class="text-muted small mb-0">
                                            <?php 
                                                $date = new DateTime($notification['created_at']);
                                                echo $date->format('d/m/Y à H:i');
                                            ?>
                                        </p>
                                    </div>
                                    <div class="ms-auto">
                                        <?php if($notification['type'] === 'booking_completed'): ?>
                                            <a href="index.php?page=driver-reviews" class="btn btn-primary btn-sm">
                                                <i class="fas fa-star me-1"></i> Évaluer
                                            </a>
                                        <?php endif; ?>
                                        <a href="index.php?page=mark-notification-read&id=<?php echo $notification['id']; ?>" class="btn btn-outline-secondary btn-sm ms-2">
                                            <i class="fas fa-check me-1"></i> Marquer comme lu
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
