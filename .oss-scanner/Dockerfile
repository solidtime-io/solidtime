# Build environment for Anthropic's OSS Scanner (https://github.com/anthropics/oss-scanner).
# The scanner builds this image with the repository root as the build context and then audits it without network
# access, so everything needed to run the app and its test suite (PHP + Composer deps, Node deps + built frontend,
# a local PostgreSQL and a local Gotenberg for PDF rendering) is installed here.
#
# The base image is the FrankenPHP image the production image (docker/prod/Dockerfile) is built on: in production the
# app runs as long-lived Laravel Octane workers in FrankenPHP worker mode, not as one PHP process per request.
#
# Inside the finished image:
#   .oss-scanner/start-postgres.sh    start the local PostgreSQL server (required for tests and the app)
#   .oss-scanner/start-gotenberg.sh   start the local Gotenberg server (required for PDF exports)
#   php artisan test                  run the PHPUnit suite (needs PostgreSQL and Gotenberg running)
#   .oss-scanner/start-octane.sh      run the app like production on http://127.0.0.1:8000 (starts everything above)

ARG FRANKENPHP_VERSION=1.11
ARG PHP_VERSION=8.3

FROM node:20-trixie-slim AS node

FROM gotenberg/gotenberg:8 AS gotenberg

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    OCTANE_SERVER=frankenphp \
    TZ=UTC

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# PostgreSQL 17 (Debian trixie default) is one of the versions solidtime is tested against in CI.
# chromium, qpdf and exiftool are what Gotenberg needs for HTML to PDF rendering.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip curl ca-certificates postgresql postgresql-client \
        chromium fonts-liberation qpdf libimage-exiftool-perl \
    && install-php-extensions pdo_pgsql pgsql intl gd zip bcmath exif pcntl sockets soap apcu \
    && rm -rf /var/lib/apt/lists/* \
    && echo "memory_limit=2G" > "$PHP_INI_DIR/conf.d/99-oss-scanner.ini"

# Gotenberg 8 (the PDF renderer, same image as in CI). solidtime only uses its Chromium HTML to PDF route, so LibreOffice
# and pdftk are not installed: Gotenberg only checks at startup that their binaries exist, start-gotenberg.sh disables
# the LibreOffice routes and the Chromium route does not use pdftk.
COPY --from=gotenberg /usr/bin/gotenberg /usr/local/bin/gotenberg
COPY --from=gotenberg /usr/bin/pdfcpu /usr/local/bin/pdfcpu
COPY --from=gotenberg /opt/gotenberg /opt/gotenberg
ENV CHROMIUM_BIN_PATH=/usr/bin/chromium \
    CHROMIUM_HYPHEN_DATA_DIR_PATH=/opt/gotenberg/chromium-hyphen-data \
    GOTENBERG_VERSIONS_DIR_PATH=/opt/gotenberg/versions \
    QPDF_BIN_PATH=/usr/bin/qpdf \
    EXIFTOOL_BIN_PATH=/usr/bin/exiftool \
    PDFCPU_BIN_PATH=/usr/local/bin/pdfcpu \
    LIBREOFFICE_BIN_PATH=/bin/false \
    UNOCONVERTER_BIN_PATH=/bin/false \
    PDFTK_BIN_PATH=/bin/false

# Database matching .env.ci: user root / password root, database laravel.
RUN pg_ctlcluster 17 main start \
    && runuser -u postgres -- psql -c "CREATE ROLE root WITH LOGIN SUPERUSER PASSWORD 'root';" \
    && runuser -u postgres -- createdb -O root laravel \
    && pg_ctlcluster 17 main stop

# scanner contract: the checkout lives inside the image, at /src
COPY . /src
WORKDIR /src

RUN composer install --prefer-dist \
    && npm ci \
    && npm run build

RUN cp .env.ci .env \
    && php artisan key:generate \
    && php artisan passport:keys --force \
    && php artisan octane:install --server=frankenphp --no-interaction \
    && chmod +x .oss-scanner/*.sh

# Run the test suite so the image is known to work, but do not fail the build on test failures.
RUN .oss-scanner/start-postgres.sh \
    && .oss-scanner/start-gotenberg.sh \
    && (php artisan test || echo "WARNING: PHPUnit reported failures") \
    && kill "$(cat /tmp/gotenberg.pid)" \
    && pg_ctlcluster 17 main stop
