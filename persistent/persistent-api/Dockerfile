# Dockerfile to build Persistent Service API

# Base image
FROM php:7.2.7-apache

# Install Postgres PDO
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Enable rewrite module
RUN a2enmod rewrite

# Copy content from build folder to nginx folder
COPY ./build/ /var/www/html/

# Create /persistent and grant 777 permissions to store data
RUN mkdir /persistent & chmod -R 777 /persistent 