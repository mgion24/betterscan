# BetterScan — Cuestionario final (Tarea 3)

> **Autor:** Marian Georgian Ion
> **Ciclo:** Desarrollo de Aplicaciones Web (DAW)
> **Tarea 3 — Junio 2026**

Respuestas a las cuatro preguntas obligatorias del enunciado de la Tarea 3.

---

## 1. ¿Ha podido cumplir la planificación? ¿Qué problemas ha tenido?

**Sí, se ha cumplido la planificación general aunque con desviaciones en los tiempos parciales.**

La planificación inicial entregada en la Tarea 2 (diagrama de Gantt de 8 semanas) se ha respetado en sus hitos principales: análisis y diseño en abril; setup Laravel + migraciones y motor Python en mayo; integración, pruebas y documentación en las últimas dos semanas de mayo y la primera de junio. El proyecto se ha entregado dentro del plazo de primera semana de junio de 2026.

Los problemas que han aparecido durante el desarrollo y cómo se han resuelto:

- **Empecé con nginx + php-fpm y acabé en Apache.** Al releer el enunciado de la Tarea 3 vi que pedía Apache 2 expresamente. Rehice el contenedor con la imagen `php:8.4-apache`. Me llevó una tarde y de paso me quedé con un contenedor menos.
- **El motor lanzaba nmap cinco veces por escaneo.** En la primera versión separaba descubrimiento, puertos, servicios, OS y vulns en cinco invocaciones encadenadas. No tenía sentido y era lentísimo de depurar. Lo refactoricé a una sola llamada a nmap por plantilla, que es como lo haría un auditor en su terminal.
- **Tests con SQLite y los `ENUM` de MariaDB.** Para los tests Feature uso SQLite en memoria porque es instantáneo. Algunas columnas (`estado`, `severidad`, `protocolo`) son `ENUM` en MariaDB y SQLite no los tiene; Laravel los traduce a `CHECK`, pero tuve que ajustar las pruebas para no asumir el motor de BD.
- **Cancelar un escaneo en curso no funciona del todo (limitación conocida).** Si borro un escaneo desde la interfaz mientras corre, la fila desaparece de la BD pero el proceso `nmap` del motor sigue hasta terminar y luego intenta su callback contra un escaneo que ya no existe (404, sin daño, pero gastando CPU). Para arreglarlo de verdad haría falta un endpoint `/scan/{id}/cancel` en FastAPI, sustituir `python-nmap` por `subprocess` para poder `terminate()` y llamarlo desde `EscaneoController::destroy()`. Lo dejo como mejora futura porque se salía del alcance del TFG.
- **CORS no me ha dado problemas** porque la arquitectura lo evita por diseño: el navegador solo habla con Laravel (mismo origen). Cuando necesitaba datos del motor en el cliente hice un proxy en Laravel (`interfacesRed()`). Así no tengo que configurar `Access-Control-Allow-Origin` en FastAPI, evito mixed content y mantengo el motor sin puertos publicados al host.

A lo anterior, que es lo más "limpio" para contar, hay que sumar los problemas que de verdad me han hecho perder más horas. Los pongo tal cual los viví:

- **Aprender FastAPI y Python a la vez que lo usaba, además de Laravel.** El ciclo de DAW se centra en PHP vanilla y en Java. Con el plazo encima decidí apoyarme bastante en asistencia de IA y en la documentación oficial de FastAPI para acelerar la implementación del motor, mientras me lo iba estudiando línea a línea. La parte web es la que mejor se me da. El motor lo entiendo entero porque me lo he releído varias veces para preparar la defensa, pero no lo escribí "a pelo": sé qué hace cada archivo, qué argumentos monta, cómo enriquece los CVEs y por qué uso `secrets.compare_digest`, pero reconozco que no podría reescribirlo de cero sin material de apoyo en este momento. Lo veo como un compromiso honesto entre alcance y tiempo. Lo mismo pasó con Laravel: fue todo un reto aprenderlo viniendo de una base de PHP vanilla, pero era lo que tenía que usar para conseguir realizar este proyecto.

