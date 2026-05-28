ARG PHP_VERSION=8.4

# Build
FROM php:${PHP_VERSION}-fpm-alpine AS build-stage
ARG PHP_VERSION

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    linux-headers \
    zlib-dev libjpeg-turbo-dev libpng-dev libwebp-dev freetype-dev \
    libzip-dev icu-dev libxml2-dev oniguruma-dev libxslt-dev libpq-dev gmp-dev \
    imagemagick-dev \
&& docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
&& docker-php-ext-install -j$(nproc) \
    mysqli pdo_mysql pdo_pgsql gd zip intl xsl opcache bcmath pcntl sockets gmp \
&& pecl install -o -f redis imagick \
&& docker-php-ext-enable redis imagick \
&& apk del .build-deps \
&& rm -rf /tmp/pear /var/cache/apk/*

# Runtime
FROM php:${PHP_VERSION}-fpm-alpine
ARG PHP_VERSION

RUN apk add --no-cache \
    bash \
    zlib libjpeg-turbo libpng libwebp freetype \
    libzip icu libxml2 oniguruma libxslt libpq \
    gmp \
    tzdata msmtp ca-certificates \
    mariadb-client \
    pngquant \
    imagemagick \
&& rm -rf /var/cache/apk/*

COPY --from=build-stage /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=build-stage /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=composer:2.8.0 /usr/bin/composer /usr/local/bin/composer

ARG TZ=Europe/Moscow
ENV TZ=${TZ}
RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime && echo ${TZ} > /etc/timezone

ARG INSTALL_XDEBUG=0
RUN if [ "${INSTALL_XDEBUG}" = "1" ]; then \
    apk add --no-cache --virtual .xdebug-deps $PHPIZE_DEPS linux-headers && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    apk del .xdebug-deps; \
fi

COPY msmtprc.template /etc/msmtprc.template
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
RUN chmod 1777 /tmp

WORKDIR /var/www/html

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
