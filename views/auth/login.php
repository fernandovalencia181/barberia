<div class="logo-login">
    <img src="/build/img/logo.webp" alt="Logo Barbería">
</div>

<!-- <h1 class="nombre-pagina">Login</h1> -->
<p class="descripcion-pagina">Inicia sesión con tus datos</p>

<?php include_once __DIR__ . "/../templates/alertas.php" ?>


<form class="formulario" method="POST" action="/">

    <div class="campo">
        <label for="email">Email</label>
        <input type="email" id="email" placeholder="Tu Email" name="email" value="<?php echo s($auth->email) ?>" autocomplete="email">
    </div>
    <div class="campo">
        <label for="password">Password</label>
        <input type="password" id="password" placeholder="Tu Password" name="password" autocomplete="new-password">
    </div>

    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
    <input type="submit" class="boton sesion" value="Iniciar Sesión">
</form>

<div class="acciones">
    <a href="/crear-cuenta">¿Aún no tienes una cuenta? Crear una</a>
    <a href="/olvide">¿Olvidaste tu password?</a>
</div>

<div class="login-google">
    <a href="/google-login" class="google-btn">
        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google logo">
        <span>Iniciar sesión con Google</span>
    </a>
</div>