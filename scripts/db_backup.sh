#!/usr/bin/env bash
# DB backup script for Finboard
# Usage: ./scripts/db_backup.sh [destination_dir]
# Defaults to storage/backups

set -euo pipefail

# Prefer common Homebrew mysql-client locations so webserver PHP can find mysqldump
export PATH="/opt/homebrew/bin:/opt/homebrew/opt/mysql-client/bin:/usr/local/opt/mysql-client/bin:/usr/local/bin:$PATH"

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEST_DIR="${1:-$PROJECT_ROOT/storage/backups}"
RETENTION_DAYS=14
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Load DB credentials from .env if present (simple parser)
ENV_FILE="$PROJECT_ROOT/.env"

# Priority: runtime env (Dokploy) -> .env file -> hardcoded default
DB_HOST="${DB_HOST:-}"
DB_PORT="${DB_PORT:-}"
DB_DATABASE="${DB_DATABASE:-}"
DB_USERNAME="${DB_USERNAME:-}"
DB_PASSWORD="${DB_PASSWORD:-}"

if [[ -f "$ENV_FILE" ]]; then
  [[ -z "$DB_HOST" ]] && DB_HOST="$(grep -E '^DB_HOST=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
  [[ -z "$DB_PORT" ]] && DB_PORT="$(grep -E '^DB_PORT=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
  [[ -z "$DB_DATABASE" ]] && DB_DATABASE="$(grep -E '^DB_DATABASE=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
  [[ -z "$DB_USERNAME" ]] && DB_USERNAME="$(grep -E '^DB_USERNAME=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
  [[ -z "$DB_PASSWORD" ]] && DB_PASSWORD="$(grep -E '^DB_PASSWORD=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-finboard}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

mkdir -p "$DEST_DIR"
BACKUP_FILE="$DEST_DIR/${DB_DATABASE}_$TIMESTAMP.sql.gz"

echo "Backing up database '$DB_DATABASE' to $BACKUP_FILE"

# Use mysqldump with options suitable for InnoDB

# Find mysqldump binary from PATH or common Homebrew locations
MYSQLDUMP_BIN="$(command -v mysqldump 2>/dev/null || true)"
for p in "/opt/homebrew/bin/mysqldump" "/opt/homebrew/opt/mysql-client/bin/mysqldump" "/usr/local/opt/mysql-client/bin/mysqldump" "/usr/local/bin/mysqldump" "/usr/bin/mysqldump"; do
  if [[ -x "$p" && -z "$MYSQLDUMP_BIN" ]]; then
    MYSQLDUMP_BIN="$p"
  fi
done

if [[ -z "$MYSQLDUMP_BIN" || ! -x "$MYSQLDUMP_BIN" ]]; then
  cat >&2 <<'ERR'
Error: mysqldump not found in PATH.
Install the MySQL client and ensure `mysqldump` is on your PATH.
On macOS (Homebrew):
  brew install mysql-client
Then add to your shell profile, for example (Intel):
  echo 'export PATH="/usr/local/opt/mysql-client/bin:$PATH"' >> ~/.zshrc
or (Apple Silicon/Homebrew default):
  echo 'export PATH="/opt/homebrew/opt/mysql-client/bin:$PATH"' >> ~/.zshrc
If the webserver runs under a different user, ensure the PATH is available to that user or set the full path to mysqldump in this script.
ERR
  exit 2
fi

"$MYSQLDUMP_BIN" --single-transaction --quick --skip-lock-tables --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" --password="$DB_PASSWORD" "$DB_DATABASE" | gzip -c > "$BACKUP_FILE"

if [[ $? -ne 0 ]]; then
  echo "Backup failed" >&2
  exit 1
fi

# Retention: delete older backups
find "$DEST_DIR" -type f -name "${DB_DATABASE}_*.sql.gz" -mtime +$RETENTION_DAYS -print -delete || true

echo "Backup complete. Kept backups in $DEST_DIR (retention: $RETENTION_DAYS days)."
exit 0
