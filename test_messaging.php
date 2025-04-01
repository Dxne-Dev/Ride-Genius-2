<?php
// Initialisation de la session de test
session_start();
$_SESSION['user_id'] = 23;
$_SESSION['jwt_token'] = 'test_jwt_token';

// Configuration de la base de données pour les tests
require 'config/database.php';

// Chargement de l'interface de chat avec les variables de test
require 'test_chat.php';
?>
