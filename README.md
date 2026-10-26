# Project Microservices (Kelompok 3)

Project mata kuliah **Cloud Computing**, Universitas Muhammadiyah Gresik.
Aplikasi toko online sederhana yang dipecah menjadi tiga microservice: User, Product, dan Order.

## Anggota Kelompok 3

- Muhammad Zaki Azhari (NIM 240602023)
- Dimas Makhzum Mufrih (NIM 240602019)
- Muhammad Nur Zakaria (NIM 240602004)
- Dimas Mochammad (NIM 240602052)

## Arsitektur

| Service | Port | Fungsi | Data |
|---|---|---|---|
| Frontend | 8000 | Tampilan toko | - |
| User Service | 5001 | Profil pelanggan dan keranjang | `users.json` |
| Product Service | 5002 | Daftar produk | `produk.json` |
| Order Service | 5003 | Checkout dan riwayat pesanan | `orders.json` |

Alur antar service:

- Tambah ke keranjang: frontend → User Service → Product Service
- Checkout: frontend → Order Service → User Service

File data JSON dibuat otomatis saat service pertama kali berjalan dan tidak ikut Git.

## Struktur repo

- `web-microservice-native/` : kode project (frontend dan ketiga service)
- `web-microservice-native/docs/` : dokumentasi API dan arsitektur
- `web-microservice-native.zip` : arsip upload awal project

## Cara menjalankan

Project dijalankan dengan Docker Compose:

```bash
cd web-microservice-native
docker compose up --build
```

Lalu buka `http://localhost:8000`.

## Dokumentasi

- [Dokumentasi API](web-microservice-native/docs/api.md)
- [Arsitektur](web-microservice-native/docs/arsitektur.md)

## Contoh uji cepat

```bash
curl "http://localhost:5002/"
curl -X POST "http://localhost:5001/?id=1" -H "Content-Type: application/json" -d '{"produk_id":102}'
curl -X POST "http://localhost:5003/?user_id=1"
```
