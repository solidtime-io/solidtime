#!/usr/bin/env bash
# Start the local PostgreSQL server used by the test suite and the app (see .oss-scanner/Dockerfile).
set -euo pipefail

pg_ctlcluster 17 main start 2>/dev/null || true
until pg_isready -h 127.0.0.1 -p 5432 -q; do sleep 0.5; done
echo "PostgreSQL is running on 127.0.0.1:5432 (user root, password root, database laravel)"
