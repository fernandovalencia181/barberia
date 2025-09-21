document.addEventListener('DOMContentLoaded', () => {

    function confirmar(mensaje, onConfirm) {
        document.querySelectorAll('.alerta-overlay, .alerta-global').forEach(el => el.remove());
        document.body.style.overflow = 'hidden';

        const overlay = document.createElement('div');
        overlay.classList.add('alerta-overlay');

        const alerta = document.createElement('div');
        alerta.classList.add('alerta-global');

        const texto = document.createElement('p');
        texto.textContent = mensaje;
        alerta.appendChild(texto);

        const acciones = document.createElement('div');
        acciones.classList.add('acciones'); // usa una clase específica para no romper CSS existente

        const btnOk = document.createElement('button');
        btnOk.textContent = 'Confirmar';
        btnOk.classList.add('boton', 'boton-confirmar');
        btnOk.addEventListener('click', () => {
            overlay.remove();
            alerta.remove();
            document.body.style.overflow = '';
            if (typeof onConfirm === 'function') onConfirm();
        });

        const btnCancel = document.createElement('button');
        btnCancel.textContent = 'Cancelar';
        btnCancel.classList.add('boton', 'boton-cancelar');
        btnCancel.addEventListener('click', () => {
            overlay.remove();
            alerta.remove();
            document.body.style.overflow = '';
        });

        acciones.appendChild(btnOk);
        acciones.appendChild(btnCancel);
        alerta.appendChild(acciones);

        document.body.appendChild(overlay);
        document.body.appendChild(alerta);
    }

    document.addEventListener('click', e => {
        if(e.target.classList.contains('boton-eliminar')) {
            e.preventDefault();
            const btn = e.target;
            const form = btn.closest('form'); // más seguro que btn.form
            confirmar("¿Seguro que deseas eliminar este registro?", () => {
                if(form) form.submit();
            });
        }
    });

});
