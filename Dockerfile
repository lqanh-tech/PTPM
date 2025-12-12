FROM php:8.3-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN pecl install redis && docker-php-ext-enable redis
RUN a2enmod rewrite headers deflate

# Configure PHP security settings
RUN echo "auto_prepend_file = /var/www/html/auto_prepend.php" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "expose_php = Off" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "display_errors = Off" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/security.ini

# Configure OPcache for performance (opcache is built-in for PHP 8.2)
RUN docker-php-ext-enable opcache && \
    echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 80
