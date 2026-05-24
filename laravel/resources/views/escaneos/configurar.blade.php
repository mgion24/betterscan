@extends('layouts.app')
@section('titulo', $escaneo->exists ? 'Editar escaneo' : 'Configurar escaneo')

@section('contenido')
@php
    $editando = $escaneo->exists;
    $accion = $editando
        ? url('/escaneos/'.$escaneo->id)
        : url('/proyectos/'.$proyecto->id.'/escaneos');

    // En edición precargamos los valores guardados en parametros_nmap.
    $params = $editando ? ($escaneo->parametros_nmap ?? []) : [];
    $val = fn($clave, $default = null) => old($clave, $params[$clave] ?? $default);
    $checked = fn($clave, $default = false) => (bool) old($clave, $params[$clave] ?? $default);
@endphp

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/proyectos/'.$proyecto->id) }}">{{ $proyecto->nombre }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $editando ? 'Editar escaneo' : 'Nuevo escaneo' }}</li>
    </ol>
</nav>

<h1 class="page-title">{{ $editando ? 'Editar escaneo' : 'Configurar nuevo escaneo' }}</h1>
<p class="page-subtitle">Proyecto: {{ $proyecto->nombre }}</p>

@if($errors->any())
    <div class="alert alert-error">
        <ul class="error-list">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

<p class="form-required-hint">Los campos marcados con <span class="required-marker">*</span> son obligatorios.</p>

