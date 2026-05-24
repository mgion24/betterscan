// Botón "mostrar/ocultar contraseña" que se activa con la clase
// .toggle-pwd y un atributo data-target con el id del input password.

'use strict';

const ICONO_MOSTRAR = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
const ICONO_OCULTAR = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/></svg>';

document.addEventListener('click', (evento) => {
    const boton = evento.target.closest('.toggle-pwd');

    // Si el clic no es sobre un botón de mostrar/ocultar contraseña
    // no hacemos nada; el resto del comportamiento (submit, navegación)
    // sigue funcionando normalmente.
    if (boton) {
        evento.preventDefault();
        const idObjetivo = boton.dataset.target;
        const input = idObjetivo ? document.getElementById(idObjetivo) : null;

        if (input) {
            const ocultaActualmente = input.type === 'password';
            input.type = ocultaActualmente ? 'text' : 'password';
            boton.innerHTML = ocultaActualmente ? ICONO_OCULTAR : ICONO_MOSTRAR;
            boton.setAttribute(
                'aria-label',
                ocultaActualmente ? 'Ocultar contraseña' : 'Mostrar contraseña'
            );
        }
    }
});
