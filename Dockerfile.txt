# ใช้ PHP 8.2 Apache เหมือนเดิม
FROM php:8.2-apache

# 🔥 เพิ่มบรรทัดนี้: เพื่อติดตั้ง mysqli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# ก๊อปปี้ไฟล์โปรเจกต์
COPY . /var/www/html/

# เปิด Port 80
EXPOSE 80
