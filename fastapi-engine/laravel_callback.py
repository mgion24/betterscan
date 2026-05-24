"""Cliente HTTP que llama a Laravel desde el motor."""
import logging
import os
from typing import Any

import httpx

LOG = logging.getLogger("laravel_callback")
INTERNAL_TOKEN = os.environ.get("INTERNAL_TOKEN", "")


class LaravelCallback:
    def __init__(self, base_callback_url: str) -> None:
        # base_callback_url ya incluye el escaneo_id:
        # http://laravel-app/api/internal/escaneo/42
        self.base = base_callback_url.rstrip("/")
        self.headers = {
            "Authorization": f"Bearer {INTERNAL_TOKEN}",
            "Accept": "application/json",
        }

    async def estado(self, estado: str, progreso_pct: int,
                     fase_actual: str = "", error: str | None = None) -> None:
        payload: dict[str, Any] = {
            "estado": estado,
            "progreso_pct": progreso_pct,
            "fase_actual": fase_actual,
        }
        if error:
            payload["error"] = error
        await self._post(f"{self.base}/estado", payload)

    async def resultados(self, activos: list[dict], comando: str = "",
                         exportados: dict[str, str] | None = None) -> None:
        payload: dict[str, Any] = {"activos": activos}
        if comando:
            payload["comando"] = comando
        if exportados:
            payload["exportados"] = exportados
        await self._post(f"{self.base}/resultados", payload)

    async def _post(self, url: str, payload: dict) -> None:
        # verify=False: el callback va por la red interna de Docker. El
        # cert del proxy es self-signed; la autenticación va por Bearer.
        try:
            async with httpx.AsyncClient(timeout=10.0, verify=False) as client:
                r = await client.post(url, json=payload, headers=self.headers)
                if r.status_code >= 400:
                    LOG.warning("Callback Laravel %s -> %s: %s",
                                url, r.status_code, r.text)
        except Exception as e:
            LOG.error("Error llamando a Laravel %s: %s", url, e)
