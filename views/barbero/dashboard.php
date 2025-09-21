<div class="titulo-seccion">
    <img src="/build/img/calendar-week.svg"/>
    <p>Tus Citas</p>
</div>

<?php include_once __DIR__ . "/../templates/barra.php" ?>

<div id="calendario"></div>

<div class="busqueda ocultar">
    <form class="formulario">
        <div class="campo">
            <label for="fecha">Fecha</label>
            <input type="date" name="fecha" id="fecha" value="<?php echo $fecha; ?>">
        </div>
    </form>
</div>

<div id="citas-barbero">
    <ul class="citas"></ul>
</div>

<?php 
    $script = "<script src='build/js/buscador-barbero.js'></script>";
?>
