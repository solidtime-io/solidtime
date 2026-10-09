#!/usr/bin/env bash
# Start the local Gotenberg server (PDF rendering) on 127.0.0.1:3000, matching GOTENBERG_URL in .env.ci.
# Only the Chromium routes are needed by solidtime, see .oss-scanner/Dockerfile. Stop: kill "$(cat /tmp/gotenberg.pid)"
set -euo pipefail

if ! curl -fs http://127.0.0.1:3000/health >/dev/null; then
    nohup gotenberg \
        --api-port=3000 \
        --libreoffice-disable-routes \
        --libreoffice-auto-start=false \
        --log-level=warn \
        >/tmp/gotenberg.log 2>&1 &
    echo $! >/tmp/gotenberg.pid
    until curl -fs http://127.0.0.1:3000/health >/dev/null; do sleep 0.5; done
fi
echo "Gotenberg is running on http://127.0.0.1:3000 (log: /tmp/gotenberg.log)"
