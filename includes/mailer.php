<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendVerificationEmail($recipientEmail, $verificationCode) {
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
        $mail->addAddress($recipientEmail); // Vérifiez que $recipientEmail est valide

        // Contenu du mail
        $mail->isHTML(true);
        $mail->Subject = 'Vérification de votre compte';
        $mail->Body = "
            <h2>Code de vérification</h2>
            <p>Votre code de vérification est : <strong>$verificationCode</strong></p>
            <p>Merci de l'utiliser pour activer votre compte.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de l'email : {$mail->ErrorInfo}"); // Log the error
        return "Erreur : Une erreur est survenue lors de l'envoi de l'email.";

    }
}
?>
