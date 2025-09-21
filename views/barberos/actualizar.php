<div class="titulo-seccion">
    <img src="/build/img/edit.svg"/>
    <p>Actualizar Barbero</p>
</div>


<?php 
    include_once __DIR__ . "/../templates/menu.php";
    include_once __DIR__ . "/../templates/alertas.php"; 
?>

<form method="POST" class="formulario" enctype="multipart/form-data">
    <?php include_once __DIR__ . "/formulario.php"; ?>

    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
    <input type="submit" class="boton" value="Actualizar">
</form>

