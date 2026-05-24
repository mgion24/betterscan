# BetterScan — Guía de instalación y configuración

> **Autor:** Marian Georgian Ion
> **Ciclo:** Desarrollo de Aplicaciones Web (DAW)
> **IES Aguadulce — TFG, junio 2026**

Esta guía explica cómo poner en marcha BetterScan en un equipo Linux desde cero. La aplicación se despliega entera con Docker Compose: contenedor web (Apache 2.4 + PHP 8.4), contenedor del motor de escaneo (FastAPI), MariaDB y un objetivo vulnerable (DVWA) para hacer pruebas.

---

## 1. Requisitos previos

El equipo donde se despliegue tiene que tener instalado:

| Software | Versión mínima | Por qué |
|---|---|---|
| Docker Engine | 24 o superior | Para los 4 contenedores. |
| Docker Compose v2 | incluido en Docker | El fichero `docker-compose.yml` usa la sintaxis v2. |
| Git | cualquier reciente | Para clonar el repositorio. |
| 4 GB de RAM libres | — | Tres contenedores PHP/Python + MariaDB. |
| 4 GB de disco libre | — | Imágenes Docker + base de datos. |

Probado en Ubuntu 24.04 LTS, Debian 12 y Kali 2024.3. Cualquier Linux con Docker debería servir. Windows y macOS funcionan con Docker Desktop pero no se ha probado para la entrega.

### Comprobar que Docker funciona

```bash
docker --version
docker compose version
docker run --rm hello-world
```

Si el último comando no imprime "Hello from Docker!", revise que su usuario está en el grupo `docker`:

```bash
sudo usermod -aG docker $USER
# Cierra sesión y vuelve a entrar.
```

---

## 2. Obtener el código

```bash
cd /opt   # o donde prefieras
git clone https://github.com/mgion24/betterscan
# También es descargable desde mi repositorio personal: https://git.syncbetter.es/marian/betterscan
cd betterscan
```

Si se tiene como ZIP:

```bash
unzip Ion_Marian_Georgian_PROY3.zip
cd Ion_Marian_Georgian_PROY3/src/betterscan
```

La estructura es:

```
betterscan/
├── docker-compose.yml          ← Despliegue local (por defecto)
├── docker-compose.kali.yml     ← Override para auditorías reales contra LAN
├── .env.example                ← Plantilla de variables de entorno
├── README.md                   ← Resumen del proyecto
├── docs/                       ← Esta documentación
├── laravel/                    ← Aplicación Laravel (Apache + PHP 8.4)
└── fastapi-engine/             ← Motor de escaneo (FastAPI + nmap + gobuster)
```

---

## 3. Configurar las variables de entorno

Copie la plantilla **en dos sitios** (la raíz para Docker Compose y `laravel/` para que el contenedor encuentre el `.env` que Laravel necesita):

```bash
cd betterscan
cp .env.example .env
nano .env                      # Se editan y cambian las variables por las que se requiera
cp .env laravel/.env           # imprescindible: bind-mount apunta a ./laravel
```

Variables que **se tienen que cambiar antes de arrancar** (en los dos archivos):

| Variable | Cómo generarla |
|---|---|
| `INTERNAL_TOKEN` | `openssl rand -hex 32` → pegue el resultado obtenido. |
| `DB_ROOT_PASSWORD` | Una contraseña fuerte (será el root de MariaDB). |
| `DB_PASSWORD` | Otra contraseña fuerte (usuario de la app). |

Variables **opcionales pero muy recomendadas**:

| Variable | Qué hace | Cómo obtenerla |
|---|---|---|
| `NVD_API_KEY` | Sube el rate limit del enriquecimiento de CVEs contra NVD de **5 req / 30 s** (sin key) a **50 req / 30 s** (con key). Sin esto, un escaneo con muchas vulnerabilidades tarda mucho más y puede dejar varias con severidad "nada" si NVD rechaza por rate limit. | Gratuita en `https://nvd.nist.gov/developers/request-an-api-key`. Tarda unos minutos en llegar al email del solicitante. |

El motor (`cve_lookup.py`) detecta automáticamente si hay key y ajusta el `DELAY` entre llamadas: `0.7 s` con key, `6 s` sin key. El resto se puede dejar tal cual para una instalación local.

> **Importante.** `INTERNAL_TOKEN` es el secreto compartido entre la app Laravel y el motor FastAPI. Si los dos servicios no tienen el mismo valor, las llamadas internas fallarán con 401.
>
> **Por qué dos `.env`.** El `.env` de la raíz lo lee Docker Compose para inyectar variables a los contenedores; el `laravel/.env` es el que Laravel lee en disco (el contenedor monta `./laravel` en `/var/www/html`). El entrypoint del contenedor genera la `APP_KEY` en el segundo `.env` la primera vez que arranca.

---

## 4. Construir y arrancar

Desde la raíz del proyecto:

```bash
docker compose up -d --build
```