- **Hacer hablar a Laravel con FastAPI fue lo más complicado del proyecto.** Pinta sencillo en el diagrama: una petición HTTP con un Bearer y otra de vuelta. En la práctica me llevó días estabilizarlo. Los síntomas iniciales:
  - El motor devolvía 401 a la primera llamada de Laravel porque cada contenedor tenía su propio `INTERNAL_TOKEN` en su `.env`. Solucionado moviéndolo al `.env` raíz y cargándolo en ambos con `env_file:`.
  - Al revés, el callback del motor a Laravel daba `Connection refused` o `Name or service not known` porque yo intentaba llamar a `http://localhost`, que dentro del contenedor del motor es el propio motor, no Laravel. Hasta que recordé que Docker Compose resuelve cada servicio por su nombre dentro de la red interna, y configuré `LARAVEL_BASE_URL=http://web` (el nombre del servicio Apache en el `docker-compose.yml`).
  - Cuando ya hablaba, el callback fallaba por el certificado autofirmado de Apache. Acabé poniendo `verify=False` en `httpx` (justificado porque la autenticación va por el Bearer y todo viaja por la red interna).
  - Y el último: las llamadas internas dejaban de funcionar nada más activar el middleware CSRF a nivel global, porque el motor obviamente no manda token CSRF. La solución fue añadir la excepción `api/internal/*` en `bootstrap/app.php`.

- **419 Page Expired en las primeras llamadas AJAX.** La primera versión del polling y del botón "Detectar mi red" fallaba con 419 a la primera petición y yo no entendía por qué. Lo entendí cuando vi que Laravel exige el header `X-CSRF-TOKEN` también en peticiones JS. La solución, en este caso, fue pintar el token en una etiqueta `<meta name="csrf-token">` en el layout y leerlo desde el helper `peticionJson()` de `app.js`. Es una de esas cosas que cuando ya la sabes parece evidente, pero la primera vez te bloquea media tarde.

- **Las migraciones se rompían entre ellas.** Al principio no tenía claro que Laravel ejecuta las migraciones por orden alfabético del nombre del archivo. Como las creé en el orden en que se me iban ocurriendo, la de `usuario` se ejecutaba antes que la de `rol` y la de `empresa`, y la FK petaba con un error de "table rol does not exist". Tuve que renombrar todos los ficheros con prefijos de timestamp coherentes y dejar claro el orden: primero `rol` y `empresa`, después `usuario`, después `proyecto`, y así sucesivamente.

- **El `StoreEscaneoRequest` con 30+ campos opcionales con reglas cruzadas.** El asistente de 4 pasos tiene muchos campos que solo aplican según la plantilla elegida o según valores de otros campos (los puertos custom solo si `puertos == 'custom'`, los flags de gobuster solo si el checkbox está marcado, etc.). Pasé un buen rato peleando con `nullable`, `required_if` y devoluciones 422 sin saber exactamente qué campo estaba fallando, hasta acostumbrarme a leer bien la respuesta JSON de Laravel.

- **El escaneo se "quedaba pillado" al 90 %.** Durante las pruebas con CVEs reales pensaba que el motor se había muerto: el polling se quedaba clavado en "Enriqueciendo CVEs" durante un par de minutos sin avanzar. Lo trazé mirando los logs del contenedor FastAPI y lo que pasaba era que sin API key de NVD el rate limit es 5 peticiones / 30 segundos y yo estaba consultando 8 o 10 CVEs seguidos. Lo dejé con `await asyncio.sleep(6.0)` sin API key (cumple el rate limit con margen) y bajo a `0.7` cuando `NVD_API_KEY` está configurada (42 req/30 s aproximadamente, por debajo del límite de 50 que da la key). La key se solicita gratuita en `https://nvd.nist.gov/developers/request-an-api-key` y se mete en `.env`.

- **`APP_DEBUG=false` me ocultó errores en una prueba.** Estaba con la integración del callback y en algún momento puse `APP_DEBUG=false` por costumbre. Cuando algo fallaba el navegador me devolvía un 500 genérico sin pista de qué pasaba. Tardé en darme cuenta de que tenía que mirar `storage/logs/laravel.log` para ver el stack trace, y de que para defender en clase me convenía dejarlo en `true` durante el desarrollo (y en `false` justo antes de entregar).

