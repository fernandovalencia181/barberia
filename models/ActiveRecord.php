<?php
namespace Model;

class ActiveRecord {

    // Base de datos
    protected static $db;
    protected static $tabla = '';
    protected static $columnasDB = [];

    public $id;

    // Alertas y mensajes
    protected static $alertas = [];

    // Definir la conexión a la BD
    public static function setDB($database) {
        self::$db = $database;
    }

    public static function getDB() {
        return self::$db;
    }

    public static function setAlerta($tipo, $mensaje) {
        static::$alertas[$tipo][] = $mensaje;
    }

    public static function getAlertas() {
        return static::$alertas;
    }

    public function validar() {
        static::$alertas = [];
        return static::$alertas;
    }

    // ---------------------------
    // Helpers internos
    // ---------------------------

    /**
     * Bind dinámico que crea referencias necesarias para bind_param.
     */
    protected static function bindParamsByRef($stmt, string $types, array $params) {
        if (empty($params)) return;
        // Construir array de referencias: [ &types, &param0, &param1, ... ]
        $bind = [];
        $bind[] = $types;
        // Creamos variables con scope local que referencian los valores
        foreach ($params as $i => $p) {
            // forzar tipos simples: null aceptable, pero bind_param necesita variable
            $varName = "bind_param_{$i}";
            $$varName = $p;
            $bind[] = &$$varName;
        }
        // Llamar bind_param con referencias
        call_user_func_array([$stmt, 'bind_param'], $bind);
    }

    /**
     * Obtener todas las filas desde un mysqli_stmt, con compatibilidad
     * si no existe get_result() (mysqlnd).
     */
    protected static function fetchAllFromStmt($stmt): array {
        $rows = [];

        if (method_exists($stmt, 'get_result')) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            if (isset($result) && method_exists($result, 'free')) $result->free();
        } else {
            // Fallback para entornos sin mysqlnd
            $meta = $stmt->result_metadata();
            if (!$meta) return $rows;
            $fields = [];
            $row = [];
            while ($field = $meta->fetch_field()) {
                $fields[] = $field->name;
                $row[$field->name] = null;
                $bind[] = &$row[$field->name];
            }
            call_user_func_array([$stmt, 'bind_result'], $bind);
            while ($stmt->fetch()) {
                $copy = [];
                foreach ($row as $k => $v) $copy[$k] = $v;
                $rows[] = $copy;
            }
            $meta->free();
        }

