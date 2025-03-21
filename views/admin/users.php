<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Gestion des utilisateurs</h1>
    
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Rôle</th>
                            <th>Date d'inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo $row['phone'] ? htmlspecialchars($row['phone']) : '<span class="text-muted">Non renseigné</span>'; ?></td>
                                <td>
                                    <?php 
                                        switch($row['role']) {
                                            case 'passager':
                                                echo '<span class="badge bg-info">Passager</span>';
                                                break;
                                            case 'conducteur':
                                                echo '<span class="badge bg-success">Conducteur</span>';
                                                break;
                                            case 'admin':
                                                echo '<span class="badge bg-danger">Admin</span>';
                                                break;
                                        }
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="#" class="btn btn-sm btn-outline-primary" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if($row['id'] != $_SESSION['user_id']): ?>
                                            <a href="index.php?page=admin-users&action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
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
