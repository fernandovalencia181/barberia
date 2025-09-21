<div class="titulo-seccion">
    <img src="/build/img/blade-plus.svg"/>
    <p>Nuevo Servicio</p>
</div>

<?php 
    include_once __DIR__ . "/../templates/menu.php";
    include_once __DIR__ . "/../templates/alertas.php"; 
?>

<form action="/servicios/crear" method="POST" class="formulario">
    <?php include_once __DIR__ . "/formulario.php"; ?>

    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
    <input type="submit" class="boton" value="Guardar Servicio">
</form>

