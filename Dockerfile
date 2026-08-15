FROM php:8.2-apache

# Menginstall ekstensi mysqli agar PHP bisa terkoneksi ke MySQL
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Mengaktifkan mod_rewrite Apache jika nantinya dibutuhkan
RUN a2enmod rewrite

# VULN: Berjalan dengan root privileges secara default karena tidak ada perintah USER

WORKDIR /var/www/html

# Menyalin seluruh source code ke dalam container
COPY . /var/www/html/