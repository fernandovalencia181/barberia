let calendar;
let calendarioYaInicializado = false;
let diaSeleccionadoEl = null;
let cita = { fecha: '', hora: '' };

async function inicializarCalendario() {
    const calendarioEl = document.getElementById('calendario');
    const inputFecha = document.getElementById('fecha');
    const inputHora = document.querySelector('input[name="hora"]');
    const selectBarbero = document.querySelector('select[name="barberoID"]');

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

        // --- Deshabilitar domingos y días pasados ---
        dayCellDidMount: function(info) {
            const cellDate = new Date(info.date);
            cellDate.setHours(0,0,0,0);
            if (cellDate.getDay() === 0 || cellDate < hoy) {
                info.el.classList.add('dia-deshabilitado');
            } else {
                info.el.classList.add('habilitado');
            }
        },

        // --- Click en fecha ---
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

        // --- Al cambiar de mes ---
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

// Helper → fecha YYYY-MM-DD
function ymdLocal(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function transformarSelectEnCustom(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const customSelect = document.createElement("div");
    customSelect.classList.add("custom-select");
    customSelect.style.display = "block";

    const selected = document.createElement("div");
    selected.classList.add("selected");
    customSelect.appendChild(selected);

    const optionsContainer = document.createElement("div");
    optionsContainer.classList.add("options");

    // Función para actualizar el selected
    function actualizarSelected(option) {
        selected.innerHTML = ""; // limpiar
        if(option.dataset.image){
            const selImg = document.createElement("img");
            selImg.src = option.dataset.image;
            selImg.classList.add("img-option");
            selected.appendChild(selImg);
            selected.appendChild(document.createTextNode(" " + option.textContent));
        } else {
            selected.textContent = option.textContent;
        }
    }

    Array.from(select.options).forEach(option => {
        const div = document.createElement("div");
        div.classList.add("option");
        div.textContent = option.textContent;
        div.dataset.value = option.value;

        if(option.dataset.image){
            const img = document.createElement("img");
            img.src = option.dataset.image;
            img.classList.add("img-option");
            div.prepend(img);
        }

        if(option.value === ""){
            div.classList.add("disabled");
        }

        if(select.selectedIndex === Array.from(select.options).indexOf(option)){
            actualizarSelected(option);
        }

        div.addEventListener("click", function() {
            if (div.classList.contains("disabled")) return;
            select.value = this.dataset.value;
            actualizarSelected(option);
            optionsContainer.style.display = "none";
            select.dispatchEvent(new Event('change')); // 🔑 aquí se engancha tu lógica actual
        });

        optionsContainer.appendChild(div);
    });

    customSelect.appendChild(optionsContainer);

    selected.addEventListener("click", function() {
        optionsContainer.style.display =
            optionsContainer.style.display === "block" ? "none" : "block";
    });

    document.addEventListener("click", function(e) {
        if (!customSelect.contains(e.target)) {
            optionsContainer.style.display = "none";
        }
    });

    // Reemplazar visualmente al select original
    select.style.position = "absolute";
    select.style.left = "-9999px";
    select.style.visibility = "hidden";
    select.style.display = "none";
    select.parentNode.insertBefore(customSelect, select.nextSibling);
}


document.addEventListener("DOMContentLoaded", () => {
    inicializarCalendario();

    const selectBarbero = document.querySelector('select[name="barberoID"]');
    const inputFecha = document.querySelector('#fecha');
    const wrapper = document.getElementById('horarios-wrapper');

    transformarSelectEnCustom('barberoID');

    // Estado inicial
    if (selectBarbero.value === "todos") {
        wrapper.innerHTML = '<p class="mensaje-no-horarios">Selecciona un barbero para ver los horarios</p>';
    } else {
        generarBotonesBloqueosYReservas(inputFecha.value, selectBarbero.value);
    }

    // Listener cambio de barbero
    selectBarbero.addEventListener('change', () => {
        const inputHora = document.querySelector('input[name="hora"]');
        const inputFecha = document.getElementById('fecha');

        if (selectBarbero.value === "todos") {
            // Mantener hora si ya existe
            if (inputHora.value) {
                mostrarMensaje("Se aplicará la hora seleccionada a todos los barberos");
            } else {
                mostrarMensaje("Selecciona un barbero primero para ver los horarios");
            }
        } else {
            // 👇 Limpiar hora
            inputHora.value = "";

            // 👇 Si ya había fecha seleccionada, regenerar horarios automáticamente
            if (inputFecha.value) {
                generarBotonesBloqueosYReservas(inputFecha.value, selectBarbero.value);
            }
        }
    });
});


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
                document.querySelector('input[name="hora"]').value = horario;
            });
        }

        contenedor.appendChild(boton);
        minutosTotales += duracion;
    }
}

