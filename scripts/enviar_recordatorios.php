<?php
require __DIR__ . '/../includes/app.php'; // bootstrap y autoload
use Model\AdminCita;

// Log de inicio
file_put_contents(__DIR__ . '/log.txt', "Script iniciado a " . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
echo "Script iniciado a " . date('Y-m-d H:i:s') . PHP_EOL;

// Obtener todas las citas próximas (ej: 60 minutos antes)
$citas = AdminCita::obtenerCitasProximas(60*24); // función que devuelve objetos con email, cliente, barbero, fecha, hora

foreach ($citas as $cita) {
    try {
        // Enviar recordatorio
        $resultado = enviarEmailRecordatorio(
            $cita->email,
            $cita->cliente,
            $cita->barbero,
            $cita->fecha,
            $cita->hora
        );

        $lineaLog = "Recordatorio enviado a {$cita->email}: " . ($resultado['ok'] ? 'OK' : 'ERROR: ' . $resultado['error']);
        file_put_contents(__DIR__ . '/log.txt', $lineaLog . PHP_EOL, FILE_APPEND);
        echo $lineaLog . PHP_EOL;

    } catch (\Exception $e) {
        $errorLog = "Error enviando a {$cita->email}: {$e->getMessage()}";
        file_put_contents(__DIR__ . '/log.txt', $errorLog . PHP_EOL, FILE_APPEND);
        echo $errorLog . PHP_EOL;
    }
}

// Log de fin
file_put_contents(__DIR__ . '/log.txt', "Script finalizado a " . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
echo "Script finalizado a " . date('Y-m-d H:i:s') . PHP_EOL;