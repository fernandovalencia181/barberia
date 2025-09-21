let calendarioYaInicializado = false;
let diaSeleccionadoEl = null;
let calendar;

const inputFecha = document.querySelector('input[name="fecha"]');
const inputHora = document.querySelector('input[name="hora"]');
const contenedor = document.getElementById('horarios-container');
const selectBarbero = document.querySelector('select[name="barberoID"]');
const cita = { fecha: '', hora: '' }; // objeto para guardar la selección

function ymdLocal(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

document.addEventListener("DOMContentLoaded", iniciarApp);

function iniciarApp() {
    buscarPorFecha();
    inicializarCalendario();
}

function buscarPorFecha() {
    if (!inputFecha) return;
    inputFecha.addEventListener("input", (e) => {
        const fechaSeleccionada = e.target.value;
        window.location = `?fecha=${fechaSeleccionada}`;
    });
}

async function inicializarCalendario() {
    const calendarioEl = document.getElementById('calendario');
    if (!calendarioEl || calendarioYaInicializado) return;

    const hoy = new Date();
    hoy.setHours(0,0,0,0);

    calendar = new FullCalendar.Calendar(calendarioEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        firstDay: 1,
        selectable: true,
        editable: false,
        timeZone: 'local',
        headerToolbar: { left: 'title', center: '', right: 'prev,next' },

        dayCellDidMount: function(info) {
            const cellDate = new Date(info.date);
            cellDate.setHours(0,0,0,0);
            if (cellDate.getDay() === 0 || cellDate < hoy) {
                info.el.classList.add('dia-deshabilitado');
            } else {
                info.el.classList.add('habilitado');
            }
        },

        dateClick: async function(info) {
            if (info.dayEl.classList.contains('dia-deshabilitado')) return;

            if (diaSeleccionadoEl) diaSeleccionadoEl.classList.remove('fecha-seleccionada');
            diaSeleccionadoEl = info.dayEl;
            diaSeleccionadoEl.classList.add('fecha-seleccionada');

            const fechaStr = ymdLocal(info.date);
            inputFecha.value = fechaStr;
            cita.fecha = fechaStr;
            inputHora.value = '';
            cita.hora = '';

            if (selectBarbero.value && selectBarbero.value !== "todos") {
                await generarBotonesBloqueosYReservas(fechaStr, selectBarbero.value);
            } else {
                mostrarMensaje("Selecciona un barbero para ver los horarios");
            }
        },

        datesSet: function() {
            if (!diaSeleccionadoEl && hoy.getDay() !== 0) {
                const fechaStr = ymdLocal(hoy);
                const diaEl = calendarioEl.querySelector(`[data-date="${fechaStr}"]`);
                if (diaEl) {
                    diaEl.classList.add('fecha-seleccionada');
                    diaSeleccionadoEl = diaEl;
                    inputFecha.value = fechaStr;
                    cita.fecha = fechaStr;
                    if (selectBarbero.value && selectBarbero.value !== "todos") {
                        generarBotonesBloqueosYReservas(fechaStr, selectBarbero.value);
                    }
                }
            }
        }
    });

    calendar.render();
    calendarioYaInicializado = true;
}

function mostrarMensaje(texto) {
    contenedor.innerHTML = `<p class="mensaje-no-horarios">${texto}</p>`;
}

function generarRangoHorarios(inicioHora, inicioMinuto, finHora, finMinuto, duracion, horariosOcupados, contenedor) {
    let minutosTotales = inicioHora * 60 + inicioMinuto;
    const minutosFin = finHora * 60 + finMinuto;

    while (minutosTotales <= minutosFin) {
        const horas = String(Math.floor(minutosTotales / 60)).padStart(2, '0');
        const minutos = String(minutosTotales % 60).padStart(2, '0');
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
            boton.addEventListener('click', () => {
                document.querySelectorAll('.boton-horario').forEach(b => b.classList.remove('seleccionado'));
                boton.classList.add('seleccionado');
                inputHora.value = horario;
            });
        }

        contenedor.appendChild(boton);
        minutosTotales += duracion;
    }
}

