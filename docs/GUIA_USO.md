# BetterScan — Guía de funcionamiento por perfiles

> Manual de uso de la aplicación para los tres perfiles definidos en la fase de diseño: **Administrador**, **Empleado (auditor)** y **Cliente**.

---

## Usuarios de prueba incluidos en el seed

Estos usuarios se crean automáticamente la primera vez que se ejecutan los seeders (durante el arranque del contenedor `web`). Sirven para poder entrar a probar cada rol sin tener que registrar nada.

| Email | Contraseña | Rol | Empresa asociada |
|---|---|---|---|
| `admin@betterscan.syncbetter.es` | `Admin1234!` | Administrador | — |
| `auditor@betterscan.syncbetter.es` | `Audit1234!` | Empleado | — |
| `auditor2@betterscan.syncbetter.es` | `Audit1234!` | Empleado | — |
| `cliente@acmecorp.com` | `Client1234!` | Cliente | ACME Corp |
| `cliente@retailmax.es` | `Client1234!` | Cliente | RetailMax SL |

Todas las contraseñas se almacenan en la base de datos con **bcrypt** (función `Hash::make()` de Laravel), nunca en texto plano. Cumple el requisito **RNF-03** del diseño y el RGPD.

Las credenciales del despliegue público (AWS + Cloudflare Tunnel en `https://betterscan.syncbetter.es`) se comparten aparte en el archivo `credenciales_seguridad_vps.txt`, distribuido al tribunal junto al ZIP. Las contraseñas de esa instancia son distintas a las de la tabla de arriba (que son las del seed para arranque local).

---

## Acceso a la aplicación

URL: `https://localhost` (en local) o el dominio configurado en `APP_URL` (en producción).

El proceso de login:

1. Introduce email y contraseña.
2. La aplicación valida los datos contra la tabla `usuario`.
3. Si son correctos, Laravel inicia sesión y redirige:
   - **Administrador** y **Empleado** → `/dashboard` (panel principal).
   - **Cliente** → `/portal` (portal de solo lectura).
4. Si fallan, se muestra un mensaje genérico (`Las credenciales no son válidas`) que no indica si el problema es el email o la contraseña, para no dar pistas a un atacante.

> El formulario tiene rate-limit: 5 intentos por minuto. Al sexto intento fallido el sistema devuelve un 429. Está protegido por ataques de fuerza bruta pero no por predicción de contraseñas, por eso en el archivo `credenciales_seguridad_vps.txt` (las del despliegue público) las contraseñas son de 20 caracteres con mayúsculas, minúsculas, números y símbolos generadas automáticamente por mi gestor de contraseñas. 

> **Timeout de sesión.** La sesión expira tras **120 minutos sin actividad** (`SESSION_LIFETIME=120` en `laravel/.env`). Es timeout por inactividad: cada request refresca el reloj. Si pasas dos horas sin hacer click, la siguiente página le lleva a `/login` y vuelves a entrar — Laravel guarda la URL a la que ibas y te redirige ahí después del login. Las cookies son `Secure` + `HttpOnly` + `SameSite=lax` para que la sesión no se pueda filtrar ni por HTTP plano ni a JavaScript desde un XSS hipotético.

---

## 1. Perfil Administrador

**Casos de uso del diseño:** UC-01, UC-03, UC-04, UC-05, UC-06.

El administrador es el único rol con acceso al BackOffice de la aplicación. Sus opciones del menú lateral son:

- **Dashboard:** KPIs globales (proyectos, escaneos activos, vulnerabilidades por severidad). Lista de últimos proyectos y escaneos.
- **Proyectos:** ver, crear, editar y eliminar cualquier proyecto. Lo mismo que un empleado.
- **Escaneos:** listado de todos los escaneos en ejecución y completados, con su estado y progreso.
- **Usuarios y roles** *(exclusivo del admin)*: alta, baja y modificación de usuarios. Al asignar rol cliente, además hay que asociarlo a una empresa.
- **Clientes** *(exclusivo del admin)*: alta, baja y modificación de empresas cliente. Datos: nombre comercial, razón social, CIF, sector, responsable y email de contacto.
- **Ajustes:** perfil propio, cambio de contraseña y, además, una sección con la URL del motor FastAPI y el estado del sistema (versión PHP, versión MariaDB, número de sesiones activas...).

### Flujo típico de administración

