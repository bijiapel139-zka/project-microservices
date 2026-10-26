# E-Commerce Mikroservis (PHP Native)

Toko online kecil yang dipecah menjadi beberapa service. Tiap service jalan di port sendiri dan punya data sendiri.

| Service         | Folder             | Port |
| --------------- | ------------------ | ---- |
| Frontend        | `frontend/`        | 8000 |
| User Service    | `user-service/`    | 5001 |
| Product Service | `product-service/` | 5002 |
| Order Service   | `order-service/`   | 5003 |

Penjelasan lengkap, dokumentasi API, dan diagram ada di folder `docs/`.

## Cara menjalankan

Ada dua cara. Pilih salah satu, jangan dua-duanya sekaligus, karena port-nya sama.

### Cara 1: Docker (disarankan)

Butuh **Docker Desktop** (pastikan sudah menyala). PHP tidak perlu dipasang.

Di folder yang berisi `docker-compose.yml`:

```
docker compose up --build
```

Tunggu sampai log berhenti mengalir, lalu buka http://localhost:8000.

Perintah lain yang berguna:

```
docker compose ps           # lihat service yang sedang jalan
docker compose logs -f      # lihat log semua service
docker compose down         # matikan semuanya (data tetap tersimpan)
docker compose down -v      # matikan dan hapus semua data (reset total)
```

### Cara 2: Tanpa Docker

Butuh PHP 8 atau lebih baru (cek: `php -v`).

**Windows:** klik 2x `run.bat`. Empat jendela server terbuka dan browser langsung menuju http://localhost:8000.

**Mac / Linux (atau manual):** buka 4 terminal di folder project ini:

```
php -S localhost:5001 -t user-service
php -S localhost:5002 -t product-service
php -S localhost:5003 -t order-service
php -S localhost:8000 -t frontend
```

Lalu buka http://localhost:8000

## Catatan

- Tanpa Docker, data disimpan di `users.json`, `produk.json`, dan `orders.json` (dibuat otomatis, tidak ikut Git). Untuk reset, hapus ketiga file itu lalu jalankan ulang.
- Dengan Docker, data disimpan di tiga volume terpisah (satu per service). Untuk reset, pakai `docker compose down -v`.
- Kalau muncul error "port is already allocated", berarti masih ada server lain di port yang sama. Tutup jendela `run.bat` atau hentikan Docker dulu.
