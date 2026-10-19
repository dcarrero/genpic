FROM php:8.4-cli

# System dependencies + msmtp for email sending
RUN apt-get update && apt-get install -y \
    git curl unzip \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libmagickwand-dev libzip-dev libicu-dev \
    nodejs npm \
    msmtp msmtp-mta \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip intl bcmath pcntl

# Imagick
RUN pecl install imagick && docker-php-ext-enable imagick

# Redis
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP config
RUN echo "upload_max_filesize=20M\npost_max_size=25M\nmemory_limit=256M" > /usr/local/etc/php/conf.d/app.ini

# Configure msmtp for direct email sending (MX lookup, no relay)
RUN echo "account default\n\
host mailhog\n\
port 1025\n\
from noreply@example.com\n\
auth off\n\
tls off\n" > /etc/msmtprc && chmod 644 /etc/msmtprc

# Tell PHP to use msmtp as sendmail
RUN echo "sendmail_path = /usr/bin/msmtp -t" > /usr/local/etc/php/conf.d/mail.ini

WORKDIR /var/www/html

EXPOSE 8000

CMD bash -c "\
    composer install --no-interaction --ignore-platform-req=php; \
    npm install && npm run build; \
    php artisan migrate --force --seed 2>/dev/null; \
    php artisan serve --host=0.0.0.0 --port=8000"
