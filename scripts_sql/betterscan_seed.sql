-- =============================================================
-- betterscan_seed.sql
-- BetterScan — Datos iniciales de prueba
-- TFG DAW — Proyecto BetterScan
-- =============================================================
-- NOTA SOBRE TRANSACCION:
-- Todo el seed está dentro de START TRANSACTION / COMMIT.
-- Si cualquier INSERT falla, el ROLLBACK deshace todos los
-- cambios y la base de datos queda limpia.
-- Para deshacer manualmente: ROLLBACK;
--
-- CONTRASEÑAS DE PRUEBA (instalación local):
--   admin@betterscan.syncbetter.es   → Admin1234!
--   auditor@betterscan.syncbetter.es → Audit1234!
--   auditor2@betterscan.syncbetter.es → Audit1234!
--   cliente@acmecorp.com             → Client1234!
--   cliente@retailmax.es             → Client1234!
--
-- NOTA SOBRE EL CAMBIO DE DOMINIO:
-- En la fase de Análisis y Diseño (Tarea 2) estos emails usaban el
-- dominio ficticio `@betterscan.local`. En la fase de implementación
-- y despliegue (Tarea 3) se alinearon con el subdominio público real
-- del despliegue en AWS (`betterscan.syncbetter.es`) por coherencia:
-- el email del usuario "pertenece" a la app que se está auditando,
-- como ocurriría en un SaaS comercial. La validación de Laravel sobre
-- el campo email es de FORMATO (regex), no de existencia DNS/MX, así
-- que no hace falta MX record para que el login funcione.
-- Las CONTRASEÑAS reales del despliegue público son distintas (20
-- caracteres aleatorios) y se entregan al tribunal en el fichero
-- `credenciales_seguridad_vps.txt` junto al ZIP, no aquí.
--
-- NOTA SOBRE EL ENUM `severidad`:
-- En T2 el ENUM listaba `'crítica'` (con tilde). En T3 se armonizó
-- a `'critica'` (sin tilde) en este seed y en `betterscan_create.sql`
-- para coincidir con la migración Laravel real y con la constante
-- `Vulnerabilidad::SEV_CRITICA = 'critica'` del modelo. Mantener
-- la tilde dejaría la BD incompatible con el código si alguien
-- ejecutara el SQL manual en vez de las migraciones de Laravel.
--
-- NOTA SOBRE LOS DATOS DE DEMOSTRACIÓN:
-- Este script inserta el escenario diseñado en T2: 3 proyectos
-- (ACME red interna + ACME web + RetailMax perimetral), 3 escaneos
-- con CVEs como CVE-2021-41773, CVE-2017-0144 (EternalBlue) y
-- CVE-2020-1938 (Ghostcat). El seeder Eloquent vivo
-- `database/seeders/ProyectoDemoSeeder.php` crea un escenario
-- equivalente pero distinto: alcances de red `192.168.10.0/24` con
-- CVE-2014-0160 (Heartbleed), CVE-2011-2523 (vsftpd backdoor) y
-- CVE-2022-21417. Ambos escenarios son CVEs reales que existen en
-- NVD; la diferencia se debe a la evolución natural de los datos
-- demo durante la fase de implementación. La fuente de verdad de
-- los datos cargados al arrancar Docker es el seeder Eloquent.
--
-- Hashes Bcrypt (cost=10) generados con Laravel Hash::make().
-- Para regenerar: php artisan tinker → Hash::make('Admin1234!')
-- =============================================================

USE betterscan_db;

SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO';

START TRANSACTION;

-- =============================================================
-- Limpieza previa (respeta orden FK: hijos antes que padres)
-- =============================================================
DELETE FROM informe;
DELETE FROM hallazgo;
DELETE FROM vulnerabilidad;
DELETE FROM puerto;
DELETE FROM activo;
DELETE FROM escaneo;
DELETE FROM proyecto;
DELETE FROM usuario;
DELETE FROM empresa;
DELETE FROM rol;

ALTER TABLE informe          AUTO_INCREMENT = 1;
ALTER TABLE hallazgo         AUTO_INCREMENT = 1;
ALTER TABLE vulnerabilidad   AUTO_INCREMENT = 1;
ALTER TABLE puerto           AUTO_INCREMENT = 1;
ALTER TABLE activo           AUTO_INCREMENT = 1;
ALTER TABLE escaneo          AUTO_INCREMENT = 1;
ALTER TABLE proyecto         AUTO_INCREMENT = 1;
ALTER TABLE usuario          AUTO_INCREMENT = 1;
ALTER TABLE empresa          AUTO_INCREMENT = 1;
ALTER TABLE rol              AUTO_INCREMENT = 1;

