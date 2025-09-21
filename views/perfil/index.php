<div class="titulo-seccion">
    <img src="/build/img/user-cog.svg"/>
    <p>Editar Perfil</p>
</div>

<?php include_once __DIR__ . '/../templates/menu.php'; ?>
<?php include_once __DIR__ . '/../templates/alertas.php'; ?>

    <a href="/cambiar-password" class="enlace">Cambiar Password</a>

    <form class="formulario" method="POST" action="/perfil">
        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" value="<?php echo $usuario->nombre . " " . $usuario->apellido; ?>" name="nombre" placeholder="Tu Nombre">
        </div>
        <div class="campo">
            <label for="email">Email</label>
            <input type="email" value="<?php echo $usuario->email; ?>" name="email" placeholder="Tu Email">
        </div>

        <div class="campo">
            <label for="telefono">Teléfono</label>
            <input type="tel" value="<?php echo $usuario->telefono; ?>" name="telefono" placeholder="Tu Teléfono">
        </div>

        <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
        <input type="submit" class="boton" value="Guardar Cambios">
    </form>

