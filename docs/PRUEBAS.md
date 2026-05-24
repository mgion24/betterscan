# BetterScan — Pruebas unitarias y de integración

> Cumple el punto 2 del enunciado de la Tarea 3 (diseño y ejecución de batería de pruebas).

---

## 1. Resumen

Se han escrito dos tipos de pruebas:

| Tipo | Tecnología | Ubicación | Nº de pruebas |
|---|---|---|---|
| Unitarias (PHP) | PHPUnit 12.5 | `laravel/tests/Unit/` | 15 |
| Integración (PHP, BD SQLite en memoria) | PHPUnit + Laravel testing | `laravel/tests/Feature/` | 12 |
| Unitarias e integración (Python) | pytest 8 | `fastapi-engine/tests/` | 23 |

**Total: 50 pruebas, 0 fallos en la ejecución final.**

### Cómo ejecutarlas

```bash
# Una vez levantado el docker compose:

# Tests PHPUnit (Laravel). Hace falta sobreescribir DB_CONNECTION a sqlite
# en la línea exec porque el contenedor recibe DB_CONNECTION=mysql del
# docker-compose y Laravel 11+ no respeta el atributo force="true" del
# phpunit.xml para esa variable concreta.
docker compose exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: \
                    -e DB_HOST= -e DB_PORT= \
                    web vendor/bin/phpunit

# Tests pytest (motor FastAPI).
docker compose exec fastapi-engine pytest -v
```

O en el host de desarrollo, sin Docker, instalando las dependencias correspondientes (composer install y pip install -r requirements.txt).

Resultado obtenido en local antes de la entrega:

```
PHPUnit: 27 tests, 48 assertions, OK
pytest:  23 tests passed in 0.52s
```

---

## 2. Pruebas unitarias PHP (Laravel)

Verifican las funciones puras de los modelos, sin tocar la base de datos.

### 2.1. `VulnerabilidadTest` — mapeo CVSS → severidad

Comprueba que `Vulnerabilidad::severidadDesdeCvss()` sigue exactamente la tabla del estándar CVSS v3.1.

| Caso | Entrada | Resultado esperado | OK |
|---|---|---|---|
| CVSS nulo | `null` | `"nada"` | ✓ |
| CVSS cero | `0.0` | `"nada"` | ✓ |
| Severidad baja (límite inf.) | `0.1` | `"baja"` | ✓ |
| Severidad baja (límite sup.) | `3.9` | `"baja"` | ✓ |
| Severidad media (límite inf.) | `4.0` | `"media"` | ✓ |
| Severidad media (límite sup.) | `6.9` | `"media"` | ✓ |
| Severidad alta (límite inf.) | `7.0` | `"alta"` | ✓ |
| Severidad alta (límite sup.) | `8.9` | `"alta"` | ✓ |
| Severidad crítica (límite inf.) | `9.0` | `"critica"` | ✓ |
| Severidad crítica (límite sup.) | `10.0` | `"critica"` | ✓ |

### 2.2. `UsuarioTest` — helpers del modelo Usuario

| Caso | Entrada | Resultado esperado | OK |
|---|---|---|---|
| Nombre completo | `nombre="Marian", apellido="Ion"` | `"Marian Ion"` | ✓ |
| Iniciales | `nombre="Marian", apellido="Ion"` | `"MI"` | ✓ |
| `esAdmin` con rol admin | rol `"admin"` | `true` | ✓ |
| `esEmpleado` con rol admin | rol `"admin"` | `false` | ✓ |
| `esCliente` con rol cliente | rol `"cliente"` | `true` | ✓ |
| Helpers sin rol cargado | rol `null` | `false`, `false`, `false` | ✓ |

### 2.3. `EscaneoTest` — estado activo

| Caso | Entrada | Resultado esperado | OK |
|---|---|---|---|
| Pendiente cuenta como activo | `estado = "pendiente"` | `estaActivo() === true` | ✓ |
| En proceso cuenta como activo | `estado = "en_proceso"` | `estaActivo() === true` | ✓ |
| Completado NO es activo | `estado = "completado"` | `estaActivo() === false` | ✓ |
| Error NO es activo | `estado = "error"` | `estaActivo() === false` | ✓ |

---

## 3. Pruebas de integración PHP (Laravel + SQLite en memoria)

Ejecutan el ciclo completo (request → controlador → BD → vista), pero contra una base de datos SQLite que se crea y destruye con cada prueba mediante el trait `RefreshDatabase`. Así no se contaminan los datos del seed.

### 3.1. `LoginTest` — autenticación

| Caso | Entrada | Resultado esperado | OK |
|---|---|---|---|
| Página de login carga | `GET /login` sin sesión | HTTP 200 | ✓ |
| Login válido redirige al dashboard | `POST /login` con email y password correctos | redirect a `/dashboard`, sesión iniciada | ✓ |
| Password incorrecta da error | `POST /login` con password mala | sesión con errors en `email`, sin login | ✓ |
| Logout cierra sesión | `POST /logout` autenticado | redirect a `/login`, sesión cerrada | ✓ |

### 3.2. `AutorizacionTest` — control de roles (RNF-08)

Verifica que el middleware `EnsureRole` cumple el principio de privilegio mínimo.

| Caso | Entrada | Resultado esperado | OK |
|---|---|---|---|
| Sin sesión no se ve el dashboard | `GET /dashboard` sin login | redirect a `/login` | ✓ |
| Admin puede ver listado de usuarios | `GET /usuarios` como admin | HTTP 200 | ✓ |
| Cliente NO puede ver listado de usuarios | `GET /usuarios` como cliente | HTTP 403 | ✓ |
| Cliente es redirigido a su portal | `GET /dashboard` como cliente | redirect a `/portal` | ✓ |

