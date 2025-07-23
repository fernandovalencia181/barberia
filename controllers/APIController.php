<?php

namespace Controllers;

use Model\Cita;
use Model\Servicio;
use Model\CitaServicio;

class APIController{
    public static function index() {
        $servicios=Servicio::all(); // Servicio::all() es un método (que debe estar en el modelo Servicio) que devuelve un arreglo con todos los servicios.
        echo json_encode($servicios); // Luego usa json_encode($servicios) para convertir ese arreglo PHP a formato JSON, que es el formato que el frontend (JavaScript) puede consumir fácilmente.
        // El echo envía ese JSON como respuesta HTTP, para que quien hizo la petición (el navegador o frontend) lo reciba.
    }

    public static function guardar(){
        //Primero verifica que la sesión esté iniciada (session_start()), para poder acceder a datos del usuario autenticado.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $usuarioID = $_SESSION["id"] ?? null; // Obtiene el ID del usuario desde la sesión ($_SESSION["id"]).

        if (!$usuarioID) {
            echo json_encode(["error" => "Usuario no autenticado"]);
            return; //Si no hay usuario autenticado, devuelve un error JSON con ["error" => "Usuario no autenticado"] y detiene la ejecución (return).
        }

        $datos = $_POST; // Recibe los datos enviados por el frontend vía POST ($datos = $_POST).
        $datos["usuarioID"] = $usuarioID; // Agrega el ID del usuario a esos datos, para que la cita quede vinculada a ese usuario.
        // Almacena la cita y devuelve el ID
        $cita = new Cita($datos); // Crea un nuevo objeto Cita con esos datos.
        $resultado = $cita->guardar(); // Guarda la cita en la base de datos 
        $id=$resultado["id"];


        // Almacena los servicios con el ID de la cita
        $idServicios=explode(",",$_POST["servicios"]);

        foreach($idServicios as $idServicio){
            $args=[
                "citaID"=>$id,
                "servicioID"=>$idServicio
            ];
        // Guardar la relación en la base de datos
        $citaServicio = new CitaServicio($args);
        $citaServicio->guardar();
        }

        echo json_encode(["resultado" => $resultado]); // Responde con el resultado en formato JSON, que el frontend usará para mostrar si la cita fue creada o hubo un problema.
    }

    public static function eliminar(){
        if($_SERVER["REQUEST_METHOD"]==="POST"){
            $id=$_POST["id"];
            $cita=Cita::find($id);
            $cita->eliminar();
            header("Location:" . $_SERVER["HTTP_REFERER"]);
        }
    }
}