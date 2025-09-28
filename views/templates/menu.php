<?php if(isset($_SESSION["login"]) && $_SESSION["login"] === true && $_SESSION["rol"] !== 'admin'): ?>
<div class="barra">
    <a href="/cita" class="logo-link">
        <img src="/build/img/logo.webp" alt="Logo Barbería">
    </a>

    <div class="menu-icon" id="menu-icon-user">
        <img src="/build/img/menu-deep.svg" alt="Menú">
    </div>

    <div class="menu glass-menu" id="menu-user">
        <ul>
            <li><a href="/cita">Servicios</a></li>
            <li><a href="/perfil">Editar Perfil</a></li>
            <li><a href="/citas">Mis Citas</a></li>
            <li><a href="/logout">Cerrar Sesión</a></li>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php if(isset($_SESSION["rol"]) && $_SESSION["rol"] === 'admin'){ ?> 
<div class="barra">
    <a href="/admin" class="logo-link">
        <img src="/build/img/logo.webp" alt="Logo Barbería">
    </a>

    <div class="menu-icon" id="menu-icon-admin">
        <img src="/build/img/menu-deep.svg" alt="Menú">
    </div>

    <div class="menu glass-menu" id="menu-admin">
        <ul>
            <li><a href="/admin">Ver Citas</a></li>
            <li><a href="/servicios">Ver Servicios</a></li>
            <li><a href="/servicios/crear">Agregar Servicio</a></li>
            <li><a href="/barberos">Ver Barberos</a></li>
            <li><a href="/barberos/crear">Agregar Barbero</a></li>
            <li><a href="/bloqueos">Bloqueos</a></li>
            <li><a href="/logout">Cerrar Sesión</a></li>
        </ul>
    </div>
</div>
<?php } ?>