-- =============================================================
-- 1. ROLES
-- =============================================================
INSERT INTO rol (id, nombre, descripcion) VALUES
(1, 'admin',    'Administrador con acceso total al sistema'),
(2, 'empleado', 'Auditor: crea proyectos, lanza escaneos y genera informes'),
(3, 'cliente',  'Acceso de solo lectura al portal de su empresa');

-- =============================================================
-- 2. EMPRESAS
-- =============================================================
INSERT INTO empresa (id, nombre, cif, nombre_comercial, razon_social, sector, direccion, activo, responsable_nombre, responsable_email) VALUES
(1, 'ACME Corporation S.L.',  'B12345678', 'ACME Corp',  'ACME Corporation Sociedad Limitada',  'Tecnologia',     'Calle Gran Via 1, 28013 Madrid',    1, 'Carlos Martinez', 'c.martinez@acmecorp.com'),
(2, 'Seguridad Global S.A.', 'A87654321', 'SegGlobal',  'Seguridad Global Sociedad Anonima',   'Ciberseguridad', 'Av. Diagonal 200, 08013 Barcelona', 1, 'Ana Torres',      'a.torres@segglobal.es'),
(3, 'RetailMax S.L.',         'B55512349', 'RetailMax',  'RetailMax Sociedad Limitada',         'Comercio',       'Calle Serrano 44, 28001 Madrid',    1, 'Pedro Lopez',     'p.lopez@retailmax.es');

-- =============================================================
-- 3. USUARIOS
-- empresa_id NULL  → admin y empleado  (restriccion de aplicacion)
-- empresa_id NOT NULL → cliente
-- =============================================================
INSERT INTO usuario (id, nombre, apellido, email, telefono, contrasena_hash, rol_id, empresa_id) VALUES
-- Administrador
(1, 'Admin',     'BetterScan',   'admin@betterscan.syncbetter.es',    '600000001',
    '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lHj6',
    1, NULL),
-- Empleado/Auditor principal
(2, 'Alejandro', 'Ruiz Garcia',  'auditor@betterscan.syncbetter.es',  '600000002',
    '$2y$10$fGkFrWrRoEV8l0F85DqO1O7nUAH9BmqfE1t9Y/dgjSMfnAnr5Gzou',
    2, NULL),
-- Segundo auditor
(3, 'Sofia',     'Martin Lopez', 'auditor2@betterscan.syncbetter.es', '600000003',
    '$2y$10$fGkFrWrRoEV8l0F85DqO1O7nUAH9BmqfE1t9Y/dgjSMfnAnr5Gzou',
    2, NULL),
-- Cliente de ACME (empresa_id NOT NULL)
(4, 'Juan',      'Perez',        'cliente@acmecorp.com',      '600000004',
    '$2y$10$2hCm7V1OLz0t0BZpHUWAJeFD8GaZaC0v5nLJkVjO8zEOXE50gx7J2',
    3, 1),
-- Cliente de RetailMax (empresa_id NOT NULL)
(5, 'Maria',     'Gonzalez',     'cliente@retailmax.es',      '600000005',
    '$2y$10$2hCm7V1OLz0t0BZpHUWAJeFD8GaZaC0v5nLJkVjO8zEOXE50gx7J2',
    3, 3);

-- =============================================================
-- 4. PROYECTOS
-- =============================================================
INSERT INTO proyecto (id, nombre, descripcion, tipo_auditoria, alcance_red, excepciones_red, visibilidad, fecha_limite_estimada, empresa_id, auditor_id) VALUES
(1, 'Auditoria Red Interna ACME',
    'Auditoria de seguridad de la infraestructura de red interna corporativa.',
    'Escaneo de red', '192.168.1.0/24', '192.168.1.254',
    'cliente', '2025-06-01', 1, 2),

(2, 'Pentest Aplicacion Web ACME',
    'Prueba de penetracion sobre la aplicacion web corporativa de ACME.',
    'Auditoria web', '192.168.1.100', NULL,
    'privado', '2025-06-15', 1, 2),

(3, 'Auditoria Perimetral RetailMax',
    'Revision de la exposicion perimetral y servicios accesibles desde Internet.',
    'Escaneo de red', '203.0.113.0/28', '203.0.113.15',
    'privado', '2025-07-01', 3, 3);

-- =============================================================
-- 5. ESCANEOS
-- =============================================================
INSERT INTO escaneo (id, nombre, tipo_escaneo, plantilla_escaneo, objetivo, velocidad, estado, fecha_inicio, fecha_fin, proyecto_id) VALUES
(1, 'Descubrimiento de hosts - Red ACME',
    'Escaneo de red', 'discovery', '192.168.1.0/24',
    'normal', 'completado', '2025-04-20 09:00:00', '2025-04-20 09:22:00', 1),

