"""Scanner gobuster: enumeración web (dir, dns, vhost)."""
import asyncio
import logging
import re
import shlex
import shutil
import socket
from pathlib import Path

from .base import ScannerBase

LOG = logging.getLogger("scanners.gobuster")

RESULTS_DIR = Path("/results")
RESULTS_DIR.mkdir(parents=True, exist_ok=True)

WORDLISTS = {
    "common": "/usr/share/wordlists/dirb/common.txt",
    "medium": "/usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt",
    "big":    "/usr/share/wordlists/dirb/big.txt",
    "raft":   "/usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt",
}

PUERTOS_HTTP = {80, 8000, 8080, 8888, 5000}
PUERTOS_HTTPS = {443, 8443}

_OK = set("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.,/-_:")
_LINEA_DIR = re.compile(r"^(?P<path>/\S+)\s+\(Status:\s*(?P<status>\d+)\)")
_LINEA_DNS = re.compile(r"^Found:\s*(?P<host>\S+)")
_LINEA_VHOST = re.compile(r"^Found:\s*(?P<host>\S+)\s+Status:\s*(?P<status>\d+)")


def _safe(value, maxlen=100):
    """Devuelve `value` saneado o None si está vacío, excede maxlen o
    contiene caracteres fuera del allow-list."""
    seguro = None
    if value:
        v = value.strip()
        if len(v) <= maxlen and all(c in _OK for c in v):
            seguro = v
    return seguro


def _wordlist_path(nombre):
    """Ruta a la wordlist seleccionada; si no existe, primera disponible."""
    ruta = WORDLISTS.get(nombre or "common")
    elegida = None

    if ruta and Path(ruta).is_file():
        elegida = ruta
    else:
        # Recorremos todas las wordlists conocidas; la primera que
        # exista en disco es la elegida. Sin break: dejamos que el
        # bucle termine y nos quedamos con la PRIMERA encontrada.
        for w in WORDLISTS.values():
            if Path(w).is_file() and elegida is None:
                elegida = w

    return elegida


def _construir_url(ip, puerto):
    scheme = "https" if puerto in PUERTOS_HTTPS else "http"
    return f"{scheme}://{ip}:{puerto}"


def _dominio_resuelve(dominio):
    """True si `dominio` resuelve a algo vía DNS, False si lanza OSError."""
    resuelve = False
    try:
        socket.gethostbyname(dominio)
        resuelve = True
    except OSError:
        resuelve = False
    return resuelve


async def _ejecutar_gobuster(diana, parametros, escaneo_id):
    """Lanza gobuster contra una URL/dominio y devuelve (hallazgos, stdout)."""
    hallazgos: list[dict] = []
    raw = ""
    gob = shutil.which("gobuster")
    wordlist = _wordlist_path(parametros.get("gobuster_wordlist"))

    if gob is None:
        LOG.warning("gobuster no está instalado")
    elif wordlist is None:
        LOG.warning("no hay wordlist disponible")
    else:
        modo = parametros.get("gobuster_modo") or "dir"
        if modo not in {"dir", "dns", "vhost"}:
            modo = "dir"

        threads = max(1, min(int(parametros.get("gobuster_threads") or 10), 50))
        args = [gob, modo, "-q", "-t", str(threads), "-w", wordlist]

        if modo == "dir":
            args += ["-u", diana]
            codes = _safe(parametros.get("gobuster_status_codes"), 50)
            if codes:
                args += ["-s", codes, "-b", ""]
            ext = _safe(parametros.get("gobuster_extensiones"), 100)
            if ext:
                args += ["-x", ext]
            if parametros.get("gobuster_follow_redirect"):
                args.append("-r")
            if parametros.get("gobuster_no_tls"):
                args.append("-k")
        elif modo == "dns":
            host = diana.split("://", 1)[-1].split(":", 1)[0].strip("/")
            if not _dominio_resuelve(host):
                LOG.warning("gobuster dns: el dominio %s no resuelve "
                            "(revisa /etc/hosts).", host)
            args += ["-d", host]
        elif modo == "vhost":
            args += ["-u", diana]
            host = diana.split("://", 1)[-1].split(":", 1)[0].strip("/")
            if not re.match(r"^\d", host) and not _dominio_resuelve(host):
                LOG.warning("gobuster vhost: el dominio %s no resuelve.", host)
            if parametros.get("gobuster_no_tls"):
                args.append("-k")

        LOG.info("gobuster: %s", " ".join(shlex.quote(a) for a in args))

        proc = await asyncio.create_subprocess_exec(
            *args,
            stdout=asyncio.subprocess.PIPE,
            stderr=asyncio.subprocess.PIPE,
        )

        timeout_alcanzado = False
        try:
            stdout, _ = await asyncio.wait_for(proc.communicate(), timeout=600)
        except asyncio.TimeoutError:
            timeout_alcanzado = True
            proc.kill()
            await proc.wait()
            LOG.warning("gobuster excedió el timeout contra %s", diana)
            stdout = b""

        if not timeout_alcanzado:
            raw = stdout.decode("utf-8", errors="replace")
            for linea in raw.splitlines():
                linea = linea.strip()
                if modo == "dir":
                    m = _LINEA_DIR.match(linea)
                    if m:
                        hallazgos.append({
                            "path": m.group("path"),
                            "status": m.group("status"),
                        })
                elif modo == "dns":
                    m = _LINEA_DNS.match(linea)
                    if m:
                        hallazgos.append({"host": m.group("host")})
                elif modo == "vhost":
                    m = _LINEA_VHOST.match(linea)
                    if m:
                        hallazgos.append({
                            "host": m.group("host"),
                            "status": m.group("status"),
                        })

            LOG.info("gobuster %s [%s] → %d hallazgos",
                     diana, modo, len(hallazgos))

    return hallazgos, raw


