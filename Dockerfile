FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
  git \
  curl \
  zip \
  unzip \
  icu-dev \
  libxml2-dev \
  oniguruma-dev \
  libzip-dev \
  libpng-dev \
  libjpeg-turbo-dev \
  freetype-dev \
  mysql-dev \
  bash \
  supervisor

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
  pdo_mysql \
  opcache \
  intl \
  zip \
  gd \
  mbstring \
  xml \
  ctype \
  pcntl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 symfony && adduser -u 1000 -G symfony -s /bin/sh -D symfony

WORKDIR /var/www

RUN mkdir -p /var/www && chown -R symfony:symfony /var/www

COPY --chown=symfony:symfony composer.json composer.lock ./

USER symfony
RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader

USER root
COPY --chown=symfony:symfony . .

USER symfony
RUN composer dump-autoload --optimize && \
  composer run-script --no-dev post-install-cmd

USER root
RUN mkdir -p var/cache var/log && \
  chown -R symfony:symfony var/ && \
  chmod -R 755 var/

EXPOSE 9000

USER symfony

CMD ["php-fpm"]