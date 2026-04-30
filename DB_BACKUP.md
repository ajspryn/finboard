Database backup and restore for Finboard

Overview

- Scripts live in `scripts/`:
  - `scripts/db_backup.sh` — create compressed mysqldump backups to `storage/backups` by default.
  - `scripts/db_restore.sh` — restore a given `.sql` or `.sql.gz` backup (drops & recreates DB).

Quick usage

1. Create a backup (manual):

```bash
chmod +x scripts/db_backup.sh
./scripts/db_backup.sh
```

This will read DB credentials from `.env` if present, and write files like `storage/backups/finboard_YYYYMMDD_HHMMSS.sql.gz`.

2. Restore a backup (manual):

```bash
chmod +x scripts/db_restore.sh
./scripts/db_restore.sh storage/backups/finboard_20260430_151400.sql.gz
```

The script prompts for confirmation and then drops and recreates the database before restoring.

Scheduling (cron)

- Example: daily backup at 02:00, keep 14 days

```cron
0 2 * * * cd /path/to/finboard && ./scripts/db_backup.sh >> storage/logs/db-backup.log 2>&1
```

Retention

- The backup script deletes backups older than `RETENTION_DAYS` (default 14). Edit `RETENTION_DAYS` in the script to change policy.

Offsite storage

- For resilience, push backups to S3 or another storage. Example with AWS CLI (optional):

```bash
aws s3 cp storage/backups/finboard_20260430_151400.sql.gz s3://my-bucket/finboard/backups/
```

You can extend `scripts/db_backup.sh` to upload after successful creation.

Testing & verification

1. Create a backup using the script.
2. On a separate test DB or local machine, run the restore script and verify app boot/connectivity.
3. Run basic counts and sanity queries to ensure data integrity.

Security notes

- Keep backups access-restricted (filesystem permissions) and consider encrypting backups with `gpg` before offsite copy.
- Do not commit backups to the repo. Use `storage/backups` which is typically outside version control.

Troubleshooting

- If `mysqldump` prompts for a password, ensure `.env` contains `DB_PASSWORD` or run via an account with proper privileges.
- For very large DBs consider streaming uploads to S3 to avoid local disk pressure.

Installation notes (if `mysqldump` is not found)

- macOS (Homebrew):

```bash
brew install mysql-client
# Add to your PATH (Intel):
echo 'export PATH="/usr/local/opt/mysql-client/bin:$PATH"' >> ~/.zshrc
# or Apple Silicon/Homebrew default:
echo 'export PATH="/opt/homebrew/opt/mysql-client/bin:$PATH"' >> ~/.zshrc
```

- Ubuntu/Debian:

```bash
sudo apt update && sudo apt install default-mysql-client
```

After installing, restart your shell or source your profile, then re-run the backup script.

If you want, I can:

- Add S3 upload support to `db_backup.sh` (requires AWS CLI), or
- Add GPG encryption step, or
- Create a systemd timer / launchd plist to schedule automatic backups on macOS.