1. Entra al BackOffice de Clientes.
2. Crea una empresa nueva ("ACME Corp", CIF, sector "Servicios").
3. Va a Usuarios, crea un usuario con rol Empleado.
4. Crea otro usuario con rol Cliente, asociado a la empresa que acaba de crear.
5. Ese cliente ya puede entrar y verá su empresa en el portal.

---

## 2. Perfil Empleado (Auditor)

**Casos de uso del diseño:** UC-01, UC-06, UC-07, UC-08, UC-09, UC-10, UC-11.

El empleado es la figura central de la aplicación: lanza escaneos y genera informes.

### Menú lateral

- **Dashboard:** mismo que el administrador.
- **Proyectos:** ve **todos los proyectos del sistema** para poder colaborar con el equipo. Solo puede editar/eliminar los proyectos donde figura como **auditor responsable**.
- **Escaneos:** ve todos los del equipo. Puede lanzar escaneos en cualquier proyecto, no solo en los suyos.
- **Ajustes:** perfil y contraseña.

No ve ni Usuarios ni Clientes.

> **Modelo de equipo.** BetterScan asume que un proyecto es trabajo de un equipo de auditores: el responsable lo configura y lo lleva, pero cualquier compañero puede entrar a lanzar escaneos contra los objetivos y generar informes. Por eso en el PDF se distinguen dos roles: el **auditor responsable** (en la ficha del proyecto, fijo) y el **emisor** del informe (el auditor que pulsó "Generar PDF" en esa exportación concreta). La gestión de metadata del proyecto (cambiar empresa, alcance, fecha, eliminarlo entero) sí queda restringida al responsable + admin.

### Crear un proyecto

`+ Nuevo proyecto` desde el listado. Datos:

- **Nombre** del proyecto.
- **Empresa cliente** asociada (selector con las empresas ya creadas por el admin).
- **Tipo de auditoría:** red, web, mixta.
- **Alcance de red:** rangos CIDR o IPs separadas por comas (`192.168.1.0/24, 10.0.0.5`).
- **Excepciones:** hosts a excluir.
- **Visibilidad:** *interna* (solo auditores) o *cliente* (el cliente también la ve en su portal).
- **Fecha límite estimada.**

### Lanzar un escaneo (asistente de 4 pasos)

Desde el detalle del proyecto, `+ Nuevo Escaneo` abre el asistente:

1. **Información:** nombre del escaneo, descripción, objetivo (precargado desde el proyecto), excepciones.
2. **Plantilla:** elige una de las 8 plantillas predefinidas (o "Personalizado" para abrir el paso 3).
   - *Descubrimiento de hosts* — `nmap -sn -PE -PP -PS22,80,443 -PA80 -T4`
   - *Escaneo rápido* — `nmap -T4 -F --open -Pn`
   - *Escaneo completo* — `nmap -p 1-65535 -T4 -sS --open`
   - *Detección de servicios* — `nmap -sV -sC -T4 --open`
   - *Análisis de vulnerabilidades* — `nmap -sV --script vuln -T4 --open`
   - *Agresivo* — `nmap -A -T4 --open`
   - *Auditoría web* — nmap puertos HTTP + Gobuster `dir`
   - *Personalizado* — desbloquea todas las opciones de nmap.
3. **Configuración avanzada** *(solo si plantilla = Personalizado)*: timing T0-T5, tipo de escaneo (-sS/-sT/-sU…), descubrimiento (-Pn/-sn/-PE…), puertos, scripts NSE, evasión (señuelos, spoof IP/MAC, fragmentación, MTU, badsum…) y exportación (-oX/-oN/-oG).
4. **Revisar:** resumen legible + equivalente como línea de comando + botón **Lanzar escaneo**.

Al pulsar Lanzar la aplicación envía la orden al motor FastAPI y redirige a la pantalla de progreso.

### Seguir el progreso

La pantalla "Escaneo en curso" muestra una barra de progreso y la fase actual. Refresca cada 2 segundos mediante peticiones AJAX (fetch JSON) a `/escaneos/{id}/estado.json`. Esto cumple **RNF-04** (sin recargar la página).

Cuando el motor termina, la pantalla redirige automáticamente a los resultados.

### Consultar resultados

Los resultados se navegan en tres niveles jerárquicos para reflejar cómo trabaja un auditor real (primero el panorama, luego el host, luego el detalle):

