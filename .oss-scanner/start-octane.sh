#!/usr/bin/env bash
# Run the app the way production does: Laravel Octane on FrankenPHP in worker mode, with the production Caddyfile.
# A worker boots the app once and then serves many requests, so state kept in memory (static properties, singletons,
# changed config, ...) survives from one request to the next. With a single worker, consecutive requests always hit the
# same worker, so such leaks between requests (e.g. between users of different organizations) reproduce reliably.
#
# Usage: .oss-scanner/start-octane.sh    (OCTANE_WORKERS and OCTANE_MAX_REQUESTS can be overridden)
# Stop:  php artisan octane:stop
# After changing PHP code, run `php artisan octane:reload`: workers keep the code they booted with.
set -euo pipefail
cd "$(dirname "$0")/.."

.oss-scanner/start-postgres.sh
.oss-scanner/start-gotenberg.sh

php artisan migrate --force
if [ "$(PGPASSWORD=root psql -h 127.0.0.1 -U root -d laravel -tAc 'SELECT count(*) FROM users')" = "0" ]; then
    php artisan db:seed --force
fi
php artisan optimize:clear >/dev/null

nohup php artisan octane:frankenphp \
    --host=127.0.0.1 \
    --port=8000 \
    --workers="${OCTANE_WORKERS:-1}" \
    --max-requests="${OCTANE_MAX_REQUESTS:-10000}" \
    --caddyfile=docker/prod/deployment/octane/FrankenPHP/Caddyfile \
    >/tmp/octane.log 2>&1 &

until curl -fs -o /dev/null http://127.0.0.1:8000/login; do
    if ! kill -0 $! 2>/dev/null; then
        cat /tmp/octane.log
        exit 1
    fi
    sleep 0.5
done
echo "solidtime is running on http://127.0.0.1:8000 with Octane/FrankenPHP (log: /tmp/octane.log)"
