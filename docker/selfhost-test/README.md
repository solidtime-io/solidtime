# Local self-host smoke test

Runs the production image locally the same way the server runs it (http +
scheduler + queue worker + postgres), to verify a build before deploying.

## 1. Build the image

The production Dockerfile expects `vendor/`, `public/build/`, and a `.env`
already present in the build context (CI does this too — see
`.github/workflows/build-image.yml`):

```bash
composer install --ignore-platform-req=php
npm ci && npm run build
cp .env.production .env.build-backup  # keep your local .env!
# build from a clean copy so your local .env is not baked into the image
rsync -a --exclude .git --exclude node_modules --exclude .env . /tmp/ldtime-ctx/
cp .env.production /tmp/ldtime-ctx/.env
docker build -f docker/prod/Dockerfile --build-arg DOCKER_FILES_BASE_PATH=docker/prod/ \
  -t ld-time:local-test /tmp/ldtime-ctx
```

## 2. Configure

```bash
cd docker/selfhost-test
cp .env.selfhost-test.example .env.selfhost-test
# set APP_KEY (php artisan key:generate --show) and the Google credentials
mkdir -p keys
openssl genrsa -out keys/oauth-private.key 4096
openssl rsa -in keys/oauth-private.key -pubout -out keys/oauth-public.key
# league/oauth2-server refuses keys with permissions other than 600/660 —
# with 644 every authenticated API call 500s and the SPA hangs on retries.
chmod 600 keys/oauth-private.key keys/oauth-public.key
```

## 3. Run

```bash
docker compose up -d
docker compose logs -f app   # wait for migrations to finish
open http://localhost:8085
```

To test the browser extension against this instance, create the public OAuth
client and note the printed client id:

```bash
docker compose exec app php artisan passport:client --name=browser-extension \
  --redirect_uri=https://3369f72567118d8c03fb34880e9d6378d3b0c569.extensions.allizom.org/,https://hpanifeankiobmgbemnhjmhpjeebdhdd.chromiumapp.org/ \
  --public -n
```

Tear down with `docker compose down -v`.