        return $rows;
    }

    // ---------------------------
    // Consulta segura genérica (usada internamente)
    // ---------------------------
    protected static function ejecutarConsulta($query, $types = '', $params = []) {
        $stmt = self::$db->prepare($query);
        if ($stmt === false) {
            throw new \Exception("Error en prepare(): " . self::$db->error);
        }

        if (!empty($params)) {
            self::bindParamsByRef($stmt, $types, $params);
        }

        if (!$stmt->execute()) {
            // Opcional: loggea $stmt->error o lanza excepción con más contexto
            $error = $stmt->error;
            $stmt->close();
            throw new \Exception("Error en execute(): " . $error);
        }

        // Devolvemos el stmt para que el llamador pueda leer resultado o cerrar
        return $stmt;
    }

    // ---------------------------
    // Crear objetos desde resultados
    // ---------------------------
    protected static function crearObjeto($registro) {
        $objeto = new static;
        foreach ($registro as $key => $value) {
            if (property_exists($objeto, $key)) {
                $objeto->$key = $value;
            }
        }
        return $objeto;
    }

    // ---------------------------
    // Atributos / sincronización
    // ---------------------------
    public function atributos() {
        $atributos = [];
        foreach (static::$columnasDB as $columna) {
            if ($columna === 'id') continue;
            $atributos[$columna] = $this->$columna ?? null;
        }
        return $atributos;
    }

    public function sincronizar($args = []) {
        foreach ($args as $key => $value) {
            if (property_exists($this, $key) && !is_null($value)) {
                $this->$key = $value;
            }
        }
    }

    // ---------------------------
    // CRUD (mismos nombres)
    // ---------------------------
    public function guardar() {
        if (!is_null($this->id)) {
            return $this->actualizar();
        } else {
            return $this->crear();
        }
    }

    public static function all() {
        $query = "SELECT * FROM " . static::$tabla;
        $stmt = self::ejecutarConsulta($query);
        $rows = self::fetchAllFromStmt($stmt);
        $stmt->close();

        $array = [];
        foreach ($rows as $registro) {
            $array[] = static::crearObjeto($registro);
        }
        return $array;
    }

    public static function find($id) {
        $id = (int)$id;
        $query = "SELECT * FROM " . static::$tabla . " WHERE id = ? LIMIT 1";
        $stmt = self::ejecutarConsulta($query, 'i', [$id]);
        $rows = self::fetchAllFromStmt($stmt);
        $stmt->close();
        $registro = $rows[0] ?? null;
        return $registro ? static::crearObjeto($registro) : null;
    }

    public static function findBy($conditions = [], $limit = 1) {
        $clauses = [];
        $params = [];
        $types = '';

        foreach ($conditions as $col => $val) {
            if (!in_array($col, static::$columnasDB, true)) {
                continue; // ignorar columnas no válidas (whitelist)
            }
            if ($val === null) {
                $clauses[] = "$col IS NULL";
            } else {
                $clauses[] = "$col = ?";
                $params[] = $val;
                $types .= is_int($val) ? 'i' : 's';
            }
        }

        if (empty($clauses)) return null;

        $query = "SELECT * FROM " . static::$tabla . " WHERE " . implode(" AND ", $clauses);
        if ($limit) {
            $query .= " LIMIT " . (int)$limit;
        }

        $stmt = self::ejecutarConsulta($query, $types, $params);
        $rows = self::fetchAllFromStmt($stmt);
        $stmt->close();

        $array = [];
        foreach ($rows as $registro) {
            $array[] = static::crearObjeto($registro);
        }
        return $limit === 1 ? array_shift($array) : $array;
    }

    public static function get($limite) {
        $limite = (int)$limite;
        // Para LIMIT es más fiable castear y usar placeholder o concatenar entero.
        $query = "SELECT * FROM " . static::$tabla . " LIMIT ?";
        $stmt = self::ejecutarConsulta($query, 'i', [$limite]);
        $rows = self::fetchAllFromStmt($stmt);
        $stmt->close();

        $array = [];
        foreach ($rows as $registro) {
            $array[] = static::crearObjeto($registro);
        }
        return $array;
    }

    public function crear() {
        $atributos = $this->atributos();
        $cols = array_keys($atributos);

        if (count($cols) === 0) {
            throw new \Exception("No hay atributos para insertar.");
        }

        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $query = "INSERT INTO " . static::$tabla . " (" . implode(', ', $cols) . ") VALUES ($placeholders)";

        $types = '';
        $params = [];
        foreach ($atributos as $val) {
            if (is_int($val)) {
                $types .= 'i';
            } elseif (is_float($val)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $params[] = $val;
        }

        $stmt = self::$db->prepare($query);
        if ($stmt === false) {
            throw new \Exception("Error en prepare(): " . self::$db->error);
        }
        if (!empty($params)) {
            self::bindParamsByRef($stmt, $types, $params);
        }

        $resultado = $stmt->execute();
        if (!$resultado) {
            $error = $stmt->error;
            $stmt->close();
            return ['resultado' => false, 'id' => null, 'mensaje' => $error];
        }

        // insertar el id desde la conexión
        $insertId = self::$db->insert_id;
        $stmt->close();

        return [
            'resultado' => true,
            'id' => $insertId,
            'mensaje' => 'Registro creado correctamente'
        ];
    }

    public function actualizar() {
        $atributos = $this->atributos();
        $valores = [];
        $params = [];
        $types = '';

        foreach ($atributos as $col => $val) {
            $valores[] = "$col = ?";
            if (is_int($val)) {
                $types .= 'i';
            } elseif (is_float($val)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $params[] = $val;
        }

        $params[] = (int)$this->id;
        $types .= 'i';

        $query = "UPDATE " . static::$tabla . " SET " . implode(', ', $valores) . " WHERE id = ? LIMIT 1";

        $stmt = self::$db->prepare($query);
        if ($stmt === false) {
            throw new \Exception("Error en prepare(): " . self::$db->error);
        }

        if (!empty($params)) {
            self::bindParamsByRef($stmt, $types, $params);
        }

        $resultado = $stmt->execute();
        $error = $resultado ? null : $stmt->error;
        $stmt->close();

        return [
            'resultado' => (bool)$resultado,
            'id' => $this->id,
            'mensaje' => $resultado ? 'Registro actualizado' : $error
        ];
    }

    public function eliminar() {
        $id = (int)$this->id;
        $query = "DELETE FROM " . static::$tabla . " WHERE id = ? LIMIT 1";
        $stmt = self::$db->prepare($query);
        if ($stmt === false) {
            throw new \Exception("Error en prepare(): " . self::$db->error);
        }
        self::bindParamsByRef($stmt, 'i', [$id]);
        $resultado = $stmt->execute();
        $error = $resultado ? null : $stmt->error;
        $stmt->close();

        return [
            'resultado' => (bool)$resultado,
            'id' => $id,
            'mensaje' => $resultado ? 'Registro eliminado' : $error
        ];
    }

    /**
     * Método SQL seguro para consultas personalizadas (joins, agregaciones...)
     * $query debe usar placeholders ? y pasar $types/$params.
     * $asObjects = true devolverá objetos del modelo; false devolverá arrays asociativos.
     */
    public static function SQL(string $query, string $types = '', array $params = [], bool $asObjects = true) {
        $stmt = self::$db->prepare($query);
        if ($stmt === false) {
            throw new \Exception("Error en prepare(): " . self::$db->error);
        }

        if (!empty($params)) {
            self::bindParamsByRef($stmt, $types, $params);
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new \Exception("Error en execute(): " . $error);
        }

        $rows = self::fetchAllFromStmt($stmt);
        $stmt->close();

        if ($asObjects) {
            $objects = [];
            foreach ($rows as $row) {
                $objects[] = static::crearObjeto($row);
            }
            return $objects;
        }

        return $rows;
    }
}