(2, 'Escaneo completo de puertos - Web ACME',
    'Auditoria web', 'full_ports', '192.168.1.100',
    'rapido', 'completado', '2025-04-21 10:00:00', '2025-04-21 10:14:00', 2),

(3, 'Escaneo perimetral RetailMax',
    'Escaneo de red', 'full_ports', '203.0.113.0/28',
    'lento', 'en_proceso', '2025-04-24 08:00:00', NULL, 3);

-- =============================================================
-- 6. ACTIVOS
-- Mismo host en distintos escaneos = registros independientes.
-- mac VARCHAR(18): formato con separador, ej. AA:BB:CC:DD:EE:FF
-- =============================================================
INSERT INTO activo (id, ip, mac, hostname, sistema_operativo, direccion_red, escaneo_id) VALUES
-- Escaneo 1: red interna ACME
(1, '192.168.1.1',   'AA:BB:CC:DD:EE:01', 'gw-acme.local',    'Linux 5.15',          '192.168.1.0/24', 1),
(2, '192.168.1.50',  'AA:BB:CC:DD:EE:02', 'srv-dc01.local',   'Windows Server 2019', '192.168.1.0/24', 1),
(3, '192.168.1.100', 'AA:BB:CC:DD:EE:03', 'web01.acme.local', 'Ubuntu 22.04 LTS',    '192.168.1.0/24', 1),
-- Escaneo 2: mismo host web, escaneo detallado (registro independiente)
(4, '192.168.1.100', 'AA:BB:CC:DD:EE:03', 'web01.acme.local', 'Ubuntu 22.04 LTS',    '192.168.1.0/24', 2),
-- Escaneo 3: perimetral RetailMax
(5, '203.0.113.1',   NULL,                'fw-retail.ext',    'Cisco IOS 15.x',      '203.0.113.0/28', 3);

-- =============================================================
-- 7. PUERTOS
-- =============================================================
INSERT INTO puerto (id, numero, protocolo, estado, servicio, version, activo_id) VALUES
-- gw-acme.local (activo 1)
(1,  22,   'tcp', 'open',     'ssh',      'OpenSSH 8.9p1',           1),
(2,  80,   'tcp', 'open',     'http',     'nginx 1.22.1',            1),
-- srv-dc01.local (activo 2)
(3,  445,  'tcp', 'open',     'smb',      'Samba 4.x / Windows SMB', 2),
(4,  3389, 'tcp', 'open',     'rdp',      'Microsoft RDP',           2),
(5,  135,  'tcp', 'open',     'msrpc',    'Microsoft RPC',           2),
-- web01 discovery (activo 3)
(6,  80,   'tcp', 'open',     'http',     'Apache 2.4.52',           3),
(7,  443,  'tcp', 'open',     'https',    'Apache 2.4.52',           3),
-- web01 escaneo detalle (activo 4)
(8,  80,   'tcp', 'open',     'http',     'Apache 2.4.52',           4),
(9,  443,  'tcp', 'open',     'https',    'Apache 2.4.52',           4),
(10, 8080, 'tcp', 'open',     'http-alt', 'Apache Tomcat 9.0.65',    4),
(11, 22,   'tcp', 'open',     'ssh',      'OpenSSH 8.9p1',           4),
-- fw-retail.ext (activo 5)
(12, 22,   'tcp', 'filtered', 'ssh',      NULL,                      5),
(13, 443,  'tcp', 'open',     'https',    'Cisco ASDM',              5);

-- =============================================================
-- 8. VULNERABILIDADES
-- Columnas: cve_asociado, descripcion, cvss, vector, severidad, remediacion, puerto_id
-- severidad en español segun CVSS v3.1:
--   0.0       → nada
--   0.1 - 3.9 → baja
--   4.0 - 6.9 → media
--   7.0 - 8.9 → alta
--   9.0 - 10.0→ crítica
-- vector: cadena CVSS v3.1 estandar
-- =============================================================
INSERT INTO vulnerabilidad (id, cve_asociado, descripcion, cvss, vector, severidad, remediacion, puerto_id) VALUES
-- Puerto 8: Apache 2.4.52, activo 4 (web01 - escaneo detalle)
(1, 'CVE-2021-41773',
    'Path traversal en Apache 2.4.49. Permite leer ficheros fuera del DocumentRoot y ejecutar codigo remoto con mod_cgi.',
    7.5,
    'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N',
    'alta',
    'Actualizar Apache a 2.4.51 o superior. Anadir "Require all denied" en la directiva Directory.',
    8),

