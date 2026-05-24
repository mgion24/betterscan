// Asistente para configurar un escaneo en 4 pasos.
// La validación de los datos la hace Laravel cuando se pulsa "Lanzar".
// Aquí solo gestionamos la navegación entre pasos, la vista previa del
// comando nmap equivalente y mostrar/ocultar campos según opciones.

'use strict';

const formulario = document.getElementById('wizard-form');

// Si la página actual no contiene el formulario del asistente,
// este script no tiene nada que hacer (por ejemplo está cargado en
// una vista distinta).
if (formulario) {

    // -------------------------------------------------
    // Referencias a los elementos del DOM que vamos a usar.
    // -------------------------------------------------
    const pasos = formulario.querySelectorAll('.wizard-step');
    const indicadores = formulario.querySelectorAll('[data-step-indicator]');
    const botonAnterior = document.getElementById('btn-anterior');
    const botonSiguiente = document.getElementById('btn-siguiente');
    const botonLanzar = document.getElementById('btn-lanzar');

    const selectorPuertos = document.getElementById('puertos');
    const cajaPuertosCustom = document.getElementById('wrapper-puertos-custom');

    const checkGobuster = document.getElementById('gobuster_habilitar');
    const cardGobuster = document.getElementById('card-gobuster');

    const selectorGobusterModo = document.getElementById('gobuster_modo');
    const avisoVhost = document.getElementById('aviso-vhost');

    const botonDetectarRed = document.getElementById('btn-detectar-red');
    const cajaInterfaces = document.getElementById('interfaces-detectadas');
    const listaInterfaces = document.getElementById('interfaces-lista');
    const inputObjetivo = document.getElementById('objetivo');

    const elementoComando = document.getElementById('comando-equivalente');

    // Paso actual del asistente. Empezamos en el 1.
    let pasoActual = 1;
    const totalPasos = pasos.length;

    // -------------------------------------------------
    // Funciones auxiliares pequeñas.
    // -------------------------------------------------

    // Devuelve el value de la plantilla seleccionada o null si no hay.
    function plantillaSeleccionada() {
        const radio = formulario.querySelector('input[name="plantilla"]:checked');
        return radio ? radio.value : null;
    }

    // ¿Estamos con la plantilla "Personalizado"? Solo entonces se ve
    // el paso 3 (los demás casos el motor usa el comando fijo de la
    // plantilla y se salta esa pantalla).
    function esPersonalizada() {
        return plantillaSeleccionada() === 'custom';
    }

    // Cambia visualmente al paso indicado y actualiza los botones.
    function mostrarPaso(numero) {
        pasos.forEach((paso) => {
            const numeroPaso = parseInt(paso.dataset.step, 10);
            paso.classList.toggle('active', numeroPaso === numero);
        });

        indicadores.forEach((indicador, indice) => {
            const numeroIndicador = indice + 1;
            indicador.classList.toggle('active', numeroIndicador === numero);
            indicador.classList.toggle('done', numeroIndicador < numero);
        });

        botonAnterior.disabled = (numero === 1);
        botonSiguiente.classList.toggle('hidden', numero === totalPasos);
        botonLanzar.classList.toggle('hidden', numero !== totalPasos);
    }

    // Avanza al siguiente paso. Si vamos del 2 al 3 y la plantilla
    // NO es Personalizada, saltamos el paso 3 entero (lo refleja el
    // motor: ignora todos los flags del paso 3 si la plantilla es
    // predefinida).
    function avanzarPaso() {
        const saltamosConfiguracion =
            pasoActual === 2 && !esPersonalizada();

        if (saltamosConfiguracion) {
            pasoActual = 4;
        } else if (pasoActual < totalPasos) {
            pasoActual = pasoActual + 1;
        }

        mostrarPaso(pasoActual);
        if (pasoActual === 4) {
            actualizarResumen();
        }
    }

    // Retrocede al paso anterior. Si volvemos del 4 al 3 pero la
    // plantilla no es Personalizada, saltamos directo al 2.
    function retrocederPaso() {
        const volvemosDeRevisar =
            pasoActual === 4 && !esPersonalizada();

        if (volvemosDeRevisar) {
            pasoActual = 2;
        } else if (pasoActual > 1) {
            pasoActual = pasoActual - 1;
        }

        mostrarPaso(pasoActual);
    }

    botonSiguiente.addEventListener('click', avanzarPaso);
    botonAnterior.addEventListener('click', retrocederPaso);

    // -------------------------------------------------
    // Tarjetas de plantilla: marcar visualmente la elegida.
    // -------------------------------------------------
    const tarjetasPlantilla = document.querySelectorAll('.template-card');
    tarjetasPlantilla.forEach((tarjeta) => {
        tarjeta.addEventListener('click', () => {
            tarjetasPlantilla.forEach((otra) => otra.classList.remove('selected'));
            tarjeta.classList.add('selected');
            const radio = tarjeta.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
        });
    });

    // En edición, marcar la tarjeta que ya viene seleccionada.
    const radioPlantillaInicial = document.querySelector(
        '.template-card input[type="radio"]:checked'
    );
    if (radioPlantillaInicial) {
        radioPlantillaInicial.closest('.template-card').classList.add('selected');
    }

    // -------------------------------------------------
    // Mostrar/ocultar campo "lista personalizada de puertos".
    // Solo se enseña cuando en el selector de puertos eligen "custom".
    // -------------------------------------------------
    function refrescarPuertosCustom() {
        if (selectorPuertos && cajaPuertosCustom) {
            const debeMostrar = selectorPuertos.value === 'custom';
            cajaPuertosCustom.classList.toggle('hidden', !debeMostrar);
        }
    }
    if (selectorPuertos) {
        selectorPuertos.addEventListener('change', refrescarPuertosCustom);
        refrescarPuertosCustom();
    }

    // -------------------------------------------------
    // Mostrar/ocultar sección de Gobuster según el checkbox.
    // -------------------------------------------------
    function refrescarGobuster() {
        if (cardGobuster) {
            const habilitada = !!(checkGobuster && checkGobuster.checked);
            cardGobuster.classList.toggle('hidden', !habilitada);
        }
    }
    if (checkGobuster) {
        checkGobuster.addEventListener('change', refrescarGobuster);
        refrescarGobuster();
    }

    // -------------------------------------------------
    // Aviso sobre /etc/hosts cuando el modo de gobuster necesita
    // un dominio resoluble (dns y vhost).
    // -------------------------------------------------
    function refrescarAvisoVhost() {
        if (avisoVhost && selectorGobusterModo) {
            const modo = selectorGobusterModo.value;
            const necesitaDominio = modo === 'vhost' || modo === 'dns';
            avisoVhost.classList.toggle('hidden', !necesitaDominio);
        }
    }
    if (selectorGobusterModo) {
        selectorGobusterModo.addEventListener('change', refrescarAvisoVhost);
        refrescarAvisoVhost();
    }

    // -------------------------------------------------
    // Construir el comando nmap equivalente para el paso 4.
    // Si la plantilla es una de las predefinidas devolvemos el comando
    // fijo (espejo del dict PLANTILLAS de Python). Si es Personalizado
    // lo montamos leyendo los campos del formulario.
    // -------------------------------------------------
    function comandoEquivalente(datos) {
        const objetivo = datos.get('objetivo') || '<objetivo>';
        const exclusiones = datos.get('excluir');
        const sufijoExcluir = exclusiones ? ` --exclude ${exclusiones}` : '';
        const plantilla = datos.get('plantilla');

        const comandosFijos = {
            host_discovery:    '-sn -PE -PP -PS22,80,443 -PA80 -T4',
            quick_scan:        '-T4 -F --open -Pn',
            full_port_scan:    '-p 1-65535 -T4 -sS --open',
            service_detection: '-sV -sC -T4 --open',
            vuln_scan:         '-sV --script vuln -T4 --open',
            aggressive:        '-A -T4 --open',
            web_audit:         '-p 80,443,8000,8080,8443,8888,5000 -sV -T4 --open',
        };

        // Para una plantilla predefinida devolvemos su comando tal cual.
        if (comandosFijos[plantilla]) {
            return `nmap ${comandosFijos[plantilla]}${sufijoExcluir} ${objetivo}`;
        }

        // Caso "Personalizado": construimos la lista de argumentos.
        const argumentos = [];

        const tipoEscaneo = datos.get('tipo_escaneo_nmap');
        if (tipoEscaneo) {
            argumentos.push('-' + tipoEscaneo);
        }

        const descubrimiento = datos.get('descubrimiento');
        if (descubrimiento && descubrimiento !== 'auto') {
            argumentos.push('-' + descubrimiento);
        }

        const velocidad = datos.get('velocidad');
        if (velocidad && velocidad !== 'none') {
            argumentos.push('-' + velocidad);
        }

        if (datos.get('detectar_servicios')) {
            argumentos.push('-sV');
        }
        if (datos.get('detectar_os')) {
            argumentos.push('-O');
        }
        // Si el usuario NO ha marcado "resolver DNS" añadimos -n para que
        // nmap no haga reverse DNS de cada IP.
        if (!datos.get('resolver_dns')) {
            argumentos.push('-n');
        }
        if (datos.get('traceroute')) {
            argumentos.push('--traceroute');
        }
        if (datos.get('open_only')) {
            argumentos.push('--open');
        }
        if (datos.get('razon_estado')) {
            argumentos.push('--reason');
        }

        const verbosidad = datos.get('verbosidad');
        if (verbosidad === 'v') {
            argumentos.push('-v');
        } else if (verbosidad === 'vv') {
            argumentos.push('-vv');
        }

        const scriptsNSE = datos.getAll('scripts_nse');
        if (scriptsNSE.length > 0) {
            argumentos.push('--script ' + scriptsNSE.join(','));
        }

        const opcionPuertos = datos.get('puertos');
        if (opcionPuertos === 'top-100') {
            argumentos.push('-F');
        } else if (opcionPuertos === 'top-5000') {
            argumentos.push('--top-ports 5000');
        } else if (opcionPuertos === 'all') {
            argumentos.push('-p 1-65535');
        } else if (opcionPuertos === '1-1024') {
            argumentos.push('-p 1-1024');
        } else if (opcionPuertos === 'custom') {
            const listaPuertos = datos.get('puertos_custom');
            if (listaPuertos) {
                argumentos.push('-p ' + listaPuertos);
            }
        }

        const minRate = parseInt(datos.get('min_rate'), 10);
        if (Number.isFinite(minRate) && minRate > 0) {
            argumentos.push('--min-rate ' + minRate);
        }

        const maxRetries = parseInt(datos.get('max_retries'), 10);
        if (Number.isFinite(maxRetries)) {
            argumentos.push('--max-retries ' + maxRetries);
        }

        if (exclusiones) {
            argumentos.push('--exclude ' + exclusiones);
        }

        // Opciones de evasión de firewall/IDS.
        if (datos.get('fragmentar')) {
            argumentos.push('-f');
        }
        const mtu = parseInt(datos.get('mtu'), 10);
        if (Number.isFinite(mtu) && mtu > 0) {
            argumentos.push('--mtu ' + mtu);
        }
        const decoy = datos.get('decoy');
        if (decoy) {
            argumentos.push('-D ' + decoy);
        }
        const spoofIp = datos.get('spoof_ip');
        if (spoofIp) {
            argumentos.push('-S ' + spoofIp);
        }
        const puertoOrigen = parseInt(datos.get('source_port'), 10);
        if (Number.isFinite(puertoOrigen) && puertoOrigen > 0) {
            argumentos.push('-g ' + puertoOrigen);
        }
        const spoofMac = datos.get('spoof_mac');
        if (spoofMac) {
            argumentos.push('--spoof-mac ' + spoofMac);
        }
        const longitudDatos = parseInt(datos.get('data_length'), 10);
        if (Number.isFinite(longitudDatos) && longitudDatos > 0) {
            argumentos.push('--data-length ' + longitudDatos);
        }
        if (datos.get('badsum')) {
            argumentos.push('--badsum');
        }

        // Opciones de exportación: muestran un nombre genérico
        // porque la ruta real la decide el motor en tiempo de ejecución.
        if (datos.get('exportar_xml')) {
            argumentos.push('-oX <id>.xml');
        }
        if (datos.get('exportar_nmap')) {
            argumentos.push('-oN <id>.nmap');
        }
        if (datos.get('exportar_grep')) {
            argumentos.push('-oG <id>.gnmap');
        }

        return `nmap ${argumentos.join(' ')} ${objetivo}`;
    }

    // -------------------------------------------------
    // Rellena la tabla resumen del paso 4 y muestra el comando.
    // -------------------------------------------------
    function actualizarResumen() {
        const datos = new FormData(formulario);

        function escribir(clave, valor) {
            const elemento = document.querySelector(`[data-resumen="${clave}"]`);
            if (elemento) {
                const vacio = valor === null || valor === '' || valor === undefined;
                elemento.textContent = vacio ? '—' : valor;
            }
        }

        escribir('nombre', datos.get('nombre'));
        escribir('objetivo', datos.get('objetivo'));
        escribir('excluir', datos.get('excluir'));
        escribir('plantilla', datos.get('plantilla'));

        if (esPersonalizada()) {
            const velocidad = datos.get('velocidad');
            escribir('tipo', '-' + (datos.get('tipo_escaneo_nmap') || 'sS'));
            escribir('descubrimiento', datos.get('descubrimiento') || 'auto');
            escribir('velocidad', velocidad === 'none' ? 'sin -T' : velocidad);
            escribir('intensidad', datos.get('intensidad'));

            const puertos = datos.get('puertos') === 'custom'
                ? datos.get('puertos_custom')
                : datos.get('puertos');
            escribir('puertos', puertos);

            escribir('scripts', datos.getAll('scripts_nse').join(', ') || 'ninguno');
            escribir('os', datos.get('detectar_os') ? 'Sí' : 'No');
            escribir('servicios', datos.get('detectar_servicios') ? 'Sí' : 'No');
            escribir('traceroute', datos.get('traceroute') ? 'Sí' : 'No');
        } else {
            // Plantilla predefinida: el paso 3 se ignora.
            const camposIgnorados = [
                'tipo', 'descubrimiento', 'velocidad', 'intensidad',
                'puertos', 'scripts', 'os', 'servicios', 'traceroute',
            ];
            camposIgnorados.forEach((clave) => escribir(clave, '(según plantilla)'));
        }

        if (elementoComando) {
            elementoComando.textContent = comandoEquivalente(datos);
        }
    }

    // -------------------------------------------------
    // Botón "Detectar mi red": pide al motor las interfaces de red
    // visibles y las pinta como botones para que el auditor las
    // añada al campo objetivo con un solo clic.
    // -------------------------------------------------
    if (botonDetectarRed && cajaInterfaces && listaInterfaces) {
        botonDetectarRed.addEventListener('click', async () => {
            botonDetectarRed.disabled = true;
            try {
                const url = botonDetectarRed.dataset.url;
                const respuesta = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                const datos = await respuesta.json();
                const interfaces = datos.interfaces || [];

                listaInterfaces.innerHTML = '';

                if (interfaces.length === 0) {
                    listaInterfaces.innerHTML =
                        '<span class="text-muted text-xs">El motor no ve ' +
                        'ninguna red local. Si necesitas escanear la LAN ' +
                        'del host arranca con docker-compose.kali.yml.</span>';
                } else {
                    interfaces.forEach((interfaz) => {
                        const boton = document.createElement('button');
                        boton.type = 'button';
                        boton.className = 'btn btn-secondary btn-sm';
                        boton.textContent = `${interfaz.nombre} · ${interfaz.cidr}`;
                        boton.title = `IP: ${interfaz.ip} (${interfaz.num_hosts} hosts)`;

                        boton.addEventListener('click', () => {
                            const valorActual = inputObjetivo.value.trim();
                            inputObjetivo.value = valorActual === ''
                                ? interfaz.cidr
                                : `${valorActual}, ${interfaz.cidr}`;
                            inputObjetivo.focus();
                        });

                        listaInterfaces.appendChild(boton);
                    });
                }

                cajaInterfaces.classList.remove('hidden');
            } catch (e) {
                alert('No se pudo contactar con el motor para detectar redes.');
            } finally {
                botonDetectarRed.disabled = false;
            }
        });
    }

    // Pintar el paso 1 al cargar la página.
    mostrarPaso(1);
}