La primera vez tarda 3-5 minutos (descarga imágenes, instala dependencias PHP y Python, hace migraciones y seed).

Cuando termine:

```bash
docker compose ps
```

Debería mostrar 4 contenedores en estado **running**:

| Nombre | Imagen | Puertos |
|---|---|---|
| `betterscan-web` | `php:8.4-apache` (custom) | 80, 443 |
| `betterscan-fastapi` | Python 3.12 (custom) | — interno |
| `betterscan-target` | `vulnerables/web-dvwa` | — interno |
| `betterscan-mariadb` | `mariadb:12.2.2` | — interno |

---

## 5. Acceder a la aplicación

Abre en el navegador:

```
# En caso de instalación local
https://localhost # ó https://127.0.0.1

# En caso de acceso a la app por URL
https://betterscan.syncbetter.es/
```

El certificado es **self-signed**, así que el navegador le avisará. Para producción real habría que generar uno con Let's Encrypt (instrucciones en la sección 9).

Si todo va bien, accederá a la pantalla de login.

### Usuarios de prueba

| Email | Contraseña | Rol |
|---|---|---|
| `admin@betterscan.syncbetter.es` | `Admin1234!` | Administrador |
| `auditor@betterscan.syncbetter.es` | `Audit1234!` | Empleado |
| `auditor2@betterscan.syncbetter.es` | `Audit1234!` | Empleado |
| `cliente@acmecorp.com` | `Client1234!` | Cliente |
| `cliente@retailmax.es` | `Client1234!` | Cliente |

> Se cambian en producción: están definidas en `laravel/database/seeders/UsuarioSeeder.php`.

**Nota:** El proyecto no hace validación de dominios real para el email. En futuras mejoras se podría implementar autenticación OAuth 2.0, por ejemplo, de Google, pudiendo tener un Workspace empresarial con el subdominio de la empresa (betterscan.syncbetter.es) y que todos los empleados se autentiquen contra BetterScan usando ese correo de Google.

En el comprimido de entrega, junto al ZIP, se incluye el archivo `credenciales_seguridad_vps.txt` con las credenciales del **despliegue público** en `https://betterscan.syncbetter.es`. Esas contraseñas son distintas a las del seeder (generadas con 20 caracteres aleatorios) para evitar que un atacante que conozca este documento pueda entrar en la instancia pública.

---

## 6. Probar que funciona

Una vez dentro como **admin** o **empleado**, se prueba el flujo principal:

1. Pulse **+ Nuevo Proyecto** y créalo asociado a una empresa.
2. Entre al proyecto y pulse **+ Nuevo Escaneo**.
3. En el paso 1, ponga como objetivo `target-vulnerable` (es el contenedor DVWA del propio Compose, accesible desde el motor por su nombre interno).
4. Elija la plantilla "Escaneo rápido".
5. Pulse **Lanzar escaneo**.

La página de progreso refrescará cada 2 segundos. Al terminar le llevará a los resultados con los puertos descubiertos.

---

## 7. Parar y reiniciar

```bash
# Parar manteniendo los datos.
docker compose stop

# Volver a arrancar.
docker compose start

# Parar y borrar contenedores (los datos persisten en volúmenes).
docker compose down

# Reset completo: borrar también la base de datos.
docker compose down -v
```

---

## 8. Problemas habituales

### El navegador da "ERR_SSL_PROTOCOL_ERROR"

El certificado de Apache se genera durante el build. Si lo ha copiado/movido y el build no termina bien:

```bash
docker compose build --no-cache web
docker compose up -d
```

### MariaDB no arranca y dice "table x doesn't exist"

Suele ser que el volumen de la BD quedó a medias. Bórrelo y vuelva a empezar:

```bash
docker compose down -v
docker compose up -d --build
```

### "No se pudo conectar con el motor de escaneo FastAPI"

Mire los logs del motor:

```bash
docker compose logs -f fastapi-engine
```

Verifique que el `INTERNAL_TOKEN` es el mismo en el `.env` y en el contenedor:

```bash
docker compose exec fastapi-engine env | grep INTERNAL_TOKEN
docker compose exec web env | grep INTERNAL_TOKEN
```

### Cambios en el código no se reflejan

El bind-mount está activo (`./laravel:/var/www/html`), así que los `.php` y `.blade.php` se recargan al instante. Si ha tocado `config/` o `routes/` y no se ve el cambio, limpie la caché:

```bash
docker compose exec web php artisan config:clear
docker compose exec web php artisan route:clear
docker compose exec web php artisan view:clear
```

---

## 9. Despliegue en producción (AWS + Cloudflare Tunnel)

Para la entrega final del TFG, BetterScan se ha desplegado en **AWS EC2** dentro de la región `eu-south-2` (Zaragoza, España) y se publica al exterior a través de un **Cloudflare Tunnel**. Esta combinación se eligió frente a un VPS plano por tres motivos: el datacenter está en territorio español (cumple RGPD por defecto), Cloudflare Tunnel permite que la instancia EC2 no tenga ningún puerto inbound abierto a internet (defensa en profundidad), y el *Savings Plan* de AWS abarata la factura mensual frente a la tarifa on-demand.

