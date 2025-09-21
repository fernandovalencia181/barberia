<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function debuguear($variable) : string {
    echo "<pre>";
    var_dump($variable);
    echo "</pre>";
    exit;
}

// Escapa / Sanitizar el HTML
function s($html) : string {
    $s = htmlspecialchars($html);
    return $s;
}

function esUltimo(string $actual,string $proximo):bool{
    if($actual!==$proximo){
        return true;
    }
    return false;
}

// Función que revisa que el usuario esté autenticado
function isAuth():void{
    if(!isset($_SESSION["login"]) || $_SESSION["login"] !== true){
        header("Location: /");
    }
}

function isAdmin(): void {
    if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
        header("Location: /");
        exit;
    }
}

function isBarbero(): void {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'barbero') {
        header('Location: /');
        exit;
    }
}

function redirectSegunRol($rol) {
    $map = [
        "admin" => "/admin",
        "barbero" => "/barbero",
        "cliente" => "/cita"
    ];
    header("Location: " . ($map[$rol] ?? "/cita"));
    exit;
}

function generarTokenCSRF(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarCSRF($tokenRecibido) {
    if (!isset($_SESSION['csrf_token']) || $tokenRecibido !== $_SESSION['csrf_token']) {
        header("HTTP/1.1 403 Forbidden");
        exit("Error: Token CSRF inválido.");
    }
}

function isPostRequest(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function enviarEmailRecordatorio($email, $cliente, $barbero, $fecha, $hora) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'];

        $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($email, $cliente);

        $mail->isHTML(true);
        $mail->Subject = "Recordatorio de tu cita en Barbería";
        $mail->Body = "
            <p>Hola {$cliente},</p>
            <p>Te recordamos que tienes una cita:</p>
            <ul>
                <li>Fecha: {$fecha}</li>
                <li>Hora: {$hora}</li>
                <li>Barbero: {$barbero}</li>
            </ul>
            <p>¡No faltes!</p>
        ";

        $mail->send();
        return ['ok' => true];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $mail->ErrorInfo];
    }
}