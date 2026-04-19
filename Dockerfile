FROM php:8.1-apache

# Disable conflicting MPM modules and enable the right one
RUN a2dismod mpm_event mpm_worker && a2enmod mpm_prefork rewrite

COPY . /var/www/html/

EXPOSE 80