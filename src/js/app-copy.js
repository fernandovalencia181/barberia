const TOTAL_PASOS = 3;
let diaSeleccionadoEl = null;
let paso=1;
const pasoInicial=1;
let calendar;
let calendarioYaInicializado = false;

const cita={
    id:"",
    nombre:"",
    fecha:"",
    hora:"",
    servicios:[],
    barberoId: ""
}

function ymdLocal(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

// 📌 Convierte un string "YYYY-MM-DD" a Date en hora local
function parseFechaLocal(fechaStr) {
    if (/^\d{4}-\d{2}-\d{2}$/.test(fechaStr)) {
        const [anio, mes, dia] = fechaStr.split('-').map(Number);
        return new Date(anio, mes - 1, dia);
    }
    return new Date(fechaStr); // fallback si no es ese formato
}

document.addEventListener("DOMContentLoaded",function(){
    iniciarApp();
});

function iniciarApp(){
    mostrarSeccion(paso); // Muestra la seccion 1 la primera vez
    tabs(); // Cambia la sección cuando se presionen los tabs
    botonesPaginador(paso); // Agrega o quita los botones del paginador
    actualizarTabActual(paso);
    paginaSiguiente();
    paginaAnterior();
    consultarAPI(); // Consultar la API en el backend de PHP
    consultarBarberos();  // Aquí cargamos los barberos para el select

    idCliente();
    nombreCliente(); // Añade el nombre del cliente al objeto de cita
    mostrarResumen(); // Muestra el resumen de la cita
}

async function inicializarCalendario() {
    const calendarioEl = document.getElementById('calendario');
    if (calendarioYaInicializado) return;

    calendar = new FullCalendar.Calendar(calendarioEl, {
        initialView: 'dayGridMonth',
        initialDate: new Date(),
        locale: 'es',
        firstDay: 1,
        selectable: true,
        editable: false,
        timeZone: 'local',
        headerToolbar: { left: 'title', center: '', right: 'prev,next' },
        dayCellDidMount: function(info) {
            const d = new Date(info.date.getFullYear(), info.date.getMonth(), info.date.getDate());
            const hoy = new Date(); hoy.setHours(0,0,0,0);

            if(d.getDay() === 0 || d < hoy) {
                info.el.classList.add('dia-deshabilitado');
            } else {
                info.el.classList.add('habilitado');
            }
        },
        dateClick: async function(info) {
            const fechaObj = parseFechaLocal(ymdLocal(info.date));
            fechaObj.setHours(0,0,0,0);
            const hoy = new Date(); hoy.setHours(0,0,0,0);

            // Bloqueo selección de domingos y fechas pasadas
            if(fechaObj.getDay() === 0 || fechaObj < hoy) return;

            limpiarHorarios();
            document.querySelector('#hora').value = '';
            cita.hora = '';

            const fechaStr = ymdLocal(fechaObj);
            document.querySelector('#fecha').value = fechaStr;
            cita.fecha = fechaStr;

            // ✅ cargar citas y bloqueos
            // await cargarEventosYGenerarHorarios();
            // await cargarBloqueosYGenerarHorarios();
            await cargarEventosYBloqueos();

            if(diaSeleccionadoEl) diaSeleccionadoEl.classList.remove('fecha-seleccionada');
            diaSeleccionadoEl = info.dayEl;
            diaSeleccionadoEl.classList.add('fecha-seleccionada');
        }
    });

    calendar.render();
    calendarioYaInicializado = true;

    // Selección inicial: hoy
    const hoy = new Date(); hoy.setHours(0,0,0,0);
    if(hoy.getDay() !== 0) {
        const fechaInicial = ymdLocal(hoy);
        document.querySelector('#fecha').value = fechaInicial;
        cita.fecha = fechaInicial;

        // await cargarEventosYGenerarHorarios();
        // await cargarBloqueosYGenerarHorarios();
        await cargarEventosYBloqueos();

        const diaEl = calendarioEl.querySelector(`[data-date="${fechaInicial}"]`);
        if(diaEl) {
            diaEl.classList.add('fecha-seleccionada');
            diaSeleccionadoEl = diaEl;
        }
    }
}

// Función para generar horarios entre dos rangos
function generarRangoHorarios(inicioHora, inicioMinuto, finHora, finMinuto, duracion, horariosOcupados, contenedor, fechaSeleccionada) {
    let minutosTotales = inicioHora * 60 + inicioMinuto;
    const minutosFin = finHora * 60 + finMinuto;

    const ahora = new Date(); // Hora actual
    const hoyStr = ahora.toISOString().split("T")[0]; // YYYY-MM-DD de hoy

    while (minutosTotales <= minutosFin) {
        const horas = Math.floor(minutosTotales / 60).toString().padStart(2, '0');
        const minutos = (minutosTotales % 60).toString().padStart(2, '0');
        const horario = `${horas}:${minutos}`;

        const boton = document.createElement('button');
        boton.type = 'button';
        boton.classList.add('boton-horario');
        boton.textContent = horario;
        boton.dataset.hora = horario;

        // ---- comparo si el horario ya pasó ----
        let horarioPasado = false;
        if (fechaSeleccionada === hoyStr) {
            // convierto horario del botón a minutos
            const minutosHorario = parseInt(horas) * 60 + parseInt(minutos);
            const minutosAhora = ahora.getHours() * 60 + ahora.getMinutes();
            if (minutosHorario <= minutosAhora) {
                horarioPasado = true;
            }
        }

        if (horariosOcupados.has(horario) || horarioPasado) {
            boton.disabled = true;
            boton.classList.add('ocupado');
        } else {
            boton.addEventListener('click', function () {
                document.querySelectorAll('.boton-horario').forEach(b => b.classList.remove('seleccionado'));
                boton.classList.add('seleccionado');
                document.querySelector('#hora').value = horario;
                cita.hora = horario;
            });
        }

        contenedor.appendChild(boton);
        minutosTotales += duracion;
    }
}

function generarBotonesHorarios(eventos = [], fechaSeleccionada) {
    const contenedor = document.getElementById('horarios-container');
    contenedor.innerHTML = '';
    contenedor.classList.add('visible');

    const horariosOcupados = new Set();

    eventos.forEach(evento => {
        if (evento.fecha === fechaSeleccionada) {
            if (!evento.hora) {
                for (let h = 8; h <= 19; h++) {
                    if (h >= 8 && h <= 13) {
                        [20, 0, 40].forEach(min => {
                            horariosOcupados.add(`${String(h).padStart(2,'0')}:${String(min).padStart(2,'0')}`);
                        });
                    }
                    if (h >= 15 && h <= 19) {
                        [20, 0, 40].forEach(min => {
                            horariosOcupados.add(`${String(h).padStart(2,'0')}:${String(min).padStart(2,'0')}`);
                        });
                    }
                }
            } else {
                horariosOcupados.add(evento.hora.substring(0,5));
            }
        }
    });

    const duracionMinutos = 40;
    generarRangoHorarios(8, 40, 13, 20, duracionMinutos, horariosOcupados, contenedor, fechaSeleccionada);
    generarRangoHorarios(15, 20, 19, 20, duracionMinutos, horariosOcupados, contenedor, fechaSeleccionada);
}



function mostrarSeccion(paso){
    const seccionAnterior=document.querySelector(".mostrar");
    if(seccionAnterior){
        seccionAnterior.classList.remove("mostrar");
    }

    const pasoSelector=`#paso-${paso}`;
    const seccion=document.querySelector(pasoSelector);
    seccion.classList.add("mostrar");

    if (paso === 2) {
        inicializarCalendario();
    }
}

function limpiarHorarios() {
    const contenedor = document.getElementById('horarios-container');
    contenedor.innerHTML = '';
}

function actualizarTabActual(paso) {
    const tabAnterior = document.querySelector(".tabs .actual");
    if (tabAnterior) {
        tabAnterior.classList.remove("actual");
    }

    const tabActual = document.querySelector(`[data-paso="${paso}"]`);
    if (tabActual) {
        tabActual.classList.add("actual");
    }
}

function tabs(){
    const botones=document.querySelectorAll(".tabs button");

    botones.forEach( boton=>{
        boton.addEventListener("click", function(e){
            const paso=parseInt(e.target.dataset.paso);
            mostrarSeccion(paso);
            botonesPaginador(paso);
            actualizarTabActual(paso);
        })
    })
}

function botonesPaginador(paso){
    const paginaAnterior=document.querySelector("#anterior");
    const paginaSiguiente=document.querySelector("#siguiente");
    paginaAnterior.classList.remove("ocultar");
    paginaSiguiente.classList.remove("ocultar");

    paginaAnterior.classList.toggle("ocultar", paso === 1);
    paginaSiguiente.classList.toggle("ocultar", paso === TOTAL_PASOS);
    if(paso===TOTAL_PASOS){
        mostrarResumen();
    }
}

function paginaAnterior(){
    const btnAnterior = document.querySelector("#anterior");

    btnAnterior.addEventListener("click", function(){
        if (paso <= pasoInicial) return;

        paso--;
        mostrarSeccion(paso);
        botonesPaginador(paso);
        actualizarTabActual(paso);
        window.scrollTo({ top: 0, behavior: 'smooth' }); // <-- Scroll arriba suave
    });
}

function paginaSiguiente(){
    const btnSiguiente = document.querySelector("#siguiente");

    btnSiguiente.addEventListener("click", function(){
        // Validación para paso 1: servicios seleccionados
        if (paso === 1 && cita.servicios.length === 0) {
            mostrarAlerta("Selecciona al menos un servicio", "error", ".listado-servicios", true);
            return;
        }

        if (paso === 2) {
            const datosIncompletos = Object.values(cita).includes("") || cita.servicios.length === 0;
            // console.log(cita);
            if (datosIncompletos) {
                mostrarAlerta("Completa todos los campos", "error", ".formulario", true);
                return;
            }
        }

        if (paso >= TOTAL_PASOS) return;

        paso++;
        mostrarSeccion(paso);
        botonesPaginador(paso);
        actualizarTabActual(paso);
        window.scrollTo({ top: 0, behavior: 'smooth' }); // <-- Scroll arriba suave
    });
}


async function consultarAPI(){
    try {
        const url="/api/servicios";
        const resultado=await fetch(url);
        const servicios=await resultado.json();
        mostrarServicios(servicios);
    } catch (error) {
        console.log(error);
    }
}

function mostrarServicios(servicios){
    servicios.forEach(servicio=>{
        const {id,nombre,precio}=servicio;

        const nombreServicio=document.createElement("P");
        nombreServicio.classList.add("nombre-servicio");
        nombreServicio.textContent=nombre;

        const precioServicio=document.createElement("P");
        precioServicio.classList.add("precio-servicio");
        precioServicio.textContent=`$${precio}`;

        const servicioDiv=document.createElement("DIV");
        servicioDiv.classList.add("servicio");
        servicioDiv.dataset.idServicio=id;
        servicioDiv.onclick=function(){
            selecionarServicio(servicio);
        }

        servicioDiv.appendChild(nombreServicio);
        servicioDiv.appendChild(precioServicio);

        document.querySelector("#servicios").appendChild(servicioDiv);
    });
}

function selecionarServicio(servicio){
    const {id}=servicio;
    const {servicios}=cita;
    // Identificar al elemento al que se le da click
    const divServicio=document.querySelector(`[data-id-servicio="${id}"]`);
    // Comprobar si un servicio ya fue agregado
    if(servicios.some(agregado=>agregado.id===id)){
        // Eliminarlo
        cita.servicios=servicios.filter(agregado=>agregado.id!==id);
        divServicio.classList.remove("seleccionado");
    }else{
        // Agregarlo
        cita.servicios=[...servicios,servicio];
        divServicio.classList.add("seleccionado");
    }
}

function idCliente(){
    cita.id=document.querySelector("#id").value;
}

function nombreCliente(){
    cita.nombre=document.querySelector("#nombre").value;
}

function mostrarAlerta(mensaje,tipo,elemento,desaparece=true){
    //Previene que se genere mas de una alerta
    const alertaPrevia=document.querySelector(".alerta");
    if(alertaPrevia){
        alertaPrevia.remove();
    }
    // Scripting para generar la alerta
    const alerta=document.createElement("DIV");
    alerta.textContent=mensaje;
    alerta.classList.add("alerta");
    alerta.classList.add(tipo);

    const referencia=document.querySelector(elemento);
    referencia.appendChild(alerta);
    if(desaparece){
        // Eliminar la alerta
        setTimeout(() => {
            alerta.remove();
        }, 3000);
    }

}

function mostrarResumen(){
    const resumen=document.querySelector(".contenido-resumen");

    // Limpiar el Contenido de Resumen
    while(resumen.firstChild){
        resumen.removeChild(resumen.firstChild);
    }

    // Formatear el div de resumen
    const {nombre,fecha,hora,servicios}=cita;

    // Heading para Servicios en Resumen
    const headingServicios=document.createElement("H3");
    headingServicios.textContent="Resumen de Servicios";
    resumen.appendChild(headingServicios);

    // Iterando y mostrando los servicios
    servicios.forEach(servicio=>{
        const {id,precio,nombre}=servicio;
        const contenedorServicio=document.createElement("DIV");
        contenedorServicio.classList.add("contenedor-servicio");

        const textoServicio=document.createElement("P");
        textoServicio.innerHTML=`Servicio: <span>${nombre}</span>`;

        const precioServicio=document.createElement("P");
        precioServicio.innerHTML=`Precio: <span>$${precio}</span>`;

        contenedorServicio.appendChild(textoServicio);
        contenedorServicio.appendChild(precioServicio);

        resumen.appendChild(contenedorServicio);
    });

    // Heading para Cita en Resumen
    const headingCita=document.createElement("H3");
    headingCita.textContent="Resumen de Cita";
    resumen.appendChild(headingCita);

    const nombreCliente=document.createElement("P");
    nombreCliente.innerHTML=`Nombre: <span>${nombre}</span>`;

    // Después (todo local):
    const opciones = { weekday:"long", year:"numeric", month:"long", day:"numeric" };
    const fechaObjResumen = parseFechaLocal(fecha); // Usar la función parseFechaLocal
    const fechaFormateada = fechaObjResumen.toLocaleDateString("es-ES", opciones);

    const fechaCita=document.createElement("P");
    fechaCita.innerHTML=`Fecha: <span>${fechaFormateada}</span>`;

    const horaCita=document.createElement("P");
    horaCita.innerHTML=`Hora: <span>${hora} Horas</span>`;

    const barberoCita = document.createElement("P");
    barberoCita.innerHTML = `Barbero: <span>${cita.barberoNombre}</span>`;

    // Boton pra Crear una cita
    const botonReservar=document.createElement("BUTTON");
    botonReservar.id = "btnReservar"; // <-- agregamos el id
    botonReservar.classList.add("boton");
    botonReservar.textContent="Reservar Cita";
    botonReservar.onclick=reservarCita;

    resumen.appendChild(nombreCliente);
    resumen.appendChild(fechaCita);
    resumen.appendChild(horaCita);
    resumen.appendChild(barberoCita);
    resumen.appendChild(botonReservar);
}

async function reservarCita(){
    const btnReservar = document.querySelector("#btnReservar"); // ajusta el selector
    btnReservar.disabled = true; // deshabilitamos el botón al inicio
    btnReservar.textContent = "Procesando..."; // opcional, para dar feedback

    if (!cita.barberoId) {
        mostrarAlerta("Selecciona un barbero antes de reservar", "error", ".contenido-resumen");
        btnReservar.disabled = false;
        btnReservar.textContent = "Reservar";
        return;
    }

    const {nombre,fecha,hora,servicios,id}=cita;
    const idServicios=servicios.map(servicio=>servicio.id);
    const datos=new FormData();
    const csrfToken = document.querySelector("#csrf_token").value;
    
    datos.append("csrf_token", csrfToken);
    datos.append("fecha",fecha);
    datos.append("hora",hora);
    datos.append("usuarioID",id);
    datos.append("servicios",idServicios);
    datos.append("barberoID", cita.barberoId); // enviamos el barbero seleccionado

    try {
        const url = "/api/citas";
        const respuesta = await fetch(url, {
            method: "POST",
            body: datos
        });

        const respuestaJson = await respuesta.json();

        if (respuestaJson.resultado && respuestaJson.resultado.resultado) {
            Swal.fire({
                title: `<strong>Cita creada correctamente</strong>`,
                html: `
                    <p><span>Barbero:</span> <b>${cita.barberoNombre}</b></p>
                    <p><span>Fecha:</span> <b>${fecha}</b></p>
                    <p><span>Hora:</span> <b>${hora}</b></p>
                `,
                icon: "success",
                showConfirmButton: true,
                confirmButtonText: "Aceptar",
                background: "#f0f8ff",   // color de fondo acorde a tu web
                iconColor: "#28a745",    // color del check
                customClass: {
                    title: "swal-title",
                    htmlContainer: "swal-text"
                }
            }).then(() => {
                window.location.href = "/citas";
            });
        } else {
            throw new Error("Error en la respuesta del servidor");
        }
    } catch (error) {
        Swal.fire({
            icon: "error",
            title: "⚠ Error",
            text: "Hubo un error al guardar la cita",
            confirmButtonText: "Intentar de nuevo",
            background: "#fff3f3",
            iconColor: "#dc3545"
        });
        btnReservar.disabled = false; // volvemos a habilitar si falla
        btnReservar.textContent = "Reservar";
    }
}

async function consultarBarberos() {
    try {
        const url = "/api/barberos"; // Ruta que debe devolver lista de usuarios con rol 'barbero'
        const resultado = await fetch(url);
        const barberos = await resultado.json();

        llenarSelectBarberos(barberos);
    } catch (error) {
        console.error("Error cargando barberos:", error);
    }
}

async function seleccionarBarberoAutomatico(cita, barberos) {
    // Traemos todas las citas ocupadas de esa fecha
    const response = await fetch(`/api/citas/fecha?fecha=${cita.fecha}`);
    const citasOcupadas = await response.json();

    // Filtramos barberos libres a esa hora
    let candidatos = barberos.filter(barbero => {
        return !citasOcupadas.some(c => c.barberoID == barbero.id && c.hora === cita.hora);
    });

    if (candidatos.length > 0) {
        // Elegir aleatorio si hay varios
        const elegido = candidatos[Math.floor(Math.random() * candidatos.length)];
        cita.barberoId = elegido.id;
        cita.barberoNombre = elegido.nombre;
    } else {
        console.warn("Ningún barbero disponible a esa hora");
    }

    return cita;
}

async function llenarSelectBarberos(barberos) {
    const selectBarbero = document.querySelector("#barbero");

    // Limpiar opciones previas
    selectBarbero.innerHTML = "";

    // Placeholder
    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "-- Selecciona un barbero --";
    selectBarbero.appendChild(placeholder);

    // Agregar barberos
    barberos.forEach(barbero => {
        const option = document.createElement("option");
        option.value = barbero.id;
        option.textContent = barbero.nombre + " " + barbero.apellido;
        option.dataset.image = barbero.imagen ? "/uploads/" + barbero.imagen : "";
        selectBarbero.appendChild(option);
    });

    // ⚡ Esperar la selección automática antes de transformar
    await seleccionarBarberoAutomatico(cita, barberos);

    // Actualizamos el <select> con el barbero elegido
    if (cita.barberoId) {
        selectBarbero.value = cita.barberoId;
    }

    // Transformar en custom select (ahora sí reflejará la selección)
    transformarSelectEnCustom("barbero");

    // Escuchar cambios manuales
    selectBarbero.addEventListener("change",async function() {
        const selectedOption = this.options[this.selectedIndex];
        cita.barberoId = selectedOption.value;
        cita.barberoNombre = selectedOption.textContent;
        // Limpiar horarios y selección
        // Limpiar horarios y selección
        limpiarHorarios();
        cita.hora = '';
        document.querySelector('#hora').value = '';
        // ⚡ Cargar citas y bloqueos del barbero seleccionado
        // await cargarEventosYGenerarHorarios();
        // await cargarBloqueosYGenerarHorarios();
        await cargarEventosYBloqueos();
    });
}

async function cargarEventosYBloqueos() {
    const fechaSeleccionada = cita.fecha;
    const barberoId = cita.barberoId;

    if (!fechaSeleccionada || !barberoId) {
        limpiarHorarios();
        return;
    }

    try {
        // 🚀 Lanzamos las dos consultas en paralelo
        const [respCitas, respBloqueos] = await Promise.all([
            fetch(`/api/citas?fecha=${fechaSeleccionada}&barberoID=${barberoId}`),
            fetch(`/bloqueos/obtener?fecha=${fechaSeleccionada}&barberoID=${barberoId}`)
        ]);

        const citas = respCitas.ok ? await respCitas.json() : [];
        const bloqueos = respBloqueos.ok ? await respBloqueos.json() : [];

        // console.log("Citas ocupadas recibidas:", citas);
        // console.log("Bloqueos recibidos:", bloqueos);

        // 🔗 Unimos ambos resultados
        const eventos = [...citas, ...bloqueos];

        generarBotonesHorarios(eventos, fechaSeleccionada);
    } catch (error) {
        console.error("Error al cargar eventos y bloqueos:", error);
        limpiarHorarios();
    }
}

function transformarSelectEnCustom(selectId) {
    const select = document.getElementById(selectId);
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

        // Mostrar imagen en cada opción del dropdown
        if(option.dataset.image){
            const img = document.createElement("img");
            img.src = option.dataset.image;
            img.classList.add("img-option");
            div.prepend(img);
        }

        // 🚫 Deshabilitar placeholder
        if(option.value === ""){
            div.classList.add("disabled");
        }

        // Si esta opción es la seleccionada al cargar
        if(select.selectedIndex === Array.from(select.options).indexOf(option)){
            actualizarSelected(option);
        }

        div.addEventListener("click", function() {
            if (div.classList.contains("disabled")) return;
            select.value = this.dataset.value;
            actualizarSelected(option);
            optionsContainer.style.display = "none";
            select.dispatchEvent(new Event('change'));
        });

        optionsContainer.appendChild(div);
    });

    customSelect.appendChild(optionsContainer);

    // Toggle al hacer click
    selected.addEventListener("click", function() {
        optionsContainer.style.display =
            optionsContainer.style.display === "block" ? "none" : "block";
    });

    // Cerrar si se hace click fuera
    document.addEventListener("click", function(e) {
        if (!customSelect.contains(e.target)) {
            optionsContainer.style.display = "none";
        }
    });

    // Insertar en el DOM
    select.style.position = "absolute";
    select.style.left = "-9999px";
    select.style.visibility = "hidden";
    select.style.display = "none";
    select.parentNode.insertBefore(customSelect, select.nextSibling);
}
