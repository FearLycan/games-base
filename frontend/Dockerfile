FROM yiisoftware/yii2-php:8.3-apache

# Change document root for Apache
RUN sed -i -e 's|/app/web|/app/frontend/web|g' /etc/apache2/sites-available/000-default.conf

RUN apt update && apt install -y bash cron nano
SHELL ["/bin/bash", "-c"]

ENV TERM=xterm
ENV EDITOR=nano

# Start cron alongside Apache (apache2-foreground stays the main process).
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
