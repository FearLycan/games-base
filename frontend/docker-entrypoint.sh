#!/bin/bash
set -e

# cron starts each job with a near-empty environment, so the variables
# docker-compose injects at runtime (DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, ...)
# would be missing inside `php yii`. Dump them to a file that every cron job
# sources via BASH_ENV (set at the top of the crontab).
printenv | sed -E 's/^([^=]+)=(.*)$/export \1="\2"/' > /etc/cron.env
chmod 0644 /etc/cron.env

# The crontab spool lives on the `cron-spool` named volume, so edits made with
# `crontab -e` persist across container restarts and rebuilds.
chown root:crontab /var/spool/cron/crontabs 2>/dev/null || true
chmod 1730 /var/spool/cron/crontabs 2>/dev/null || true

# The schedule starts with no jobs, but the two lines cron needs to reach the
# database must always be present: SHELL=/bin/bash + BASH_ENV=/etc/cron.env make
# every job source the runtime env written above. Seed just that header the
# first time the volume is created (when root has no crontab yet); add jobs with
# `docker compose exec frontend crontab -e`.
if [ ! -f /var/spool/cron/crontabs/root ]; then
    printf 'SHELL=/bin/bash\nBASH_ENV=/etc/cron.env\n' | crontab -
fi

# Make sure the cron log target exists (mounted from the host).
mkdir -p /app/console/runtime/logs

# Start the cron daemon in the background.
cron

# Hand off to the image's default command (apache2-foreground).
exec "$@"
