<?php

namespace Controllers;

use MVC\Router;
use Model\Usuario;
use Model\AdminCita;

class UsuarioController{
    public static function index(Router $router){
        iniciarSesion();
        isAuth();

        // Validar sesión
        if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
            header('Location: /login');
            exit;
        }

        $usuarioID = (int) $_SESSION['id'];
        /** @var \Model\Usuario|null $usuario */
        $usuario = Usuario::find($usuarioID);

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            validarCSRF($_POST['csrf_token'] ?? '');
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarPerfil();

            if(empty($alertas)){
                $existeUsuario = Usuario::findBy(['email' => $usuario->email], 1);
                if($existeUsuario && $existeUsuario->id !== $usuario->id){
                    Usuario::setAlerta('error','Email no válido, ya pertenece a otra cuenta');
                } else {
                    $usuario->guardar();
                    Usuario::setAlerta('exito','Guardado Correctamente');
                    $_SESSION['nombre'] = $usuario->nombre;
                }
            }
        }

        $alertas = Usuario::getAlertas();

        $router->render("perfil/index", [
            'titulo' => 'Perfil',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);       
    }

    public static function cambiar_password(Router $router){
        iniciarSesion();
        isAuth();

        if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
            header('Location: /login');
            exit;
        }

        $usuarioID = (int) $_SESSION['id'];
        /** @var \Model\Usuario|null $usuario */
        $usuario = Usuario::find($usuarioID);

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            validarCSRF($_POST['csrf_token'] ?? '');
            $usuario->sincronizar($_POST);
            $alertas = $usuario->nuevoPassword();

            if(empty($alertas)){
                $resultado = $usuario->comprobarPassword();
                if($resultado){
                    $usuario->password = $usuario->password_nuevo;
                    unset($usuario->password_actual, $usuario->password_nuevo);
                    $usuario->hashPassword();
                    $usuario->guardar();

                    // Regenerar sesión por seguridad
                    session_regenerate_id(true);

                    Usuario::setAlerta('exito','Password Guardado Correctamente');
                } else {
                    Usuario::setAlerta('error','Password Incorrecto');
                }
            }
        }

        $alertas = Usuario::getAlertas();

        $router->render("perfil/cambiar-password", [
            'titulo' => 'Cambiar Password',
            'alertas' => $alertas,
            'usuario' => $usuario
        ]);
    }

    public static function verCitas(Router $router) {
        iniciarSesion();
        isAuth();

        $usuarioID = $_SESSION['id'];

        $citas = AdminCita::obtenerCitasUsuario($usuarioID);

        $router->render('perfil/citas', [
            'titulo' => 'Mis Citas',
            'citas'  => $citas
        ]);
    }
}