En resumen, los desvíos han sido técnicos y han venido a mejorar el resultado. No ha habido retrasos en la fecha final de entrega.

---

## 2. ¿Su proyecto necesita algún tipo de permiso o autorización administrativa?

**Sí. BetterScan es una herramienta de auditoría de seguridad y, como tal, su uso real (no simulado) está sujeto a tres tipos de autorización:**

### Autorización del propietario del sistema auditado

Lanzar un escaneo de nmap contra una red o servidor sin permiso del titular puede constituir un delito tipificado en el **artículo 197 bis del Código Penal español** (acceso ilícito a sistemas de información) y/o el **artículo 264 ter** (interferencia ilegal en sistemas), con penas de hasta tres años de prisión.

Por eso BetterScan se diseñó como herramienta para auditores profesionales que firman un contrato previo con el cliente. En la propuesta inicial de la Tarea 1 ya se contemplaba el modelo de negocio B2B con contratos de auditoría. La aplicación incluye un campo "Visibilidad" en los proyectos y un sistema de roles para que el cliente solo vea lo que el auditor decida.

### Cumplimiento del RGPD y LOPDGDD

La aplicación almacena datos de redes de empresas terceras (IPs, hostnames, vulnerabilidades detectadas, datos de contacto del responsable). Esto la convierte en **encargada del tratamiento** de datos personales bajo el Reglamento (UE) 2016/679 y la Ley Orgánica 3/2018. Las medidas adoptadas en el código:

- Contraseñas con bcrypt (`Hash::make()`), nunca en texto plano.
- HTTPS obligatorio en todas las rutas (redirección 80 → 443 en Apache).
- Borrado en cascada de datos de un cliente cuando se elimina la empresa.
- Audit trail mínimo: el `informe.emitido_por` guarda quién generó cada informe.

En un despliegue real habría que añadir:

- Registro de actividades de tratamiento.
- Contrato de encargo del tratamiento con cada cliente.
- Cifrado en reposo del volumen de MariaDB.
- Servidores ubicados en territorio europeo.

### Marco normativo del servicio en internet

Si la aplicación se publica como SaaS:

- **LSSI-CE** (Ley 34/2002): aviso legal, política de privacidad y política de cookies obligatorios.
- **LOPDGDD**: registro de actividades de tratamiento ante la AEPD si fuera necesario.
- **Cumplimiento ENS** si se va a ofrecer a administraciones públicas.

---

## 3. ¿Ha establecido algún documento de prevención de riesgos laborales?

**Sí. Aunque el proyecto es de desarrollo software realizado por una sola persona y los riesgos son limitados, se ha hecho una evaluación basada en la Ley 31/1995 de Prevención de Riesgos Laborales aplicable a puestos de trabajo con pantallas de visualización de datos (PVD).**

### Riesgos identificados durante el desarrollo

| Riesgo | Origen | Medida adoptada |
|---|---|---|
| Fatiga visual | Uso continuado de monitor para programar y leer documentación. | Pausas de 5 minutos cada 50 trabajados (técnica Pomodoro). Iluminación natural durante el día y luz cálida por la noche. |
| Problemas musculoesqueléticos | Postura mantenida frente al ordenador. | Silla con respaldo regulable, monitor a la altura de los ojos, teclado y ratón a la altura de los codos. |
| Estrés por plazos | Entrega del TFG con fecha límite. | Planificación realista entregada en la Tarea 2 (Gantt de 8 semanas). Reparto del trabajo en hitos semanales. |
| Sedentarismo | Trabajo intelectual sentado muchas horas. | Pausas activas y caminatas cortas entre bloques de programación. |

### Riesgos específicos del producto (auditoría de seguridad)

En el caso de que la aplicación se utilice en producción para auditorías reales, el principal riesgo *operacional* sería un escaneo descontrolado que afecte a la disponibilidad del servicio auditado (DoS accidental). Medidas adoptadas en el código:

