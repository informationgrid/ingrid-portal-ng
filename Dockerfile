FROM php:8.3-fpm-bullseye

SHELL [ "/bin/bash", "-exo", "pipefail", "-c" ]

# renovate: datasource=github-tags depName=getgrav/grav versioning=semver
ENV GRAV_VERSION 1.7.46
# renovate: datasource=github-tags depName=krakjoe/apcu versioning=semver
ENV PHP_APCU_VERSION v5.1.23
# renovate: datasource=github-tags depName=php/pecl-file_formats-yaml versioning=semver
ENV PHP_YAML_VERSION 2.2.3

ENV ADMIN_EMAIL portal@test.de
ENV ADMIN_FULL_NAME "The Admin"

RUN groupadd --system foo; \
    useradd --no-log-init --system --gid foo --create-home foo; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git \
        unzip \
        rsync \
        gosu \
        ##### Run dependencies
        libzip4 \
        libyaml-0-2 \
        libpng16-16 \
        libjpeg62-turbo \
        libwebp6 \
        libfreetype6 \
        ##### Build dependencies
        libwebp-dev \
        libjpeg-dev \
        libpng-dev \
        libfreetype6-dev \
        libyaml-dev \
        libzip-dev \
        apache2-utils \
        libxslt1-dev \
        libxml2 \
    ; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
	docker-php-ext-install -j "$(nproc)" \
        zip \
        gd \
        opcache \
        xsl \
        xml \
        dom \
    ; \
    pecl install apcu-${PHP_APCU_VERSION:1}; \
    pecl install yaml-$PHP_YAML_VERSION; \
    \
    docker-php-ext-enable \
        apcu \
        yaml \
    ; \
    apt-get purge -y --auto-remove \
        libwebp-dev \
        libjpeg-dev \
        libpng-dev \
        libfreetype6-dev \
        libyaml-dev \
        libzip-dev \
    ; \
    rm -rf /var/lib/apt/lists/*; \
    \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini";

WORKDIR /var/www
RUN curl -o grav-admin.zip -SL https://getgrav.org/download/core/grav-admin/${GRAV_VERSION} && \
    unzip -qq grav-admin.zip -d /usr/share -x "grav-admin/user/themes/*" -x "grav-admin/user/pages/*" && \
    rm grav-admin.zip

# COPY OUR ADDITIONAL THEMES AND PLUGINS
COPY user/themes /usr/share/grav-admin/user/themes
COPY user/plugins /usr/share/grav-admin/user/plugins
COPY user/pages /usr/share/grav-admin/user/pages
COPY data /usr/share/grav-admin/user/data
COPY user/accounts /usr/share/grav-admin/user/accounts

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN cd /usr/share/grav-admin/user/plugins/ingrid-search-result && composer update
RUN cd /usr/share/grav-admin/user/plugins/ingrid-detail && composer update
RUN cd /usr/share/grav-admin/user/plugins/ingrid-rss && composer update
RUN cd /usr/share/grav-admin/user/plugins/ingrid-codelist && composer update
RUN cd /usr/share/grav-admin/user/plugins/ingrid-providers && composer update
RUN cd /usr/share/grav-admin/user/plugins/ingrid-help && composer update
RUN cd /usr/share/grav-admin/user/plugins/ingrid-catalog && composer update
RUN cd /usr/share/grav-admin/user/plugins/ingrid-datasources && composer update
RUN cd /usr/share/grav-admin/user/plugins/ingrid-grav-utils && composer update

COPY entrypoint.sh /entrypoint.sh
#COPY grav.ini $PHP_INI_DIR/conf.d/

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
