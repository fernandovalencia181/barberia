<?php

namespace Model;

class AdminCita extends ActiveRecord {
    protected static $tabla = "citasservicios";
    protected static $columnasDB = ["id", "hora", "cliente", "email", "telefono", "servicio", "precio"];

    public $id;
    public $hora;
    public $cliente;
    public $email;
    public $telefono;
    public $barbero;
    public $barberoID;
    public $servicio;
    public $precio;
    public $fecha;

    public function __construct($args = []) {
        $this->id = $args["id"] ?? null;
        $this->fecha = $args["fecha"] ?? "";
        $this->hora = $args["hora"] ?? "";
        $this->cliente = $args["cliente"] ?? "";
        $this->email = $args["email"] ?? "";
        $this->telefono = $args["telefono"] ?? "";
        $this->servicio = $args["servicio"] ?? "";
        $this->precio = $args["precio"] ?? "";
        $this->barberoID = $args["barberoID"] ?? null;
    }

    // -----------------------------
    // Consultas seguras
    // -----------------------------
    public static function citasPorBarbero(int $barberoID, string $fecha) {
        $query = "SELECT 
                    citas.id, 
                    citas.hora, 
                    CONCAT(IFNULL(cliente.nombre,''), ' ', IFNULL(cliente.apellido,'')) AS cliente, 
                    IFNULL(cliente.email,'---') AS email, 
                    IFNULL(cliente.telefono,'---') AS telefono, 
                    CONCAT(IFNULL(barbero.nombre,''), ' ', IFNULL(barbero.apellido,'')) AS barbero,
                    servicios.nombre AS servicio, 
                    servicios.precio  
                  FROM citas
                  LEFT JOIN usuarios AS cliente ON citas.usuarioID = cliente.id  
                  LEFT JOIN usuarios AS barbero ON citas.barberoID = barbero.id
                  LEFT JOIN citasservicios ON citasservicios.citaID = citas.id 
                  LEFT JOIN servicios ON servicios.id = citasservicios.servicioID
                  WHERE fecha = ? AND citas.barberoID = ?";
        
        return self::SQL($query, 'si', [$fecha, $barberoID]);
    }

    public static function citasPorFecha(string $fecha) {
        $query = "SELECT 
                    citas.id, 
                    citas.hora,
                    citas.barberoID,
                    CONCAT(IFNULL(cliente.nombre,''), ' ', IFNULL(cliente.apellido,'')) AS cliente, 
                    IFNULL(cliente.email,'---') AS email, 
                    IFNULL(cliente.telefono,'---') AS telefono, 
                    CONCAT(IFNULL(barbero.nombre,''), ' ', IFNULL(barbero.apellido,'')) AS barbero,
                    IFNULL(servicios.nombre,'---') AS servicio, 
                    IFNULL(servicios.precio,0) AS precio
                  FROM citas
                  LEFT JOIN usuarios AS cliente ON citas.usuarioID = cliente.id  
                  LEFT JOIN usuarios AS barbero ON citas.barberoID = barbero.id
                  LEFT JOIN citasservicios ON citasservicios.citaID = citas.id 
                  LEFT JOIN servicios ON servicios.id = citasservicios.servicioID
                  WHERE fecha = ?";
        
        return self::SQL($query, 's', [$fecha]);
    }

    public static function obtenerCitasUsuario(int $usuarioID, ?string $fechaInicio = null): array {
        $fechaInicio = $fechaInicio ?? date('Y-m-d');

        $query = "SELECT 
                    citas.id, 
                    citas.fecha,
                    citas.hora,
                    CONCAT(IFNULL(barbero.nombre,''), ' ', IFNULL(barbero.apellido,'')) AS barbero,
                    IFNULL(servicios.nombre,'---') AS servicio, 
                    IFNULL(servicios.precio,0) AS precio
                  FROM citas
                  LEFT JOIN usuarios AS barbero ON citas.barberoID = barbero.id
                  LEFT JOIN citasservicios ON citasservicios.citaID = citas.id 
                  LEFT JOIN servicios ON servicios.id = citasservicios.servicioID
                  WHERE citas.usuarioID = ? 
                  AND citas.fecha >= ?
                  ORDER BY citas.fecha, citas.hora";

        return self::SQL($query, 'is', [$usuarioID, $fechaInicio]);
    }

    public static function obtenerCitasProximas($intervaloMinutos = 60) {
        $ahora = date('Y-m-d H:i:00');
        $hasta = date('Y-m-d H:i:00', strtotime("+$intervaloMinutos minutes"));

        $consulta = "SELECT 
                        c.id, c.fecha, c.hora, u.nombre AS cliente, u.email
                     FROM citas c
                     LEFT JOIN usuarios u ON c.usuarioID = u.id
                     WHERE CONCAT(c.fecha,' ',c.hora) BETWEEN '$ahora' AND '$hasta'";

        return self::SQL($consulta);
    }
}

