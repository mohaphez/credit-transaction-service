FROM php:8.3-fpm


ARG user=www-data
ARG uid=1000


RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    default-mysql-client

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath

RUN pecl install redis && docker-php-ext-enable redis


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user && \
    chmod -R 755 /home/$user && \
    usermod -u $uid $user

WORKDIR /var/www/html

COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini


RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN chown -R $user:$user /var/www/html

USER $user

CMD ["php-fpm"]

EXPOSE 9000