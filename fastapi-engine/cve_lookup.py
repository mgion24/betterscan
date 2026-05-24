"""Enriquecimiento de CVEs contra NVD y MITRE."""
import asyncio
import logging
import os
from typing import Any

import httpx

LOG = logging.getLogger("cve_lookup")

NVD_API_KEY = os.environ.get("NVD_API_KEY", "")
NVD_URL = "https://services.nvd.nist.gov/rest/json/cves/2.0"
MITRE_URL = "https://cveawg.mitre.org/api/cve/{cve}"

# Pausa entre llamadas. NVD permite 5 req/30s sin key y 50 req/30s con
# key. Con key bajamos a 0.7s/req (≈ 42 req/30s) para mantener margen.
DELAY = 0.7 if NVD_API_KEY else 6.0


# Mapeo de severidades NVD (LOW/MEDIUM/HIGH/CRITICAL) al ENUM de la BD.
_MAPA_SEVERIDAD = {
    "low": "baja",
    "medium": "media",
    "high": "alta",
    "critical": "critica",
}


async def enriquecer_cves(activos: list[dict[str, Any]]) -> None:
    """Rellena cvss/vector/severidad/descripción en cada vulnerabilidad."""
    cves = set()
    for a in activos:
        for p in a.get("puertos", []):
            for v in p.get("vulnerabilidades", []):
                if v.get("cve_asociado"):
                    cves.add(v["cve_asociado"])

    detalles: dict[str, dict] = {}

    if cves:
        async with httpx.AsyncClient(timeout=15.0) as client:
            for cve in cves:
                info = await _consultar(client, cve)
                if info:
                    detalles[cve] = info
                await asyncio.sleep(DELAY)

    for a in activos:
        for p in a.get("puertos", []):
            for v in p.get("vulnerabilidades", []):
                info = detalles.get(v.get("cve_asociado"))
                if info:
                    for clave in ("descripcion", "cvss", "vector",
                                  "severidad", "remediacion", "referencias"):
                        if info.get(clave) and not v.get(clave):
                            v[clave] = info[clave]

                # Fallback honesto: si ni NVD ni MITRE traen remediación
                # específica, ponemos un texto genérico que apunta a las
                # referencias oficiales. Nunca inventamos contenido técnico.
                if v.get("cve_asociado") and not v.get("remediacion"):
                    v["remediacion"] = (
                        "Aplicar la actualización del proveedor o las "
                        "mitigaciones publicadas en las referencias "
                        "oficiales adjuntas."
                    )


async def _consultar(client: httpx.AsyncClient, cve: str) -> dict | None:
    # Intentamos primero NVD (trae CVSS); si no llega o falta info,
    # vamos a MITRE como fallback y completamos huecos.
    info = await _nvd(client, cve)
    resultado = info

    if info is None or info.get("cvss") is None:
        mitre = await _mitre(client, cve)
        if info is not None and mitre:
            for k, v in mitre.items():
                if v and not info.get(k):
                    info[k] = v
            resultado = info
        elif info is None:
            resultado = mitre

    return resultado


async def _nvd(client: httpx.AsyncClient, cve: str) -> dict | None:
    headers = {"apiKey": NVD_API_KEY} if NVD_API_KEY else {}
    resultado: dict | None = None

    try:
        r = await client.get(NVD_URL, params={"cveId": cve}, headers=headers)
        if r.status_code == 200:
            data = r.json()
            vulns = data.get("vulnerabilities") or []
            if vulns:
                resultado = _parsear_nvd(vulns[0].get("cve", {}))
    except Exception as e:
        LOG.warning("Error NVD %s: %s", cve, e)
        resultado = None

    return resultado


def _parsear_nvd(cve_obj: dict) -> dict:
    """Extrae descripción + CVSS + vector + referencias del JSON de NVD."""
    desc = _texto_en_idioma(cve_obj.get("descriptions", []), "en")

    cvss = None
    vector = None
    severidad_raw = None
    metrics = cve_obj.get("metrics", {})

    # Recorremos sin break: el último match (cvssMetricV31) prevalece, y si
    # no, V30; si tampoco, V2 (rama aparte porque está en otro nivel).
    for clave in ("cvssMetricV30", "cvssMetricV31"):
        bloques = metrics.get(clave) or []
        if bloques:
            d = bloques[0].get("cvssData", {})
            cvss = d.get("baseScore")
            vector = d.get("vectorString")
            severidad_raw = (d.get("baseSeverity") or "").lower()

    if cvss is None:
        bloques_v2 = metrics.get("cvssMetricV2") or []
        if bloques_v2:
            d = bloques_v2[0]
            cvss = d.get("cvssData", {}).get("baseScore")
            vector = d.get("cvssData", {}).get("vectorString")
            severidad_raw = (d.get("baseSeverity") or "").lower()

    sev_final = _MAPA_SEVERIDAD.get(severidad_raw) or _severidad_por_cvss(cvss)

    refs = [
        r.get("url")
        for r in cve_obj.get("references", [])
        if r.get("url")
    ][:5]

    # CISA KEV / catálogo CISA: cuando NVD lo incluye, trae una
    # remediación accionable y oficial (ej. "Apply updates per vendor
    # instructions" o el procedimiento completo para Log4Shell).
    remediacion = cve_obj.get("cisaRequiredAction") or None

    return {
        "descripcion": desc,
        "cvss": cvss,
        "vector": vector,
        "severidad": sev_final,
        "remediacion": remediacion,
        "referencias": "\n".join(refs) if refs else None,
    }


async def _mitre(client: httpx.AsyncClient, cve: str) -> dict | None:
    resultado: dict | None = None

    try:
        r = await client.get(MITRE_URL.format(cve=cve))
        if r.status_code == 200:
            data = r.json()
            cna = (data.get("containers") or {}).get("cna") or {}
            desc = _texto_en_idioma(cna.get("descriptions", []), "en")

            # MITRE rara vez rellena solutions/workarounds, pero cuando
            # lo hace son textos del propio fabricante. Probamos los dos
            # campos por si alguno tiene contenido.
            remediacion = (
                _texto_en_idioma(cna.get("solutions", []), "en")
                or _texto_en_idioma(cna.get("workarounds", []), "en")
                or None
            )

            refs = [
                ref.get("url")
                for ref in cna.get("references", [])
                if ref.get("url")
            ][:5]
            resultado = {
                "descripcion": desc,
                "cvss": None,
                "vector": None,
                "severidad": None,
                "remediacion": remediacion,
                "referencias": "\n".join(refs) if refs else None,
            }
    except Exception as e:
        LOG.warning("Error MITRE %s: %s", cve, e)
        resultado = None

    return resultado


def _texto_en_idioma(descripciones: list[dict], idioma: str) -> str:
    """Primer 'value' de la lista cuyo 'lang' coincide con `idioma`."""
    encontrado = ""
    for d in descripciones:
        if d.get("lang") == idioma and encontrado == "":
            encontrado = d.get("value", "")
    return encontrado


def _severidad_por_cvss(cvss):
    """Mapea CVSS numérico al ENUM de severidad. Equivalente al método
    `Vulnerabilidad::severidadDesdeCvss()` de Laravel (deben coincidir)."""
    nivel = "critica"

    if cvss is None or cvss <= 0:
        nivel = "nada"
    elif cvss < 4.0:
        nivel = "baja"
    elif cvss < 7.0:
        nivel = "media"
    elif cvss < 9.0:
        nivel = "alta"

    return nivel