async function generarBotonesBloqueosYReservas(fecha, barberoId) {
    contenedor.innerHTML = ''; // limpiar

    if (!fecha) return;

    const fechaObj = new Date(fecha);
    fechaObj.setHours(0, 0, 0, 0);
    const hoy = new Date(); hoy.setHours(0, 0, 0, 0);

    if (fechaObj.getDay() === 0 || fechaObj < hoy) {
        mostrarMensaje("No se pueden bloquear horarios en domingo o fechas pasadas");
        return;
    }

    if (!barberoId || barberoId === "todos") {
        mostrarMensaje("Selecciona un barbero para ver los horarios");
        return;
    }

    const horariosOcupados = new Set();

    try {
        // Traer citas
        const urlCitas = new URL('/api/citas', window.location.origin);
        urlCitas.searchParams.set('fecha', fecha);
        urlCitas.searchParams.set('barberoID', barberoId);
        const resCitas = await fetch(urlCitas);
        if (resCitas.ok) {
            const citas = await resCitas.json();
            citas.forEach(c => { if(c.hora) horariosOcupados.add(c.hora.substring(0,5)); });
        }

        // Traer bloqueos
        const urlBloqueos = new URL('/bloqueos/obtener', window.location.origin);
        urlBloqueos.searchParams.set('fecha', fecha);
        urlBloqueos.searchParams.set('barberoID', barberoId);
        const resBloq = await fetch(urlBloqueos);
        if (resBloq.ok) {
            const bloqueos = await resBloq.json();
            bloqueos.forEach(b => {
                if (b.hora) {
                    horariosOcupados.add(b.hora.substring(0,5));
                } else {
                    // Bloqueo todo el día
                    let minTotal;
                    // Mañana
                    minTotal = 8*60 + 40;
                    const minFinManana = 13*60 + 20;
                    while (minTotal <= minFinManana) {
                        const h = String(Math.floor(minTotal/60)).padStart(2,'0');
                        const m = String(minTotal%60).padStart(2,'0');
                        horariosOcupados.add(`${h}:${m}`);
                        minTotal += 40;
                    }
                    // Tarde
                    minTotal = 15*60 + 20;
                    const minFinTarde = 19*60 + 20;
                    while (minTotal <= minFinTarde) {
                        const h = String(Math.floor(minTotal/60)).padStart(2,'0');
                        const m = String(minTotal%60).padStart(2,'0');
                        horariosOcupados.add(`${h}:${m}`);
                        minTotal += 40;
                    }
                }
            });
        }

        generarRangoHorarios(8, 40, 13, 20, 40, horariosOcupados, contenedor);
        generarRangoHorarios(15, 20, 19, 20, 40, horariosOcupados, contenedor);

    } catch(err) {
        console.error('Error al cargar citas o bloqueos:', err);
        mostrarMensaje("No se pudieron cargar los horarios");
    }
}

// Manejo de cambios de fecha o barbero
inputFecha.addEventListener('change', () => {
    if (selectBarbero.value !== "todos") {
        generarBotonesBloqueosYReservas(inputFecha.value, selectBarbero.value);
    }
});

selectBarbero.addEventListener('change', () => {
    if (selectBarbero.value === "todos") {
        if (inputHora.value) {
            mostrarMensaje("Se aplicará la hora seleccionada a todos los barberos");
        } else {
            mostrarMensaje("Selecciona un barbero primero para ver los horarios");
        }
    } else {
        generarBotonesBloqueosYReservas(inputFecha.value, selectBarbero.value);
    }
});

// Inicializar si ya hay fecha y barbero seleccionado
if (inputFecha.value && selectBarbero.value !== "todos") {
    generarBotonesBloqueosYReservas(inputFecha.value, selectBarbero.value);
}