1. **Vista resultados (`/escaneos/{id}/resultados`)**: KPIs globales por severidad + tabla **Activos descubiertos** con IP, **MAC**, hostname, sistema operativo, puertos abiertos y conteo de vulnerabilidades por severidad en forma de badges. Si el escaneo descubre 5 hosts, aparecen 5 filas.
2. **Detalle de activo (`/escaneos/{id}/activos/{id}`)**: ficha del host (IP, MAC, hostname, SO, red) + KPIs del propio activo + tabla **Puertos y servicios** (con estado open/closed/filtered) + tabla **Vulnerabilidades del activo**.
3. **Detalle de vulnerabilidad (`/vulnerabilidades/{id}`)**: ficha técnica con CVE, puntuación CVSS, vector, descripción enriquecida desde NVD/MITRE, remediación y referencias.

Adicionalmente: **descarga de archivos exportados** (XML, normal, grepeable, salida cruda de gobuster) desde la vista de resultados si los marcó en la configuración del escaneo.

### Generar un informe PDF

Desde el detalle del proyecto, `Generar informe` abra la pantalla de exportación:

- Seleccione el tipo: *Ejecutivo*, *Técnico* o *Completo*.
- Previsualización con conteo de activos y vulnerabilidades.
- Pulse **Generar PDF** → se descarga directamente y queda guardado en el historial del proyecto.

**Estructura del documento generado**:

- **Ejecutivo**: cabecera + metadatos del proyecto + resumen ejecutivo con KPIs por severidad. Pensado para perfil directivo.
- **Técnico**: lo anterior + **una sección por cada activo descubierto** (con IP, **MAC**, hostname, SO y red), seguida de una tabla de puertos del activo y, por cada puerto que tenga vulnerabilidades, una tabla específica con sus CVEs. La estructura refleja la relación **Activo → Puerto → Vulnerabilidad** de manera explícita: el lector ve qué CVEs cuelgan exactamente de qué puerto.
- **Completo**: lo anterior + un anexo final con una *card* extendida por cada vulnerabilidad (descripción completa, vector CVSS y remediación recomendada cuando está disponible).

### Eliminar un informe ya generado

Si el auditor se equivoca al generar (eligió mal el tipo, lo emitió antes de tiempo, o quiere reemplazarlo por una versión corregida), puede borrarlo desde la pestaña **Informes** del proyecto:

1. En la fila del informe, pulse **Eliminar** (botón rojo, al lado de "Descargar PDF").
2. Confirme el aviso. La aplicación borra el registro de la tabla `informe` Y el fichero físico del filesystem.
3. **Efecto inmediato**: el informe deja de aparecer en el portal del cliente. Ya no podrá descargarlo.
4. El auditor puede volver a `Generar informe` y emitir uno nuevo. Esta vez sí lo verá el cliente.

Reglas de autorización: el administrador puede eliminar cualquier informe; el empleado solo los de proyectos donde figura como auditor. Si pulsa Eliminar en un informe ajeno → **403 Forbidden**.

---

## 3. Perfil Cliente

**Casos de uso del diseño:** UC-01, UC-11, UC-12.

El cliente solo puede:

- Iniciar y cerrar sesión.
- Ver el listado de proyectos de **su empresa** que estén marcados como *visibilidad = cliente*.
- Descargar los informes asociados a esos proyectos.

No ve los escaneos en bruto, no puede lanzar nada, no entra al dashboard ni al BackOffice. El menú lateral está reducido a "Inicio" y "Mis informes". **No tiene buscador global**: el campo del topbar se oculta para clientes y la ruta `/buscar` solo es accesible para admin/empleado.

Si por curiosidad pega manualmente la URL `/usuarios` o `/buscar?q=...` en el navegador, la app responde **403 Forbidden** gracias al middleware `EnsureRole`. La única superficie del portal son `/portal`, `/portal/proyectos/{id}` y `/portal/informes/{id}`, todas con guard `esDeSuEmpresa && esVisible` en `PortalClienteController`.

---

## Matriz de permisos resumida