- El campo "Excluir" permite descartar hosts críticos del escaneo.
- Las plantillas predefinidas no incluyen tipos de escaneo que puedan colgar servicios (no se usa `-sN`/`-sX` salvo en personalizado).
- Las plantillas con scripts intrusivos (`vuln`, `exploit`, `brute`) están marcadas en la UI con la indicación "Úsalos sólo con autorización escrita".

### Marco normativo aplicable

- **Ley 31/1995 de Prevención de Riesgos Laborales.**
- **Real Decreto 488/1997 sobre disposiciones mínimas de seguridad para trabajo con PVD.**
- **Real Decreto 39/1997 del Reglamento de los Servicios de Prevención** (relevante en caso de contratar empleados en el futuro modelo SaaS).

---

## 4. Valoración económica respecto a su ejecución

Se mantiene el presupuesto entregado en la Tarea 2, actualizado con los costes reales del desarrollo del TFG.

### Coste de desarrollo (Tarea 3)

| Concepto | Coste estimado |
|---|---|
| Dedicación del desarrollador (200 h × 10 €/h estimado para junior) | 2.000 € |
| Equipamiento (portátil propio, ya amortizado) | 0 € |
| Software (todo Open Source: PHP, Laravel, Python, MariaDB, Docker, Apache, nmap, gobuster) | 0 € |
| Documentación (PDFs hechos con Obsidian, lenguaje MarkDown con exportación a PDF) | 0 € |
| **Subtotal desarrollo** | **2.000 €** |

### Coste de despliegue en producción (anual)

El despliegue del TFG se ha realizado en **AWS EC2 en la región `eu-south-2` (Zaragoza)** con un Cloudflare Tunnel delante. La elección frente a un VPS plano (Hetzner, OVH, etc.) responde a tres motivos: latencia óptima desde España (datacenter en Aragón), integración con Cloudflare Tunnel para no exponer ningún puerto inbound de la instancia a internet (defensa en profundidad), y posibilidad de aplicar un *Savings Plan* a 1 año que reduce el coste de la instancia un 30 % aproximadamente frente a la tarifa on-demand.

| Concepto | Coste |
|---|---|
| AWS EC2 `t3.medium` (eu-south-2, Zaragoza) — 2 vCPU / 4 GB RAM con Savings Plan 1 año | 280 €/año (≈23 €/mes) |
| Dominio `.es` | 15 €/año |
| Cloudflare Tunnel + DNS (plan Free) | 0 €/año |
| Certificado HTTPS (Cloudflare Origin Cert, vigencia 15 años) | 0 €/año |
| Backup en AWS S3 Glacier Deep Archive (~10 GB efectivos comprimidos) | 6 €/año |
| Mantenimiento (10 h/mes de actualizaciones y soporte, a 10 €/h) | 1.200 €/año |
| **Subtotal anual** | **1.501 €/año** |

> La instancia se apaga fuera de la ventana de defensa para no incurrir en costes innecesarios — la facturación de AWS es por hora real de uso. En un despliegue comercial sostenido la cifra de EC2 se mantiene; en un MVP en pre-lanzamiento se puede dejar apagada salvo durante demos.

### Coste total primer año

| Concepto | Importe |
|---|---|
| Desarrollo | 2.000 € |
| Infraestructura y mantenimiento (año 1) | 1.501 € |
| **TOTAL año 1** | **3.501 €** |

### Financiación

Para el TFG se autofinancian todos los costes con recursos propios. En caso de seguir adelante con el producto como SaaS:

- **Kit Digital (Red.es)** como agente digitalizador: ingresos por dar servicio a pymes.
- **Andalucía Emprende**: subvenciones a fondo perdido para empresas tecnológicas en Andalucía.
- **ENISA**: préstamos a tipo bajo para proyectos innovadores.
- **Pago Único del Desempleo**: capitalización del paro para invertir en equipamiento y servidores.

### Comparativa con la competencia

| Producto | Coste licencia/año | Mercado objetivo |
|---|---|---|
| Tenable Nessus Professional | aprox 4.000 €/año | Empresas medianas/grandes. |
| Greenbone Enterprise (OpenVAS) | desde 2.500 €/año | Administración pública. |
| Tenable.io (multi-tenant) | desde 10.000 €/año | Consultoras de auditoría grandes. |
| **BetterScan (propuesta SaaS)** | desde 1000 €/año (estimado) | **Pymes que auditan a pymes** |

