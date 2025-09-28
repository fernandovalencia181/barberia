<div class="titulo-seccion">
    <img src="/build/img/calendar-week.svg"/>
    <p>Mis Citas</p>
</div>

<?php include_once __DIR__ . '/../templates/menu.php'; ?>

<?php if(empty($citas)): ?>
    <h3>No tienes citas activas</h3>
<?php else: ?>
    <ul class="citas">
        <?php
        $idCitaAnterior = null;
        foreach($citas as $cita):
            if($cita->id !== $idCitaAnterior):
                if($idCitaAnterior !== null):
                    echo "</li>";
                endif;
                echo "<li>";
                $fechaFormateada = date("d-m-Y", strtotime($cita->fecha));
                echo "<p><strong>Fecha:</strong> <span>{$fechaFormateada}</span></p>";
                echo "<p><strong>Hora:</strong> <span>" . substr($cita->hora, 0, 5) . "</span></p>";
                echo "<p><strong>Barbero:</strong> <span>{$cita->barbero}</span></p>";
                echo "<h3>Servicios</h3>";
                $idCitaAnterior = $cita->id;
            endif;

            echo "<p class='servicio'>{$cita->servicio} - $ {$cita->precio}</p>";

            // Botón de cancelar para la cita
            $proximaCita = next($citas);
            if(!$proximaCita || $proximaCita->id !== $cita->id){
                echo "<form action='/api/eliminar' method='POST'>";
                echo "<input type='hidden' name='id' value='{$cita->id}'>";
                echo "<input type='hidden' name='csrf_token' value='" . generarTokenCSRF() . "'>";
                echo "<input type='button' class='boton-eliminar' value='Cancelar Cita'>";
                echo "</form>";
            }
            // Cierre del último li
            if(end($citas) === $cita) {
                echo "</li>";
            }

        endforeach;
        ?>
    </ul>
<?php endif; ?>
