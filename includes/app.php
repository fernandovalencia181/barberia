<?php
// ---------------------------
// Configuración de errores
// ---------------------------
// Suprimir Deprecated y mostrar solo errores críticos
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', 0);           // No mostrar errores en pantalla
ini_set('log_errors', 1);               // Registrar errores en log
ini_set('error_log', __DIR__ . '/../logs/error.log'); // Ruta al log

// ---------------------------
// Cargar dependencias
// ---------------------------
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Model\ActiveRecord;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Funciones y base de datos
require 'funciones.php';
require 'database.php';

// Conectarnos a la base de datos
ActiveRecord::setDB($db);
