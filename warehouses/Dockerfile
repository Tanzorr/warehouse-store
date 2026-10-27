FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    git \
    unzip \
    cron \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

COPY crontab /etc/cron.d/cron-schedule

RUN chmod 0644 /etc/cron.d/cron-schedule

RUN crontab /etc/cron.d/cron-schedule

RUN apt-get update && apt-get install -y libcurl4-openssl-dev pkg-config && docker-php-ext-install curl

CMD ["sh", "-c", "cron && php-fpm"]