-- =============================================================
-- betterscan_create.sql
-- BetterScan — Creación de base de datos, tablas y privilegios
-- TFG DAW — Proyecto BetterScan
-- =============================================================
--
-- NOTAS SOBRE ESTE SCRIPT (cambios respecto a la entrega T2)
-- ----------------------------------------------------------
-- 1. CONTRASEÑAS:
--    Las cláusulas `IDENTIFIED BY` del final usan PLACEHOLDERS
--    (`CAMBIAR_ME_*`), NO contraseñas reales. Sustitúyalos por
--    contraseñas robustas (`openssl rand -hex 24`) ANTES de
--    ejecutar este script. La instancia pública desplegada en
--    AWS usa contraseñas distintas a las del seeder por defecto.
--
-- 2. ENUM `severidad`:
--    En la entrega T2 figuraba `'critica'` (con tilde). En la fase
--    de implementación se armonizó a `'critica'` (sin tilde) para
--    coincidir con el ENUM real de la migración Laravel y con la
--    constante `Vulnerabilidad::SEV_CRITICA` del modelo. Mantener
--    la tilde aquí dejaba la BD incompatible con el código si
--    alguien ejecutaba el SQL manual en vez de usar las
--    migraciones de Laravel.
--
-- 3. HOST DE USUARIOS:
--    Los `CREATE USER` usan `@'localhost'` porque este script está
--    pensado para una instalación MariaDB manual en el mismo host.
--    En el despliegue Docker los usuarios se crean con `@'%'` desde
--    `laravel/database/init.sh` (lo monta el docker-entrypoint y
--    además interpola la contraseña real desde el `.env`).
--
-- 4. CAMPOS ADICIONALES INCORPORADOS EN T3:
--    Durante la implementación se añadieron a las migraciones Laravel
--    varios campos que no figuran en este script de diseño T2:
--      * escaneo: `parametros_nmap` (JSON con la configuración del
--        asistente de 4 pasos), `progreso_pct`, `fase_actual`,
--        `error_mensaje`, `lanzado_por` (FK a usuario, para la
--        autorización de borrado), `created_at`/`updated_at`.
--      * vulnerabilidad: `referencias` (text con URLs de NVD/MITRE)
--        y `enriquecido_en` (datetime, marca temporal del lookup
--        a NVD/MITRE para evitar re-pedirlo en cada consulta).
--      * todas las tablas Eloquent: `created_at`/`updated_at`
--        añadidos por Laravel automáticamente (`$table->timestamps()`).
--    La fuente de verdad del esquema VIVO son las migraciones de
--    `laravel/database/migrations/`. Este `betterscan_create.sql` se
--    mantiene como entregable T2 (Análisis y Diseño) — para una BD
--    operativa hay que arrancar Docker y dejar que Laravel ejecute
--    `php artisan migrate` (lo hace el entrypoint del contenedor).
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS betterscan_db;
CREATE DATABASE betterscan_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE betterscan_db;
 
-- =============================================================
-- TABLA: rol
-- =============================================================
CREATE TABLE rol (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(50)     NOT NULL UNIQUE
                    COMMENT 'Valores esperados: admin, empleado, cliente',
    descripcion TEXT,
    PRIMARY KEY (id)
) ENGINE=InnoDB COMMENT='Roles de usuario del sistema';
 
-- =============================================================
-- TABLA: empresa
-- Los datos de responsable son contacto comercial independiente
-- del usuario del portal (sin FK → usuario para evitar circularidad)
-- =============================================================
CREATE TABLE empresa (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre              VARCHAR(150)    NOT NULL,
    cif                 VARCHAR(20)     UNIQUE,
    nombre_comercial    VARCHAR(150),
    razon_social        VARCHAR(200),
    sector              VARCHAR(100),
    direccion           TEXT,
    logo_path           VARCHAR(500),
    activo              TINYINT(1)      NOT NULL DEFAULT 1,
    responsable_nombre  VARCHAR(100),
    responsable_email   VARCHAR(150),
    PRIMARY KEY (id)
) ENGINE=InnoDB COMMENT='Empresas clientes auditadas';
 
