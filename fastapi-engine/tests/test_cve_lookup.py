"""Pruebas unitarias del mapeo CVSS -> severidad."""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from cve_lookup import _severidad_por_cvss  # noqa: E402


def test_cvss_none_devuelve_nada():
    assert _severidad_por_cvss(None) == "nada"


def test_cvss_cero_devuelve_nada():
    assert _severidad_por_cvss(0.0) == "nada"


def test_cvss_baja():
    assert _severidad_por_cvss(0.1) == "baja"
    assert _severidad_por_cvss(3.9) == "baja"


def test_cvss_media():
    assert _severidad_por_cvss(4.0) == "media"
    assert _severidad_por_cvss(6.9) == "media"


def test_cvss_alta():
    assert _severidad_por_cvss(7.0) == "alta"
    assert _severidad_por_cvss(8.9) == "alta"


def test_cvss_critica():
    assert _severidad_por_cvss(9.0) == "critica"
    assert _severidad_por_cvss(10.0) == "critica"
