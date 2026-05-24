# =============================================================
# fastapi-engine — motor de escaneo modular
# Incluye nmap + gobuster + wordlists para escaneos reales.
# =============================================================
FROM python:3.12-slim

WORKDIR /app

# Dependencias del sistema:
#   - nmap: herramienta principal de escaneo de red.
#   - gobuster: enumeración de directorios web.
#   - dirb: aporta /usr/share/dirb/wordlists/common.txt y big.txt.
#   - dnsutils: necesario para resolver nombres durante escaneos DNS.
#   - curl, wget: descargar wordlists adicionales y healthcheck.
#   - ca-certificates: para conexiones HTTPS (NVD/MITRE).
RUN apt-get update && apt-get install -y --no-install-recommends \
        nmap \
        gobuster \
        dirb \
        dnsutils \
        curl \
        wget \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Wordlists para gobuster. dirb instala common.txt y big.txt en
# /usr/share/dirb/wordlists/. Creamos enlaces en una ruta unificada
# /usr/share/wordlists/ que es la que esperan los scanners.
RUN mkdir -p /usr/share/wordlists/dirb /usr/share/wordlists/dirbuster \
    && cp /usr/share/dirb/wordlists/common.txt /usr/share/wordlists/dirb/common.txt \
    && cp /usr/share/dirb/wordlists/big.txt /usr/share/wordlists/dirb/big.txt \
    && wget -q -O /usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt \
        https://raw.githubusercontent.com/daviddias/node-dirbuster/master/lists/directory-list-2.3-medium.txt \
        || cp /usr/share/dirb/wordlists/common.txt /usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt

# Dependencias Python.
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Código de la aplicación.
COPY . .

# El servicio NO publica puertos al host — solo lo expone dentro de
# la red Docker app-net para que laravel-app pueda alcanzarlo.
# Puerto 8888 según fase de diseño (no el 8000 por defecto de uvicorn).
EXPOSE 8888

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fs http://localhost:8888/health || exit 1

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8888"]
