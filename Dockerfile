# Youthline: container image for hosting (used by Render, see render.yaml).
# Three stages: PHP dependencies -> front-end build -> the final web server.

# ---- 1. PHP dependencies ----------------------------------------------------
FROM php:8.5-cli AS vendor
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions gd zip intl exif bcmath
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
# Dev packages are included because the demo database is filled with sample data (needs Faker).
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

# ---- 2. Front-end build -----------------------------------------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
# The Vue app imports Ziggy (routes) from the PHP vendor folder.
COPY --from=vendor /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy
RUN npm run build

# ---- 3. Web server ----------------------------------------------------------
FROM php:8.5-apache
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions gd zip intl exif bcmath opcache \
    && a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/youthline.conf \
    && a2enconf youthline \
    && echo 'ServerName localhost' >> /etc/apache2/apache2.conf
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-interaction \
    && rm -f public/hot \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod +x docker/start.sh

CMD ["docker/start.sh"]
