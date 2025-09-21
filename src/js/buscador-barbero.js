let diaSeleccionadoEl = null; // referencia al elemento seleccionado
let bloqueosMes = [];

document.addEventListener("DOMContentLoaded", function(){
    iniciarApp();
});

function iniciarApp(){
    buscarPorFecha();
    inicializarCalendario();
}

function buscarPorFecha(){
    const fechaInput = document.querySelector('#fecha');
    if (!fechaInput) return;

    fechaInput.addEventListener("input", function (e) {
        const fechaSeleccionada = e.target.value;
        window.location = `?fecha=${fechaSeleccionada}`;
    });
}

async function inicializarCalendario() {
    const calendarioEl = document.getElementById('calendario');
    if (!calendarioEl) return;

    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0); // resetear hora

    const calendar = new FullCalendar.Calendar(calendarioEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        firstDay: 1,
        selectable: true,
        editable: false,
        timeZone: 'local',
        headerToolbar: { left: '', center: 'title', right: 'prev,next' },
        dateClick: async function(info) {
            if (info.dayEl.classList.contains('dia-deshabilitado')) return;
            await mostrarCitas(ymdLocal(info.date), info.dayEl);
        },
        dayCellDidMount: async function(info) {
            const cellDate = info.date;
            const dayOfWeek = cellDate.getDay();
            const fechaStr = ymdLocal(cellDate);

            // Domingo o fecha pasada
            if(dayOfWeek === 0 || cellDate < hoy) {
                info.el.classList.add('dia-deshabilitado');
                return;
            }

            // Bloqueos de la fecha
            // await cargarBloqueos(fechaStr);
            // if(bloqueosMes.some(b => b.fecha === fechaStr)) {
            //     info.el.classList.add('dia-deshabilitado');
            // }
        }
    });

    calendar.render();

    // Marcar fecha inicial y cargar citas
    const fechaInput = document.querySelector('#fecha');
    const fechaParaMarcar = fechaInput?.value || ymdLocal(new Date());
    const diaEl = calendarioEl.querySelector(`[data-date="${fechaParaMarcar}"]`);
    if (diaEl && !diaEl.classList.contains('dia-deshabilitado')) {
        diaEl.classList.add('fecha-seleccionada');
        diaSeleccionadoEl = diaEl;
        mostrarCitas(fechaParaMarcar, diaEl);
    }
}


async function cargarBloqueos(fecha, barberoID = null) {
    const url = new URL(`/bloqueos/obtener`, window.location.origin);
    url.searchParams.set('fecha', fecha);
    if(barberoID) url.searchParams.set('barberoID', barberoID);

    const response = await fetch(url);
    if(!response.ok) {
        console.error("Error en la petición", response.status);
        bloqueosMes = [];
        return;
    }

    const text = await response.text();
    console.log("Respuesta cruda de bloqueos:", text); // 🔍 ver qué devuelve realmente

    try {
        bloqueosMes = JSON.parse(text);
        console.log("Bloqueos recibidos:", bloqueosMes);
    } catch(err) {
        console.error("No se pudo parsear JSON de bloqueos:", err);
        bloqueosMes = [];
    }
}

async function mostrarCitas(fechaStr, diaEl){
    try {
        // quitar selección anterior y marcar la nueva
        if (diaSeleccionadoEl) diaSeleccionadoEl.classList.remove('fecha-seleccionada');
        if (diaEl) {
            diaSeleccionadoEl = diaEl;
            diaSeleccionadoEl.classList.add('fecha-seleccionada');
        }

        // actualizar input
        const fechaInput = document.querySelector('#fecha');
        if (fechaInput) fechaInput.value = fechaStr;

        // referencia al contenedor
        const contenedor = document.getElementById('citas-barbero');
        contenedor.innerHTML = '';

        // 👉 chequeo de bloqueos
        if (bloqueosMes.some(b => b.fecha === fechaStr)) {
            contenedor.innerHTML = '<h2>Este día está bloqueado, no hay citas</h2>';
            return;
        }

        // fetch citas del barbero
        const response = await fetch(`/api/barbero/citas?fecha=${fechaStr}`);
        if (!response.ok) {
            const text = await response.text();
            console.error("Error al obtener citas:", response.status, text);
            return;
        }
        const citas = await response.json();

        // mostrar citas
        if (citas.length === 0) {
            contenedor.innerHTML = '<h2>No hay citas en esta fecha</h2>';
            return;
        }

        const ul = document.createElement('ul');
        ul.classList.add('citas');

        let idCitaAnterior = null;
        let total = 0;

        citas.forEach((cita, index) => {
            // Si cambia el ID, crear nuevo <li>
            if (cita.id !== idCitaAnterior) {
                if (idCitaAnterior !== null) {
                    // cerrar <li> anterior con total
                    const lastLi = ul.lastElementChild;
                    lastLi.innerHTML += `
                        <p class="total">Total: <span>$ ${total}</span></p>
                    `;
                }

                // iniciar nuevo <li>
                const li = document.createElement('li');
                li.innerHTML = `
                    <p>ID: <span>${cita.id}</span></p>
                    <p>Hora: <span>${cita.hora.substring(0,5)}</span></p>
                    <p>Cliente: <span>${cita.cliente || '---'}</span></p>
                    <p>Email: <span>${cita.email || '---'}</span></p>
                    <p>Teléfono: <span>${cita.telefono || '---'}</span></p>
                    <p>Barbero: <span>${cita.barbero || cita.barberoID}</span></p>
                    <h3>Servicios</h3>
                `;
                ul.appendChild(li);

                idCitaAnterior = cita.id;
                total = 0;
            }

            // agregar servicio y sumar total
            const liActual = ul.lastElementChild;
            liActual.innerHTML += `<p class="servicio">${cita.servicio} - $${cita.precio}</p>`;
            total += Number(cita.precio);

            // si es la última cita, cerrar el <li>
            if (index === citas.length - 1) {
                liActual.innerHTML += `
                    <p class="total">Total: <span>$ ${total}</span></p>
                `;
            }
        });

        contenedor.appendChild(ul);

    } catch (err) {
        console.error(err);
    }
}


function ymdLocal(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}
