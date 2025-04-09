<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RideGenius - Covoiturage Intelligent</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
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
    
    <!-- Theme detection script - runs early to prevent flash -->
    <script>
        // Check for saved theme preference and apply immediately
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.body.classList.add('dark-mode');
        }
    </script>
</head>
<body>