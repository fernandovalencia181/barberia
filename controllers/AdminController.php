<?php

namespace Controllers;

use MVC\Router;
use Model\AdminCita;

class AdminController {
    
    public static function index(Router $router) {
        // Usar tu función centralizada para iniciar sesión
        iniciarSesion();

        isAdmin(); // Verificar que sea admin

        $nombre = $_SESSION["nombre"] ?? "";
        $fecha = $_GET["fecha"] ?? date("Y-m-d");

        // Validación estricta de fecha
        $fechas = explode("-", $fecha);
        if (!checkdate($fechas[1], $fechas[2], $fechas[0])) {
            header("Location: /404");
            exit;
        }

        // Obtener citas de manera segura
        $citas = AdminCita::citasPorFecha($fecha);

        $router->render("admin/index", [
            "nombre" => $nombre,
            "citas" => $citas,
            "fecha" => $fecha
        ]);
    }

    public static function obtenerCitas() {
        iniciarSesion();
        isAdmin();

        $fecha = $_GET["fecha"] ?? date("Y-m-d");

        $fechas = explode("-", $fecha);
        if (!checkdate($fechas[1], $fechas[2], $fechas[0])) {
            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode(["error" => "Fecha inválida"]);
            exit;
        }

        $citas = AdminCita::citasPorFecha($fecha);

        header("Content-Type: application/json");
        echo json_encode($citas);
    }
}
