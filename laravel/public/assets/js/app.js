// Utilidades comunes de BetterScan.
// Sin frameworks: vanilla JS para el TFG de DAW.

// Lee el token CSRF que Laravel pinta en una etiqueta <meta>.
const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.content;

// Hace una petición fetch contra Laravel con los headers que espera
// (Accept JSON, X-Requested-With y el token CSRF). Devuelve el JSON ya
// parseado. Si la respuesta no es 2xx lanza un Error con el código de
// estado y el cuerpo, para que el llamante pueda mostrar el mensaje.
window.peticionJson = async function (url, opciones = {}) {
    const cabeceras = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': tokenCsrf,
        //...(opciones.headers || {}),
        ...opciones.headers,
    };
    if (opciones.body) {
        cabeceras['Content-Type'] = 'application/json';
    }

    const respuesta = await fetch(url, {
        ...opciones,
        headers: cabeceras,
        credentials: 'same-origin',
    });

    const cuerpo = await respuesta.json().catch(() => null);
    if (!respuesta.ok) {
        const error = new Error(cuerpo?.message || respuesta.statusText);
        error.status = respuesta.status;
        error.data = cuerpo;
        throw error;
    }

    return cuerpo;
};

// Wrapper sencillo de confirm() para usar desde formularios:
//   <form onsubmit="return confirmar('¿Borrar?')">
window.confirmar = function (mensaje) {
    return window.confirm(mensaje);
};

// Activa las pestañas declarativas: cualquier elemento .tabs con botones
// data-tab="ID" muestra el panel #tab-ID y oculta los demás.
document.addEventListener('DOMContentLoaded', () => {
    const contenedoresTabs = document.querySelectorAll('.tabs');
    contenedoresTabs.forEach((tabs) => {
        const botones = tabs.querySelectorAll('button[data-tab]');
        const contenedor = tabs.parentElement;

        botones.forEach((boton) => {
            boton.addEventListener('click', () => {
                botones.forEach((b) => b.classList.remove('active'));
                boton.classList.add('active');

                const paneles = contenedor.querySelectorAll('.tab-pane');
                paneles.forEach((p) => p.classList.remove('active'));

                const panelObjetivo = contenedor.querySelector(`#tab-${boton.dataset.tab}`);
                if (panelObjetivo) {
                    panelObjetivo.classList.add('active');
                }
            });
        });
    });
});

// Mueve el <h1> de la topbar al main cuando el viewport es móvil/tablet
// (≤866px) y lo devuelve al topbar en desktop. Movemos el MISMO nodo
// (no lo clonamos) para no romper el outline semántico de la página:
// en todo momento existe un único <h1>, accesible por screen readers y
// bien indexable por SEO. Si clonáramos perderíamos esa garantía o
// tendríamos dos <h1> en el DOM, que es perjudicial.
function moverTituloSegunBreakpoint() {
    // El <h1> puede estar en la topbar (estado "desktop") o ya movido al
    // main con la clase .mobile-title (estado "móvil/tablet"). El
    // selector OR cubre los dos casos: lo encontramos esté donde esté.
    const titulo = document.querySelector('.topbar h1, main > h1.mobile-title');
    if (!titulo) return;

    const main = document.querySelector('main');
    const topbar = document.querySelector('.topbar');
    if (!main || !topbar) return;

    const esMovil = window.matchMedia('(max-width: 866px)').matches;
    const estaEnMain = titulo.parentElement === main;

    if (esMovil && !estaEnMain) {
        // Entramos en vista móvil/tablet: sacamos el h1 de la topbar
        // y lo anclamos como primer hijo del main. La clase .mobile-title
        // le da el estilo grande del CSS (font-size 2.25rem + margen).
        titulo.classList.add('mobile-title');
        main.insertBefore(titulo, main.firstChild);
    } else if (!esMovil && estaEnMain) {
        // Volvemos a desktop: el h1 regresa a la topbar entre el logo
        // (.topbar-brand) y los atajos (.topbar-actions). insertBefore
        // con .topbar-actions como referencia lo deja en el orden
        // correcto sin tener que recordar su posición original.
        titulo.classList.remove('mobile-title');
        topbar.insertBefore(titulo, topbar.querySelector('.topbar-actions'));
    }
}

document.addEventListener('DOMContentLoaded', moverTituloSegunBreakpoint);

// Debounce de 100ms en resize: el evento se dispara docenas de veces
// por segundo mientras el usuario arrastra el borde de la ventana.
// Sin debounce, llamaríamos a la función igual de veces, manipulando
// el DOM en cada tick. Con setTimeout + clearTimeout solo ejecutamos
// la última llamada — cuando el usuario "se queda quieto" 100ms.
let temporizadorResize;
window.addEventListener('resize', () => {
    clearTimeout(temporizadorResize);
    temporizadorResize = setTimeout(moverTituloSegunBreakpoint, 100);
});
