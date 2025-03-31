<?php
// Informations de connexion (remplacez par vos propres valeurs)
$host = "localhost";
$db_name = "ride_genius";
$username = "root";
$password = "";

try {
    // Créer une nouvelle connexion PDO
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

    // Définir le mode d'erreur PDO sur exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connexion réussie !";
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}

// Fermer la connexion
$conn = null;
?>