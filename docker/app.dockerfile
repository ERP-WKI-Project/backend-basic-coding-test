FROM php:8.2-fpm

# Install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install dependencies #1
RUN apt-get update && apt-get install -y  \
    libmagickwand-dev \
    --no-install-recommends \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && docker-php-ext-install pdo_mysql

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libxpm-dev \
    libxml2-dev \  
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    libzip-dev \
    libonig-dev \
    libpq-dev \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \   
    && docker-php-ext-install zip \
    && docker-php-ext-install exif pcntl 

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer