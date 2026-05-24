#!/bin/bash
# =============================================================
# BetterScan - init.sh
# Se ejecuta UNA VEZ en el primer arranque del contenedor MariaDB
# (carpeta /docker-entrypoint-initdb.d/ de la imagen).
#
# Se eligió un .sh en lugar de un .sql porque el shell SÍ puede
# interpolar la variable DB_BACKUP_PASSWORD que viene del .env
# (un .sql plano la trataría como texto literal). Así la
# contraseña del usuario `betterscan_backup` queda automáticamente
# sincronizada con la del .env, sin pasos manuales.
#
# Operaciones que realiza:
#   * betterscan_app    : revoca el ALL por defecto de la imagen y
#                         deja solo DML (sin DROP/ALTER/CREATE).
#   * betterscan_backup : crea el usuario con la contraseña del
#                         .env y le da solo SELECT + LOCK TABLES
#                         (para mysqldump).
# =============================================================
set -euo pipefail

mariadb -uroot -p"${MARIADB_ROOT_PASSWORD}" <<SQL
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'betterscan_app'@'%';

GRANT SELECT, INSERT, UPDATE, DELETE
    ON betterscan_db.* TO 'betterscan_app'@'%';

CREATE USER IF NOT EXISTS 'betterscan_backup'@'%'
    IDENTIFIED BY '${DB_BACKUP_PASSWORD}';
GRANT SELECT, LOCK TABLES
    ON betterscan_db.* TO 'betterscan_backup'@'%';

FLUSH PRIVILEGES;
SQL
