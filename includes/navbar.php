<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-car-side me-2"></i>RideGenius
        </a>
        <div class="d-flex align-items-center">
            <!-- Theme Toggle Mobile -->
            <div class="theme-switch-wrapper d-lg-none me-2">
                <label class="switch" title="Changer le thème">
                    <span class="sun"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="#ffd43b"><circle r="5" cy="12" cx="12"></circle><path d="m21 13h-1a1 1 0 0 1 0-2h1a1 1 0 0 1 0 2zm-17 0h-1a1 1 0 0 1 0-2h1a1 1 0 0 1 0 2zm13.66-5.66a1 1 0 0 1 -.66-.29 1 1 0 0 1 0-1.41l.71-.71a1 1 0 1 1 1.41 1.41l-.71.71a1 1 0 0 1 -.75.29zm-12.02 12.02a1 1 0 0 1 -.71-.29 1 1 0 0 1 0-1.41l.71-.66a1 1 0 0 1 1.41 1.41l-.71.71a1 1 0 0 1 -.7.24zm6.36-14.36a1 1 0 0 1 -1-1v-1a1 1 0 0 1 2 0v1a1 1 0 0 1 -1 1zm0 17a1 1 0 0 1 -1-1v-1a1 1 0 0 1 2 0v1a1 1 0 0 1 -1 1zm-5.66-14.66a1 1 0 0 1 -.7-.29l-.71-.71a1 1 0 0 1 1.41-1.41l.71.71a1 1 0 0 1 0 1.41 1 1 0 0 1 -.71.29zm12.02 12.02a1 1 0 0 1 -.7-.29l-.66-.71a1 1 0 0 1 1.36-1.36l.71.71a1 1 0 0 1 0 1.41 1 1 0 0 1 -.71.24z"></path></g></svg></span>
                    <span class="moon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="m223.5 32c-123.5 0-223.5 100.3-223.5 224s100 224 223.5 224c60.6 0 115.5-24.2 155.8-63.4 5-4.9 6.3-12.5 3.1-18.7s-10.1-9.7-17-8.5c-9.8 1.7-19.8 2.6-30.1 2.6-96.9 0-175.5-78.8-175.5-176 0-65.8 36-123.1 89.3-153.3 6.1-3.5 9.2-10.5 7.7-17.3s-7.3-11.9-14.3-12.5c-6.3-.5-12.6-.8-19-.8z"></path></svg></span>   
                    <input type="checkbox" class="input" id="theme-toggle-mobile">
                    <span class="slider"></span>
                </label>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <?php if(isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <!-- Navbar pour l'administrateur -->
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin-dashboard"><i class="fas fa-tachometer-alt me-1"></i>Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin-users"><i class="fas fa-users me-1"></i>Utilisateurs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin-rides"><i class="fas fa-car me-1"></i>Trajets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=wallet"><i class="fas fa-wallet me-1"></i>Wallet</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=messages"><i class="fas fa-envelope me-1"></i>Messages</a>
                    </li>
                <?php else: ?>
                    <!-- Navbar pour les utilisateurs normaux -->
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=rides"><i class="fas fa-route me-1"></i>Trajets disponibles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=search-rides"><i class="fas fa-search me-1"></i>Rechercher</a>
                    </li>

                    <?php if(isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'conducteur'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-steering-wheel me-1"></i>Conducteur
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="index.php?page=create-ride">Proposer un trajet</a></li>
                                <li><a class="dropdown-item" href="index.php?page=my-rides">Mes trajets</a></li>
                                <li><a class="dropdown-item" href="index.php?page=my-reviews">Avis reçus</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=wallet">
                                <i class="fas fa-wallet me-1"></i>Wallet
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'passager'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-calendar-alt me-1"></i>Mes réservations
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="index.php?page=my-bookings">Voir mes réservations</a></li>
                                <li><a class="dropdown-item" href="index.php?page=my-reviews">Avis reçus</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=wallet">
                                <i class="fas fa-wallet me-1"></i>Wallet
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav align-items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if(!(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=messages">
                                <i class="fas fa-envelope me-1"></i>Messages
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?php if(isset($_SESSION['first_name']) && isset($_SESSION['last_name'])): ?>
                                <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="index.php?page=profile"><i class="fas fa-user-circle me-1"></i>Mon profil</a></li>
                            <?php if(!(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                                <li><a class="dropdown-item" href="index.php?page=edit-profile"><i class="fas fa-user-edit me-1"></i>Modifier profil</a></li>
                                <li><a class="dropdown-item" href="index.php?page=change-password"><i class="fas fa-key me-1"></i>Changer mot de passe</a></li>
                            <?php endif; ?>
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
                <!-- Theme Toggle Desktop -->
                <li class="nav-item d-none d-lg-flex align-items-center ms-3">
                    <label class="switch" title="Changer le thème">
                        <span class="sun"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="#ffd43b"><circle r="5" cy="12" cx="12"></circle><path d="m21 13h-1a1 1 0 0 1 0-2h1a1 1 0 0 1 0 2zm-17 0h-1a1 1 0 0 1 0-2h1a1 1 0 0 1 0 2zm13.66-5.66a1 1 0 0 1 -.66-.29 1 1 0 0 1 0-1.41l.71-.71a1 1 0 1 1 1.41 1.41l-.71.71a1 1 0 0 1 -.75.29zm-12.02 12.02a1 1 0 0 1 -.71-.29 1 1 0 0 1 0-1.41l.71-.66a1 1 0 0 1 1.41 1.41l-.71.71a1 1 0 0 1 -.7.24zm6.36-14.36a1 1 0 0 1 -1-1v-1a1 1 0 0 1 2 0v1a1 1 0 0 1 -1 1zm0 17a1 1 0 0 1 -1-1v-1a1 1 0 0 1 2 0v1a1 1 0 0 1 -1 1zm-5.66-14.66a1 1 0 0 1 -.7-.29l-.71-.71a1 1 0 0 1 1.41-1.41l.71.71a1 1 0 0 1 0 1.41 1 1 0 0 1 -.71.29zm12.02 12.02a1 1 0 0 1 -.7-.29l-.66-.71a1 1 0 0 1 1.36-1.36l.71.71a1 1 0 0 1 0 1.41 1 1 0 0 1 -.71.24z"></path></g></svg></span>
                        <span class="moon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="m223.5 32c-123.5 0-223.5 100.3-223.5 224s100 224 223.5 224c60.6 0 115.5-24.2 155.8-63.4 5-4.9 6.3-12.5 3.1-18.7s-10.1-9.7-17-8.5c-9.8 1.7-19.8 2.6-30.1 2.6-96.9 0-175.5-78.8-175.5-176 0-65.8 36-123.1 89.3-153.3 6.1-3.5 9.2-10.5 7.7-17.3s-7.3-11.9-14.3-12.5c-6.3-.5-12.6-.8-19-.8z"></path></svg></span>   
                        <input type="checkbox" class="input" id="theme-toggle">
                        <span class="slider"></span>
                    </label>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Theme Toggle Script -->
<script src="assets/js/theme.js"></script>

<?php if(isset($_SESSION['error'])): ?>
    <div class="container" style="margin-top: 80px;">
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
    <div class="container" style="margin-top: 80px;">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>