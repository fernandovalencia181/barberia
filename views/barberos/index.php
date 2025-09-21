<div class="titulo-seccion">
    <img src="/build/img/user.svg"/>
    <p>Barberos</p>
</div>

<?php 
    include_once __DIR__ . "/../templates/menu.php"; 
?>

<ul class="servicios">
    <?php foreach($barberos as $barbero){ ?>
        <li>
            <p>ID: <span><?php echo $barbero->id; ?></span></p>
            <p>Nombre Completo: <span><?php echo $barbero->nombre . " " . $barbero->apellido; ?></span></p>
            <p>Correo: <span><?php echo $barbero->email; ?></span></p>
            <p>Telefono: <span><?php echo $barbero->telefono; ?></span></p>

            <div class="acciones">
                <a class="boton" href="/barberos/actualizar?id=<?php echo $barbero->id; ?>">Actualizar</a>

                <form action="/barberos/eliminar" method="POST">
                    <input type="hidden" name="id" value="<?php echo $barbero->id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                    <input type="button" value="Borrar" class="boton-eliminar">
                </form>
            </div>
        </li>
    <?php } ?>
</ul>

