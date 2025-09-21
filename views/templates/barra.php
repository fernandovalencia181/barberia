<div class="barra">
    <!-- <p>Hola: <?php /*echo $nombre??""*/; ?></p> -->
    <a class="boton" href="/logout">Cerrar Sesión</a>
</div>

<?php if(isset($_SESSION["rol"]) && $_SESSION["rol"] === 'admin'){ ?> 
    <div class="barra-servicios">
        <a class="boton" href="/admin">Ver Citas</a>
        <a class="boton" href="/servicios">Ver Servicios</a>
        <a class="boton" href="/servicios/crear">Agregar Servicio</a>
        <a class="boton" href="/barberos">Ver Barberos</a>
        <a class="boton" href="/barberos/crear">Agregar Barbero</a>
        <a class="boton" href="/bloqueos">Bloqueos</a>
    </div>
<?php } ?>