### 3.3. `CallbackInternoTest` — endpoint interno autenticado por token

Verifica el endpoint `/api/internal/escaneo/{id}/estado` que llama el motor FastAPI.

| Caso | Entrada | Resultado esperado | OK |
|---|---|---|---|
| Sin token devuelve 401 | `POST` sin header Authorization | HTTP 401 | ✓ |
| Token incorrecto devuelve 401 | `POST` con `Authorization: Bearer token-malo` | HTTP 401 | ✓ |
| Token correcto actualiza el estado | `POST` con token válido y body `{estado: "en_proceso", progreso_pct: 50}` | HTTP 200, BD actualizada | ✓ |
| Estado inválido devuelve 422 | `POST` con `estado: "inventado"` | HTTP 422 | ✓ |

---

## 4. Pruebas Python (pytest, FastAPI)

### 4.1. `test_auth.py` — autenticación Bearer

| Caso | Entrada | Resultado esperado | OK |
|---|---|---|---|
| Token correcto pasa sin excepción | `"Bearer <TOKEN_VÁLIDO>"` | sin HTTPException | ✓ |
| Token incorrecto lanza 401 | `"Bearer otro-token"` | `HTTPException 401` | ✓ |
| Header vacío lanza 401 | `""` | `HTTPException 401` | ✓ |
| Sin prefijo Bearer lanza 401 | `"<TOKEN_VÁLIDO>"` (sin "Bearer ") | `HTTPException 401` | ✓ |

### 4.2. `test_nmap_runner.py` — construcción del comando nmap

| Caso | Entrada | Resultado esperado | OK |
|---|---|---|---|
| Plantilla `quick_scan` | `{plantilla: "quick_scan"}` | args = `"-T4 -F --open -Pn"`, ports = `None` | ✓ |
| Plantilla `full_port_scan` define puertos | `{plantilla: "full_port_scan"}` | ports = `"1-65535"`, args contiene `-sS` | ✓ |
| Plantilla desconocida no rompe | `{plantilla: "no-existe"}` | devuelve algo válido sin lanzar excepción | ✓ |
| Custom: velocidad + tipo + servicios | T4, sS, detectar_servicios=True | args contiene `-T4 -sS -sV` | ✓ |
| Custom: puertos "all" | `puertos = "all"` | ports = `"1-65535"` | ✓ |
| Custom: puertos "top-100" | `puertos = "top-100"` | args contiene `-F` | ✓ |
| NSE filtra scripts no permitidos | `scripts_nse = ["default","vuln","malicioso","../etc/passwd"]` | args solo contiene `default,vuln` | ✓ |
| Inyección en puertos lanza ValueError | `puertos_custom = "80; rm -rf /"` | `ValueError` | ✓ |
| Evasión: decoy/spoof_mac/fragmentar/mtu | flags marcados | args contiene `-D`, `--spoof-mac`, `-f`, `--mtu` | ✓ |
| Extracción de CVEs del output NSE | scripts con texto `"CVE-2021-44228 CVE-2014-0160"` | lista `["CVE-2014-0160", "CVE-2021-44228"]` | ✓ |

### 4.3. `test_cve_lookup.py` — mapeo CVSS → severidad (lado Python)

Reflejo de la misma tabla CVSS v3.1 del lado PHP, para asegurar que ambos motores coinciden.

| Caso | Entrada | Resultado esperado | OK |
|---|---|---|---|
| CVSS `None` | `None` | `"nada"` | ✓ |
| CVSS `0.0` | `0.0` | `"nada"` | ✓ |
| Baja | `0.1`, `3.9` | `"baja"` | ✓ |
| Media | `4.0`, `6.9` | `"media"` | ✓ |
| Alta | `7.0`, `8.9` | `"alta"` | ✓ |
| Crítica | `9.0`, `10.0` | `"critica"` | ✓ |

### 4.4. `test_main.py` — endpoint HTTP

| Caso | Entrada | Resultado esperado | OK |
|---|---|---|---|
| `/health` no requiere autenticación | `GET /health` | HTTP 200, body `{"status": "ok"}` | ✓ |
| `POST /scan/start` sin token | sin header Authorization | HTTP 401 | ✓ |
| `POST /scan/start` con token correcto | body válido + `Bearer <TOKEN>` | HTTP 202, body `{accepted: true, escaneo_id: 999}` | ✓ |

---

## 5. Cobertura

La batería actual cubre:

- **Modelo de dominio (Eloquent):** lógica derivable de severidad, helpers de Usuario y Escaneo.
- **Autenticación y autorización:** flujo de login completo y middleware de roles.
- **Endpoint interno FastAPI ↔ Laravel:** integridad del token compartido.
- **Construcción segura del comando nmap:** inyección, allow-lists, plantillas, evasión.
- **Mapeo CVSS:** mismo resultado en PHP y Python (evita inconsistencias entre el motor y la app).
- **API HTTP del motor:** salud, autenticación y aceptación de escaneos.

Lo que **no** se cubre con pruebas automatizadas y se ha probado manualmente:

- Generación real del PDF (depende de fuentes y dompdf, no es práctico testearlo con tests unitarios).
- Resultado real de nmap contra un host (depende del entorno de red).
- Render visual de las vistas Blade (se cubre con tests Feature que verifican el HTTP 200 pero no comparan markup).

Estas pruebas manuales se han realizado durante el desarrollo lanzando escaneos contra el contenedor `target-vulnerable` (DVWA) incluido en el `docker-compose.yml`.
