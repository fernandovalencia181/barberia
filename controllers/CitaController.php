<?php

namespace Controllers;

use MVC\Router;

class CitaController{
    public static function index(Router $router){
        iniciarSesion();
        isAuth();
        $nombre = $_SESSION["nombre"];

        $router->render("cita/index",[
            "nombre" => $nombre,
            "id" => $_SESSION["id"]
        ]);
    }
}