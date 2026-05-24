"""Pruebas unitarias de la verificación de Bearer token."""
import os
import sys
from pathlib import Path

import pytest

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

os.environ["INTERNAL_TOKEN"] = "token-de-pruebas-1234567890"

import auth  # noqa: E402
from fastapi import HTTPException  # noqa: E402


def test_token_correcto_pasa():
    # No debe lanzar excepción
    auth.verify_bearer("Bearer token-de-pruebas-1234567890")


def test_token_incorrecto_lanza_401():
    with pytest.raises(HTTPException) as exc:
        auth.verify_bearer("Bearer otro-token")
    assert exc.value.status_code == 401


def test_sin_header_lanza_401():
    with pytest.raises(HTTPException) as exc:
        auth.verify_bearer("")
    assert exc.value.status_code == 401


def test_sin_prefijo_bearer_lanza_401():
    with pytest.raises(HTTPException) as exc:
        auth.verify_bearer("token-de-pruebas-1234567890")
    assert exc.value.status_code == 401
