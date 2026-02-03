FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libpq-dev \
    zip \
    unzip \
    postgresql-client \
    msmtp \
    msmtp-mta \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    xml

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Create drupal user
RUN useradd -u 1000 -ms /bin/bash drupal \
    && chown -R drupal:drupal /var/www/html

# Copy entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Configure msmtp for email sending
COPY msmtprc /etc/msmtprc
RUN chmod 644 /etc/msmtprc \
    && touch /tmp/mail.log \
    && chmod 666 /tmp/mail.log \
    && echo "sendmail_path = /usr/bin/msmtp -t -i" >> /usr/local/etc/php/conf.d/mail.ini

# Set proper permissions
RUN mkdir -p /var/www/html/web/sites/default/files \
    && chmod -R 775 /var/www/html

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
