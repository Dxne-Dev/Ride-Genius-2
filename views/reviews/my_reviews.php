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

// S'assurer que l'ID du destinataire est bien défini (utilisateur connecté)
$reviewModel->recipient_id = $user->id;
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
                            echo strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1));
                        ?>
                    </div>
                </div>
                <div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($user->first_name . ' ' . $user->last_name); ?></h4>
                    <?php
                        // On doit recalculer le nombre d'avis et la moyenne, car $reviews est un PDOStatement déjà parcouru plus bas
                        $reviewModelTmp = new Review($db);
                        $reviewModelTmp->recipient_id = $user->id;
                        $rating_data = $reviewModelTmp->getUserRating();
                        $total_reviews = isset($rating_data['total_reviews']) ? (int)$rating_data['total_reviews'] : 0;
                        $average = isset($rating_data['average_rating']) ? round($rating_data['average_rating'], 1) : 0;
                    ?>
                    <?php if($total_reviews > 0): ?>
                        <div class="d-flex align-items-center">
                            <div class="text-warning me-2">
                                <?php 
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
                            <span class="text-muted ms-2">(<?php echo $total_reviews; ?> avis)</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <hr>
            
            <?php
            $reviews_arr = [];
            while($row = $reviews->fetch(PDO::FETCH_ASSOC)) {
                $reviews_arr[] = $row;
            }
            
            // Pagination
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
            $per_page = 4;
            $total_reviews = count($reviews_arr);
            $total_pages = ceil($total_reviews / $per_page);
            $start = ($page - 1) * $per_page;
            $reviews_page = array_slice($reviews_arr, $start, $per_page);

            if($total_reviews > 0) {
                foreach($reviews_page as $idx => $row) {
            ?>
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
                    <p class="mb-0">
                        <?php echo $row['comment'] ? nl2br(html_entity_decode(htmlspecialchars($row['comment'], ENT_QUOTES | ENT_HTML5))) : '<span class="text-muted">Aucun commentaire</span>'; ?>
                    </p>
                </div>
                <?php if($idx < count($reviews_arr) - 1): ?>
                    <hr>
                <?php endif; ?>
            <?php }
            } else { ?>
                <div class="text-center py-4">
                    <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                    <p>Vous n'avez pas encore reçu d'avis de la part des passagers.</p>
                </div>
            <?php } ?>

<?php if($total_pages > 1): ?>
<nav aria-label="Pagination avis" class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item<?php if($page <= 1) echo ' disabled'; ?>">
            <a class="page-link" href="?page=<?php echo $page-1; ?>" tabindex="-1">Précédent</a>
        </li>
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item<?php if($i == $page) echo ' active'; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item<?php if($page >= $total_pages) echo ' disabled'; ?>">
            <a class="page-link" href="?page=<?php echo $page+1; ?>">Suivant</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