-- =============================================================
-- TABLA: usuario
-- empresa_id NULLABLE: solo NOT NULL cuando rol = cliente.
-- Restricción de aplicación — se valida en middleware Laravel.
-- =============================================================
CREATE TABLE usuario (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100)    NOT NULL,
    apellido        VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL UNIQUE,
    telefono        VARCHAR(20),
    contrasena_hash VARCHAR(255)    NOT NULL
                        COMMENT 'Bcrypt mediante Hash::make() de Laravel. Nunca texto plano.',
    avatar          VARCHAR(500),
    rol_id          INT UNSIGNED    NOT NULL,
    empresa_id      INT UNSIGNED    NULL
                        COMMENT 'NULL para admin/empleado. NOT NULL para cliente (restricción de aplicación).',
    PRIMARY KEY (id),
    CONSTRAINT fk_usuario_rol
        FOREIGN KEY (rol_id) REFERENCES rol(id),
    CONSTRAINT fk_usuario_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresa(id)
            ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Usuarios del sistema con rol asignado';
 
-- =============================================================
-- TABLA: proyecto
-- =============================================================
CREATE TABLE proyecto (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre                  VARCHAR(200)    NOT NULL,
    descripcion             TEXT,
    etiquetas               VARCHAR(500)
                                COMMENT 'valores separados por comas.',
    tipo_auditoria          VARCHAR(100),
    alcance_red             TEXT
                                COMMENT 'rangos CIDR separados por comas.',
    excepciones_red         TEXT
                                COMMENT 'IPs/rangos excluidos separados por comas',
    visibilidad             ENUM('privado', 'cliente') NOT NULL DEFAULT 'privado',
    fecha_limite_estimada   DATE,
    empresa_id              INT UNSIGNED    NOT NULL,
    auditor_id              INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_proyecto_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresa(id),
    CONSTRAINT fk_proyecto_auditor
        FOREIGN KEY (auditor_id) REFERENCES usuario(id)
) ENGINE=InnoDB COMMENT='Proyectos de auditoría de seguridad';
 
-- =============================================================
-- TABLA: escaneo
-- estado ENUM: impide que FastAPI escriba valores inválidos.
-- FastAPI actualiza el estado llamando a:
--   POST /api/internal/escaneo/{id}/estado  (Bearer token interno)
-- Laravel es el único punto de escritura real en la BD.
-- exclusiones: violación 1FN documentada (MVP).
-- =============================================================
CREATE TABLE escaneo (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre              VARCHAR(200)    NOT NULL,
    descripcion         TEXT,
    tipo_escaneo        VARCHAR(200)    COMMENT 'Auditoría web, Escaneo de red, etc.',
    plantilla_escaneo   VARCHAR(100),
    objetivo            VARCHAR(500)    COMMENT 'IP, rango CIDR o hostname objetivo',
    velocidad           ENUM('lento','normal','rapido','agresivo') NOT NULL DEFAULT 'normal',
    intensidad          VARCHAR(50),
    estado              ENUM('pendiente','en_proceso','completado','error') NOT NULL DEFAULT 'pendiente',
    exclusiones         TEXT
                            COMMENT 'IPs/rangos excluidos separados por comas.',
    fecha_inicio        DATETIME,
    fecha_fin           DATETIME,
    proyecto_id         INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_escaneo_proyecto
        FOREIGN KEY (proyecto_id) REFERENCES proyecto(id)
) ENGINE=InnoDB COMMENT='Escaneos lanzados dentro de un proyecto';
 
-- =============================================================
-- TABLA: activo
-- Un activo pertenece a un escaneo concreto (snapshot en tiempo).
-- El mismo host en dos escaneos distintos genera dos registros
-- independientes, preservando el histórico de cada auditoría.
-- =============================================================
CREATE TABLE activo (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    ip                  VARCHAR(45)     COMMENT 'IPv4 o IPv6',
    mac                 VARCHAR(18),
    hostname            VARCHAR(255),
    sistema_operativo   VARCHAR(255),
    direccion_red       VARCHAR(50)     COMMENT 'Red a la que pertenece el activo',
    escaneo_id          INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_activo_escaneo
        FOREIGN KEY (escaneo_id) REFERENCES escaneo(id)
) ENGINE=InnoDB COMMENT='Hosts/activos descubiertos en un escaneo';
 
-- =============================================================
-- TABLA: puerto
-- =============================================================
CREATE TABLE puerto (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    numero      SMALLINT UNSIGNED NOT NULL,
    protocolo   ENUM('tcp','udp') NOT NULL DEFAULT 'tcp',
    estado      ENUM('open','closed','filtered') NOT NULL DEFAULT 'open',
    servicio    VARCHAR(100),
    version     VARCHAR(255),
    activo_id   INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_puerto_activo
        FOREIGN KEY (activo_id) REFERENCES activo(id)
) ENGINE=InnoDB COMMENT='Puertos descubiertos en un activo';
 