class GobusterDir(ScannerBase):
    nombre = "Gobuster"

    async def run(self, objetivo, parametros):
        return [{"_pendiente_gobuster": True}]


async def ejecutar_sobre_activos(escaneo_id, activos, parametros):
    """Lanza gobuster contra cada puerto HTTP/HTTPS de los activos descubiertos
    por nmap. En modo dns/vhost usa el dominio del objetivo original."""
    modo = parametros.get("gobuster_modo") or "dir"
    buffer = []

    if modo in {"dns", "vhost"}:
        dominios = set()
        for trozo in (parametros.get("_objetivo_original") or "").split(","):
            trozo = trozo.strip()
            if trozo and not trozo.replace(".", "").replace("/", "").isdigit():
                dominios.add(trozo.split("/")[0])
        for activo in activos:
            if activo.get("hostname"):
                dominios.add(activo["hostname"])

        for dominio in dominios:
            url = f"http://{dominio}" if modo == "vhost" else dominio
            hallazgos, raw = await _ejecutar_gobuster(url, parametros, escaneo_id)
            if hallazgos:
                _adjuntar(activos, dominio, hallazgos)
            buffer.append(f"### {modo} {dominio}\n{raw}\n")
    else:
        for activo in activos:
            ip = activo.get("ip")
            if ip:
                for puerto in activo.get("puertos", []):
                    num = int(puerto.get("numero") or 0)
                    if num in PUERTOS_HTTP or num in PUERTOS_HTTPS:
                        url = _construir_url(ip, num)
                        hallazgos, raw = await _ejecutar_gobuster(
                            url, parametros, escaneo_id,
                        )
                        if hallazgos:
                            puerto.setdefault("hallazgos_web", []).extend(hallazgos)
                        buffer.append(f"### dir {url}\n{raw}\n")

    exportados: dict[str, str] = {}
    if parametros.get("gobuster_exportar") and buffer:
        ruta = RESULTS_DIR / f"escaneo_{escaneo_id}_gobuster.txt"
        ruta.write_text("\n".join(buffer), encoding="utf-8")
        exportados["gobuster"] = str(ruta)
    return exportados


def _adjuntar(activos, dominio, hallazgos):
    """Asocia los hallazgos al activo cuyo hostname coincide. Si no hay
    coincidencia, los cuelga del primero como fallback."""
    asignado = False
    for a in activos:
        if a.get("hostname") == dominio and not asignado:
            a.setdefault("hallazgos_web", []).extend(hallazgos)
            asignado = True

    if not asignado and activos:
        activos[0].setdefault("hallazgos_web", []).extend(hallazgos)
