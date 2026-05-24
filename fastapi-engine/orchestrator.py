"""Orquestador del motor: ejecuta nmap, gobuster (si toca) y enriquece CVEs."""
import logging

from cve_lookup import enriquecer_cves
from laravel_callback import LaravelCallback
from scanners import gobuster_dir as gobuster_mod
from scanners.nmap_scanner import NmapScanner

LOG = logging.getLogger("orchestrator")

# Plantillas que necesitan gobuster además de nmap.
PLANTILLAS_CON_GOBUSTER = {"web_audit"}


async def run_scan(escaneo_id, objetivo, parametros, callback_url):
    cb = LaravelCallback(callback_url)
    plantilla = parametros.get("plantilla", "quick_scan")
    LOG.info("[escaneo %s] inicio plantilla=%s objetivo=%s",
             escaneo_id, plantilla, objetivo)

    # Lo necesita gobuster para dns/vhost.
    parametros["_objetivo_original"] = objetivo

    try:
        await cb.estado("en_proceso", 5, "Iniciando")

        # 1. nmap
        nmap = NmapScanner(escaneo_id)
        await cb.estado("en_proceso", 10, f"Ejecutando nmap ({plantilla})")
        activos = await nmap.run(objetivo, parametros)
        comando_nmap = nmap.comando_ejecutado
        exportados = dict(nmap.exportados)

        # 2. gobuster opcional
        usar_gobuster = (
            plantilla in PLANTILLAS_CON_GOBUSTER
            or (plantilla == "custom" and parametros.get("gobuster_habilitar"))
        )
        if usar_gobuster and activos:
            await cb.estado("en_proceso", 70, "Enumeración web (gobuster)")
            extra = await gobuster_mod.ejecutar_sobre_activos(
                escaneo_id, activos, parametros,
            )
            exportados.update(extra)

        # 3. enriquecer CVEs contra NVD/MITRE
        await cb.estado("en_proceso", 90, "Enriqueciendo CVEs")
        await enriquecer_cves(activos)

        _quitar_campos_internos(activos)

        # 4. devolver resultados
        await cb.resultados(activos, comando=comando_nmap, exportados=exportados)
        total_puertos = sum(len(a.get("puertos", [])) for a in activos)
        await cb.estado(
            "completado", 100,
            f"{len(activos)} activos, {total_puertos} puertos",
        )
        LOG.info("[escaneo %s] completado", escaneo_id)

    except Exception as e:
        LOG.exception("[escaneo %s] error: %s", escaneo_id, e)
        await cb.estado("error", 0, "Error", error=str(e))


def _quitar_campos_internos(activos):
    """Limpia campos auxiliares antes de mandar el JSON a Laravel."""
    for a in activos:
        a.pop("_so_sugerido", None)
        for p in a.get("puertos", []):
            p.pop("scripts", None)
            p.pop("_pendiente_gobuster", None)
