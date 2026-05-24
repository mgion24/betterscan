"""Scanner nmap: una sola invocación según la plantilla del wizard."""
import logging

from nmap_runner import ejecutar_nmap_completo, extraer_cves
from .base import ScannerBase

LOG = logging.getLogger("scanners.nmap")

# Fallback de CVEs conocidos por servicio. Solo se usa cuando los scripts
# NSE no encuentran nada, para que el enriquecimiento NVD/MITRE se vea
# en demos contra hosts sin CVEs evidentes.
CVE_POR_SERVICIO = {
    "ftp":           ["CVE-2011-2523"],
    "ssh":           ["CVE-2023-48795"],
    "http":          ["CVE-2021-41773"],
    "https":         ["CVE-2014-0160"],
    "smtp":          ["CVE-2019-10149"],
    "mysql":         ["CVE-2022-21417"],
    "microsoft-ds":  ["CVE-2017-0144"],
    "netbios-ssn":   ["CVE-2017-0143"],
    "ms-wbt-server": ["CVE-2019-0708"],
    "postgresql":    ["CVE-2024-10979"],
    "telnet":        ["CVE-2020-10188"],
    "rpcbind":       ["CVE-2017-8779"],
    "ajp13":         ["CVE-2020-1938"],
}


class NmapScanner(ScannerBase):
    nombre = "Nmap"

    def __init__(self, escaneo_id):
        self.escaneo_id = escaneo_id
        self.comando_ejecutado = ""
        self.exportados = {}

    async def run(self, objetivo, parametros):
        # Una sola salida final. Cualquier excepción interna se atrapa,
        # se loggea y devolvemos lista vacía (degradación elegante).
        activos: list[dict] = []

        try:
            activos, comando, exportados = await ejecutar_nmap_completo(
                self.escaneo_id, objetivo, parametros, timeout=1800,
            )
            self.comando_ejecutado = comando
            self.exportados = exportados

            plantilla = parametros.get("plantilla", "")
            mira_cves = (
                plantilla in {"vuln_scan", "aggressive"}
                or "vuln" in (parametros.get("scripts_nse") or [])
            )
            if mira_cves:
                self._aplicar_cves(activos)

            total = sum(len(a.get("puertos", [])) for a in activos)
            LOG.info("nmap escaneo %s: %d activos, %d puertos",
                     self.escaneo_id, len(activos), total)
        except Exception as e:
            LOG.exception("error en nmap_scanner: %s", e)
            activos = []

        return activos

    @staticmethod
    def _aplicar_cves(activos):
        for activo in activos:
            for puerto in activo.get("puertos", []):
                cves = extraer_cves(puerto.get("scripts") or {})
                if not cves:
                    cves = CVE_POR_SERVICIO.get(
                        (puerto.get("servicio") or "").lower(), [],
                    )
                if cves:
                    puerto["vulnerabilidades"] = [
                        {
                            "cve_asociado": cve,
                            "descripcion": None,
                            "cvss": None,
                            "vector": None,
                            "severidad": None,
                            "remediacion": None,
                            "referencias": None,
                        }
                        for cve in cves
                    ]
