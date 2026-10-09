# Build environment for Anthropic's OSS Scanner (https://github.com/anthropics/oss-scanner).
# The scanner builds this image with the repository root as the build context and then audits it without network
# access, so everything needed to run the app and its test suite (PHP + Composer deps, Node deps + built frontend,
# a local PostgreSQL) is installed here.
#
# Inside the finished image:
#   .oss-scanner/start-postgres.sh    start the local PostgreSQL server (required for tests and the app)
#   php artisan test                  run the PHPUnit suite
#   php artisan serve                 run the app on http://127.0.0.1:8000 (after `php artisan migrate --seed`)

FROM node:20-bookworm-slim AS node

FROM php:8.3-cli-bookworm

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    TZ=UTC

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# PostgreSQL 15 (Debian bookworm default) is the database solidtime runs and is tested against.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip curl ca-certificates postgresql postgresql-client \
    && install-php-extensions pdo_pgsql pgsql intl gd zip bcmath exif pcntl sockets soap \
    && rm -rf /var/lib/apt/lists/* \
    && echo "memory_limit=2G" > "$PHP_INI_DIR/conf.d/99-oss-scanner.ini"

# Database matching .env.ci: user root / password root, database laravel.
RUN pg_ctlcluster 15 main start \
    && runuser -u postgres -- psql -c "CREATE ROLE root WITH LOGIN SUPERUSER PASSWORD 'root';" \
    && runuser -u postgres -- createdb -O root laravel \
    && pg_ctlcluster 15 main stop

# scanner contract: the checkout lives inside the image, at /src
COPY . /src
WORKDIR /src

RUN composer install --prefer-dist \
    && npm ci \
    && npm run build

RUN cp .env.ci .env \
    && php artisan key:generate \
    && php artisan passport:keys --force \
    && chmod +x .oss-scanner/start-postgres.sh

# Run the test suite so the image is known to work, but do not fail the build on test failures.
RUN .oss-scanner/start-postgres.sh \
    && (php artisan test || echo "WARNING: PHPUnit reported failures") \
    && pg_ctlcluster 15 main stop