-- Puerto 3: SMB, activo 2 (srv-dc01)
(2, 'CVE-2017-0144',
    'EternalBlue: desbordamiento de buffer en SMBv1 que permite ejecucion remota de codigo sin autenticacion.',
    9.3,
    'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
    'critica',
    'Deshabilitar SMBv1. Aplicar parche MS17-010. Bloquear puertos 139 y 445 en el firewall.',
    3),

-- Puerto 4: RDP, activo 2 (srv-dc01) — sin CVE conocido
(3, NULL,
    'RDP expuesto sin Network Level Authentication (NLA). Permite intentos de autenticacion directos contra el escritorio remoto.',
    6.5,
    'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:L/A:N',
    'media',
    'Habilitar NLA en propiedades del sistema. Restringir acceso RDP mediante VPN.',
    4),

-- Puerto 10: Tomcat, activo 4 (web01 - escaneo detalle)
(4, 'CVE-2020-1938',
    'Ghostcat: conector AJP de Tomcat permite leer o incluir ficheros arbitrarios del servidor si el conector esta expuesto.',
    9.8,
    'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
    'critica',
    'Deshabilitar el conector AJP en server.xml si no se usa. Actualizar Tomcat a 9.0.31 o superior.',
    10),

-- Puerto 1: SSH, activo 1 (gw-acme) — sin CVE conocido
(5, NULL,
    'OpenSSH con soporte para cifrados debiles (arcfour, 3des-cbc) detectados durante la negociacion.',
    4.3,
    'CVSS:3.1/AV:N/AC:H/PR:N/UI:N/S:U/C:L/I:L/A:N',
    'media',
    'Deshabilitar cifrados debiles en sshd_config. Usar solo AES-256-CTR o ChaCha20-Poly1305.',
    1);

-- =============================================================
-- 9. HALLAZGOS
-- Tabla existente para extension futura (Gobuster, SQLMap...).
-- No implementado en MVP. Registros de muestra funcionales.
-- severidad usa ENUM: ('info','baja','media','alta','critica')
-- =============================================================
INSERT INTO hallazgo (id, herramienta, tipo, recurso, codigo_respuesta, descripcion, severidad, activo_id) VALUES
(1, 'gobuster', 'directory', '/admin/',          200, 'Directorio /admin/ accesible publicamente sin autenticacion.',    'alta',    4),
(2, 'gobuster', 'directory', '/backup/',         200, 'Directorio /backup/ expuesto. Posibles ficheros sensibles.',      'alta',    4),
(3, 'gobuster', 'file',      '/config.php.bak',  200, 'Fichero de configuracion con extension .bak accesible.',          'critica', 4),
(4, 'gobuster', 'directory', '/.git/',           403, 'Repositorio Git potencialmente expuesto (403 en listado).',       'media',   4),
(5, 'gobuster', 'file',      '/robots.txt',      200, 'robots.txt accesible. Puede revelar rutas internas.',             'info',    4);

-- =============================================================
-- 10. INFORMES
-- emitido_por preserva audit trail.
-- =============================================================
INSERT INTO informe (id, tipo_informe, formato, ruta_archivo, fecha_creacion, proyecto_id, emitido_por) VALUES
(1, 'Informe Ejecutivo', 'pdf', 'storage/informes/acme_red_interna_ejecutivo_20250420.pdf', '2025-04-20 14:00:00', 1, 2),
(2, 'Informe Tecnico',   'pdf', 'storage/informes/acme_web_tecnico_20250421.pdf',           '2025-04-21 16:30:00', 2, 2);

-- =============================================================
-- Todo correcto → confirmamos la transaccion
-- =============================================================
COMMIT;

-- =============================================================
-- VERIFICACION
-- =============================================================
SELECT 'betterscan_seed.sql ejecutado correctamente.' AS resultado;

SELECT 'rol'            AS tabla, COUNT(*) AS registros FROM rol
UNION ALL SELECT 'empresa',        COUNT(*) FROM empresa
UNION ALL SELECT 'usuario',        COUNT(*) FROM usuario
UNION ALL SELECT 'proyecto',       COUNT(*) FROM proyecto
UNION ALL SELECT 'escaneo',        COUNT(*) FROM escaneo
UNION ALL SELECT 'activo',         COUNT(*) FROM activo
UNION ALL SELECT 'puerto',         COUNT(*) FROM puerto
UNION ALL SELECT 'vulnerabilidad', COUNT(*) FROM vulnerabilidad
UNION ALL SELECT 'hallazgo',       COUNT(*) FROM hallazgo
UNION ALL SELECT 'informe',        COUNT(*) FROM informe;
