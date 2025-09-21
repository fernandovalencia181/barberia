
let diaSeleccionadoEl = null;
let cita = {}; // objeto global para la cita actual

document.addEventListener("DOMContentLoaded", () => {
    iniciarApp();
});

const contenedor = document.getElementById('citas-admin');

function iniciarApp(){
    buscarPorFecha();
    inicializarCalendarioAdmin();
}

function buscarPorFecha(){
    const fechaInput = document.querySelector('#fecha');
    if (!fechaInput) return;

    fechaInput.addEventListener("input", function (e) {
        const fechaSeleccionada = e.target.value;
        window.location = `?fecha=${fechaSeleccionada}`;
    });
}

function inicializarCalendarioAdmin() {
    const calendarioEl = document.getElementById('calendario');
    if (!calendarioEl) return;

    const calendar = new FullCalendar.Calendar(calendarioEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        firstDay: 1,
        selectable: true,
        editable: false,
        timeZone: 'local',
        headerToolbar: { left: 'title', center: '', right: 'prev,next' },
        dateClick: async function(info) {
            if (diaSeleccionadoEl) diaSeleccionadoEl.classList.remove('fecha-seleccionada');

            diaSeleccionadoEl = info.dayEl;
            diaSeleccionadoEl.classList.add('fecha-seleccionada');

            const fechaStr = ymdLocal(info.date);
            const fechaInput = document.querySelector('#fecha');
            if (fechaInput) fechaInput.value = fechaStr;

            await cargarCitasAdmin(fechaStr);
        }
    });

    calendar.render();

    // marcar fecha inicial
    const fechaInput = document.querySelector('#fecha');
    const fechaParaMarcar = fechaInput?.value || ymdLocal(new Date());
    const diaEl = calendarioEl.querySelector(`[data-date="${fechaParaMarcar}"]`);
    if(diaEl) {
        diaEl.classList.add('fecha-seleccionada');
        diaSeleccionadoEl = diaEl;
    }

    cargarCitasAdmin(fechaParaMarcar);
}

