<?php

namespace Controllers;

use Model\Cita;
use Model\Usuario;
use Model\Servicio;
use Model\CitaServicio;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class APIController {

    // Lista todos los servicios
    public static function index() {
        $servicios = Servicio::all();
        header('Content-Type: application/json');
        echo json_encode($servicios);
    }

    // Guardar una nueva cita
    public static function guardar() {
        iniciarSesion();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        validarCSRF($_POST['csrf_token'] ?? '');

        $usuarioID = (int)($_SESSION["id"] ?? 0);
        if (!$usuarioID) {
            echo json_encode(["error" => "Usuario no autenticado"]);
            return;
        }

        $fecha = $_POST['fecha'] ?? '';
        $hora  = $_POST['hora'] ?? '';
        $barberoID = (int)($_POST['barberoID'] ?? 0);
        $serviciosInput = $_POST['servicios'] ?? '';

        // Validación básica
        $fechas = explode('-', $fecha);
        if (!checkdate($fechas[1] ?? 0, $fechas[2] ?? 0, $fechas[0] ?? 0) ||
            !preg_match('/^\d{2}:\d{2}$/', $hora) ||
            !$barberoID || !$serviciosInput
        ) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos inválidos']);
            return;
        }

        $datos = [
            'usuarioID' => $usuarioID,
            'fecha' => $fecha,
            'hora' => $hora,
            'barberoID' => $barberoID
        ];

        // Guardar cita
        $cita = new Cita($datos);
        $resultado = $cita->guardar();
        $idCita = $resultado['id'];

        // Guardar servicios
        $idServicios = array_map('intval', explode(',', $serviciosInput));
        foreach ($idServicios as $idServicio) {
            $citaServicio = new CitaServicio([
                'citaID' => $idCita,
                'servicioID' => $idServicio,
                'usuarioID' => $usuarioID
            ]);
            $citaServicio->guardar();
        }

        // Datos cliente y barbero
        $usuario = Usuario::find($usuarioID);
        $barbero = Usuario::find($barberoID);

        $nombreCliente = htmlspecialchars($usuario->nombre, ENT_QUOTES, 'UTF-8');
        $telefonoCliente = "34" . ltrim($usuario->telefono, "0");

        $nombreBarbero = htmlspecialchars($barbero->nombre, ENT_QUOTES, 'UTF-8');
        $telefonoBarbero = "34" . ltrim($barbero->telefono, "0");

        // Enviar email
        $respuestaEmail = self::enviarEmailCita($usuario->email, $nombreCliente, $nombreBarbero, $fecha, $hora);
        self::log('email_log.txt', $respuestaEmail, 'Cliente');

        // Enviar WhatsApp
        $respuestaCliente = self::enviarWhatsApp($telefonoCliente, $nombreCliente, $nombreBarbero, $fecha, $hora, 'cliente');
        $respuestaBarbero = self::enviarWhatsApp($telefonoBarbero, $nombreBarbero, $nombreCliente, $fecha, $hora, 'barbero');
        self::log('whatsapp_log.txt', ['Cliente' => $respuestaCliente, 'Barbero' => $respuestaBarbero]);

        header('Content-Type: application/json');
        echo json_encode(['resultado' => $resultado]);
    }

    // Eliminar cita
    public static function eliminar() {
        iniciarSesion();
        if ($_SERVER["REQUEST_METHOD"] !== "POST") return;

        validarCSRF($_POST['csrf_token'] ?? '');
        $id = (int)($_POST["id"] ?? 0);
        $cita = Cita::find($id);
        if ($cita) $cita->eliminar();

        header("Location:" . ($_SERVER["HTTP_REFERER"] ?? '/'));
    }

    // Obtener barberos
    public static function barberos() {
        $barberos = Usuario::findBy(['rol' => 'barbero'], 0);
        header('Content-Type: application/json');
        echo json_encode($barberos);
    }

    // Citas ocupadas por barbero y fecha
    public static function citasOcupadas() {
        $fecha = $_GET['fecha'] ?? '';
        $barberoID = (int)($_GET['barberoID'] ?? 0);
        if (!$fecha || !$barberoID) {
            echo json_encode([]);
            return;
        }

        $citas = Cita::findBy(['fecha' => $fecha, 'barberoID' => $barberoID], 0);
        header('Content-Type: application/json');
        echo json_encode($citas);
    }

    // Citas por fecha
    public static function citasPorFecha() {
        $fecha = $_GET['fecha'] ?? '';
        if (!$fecha) {
            echo json_encode([]);
            return;
        }

        $citas = Cita::findBy(['fecha' => $fecha], 0);
        header('Content-Type: application/json');
        echo json_encode($citas);
    }

    // Actualizar hora de una cita
    public static function actualizarHora() {
        iniciarSesion();
        $data = json_decode(file_get_contents("php://input"), true);
        validarCSRF($data['csrf_token'] ?? '');

        $id = (int)($data['id'] ?? 0);
        $hora = $data['hora'] ?? '';

        if (!$id || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos inválidos']);
            return;
        }

        $cita = Cita::find($id);
        if (!$cita) {
            http_response_code(404);
            echo json_encode(['error' => 'Cita no encontrada']);
            return;
        }

        $cita->hora = $hora;
        $resultado = $cita->guardar();

        if ($resultado['resultado']) {
            echo json_encode(['success' => true, 'hora' => $cita->hora]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo actualizar la hora']);
        }
    }

    // -----------------------
    // Funciones auxiliares
    // -----------------------
    private static function enviarEmailCita($emailCliente, $nombreCliente, $nombreBarbero, $fecha, $hora) {
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
            $mail->addAddress($emailCliente, $nombreCliente);

            $mail->isHTML(true);
            $mail->Subject = "Confirmación de tu cita en Tu Barbería";
            $mail->Body    = "
                <h2>Tu cita ha sido reservada ✅</h2>
                <p><strong>Barbero:</strong> {$nombreBarbero}</p>
                <p><strong>Fecha:</strong> {$fecha}</p>
                <p><strong>Hora:</strong> {$hora}</p>
                <p>¡Te esperamos!</p>
            ";

            $mail->send();
            return ['ok' => true];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $mail->ErrorInfo];
        }
    }

    private static function enviarWhatsApp($telefono, $nombre1, $nombre2, $fecha, $hora, $tipo = "cliente") {
        $token = "TU_TOKEN_DE_ACCESO";
        $phoneNumberId = "TU_PHONE_NUMBER_ID";
        $url = "https://graph.facebook.com/v17.0/$phoneNumberId/messages";

        $templateName = ($tipo === "barbero") ? "nueva_reserva_barbero" : "cita_confirmada";

        $data = [
            "messaging_product" => "whatsapp",
            "to" => $telefono,
            "type" => "template",
            "template" => [
                "name" => $templateName,
                "language" => ["code" => "es"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $nombre1],
                            ["type" => "text", "text" => $nombre2],
                            ["type" => "text", "text" => $fecha],
                            ["type" => "text", "text" => $hora],
                        ]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private static function log($archivo, $data, $tipo = '') {
        $ruta = __DIR__ . "/logs/";
        if (!is_dir($ruta)) mkdir($ruta, 0755, true);
        file_put_contents($ruta . $archivo, date("Y-m-d H:i:s") . " - $tipo: " . json_encode($data) . PHP_EOL, FILE_APPEND);
    }
}
