# the different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target


# https://docs.docker.com/engine/reference/builder/#understand-how-arg-and-from-interact
ARG PHP_VERSION=8.1
ARG NGINX_VERSION=1.17

# "php" stage
FROM php:${PHP_VERSION}-fpm-alpine AS container_php

# persistent / runtime deps
RUN apk add --no-cache \
		acl \
		file \
		gettext \
		git \
	;

ARG APCU_VERSION=5.1.21
RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		icu-dev \
		libzip-dev \
		postgresql-dev \
		zlib-dev \
	; \
	\
	docker-php-ext-configure zip ; \
#	docker-php-ext-configure gd ; \
	docker-php-ext-install -j$(nproc) \
		intl \
		pdo_pgsql \
		zip \
#		gd \
	; \
	pecl install \
		apcu-${APCU_VERSION} \
	; \
	pecl install \
	    redis-5.3.6 \
	; \
	pecl clear-cache; \
	docker-php-ext-enable \
		apcu \
		opcache \
		redis \
#		gd \
	; \
	\
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache --virtual .api-phpexts-rundeps $runDeps; \
	\
	apk del .build-deps

RUN ln -s $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
COPY docker/php/conf.d/api-platform.ini $PHP_INI_DIR/conf.d/api-platform.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
# install Symfony Flex globally to speed up download of Composer packages (parallelized prefetching)
RUN set -eux; \
	composer global require "symfony/flex" --prefer-dist --no-progress --no-suggest --classmap-authoritative; \
	composer clear-cache
ENV PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /srv

# build for production
#ARG APP_ENV=prod

# prevent the reinstallation of vendors at every changes in the source code
COPY composer.json composer.lock  ./
# do not use .env files in production
#RUN echo '<?php return [];' > .env.local.php
RUN set -eux; \
	composer install --prefer-dist --no-autoloader --no-scripts --no-progress --no-suggest; \
	composer clear-cache

# copy only specifically what we need
COPY .env .env
COPY bin bin/
COPY config config/
COPY public public/
COPY src src/

RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer dump-autoload --classmap-authoritative; \
	composer run-script post-install-cmd; \
	sync
VOLUME /srv/var

COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

ARG XDEBUG_VERSION=3.1.2
RUN set -eux; \
	apk add --no-cache --virtual .build-deps $PHPIZE_DEPS; \
	pecl install xdebug-$XDEBUG_VERSION; \
	docker-php-ext-enable xdebug; \
	apk del .build-deps

# "nginx" stage
# depends on the "php" stage above
FROM nginx:${NGINX_VERSION}-alpine AS container_nginx

COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /srv

COPY --from=container_php /srv/public public/