| Vista | Admin | Empleado | Cliente |
|---|:---:|:---:|:---:|
| `/login` | ✓ | ✓ | ✓ |
| `/dashboard` | ✓ | ✓ | redirige a `/portal` |
| `/proyectos` (listado) | ✓ (todos) | ✓ (todos los del equipo) | ✗ |
| Crear proyecto | ✓ | ✓ | ✗ |
| Ver detalle de proyecto | ✓ | ✓ (cualquiera) | ✗ |
| Editar / eliminar proyecto | ✓ | ✓ (solo si es responsable) | ✗ |
| Lanzar escaneo | ✓ | ✓ (en cualquier proyecto) | ✗ |
| Eliminar escaneo | ✓ | ✓ (solo los que él lanzó) | ✗ |
| Ver resultados | ✓ | ✓ (en cualquier proyecto) | ✗ |
| Generar informe PDF | ✓ | ✓ (en cualquier proyecto) | ✗ |
| Eliminar informe PDF | ✓ | ✓ (responsable del proyecto o emisor del PDF) | ✗ |
| `/usuarios` (CRUD) | ✓ | ✗ | ✗ |
| `/clientes` (CRUD empresas) | ✓ | ✗ | ✗ |
| `/ajustes` | ✓ | ✓ | ✗ |
| `/portal` | redirigido aquí | redirigido aquí | ✓ |
| Descargar informe propio | ✓ | ✓ | ✓ (solo si es de su empresa) |
| `/buscar?q=…` (buscador global) | ✓ | ✓ | ✗ (403, no enumera) |

> **Autorización por responsable** (empleados). Cualquier empleado puede ver el listado, entrar al detalle y operar (escaneos, resultados, informes) sobre cualquier proyecto. La **gestión de metadata del proyecto** (`edit`, `update`, `destroy`) sí está restringida al **auditor responsable** del proyecto + admin. Si un empleado pega manualmente la URL `/proyectos/123/edit` de un proyecto que no dirige, la app responde **403 Forbidden**. La comprobación vive en `ProyectoController::asegurarPropietario()`. El administrador queda exento de esta regla.
>
> **Autorización por lanzador** (escaneos). El borrado de escaneos también está restringido: cualquier empleado puede lanzar, ver y editar escaneos pendientes en cualquier proyecto del equipo, pero **solo el empleado que lanzó el escaneo puede eliminarlo**. La columna `escaneo.lanzado_por` (FK a `usuario`) se rellena automáticamente con `Auth::id()` al ejecutar `EscaneoController::store()`, y el guard `asegurarPropietarioEscaneo()` valida la propiedad antes de borrar. Admin queda exento. El botón "Eliminar" se esconde en la vista cuando el empleado no es el lanzador, pero el servidor sigue verificando con 403.
>
> **Autorización en tres caminos** (informes). El borrado de informes admite **tres rutas**: admin (siempre), **auditor responsable** del proyecto al que pertenece el informe, o **emisor** que pulsó "Generar PDF" (`informe.emitido_por`). Cualquier otro empleado del equipo recibe 403. Lanzar y descargar informes siguen siendo libres para todo el equipo — solo el borrado se restringe para que un compañero no tire por error el trabajo de otro. El guard vive en `InformeController::asegurarPuedeBorrarInforme()`, la UI esconde el botón "Eliminar" cuando ninguna de las tres condiciones se cumple.
>
> **UX coherente.** La vista de listado y la de detalle esconden los botones "Editar" y "Eliminar" cuando el usuario no es el responsable. El servidor sigue verificando vía 403 — la ocultación en la vista es solo para no enseñar acciones que van a fallar.
>
> **Por qué este modelo de equipo.** Un proyecto es trabajo colaborativo: el responsable lo configura y lo lleva, pero cualquier compañero del equipo puede entrar a lanzar escaneos y generar/eliminar informes. Esto justifica que en el PDF aparezcan dos roles distintos — el **auditor responsable** (fijo en la ficha del proyecto) y el **emisor del informe** (el auditor que pulsó "Generar PDF" en esa exportación concreta). Sin modelo de equipo esos dos campos serían siempre la misma persona.

---

## Notas para la defensa

- **Persistencia de los datos demo.** El seed crea un par de proyectos con escaneos completados y vulnerabilidades reales (CVEs presentes en NVD), de modo que entrando como admin o auditor ya hay datos para enseñar sin tener que esperar a lanzar un escaneo desde cero.
- **Escaneo en vivo.** El seed también deja un escaneo en estado *pendiente* para que se pueda relanzar durante la defensa y ver el polling refrescar.
- **Objetivo de pruebas seguro.** El `docker-compose.yml` incluye el contenedor DVWA con hostname interno `target-vulnerable`. Es accesible solo desde dentro de la red de Docker, así que se puede escanear sin riesgo.
- **Cómo cerrar sesión.** Botón "Salir" en la parte inferior del menú lateral.
