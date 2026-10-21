# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.3.36
ARG GO_VERSION=1.27
# FrankenPHP v1.13.1
ARG FRANKENPHP_REF=99d4e119ca2e5a90a69d8dbee5d5c9a8016416c5
ARG WATCHER_VERSION=0.14.5
ARG COMPOSER_VERSION=2.8
ARG APP_ENV
ARG DOCKER_FILES_BASE_PATH="docker/prod/"

FROM composer:${COMPOSER_VERSION} AS vendor

FROM golang:${GO_VERSION}-trixie AS golang-base

# Rebuild of the official dunglas/frankenphp images on a patched PHP base.
# Mirrors the "common", "builder" and "runner" stages of https://github.com/php/frankenphp/blob/v1.13.1/Dockerfile
FROM scratch AS frankenphp-src
ARG FRANKENPHP_REF
ADD https://github.com/php/frankenphp.git#${FRANKENPHP_REF} /

FROM php:${PHP_VERSION}-zts-trixie AS frankenphp-common

WORKDIR /app

RUN apt-get update && \
    apt-get -y --no-install-recommends install \
        mailcap \
        libcap2-bin \
    && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    mkdir -p \
        /app/public \
        /config/caddy \
        /data/caddy \
        /etc/caddy \
        /etc/frankenphp; \
    sed -i 's/php/frankenphp run/g' /usr/local/bin/docker-php-entrypoint

COPY --link --from=frankenphp-src /caddy/frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY --link --from=frankenphp-src /package/content/index.php /app/public/index.php
RUN ln /etc/caddy/Caddyfile /etc/frankenphp/Caddyfile && \
    curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions

ENV XDG_CONFIG_HOME=/config
ENV XDG_DATA_HOME=/data

FROM frankenphp-common AS upstream

ARG WATCHER_VERSION

COPY --from=golang-base /usr/local/go /usr/local/go

ENV PATH=/usr/local/go/bin:$PATH
ENV GOTOOLCHAIN=local

RUN apt-get update && \
    apt-get -y --no-install-recommends install \
    cmake \
    git \
    libargon2-dev \
    libbrotli-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    libreadline-dev \
    libsodium-dev \
    libsqlite3-dev \
    libssl-dev \
    libxml2-dev \
    zlib1g-dev \
    && \
    apt-get clean

WORKDIR /usr/local/src/watcher
RUN curl -fsSL "https://github.com/e-dant/watcher/archive/refs/tags/${WATCHER_VERSION}.tar.gz" | tar xz --strip-components 1 && \
    cmake -S . -B build -DCMAKE_BUILD_TYPE=Release && \
    cmake --build build && \
    cmake --install build && \
    ldconfig

WORKDIR /go/src/app
COPY --link --from=frankenphp-src / ./

COPY --from=caddy:builder /usr/bin/xcaddy /usr/bin/xcaddy

RUN CGO_ENABLED=1 \
    XCADDY_SETCAP=1 \
    XCADDY_GO_BUILD_FLAGS="-ldflags='-w -s' -tags=nobadger,nomysql,nopgx" \
    CGO_CFLAGS=$(php-config --includes) \
    CGO_LDFLAGS="$(php-config --ldflags) $(php-config --libs)" \
    xcaddy build \
        --output /usr/local/bin/frankenphp \
        --with github.com/dunglas/frankenphp=./ \
        --with github.com/dunglas/frankenphp/caddy=./caddy/ \
        --with github.com/dunglas/caddy-cbrotli

FROM frankenphp-common

ENV GODEBUG=cgocheck=0

COPY --from=upstream /usr/local/lib/libwatcher* /usr/local/lib/
RUN apt-get update && \
    apt-get install -y --no-install-recommends libstdc++6 && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    ldconfig

COPY --from=upstream /usr/local/bin/frankenphp /usr/local/bin/frankenphp

LABEL maintainer="solidtime <hello@solidtime.io>"
LABEL org.opencontainers.image.title="solidtime"
LABEL org.opencontainers.image.description="solidtime is a modern open source timetracker for freelancers and agencies"
LABEL org.opencontainers.image.source="https://github.com/solidtime-io/solidtime"
LABEL org.opencontainers.image.licenses="AGPL"

ARG USER_ID=1000
ARG GROUP_ID=1000
ARG TZ=UTC
ARG DOCKER_FILES_BASE_PATH

