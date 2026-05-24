// Polling del estado del escaneo (cumple RF-06 y RNF-04 de la Tarea 2).
// Cada 2 segundos pregunta a Laravel cómo va el escaneo y refresca
// la barra de progreso y la fase actual sin recargar la página.
// Cuando el escaneo termina, para el polling y redirige a resultados.

'use strict';

const URL_ESTADO = window.ESCANEO_STATUS_URL;
const URL_RESULTADOS = window.ESCANEO_RESULTADOS_URL;
const INTERVALO_MS = 2000;
const estadoInicial = window.ESCANEO_ESTADO_INICIAL;

// Si la pantalla se ha cargado sin URL de polling o con el escaneo ya
// terminado, no hay nada que actualizar. En cualquier otro caso
// arrancamos el ciclo de polling.
const debeIniciar = URL_ESTADO
    && estadoInicial !== 'completado'
    && estadoInicial !== 'error';

if (debeIniciar) {
    const elementoEstado = document.querySelector('[data-estado]');
    const elementoFase = document.querySelector('[data-fase]');
    const elementoProgreso = document.querySelector('[data-progreso]');
    const barraProgreso = document.querySelector('progress');

    let temporizador = null;

    async function consultarEstado() {
        try {
            const datos = await window.peticionJson(URL_ESTADO);

            if (elementoEstado) {
                elementoEstado.textContent = datos.estado;
                elementoEstado.className = `badge badge-${datos.estado}`;
            }
            if (elementoFase) {
                elementoFase.textContent = datos.fase_actual || '—';
            }
            if (elementoProgreso) {
                elementoProgreso.textContent = datos.progreso_pct ?? 0;
            }
            if (barraProgreso) {
                barraProgreso.value = datos.progreso_pct ?? 0;
            }

            if (datos.estado === 'completado') {
                clearInterval(temporizador);
                // Pequeña pausa para que el usuario vea el 100% antes de saltar.
                setTimeout(() => {
                    window.location.href = URL_RESULTADOS;
                }, 800);
            } else if (datos.estado === 'error') {
                clearInterval(temporizador);
                window.location.reload();
            }
        } catch (e) {
            console.warn('No se pudo consultar el estado del escaneo:', e);
        }
    }

    consultarEstado();
    temporizador = setInterval(consultarEstado, INTERVALO_MS);
}
