document.addEventListener("DOMContentLoaded", function(){
    iniciarApp();
});

function iniciarApp(){
    buscarPorFecha();
}

function buscarPorFecha(){
    const fechaInput = document.querySelector('#fecha');
    if (!fechaInput) return; // por si no existe el input

    fechaInput.addEventListener("input", function (e) {
        const fechaSeleccionada = e.target.value;
        window.location = `?fecha=${fechaSeleccionada}`;
    });
}