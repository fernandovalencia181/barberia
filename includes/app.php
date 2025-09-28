<?php
// ---------------------------
// Configuración de errores
// ---------------------------
// Suprimir Deprecated y mostrar solo errores críticos
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', 1); // 1 en desarrollo, 0 en producción

// Crear carpeta logs si no existe
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Definir archivo de log
ini_set('log_errors', 1);
ini_set('error_log', $logDir . '/error.log');

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
