<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RideGenius - Covoiturage Intelligent</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Theme CSS -->
    <link href="assets/css/theme.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
    <!-- Wallet CSS -->
    <link href="assets/css/wallet.css" rel="stylesheet">
    <!-- Destinations CSS -->
    <link href="assets/css/destinations.css" rel="stylesheet">
    <!-- Navbar Fix CSS - pour éviter que le contenu soit masqué par la navbar fixe -->
    <link href="assets/css/navbar-fix.css" rel="stylesheet">
    <!-- Navbar Alignment CSS - pour améliorer l'alignement et l'aspect visuel de la navbar -->
    <link href="assets/css/navbar-alignment.css" rel="stylesheet">
    <!-- Role Specific CSS - pour les styles spécifiques aux rôles d'utilisateurs -->
    <link href="assets/css/role-specific.css" rel="stylesheet">
    
    <!-- Bootstrap JS Bundle avec Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script de notifications -->
    <script src="assets/js/notifications.js"></script>
    
    <!-- Theme detection script - runs early to prevent flash -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check for saved theme preference and apply immediately
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.classList.add('dark-mode');
                document.body.classList.add('dark-mode');
            }
        });
    </script>
</head>
<body <?php echo isset($_SESSION['user_role']) ? 'data-role="' . htmlspecialchars($_SESSION['user_role']) . '"' : ''; ?>>