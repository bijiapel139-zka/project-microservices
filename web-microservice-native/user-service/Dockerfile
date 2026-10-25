# ==========================================
# Dockerfile User Service
# Ibarat resep: "mulai dari mesin PHP siap pakai, taruh kode kita, nyalakan server di port 5001".
# ==========================================

# Image resmi PHP (versi CLI). Server bawaan PHP (php -S) sudah ada di dalamnya.
FROM php:8.3-cli

WORKDIR /app
COPY index.php .

# Data (users.json) disimpan di /data. Folder ini ditempeli volume oleh docker-compose,
# jadi data tidak hilang saat container dibuat ulang.
ENV DATA_DIR=/data
RUN mkdir -p /data

EXPOSE 5001

# 0.0.0.0 = terima koneksi dari luar container (kalau localhost saja, port tidak bisa diakses dari laptop)
CMD ["php", "-S", "0.0.0.0:5001", "-t", "/app"]
