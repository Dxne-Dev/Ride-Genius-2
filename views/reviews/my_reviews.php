<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php
// views/reviews/my_reviews.php

require_once 'config/database.php';
$database  = new Database();
$db        = $database->getConnection();

require_once 'models/Review.php';
require_once 'models/User.php';

$reviewModel = new Review($db);
$userModel   = new User($db);

$reviews = $reviewModel->readUserReviews();
?>

<div class="container py-5">
    <h1 class="mb-4">Mes avis reçus</h1>
    
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center mb-4">
                <div class="me-4">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 1.5rem;">
                        <?php 
                            $user = new User($db);
                            $user->id = $_SESSION['user_id'];
                            $user->readOne();
                            echo strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1));
                        ?>
                    </div>
                </div>
                <div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($user->first_name . ' ' . $user->last_name); ?></h4>
                    
                    <?php if($rating_data['total_reviews'] > 0): ?>
                        <div class="d-flex align-items-center">
                            <div class="text-warning me-2">
                                <?php 
                                    $average = round($rating_data['average_rating'], 1);
                                    for($i = 1; $i <= 5; $i++) {
                                        if($i <= floor($average)) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif($i == ceil($average) && $average != floor($average)) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                ?>
                            </div>
                            <span class="fw-bold"><?php echo $average; ?>/5</span>
                            <span class="text-muted ms-2">(<?php echo $rating_data['total_reviews']; ?> avis)</span>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucun avis reçu pour le moment</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <hr>
            
            <?php if($stmt->rowCount() > 0): ?>
                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                <h5 class="mb-0"><?php echo htmlspecialchars($row['author_name']); ?></h5>
                                <div class="text-warning">
                                    <?php 
                                        for($i = 1; $i <= 5; $i++) {
                                            if($i <= $row['rating']) {
                                                echo '<i class="fas fa-star"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                    ?>
                                </div>
                            </div>
                            <div class="text-muted">
                                <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                            </div>
                        </div>
                        <?php if(!empty($row['comment'])): ?>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($row['comment'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted fst-italic mb-0">Aucun commentaire</p>
                        <?php endif; ?>
                    </div>
                    <hr>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                    <p>Vous n'avez pas encore reçu d'avis. Les avis apparaîtront ici une fois que d'autres utilisateurs auront laissé leur opinion sur leurs trajets avec vous.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
