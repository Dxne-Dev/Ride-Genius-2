<?php
// views/reviews/admin_reviews.php
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>
<div class="container py-5">
    <h1 class="mb-4">Gestion des avis utilisateurs</h1>
    <div class="card shadow mb-4">
        <div class="card-body">
            <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>
<?php if($total_reviews > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Auteur</th>
                    <th>Destinataire</th>
                    <th>Note</th>
                    <th>Commentaire</th>
                    <th>Date</th>
                    <th>État</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($reviews_page as $i => $row): ?>
                <tr>
                    <td><?php echo $start + $i + 1; ?></td>
                    <td><?php echo htmlspecialchars($row['author_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
                    <td>
                        <span class="text-warning">
                        <?php for($j=1; $j<=5; $j++) {
                            if($j <= $row['rating']) echo '<i class="fas fa-star"></i>';
                            else echo '<i class="far fa-star"></i>';
                        } ?>
                        </span>
                    </td>
                    <td><?php echo $row['comment'] ? html_entity_decode(htmlspecialchars($row['comment'], ENT_QUOTES | ENT_HTML5)) : '<span class="text-muted">Aucun commentaire</span>'; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <?php if($row['is_hidden']): ?>
                            <span class="badge bg-warning text-dark">Masqué</span>
                        <?php else: ?>
                            <span class="badge bg-success">Visible</span>
                        <?php endif; ?>
                        <?php if($row['blocked_until'] && strtotime($row['blocked_until']) > time()): ?>
                            <span class="badge bg-danger">Auteur bloqué<br>(jusqu'au <?php echo date('d/m/Y H:i', strtotime($row['blocked_until'])); ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($row['is_hidden']): ?>
                            <a href="index.php?page=toggle-hide-review&id=<?php echo $row['id']; ?>&hide=0" class="btn btn-sm btn-success">Afficher</a>
                        <?php else: ?>
                            <a href="index.php?page=toggle-hide-review&id=<?php echo $row['id']; ?>&hide=1" class="btn btn-sm btn-warning">Masquer</a>
                        <?php endif; ?>
                        <a href="index.php?page=delete-review&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet avis ? Cette action est définitive.');">Supprimer</a>
                        <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#blockModal<?php echo $row['author_id']; ?>">Bloquer passager</button>
                        <!-- Modal Blocage Passager -->
                        <div class="modal fade" id="blockModal<?php echo $row['author_id']; ?>" tabindex="-1" aria-labelledby="blockModalLabel<?php echo $row['author_id']; ?>" aria-hidden="true">
                          <div class="modal-dialog">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="blockModalLabel<?php echo $row['author_id']; ?>">Bloquer le passager</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                <form method="get" action="index.php">
                                  <input type="hidden" name="page" value="block-author">
                                  <input type="hidden" name="author_id" value="<?php echo $row['author_id']; ?>">
                                  <div class="mb-3">
                                    <label for="until<?php echo $row['author_id']; ?>" class="form-label">Bloquer jusqu'à (laisser vide pour bloquer définitivement)</label>
                                    <input type="datetime-local" class="form-control" name="until" id="until<?php echo $row['author_id']; ?>">
                                  </div>
                                  <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-danger">Bloquer</button>
                                    <a href="index.php?page=block-author&author_id=<?php echo $row['author_id']; ?>" class="btn btn-outline-success">Débloquer</a>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
                </div>
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
            <?php else: ?>
                <p class="text-center">Aucun avis à afficher.</p>
            <?php endif; ?>
        </div>
</div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
