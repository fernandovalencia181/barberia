<?php

namespace Classes;

use PHPMailer\PHPMailer\PHPMailer;

class Email{

    public $email;
    public $nombre;
    public $token;

    public function __construct($nombre,$email,$token) {
        $this->nombre = $nombre;
        $this->email = $email;
        $this->token = $token;
    }

    public function enviarConfirmacion() {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug = 2;  // Mostrar debug detallado
        $mail->Debugoutput = 'html';

        $mail->Host = $_ENV["EMAIL_HOST"];
        $mail->SMTPAuth = true;
        $mail->Port = $_ENV["EMAIL_PORT"];
        $mail->Username = $_ENV["EMAIL_USER"];
        $mail->Password = $_ENV["EMAIL_PASS"];

        $mail->setFrom("no-reply@appsalon.com", "App Salon");
        $mail->addAddress($this->email, $this->nombre);
        $mail->Subject = "Confirma tu cuenta";

        $mail->isHTML(true);
        $mail->CharSet = "UTF-8";

        $contenido = "<html>";
        $contenido .= "<p><strong>Hola " . htmlspecialchars($this->nombre) . "</strong>, has creado tu cuenta en App Salon, solo debes confirmarla presionando el siguiente enlace:</p>";
        $contenido .= "<p><a href='" . $_ENV["APP_URL"] . "/confirmar-cuenta?token=" . urlencode($this->token) . "'>Confirmar Cuenta</a></p>";
        $contenido .= "<p>Si no solicitaste esta cuenta, puedes ignorar este mensaje.</p>";
        $contenido .= "</html>";

        $mail->Body = $contenido;

        if (!$mail->send()) {
            echo "Error enviando correo: " . $mail->ErrorInfo;
            exit;
        }

        return true;
    }

    public function enviarInstrucciones(){
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug = 2;  // Mostrar debug detallado
        $mail->Debugoutput = 'html';

        $mail->Host = $_ENV["EMAIL_HOST"];
        $mail->SMTPAuth = true;
        $mail->Port = $_ENV["EMAIL_PORT"];
        $mail->Username = $_ENV["EMAIL_USER"];
        $mail->Password = $_ENV["EMAIL_PASS"];

        $mail->setFrom("no-reply@appsalon.com", "App Salon");
        $mail->addAddress($this->email, $this->nombre);
        $mail->Subject = "Reestablece tu password";

        $mail->isHTML(true);
        $mail->CharSet = "UTF-8";

        $contenido = "<html>";
        $contenido .= "<p><strong>Hola " . htmlspecialchars($this->nombre) . "</strong>, has solicitado reestablecer tu password, sigue el siguinete enlace para hacerlo:</p>";
        $contenido .= "<p><a href='" . $_ENV["APP_URL"] . "/recuperar?token=" . urlencode($this->token) . "'>Reestablecer Password</a></p>";
        $contenido .= "<p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>";
        $contenido .= "</html>";

        $mail->Body = $contenido;

        if (!$mail->send()) {
            echo "Error enviando correo: " . $mail->ErrorInfo;
            exit;
        }

        return true;
    }
}