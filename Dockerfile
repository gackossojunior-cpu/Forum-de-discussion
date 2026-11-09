FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql

WORKDIR /var/www/html
COPY . .
RUN cp config.example.php config.php

EXPOSE 80
