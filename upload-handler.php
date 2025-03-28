<?php
// Vérifier si un fichier a été téléchargé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = __DIR__ . '/uploads/';
    $uploadFile = $uploadDir . basename($_FILES['file']['name']);

    // Vérifier les erreurs
    if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Déplacer le fichier téléchargé vers le dossier uploads
        if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
            echo "Fichier téléchargé avec succès : " . htmlspecialchars($_FILES['file']['name']);
        } else {
            echo "Erreur lors du déplacement du fichier.";
        }
    } else {
        echo "Erreur lors du téléchargement : " . $_FILES['file']['error'];
    }
} else {
    echo "Aucun fichier téléchargé.";
}
?>
