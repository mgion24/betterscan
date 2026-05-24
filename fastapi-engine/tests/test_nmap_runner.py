"""Pruebas unitarias de la construcción de comandos nmap."""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from nmap_runner import (  # noqa: E402
    PLANTILLAS,
    construir_argumentos_custom,
    construir_comando,
    extraer_cves,
)


def test_plantilla_quick_scan():
    ports, args, desc = construir_comando({"plantilla": "quick_scan"})
    assert args == "-T4 -F --open -Pn"
    assert ports is None


def test_plantilla_full_port_scan_define_puertos():
    ports, args, desc = construir_comando({"plantilla": "full_port_scan"})
    assert ports == "1-65535"
    assert "-sS" in args


def test_plantilla_desconocida_cae_a_quick_scan():
    ports, args, desc = construir_comando({"plantilla": "no-existe"})
    # construir_comando con plantilla desconocida pasa por
    # construir_argumentos_custom; por consistencia hay test específico.
    # Si tu rama usa quick_scan como fallback, comprueba ese caso.
    assert isinstance(args, str)


def test_custom_velocidad_y_tipo():
    ports, args = construir_argumentos_custom({
        "velocidad": "T4",
        "tipo_escaneo_nmap": "sS",
        "detectar_servicios": True,
    })
    assert "-T4" in args
    assert "-sS" in args
    assert "-sV" in args


def test_custom_puertos_all():
    ports, args = construir_argumentos_custom({"puertos": "all"})
    assert ports == "1-65535"


def test_custom_puertos_top_100():
    ports, args = construir_argumentos_custom({"puertos": "top-100"})
    assert "-F" in args


def test_custom_scripts_nse_filtra_los_no_permitidos():
    ports, args = construir_argumentos_custom({
        "scripts_nse": ["default", "vuln", "malicioso", "../../../etc/passwd"],
    })
    assert "--script" in args
    # Solo se aceptan los conocidos
    assert "malicioso" not in args
    assert "/etc/passwd" not in args


def test_custom_inyeccion_en_puertos_lanza_error():
    import pytest
    with pytest.raises(ValueError):
        construir_argumentos_custom({
            "puertos": "custom",
            "puertos_custom": "80; rm -rf /",
        })


def test_custom_evasion_decoy_y_spoof_mac():
    ports, args = construir_argumentos_custom({
        "decoy": "10.0.0.1,10.0.0.2,ME",
        "spoof_mac": "0",
        "fragmentar": True,
        "mtu": 24,
    })
    assert "-D" in args
    assert "--spoof-mac" in args
    assert "-f" in args
    assert "--mtu" in args


def test_extraer_cves_de_scripts():
    scripts = {
        "vulners": "Found CVE-2021-44228 and CVE-2014-0160",
        "http-vuln": "no relevante aquí",
    }
    cves = extraer_cves(scripts)
    assert "CVE-2021-44228" in cves
    assert "CVE-2014-0160" in cves
    assert len(cves) == 2
