<h1 class="nombre-pagina">Recuperar Password</h1>
<p class="descripcion-pagina">Coloca tu nuevo password a continuación</p>

<?php include_once __DIR__ . "/../templates/alertas.php"; ?>

<?php if($error) return; ?>
<form class="formulario" method="POST">
    <div class="campo">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Tu Nuevo Password" autocomplete="new-password">
    </div>
    <div class="campo">
        <label for="password2">Repetir Password</label>
        <input type="password" id="password2" name="password2" placeholder="Repite tu Password" autocomplete="new-password">
    </div>

    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
    <input type="submit" class="boton" value="Guardar Nuevo Password">
</form>

<div class="acciones">
    <a href="/">¿Ya tienes cuenta? Iniciar Sesión</a>
    <a href="/crear-cuenta">¿Aún no tienes cuenta? Obtener una</a>
</div>