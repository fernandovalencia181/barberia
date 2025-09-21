<?php

namespace Model;

class Bloqueo extends ActiveRecord {
    protected static $tabla = 'bloqueos';
    protected static $columnasDB = ['id', 'barberoID', 'fecha', 'hora', 'motivo'];

    public $id;
    public $barberoID;
    public $fecha;
    public $hora;
    public $motivo;

    public function __construct($args = []) {
        $this->id = $args['id'] ?? null;
        $this->barberoID = $args['barberoID'] ?? null;
        $this->fecha = $args['fecha'] ?? '';
        $this->hora = $args['hora'] ?? null;
        $this->motivo = $args['motivo'] ?? '';
    }

    public static function obtenerBloqueos(?int $barberoID = null, ?string $fecha = null) {
        $query = "SELECT * FROM bloqueos WHERE 1=1";
        $params = [];
        $types = '';

        if (!is_null($barberoID)) {
            $query .= " AND (barberoID = ? OR barberoID IS NULL)";
            $params[] = $barberoID;
            $types .= 'i';
        }

        if (!is_null($fecha)) {
            $query .= " AND fecha = ?";
            $params[] = $fecha;
            $types .= 's';
        }

        return self::SQL($query, $types, $params);
    }
}