async function generarBotonesBloqueosYReservas(fecha, barberoId) {
    const wrapper = document.getElementById('horarios-wrapper');
    if (!wrapper) return console.error('No existe el wrapper #horarios-wrapper en el HTML');
    
    // Limpiar siempre
    wrapper.innerHTML = '';

    if (!fecha) return;

    const fechaObj = new Date(fecha);
    fechaObj.setHours(0, 0, 0, 0);
    const hoy = new Date(); hoy.setHours(0, 0, 0, 0);

    // Validación de fecha
    if (fechaObj.getDay() === 0 || fechaObj < hoy) {
        wrapper.innerHTML = '<p class="mensaje-no-horarios">No se pueden bloquear horarios en domingo o fechas pasadas</p>';
        return;
    }

    if (!barberoId || barberoId === "todos") {
        wrapper.innerHTML = '<p class="mensaje-no-horarios">Selecciona un barbero para ver los horarios</p>';
        return;
    }

    const horariosOcupados = new Set();

    try {
        // --- Traer citas ocupadas ---
        const urlCitas = new URL('/api/citas', window.location.origin);
        urlCitas.searchParams.set('fecha', fecha);
        urlCitas.searchParams.set('barberoID', barberoId);
        const resCitas = await fetch(urlCitas);
        if (resCitas.ok) {
            const citas = await resCitas.json();
            citas.forEach(c => { if(c.hora) horariosOcupados.add(c.hora.substring(0,5)); });
        }

        // --- Traer bloqueos existentes ---
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
                    // Bloqueo todo el día → marcar todos los horarios
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

        // --- Crear título ---
        const titulo = document.createElement('p');
        titulo.classList.add('descripcion-pagina');
        titulo.textContent = 'Hora (dejar vacío para todo el día):';
        wrapper.appendChild(titulo);

        // --- Crear contenedor de botones ---
        const contenedor = document.createElement('div');
        contenedor.id = 'horarios-container';
        contenedor.classList.add('horarios-grid');
        contenedor.classList.add('visible');
        wrapper.appendChild(contenedor);

        // --- Generar botones ---
        generarRangoHorarios(8, 40, 13, 20, 40, horariosOcupados, contenedor);
        generarRangoHorarios(15, 20, 19, 20, 40, horariosOcupados, contenedor);

    } catch(err) {
        console.error('Error al cargar citas o bloqueos:', err);
        wrapper.innerHTML = '<p class="mensaje-no-horarios">No se pudieron cargar los horarios</p>';
    }
}

// --- Mensaje de error ---
function mostrarMensaje(texto) {
    const wrapper = document.getElementById('horarios-wrapper');
    if (!wrapper) return;
    wrapper.innerHTML = `<p class="mensaje-no-horarios">${texto}</p>`;
}

document.getElementById('crear-bloqueo').addEventListener('click', () => {
    const form = document.querySelector('form');
    const inputFecha = document.getElementById('fecha');
    const selectBarbero = document.querySelector('select[name="barberoID"]');

    if (!inputFecha.value) {
        mostrarMensaje("Selecciona una fecha primero");
        return;
    }

    // Lógica para todos los barberos
    if (selectBarbero.value === "todos") {
        confirmar("Se aplicará el bloqueo a todos los barberos, ¿deseas continuar?", "warning", true, form);
        return;
    }

    confirmar("¿Deseas crear el bloqueo para este barbero y fecha?", "warning", true, form);
});


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