function ymdLocal(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

async function cargarCitasAdmin(fecha) {
    try {
        const response = await fetch(`/api/admin/citas?fecha=${fecha}`);
        if (!response.ok) throw new Error('Error al obtener citas');
        const citas = await response.json();

        const contenedor = document.getElementById('citas-admin');
        contenedor.innerHTML = '';

        if (citas.length === 0) {
            contenedor.innerHTML = '<h3>No hay citas en esta fecha</h3>';
            return;
        }

        const ul = document.createElement('ul');
        ul.classList.add('citas');

        let idCitaAnterior = null;
        let liActual = null;
        let total = 0;

        citas.forEach((citaItem, index) => {
            if(citaItem.id !== idCitaAnterior){
                // si había una cita anterior, actualizar su total
                if(liActual){
                    liActual.querySelector('.total span').textContent = `$ ${total}`;
                }

                // crear un nuevo <li> para esta cita
                liActual = document.createElement('li');
                liActual.dataset.citaId = citaItem.id;
                liActual.innerHTML = `
                    <p>ID: <span>${citaItem.id}</span></p>
                    <p>Hora: <span>${citaItem.hora.substring(0,5)}</span></p>
                    <p>Cliente: <span>${citaItem.cliente || '---'}</span></p>
                    <p>Email: <span>${citaItem.email || '---'}</span></p>
                    <p>Teléfono: <span>${citaItem.telefono || '---'}</span></p>
                    <p>Barbero: <span>${citaItem.barbero}</span></p>
                    <h3>Servicios</h3>
                    <div class="servicios"></div>
                    <p class="total">Total: <span>$ 0</span></p>
                    <div class="acciones servicios">
                        <form action="/api/eliminar" method="POST">
                            <input type="hidden" name="id" value="${citaItem.id}">
                            <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                            <input type="submit" class="boton-eliminar" value="Eliminar">
                        </form>
                        <button class="btn-actualizar-hora boton" 
                            data-cita-id="${citaItem.id}" 
                            data-barbero-id="${citaItem.barberoID}" 
                            data-fecha="${fecha}">Cambiar Hora</button>
                    </div>
                    <div class="horarios-grid"></div>
                `;
                ul.appendChild(liActual);

                // resetear total
                idCitaAnterior = citaItem.id;
                total = 0;
            }

            // agregar servicio a la cita actual
            const serviciosDiv = liActual.querySelector('.servicios');
            serviciosDiv.innerHTML += `<p>${citaItem.servicio}: <span>$${citaItem.precio}</span></p>`;
            total += Number(citaItem.precio);

            // si es la última, cerrar el total
            if(index === citas.length - 1){
                liActual.querySelector('.total span').textContent = `$ ${total}`;
            }
        });

        contenedor.appendChild(ul);

        generarResumenBarberos(citas, contenedor);

    } catch(err){
        console.error(err);
    }
}

function generarResumenBarberos(citas, contenedor) {
    // Acumulador por barbero
    const resumenBarberos = {};
    let totalGeneral = 0;
    let cortesTotales = 0;

    citas.forEach(cita => {
        if (!resumenBarberos[cita.barbero]) {
            resumenBarberos[cita.barbero] = { cantidad: 0, total: 0 };
        }
        resumenBarberos[cita.barbero].cantidad += 1;
        resumenBarberos[cita.barbero].total += Number(cita.precio);

        totalGeneral += Number(cita.precio);
        cortesTotales++;
    });

    // Crear bloque HTML
    const resumenDiv = document.createElement('div');
    resumenDiv.classList.add('resumen-barberos');
    resumenDiv.innerHTML = '<h3>Resumen</h3>';

    for (const [barbero, data] of Object.entries(resumenBarberos)) {
        resumenDiv.innerHTML += `
            <p>${barbero}: <span>${data.cantidad} cortes</span> - Total: <span>$${data.total}</span></p>
        `;
    }

    resumenDiv.innerHTML += `
        <hr>
        <p><strong>Total cortes:</strong> <span>${cortesTotales}</span></p>
        <p><strong>Total general:</strong> <span>$${totalGeneral}</span></p>
    `;

    contenedor.appendChild(resumenDiv);
}



// -------------------- HORARIOS DISPONIBLES --------------------
async function mostrarHorariosDisponibles(fecha, barberoId, citaId, contenedor){
    try{
        const response = await fetch(`/api/citas?fecha=${fecha}&barberoID=${barberoId}`);
        if(!response.ok) throw new Error('Error al obtener citas');

        const citasOcupadasArr = await response.json();
        const horariosOcupados = new Set(
            citasOcupadasArr.map(c => c.hora?.substring(0,5))
        );

        contenedor.innerHTML = '';
        // contenedor.classList.add('visible');

        const duracionMinutos = 40;

        generarRangoHorarios(8, 20, 13, 20, duracionMinutos, horariosOcupados, contenedor);
        generarRangoHorarios(15, 20, 19, 20, duracionMinutos, horariosOcupados, contenedor);

        const btnGuardar = document.createElement('button');
        btnGuardar.type = 'button';
        btnGuardar.textContent = 'Guardar';
        btnGuardar.classList.add('boton', 'boton-guardar');
        btnGuardar.addEventListener('click', async () => {
            const horaSeleccionada = contenedor.dataset.horaSeleccionada;
            if (!horaSeleccionada) return alert('Seleccione un horario primero');
            await actualizarHoraCita(citaId, horaSeleccionada);
        });

        contenedor.appendChild(btnGuardar);
    } catch(err){
        console.error(err);
    }
}


// Función para generar horarios entre dos rangos
function generarRangoHorarios(inicioHora, inicioMinuto, finHora, finMinuto, duracion, horariosOcupados, contenedor) {
    let minutosTotales = inicioHora * 60 + inicioMinuto;
    const minutosFin = finHora * 60 + finMinuto;

    while (minutosTotales <= minutosFin) {
        const horas = Math.floor(minutosTotales / 60).toString().padStart(2, '0');
        const minutos = (minutosTotales % 60).toString().padStart(2, '0');
        const horario = `${horas}:${minutos}`;

        const boton = document.createElement('button');
        boton.type = 'button';
        boton.classList.add('boton-horario');
        boton.textContent = horario;
        boton.dataset.hora = horario;

        if (horariosOcupados.has(horario)) {
            boton.disabled = true;
            boton.classList.add('ocupado');
        } else {
            boton.addEventListener('click', function () {
                document.querySelectorAll('.boton-horario').forEach(b => b.classList.remove('seleccionado'));
                boton.classList.add('seleccionado');
                // cita.hora = horario;
                contenedor.dataset.horaSeleccionada = horario;
            });
        }

        contenedor.appendChild(boton);
        minutosTotales += duracion;
    }
}

// -------------------- ACTUALIZAR HORA --------------------
async function actualizarHoraCita(citaId, nuevaHora){
    try{
        const response = await fetch(`/api/citas/actualizar-hora`, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id:citaId, hora:nuevaHora, csrf_token: csrfToken})
        });

        // if(!response.ok) throw new Error('Error al actualizar hora');
        // alert('Hora actualizada correctamente');

        // recargar citas
        const fechaInput = document.querySelector('#fecha');
        if(fechaInput){
            await cargarCitasAdmin(fechaInput.value);
        }

    }catch(err){
        console.error(err);
        alert('No se pudo actualizar la hora');
    }
}

