FROM php:8.1-cli-alpine

# upgrade alpine
RUN apk -U upgrade

# install redis extension
#RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} \
#  && pecl install redis \
#  && docker-php-ext-enable redis \
#  && apk del pcre-dev ${PHPIZE_DEPS}

RUN curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions redis sockets


WORKDIR /app
