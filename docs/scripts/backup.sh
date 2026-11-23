#!/usr/bin/env bash
# ==============================================================================
# PrimeOS AcadSch - Daily Automated Backup Script
# Creates compressed database dump and storage archive with 14-day rotation
# ==============================================================================

set -euo pipefail

APP_DIR="/var/www/acadsch"
BACKUP_DIR="/var/backups/acadsch"
DATE=$(date +"%Y-%m-%d_%H%M%S")
RETENTION_DAYS=14

mkdir -p "${BACKUP_DIR}/db"
mkdir -p "${BACKUP_DIR}/storage"

echo "[$(date)] Starting daily backup for AcadSch..."

# 1. Database Backup (MySQL)
if [ -f "${APP_DIR}/.env" ]; then
    DB_DATABASE=$(grep "^DB_DATABASE=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"')
    DB_USERNAME=$(grep "^DB_USERNAME=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"')
    DB_PASSWORD=$(grep "^DB_PASSWORD=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"')
    DB_HOST=$(grep "^DB_HOST=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"')

    if [ -n "${DB_DATABASE}" ] && [ "${DB_DATABASE}" != "null" ]; then
        echo "Dumping database: ${DB_DATABASE}..."
        mysqldump -h "${DB_HOST:-127.0.0.1}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" \
            --single-transaction --quick --lock-tables=false | gzip > "${BACKUP_DIR}/db/acadsch_db_${DATE}.sql.gz"
        echo "Database backup saved to: ${BACKUP_DIR}/db/acadsch_db_${DATE}.sql.gz"
    fi
fi

# 2. Uploaded Storage Backup
if [ -d "${APP_DIR}/storage/app/public" ]; then
    echo "Archiving public storage..."
    tar -czf "${BACKUP_DIR}/storage/acadsch_storage_${DATE}.tar.gz" -C "${APP_DIR}/storage/app" public
    echo "Storage archive saved to: ${BACKUP_DIR}/storage/acadsch_storage_${DATE}.tar.gz"
fi

# 3. Clean up backups older than retention policy
echo "Enforcing ${RETENTION_DAYS}-day retention cleanup..."
find "${BACKUP_DIR}/db" -type f -name "*.sql.gz" -mtime +${RETENTION_DAYS} -delete
find "${BACKUP_DIR}/storage" -type f -name "*.tar.gz" -mtime +${RETENTION_DAYS} -delete

echo "[$(date)] Daily backup completed successfully."
