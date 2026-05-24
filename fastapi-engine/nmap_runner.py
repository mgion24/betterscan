"""Construye y ejecuta el comando nmap según la plantilla del wizard.

Cada plantilla tiene un comando fijo; "custom" se monta a partir de los
campos del formulario. Todo lo que llega a la CLI pasa por allow-lists
para evitar inyección.
"""
import asyncio
import logging
import re
from pathlib import Path

import nmap

LOG = logging.getLogger("nmap_runner")

# Volumen compartido con Laravel para los ficheros -oX/-oN/-oG.
RESULTS_DIR = Path("/results")
RESULTS_DIR.mkdir(parents=True, exist_ok=True)

# Allow-lists de caracteres y valores permitidos.
_TARGET_OK = set("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.-_/, :")
_PORTS_OK = set("0123456789,-TU:")
_MAC_OK = set("0123456789abcdefABCDEF:0")
_TIPOS_OK = {"sS", "sT", "sU", "sN", "sF", "sX", "sA"}
_DESCUB_OK = {"Pn", "sn", "PE", "PS", "PA"}
_TIMING_OK = {"T0", "T1", "T2", "T3", "T4", "T5"}
_SCRIPTS_OK = {"default", "safe", "vuln", "discovery", "intrusive",
               "auth", "brute", "exploit", "malware", "version"}


def _sanitize(value, allowed, maxlen):
    """Devuelve `value` saneado o None si está vacío. Lanza ValueError si
    excede `maxlen` o contiene un carácter fuera del allow-list."""
    saneado = None

    if value:
        value = value.strip()
        if len(value) > maxlen:
            raise ValueError(f"Valor demasiado largo: {value[:50]}…")
        # Comprobación de caracteres en una sola pasada con generador;
        # no usamos break/continue, recorremos todo.
        fuera = [ch for ch in value if ch not in allowed]
        if fuera:
            raise ValueError(f"Carácter no permitido: '{fuera[0]}'")
        saneado = value

    return saneado


# Plantillas: (ports, args, descripcion)
PLANTILLAS = {
    "host_discovery":    (None,        "-sn -PE -PP -PS22,80,443 -PA80 -T4", "Descubrimiento de hosts"),
    "quick_scan":        (None,        "-T4 -F --open -Pn",                  "Escaneo rápido"),
    "full_port_scan":    ("1-65535",   "-T4 -sS --open",                     "Escaneo completo de puertos"),
    "service_detection": (None,        "-sV -sC -T4 --open",                 "Detección de servicios"),
    "vuln_scan":         (None,        "-sV --script vuln -T4 --open",       "Análisis de vulnerabilidades"),
    "aggressive":        (None,        "-A -T4 --open",                      "Agresivo (-A)"),
    "web_audit":         ("80,443,8000,8080,8443,8888,5000", "-sV -T4 --open", "Auditoría web"),
}


def construir_argumentos_custom(parametros):
    """Monta los flags de nmap leyendo los campos del wizard."""
    args = []
    ports = None

    # Timing
    if parametros.get("velocidad") in _TIMING_OK:
        args.append("-" + parametros["velocidad"])

    # Tipo de escaneo
    if parametros.get("tipo_escaneo_nmap") in _TIPOS_OK:
        args.append("-" + parametros["tipo_escaneo_nmap"])

    # Descubrimiento
    if parametros.get("descubrimiento") in _DESCUB_OK:
        args.append("-" + parametros["descubrimiento"])

    # Puertos
    puertos = parametros.get("puertos")
    if puertos == "top-100":
        args.append("-F")
    elif puertos == "top-5000":
        args += ["--top-ports", "5000"]
    elif puertos == "all":
        ports = "1-65535"
    elif puertos == "1-1024":
        ports = "1-1024"
    elif puertos == "custom":
        ports = _sanitize(parametros.get("puertos_custom"), _PORTS_OK, 500)

    # Detección
    if parametros.get("detectar_servicios"):
        args.append("-sV")
    if parametros.get("detectar_os"):
        args.append("-O")
    if not parametros.get("resolver_dns", True):
        args.append("-n")
    if parametros.get("traceroute"):
        args.append("--traceroute")

    # Flags extra
    if parametros.get("open_only"):
        args.append("--open")
    if parametros.get("razon_estado"):
        args.append("--reason")
    if parametros.get("verbosidad") == "v":
        args.append("-v")
    elif parametros.get("verbosidad") == "vv":
        args.append("-vv")

    # Scripts NSE
    scripts = [s for s in (parametros.get("scripts_nse") or []) if s in _SCRIPTS_OK]
    if scripts:
        args += ["--script", ",".join(scripts)]

    # Rendimiento
    if isinstance(parametros.get("min_rate"), int) and parametros["min_rate"] > 0:
        args += ["--min-rate", str(parametros["min_rate"])]
    if isinstance(parametros.get("max_retries"), int):
        args += ["--max-retries", str(parametros["max_retries"])]

    # Excluir
    excluir = parametros.get("excluir") or parametros.get("exclusiones")
    if excluir:
        excluir = _sanitize(excluir, _TARGET_OK, 500)
        if excluir:
            args += ["--exclude", excluir]

    # Evasión
    if parametros.get("fragmentar"):
        args.append("-f")
    if isinstance(parametros.get("mtu"), int) and parametros["mtu"] > 0:
        args += ["--mtu", str(parametros["mtu"])]
    if parametros.get("decoy"):
        decoy = _sanitize(parametros["decoy"], _TARGET_OK, 200)
        if decoy:
            args += ["-D", decoy]
    if parametros.get("spoof_ip"):
        spoof = _sanitize(parametros["spoof_ip"], _TARGET_OK, 64)
        if spoof:
            args += ["-S", spoof]
    sp = parametros.get("source_port")
    if isinstance(sp, int) and 1 <= sp <= 65535:
        args += ["-g", str(sp)]
    if parametros.get("spoof_mac"):
        mac = _sanitize(parametros["spoof_mac"], _MAC_OK, 17)
        if mac:
            args += ["--spoof-mac", mac]
    dl = parametros.get("data_length")
    if isinstance(dl, int) and 0 < dl <= 1400:
        args += ["--data-length", str(dl)]
    if parametros.get("badsum"):
        args.append("--badsum")

    return ports, " ".join(args)


