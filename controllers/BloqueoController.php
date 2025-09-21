<?php

namespace Controllers;

use MVC\Router;
use Model\Bloqueo;
use Model\Usuario;

class BloqueoController {

    // ---------------------------
    // Mostrar vista de bloqueos
    // ---------------------------
    public static function index(Router $router) {
        iniciarSesion();
        isAdmin(); // Valida rol admin

        $nombre = $_SESSION["nombre"] ?? "";
        $bloqueos = Bloqueo::all();
        $barberos = Usuario::findBy(['rol' => 'barbero'], 0);

        $router->render("bloqueos/index", [
            "nombre" => $nombre,
            "bloqueos" => $bloqueos,
            "barberos" => $barberos
        ]);
    }

    // ---------------------------
    // Crear un bloqueo
    // ---------------------------
    public static function crear() {
        iniciarSesion();
        isAdmin();

        if ($_SERVER["REQUEST_METHOD"] !== 'POST') return;

        validarCSRF($_POST['csrf_token'] ?? '');
        $args = $_POST;

        $barberoID = $args['barberoID'] ?? null; // puede ser "todos"
        $fecha     = $args['fecha'] ?? null;
        $hora      = $args['hora'] ?: null;       // null = todo el día
        $motivo    = $args['motivo'] ?? '';

        if (!$fecha) {
            header("Location: /bloqueos");
            exit;
        }

        if ($barberoID === "todos" || $barberoID === null) {
            // Bloqueo para todos los barberos
            $barberos = Usuario::findBy(['rol' => 'barbero'], 0);
            foreach ($barberos as $barbero) {
                $bloqueo = new Bloqueo([
                    'barberoID' => $barbero->id,
                    'fecha'     => $fecha,
                    'hora'      => $hora,
                    'motivo'    => $motivo
                ]);
                $bloqueo->guardar();
            }
        } else {
            // Bloqueo individual
            $barberoID = (int)$barberoID;
            $bloqueo = new Bloqueo([
                'barberoID' => $barberoID,
                'fecha'     => $fecha,
                'hora'      => $hora,
                'motivo'    => $motivo
            ]);
            $bloqueo->guardar();
        }

        header("Location: /bloqueos");
        exit;
    }

    // ---------------------------
    // Obtener bloqueos (JSON)
    // ---------------------------
    public static function obtener() {
        iniciarSesion();

        $barberoID = isset($_GET['barberoID']) ? ($_GET['barberoID'] !== 'todos' ? (int)$_GET['barberoID'] : null) : null;
        $fecha     = $_GET['fecha'] ?? null;

        $bloqueos = Bloqueo::obtenerBloqueos($barberoID, $fecha);

        header('Content-Type: application/json');
        echo json_encode($bloqueos);
        exit;
    }

    // ---------------------------
    // Eliminar un bloqueo
    // ---------------------------
    public static function eliminar() {
        iniciarSesion();
        isAdmin();

        if ($_SERVER["REQUEST_METHOD"] !== 'POST') return;

        validarCSRF($_POST['csrf_token'] ?? '');
        $id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;

        /** @var \Model\Bloqueo|null $bloqueo */
        $bloqueo = Bloqueo::find($id);

        if ($bloqueo) {
            $bloqueo->eliminar();
        }

        header("Location: /bloqueos");
        exit;
    }
}