La URL pública del despliegue es **https://betterscan.syncbetter.es**. Las credenciales de acceso a esa instancia se entregan al tribunal en el fichero `credenciales_seguridad_vps.txt` (NO incluido en el repositorio público, sí en el ZIP).

### 9.1. Recursos AWS

| Recurso | Configuración |
|---|---|
| Instancia | EC2 `t3.medium` (2 vCPU, 4 GB RAM) en `eu-south-2a` (Zaragoza) |
| AMI | Ubuntu Server 24.04 LTS |
| Volumen | EBS `gp3` 20 GB cifrado en reposo (KMS) |
| Security Group | **Solo egress permitido**. Sin reglas inbound — el túnel de Cloudflare hace conexión saliente al edge de Cloudflare, no se abre ningún puerto al exterior. |
| Coste estimado | 280 €/año con Savings Plan a 1 año (aproximadamente 23 €/mes) |
| Backup | Snapshot diario del EBS a S3 Glacier Deep Archive |

### 9.2. Cloudflare Tunnel

En lugar de exponer 80/443 directamente, se usa el cliente `cloudflared` como **named tunnel**:

```bash
# En la instancia EC2, tras un docker compose up correcto:
sudo apt install cloudflared
cloudflared tunnel login                                   # autentica con Cloudflare
cloudflared tunnel create betterscan-prod                  # crea el túnel
cloudflared tunnel route dns betterscan-prod betterscan.syncbetter.es
```

`/etc/cloudflared/config.yml`:

```yaml
tunnel: <UUID-del-tunnel>
credentials-file: /etc/cloudflared/<UUID>.json
ingress:
  - hostname: betterscan.syncbetter.es
    service: https://localhost:443
    originRequest:
      noTLSVerify: true   # Apache sigue con cert self-signed; Cloudflare confía en él en red local.
  - service: http_status:404
```

Cloudflare emite un **Origin Certificate** con vigencia de 15 años para el dominio `*.syncbetter.es`, por lo que no hace falta `certbot` ni renovaciones de Let's Encrypt en el host. El cliente final ve un certificado público válido emitido por Cloudflare, sin advertencias.

Servicio systemd para arranque automático:

```bash
sudo cloudflared service install
sudo systemctl enable cloudflared
sudo systemctl start cloudflared
```

### 9.3. Variables de entorno de producción

En el `.env` de la instancia (raíz **y** `laravel/.env`):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://betterscan.syncbetter.es
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=betterscan.syncbetter.es
```

### 9.4. Bastionado mínimo de la instancia

- **SSH solo por clave** (PasswordAuthentication no, PermitRootLogin no). Acceso administrativo desde la VPN privada del autor, no por IP pública.
- **Fail2Ban** activo sobre el log `auth.log` (ban tras 5 intentos en 10 min).
- `unattended-upgrades` habilitado para parches de seguridad de Ubuntu.
- **No** se usa UFW abriendo 80/443 al exterior: la instancia no tiene puertos públicos. El único acceso desde fuera es a través del túnel de Cloudflare.
- ClamAV se documenta como mejora futura — no instalado en este despliegue por el coste de RAM en `t3.medium`.

### 9.5. Backup

- **Snapshot EBS** diario a las 04:00 vía AWS Backup, retención de 7 días.
- **mysqldump** lógico del esquema `betterscan_db` semanal con el usuario `betterscan_backup` (solo SELECT/SHOW VIEW/LOCK TABLES), subido a S3 Glacier Deep Archive.
- Coste de backup ≈ 6 €/año para ≈10 GB efectivos comprimidos.

### 9.6. Ventana de operación

La instancia EC2 se mantiene encendida entre **el 5 y el 20 de junio de 2026** (ventana de defensa + revisión). Fuera de esa ventana se apaga para no incurrir en costes innecesarios — la facturación de AWS es por hora real de uso. Las credenciales y la URL del fichero `credenciales_seguridad_vps.txt` solo son válidas durante esa ventana.

> Justificación económica completa (costes, comparativa con OpenVAS/Nessus y modelo de financiación) en `docs/CUESTIONARIO_FINAL.md`, sección 4.

---

## 10. Ejecutar las pruebas

Para correr las pruebas unitarias y de integración:

```bash
# Tests Laravel (PHPUnit, 27 tests). El override de DB_CONNECTION fuerza
# la BD a SQLite en memoria para los tests Feature (RefreshDatabase
# necesita permisos DDL que el usuario betterscan_app no tiene).
docker compose exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: \
                    -e DB_HOST= -e DB_PORT= \
                    web vendor/bin/phpunit

# Tests FastAPI (pytest, 23 tests).
docker compose exec fastapi-engine pytest -v
```

Resultados esperados y descripción de cada prueba en el documento `docs/PRUEBAS.md`.
