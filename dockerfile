FROM php:8.2-apache

# Habilita mod_rewrite (opcional, mas comum)
RUN a2enmod rewrite

# Ajusta Apache para escutar na porta do Render ($PORT)
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -i 's/:80/:${PORT}/' /etc/apache2/sites-available/000-default.conf

# Copia o site para o document root do Apache
COPY . /var/www/html

# Permissões (opcional)
RUN chown -R www-data:www-data /var/www/html

# Render define PORT em runtime; expor 80 é ok, mas não é obrigatório aqui
CMD ["apache2-foreground"]
