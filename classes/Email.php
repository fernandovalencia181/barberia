<?php

namespace Classes;

use PHPMailer\PHPMailer\PHPMailer;

class Email {

    public $email;
    public $nombre;
    public $token;

    public function __construct($nombre, $email, $token) {
        $this->nombre = $nombre;
        $this->email = $email;
        $this->token = $token;
    }

    // Genera el host dinámicamente según el entorno
    private function getHost(): string {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        return $protocol . "://{$_SERVER['HTTP_HOST']}";
    }

    public function enviarConfirmacion() {
        $host = $this->getHost();

        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug = 0;  // Mostrar debug detallado (cambiar a 0 en producción)
        $mail->Debugoutput = 'html';

        $mail->Host = $_ENV["SMTP_HOST"];
        $mail->SMTPAuth = true;
        $mail->Port = $_ENV["SMTP_PORT"];
        $mail->Username = $_ENV["SMTP_USER"];
        $mail->Password = $_ENV["SMTP_PASS"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->setFrom($_ENV["SMTP_FROM"], $_ENV["SMTP_FROM_NAME"]);
        $mail->addAddress($this->email, $this->nombre);
        $mail->Subject = "Confirma tu cuenta";

        $mail->isHTML(true);
        $mail->CharSet = "UTF-8";

        $contenido = "<html>";
        $contenido .= "<p><strong>Hola " . htmlspecialchars($this->nombre) . "</strong>, has creado tu cuenta en App Salon, solo debes confirmarla presionando el siguiente enlace:</p>";
        $contenido .= "<p><a href='" . $host . "/confirmar-cuenta?token=" . urlencode($this->token) . "'>Confirmar Cuenta</a></p>";
        $contenido .= "<p>Si no solicitaste esta cuenta, puedes ignorar este mensaje.</p>";
        $contenido .= "</html>";

        $mail->Body = $contenido;

        if (!$mail->send()) {
            echo "Error enviando correo: " . $mail->ErrorInfo;
            exit;
        }

        return true;
    }

    public function enviarInstrucciones() {
        $host = $this->getHost();

        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug = 0;  // Mostrar debug detallado (cambiar a 0 en producción)
        $mail->Debugoutput = 'html';

        $mail->Host = $_ENV["SMTP_HOST"];
        $mail->SMTPAuth = true;
        $mail->Port = $_ENV["SMTP_PORT"];
        $mail->Username = $_ENV["SMTP_USER"];
        $mail->Password = $_ENV["SMTP_PASS"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->setFrom($_ENV["SMTP_FROM"], $_ENV["SMTP_FROM_NAME"]);
        $mail->addAddress($this->email, $this->nombre);
        $mail->Subject = "Reestablece tu password";

        $mail->isHTML(true);
        $mail->CharSet = "UTF-8";

        $contenido = "<html>";
        $contenido .= "<p><strong>Hola " . htmlspecialchars($this->nombre) . "</strong>, has solicitado reestablecer tu password, sigue el siguiente enlace para hacerlo:</p>";
        $contenido .= "<p><a href='" . $host . "/recuperar?token=" . urlencode($this->token) . "'>Reestablecer Password</a></p>";
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
