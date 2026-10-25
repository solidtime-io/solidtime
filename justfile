# tock task runner. `just` lists recipes.

set dotenv-load := false

# PHP's composer vendor/ collides with Go vendor mode — always -mod=mod until
# the PHP tree is deleted (Phase 9).
export GOFLAGS := "-mod=mod"

# go packages under node_modules are not ours
gopkgs := `go list ./... | grep -v /node_modules/`

# --- development ---

# start dev dependencies (pgsql, pgsql_test, gotenberg, mailpit, minio)
dev:
    docker compose -f docker-compose.dev.yml up -d

# stop dev dependencies
dev-down:
    docker compose -f docker-compose.dev.yml down

# tail dev dependency logs
dev-logs:
    docker compose -f docker-compose.dev.yml logs -f

# run the upstream PHP app (behavior oracle) — uses the root compose file
oracle:
    docker compose up -d pgsql laravel.test mailpit minio gotenberg

# --- build ---

# build the Vue SPA and stage it for embedding
spa:
    npm run build
    rm -rf internal/web/dist
    mkdir -p internal/web/dist
    cp -r public/build internal/web/dist/build
    cp -r public/favicons public/images public/fonts public/desktop-version internal/web/dist/
    cp public/favicon.ico public/robots.txt public/security.txt internal/web/dist/
    touch internal/web/dist/.gitkeep

# compile the tock binary (requires `just spa` first for the embedded SPA)
build:
    go build -o bin/tock ./cmd/tock

# regenerate sqlc code
sqlc:
    sqlc generate

# --- run ---

# run the dev server (rebuilds, uses .envrc DSN)
serve: build
    ./bin/tock serve

# apply migrations against TOCK_DB_DSN
migrate:
    go run ./cmd/tock migrate

# insert demo data (users, org, projects, time entries)
seed:
    go run ./cmd/tock seed

# --- verification ---

# go build + vet + test
test:
    go build ./...
    go test {{gopkgs}}

# golangci-lint
lint:
    golangci-lint run

# full pre-push gate
verify: lint test
