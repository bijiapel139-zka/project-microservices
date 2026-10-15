# E-Commerce Mikroservis (PHP Native)

Aplikasi e-commerce sederhana dengan arsitektur mikroservis.
Tiap service jalan di port sendiri.

| Service         | Folder             | Port |
| --------------- | ------------------ | ---- |
| Frontend        | `frontend/`        | 8000 |
| User Service    | `user-service/`    | 5001 |
| Product Service | `product-service/` | 5002 |

## Syarat

- PHP 8 atau lebih baru (cek: `php -v`)

## Cara menjalankan

### Windows

Klik 2x `run.bat`. Tiga jendela server terbuka dan browser langsung
menuju http://localhost:8000.

### Mac / Linux (atau manual)

Buka 3 terminal di folder project ini:

```
php -S localhost:5001 -t user-service
php -S localhost:5002 -t product-service
php -S localhost:8000 -t frontend
```

Lalu buka http://localhost:8000

## Catatan

- Data disimpan di `users.json` dan `produk.json` (dibuat otomatis,
  tidak ikut Git).
- Untuk reset data, hapus kedua file itu lalu jalankan ulang.
