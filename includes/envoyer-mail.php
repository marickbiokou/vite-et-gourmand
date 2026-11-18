<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

// Fonction commune d'envoi des e-mails

function envoyerMail(
    string $destinataire,
    string $sujet,
    string $message,
    ?string $adresseReponse = null
): bool
{
    try {

        // Chargement de la configuration

        $config = require __DIR__ . '/../config/mail.php';

        // Initialisation de PHPMailer

        $mail = new PHPMailer(true);

        // Configuration SMTP

        $mail->isSMTP();

        $mail->Host = $config['host'];
        $mail->Port = $config['port'];

        $mail->SMTPAuth = true;

        $mail->Username = $config['username'];
        $mail->Password = $config['password'];

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->CharSet = 'UTF-8';

        $mail->Timeout = 10;

        // Expéditeur et destinataire

        $mail->setFrom(
            $config['from_email'],
            $config['from_name']
        );

        $mail->addAddress($destinataire);

        // Adresse de réponse facultative

        if ($adresseReponse !== null) {

            $mail->addReplyTo($adresseReponse);
        }

        // Contenu de l'e-mail

        $mail->isHTML(false);

        $mail->Subject = $sujet;
        $mail->Body = $message;

        // Envoi du message

        return $mail->send();

    } catch (Throwable $e) {

        // Enregistrement de l'erreur côté serveur

        error_log('Erreur e-mail : ' . $e->getMessage());

        return false;
    }
}