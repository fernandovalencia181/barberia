<?php 

require_once __DIR__ . '/../includes/app.php';

use MVC\Router;
use Controllers\APIController;
use Controllers\CitaController;
use Controllers\AdminController;
use Controllers\LoginController;
use Controllers\BarberoController;
use Controllers\BloqueoController;
use Controllers\UsuarioController;
use Controllers\ServicioController;

$router = new Router();

// Iniciar Sesión
$router->get("/",[LoginController::class, "login"]);
$router->post("/",[LoginController::class, "login"]);
$router->get("/logout",[LoginController::class, "logout"]);

// Iniciar Sesión con Google
$router->get("/google-login",[LoginController::class, "googleLogin"]);
$router->get("/google-callback",[LoginController::class, "googleCallback"]);
$router->get('/agregar-password', [LoginController::class, 'agregarPassword']);
$router->post('/agregar-password', [LoginController::class, 'agregarPassword']);


// Recuperar Password
$router->get("/olvide",[LoginController::class, "olvide"]);
$router->post("/olvide",[LoginController::class, "olvide"]);
$router->get("/recuperar",[LoginController::class, "recuperar"]);
$router->post("/recuperar",[LoginController::class, "recuperar"]);

// Crear Cuenta
$router->get("/crear-cuenta",[LoginController::class, "crear"]);
$router->post("/crear-cuenta",[LoginController::class, "crear"]);

// Confirmar Cuenta
$router->get("/confirmar-cuenta",[LoginController::class, "confirmar"]);
$router->get("/mensaje",[LoginController::class, "mensaje"]);

// AREA PRIVADA 
$router->get("/cita",[CitaController::class,"index"]);
$router->get("/admin",[AdminController::class,"index"]);
$router->get("/api/admin/citas", [AdminController::class, "obtenerCitas"]);

// PERFIL Y CAMBIO DE PASSWORD
$router->get("/perfil",[UsuarioController::class,"index"]);
$router->post("/perfil",[UsuarioController::class,"index"]);
$router->get("/cambiar-password",[UsuarioController::class,"cambiar_password"]);
$router->post("/cambiar-password",[UsuarioController::class,"cambiar_password"]);
$router->get("/citas", [UsuarioController::class, "verCitas"]);

// BLOQUEOS
$router->get("/bloqueos", [BloqueoController::class, "index"]);
$router->post("/bloqueos/crear", [BloqueoController::class, "crear"]);
$router->get("/bloqueos/obtener", [BloqueoController::class, "obtener"]);
$router->post("/bloqueos/eliminar", [BloqueoController::class, "eliminar"]);

// BARBERO
$router->get('/barbero', [BarberoController::class, 'dashboard']);
$router->get("/api/barbero/citas", [BarberoController::class, "citas"]);

// API de Citas
$router->get("/api/servicios",[APIController::class,"index"]);
$router->post("/api/citas",[APIController::class,"guardar"]);
$router->get("/api/citas",[APIController::class,"citasOcupadas"]);
$router->post("/api/eliminar",[APIController::class,"eliminar"]);
$router->get("/api/citas/fecha", [APIController::class, "citasPorFecha"]);
$router->post('/api/citas/actualizar-hora', [APIController::class, 'actualizarHora']);


// API de Barberos
$router->get("/api/barberos", [APIController::class, "barberos"]);

// API de Calendario
$router->get('/api/eventos', [APIController::class, 'eventos']);

// CRUD de Servicios
$router->get("/servicios",[ServicioController::class,"index"]);
$router->get("/servicios/crear",[ServicioController::class,"crear"]);
$router->post("/servicios/crear",[ServicioController::class,"crear"]);
$router->get("/servicios/actualizar",[ServicioController::class,"actualizar"]);
$router->post("/servicios/actualizar",[ServicioController::class,"actualizar"]);
$router->post("/servicios/eliminar",[ServicioController::class,"eliminar"]);

// CRUD de Barberos
$router->get('/barberos', [BarberoController::class, 'index']);
$router->get('/barberos/crear', [BarberoController::class, 'crear']);
$router->post('/barberos/crear', [BarberoController::class, 'crear']);
$router->get('/barberos/actualizar', [BarberoController::class, 'actualizar']);
$router->post('/barberos/actualizar', [BarberoController::class, 'actualizar']);
$router->post('/barberos/eliminar', [BarberoController::class, 'eliminar']);


// Comprueba y valida las rutas, que existan y les asigna las funciones del Controlador
$router->comprobarRutas();