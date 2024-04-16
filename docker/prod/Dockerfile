# Accepted values: 8.3 - 8.2
ARG PHP_VERSION=8.3

ARG DOCKER_FILES_BASE_PATH="docker/prod"

ARG COMPOSER_VERSION=latest

###########################################

FROM composer:${COMPOSER_VERSION} AS vendor

FROM php:${PHP_VERSION}-cli-bookworm AS base

LABEL maintainer="solidtime <hello@solidtime.io>"
LABEL org.opencontainers.image.title="solidtime"
LABEL org.opencontainers.image.description="solidtime is a modern open source timetracker for Freelancers and Agencies"
LABEL org.opencontainers.image.source="https://github.com/solidtime-io/solidtime"
LABEL org.opencontainers.image.licenses="AGPL"

ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG TZ=UTC

ENV DEBIAN_FRONTEND=noninteractive \
  TERM=xterm-color \
  WITH_HORIZON=false \
  WITH_SCHEDULER=false \
  OCTANE_SERVER=swoole \
  USER=octane \
  ROOT=/var/www/html \
  COMPOSER_FUND=0 \
  COMPOSER_MAX_PARALLEL_HTTP=24

WORKDIR ${ROOT}

SHELL ["/bin/bash", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
  && echo ${TZ} > /etc/timezone

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apt-get update; \
  apt-get upgrade -yqq; \
  apt-get install -yqq --no-install-recommends --show-progress \
  apt-utils \
  curl \
  wget \
  nano \
  ncdu \
  ca-certificates \
  supervisor \
  libsodium-dev \
  # Install PHP extensions
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
  swoole \
  && apt-get -y autoremove \
  && apt-get clean \
  && docker-php-source delete \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
  && rm /var/log/lastlog /var/log/faillog

RUN wget -q "https://github.com/aptible/supercronic/releases/download/v0.2.29/supercronic-linux-amd64" \
  -O /usr/bin/supercronic \
  && chmod +x /usr/bin/supercronic \
  && mkdir -p /etc/supercronic \
  && echo "*/1 * * * * php ${ROOT}/artisan schedule:run --verbose --no-interaction" > /etc/supercronic/laravel

RUN userdel --remove --force www-data \
  && groupadd --force -g ${WWWGROUP} ${USER} \
  && useradd -ms /bin/bash --no-log-init --no-user-group -g ${WWWGROUP} -u ${WWWUSER} ${USER}

RUN chown -R ${USER}:${USER} ${ROOT} /var/{log,run} \
  && chmod -R a+rw /var/{log,run}

RUN cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

USER ${USER}

COPY --chown=${USER}:${USER} --from=vendor /usr/bin/composer /usr/bin/composer

COPY --chown=${USER}:${USER} . .

RUN mkdir -p \
  storage/framework/{sessions,views,cache,testing} \
  storage/logs \
  bootstrap/cache && chmod -R a+rw storage

COPY --chown=${USER}:${USER} docker/prod/deployment/supervisord.*.conf /etc/supervisor/conf.d/
COPY --chown=${USER}:${USER} docker/prod/deployment/php.ini ${PHP_INI_DIR}/conf.d/99-octane.ini
COPY --chown=${USER}:${USER} docker/prod/deployment/start-container /usr/local/bin/start-container

RUN cat .env
RUN php artisan env
RUN php artisan storage:link

RUN chmod +x /usr/local/bin/start-container

RUN cat docker/prod/deployment/utilities.sh >> ~/.bashrc

EXPOSE 8000

ENTRYPOINT ["start-container"]

HEALTHCHECK --start-period=5s --interval=2s --timeout=5s --retries=8 CMD php artisan octane:status || exit 1
