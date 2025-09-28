<?php 

namespace Controllers;

use MVC\Router;
use Classes\Email;
use Model\Usuario;
use Google\Client;
use Google\Service\Oauth2;

class LoginController {

    public static function login(Router $router) {
        $alertas = [];
        $auth = new Usuario;
        
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            validarCSRF($_POST['csrf_token']);
            $auth = new Usuario($_POST);
            $alertas = $auth->validarLogin();

            if (empty($alertas)) {
                /** @var \Model\Usuario|null $usuario */
                $usuario = Usuario::findBy(["email" => $auth->email]);

                if ($usuario) {
                    if ($usuario->comprobarPasswordAndVerificado($auth->password)) {
                        session_start();
                        $_SESSION["id"] = $usuario->id;
                        $_SESSION["nombre"] = $usuario->nombre . " " . $usuario->apellido;
                        $_SESSION["email"] = $usuario->email;
                        $_SESSION["login"] = true;
                        $_SESSION["rol"] = $usuario->rol;

                        redirectSegunRol($usuario->rol);
                    }
                } else {
                    Usuario::setAlerta("error", "Usuario no encontrado");
                }
            }
        }

        $alertas = Usuario::getAlertas();
        $router->render("auth/login", [
            "alertas" => $alertas,
            "auth" => $auth,
            "csrf_token" => generarTokenCSRF()
        ]);
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION = [];
        session_destroy();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        header("Location: /");
        exit;
    }

    public static function olvide(Router $router) {
        $alertas = [];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            validarCSRF($_POST['csrf_token']);
            $auth = new Usuario($_POST);
            // Normalizar email a minúsculas antes de validar o buscar
            $auth->email = strtolower($auth->email);
            $alertas = $auth->validarEmail();

            if (empty($alertas)) {
                /** @var \Model\Usuario|null $usuario */
                $usuario = Usuario::findBy(["email" => $auth->email]);

                if ($usuario && $usuario->confirmado === "1") {
                    // Rate limiting: evitar solicitudes frecuentes
                    if ($usuario->token && isset($usuario->token_creado) &&
                        strtotime($usuario->token_creado) > strtotime("-10 minutes")) {
                        Usuario::setAlerta("error", "Ya se ha solicitado un cambio de contraseña recientemente. Revisa tu email.");
                    } else {
                        $usuario->crearToken();
                        $usuario->token_creado = date("Y-m-d H:i:s");
                        unset($usuario->password2);
                        $usuario->guardar();

                        $email = new Email($usuario->nombre, $usuario->email, $usuario->token);
                        $email->enviarInstrucciones();
                        Usuario::setAlerta("exito", "Revisa tu email");
                    }
                } else {
                    Usuario::setAlerta("error", "El usuario no existe o no está confirmado");
                }
            }
        }

        $alertas = Usuario::getAlertas();
        $router->render("auth/olvide-password", [
            "alertas" => $alertas,
            "csrf_token" => generarTokenCSRF()
        ]);
    }

    public static function recuperar(Router $router) {
        $alertas = [];
        $error = false;
        $token = isset($_GET['token']) ? trim($_GET['token']) : '';
        /** @var \Model\Usuario|null $usuario */
        $usuario = Usuario::findBy(["token" => $token]);

        if (empty($usuario)) {
            Usuario::setAlerta("error", "Token no válido");
            $error = true;
        } else {
            // Verificar expiración (1 hora)
            if (!isset($usuario->token_creado) || strtotime($usuario->token_creado) < strtotime("-1 hour")) {
                Usuario::setAlerta("error", "El token ha expirado");
                $error = true;
            }
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST" && !$error) {
            validarCSRF($_POST['csrf_token']); 
            $password = new Usuario($_POST);
            $alertas = $password->validarPassword();

            if ($_POST['password'] !== $_POST['password2']) {
                Usuario::setAlerta("error", "Los passwords no coinciden");
                $alertas = Usuario::getAlertas();
            }

            if (empty($alertas)) {
                $usuario->password = $password->password;
                $usuario->hashPassword();
                $usuario->token = null;
                $usuario->token_creado = null;
                $usuario->guardar();

                header("Location: /");
                exit;
            }
        }

        $alertas = Usuario::getAlertas();
        $router->render("auth/recuperar-password", [
            "alertas" => $alertas,
            "error" => $error,
            "csrf_token" => generarTokenCSRF()
        ]);
    }

    public static function crear(Router $router) {
        $usuario = new Usuario();
        $alertas = [];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            validarCSRF($_POST['csrf_token']);
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();

            if (empty($alertas)) {
                $resultado = $usuario->existeUsuario();

                if ($resultado) {
                    $alertas = Usuario::getAlertas();
                } else {
                    $usuario->hashPassword();
                    unset($usuario->password2);
                    $usuario->crearToken();
                    $usuario->rol = "cliente";

                    $email = new Email($usuario->nombre, $usuario->email, $usuario->token);
                    $email->enviarConfirmacion();

                    $usuario->guardar();
                    header("Location: /mensaje");
                    exit;
                }
            }
        }

        $router->render("auth/crear-cuenta", [
            "usuario" => $usuario,
            "alertas" => $alertas,
            "csrf_token" => generarTokenCSRF()
        ]);
    }

    public static function mensaje(Router $router) {
        $router->render("auth/mensaje");
    }

    public static function confirmar(Router $router) {
        $alertas = [];

        if (!isset($_GET["token"]) || empty($_GET["token"])) {
            Usuario::setAlerta("error", "Token no válido");
            $alertas = Usuario::getAlertas();

            return $router->render("auth/confirmar-cuenta", [
                "alertas" => $alertas
            ]);
        }

        $token = isset($_GET['token']) ? trim($_GET['token']) : '';
        /** @var \Model\Usuario|null $usuario */
        $usuario = Usuario::findBy(["token" => $token]);

        if (empty($usuario)) {
            Usuario::setAlerta("error", "Token no válido");
        } else {
            if ($usuario->confirmado === "1") {
                Usuario::setAlerta("info", "Cuenta ya confirmada");
            } else {
                $usuario->confirmado = "1";
                $usuario->token = null;
                $usuario->guardar();
                Usuario::setAlerta("exito", "Cuenta comprobada correctamente");
            }
        }

        $alertas = Usuario::getAlertas();
        $router->render("auth/confirmar-cuenta", [
            "alertas" => $alertas
        ]);
    }

    public static function googleLogin() {
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
        $redirectUri = $host . '/google-callback';

        $client = new \Google_Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri($redirectUri); // dinámico según el dominio
        $client->addScope("email");
        $client->addScope("profile");

        header("Location: " . $client->createAuthUrl());
        exit;
    }

    public static function googleCallback() {
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
        $redirectUri = $host . '/google-callback';

        $client = new \Google_Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri($redirectUri);

        // Opcional: para desarrollo local
        // $client->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));

        if (!isset($_GET['code'])) {
            $auth_url = $client->createAuthUrl();
            header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
            exit;
        }

        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token['access_token']);

        $oauth = new Oauth2($client);
        $googleUser = $oauth->userinfo->get();

        /** @var \Model\Usuario|null $usuario */
        $usuario = Usuario::findBy(["email" => strtolower($googleUser->email)]);

        if (!$usuario) {
            // Crear usuario automáticamente
            $usuario = new Usuario();
            $usuario->nombre = $googleUser->givenName;
            $usuario->apellido = $googleUser->familyName;
            $usuario->email = $googleUser->email;
            $usuario->confirmado = "1"; // confirmado por Google
            $usuario->google_id = $googleUser->id;
            $usuario->rol = "cliente";
            $usuario->guardar();
        } else {
            // Actualizar Google ID si no estaba guardado
            if (!$usuario->google_id) {
                $usuario->google_id = $googleUser->id;
                $usuario->guardar();
            }
        }

        // Crear sesión
        session_start();
        $_SESSION["id"] = $usuario->id;
        $_SESSION["nombre"] = $usuario->nombre . " " . $usuario->apellido;
        $_SESSION["email"] = $usuario->email;
        $_SESSION["login"] = true;
        $_SESSION["rol"] = $usuario->rol;

        // Verificar contraseña
        if (empty($usuario->password)) {
            header("Location: /agregar-password");
            exit;
        }

        // Redirigir según rol
        redirectSegunRol($usuario->rol);
    }

    public static function agregarPassword(Router $router) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!$_SESSION["login"]) {
            header("Location: /");
            exit;
        }

        $alertas = [];
        /** @var \Model\Usuario|null $usuario */
        $usuario = Usuario::findBy(["id" => $_SESSION["id"]]);

        // 🚨 Si ya tiene contraseña creada, lo mandamos al panel
        if ($usuario->tiene_password == 1) {
            redirectSegunRol($usuario->rol);
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            validarCSRF($_POST['csrf_token']);
            $password = new Usuario($_POST);

            // validar teléfono obligatorio
            if (empty(trim($_POST["telefono"] ?? ""))) {
                Usuario::setAlerta("error", "El teléfono es obligatorio");
            }

            $alertas = Usuario::getAlertas();

            // validar contraseña solo si la escriben
            if (!empty($_POST["password"]) || !empty($_POST["password2"])) {
                $alertas = $password->validarPassword();

                if ($_POST['password'] !== $_POST['password2']) {
                    Usuario::setAlerta("error", "Los passwords no coinciden");
                    $alertas = Usuario::getAlertas();
                }
            }

            if (empty($alertas)) {
                $usuario->telefono = $_POST["telefono"];

                if (!empty($_POST["password"])) {
                    $usuario->password = $password->password;
                    $usuario->hashPassword();
                    $usuario->tiene_password = 1; 
                }

                $usuario->guardar();
                // Redirigir al panel según rol
                redirectSegunRol($usuario->rol);;
            }
        }

        $router->render("auth/agregar-password", [
            "alertas" => $alertas,
            "usuario" => $usuario
        ]);
    }

}
