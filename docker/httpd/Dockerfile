FROM php:7.3-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get update \
    && apt-get install -y net-tools iputils-ping vim wget cron git msmtp \
    && apt-get install -y software-properties-common dirmngr apt-transport-https lsb-release ca-certificates rrdtool \
    && apt-get install -y libzip-dev \
    && apt-get install -y zlib1g-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install zip \
    && apt dist-upgrade

# Older versions of lmutil needs this.  Future versions of Debian may need this line tweaked.
RUN ln -s /lib64/ld-linux-x86-64.so.2 /lib64/ld-lsb-x86-64.so.3

RUN apt-get update && apt-get install -y \
		libfreetype6-dev \
		libjpeg62-turbo-dev \
		libpng-dev \
	&& docker-php-ext-configure gd --with-freetype-dir=/usr  --with-jpeg-dir=/usr --with-png-dir=/usr \
	&& docker-php-ext-install -j$(nproc) gd

RUN mkdir -p /usr/local/bin
COPY lmutil /usr/local/bin/
RUN chmod 755 /usr/local/bin/lmutil

WORKDIR /var/www/html
RUN mkdir /app
RUN git clone https://github.com/rpi-dotcio/phpLicenseWatcher.git /app
RUN cp -R /app/* /var/www/html
COPY config.php /var/www/html

COPY php.ini /usr/local/etc/php/

COPY crontab /root/crontab
RUN crontab /root/crontab

COPY entrypoint.sh /app/entrypoint.sh
CMD /app/entrypoint.sh

