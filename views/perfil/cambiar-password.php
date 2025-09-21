<div class="titulo-seccion">
    <img src="/build/img/edit.svg"/>
    <p>Cambiar Password</p>
</div>

<?php include_once __DIR__ . '/../templates/menu.php'; ?>
<?php include_once __DIR__ . '/../templates/alertas.php'; ?>

<div class="app">

    <a href="/perfil" class="enlace">Volver a Perfil</a>

    <form class="formulario" method="POST" action="/cambiar-password">
        <div class="campo">
            <label for="password_actual">Password Actual</label>
            <input type="password" name="password_actual" placeholder="Tu Password Actual">
        </div>
        <div class="campo">
            <label for="password_nuevo">Password Nuevo</label>
            <input type="password" name="password_nuevo" placeholder="Tu Password Nuevo">
        </div>

        <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
        <input type="submit" class="boton" value="Guardar Cambios">
    </form>
</div>

