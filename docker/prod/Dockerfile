# Accepted values: 8.3 - 8.2
ARG PHP_VERSION=8.3

ARG FRANKENPHP_VERSION=latest

ARG COMPOSER_VERSION=latest

ARG DOCKER_FILES_BASE_PATH="docker/prod/"

###########################################
# Build frontend assets with NPM
###########################################

#ARG NODE_VERSION=20-alpine
#
#FROM node:${NODE_VERSION} AS build
#
#ENV ROOT=/var/www/html
#
#WORKDIR ${ROOT}
#
#RUN npm config set update-notifier false && npm set progress=false
#
#COPY package*.json ./
#
#RUN if [ -f $ROOT/package-lock.json ]; \
#    then \
#    npm ci --loglevel=error --no-audit; \
#    else \
#    npm install --loglevel=error --no-audit; \
#    fi
#
#COPY . .
#
#RUN npm run build

###########################################

FROM composer:${COMPOSER_VERSION} AS vendor

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}

ARG DOCKER_FILES_BASE_PATH

LABEL maintainer="solidtime <hello@solidtime.io>"
LABEL org.opencontainers.image.title="solidtime"
LABEL org.opencontainers.image.description="solidtime is a modern open source timetracker for freelancers and agencies"
LABEL org.opencontainers.image.source="https://github.com/solidtime-io/solidtime"
LABEL org.opencontainers.image.licenses="AGPL"

ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG TZ=UTC
ARG APP_DIR=/var/www/html

ENV DEBIAN_FRONTEND=noninteractive \
    TERM=xterm-color \
    WITH_HORIZON=false \
    WITH_SCHEDULER=false \
    OCTANE_SERVER=frankenphp \
    USER=octane \
    ROOT=${APP_DIR} \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=24 \
    XDG_CONFIG_HOME=${APP_DIR}/.config \
    XDG_DATA_HOME=${APP_DIR}/.data

WORKDIR ${ROOT}

SHELL ["/bin/bash", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo ${TZ} > /etc/timezone

RUN apt-get update; \
    apt-get upgrade -yqq; \
    apt-get install -yqq --no-install-recommends --show-progress \
    apt-utils \
    curl \
    wget \
    nano \
    ncdu \
    procps \
    ca-certificates \
    supervisor \
    libsodium-dev \
    # Install PHP extensions (included with dunglas/frankenphp)
    && install-php-extensions \
    bz2 \
    pcntl \
    mbstring \
    bcmath \
    sockets \
    pgsql \
    pdo_pgsql \
    opcache \
    exif \
    pdo_mysql \
    zip \
    intl \
    gd \
    redis \
    rdkafka \
    memcached \
    igbinary \
    ldap \
    && apt-get -y autoremove \
    && apt-get clean \
    && docker-php-source delete \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && rm /var/log/lastlog /var/log/faillog

RUN arch="$(uname -m)" \
    && case "$arch" in \
    armhf) _cronic_fname='supercronic-linux-arm' ;; \
    aarch64) _cronic_fname='supercronic-linux-arm64' ;; \
    x86_64) _cronic_fname='supercronic-linux-amd64' ;; \
    x86) _cronic_fname='supercronic-linux-386' ;; \
    *) echo >&2 "error: unsupported architecture: $arch"; exit 1 ;; \
    esac \
    && wget -q "https://github.com/aptible/supercronic/releases/download/v0.2.29/${_cronic_fname}" \
    -O /usr/bin/supercronic \
    && chmod +x /usr/bin/supercronic \
    && mkdir -p /etc/supercronic \
    && echo "*/1 * * * * php ${ROOT}/artisan schedule:run --no-interaction" > /etc/supercronic/laravel

RUN userdel --remove --force www-data \
    && groupadd --force -g ${WWWGROUP} ${USER} \
    && useradd -ms /bin/bash --no-log-init --no-user-group -g ${WWWGROUP} -u ${WWWUSER} ${USER}

RUN chown -R ${USER}:${USER} ${ROOT} /var/{log,run} \
    && chmod -R a+rw ${ROOT} /var/{log,run}

RUN cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

USER ${USER}

COPY --chown=${USER}:${USER} --from=vendor /usr/bin/composer /usr/bin/composer
#COPY --chown=${USER}:${USER} composer.json composer.lock ./
#
#RUN composer install \
#    --no-dev \
#    --no-interaction \
#    --no-autoloader \
#    --no-ansi \
#    --no-scripts \
#    --audit

COPY --chown=${USER}:${USER} . .
#COPY --chown=${USER}:${USER} --from=build ${ROOT}/public public

RUN mkdir -p \
    storage/framework/{sessions,views,cache,testing} \
    storage/logs \
    bootstrap/cache && chmod -R a+rw storage

COPY --chown=${USER}:${USER} ${DOCKER_FILES_BASE_PATH}deployment/supervisord.conf /etc/supervisor/
COPY --chown=${USER}:${USER} ${DOCKER_FILES_BASE_PATH}deployment/octane/FrankenPHP/supervisord.frankenphp.conf /etc/supervisor/conf.d/
COPY --chown=${USER}:${USER} ${DOCKER_FILES_BASE_PATH}deployment/supervisord.*.conf /etc/supervisor/conf.d/
COPY --chown=${USER}:${USER} ${DOCKER_FILES_BASE_PATH}deployment/start-container /usr/local/bin/start-container
COPY --chown=${USER}:${USER} ${DOCKER_FILES_BASE_PATH}deployment/php.ini ${PHP_INI_DIR}/conf.d/99-octane.ini

# FrankenPHP embedded PHP configuration
COPY --chown=${USER}:${USER} ${DOCKER_FILES_BASE_PATH}deployment/php.ini /lib/php.ini

#RUN composer install \
#    --classmap-authoritative \
#    --no-interaction \
#    --no-ansi \
#    --no-dev \
#    && composer clear-cache

RUN cat .env
RUN php artisan env
RUN php artisan storage:link

RUN chmod +x /usr/local/bin/start-container

RUN cat ${DOCKER_FILES_BASE_PATH}deployment/utilities.sh >> ~/.bashrc

EXPOSE 8000

ENTRYPOINT ["start-container"]

HEALTHCHECK --start-period=5s --interval=2s --timeout=5s --retries=8 CMD php artisan octane:status || exit 1
