<?php

namespace Model;

class Usuario extends ActiveRecord {
    // Base de datos
    protected static $tabla = "usuarios";
    protected static $columnasDB = [
        "id","nombre","apellido","email","password","telefono",
        "rol","confirmado","token","token_creado","imagen"
    ];

    public $id;
    public $nombre;
    public $apellido;
    public $email;
    public $password;
    public $password2;
    public $password_actual;
    public $password_nuevo;
    public $telefono;
    public $rol;
    public $confirmado;
    public $token;
    public $token_creado;
    public $google_id;
    public $tiene_password;
    public $imagen;

    public function __construct($args = []) {
        $this->id = $args["id"] ?? null;
        $this->nombre = $args["nombre"] ?? "";
        $this->apellido = $args["apellido"] ?? "";
        $this->email = isset($args["email"]) ? strtolower($args["email"]) : "";
        $this->password = $args["password"] ?? "";
        $this->password2 = $args["password2"] ?? "";
        $this->password_actual = $args["password_actual"] ?? "";
        $this->password_nuevo = $args["password_nuevo"] ?? "";
        $this->telefono = $args["telefono"] ?? "";
        $this->rol = $args["rol"] ?? "";
        $this->confirmado = $args["confirmado"] ?? "0";
        $this->token = $args["token"] ?? "";
        $this->token_creado = $args["token_creado"] ?? null;
        $this->google_id = $args["google_id"] ?? null;
        $this->tiene_password = $args["tiene_password"] ?? 0;
        $this->imagen = $args["imagen"] ?? "";
        $this->normalizarTelefono();
    }

    // ------------------------
    // Normalizaciones y helpers
    // ------------------------
    private function normalizarTelefono() {
        $this->telefono = preg_replace("/[^0-9]/", "", $this->telefono);
    }

    public function hashPassword() {
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
    }

    public function crearToken() {
        // Token seguro
        $this->token = bin2hex(random_bytes(8));
        $this->token_creado = date("Y-m-d H:i:s");
    }

    public function comprobarPasswordAndVerificado($password) {
        $resultado = password_verify($password, $this->password);
        if (!$resultado || !$this->confirmado) {
            self::$alertas["error"][] = "Password incorrecto o tu cuenta no ha sido confirmada";
            return false;
        }
        return true;
    }

    // ------------------------
    // Validaciones
    // ------------------------
    public function validarNuevaCuenta() {
        $this->normalizarTelefono();

        if (!$this->nombre) self::$alertas["error"][] = "El nombre es obligatorio";
        if (!$this->apellido) self::$alertas["error"][] = "El apellido es obligatorio";
        if (!$this->email || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) self::$alertas["error"][] = "El email no es válido";
        if (!$this->password) self::$alertas["error"][] = "El password es obligatorio";
        if (strlen($this->password) < 6) self::$alertas["error"][] = "El password debe contener al menos 6 caracteres";
        if ($this->password !== $this->password2) self::$alertas["error"][] = "Los passwords son diferentes";
        if (!$this->telefono) self::$alertas["error"][] = "El teléfono es obligatorio";
        elseif (strlen($this->telefono) < 9 || strlen($this->telefono) > 10) self::$alertas["error"][] = "El teléfono debe contener entre 9 y 10 números";

        return self::$alertas;
    }

    public function validarLogin() {
        if (!$this->email) self::$alertas["error"][] = "El email es obligatorio";
        if (!$this->password) self::$alertas["error"][] = "El password es obligatorio";
        return self::$alertas;
    }

    public function validarEmail() {
        if (!$this->email || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) self::$alertas["error"][] = "El email no es válido";
        return self::$alertas;
    }

    public function validarPassword() {
        if (!$this->password) self::$alertas["error"][] = "El password es obligatorio";
        if (strlen($this->password) < 6) self::$alertas["error"][] = "El password debe tener al menos 6 caracteres";
        return self::$alertas;
    }

    public function validarPerfil() {
        $this->normalizarTelefono();
        if (!$this->nombre) self::$alertas['error'][] = 'El Nombre es Obligatorio';
        if (!$this->email) self::$alertas['error'][] = 'El Email es Obligatorio';
        if (!$this->telefono) self::$alertas["error"][] = "El teléfono es obligatorio";
        elseif (strlen($this->telefono) < 9 || strlen($this->telefono) > 10)
            self::$alertas["error"][] = "El teléfono debe contener entre 9 y 10 números";
        return self::$alertas;
    }

    public function validarActualizar() {
        if (!$this->password) self::$alertas["error"][] = "El password es obligatorio";
        if (strlen($this->password) < 6) self::$alertas["error"][] = "El password debe tener al menos 6 caracteres";
        return self::$alertas;
    }

    public function nuevoPassword(): array {
        if (!$this->password_actual) self::$alertas['error'][] = 'El Password Actual no puede ir vacio';
        if (!$this->password_nuevo) self::$alertas['error'][] = 'El Password Nuevo no puede ir vacio';
        if (strlen($this->password_nuevo) < 6) self::$alertas['error'][] = 'El Password debe contener al menos 6 caracteres';
        return self::$alertas;
    }

    public function comprobarPassword(): bool {
        return password_verify($this->password_actual, $this->password);
    }

    // ------------------------
    // Consultas seguras
    // ------------------------
    public function existeUsuario(): bool {
        $resultado = self::SQL(
            "SELECT * FROM " . self::$tabla . " WHERE email = ? LIMIT 1",
            's',
            [$this->email],
            false
        );
        if (count($resultado)) self::$alertas["error"][] = "El usuario ya está registrado";
        return count($resultado) > 0;
    }

    // ------------------------
    // Archivos
    // ------------------------
    public function setImagen($archivo) {
        if (empty($archivo['tmp_name'])) return;

        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif'])) return;

        $nombreArchivo = md5(uniqid(rand(), true)) . "." . $ext;
        $carpeta = __DIR__ . "/../public/uploads/";
        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
        move_uploaded_file($archivo['tmp_name'], $carpeta . $nombreArchivo);
        $this->imagen = $nombreArchivo;
    }
}
