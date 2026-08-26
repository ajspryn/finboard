#!/usr/bin/env bash
# DB restore script for Finboard
# Usage: ./scripts/db_restore.sh /path/to/backup.sql.gz

set -euo pipefail

# Prefer common Homebrew mysql-client locations so webserver PHP can find mysql
export PATH="/opt/homebrew/bin:/opt/homebrew/opt/mysql-client/bin:/usr/local/opt/mysql-client/bin:/usr/local/bin:$PATH"

if [[ $# -lt 1 ]]; then
  echo "Usage: $0 /path/to/backup.sql.gz" >&2
  exit 2
fi

BACKUP_FILE="$1"
# optional second arg: --force or --yes to skip interactive confirmation
SKIP_CONFIRM=0
if [[ "${2:-}" == "--force" || "${2:-}" == "--yes" || "${NONINTERACTIVE:-}" == "1" ]]; then
  SKIP_CONFIRM=1
fi
PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ENV_FILE="$PROJECT_ROOT/.env"

# Priority: runtime env (Dokploy) -> .env file -> hardcoded default
DB_HOST="${DB_HOST:-}"
DB_PORT="${DB_PORT:-}"
DB_DATABASE="${DB_DATABASE:-}"
DB_USERNAME="${DB_USERNAME:-}"
DB_PASSWORD="${DB_PASSWORD:-}"
MYSQL_SSL_MODE="${MYSQL_SSL_MODE:-}"
MYSQL_SSL_CA="${MYSQL_SSL_CA:-}"
MYSQL_ATTR_SSL_CA="${MYSQL_ATTR_SSL_CA:-}"

if [[ -f "$ENV_FILE" ]]; then
  [[ -z "$DB_HOST" ]] && DB_HOST="$(grep -E '^DB_HOST=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
  [[ -z "$DB_PORT" ]] && DB_PORT="$(grep -E '^DB_PORT=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
  [[ -z "$DB_DATABASE" ]] && DB_DATABASE="$(grep -E '^DB_DATABASE=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
  [[ -z "$DB_USERNAME" ]] && DB_USERNAME="$(grep -E '^DB_USERNAME=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
  [[ -z "$DB_PASSWORD" ]] && DB_PASSWORD="$(grep -E '^DB_PASSWORD=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
  [[ -z "$MYSQL_SSL_MODE" ]] && MYSQL_SSL_MODE="$(grep -E '^MYSQL_SSL_MODE=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
  [[ -z "$MYSQL_SSL_CA" ]] && MYSQL_SSL_CA="$(grep -E '^MYSQL_SSL_CA=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
  [[ -z "$MYSQL_ATTR_SSL_CA" ]] && MYSQL_ATTR_SSL_CA="$(grep -E '^MYSQL_ATTR_SSL_CA=' "$ENV_FILE" | tail -n1 | cut -d'=' -f2- | tr -d '\r' || true)"
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-finboard}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
MYSQL_SSL_MODE="${MYSQL_SSL_MODE:-REQUIRED}"
MYSQL_SSL_CA="${MYSQL_SSL_CA:-${MYSQL_ATTR_SSL_CA:-}}"

if [[ ! -f "$BACKUP_FILE" ]]; then
  echo "Backup file not found: $BACKUP_FILE" >&2
  exit 3
fi

if [[ $SKIP_CONFIRM -ne 1 ]]; then
  read -p "This will DROP and restore database '$DB_DATABASE'. Proceed? [y/N] " CONFIRM
  if [[ "$CONFIRM" != "y" && "$CONFIRM" != "Y" ]]; then
    echo "Aborted by user."
    exit 0
  fi
fi

# Find mysql binary from PATH or common Homebrew locations
MYSQL_BIN="$(command -v mysql 2>/dev/null || true)"
for p in "/opt/homebrew/bin/mysql" "/opt/homebrew/opt/mysql-client/bin/mysql" "/usr/local/opt/mysql-client/bin/mysql" "/usr/local/bin/mysql" "/usr/bin/mysql"; do
  if [[ -x "$p" && -z "$MYSQL_BIN" ]]; then
    MYSQL_BIN="$p"
  fi
done

if [[ -z "$MYSQL_BIN" || ! -x "$MYSQL_BIN" ]]; then
  echo "Error: mysql client not found in PATH. Install mysql-client (Homebrew: brew install mysql-client)" >&2
  exit 2
fi

# TLS options for environments with self-signed certificates.
# Default mode REQUIRED keeps TLS enabled but does not enforce CA validation.
MYSQL_SSL_ARGS=()
if "$MYSQL_BIN" --help 2>/dev/null | grep -q -- '--ssl-mode'; then
  MYSQL_SSL_ARGS+=("--ssl-mode=$MYSQL_SSL_MODE")
elif [[ "$MYSQL_SSL_MODE" != "DISABLED" ]]; then
  MYSQL_SSL_ARGS+=("--ssl")
fi
if [[ -n "$MYSQL_SSL_CA" ]]; then
  MYSQL_SSL_ARGS+=("--ssl-ca=$MYSQL_SSL_CA")
fi

# Drop and recreate database (requires privileges)
"$MYSQL_BIN" "${MYSQL_SSL_ARGS[@]}" --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" --password="$DB_PASSWORD" -e "DROP DATABASE IF EXISTS \`$DB_DATABASE\`; CREATE DATABASE \`$DB_DATABASE\`;"

# Restore
if [[ "$BACKUP_FILE" == *.gz ]]; then
  gunzip -c "$BACKUP_FILE" | "$MYSQL_BIN" "${MYSQL_SSL_ARGS[@]}" --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" --password="$DB_PASSWORD" "$DB_DATABASE"
else
  "$MYSQL_BIN" "${MYSQL_SSL_ARGS[@]}" --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" --password="$DB_PASSWORD" "$DB_DATABASE" < "$BACKUP_FILE"
fi

if [[ $? -ne 0 ]]; then
  echo "Restore failed" >&2
  exit 4
fi

echo "Restore complete."
exit 0