El nicho que cubre BetterScan está justificado en el documento "Informe de Plan de Empresa y Viabilidad" entregado en la Tarea 1.

### Posicionamiento técnico frente a OpenVAS y Nessus

Conviene ser honesto sobre qué ofrece BetterScan y qué no, porque las herramientas establecidas llevan dos décadas de ventaja en cobertura técnica:

**Lo que OpenVAS y Nessus tienen que BetterScan no:**

- Cobertura de vulnerabilidades a escala industrial: OpenVAS tiene **aproximadamente 150.000 NVTs** (Network Vulnerability Tests) mantenidos por GreenBone, Nessus tiene **más de 200.000 plugins** actualizados por Tenable. BetterScan se apoya en NVD/MITRE/CISA y enriquece por CVE asociado, pero no tiene el motor de matching CPE-a-producto que esas herramientas llevan décadas afinando.
- **Authenticated scans** (login por SSH/SNMP/WMI) que comprueban configuraciones reales del sistema, no solo lo que se ve desde fuera.
- **Compliance scanning** integrado: PCI-DSS, HIPAA, ISO 27001, NIST 800-53, CIS Benchmarks.
- Web app scanning estilo Burp/ZAP, database scanning autenticado, hypervisor scanning, IoT/SCADA.
- **Risk scoring contextualizado** con EPSS, KEV y asset value.
- Integración nativa con SIEMs (Splunk, Sentinel, ELK), patch management, asset CMDB.

**Lo que BetterScan tiene que OpenVAS Community y Nessus Pro no:**

- **Multi-tenant desde el día 1.** OpenVAS Community y Nessus Professional son single-tenant (una instalación = un auditor). Para multi-tenant Tenable obliga a migrar a Tenable.io, que arranca en **10.000 €/año**. BetterScan modela empresas cliente, proyectos, auditores asignados como responsables, portal cliente y permisos por rol desde la primera entrega del E-R.
- **Modelo de equipo con responsable + emisor.** En el PDF de informe aparecen dos roles distintos: el auditor responsable del proyecto (fijo) y el emisor del informe (variable, el auditor que pulsó "Generar PDF"). Permite trazabilidad cruzada en una consultora pequeña de 2-3 auditores. OpenVAS y Nessus no traen este modelo sin licencia enterprise.
- **Portal cliente integrado.** El cliente final accede a su URL, ve solo los informes de su empresa, los descarga. En Nessus/OpenVAS el flujo es "el auditor exporta el PDF y se lo manda por correo al cliente". El portal hace la entrega directa.
- **Coste cero** para la consultora que arranca: solo el VPS y el dominio. La barrera de entrada al mercado de auditoría B2B baja significativamente.
- **Arquitectura modular** del motor (`fastapi-engine/scanners/`) pensada para extender. Añadir wpscan, droopescan o nuclei son aproximadamente 50 líneas siguiendo el patrón de `gobuster_dir.py`. Extender OpenVAS exige escribir scripts NASL y mandarlos upstream a GreenBone.
- **Cumplimiento RGPD friendly por defecto.** El despliegue se puede hacer en datacenter europeo (AWS Zaragoza, OVH España, Hetzner Alemania). Tenable es proveedor estadounidense, lo cual genera fricción para datos sensibles de empresas españolas.
- **Código auditable.** El cliente puede revisar el código fuente y entender qué hace. Nessus es propietario y caja negra; OpenVAS es open source pero su complejidad lo hace prácticamente impenetrable para un cliente final.

**Resumen del posicionamiento.** BetterScan no es un sustituto de OpenVAS ni de Nessus para auditorías de cumplimiento profundo en empresas grandes. Es una alternativa viable y gratuita en el nicho específico de **consultoras de ciberseguridad pequeñas que auditan a pymes**, donde el coste de licencia y la complejidad de despliegue de las herramientas establecidas dejan un hueco real. La arquitectura modular permite acercarse a la cobertura técnica de los grandes por iteración futura (wpscan, droopescan, nuclei como módulos del motor), sin pretender llegar a igualar 20 años de feed maduros.
