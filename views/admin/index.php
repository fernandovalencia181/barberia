<div class="titulo-seccion">
    <img src="/build/img/calendar-cog.svg"/>
    <p>Citas</p>
</div>

<?php include_once __DIR__ . "/../templates/menu.php" ?>

<div id="calendario"></div>


<div class="busqueda" hidden>
    <form class="formulario">
        <div class="campo">
            <label for="fecha" hidden>Fecha</label>
            <input type="date" name="fecha" id="fecha" value="<?php echo $fecha; ?>" disabled hidden>
        </div>
    </form>
</div>


<input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
<div id="citas-admin"></div>

<a href="#resumen-barberos" class="floating-btn" id="scrollBtn">
    <img src="/build/img/arrow-down.svg" alt="Resumen" />
</a>

<div id="resumen-barberos"></div>

<?php 
    $script="<script src='build/js/buscador-admin.js'></script>
    ";
?>