<form method="POST" action="{{ $accion }}" id="wizard-form" novalidate>
    @csrf
    @if($editando) @method('PUT') @endif

    <nav class="stepper" aria-label="Pasos del asistente">
        <div class="stepper-item active" data-step-indicator="1"><span class="step-num">1</span> Información</div>
        <div class="stepper-item" data-step-indicator="2"><span class="step-num">2</span> Plantilla</div>
        <div class="stepper-item" data-step-indicator="3"><span class="step-num">3</span> Configuración</div>
        <div class="stepper-item" data-step-indicator="4"><span class="step-num">4</span> Revisar</div>
    </nav>

    {{-- Paso 1: información básica --}}
    <section class="wizard-step active" data-step="1">
        <article class="card">
            <header><h2>Información del escaneo</h2></header>

            <div class="form-group">
                <label class="form-label" for="nombre">Nombre <span class="required-marker">*</span></label>
                <input class="form-input" id="nombre" name="nombre"
                       value="{{ $val('nombre', '') }}"
                       placeholder="Escaneo semanal de red interna">
                <p class="form-error" data-error-for="nombre"></p>
            </div>

            <div class="form-group">
                <label class="form-label" for="descripcion">Descripción</label>
                <textarea class="form-textarea" id="descripcion" name="descripcion">{{ $val('descripcion', '') }}</textarea>
                <p class="form-error" data-error-for="descripcion"></p>
            </div>

            <div class="form-group">
                <label class="form-label" for="objetivo">Objetivo (targets) <span class="required-marker">*</span></label>
                <div class="input-group">
                    <input class="form-input mono" id="objetivo" name="objetivo"
                           value="{{ $val('objetivo', $proyecto->alcance_red) }}"
                           placeholder="192.168.1.0/24, scanme.nmap.org">
                    <button type="button" class="btn btn-secondary" id="btn-detectar-red"
                            data-url="{{ url('/escaneos/network/interfaces') }}"
                            title="Detecta las redes locales visibles por el motor (modo Kali)">
                        <i class="bi bi-broadcast" aria-hidden="true"></i>
                        <span class="d-md-inline">Detectar mi red</span>
                    </button>
                </div>
                <p class="form-help">Una o varias IPs, rangos CIDR o hostnames separados por comas. <strong>No incluyas protocolo</strong> (sin http:// o https://). El botón solo devuelve redes útiles si el motor corre en <code>network_mode: host</code>.</p>
                <p class="form-error" data-error-for="objetivo"></p>

                <div id="interfaces-detectadas" class="interfaces-box hidden">
                    <p class="text-muted text-xs mb-1">Redes detectadas (pulsa para añadir al objetivo):</p>
                    <div id="interfaces-lista" class="flex gap-1 flex-wrap"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="excluir">Excluir</label>
                <input class="form-input mono" id="excluir" name="excluir"
                       value="{{ $val('excluir', $proyecto->excepciones_red) }}"
                       placeholder="192.168.1.1">
                <p class="form-help">Hosts o rangos que se omiten del escaneo (equivale a <code>--exclude</code> en nmap).</p>
                <p class="form-error" data-error-for="excluir"></p>
            </div>
        </article>
    </section>

    {{-- Paso 2: plantilla --}}
    <section class="wizard-step" data-step="2">
        <article class="card">
            <header><h2>Elige una plantilla <span class="required-marker">*</span></h2></header>
            <p class="text-muted text-sm mb-2">Solo «Personalizado» abre el paso de configuración avanzada. Las demás plantillas ejecutan exactamente el comando que indica su subtítulo.</p>

            <div class="template-grid">
                @php
                    $plantillas = [
                        ['key'=>'host_discovery',
                         'nombre'=>'Descubrimiento de hosts',
                         'cmd'=>'nmap -sn -PE -PP -PS22,80,443 -PA80 -T4',
                         'desc'=>'Solo ping sweep multi-método: ICMP echo + timestamp + TCP SYN/ACK contra puertos comunes. Detecta qué hosts están vivos sin escanear puertos.'],
                        ['key'=>'quick_scan',
                         'nombre'=>'Escaneo rápido',
                         'cmd'=>'nmap -T4 -F --open -Pn',
                         'desc'=>'Top 100 puertos TCP, timing rápido, salta el ping (Pn). Sólo muestra puertos abiertos.'],
                        ['key'=>'full_port_scan',
                         'nombre'=>'Escaneo completo',
                         'cmd'=>'nmap -p 1-65535 -T4 -sS --open',
                         'desc'=>'Los 65.535 puertos TCP con SYN scan. Más lento pero exhaustivo.'],
                        ['key'=>'service_detection',
                         'nombre'=>'Detección de servicios',
                         'cmd'=>'nmap -sV -sC -T4 --open',
                         'desc'=>'Versión de cada servicio + scripts NSE default (banners, certificados, info pública).'],
                        ['key'=>'vuln_scan',
                         'nombre'=>'Análisis de vulnerabilidades',
                         'cmd'=>'nmap -sV --script vuln -T4 --open',
                         'desc'=>'Detección de versión + categoría completa de scripts NSE «vuln» (CVEs conocidos).'],
                        ['key'=>'aggressive',
                         'nombre'=>'Escaneo agresivo',
                         'cmd'=>'nmap -A -T4 --open',
                         'desc'=>'OS + versión + scripts default + traceroute en un solo comando (-A).'],
                        ['key'=>'web_audit',
                         'nombre'=>'Auditoría web',
                         'cmd'=>'nmap -p 80,443,8000,8080,8443,8888,5000 -sV -T4 --open + gobuster',
                         'desc'=>'Nmap sobre puertos HTTP/HTTPS típicos + gobuster (dir por defecto) contra cada uno.'],
                        ['key'=>'custom',
                         'nombre'=>'Personalizado',
                         'cmd'=>'configurable en el paso 3',
                         'desc'=>'Desbloquea TODAS las opciones de nmap (tipo, evasión, NSE, exportar, gobuster). Para auditores.'],
                    ];
                @endphp

                @foreach($plantillas as $p)
                    <label class="template-card" data-plantilla="{{ $p['key'] }}">
                        <input type="radio" name="plantilla" value="{{ $p['key'] }}"
                               @checked($val('plantilla') === $p['key'])>
                        <h4>{{ $p['nombre'] }}</h4>
                        <p class="text-muted text-xs mb-1"><code>{{ $p['cmd'] }}</code></p>
                        <p>{{ $p['desc'] }}</p>
                    </label>
                @endforeach
            </div>
            <p class="form-error" data-error-for="plantilla"></p>
        </article>
    </section>

    {{-- Paso 3: configuración avanzada (solo si plantilla = custom) --}}
    <section class="wizard-step" data-step="3">

        <article class="card mb-2">
            <header><h2>Velocidad e intensidad</h2></header>

            <div class="form-group">
                <label class="form-label">Velocidad / Timing</label>
                <div class="flex gap-1 flex-wrap">
                    @foreach([
                        'none'=>'Sin -T (default nmap)',
                        'T0'=>'T0 · Paranoico','T1'=>'T1 · Sigiloso','T2'=>'T2 · Educado',
                        'T3'=>'T3 · Normal','T4'=>'T4 · Agresivo','T5'=>'T5 · Locura',
                    ] as $valItem => $label)
                        <label class="form-checkbox">
                            <input type="radio" name="velocidad" value="{{ $valItem }}"
                                   @checked($val('velocidad', 'T3') === $valItem)>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="form-help">Si eliges «Sin -T» nmap usa su valor por defecto (T3) sin añadir el flag.</p>
                <p class="form-error" data-error-for="velocidad"></p>
            </div>

            <div class="form-group">
                <label class="form-label">Intensidad</label>
                <div class="flex gap-1 flex-wrap">
                    @foreach(['sigiloso'=>'Sigiloso (fragmentado, sin ping)','normal'=>'Normal','agresivo'=>'Agresivo (scripts intrusivos)'] as $valItem => $label)
                        <label class="form-checkbox">
                            <input type="radio" name="intensidad" value="{{ $valItem }}"
                                   @checked($val('intensidad', 'normal') === $valItem)>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="form-error" data-error-for="intensidad"></p>
            </div>
        </article>

        <article class="card mb-2">
            <header><h2>Tipo de escaneo y descubrimiento</h2></header>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="tipo_escaneo_nmap">Tipo de escaneo</label>
                    <select class="form-select" id="tipo_escaneo_nmap" name="tipo_escaneo_nmap">
                        <option value="">— Sin especificar —</option>
                        @foreach([
                            'sS'=>'TCP SYN stealth (-sS) [recomendado]',
                            'sT'=>'TCP connect (-sT, sin root)',
                            'sU'=>'UDP (-sU)',
                            'sN'=>'TCP Null (-sN)',
                            'sF'=>'TCP FIN (-sF)',
                            'sX'=>'TCP Xmas (-sX)',
                            'sA'=>'TCP ACK (-sA, detección de firewall)',
                        ] as $valItem=>$lbl)
                            <option value="{{ $valItem }}" @selected($val('tipo_escaneo_nmap', 'sS')===$valItem)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <p class="form-error" data-error-for="tipo_escaneo_nmap"></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="descubrimiento">Descubrimiento de hosts</label>
                    <select class="form-select" id="descubrimiento" name="descubrimiento">
                        <option value="auto" @selected($val('descubrimiento','auto')==='auto')>Automático (por defecto)</option>
                        <option value="Pn"   @selected($val('descubrimiento')==='Pn')>Saltar ping (-Pn)</option>
                        <option value="sn"   @selected($val('descubrimiento')==='sn')>Solo ping (-sn)</option>
                        <option value="PE"   @selected($val('descubrimiento')==='PE')>ICMP Echo (-PE)</option>
                        <option value="PS"   @selected($val('descubrimiento')==='PS')>TCP SYN ping (-PS)</option>
                        <option value="PA"   @selected($val('descubrimiento')==='PA')>TCP ACK ping (-PA)</option>
                    </select>
                    <p class="form-error" data-error-for="descubrimiento"></p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="detectar_servicios" value="1" @checked($checked('detectar_servicios', true))>
                        Detectar versiones de servicios (<code>-sV</code>)
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="detectar_os" value="1" @checked($checked('detectar_os'))>
                        Detectar sistema operativo (<code>-O</code>)
                    </label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="resolver_dns" value="1" @checked($checked('resolver_dns', true))>
                        Resolver DNS inverso (desmarcado = <code>-n</code>)
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="traceroute" value="1" @checked($checked('traceroute'))>
                        Traceroute (<code>--traceroute</code>)
                    </label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="open_only" value="1" @checked($checked('open_only', true))>
                        Sólo puertos abiertos (<code>--open</code>)
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="razon_estado" value="1" @checked($checked('razon_estado'))>
                        Mostrar razón del estado (<code>--reason</code>)
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label" for="verbosidad">Verbosidad</label>
                    <select class="form-select" id="verbosidad" name="verbosidad">
                        <option value="">Por defecto</option>
                        <option value="v"  @selected($val('verbosidad')==='v')>-v (verbose)</option>
                        <option value="vv" @selected($val('verbosidad')==='vv')>-vv (más verbose)</option>
                    </select>
                </div>
            </div>
        </article>

        <article class="card mb-2">
            <header><h2>Puertos y scripts NSE</h2></header>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="puertos">Puertos</label>
                    <select class="form-select" id="puertos" name="puertos">
                        <option value="top-100"     @selected($val('puertos','top-1000')==='top-100')>Top 100</option>
                        <option value="top-1000"    @selected($val('puertos','top-1000')==='top-1000')>Top 1000 (default)</option>
                        <option value="top-5000"    @selected($val('puertos')==='top-5000')>Top 5000</option>
                        <option value="all"         @selected($val('puertos')==='all')>Todos (1-65535)</option>
                        <option value="1-1024"      @selected($val('puertos')==='1-1024')>Privilegiados (1-1024)</option>
                        <option value="custom"      @selected($val('puertos')==='custom')>Personalizado…</option>
                    </select>
                    <p class="form-error" data-error-for="puertos"></p>
                </div>
                <div class="form-group" id="wrapper-puertos-custom">
                    <label class="form-label" for="puertos_custom">Lista personalizada</label>
                    <input class="form-input mono" id="puertos_custom" name="puertos_custom"
                           value="{{ $val('puertos_custom', '') }}"
                           placeholder="22,80,443,8000-9000 o T:80,U:53">
                    <p class="form-help">Solo si arriba eliges «Personalizado». Acepta rangos, listas y prefijos <code>T:</code>/<code>U:</code>.</p>
                    <p class="form-error" data-error-for="puertos_custom"></p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="min_rate">Min-rate (paquetes/s)</label>
                    <input class="form-input" id="min_rate" name="min_rate"
                           value="{{ $val('min_rate', 0) }}">
                    <p class="form-help">0 = sin mínimo. Subirlo va más rápido pero menos sigiloso.</p>
                    <p class="form-error" data-error-for="min_rate"></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="max_retries">Máximo de reintentos</label>
                    <input class="form-input" id="max_retries" name="max_retries"
                           value="{{ $val('max_retries', 2) }}">
                    <p class="form-error" data-error-for="max_retries"></p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Scripts NSE</label>
                <div class="flex gap-1 flex-wrap">
                    @foreach(['default','safe','vuln','discovery','intrusive','auth','brute','exploit','malware','version'] as $script)
                        <label class="form-checkbox">
                            <input type="checkbox" name="scripts_nse[]" value="{{ $script }}"
                                   @checked(in_array($script, old('scripts_nse', $params['scripts_nse'] ?? ['default'])))>
                            <code>{{ $script }}</code>
                        </label>
                    @endforeach
                </div>
                <p class="form-help">Categorías del Nmap Scripting Engine. <code>intrusive</code>, <code>brute</code> y <code>exploit</code> son agresivos: úsalos con permiso.</p>
                <p class="form-error" data-error-for="scripts_nse"></p>
            </div>
        </article>

        <article class="card mb-2">
            <header><h2>Evasión de firewall / IDS <small class="text-muted">(avanzado)</small></h2></header>
            <p class="form-help">Estos flags están pensados para auditorías contra defensas perimetrales. <strong>Úsalos sólo con autorización escrita.</strong></p>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="fragmentar" value="1" @checked($checked('fragmentar'))>
                        Fragmentar paquetes (<code>-f</code>)
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label" for="mtu">MTU custom (múltiplo de 8)</label>
                    <input class="form-input" id="mtu" name="mtu" value="{{ $val('mtu','') }}" placeholder="16, 24, 32...">
                    <p class="form-error" data-error-for="mtu"></p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="decoy">Señuelos (<code>-D</code>)</label>
                    <input class="form-input mono" id="decoy" name="decoy"
                           value="{{ $val('decoy','') }}" placeholder="RND:5 o 10.0.0.1,10.0.0.2,ME">
                    <p class="form-error" data-error-for="decoy"></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="spoof_ip">Spoof IP origen (<code>-S</code>)</label>
                    <input class="form-input mono" id="spoof_ip" name="spoof_ip"
                           value="{{ $val('spoof_ip','') }}" placeholder="10.0.0.99">
                    <p class="form-error" data-error-for="spoof_ip"></p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="source_port">Puerto origen (<code>-g</code>)</label>
                    <input class="form-input" id="source_port" name="source_port"
                           value="{{ $val('source_port','') }}" placeholder="53, 80, 443...">
                    <p class="form-error" data-error-for="source_port"></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="spoof_mac">Spoof MAC (<code>--spoof-mac</code>)</label>
                    <input class="form-input mono" id="spoof_mac" name="spoof_mac"
                           value="{{ $val('spoof_mac','') }}" placeholder="0 (random), Apple, AA:BB:CC:DD:EE:FF">
                    <p class="form-error" data-error-for="spoof_mac"></p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="data_length">Data length (<code>--data-length</code>)</label>
                    <input class="form-input" id="data_length" name="data_length"
                           value="{{ $val('data_length','') }}" placeholder="200">
                    <p class="form-error" data-error-for="data_length"></p>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="badsum" value="1" @checked($checked('badsum'))>
                        Checksum inválido (<code>--badsum</code>)
                    </label>
                </div>
            </div>
        </article>

        <article class="card mb-2">
            <header><h2>Exportar resultados nmap</h2></header>
            <p class="form-help">Si marcas alguna opción el motor escribe el archivo y la vista de resultados te ofrecerá descargarlo.</p>
            <div class="flex gap-2 flex-wrap">
                <label class="form-checkbox">
                    <input type="checkbox" name="exportar_xml" value="1" @checked($checked('exportar_xml'))>
                    XML (<code>-oX</code>)
                </label>
                <label class="form-checkbox">
                    <input type="checkbox" name="exportar_nmap" value="1" @checked($checked('exportar_nmap'))>
                    Normal (<code>-oN</code>)
                </label>
                <label class="form-checkbox">
                    <input type="checkbox" name="exportar_grep" value="1" @checked($checked('exportar_grep'))>
                    Grepable (<code>-oG</code>)
                </label>
            </div>
        </article>

        <article class="card">
            <header>
                <h2>Gobuster (enumeración web)</h2>
                <label class="form-checkbox mt-1">
                    <input type="checkbox" id="gobuster_habilitar" name="gobuster_habilitar"
                           value="1" @checked($checked('gobuster_habilitar'))>
                    Habilitar gobuster después de nmap
                </label>
            </header>

            <div id="card-gobuster" class="hidden">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="gobuster_modo">Modo</label>
                        <select class="form-select" id="gobuster_modo" name="gobuster_modo">
                            <option value="dir"   @selected($val('gobuster_modo','dir')==='dir')>dir — directorios y ficheros</option>
                            <option value="dns"   @selected($val('gobuster_modo')==='dns')>dns — subdominios</option>
                            <option value="vhost" @selected($val('gobuster_modo')==='vhost')>vhost — virtual hosts</option>
                        </select>
                        <p class="form-error" data-error-for="gobuster_modo"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="gobuster_wordlist">Wordlist</label>
                        <select class="form-select" id="gobuster_wordlist" name="gobuster_wordlist">
                            <option value="common"  @selected($val('gobuster_wordlist','common')==='common')>common.txt (4.6k)</option>
                            <option value="medium"  @selected($val('gobuster_wordlist')==='medium')>directory-list-2.3-medium (220k)</option>
                            <option value="big"     @selected($val('gobuster_wordlist')==='big')>big.txt (20k)</option>
                            <option value="raft"    @selected($val('gobuster_wordlist')==='raft')>raft-medium-directories</option>
                        </select>
                        <p class="form-error" data-error-for="gobuster_wordlist"></p>
                    </div>
                </div>

                <div id="aviso-vhost" class="alert alert-warning hidden">
                    <strong>vhost / dns:</strong> el dominio que indiques como objetivo tiene que resolver. Si no usas DNS público,
                    añádelo a <code>/etc/hosts</code> del contenedor o del host (en modo Kali). Si no resuelve, el motor lanzará gobuster
                    pero no recibirá respuestas válidas.
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="gobuster_threads">Hilos</label>
                        <input class="form-input" id="gobuster_threads" name="gobuster_threads"
                               value="{{ $val('gobuster_threads', 10) }}">
                        <p class="form-error" data-error-for="gobuster_threads"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="gobuster_status_codes">Códigos a aceptar</label>
                        <input class="form-input" id="gobuster_status_codes" name="gobuster_status_codes"
                               value="{{ $val('gobuster_status_codes', '200,204,301,302,307,401,403') }}"
                               placeholder="200,301,302">
                        <p class="form-error" data-error-for="gobuster_status_codes"></p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="gobuster_extensiones">Extensiones (sin punto, comas)</label>
                        <input class="form-input" id="gobuster_extensiones" name="gobuster_extensiones"
                               value="{{ $val('gobuster_extensiones', 'php,html,txt') }}">
                        <p class="form-error" data-error-for="gobuster_extensiones"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="gobuster_follow_redirect" value="1" @checked($checked('gobuster_follow_redirect'))>
                            Seguir redirecciones (<code>-r</code>)
                        </label>
                        <label class="form-checkbox mt-1">
                            <input type="checkbox" name="gobuster_no_tls" value="1" @checked($checked('gobuster_no_tls'))>
                            Saltar verificación TLS (<code>-k</code>)
                        </label>
                        <label class="form-checkbox mt-1">
                            <input type="checkbox" name="gobuster_exportar" value="1" @checked($checked('gobuster_exportar'))>
                            Exportar salida cruda a archivo
                        </label>
                    </div>
                </div>
            </div>
        </article>
    </section>

    {{-- Paso 4: revisar --}}
    <section class="wizard-step" data-step="4">
        <article class="card mb-2">
            <header><h2>Revisar configuración</h2></header>

            <dl id="resumen" class="dl-grid">
                <dt class="text-muted">Nombre</dt><dd data-resumen="nombre">—</dd>
                <dt class="text-muted">Objetivo</dt><dd class="mono" data-resumen="objetivo">—</dd>
                <dt class="text-muted">Excluir</dt><dd class="mono" data-resumen="excluir">—</dd>
                <dt class="text-muted">Plantilla</dt><dd data-resumen="plantilla">—</dd>
                <dt class="text-muted">Tipo escaneo</dt><dd data-resumen="tipo">—</dd>
                <dt class="text-muted">Descubrimiento</dt><dd data-resumen="descubrimiento">—</dd>
                <dt class="text-muted">Velocidad</dt><dd data-resumen="velocidad">—</dd>
                <dt class="text-muted">Intensidad</dt><dd data-resumen="intensidad">—</dd>
                <dt class="text-muted">Puertos</dt><dd data-resumen="puertos">—</dd>
                <dt class="text-muted">Scripts NSE</dt><dd data-resumen="scripts">—</dd>
                <dt class="text-muted">Detección de SO</dt><dd data-resumen="os">—</dd>
                <dt class="text-muted">Detección de servicios</dt><dd data-resumen="servicios">—</dd>
                <dt class="text-muted">Traceroute</dt><dd data-resumen="traceroute">—</dd>
            </dl>
        </article>

        <article class="card">
            <header><h2>Equivalente como comando</h2></header>
            <p class="text-muted text-xs mb-2">Esta es la línea que el motor ejecutará realmente contra el objetivo:</p>
            <pre class="code-block" id="comando-equivalente">—</pre>
        </article>
    </section>

    <div class="wizard-actions">
        <button type="button" class="btn btn-primary" id="btn-anterior" disabled>← Anterior</button>
        <div class="flex gap-1">
            <a href="{{ url('/proyectos/'.$proyecto->id) }}" class="btn btn-secondary">Cancelar</a>
            <button type="button" class="btn btn-primary" id="btn-siguiente">Siguiente →</button>
            <button type="submit" class="btn btn-primary hidden" id="btn-lanzar">
                {{ $editando ? 'Guardar cambios' : 'Lanzar escaneo' }}
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script src="{{ asset('assets/js/asistente-escaneo.js') }}"></script>
@endpush

@endsection