document.getElementById('scrollBtn').addEventListener('click', (e) => {
    e.preventDefault();
    document.querySelector('.resumen-barberos').scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
});

// -------------------- FUNCIONES DE ALERTA --------------------

function confirmar(mensaje, tipo, mostrarBotones=false, form=null) {
    // Evitar que haya otra alerta
    const alertaPrevia = document.querySelector(".alerta-global");
    if (alertaPrevia) alertaPrevia.remove();

    // Deshabilitar scroll
    document.body.style.overflow = 'hidden';

    // Overlay
    const overlay = document.createElement("div");
    overlay.classList.add("alerta-overlay");

    const alerta = document.createElement("div");
    alerta.classList.add("alerta-global", tipo);

    const mensajeEl = document.createElement("p");
    mensajeEl.textContent = mensaje;
    alerta.appendChild(mensajeEl);

    if (mostrarBotones && form) {
        const accionesDiv = document.createElement("div");
        accionesDiv.classList.add("acciones");

        const botonConfirmar = document.createElement("button");
        botonConfirmar.textContent = "Confirmar";
        botonConfirmar.classList.add("boton", "boton-confirmar");
        botonConfirmar.addEventListener("click", () => {
            alerta.remove();
            overlay.remove();
            document.body.style.overflow = ''; // habilitar scroll
            form.submit();
        });

        const botonCancelar = document.createElement("button");
        botonCancelar.textContent = "Cancelar";
        botonCancelar.classList.add("boton", "boton-cancelar");
        botonCancelar.addEventListener("click", () => {
            alerta.remove();
            overlay.remove();
            document.body.style.overflow = ''; // habilitar scroll
        });

        accionesDiv.appendChild(botonConfirmar);
        accionesDiv.appendChild(botonCancelar);
        alerta.appendChild(accionesDiv);
    }

    overlay.appendChild(alerta);
    document.body.appendChild(overlay);
}

contenedor.addEventListener('click', async e => {
    if (e.target.classList.contains('boton-eliminar')) {
        e.preventDefault();
        const form = e.target.closest('form');
        confirmar("¿Seguro que deseas eliminar esta cita?", "warning", true, form);
    }

    if (e.target.classList.contains('btn-actualizar-hora')) {
        const btn = e.target;
        const li = btn.closest('li');
        const horariosGrid = li.querySelector('.horarios-grid');

        if (horariosGrid.classList.contains('visible')) {
            horariosGrid.classList.remove('visible');
            btn.textContent = 'Cambiar Hora';
        } else {
            horariosGrid.classList.add('visible');
            btn.textContent = 'Contraer';

            if (!horariosGrid.hasChildNodes()) {
                const citaId = btn.dataset.citaId;
                const barberoId = btn.dataset.barberoId;
                const fecha = btn.dataset.fecha;
                await mostrarHorariosDisponibles(fecha, barberoId, citaId, horariosGrid);
            }
        }
    }
});