def construir_comando(parametros):
    """(ports, args, descripcion) según la plantilla elegida."""
    plantilla = parametros.get("plantilla", "quick_scan")
    if plantilla in PLANTILLAS and plantilla != "custom":
        resultado = PLANTILLAS[plantilla]
    else:
        ports, args = construir_argumentos_custom(parametros)
        resultado = (ports, args, "Personalizado")
    return resultado


def aplicar_exportaciones(args, escaneo_id, parametros):
    """Si la plantilla custom pide -oX/-oN/-oG, añade los flags y
    devuelve el dict de archivos generados."""
    exportados = {}

    if parametros.get("plantilla") == "custom":
        extras = []
        for clave, flag, ext in [
            ("exportar_xml",  "-oX", "xml"),
            ("exportar_nmap", "-oN", "nmap"),
            ("exportar_grep", "-oG", "gnmap"),
        ]:
            if parametros.get(clave):
                ruta = RESULTS_DIR / f"escaneo_{escaneo_id}.{ext}"
                extras += [flag, str(ruta)]
                exportados[ext] = str(ruta)
        if extras:
            args = (args + " " + " ".join(extras)).strip()

    return args, exportados


def _primer_hostname(hostnames):
    """Devuelve el primer hostname con `name` no vacío de la lista."""
    encontrado = None
    for hn in hostnames or []:
        if hn.get("name") and encontrado is None:
            encontrado = hn["name"]
    return encontrado


def _convertir_resultado(scan):
    """Convierte el dict crudo de python-nmap al formato de Laravel."""
    activos = []

    for ip, host in (scan.get("scan") or {}).items():
        host_arriba = host.get("status", {}).get("state") != "down"

        if host_arriba:
            mac = host.get("addresses", {}).get("mac")
            hostname = _primer_hostname(host.get("hostnames"))

            sistema_operativo = None
            osmatch = host.get("osmatch") or []
            if osmatch:
                sistema_operativo = osmatch[0].get("name")

            puertos = []
            for proto in ("tcp", "udp", "sctp"):
                pp = host.get(proto) or {}
                for numero, info in pp.items():
                    if info.get("state") in {"open", "open|filtered"}:
                        partes = [
                            info.get(k)
                            for k in ("product", "version", "extrainfo")
                            if info.get(k)
                        ]
                        version = " ".join(partes) if partes else None
                        scripts = {
                            sc[0]: sc[1]
                            for sc in info.get("script", {}).items()
                        }
                        puertos.append({
                            "numero": int(numero),
                            "protocolo": proto,
                            "estado": info.get("state"),
                            "servicio": info.get("name") or None,
                            "version": version,
                            "scripts": scripts,
                            "vulnerabilidades": [],
                        })

            activos.append({
                "ip": ip,
                "mac": mac,
                "hostname": hostname,
                "sistema_operativo": sistema_operativo,
                "puertos": puertos,
            })

    return activos


async def ejecutar_nmap_completo(escaneo_id, objetivo, parametros, timeout=1800):
    """Lanza nmap, parsea el resultado y devuelve (activos, comando, exportados)."""
    objetivo_seguro = _sanitize(objetivo, _TARGET_OK, 500)
    if not objetivo_seguro:
        raise ValueError("Objetivo vacío o inválido.")

    ports, args, descripcion = construir_comando(parametros)
    args, exportados = aplicar_exportaciones(args, escaneo_id, parametros)

    LOG.info("nmap.scan id=%s hosts=%r ports=%r args=%r (%s)",
             escaneo_id, objetivo_seguro, ports, args, descripcion)

    def _scan_sync():
        return nmap.PortScanner().scan(
            hosts=objetivo_seguro, ports=ports, arguments=args, timeout=timeout,
        )

    try:
        scan = await asyncio.wait_for(
            asyncio.to_thread(_scan_sync), timeout=timeout + 30,
        )
    except asyncio.TimeoutError as e:
        raise RuntimeError(f"nmap excedió el timeout de {timeout}s") from e
    except nmap.PortScannerError as e:
        raise RuntimeError(f"Error de nmap: {e}") from e

    activos = _convertir_resultado(scan)
    comando = _formato_comando(ports, args, objetivo_seguro)
    return activos, comando, exportados


def _formato_comando(ports, args, objetivo):
    """Imita la línea como la mostraría un sysadmin en la terminal."""
    partes = ["nmap"]
    if ports:
        partes += ["-p", ports]
    if args:
        partes.append(args)
    partes.append(objetivo)
    return " ".join(partes)


CVE_REGEX = re.compile(r"CVE-\d{4}-\d{4,7}", re.IGNORECASE)


def extraer_cves(scripts):
    """Saca los CVE-XXXX-YYYY del output de los scripts NSE."""
    encontrados = set()
    for output in scripts.values():
        for m in CVE_REGEX.finditer(output or ""):
            encontrados.add(m.group(0).upper())
    return sorted(encontrados)
