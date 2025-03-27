<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Configuration SMTP pour Mailtrap
    $mail->isSMTP();
    $mail->Host = 'sandbox.smtp.mailtrap.io'; // Vérifiez l'hôte
    $mail->SMTPAuth = true;
    $mail->Username = 'ee7edc89662ad2'; // Vérifiez ce champ
    $mail->Password = 'f713757e3577e7'; // Vérifiez ce champ
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Conservez STARTTLS
    $mail->Port = 2525; // Mailtrap utilise 2525

    // Expéditeur et destinataire
    $mail->setFrom('noreply@ridegenius.com', 'RideGenius'); // Adresse valide requise
    $mail->addAddress('test@example.com'); // Change to a valid recipient email for testing

    // Contenu du mail
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email to verify SMTP configuration.';

    $mail->send();
    echo 'Test email sent successfully.';
} catch (Exception $e) {
    echo "Error: {$mail->ErrorInfo}";
}
?>
