<?php

namespace Controllers;

use MVC\Router;
use Model\Usuario;
use Model\AdminCita;

class BarberoController {

    // ---------------------------
    // Dashboard del barbero
    // ---------------------------
    public static function dashboard(Router $router) {
        iniciarSesion();
        isBarbero(); // Valida rol

        $nombre = $_SESSION["nombre"] ?? "";
        $barberoID = $_SESSION['id'];
        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        // Validación de fecha
        $fechas = explode('-', $fecha);
        if (!checkdate($fechas[1], $fechas[2], $fechas[0])) {
            header("Location: /404");
            exit;
        }

        $citas = AdminCita::citasPorBarbero($barberoID, $fecha);

        $router->render('barbero/dashboard', [
            'nombre' => $nombre,
            'citas' => $citas,
            'fecha' => $fecha
        ]);
    }

    // ---------------------------
    // Listado de barberos (solo admin)
    // ---------------------------
    public static function index(Router $router) {
        iniciarSesion();
        isAdmin();

        $nombre = $_SESSION["nombre"] ?? "";
        $barberos = Usuario::findBy(['rol' => 'barbero'], 0);

        $router->render('barberos/index', [
            'barberos' => $barberos,
            'nombre' => $nombre
        ]);
    }

    // ---------------------------
    // Crear barbero (solo admin)
    // ---------------------------
    public static function crear(Router $router) {
        iniciarSesion();
        isAdmin();

        $nombre = $_SESSION["nombre"] ?? "";
        $usuario = new Usuario();
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            validarCSRF($_POST['csrf_token'] ?? '');
            $usuario->sincronizar($_POST);
            $usuario->rol = 'barbero';
            $usuario->confirmado = 1;
            $usuario->token = null;
            $usuario->token_creado = null;

            $alertas = $usuario->validarNuevaCuenta();

            if (!empty($_FILES['imagen']['tmp_name'])) {
                $usuario->setImagen($_FILES['imagen']);
            }

            if (empty($alertas)) {
                $usuario->password = password_hash($usuario->password, PASSWORD_BCRYPT);
                $resultado = $usuario->guardar();

                if ($resultado) {
                    header('Location: /barberos');
                    exit;
                }
            }
        }

        $router->render('barberos/crear', [
            'usuario' => $usuario,
            'alertas' => $alertas,
            'nombre' => $nombre
        ]);
    }

    // ---------------------------
    // Actualizar barbero (solo admin)
    // ---------------------------
    public static function actualizar(Router $router) {
        iniciarSesion();
        isAdmin();

        $nombre = $_SESSION["nombre"] ?? "";
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$id) {
            header('Location: /barberos');
            exit;
        }

        /** @var \Model\Usuario|null $usuario */
        $usuario = Usuario::find($id);
        if (!$usuario || $usuario->rol !== 'barbero') {
            header('Location: /barberos');
            exit;
        }

        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            validarCSRF($_POST['csrf_token'] ?? '');
            $usuario->sincronizar($_POST['usuario']);
            $alertas = $usuario->validarActualizar();

            if (!empty($_FILES['imagen']['tmp_name'])) {
                $usuario->setImagen($_FILES['imagen']);
            }

            if (empty($alertas)) {
                if (!empty($usuario->password)) {
                    $usuario->password = password_hash($usuario->password, PASSWORD_BCRYPT);
                } else {
                    unset($usuario->password);
                }

                $resultado = $usuario->guardar();
                if ($resultado) {
                    header('Location: /barberos');
                    exit;
                }
            }
        }

        $router->render('barberos/actualizar', [
            'usuario' => $usuario,
            'alertas' => $alertas,
            'nombre' => $nombre
        ]);
    }

    // ---------------------------
    // Eliminar barbero (solo admin)
    // ---------------------------
    public static function eliminar() {
        iniciarSesion();
        isAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            validarCSRF($_POST['csrf_token'] ?? '');
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            header("Content-Type: application/json");

            /** @var \Model\Usuario|null $usuario */
            $usuario = Usuario::find($id);

            if ($usuario && $usuario->rol === 'barbero') {
                $usuario->eliminar();
                echo json_encode(['resultado' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['resultado' => false, 'error' => 'Barbero no encontrado']);
            }
            exit;
        }
    }

    // ---------------------------
    // Obtener citas del barbero
    // ---------------------------
    public static function citas() {
        iniciarSesion();
        isBarbero();

        $barberoID = $_SESSION["id"];
        $fecha = $_GET["fecha"] ?? date("Y-m-d");

        // Validación de fecha
        $fechas = explode('-', $fecha);
        if (!checkdate($fechas[1], $fechas[2], $fechas[0])) {
            http_response_code(400);
            echo json_encode(["error" => "Fecha inválida"]);
            exit;
        }

        $citas = AdminCita::citasPorBarbero($barberoID, $fecha);

        header("Content-Type: application/json");
        echo json_encode($citas);
        exit;
    }
}