ENV DEBIAN_FRONTEND=noninteractive \
    TERM=xterm-color \
    OCTANE_SERVER=frankenphp \
    TZ=${TZ} \
    LANG=C.UTF-8 \
    USER=laravel \
    ROOT=/var/www/html \
    APP_ENV=production \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=48 \
    WITH_HORIZON=false \
    WITH_SCHEDULER=false \
    WITH_REVERB=false \
    WITH_SSR=false

ENV XDG_CONFIG_HOME=${ROOT}/.config XDG_DATA_HOME=${ROOT}/.data

WORKDIR ${ROOT}

SHELL ["/bin/bash", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo ${TZ} > /etc/timezone

RUN echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom

RUN apt-get update; \
    apt-get upgrade -yqq; \
    apt-get install -yqq --no-install-recommends --show-progress \
    apt-utils \
    curl \
    wget \
    vim \
    git \
    unzip \
    ncdu \
    procps \
    ca-certificates \
    supervisor \
    libsodium-dev \
#    && curl -fsSL https://bun.sh/install | BUN_INSTALL=/usr bash \
    && install-php-extensions \
    apcu \
    bz2 \
    pcntl \
    mbstring \
    bcmath \
    sockets \
    pdo_pgsql \
    opcache \
    exif \
    pdo_mysql \
    zip \
    uv \
    intl \
    gd \
    redis \
    rdkafka \
    ffi \
    ldap \
    && apt-get -y autoremove \
    && apt-get clean \
    && docker-php-source delete \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /var/log/lastlog /var/log/faillog

RUN arch="$(uname -m)" \
    && case "$arch" in \
    armhf) _cronic_fname='supercronic-linux-arm' ;; \
    aarch64) _cronic_fname='supercronic-linux-arm64' ;; \
    x86_64) _cronic_fname='supercronic-linux-amd64' ;; \
    x86) _cronic_fname='supercronic-linux-386' ;; \
    *) echo >&2 "error: unsupported architecture: $arch"; exit 1 ;; \
    esac \
    && wget -q "https://github.com/aptible/supercronic/releases/download/v0.2.38/${_cronic_fname}" \
    -O /usr/bin/supercronic \
    && chmod +x /usr/bin/supercronic \
    && mkdir -p /etc/supercronic \
    && echo "*/1 * * * * php ${ROOT}/artisan schedule:run --no-interaction" > /etc/supercronic/laravel

RUN userdel --remove --force www-data \
    && groupadd --force -g ${GROUP_ID} ${USER} \
    && useradd -ms /bin/bash --no-log-init --no-user-group -g ${GROUP_ID} -u ${USER_ID} ${USER}

RUN cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

COPY --link --from=vendor /usr/bin/composer /usr/bin/composer
COPY --link ${DOCKER_FILES_BASE_PATH}deployment/supervisord.conf /etc/
COPY --link ${DOCKER_FILES_BASE_PATH}deployment/octane/FrankenPHP/supervisord.frankenphp.conf /etc/supervisor/conf.d/
COPY --link ${DOCKER_FILES_BASE_PATH}deployment/supervisord.*.conf /etc/supervisor/conf.d/
COPY --link ${DOCKER_FILES_BASE_PATH}deployment/start-container /usr/local/bin/start-container
COPY --link ${DOCKER_FILES_BASE_PATH}deployment/healthcheck /usr/local/bin/healthcheck
COPY --link ${DOCKER_FILES_BASE_PATH}deployment/php.ini ${PHP_INI_DIR}/conf.d/99-php.ini
#COPY --link composer.* ./

#RUN composer install \
#    --no-dev \
#    --no-interaction \
#    --no-autoloader \
#    --no-ansi \
#    --no-scripts \
#    --no-progress \
#    --audit

#COPY --link package.json bun.lock* ./

#RUN bun install --frozen-lockfile

COPY --link . .

RUN mkdir -p \
    storage/framework/{sessions,views,cache,testing} \
    storage/logs \
    bootstrap/cache \
    && chmod +x /usr/local/bin/start-container /usr/local/bin/healthcheck

RUN composer dump-autoload \
    --optimize \
    --apcu \
    --no-dev

#RUN bun run build

RUN chown -R ${USER_ID}:${GROUP_ID} ${ROOT} \
    && find / -perm /6000 -type f -exec chmod a-s {} + 2>/dev/null || true

USER ${USER}

EXPOSE 8000
#EXPOSE 2019
#EXPOSE 8080

ENTRYPOINT ["start-container"]

#HEALTHCHECK --start-period=30s --interval=10s --timeout=3s --retries=3 CMD healthcheck || exit 1
