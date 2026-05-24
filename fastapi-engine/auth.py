"""Autenticación FastAPI <-> Laravel mediante Bearer token compartido."""
import os
import secrets

from fastapi import Header, HTTPException

INTERNAL_TOKEN = os.environ.get("INTERNAL_TOKEN", "")


def verify_bearer(authorization: str = Header(default="")) -> None:
    # Estilo "una sola salida": acumulamos el motivo y el código en
    # variables locales y al final del cuerpo lanzamos la excepción
    # una sola vez si procede. Sin raises intermedios.
    motivo = ""
    codigo = 0

    if not INTERNAL_TOKEN:
        motivo = "INTERNAL_TOKEN no configurado."
        codigo = 500
    elif not authorization.startswith("Bearer "):
        motivo = "Falta el header Authorization."
        codigo = 401
    else:
        token = authorization[len("Bearer "):].strip()
        # compare_digest evita ataques por tiempo.
        if not secrets.compare_digest(token, INTERNAL_TOKEN):
            motivo = "Token inválido."
            codigo = 401

    if motivo:
        raise HTTPException(status_code=codigo, detail=motivo)
