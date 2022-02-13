ARG ALPINE
FROM alpine:${ALPINE}
ARG PHP

# Add testing repository
RUN echo 'https://dl-cdn.alpinelinux.org/alpine/edge/testing' >> /etc/apk/repositories

# Install packages
RUN apk add \
    curl \
    php${PHP} \
    php${PHP}-simplexml \
    php${PHP}-dom \
    php${PHP}-pdo \
    php${PHP}-fileinfo \
    php${PHP}-tokenizer \
    php${PHP}-xml \
    php${PHP}-xmlwriter \
    php${PHP}-pdo_sqlite \
    php${PHP}-session \
    php${PHP}-phar \
    php${PHP}-mbstring \
    php${PHP}-iconv \
    php${PHP}-json \
    php${PHP}-openssl \
    php${PHP}-curl \
    php${PHP}-pdo_mysql \
    php${PHP}-pdo_pgsql \
    php${PHP}-pecl-xdebug

# Download composer
RUN curl -o /usr/bin/composer https://getcomposer.org/download/latest-stable/composer.phar
RUN chmod +x /usr/bin/composer

# Download CC reporter
RUN curl -Lo /usr/bin/cc-reporter https://codeclimate.com/downloads/test-reporter/test-reporter-latest-linux-amd64
RUN chmod +x /usr/bin/cc-reporter

# Create ini file
RUN printf "zend_extension=xdebug.so\nxdebug.mode=off\nmemory_limit=-1\n" > /etc/php${PHP}/conf.d/99_lodata.ini

# Link PHP executables
RUN if [ ! -e /usr/bin/php ]; then \
    ln -s /usr/bin/php${PHP} /usr/bin/php; \
    ln -s /usr/bin/phpize${PHP} /usr/bin/phpize; \
    ln -s /usr/bin/pecl${PHP} /usr/bin/pecl; \
    ln -s /usr/bin/php-config${PHP} /usr/bin/php-config; \
    fi

# Install sqlsrv drivers
RUN \
    if [ $PHP = "81" ] || [ $PHP = "8" ] || [ $PHP = "74" ]; then \
      apk add autoconf make unixodbc-dev g++ php${PHP}-dev php${PHP}-pear; \
      curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/msodbcsql17_17.8.1.1-1_amd64.apk; \
      curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/mssql-tools_17.8.1.1-1_amd64.apk; \
      apk add --allow-untrusted msodbcsql17_17.8.1.1-1_amd64.apk; \
      apk add --allow-untrusted mssql-tools_17.8.1.1-1_amd64.apk; \
      pecl install sqlsrv; \
      pecl install pdo_sqlsrv; \
      echo extension=pdo_sqlsrv.so >> /etc/php${PHP}/conf.d/99_lodata.ini; \
      echo extension=sqlsrv.so >> /etc/php${PHP}/conf.d/99_lodata.ini; \
      apk del autoconf make unixodbc-dev g++ php${PHP}-dev php${PHP}-pear; \
      rm *.apk; \
      rm -r /tmp/pear; \
    fi

WORKDIR /lodata