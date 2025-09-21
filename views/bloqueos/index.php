<div class="titulo-seccion">
    <img src="/build/img/clock-cancel.svg"/>
    <p>Bloquear Día/Hora</p>
</div>

<?php include_once __DIR__ . "/../templates/menu.php" ?>

<form class="formulario" action="/bloqueos/crear" method="POST">
    <div class="campo">
        <label for="barberoID">Barbero:</label>
        <select name="barberoID" id="barberoID">
            <option value="todos">Todos</option>
            <?php foreach($barberos as $barbero): ?>
                <option value="<?php echo $barbero->id ?>"
                    <?php if (!empty($barbero->imagen)): ?>
                        data-image="/uploads/<?php echo $barbero->imagen ?>"
                    <?php endif; ?>>
                    <?php echo $barbero->nombre . ' ' . $barbero->apellido ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <input type="hidden" name="fecha" id="fecha" required>
        <div id="calendario"></div>
    </div>

    <div class="campo ocultar">
        <label>Hora:</label>
        <input name="hora" value="">
    </div>

    <div id="horarios-wrapper"></div>

    <div class="campo">
        <label>Motivo:</label>
        <input type="text" name="motivo">
    </div>

    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
    <input class="boton" type="button" id="crear-bloqueo" value="Crear Bloqueo">
</form>

<h3 class="margin-top">Bloqueos existentes</h3>
<?php foreach($bloqueos as $b): ?>
    <div class="bloqueo-card">
        <p>Barbero: <span><?php echo $b->barberoID ? "ID ".$b->barberoID : "Todos"; ?></span></p>
        <p></p>
        <p>Fecha: <span><?php echo $b->fecha; ?></span></p>
        <p>Hora: <span><?php echo $b->hora ?? "Todo el día"; ?></span></p>
        <p>Motivo: <span><?php echo $b->motivo; ?></span></p>

        <div class="acciones">
            <form action="/bloqueos/eliminar" method="POST">
                <input type="hidden" name="id" value="<?php echo $b->id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                <input type="button" class="boton-eliminar" value="Eliminar">
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="build/js/bloqueos.js"></script>
    ';
?>