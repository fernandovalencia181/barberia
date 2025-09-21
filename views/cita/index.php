<?php include_once __DIR__ . "/../templates/menu.php" ?>

<div id="app">
    <div id="paso-1" class="seccion">
        <div class="titulo-seccion" type="button" data-paso="1">
            <img src="/build/img/razor-electric.svg"/>
            <p>Servicios</p>
        </div>

        <h3 >Elige tus servicios a continuación</h3>
        <div id="servicios" class="listado-servicios"></div>
    </div>
    <div id="paso-2" class="seccion">
        <div class="titulo-seccion" data-paso="2">
            <img src="/build/img/calendar-plus.svg"/>
            <p>Información de Cita</p>
        </div>

        <h3>Elija un Barbero, Día y Hora</h3>
        <form class="formulario">

            <div class="campo" id="seleccionar-barbero">
                <label for="barbero">Barbero:</label>
                <select id="barbero">
                    <option value="">-- Selecciona un barbero --</option>
                </select>
            </div>

            <div id="calendario"></div>

            <div id="horarios-container" class="horarios-grid"></div>

            <div class="campo ocultar">
                <label for="nombre">Nombre</label>
                <input id="nombre" type="text" placeholder="Tu Nombre" value="<?php echo $nombre; ?>" disabled>
            </div>

            <div class="campo ocultar">
                <label for="fecha">Fecha</label>
                <input id="fecha" type="date" min="<?php echo date("Y-m-d",strtotime("+1 day")); ?>" disabled>
            </div>

            <div class="campo ocultar">
                <label for="hora">Hora</label>
                <input id="hora" type="time" disabled>
            </div>

            <input type="hidden" id="id" value="<?php echo $id; ?>">
        </form>
    </div>
    <div id="paso-3" class="seccion">
        <div class="titulo-seccion" data-paso="3">
            <img src="/build/img/list-check.svg"/>
            <p>Resumen</p>
        </div>

        <h3>Verifica que la información sea correcta</h3>

        <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
        <div class="contenido-resumen"></div>
    </div>

    <div class="paginacion">
        <button id="anterior" class="boton
        ">&laquo; Anterior</button>
        <button id="siguiente" class="boton
        ">Siguiente &raquo;</button>
    </div>
</div>


<?php
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="build/js/app-copy.js"></script>
    ';
?>
