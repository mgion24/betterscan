"""Motor de escaneo BetterScan (FastAPI)."""
import ipaddress
import logging
from typing import Any

import psutil
from fastapi import BackgroundTasks, Depends, FastAPI, HTTPException

from auth import verify_bearer
from orchestrator import run_scan

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
)

app = FastAPI(title="BetterScan engine", version="0.1.0")

# Estado en memoria de los escaneos lanzados (fallback al polling).
_estado_escaneos: dict[int, dict[str, Any]] = {}


# ---------- Modelos pydantic ----------

from pydantic import BaseModel, Field


class ScanParameters(BaseModel):
    plantilla: str
    velocidad: str = "T3"
    intensidad: str = "normal"
    puertos: str | None = None
    scripts_nse: list[str] = Field(default_factory=list)
    excluir: str | None = None
    detectar_os: bool = False
    detectar_servicios: bool = True
    gobuster_wordlist: str | None = "common"
    gobuster_extensiones: str | None = None
    gobuster_threads: int = 10


class ScanStartRequest(BaseModel):
    escaneo_id: int
    objetivo: str
    callback_url: str
    parametros: ScanParameters


# ---------- Endpoints ----------

@app.get("/health")
async def health() -> dict:
    return {"status": "ok"}


@app.post("/scan/start", status_code=202, dependencies=[Depends(verify_bearer)])
async def start_scan(req: ScanStartRequest, background: BackgroundTasks) -> dict:
    _estado_escaneos[req.escaneo_id] = {"estado": "en_proceso", "progreso_pct": 0}
    background.add_task(
        run_scan,
        escaneo_id=req.escaneo_id,
        objetivo=req.objetivo,
        parametros=req.parametros.model_dump(),
        callback_url=req.callback_url,
    )
    return {"accepted": True, "escaneo_id": req.escaneo_id}


@app.get("/scan/{escaneo_id}/status", dependencies=[Depends(verify_bearer)])
async def status(escaneo_id: int) -> dict:
    info = _estado_escaneos.get(escaneo_id)
    if info is None:
        raise HTTPException(404, "Escaneo no encontrado.")
    return info


@app.get("/network/interfaces", dependencies=[Depends(verify_bearer)])
async def network_interfaces() -> dict:
    # Lista interfaces IPv4 visibles desde el motor. Útil cuando se
    # arranca con network_mode: host para auditar la LAN. Filtramos
    # loopback (lo) y APIPA: el auditor decide cuál escanear.
    resultado: list[dict] = []
    stats = psutil.net_if_stats()

    for nombre, direcciones in psutil.net_if_addrs().items():
        info = stats.get(nombre)
        interfaz_up = (info is None) or info.isup
        es_loopback = (nombre == "lo")

        if interfaz_up and not es_loopback:
            for addr in direcciones:
                if addr.family.name == "AF_INET":
                    red = None
                    try:
                        red = ipaddress.IPv4Network(
                            f"{addr.address}/{addr.netmask}", strict=False,
                        )
                    except (ValueError, TypeError):
                        red = None

                    if red is not None and not red.is_loopback and not red.is_link_local:
                        num_hosts = (
                            red.num_addresses - 2
                            if red.prefixlen < 31
                            else red.num_addresses
                        )
                        resultado.append({
                            "nombre": nombre,
                            "ip": addr.address,
                            "netmask": addr.netmask,
                            "cidr": str(red),
                            "es_privada": red.is_private,
                            "num_hosts": num_hosts,
                        })

    return {"interfaces": resultado}
