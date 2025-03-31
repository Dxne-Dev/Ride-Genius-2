<?php
// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=ride_genius', 'root', '');

// Chemin vers le fichier CSV
$fichier_csv = 'C:\wamp64\www\Ride-Genius\city--\worldcities.csv';
// Vérification de l'existence du fichier  
if (!file_exists($fichier_csv)) {
    die("Le fichier CSV n'existe pas.");
}


// Lecture du fichier CSV
$fichier = fopen($fichier_csv, 'r');
if ($fichier) {
    // Ignorer la première ligne (en-tête)
    fgetcsv($fichier);

    // Lire les données ligne par ligne
    while (($ligne = fgetcsv($fichier)) !== false) {
        $nom = $ligne[0]; // city
        $latitude = $ligne[1]; // lat
        $longitude = $ligne[2]; // lng
        $pays = $ligne[3]; // country
        $population = $ligne[4]; // population

        // Insertion des données dans la base de données
        $sql = "INSERT INTO villes (nom, latitude, longitude, pays, population) VALUES (:nom, :latitude, :longitude, :pays, :population)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':pays', $pays);
        $stmt->bindParam(':population', $population);
        $stmt->execute();
    }

    fclose($fichier);
    echo "Données importées avec succès.";
} else {
    echo "Erreur lors de l'ouverture du fichier CSV.";
}
?>