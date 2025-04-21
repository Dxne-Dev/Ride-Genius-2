<?php
// Ce script doit être exécuté quotidiennement via une tâche cron
// Exemple de configuration cron : 0 0 * * * php /chemin/vers/cron/renew_subscriptions.php

// Initialisation de la connexion à la base de données
require_once __DIR__ . '/../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Récupération des modèles nécessaires
require_once __DIR__ . '/../models/Subscription.php';
require_once __DIR__ . '/../models/Wallet.php';

$subscription = new Subscription($db);
$wallet = new Wallet($db);

// Récupération des abonnements à renouveler
$query = "SELECT s.*, u.email 
          FROM subscriptions s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.status = 'active' 
          AND s.auto_renew = 1 
          AND s.end_date <= DATE_ADD(NOW(), INTERVAL 1 DAY)";

$stmt = $db->prepare($query);
$stmt->execute();
$subscriptionsToRenew = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($subscriptionsToRenew as $sub) {
    // Vérification du solde du wallet pour les plans payants
    if ($sub['plan_type'] !== 'eco') {
        $balance = $wallet->getBalance($sub['user_id']);
        if ($balance < $sub['price']) {
            // Envoi d'un email pour informer l'utilisateur
            $to = $sub['email'];
            $subject = "Renouvellement d'abonnement - Solde insuffisant";
            $message = "Bonjour,\n\n";
            $message .= "Votre abonnement " . strtoupper($sub['plan_type']) . " arrive à expiration demain.\n";
            $message .= "Le renouvellement automatique n'a pas pu être effectué car votre solde est insuffisant.\n";
            $message .= "Veuillez recharger votre wallet pour continuer à bénéficier de votre abonnement.\n\n";
            $message .= "Cordialement,\nL'équipe RideGenius";
            
            mail($to, $subject, $message);
            continue;
        }
    }

    // Création du nouvel abonnement
    $newSubscriptionData = [
        'user_id' => $sub['user_id'],
        'plan_type' => $sub['plan_type'],
        'start_date' => $sub['end_date'],
        'end_date' => date('Y-m-d H:i:s', strtotime('+1 month', strtotime($sub['end_date']))),
        'status' => 'active',
        'price' => $sub['price'],
        'auto_renew' => 1
    ];

    if ($subscription->create($newSubscriptionData)) {
        // Débit du wallet pour les plans payants
        if ($sub['plan_type'] !== 'eco') {
            $wallet->debit($sub['user_id'], $sub['price'], 'Renouvellement abonnement ' . strtoupper($sub['plan_type']));
        }

        // Envoi d'un email de confirmation
        $to = $sub['email'];
        $subject = "Renouvellement d'abonnement - Confirmation";
        $message = "Bonjour,\n\n";
        $message .= "Votre abonnement " . strtoupper($sub['plan_type']) . " a été renouvelé avec succès.\n";
        $message .= "Nouvelle période : du " . date('d/m/Y', strtotime($newSubscriptionData['start_date'])) . 
                   " au " . date('d/m/Y', strtotime($newSubscriptionData['end_date'])) . "\n";
        if ($sub['plan_type'] !== 'eco') {
            $message .= "Montant débité : " . number_format($sub['price'], 2) . " FCFA\n";
        }
        $message .= "\nCordialement,\nL'équipe RideGenius";
        
        mail($to, $subject, $message);
    }
}

// Mise à jour du statut des abonnements expirés
$query = "UPDATE subscriptions 
          SET status = 'expired' 
          WHERE status = 'active' 
          AND end_date < NOW()";
$db->exec($query);

echo "Renouvellement des abonnements terminé.\n"; 