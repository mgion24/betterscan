"""Pruebas de integración del endpoint /health."""
import os
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

os.environ["INTERNAL_TOKEN"] = "token-de-pruebas-1234567890"

from fastapi.testclient import TestClient  # noqa: E402
from main import app  # noqa: E402


client = TestClient(app)


def test_health_sin_auth():
    resp = client.get("/health")
    assert resp.status_code == 200
    assert resp.json()["status"] == "ok"


def test_scan_start_sin_token_devuelve_401():
    resp = client.post("/scan/start", json={
        "escaneo_id": 1,
        "objetivo": "127.0.0.1",
        "callback_url": "http://localhost/cb/1",
        "parametros": {"plantilla": "quick_scan"},
    })
    assert resp.status_code == 401


def test_scan_start_con_token_correcto_acepta():
    resp = client.post(
        "/scan/start",
        json={
            "escaneo_id": 999,
            "objetivo": "127.0.0.1",
            "callback_url": "http://localhost/cb/999",
            "parametros": {"plantilla": "quick_scan"},
        },
        headers={"Authorization": "Bearer token-de-pruebas-1234567890"},
    )
    # 202 Accepted (encolado).
    assert resp.status_code == 202
    body = resp.json()
    assert body["accepted"] is True
    assert body["escaneo_id"] == 999
