<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5><i class="fas fa-car-side me-2"></i>RideGenius</h5>
                <p>La plateforme de covoiturage intelligente qui vous permet de voyager facilement et économiquement.</p>
            </div>
            <div class="col-md-4">
                <h5>Liens rapides</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-white">Accueil</a></li>
                    <li><a href="index.php?page=rides" class="text-white">Trajets disponibles</a></li>
                    <li><a href="index.php?page=search-rides" class="text-white">Rechercher un trajet</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="index.php?page=profile" class="text-white">Mon profil</a></li>
                    <?php else: ?>
                        <li><a href="index.php?page=login" class="text-white">Connexion</a></li>
                        <li><a href="index.php?page=register" class="text-white">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Contact</h5>
                <p><i class="fas fa-envelope me-2"></i>contact@ridegenius.com</p>
                <p><i class="fas fa-phone me-2"></i>+33 1 23 45 67 89</p>
                <div class="mt-3">
                    <a href="#" class="text-white me-2"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                </div>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> RideGenius. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="assets/js/main.js"></script>
</body>
</html>