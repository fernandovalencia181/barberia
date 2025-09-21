<?php

namespace Controllers;

use Model\Servicio;
use MVC\Router;

class ServicioController {

    // ---------------------------
    // Listado de servicios
    // ---------------------------
    public static function index(Router $router) {
        iniciarSesion();
        isAdmin();

        $nombre = $_SESSION["nombre"] ?? "";
        $servicios = Servicio::all();

        $router->render("servicios/index", [
            "nombre" => $nombre,
            "servicios" => $servicios
        ]);
    }

    // ---------------------------
    // Crear un servicio
    // ---------------------------
    public static function crear(Router $router) {
        iniciarSesion();
        isAdmin();

        $nombre = $_SESSION["nombre"] ?? "";
        $servicio = new Servicio();
        $alertas = [];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            validarCSRF($_POST['csrf_token'] ?? '');
            $servicio->sincronizar($_POST);
            $alertas = $servicio->validar();

            if (empty($alertas)) {
                $servicio->guardar();
                header("Location: /servicios");
                exit;
            }
        }

        $router->render("servicios/crear", [
            "nombre" => $nombre,
            "servicio" => $servicio,
            "alertas" => $alertas
        ]);
    }

    // ---------------------------
    // Actualizar un servicio
    // ---------------------------
    public static function actualizar(Router $router) {
        iniciarSesion();
        isAdmin();

        $nombre = $_SESSION["nombre"] ?? "";
        $id = $_GET["id"] ?? null;

        if (!is_numeric($id)) {
            header("Location: /404");
            exit;
        }

        $servicio = Servicio::find($id);
        if (!$servicio) {
            header("Location: /404");
            exit;
        }

        $alertas = [];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            validarCSRF($_POST['csrf_token'] ?? '');
            $servicio->sincronizar($_POST);
            $alertas = $servicio->validar();

            if (empty($alertas)) {
                $servicio->guardar();
                header("Location: /servicios");
                exit;
            }
        }

        $router->render("servicios/actualizar", [
            "nombre" => $nombre,
            "servicio" => $servicio,
            "alertas" => $alertas
        ]);
    }

    // ---------------------------
    // Eliminar un servicio
    // ---------------------------
    public static function eliminar() {
        iniciarSesion();
        isAdmin();

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            validarCSRF($_POST['csrf_token'] ?? '');
            $id = $_POST["id"] ?? null;

            if (!is_numeric($id)) {
                header("Location: /servicios");
                exit;
            }

            $servicio = Servicio::find($id);
            if ($servicio) {
                $servicio->eliminar();
            }

            header("Location: /servicios");
            exit;
        }
    }
}
