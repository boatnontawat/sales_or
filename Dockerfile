# ใช้ PHP เวอร์ชั่น 8.2 พร้อม Apache
FROM php:8.2-apache

# ติดตั้ง Extension mysqli เพื่อให้ต่อ Database ได้
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# เปิดใช้งาน mod_rewrite ของ Apache (เผื่อใช้ .htaccess)
RUN a2enmod rewrite

# ก๊อปปี้ไฟล์ทั้งหมดในโปรเจกต์ไปใส่ใน Container
COPY . /var/www/html/

# ตั้งค่า Permission ให้ Apache อ่านไฟล์ได้
RUN chown -R www-data:www-data /var/www/html

# บอก Render ว่าเราใช้พอร์ต 80
EXPOSE 80
