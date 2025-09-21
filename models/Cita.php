<?php

namespace Model;

class Cita extends ActiveRecord{
    // Base de datos
    protected static $tabla="citas";
    protected static $columnasDB=["id","fecha","hora","usuarioID", "barberoID"];

    public $id;
    public $fecha;
    public $hora;
    public $usuarioID;
    public $barberoID; // ← agrega esta propiedad

    public function __construct($args=[]) {
        $this->id = $args["id"]??null;
        $this->fecha=$args["fecha"]??"";
        $this->hora=$args["hora"]??"";
        $this->usuarioID=$args["usuarioID"]??"";
        $this->barberoID = $args["barberoID"] ?? ""; 
    }
}