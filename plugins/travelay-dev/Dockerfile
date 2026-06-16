FROM registry.digitalocean.com/dslk/php/wordpress:php8.2

WORKDIR /var/www

COPY --chown=www-data:www-data wp-content /var/www/wp-content

# Prepare files for plugin updates		
COPY --chown=www-data:www-data .git /opt/.git
RUN if [ -f .gitmodules ]; then cp .gitmodules /opt/; chown www-data:www-data /opt/.gitmodules; fi

VOLUME ["/var/www/wp-content/uploads"]