-- =============================================================
-- TABLA: vulnerabilidad
-- cve_asociado NULLABLE: puede no existir CVE registrado.
-- severidad: según CVSS v3.1 (0.0=none, 0.1-3.9=low, 4.0-6.9=medium,
-- 7.0-8.9=high, 9.0-10.0=critical). Se mantiene por rendimiento
-- y legibilidad. FastAPI asigna el valor al parsear Nmap.
-- =============================================================
CREATE TABLE vulnerabilidad (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    cve_asociado    VARCHAR(20)     NULL
                        COMMENT 'Nullable: puede no existir CVE registrado.',
    descripcion     TEXT,
    cvss            DECIMAL(3,1)    COMMENT 'Puntuación CVSS v3.1 (0.0 – 10.0)',
    vector			    VARCHAR(255),
    severidad       ENUM('nada','baja','media','alta','critica'),
    remediacion     TEXT,
    puerto_id       INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_vulnerabilidad_puerto
        FOREIGN KEY (puerto_id) REFERENCES puerto(id)
) ENGINE=InnoDB COMMENT='Vulnerabilidades asociadas a un puerto';
 
-- =============================================================
-- TABLA: hallazgo
-- Absorbe resultados de herramientas de capa de aplicación web:
-- Gobuster, Wfuzz, SQLMap, Nikto. Trabaja sobre recursos URL del
-- activo, a diferencia de VULNERABILIDAD que se liga a PUERTO.
-- No rompe la cadena ACTIVO→PUERTO→VULNERABILIDAD de Nmap.
-- Integración con Gobuster: NO implementada aún.
-- La tabla existe para facilitar la extensión futura.
-- =============================================================
CREATE TABLE hallazgo (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    herramienta     VARCHAR(50)     COMMENT 'gobuster, wfuzz, sqlmap, nikto...',
    tipo            VARCHAR(100),
    recurso         VARCHAR(1000)   COMMENT 'Ruta o URL del recurso encontrado',
    codigo_respuesta SMALLINT UNSIGNED COMMENT 'Código HTTP de respuesta',
    descripcion     TEXT,
    severidad       ENUM('info','baja','media','alta','critica'),
    activo_id       INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_hallazgo_activo
        FOREIGN KEY (activo_id) REFERENCES activo(id)
) ENGINE=InnoDB COMMENT='Hallazgos de herramientas de aplicación web (Gobuster, SQLMap...)';
 
-- =============================================================
-- TABLA: informe
-- Ligado a PROYECTO, no a ESCANEO: cubre el proyecto completo.
-- emitido_por preserva el audit trail: quién lo generó en el
-- momento de creación, independientemente de reasignaciones.
-- Formato: solo PDF en el MVP.
-- =============================================================
CREATE TABLE informe (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    tipo_informe    VARCHAR(100)    COMMENT 'ejecutivo, tecnico...',
    formato         ENUM('pdf')     NOT NULL DEFAULT 'pdf'
                        COMMENT 'Solo PDF por ahora.',
    ruta_archivo    VARCHAR(500),
    fecha_creacion  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    proyecto_id     INT UNSIGNED    NOT NULL,
    emitido_por     INT UNSIGNED    NOT NULL
                        COMMENT 'Audit trail: FK al usuario que generó el informe.',
    PRIMARY KEY (id),
    CONSTRAINT fk_informe_proyecto
        FOREIGN KEY (proyecto_id) REFERENCES proyecto(id),
    CONSTRAINT fk_informe_emisor
        FOREIGN KEY (emitido_por) REFERENCES usuario(id)
) ENGINE=InnoDB COMMENT='Informes generados por proyecto';
 
SET FOREIGN_KEY_CHECKS = 1;
 
-- =============================================================
-- USUARIOS DE BASE DE DATOS Y PRIVILEGIOS
-- betterscan_app: solo DML (SELECT, INSERT, UPDATE, DELETE).
--   Sin DROP, ALTER ni GRANT para limitar el impacto de SQLi.
-- betterscan_backup: solo lectura para scripts de backup.
-- =============================================================
CREATE USER IF NOT EXISTS 'betterscan_app'@'localhost'
    IDENTIFIED BY 'CAMBIAR_ME_APP_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE
    ON betterscan_db.* TO 'betterscan_app'@'localhost';

CREATE USER IF NOT EXISTS 'betterscan_backup'@'localhost'
    IDENTIFIED BY 'CAMBIAR_ME_BACKUP_PASSWORD';
GRANT SELECT, LOCK TABLES
    ON betterscan_db.* TO 'betterscan_backup'@'localhost';
 
FLUSH PRIVILEGES;
 
-- Verificación rápida
SELECT 'betterscan_create.sql ejecutado correctamente.' AS resultado;
SHOW TABLES;
