#!/usr/bin/env bash

# Prepares a git worktree so its docker environment runs next to the one of the main checkout.
# Usage: bash docker/local/setup-worktree.sh <name> [db-port]
# The worktree gets its own compose project (containers, volumes), traefik routers and the
# host name <name>.solidtime.test (plus mail., storage., vite. and playwright. subdomains).

set -euo pipefail

NAME="${1:?Usage: setup-worktree.sh <name> [db-port]}"
DB_PORT="${2:-54330}"

WORKTREE_DIR="$(git rev-parse --show-toplevel)"
MAIN_DIR="$(dirname "$(git rev-parse --path-format=absolute --git-common-dir)")"

if [ "$WORKTREE_DIR" = "$MAIN_DIR" ]; then
    echo "Run this script inside a worktree, not in the main checkout." >&2
    exit 1
fi

HOST="${NAME}.solidtime.test"

cp "$MAIN_DIR/.env" "$WORKTREE_DIR/.env"
[ -f "$MAIN_DIR/auth.json" ] && cp "$MAIN_DIR/auth.json" "$WORKTREE_DIR/auth.json"
cp "$MAIN_DIR"/storage/oauth-*.key "$WORKTREE_DIR/storage/" 2>/dev/null || true

sed -i.bak \
    -e "s#solidtime\.test#${HOST}#g" \
    -e "s#^FORWARD_DB_PORT=.*#FORWARD_DB_PORT=${DB_PORT}#" \
    "$WORKTREE_DIR/.env"
rm "$WORKTREE_DIR/.env.bak"

{
    echo ""
    echo "COMPOSE_PROJECT_NAME=solidtime-${NAME}"
    echo "TRAEFIK_PREFIX=solidtime-${NAME}"
} >> "$WORKTREE_DIR/.env"

echo "Worktree environment configured for http://${HOST}"
echo "Add these entries to /etc/hosts if your DNS does not resolve *.test:"
for SUB in "" "mail." "storage." "storage-management." "vite." "playwright."; do
    echo "127.0.0.1 ${SUB}${HOST}"
done
