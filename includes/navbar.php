<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-car-side me-2"></i>RideGenius
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=rides"><i class="fas fa-route me-1"></i>Trajets disponibles</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=search-rides"><i class="fas fa-search me-1"></i>Rechercher</a>
                </li>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_role'] === 'conducteur'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-steering-wheel me-1"></i>Conducteur
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="index.php?page=create-ride">Proposer un trajet</a></li>
                                <li><a class="dropdown-item" href="index.php?page=my-rides">Mes trajets</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-calendar-alt me-1"></i>Mes réservations
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=my-bookings">Voir mes réservations</a></li>
                            <li><a class="dropdown-item" href="index.php?page=my-reviews">Avis reçus</a></li>
                        </ul>
                    </li>

                    <?php if($_SESSION['user_role'] === 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-shield me-1"></i>Administration
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="index.php?page=admin-dashboard">Tableau de bord</a></li>
                                <li><a class="dropdown-item" href="index.php?page=admin-users">Utilisateurs</a></li>
                                <li><a class="dropdown-item" href="index.php?page=admin-rides">Trajets</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=messages">
                            <i class="fas fa-envelope me-1"></i>Messages
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="index.php?page=profile"><i class="fas fa-user-circle me-1"></i>Mon profil</a></li>
                            <li><a class="dropdown-item" href="index.php?page=edit-profile"><i class="fas fa-user-edit me-1"></i>Modifier profil</a></li>
                            <li><a class="dropdown-item" href="index.php?page=change-password"><i class="fas fa-key me-1"></i>Changer mot de passe</a></li>
                            <li><a class="dropdown-item" href="index.php?page=wallet"><i class="fas fa-wallet me-1"></i>Wallet</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=logout"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=login">Connexion</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=register">Inscription</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if(isset($_SESSION['error'])): ?>
    <div class="container mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['success'])): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>