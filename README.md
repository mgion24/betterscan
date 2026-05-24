# BetterScan

Plataforma web para gestionar auditorías de seguridad. Centraliza varios proyectos por cliente, lanza escaneos de nmap y gobuster desde un asistente web y muestra los resultados con sus CVEs enriquecidos contra NVD/MITRE.

> Trabajo de Fin de Grado del ciclo **Desarrollo de Aplicaciones Web (DAW)** - Marian Georgian Ion, IES Aguadulce, junio 2026.

## Antes de empezar: lea el archivo de credenciales

Si está evaluando este proyecto desde el ZIP de entrega, **abra primero el archivo `credenciales_seguridad_vps.txt`** que está en la raíz del ZIP (junto al `LEEME.md`). Contiene la URL del despliegue público en AWS (`https://betterscan.syncbetter.es`) y las credenciales reales de ese entorno.

Las contraseñas de la tabla "Usuarios de prueba" más abajo son **solo para el despliegue local con Docker** — son legibles y memorables (`Admin1234!`, `Audit1234!`, ...). En el despliegue público las contraseñas son distintas (20 caracteres aleatorios) para que el conocimiento de este `README.md` no abra la puerta a la instancia real. Es la misma estrategia que aplica cualquier SaaS comercial: las credenciales del sandbox son distintas a las de producción.

El archivo `credenciales_seguridad_vps.txt` está excluido del repositorio público (`.gitignore`) y solo se distribuye al tribunal junto al ZIP de entrega.

## Requisitos

Requisitos: Docker 24+ y Docker Compose v2.

## Usuarios de prueba (despliegue local)

| Email | Contraseña | Rol |
|---|---|---|
| `admin@betterscan.syncbetter.es` | `Admin1234!` | Administrador |
| `auditor@betterscan.syncbetter.es` | `Audit1234!` | Empleado |
| `auditor2@betterscan.syncbetter.es` | `Audit1234!` | Empleado |
| `cliente@acmecorp.com` | `Client1234!` | Cliente |
| `cliente@retailmax.es` | `Client1234!` | Cliente |

## Documentación

| Documento | Para qué |
|---|---|
| [`docs/GUIA_INSTALACION.md`](docs/GUIA_INSTALACION.md) | Pasos detallados para desplegar en local (Docker) y en VPS. |
| [`docs/GUIA_USO.md`](docs/GUIA_USO.md) | Manual de uso por rol (admin, auditor, cliente). |
| [`docs/PRUEBAS.md`](docs/PRUEBAS.md) | Batería de pruebas unitarias e integración con sus resultados. |
| [`docs/CUESTIONARIO_FINAL.md`](docs/CUESTIONARIO_FINAL.md) | Respuestas a las 4 preguntas finales del enunciado de Tarea 3. |

## Stack

- **PHP 8.4** + **Laravel 13** sobre **Apache 2.4** (mod_php + mod_rewrite + mod_ssl).
- **MariaDB 12.2** como base de datos.
- **Python 3.12** + **FastAPI** como motor de escaneo (módulos `nmap`, `gobuster`, `httpx`).
- **Vanilla JS** (sin frameworks) + Blade + CSS propio para el frontend + Bootstrap.
- **Docker Compose** para orquestar los 4 contenedores.

## Arquitectura

```
            [navegador]
                |
                | HTTPS (443)
                v
       ┌────────────────────┐
       │  betterscan-web    │  Apache 2.4 + PHP 8.4 (Laravel 13)
       │  /var/www/html     │
       └────┬───────────┬───┘
            │           │
         BD │           │ HTTP + Bearer token (red interna Docker)
            v           v
       ┌────────┐  ┌────────────────────────┐
       │MariaDB │  │  betterscan-fastapi    │  Python 3.12 + FastAPI
       │  12.2  │  │  nmap + gobuster + CVE │
       └────────┘  └───────────┬────────────┘
                               │ HTTPS pública (verify=false)
                               v
                          NVD + MITRE
                          (enriquecimiento CVE)
```

## Reglas de comunicación

1. Solo `betterscan-web` publica puertos al host (80 y 443).
2. FastAPI no se expone al exterior, solo le habla Laravel por la red interna.
3. Las llamadas Laravel ↔ FastAPI van autenticadas con un `Authorization: Bearer <INTERNAL_TOKEN>` compartido (`.env`).
4. Laravel es el único que escribe en MariaDB. FastAPI le devuelve los resultados por HTTP a un endpoint interno también protegido por el mismo token.

## Estructura del repositorio

```
betterscan/
├── docker-compose.yml         # Despliegue local
├── docker-compose.kali.yml    # Override para auditar la LAN del host
├── .env.example               # Ejemplo de .env
├── docs/                      # Guías + cuestionario final
├── laravel/                   # App Laravel
│   ├── Dockerfile             # php:8.4-apache + extensiones
│   ├── apache.conf            # VirtualHost HTTPS con mod_rewrite
│   ├── app/                   # Controllers, Models, Middlewares...
│   ├── public/                
│   │   ├── assets/ 
│   │   │   ├── css/           # El CSS completo del proyecto
│   │   │   ├── icons/
│   │   │   └── img/
│   │   │   └── js/            # El Javascript completo del proyecto
│   ├── database/
│   │   ├── migrations/        # 10 migraciones (una por tabla del E-R)
│   │   ├── seeders/           # Datos demo
│   │   └── init.sh            # GRANTs y usuario backup (init.d MariaDB)
│   ├── resources/
│   │   ├── views/             # Blade (14 vistas del diseño)
│   │   ├── css/app.css
│   │   └── js/
│   └── routes/                # Rutas de la APP
│   └── storage/               # Informes generados 
│   └── tests/                 # Tests PHPUnit (Unit + Feature)
├── fastapi-engine/            # Motor de escaneo
│   ├── Dockerfile
│   ├── main.py                # Endpoints
│   ├── orchestrator.py        # Orquestación nmap + gobuster + CVE
│   ├── nmap_runner.py         # Construcción del comando nmap
│   ├── cve_lookup.py          # NVD + MITRE
│   ├── scanners/
│   │   ├── nmap_scanner.py
│   │   └── gobuster_dir.py
│   └── tests/                 # Tests pytest
└── scripts_sql/               # Scripts SQL sin seeders de Laravel, en RAW
```

## Lanzar pruebas

Cobertura y resultados detallados en [`docs/PRUEBAS.md`](docs/PRUEBAS.md).

## Licencia

Todo el software de terceros usado es Open Source con sus respectivas licencias (MIT, GPL v2, Apache 2.0).
