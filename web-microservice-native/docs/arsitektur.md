# Arsitektur Aplikasi E-Commerce Mikroservis (PHP Native)

> Berdasarkan kode terbaru di branch `main`.

## Komponen

| Komponen | Port | Tugas | Data |
|---|---|---|---|
| Frontend (`index.html`, `script.js`, `style.css`) | 8000 | Tampilan dan pemanggil API | - |
| User Service | 5001 | Profil pengguna dan keranjang | `users.json` |
| Product Service | 5002 | Daftar produk | `produk.json` |
| Order Service | 5003 | Checkout dan riwayat pesanan | `orders.json` |

Prinsip: tiap service punya data sendiri dan berkomunikasi lewat HTTP (JSON). Semua mengaktifkan CORS.

## Diagram alur

```mermaid
flowchart LR
    F[Frontend<br/>:8000]
    U[User Service<br/>:5001]
    P[Product Service<br/>:5002]
    O[Order Service<br/>:5003]
    UD[(users.json)]
    PD[(produk.json)]
    OD[(orders.json)]

    F -- "profil, keranjang, hapus item" --> U
    F -- "daftar produk" --> P
    F -- "checkout, riwayat" --> O

    U -- "ambil detail produk (produk_id)" --> P
    O -- "baca keranjang" --> U
    O -- "kosongkan keranjang" --> U

    U --- UD
    P --- PD
    O --- OD
```

Panah dari frontend adalah panggilan dari browser. Panah antar service (User → Product, Order → User) adalah panggilan service ke service lewat HTTP.

## Alur utama

### 1. Membuka halaman
```mermaid
sequenceDiagram
    participant F as Frontend
    participant P as Product Service
    participant U as User Service
    participant O as Order Service
    F->>P: GET /
    P-->>F: daftar produk
    F->>U: GET /?id=1
    U-->>F: profil + keranjang
    F->>O: GET /?user_id=1
    O-->>F: riwayat pesanan
```

### 2. Menambah produk ke keranjang
```mermaid
sequenceDiagram
    participant F as Frontend
    participant U as User Service
    participant P as Product Service
    F->>U: POST /?id=1 {produk_id}
    U->>P: GET /?id=produk_id
    P-->>U: {id, nama, harga}
    U->>U: simpan ke keranjang (users.json)
    U-->>F: data user terbaru
```
Frontend hanya mengirim nomor produk. Nama dan harga selalu diambil dari Product Service. Kalau Product Service mati, User Service membalas 503.

### 3. Checkout
```mermaid
sequenceDiagram
    participant F as Frontend
    participant O as Order Service
    participant U as User Service
    F->>O: POST /?user_id=1
    O->>U: GET /?id=1
    U-->>O: keranjang
    O->>O: hitung total, simpan pesanan (orders.json)
    O->>U: POST /?id=1&action=kosongkan
    U-->>O: keranjang kosong
    O-->>F: 201 struk pesanan
```
Pesanan disimpan lebih dulu, baru keranjang dikosongkan, supaya pesanan tidak hilang jika pengosongan gagal.

### 4. Melihat riwayat
```mermaid
sequenceDiagram
    participant F as Frontend
    participant O as Order Service
    F->>O: GET /?user_id=1
    O-->>F: daftar struk (terbaru di atas)
```

## Penanganan error antar service
| Situasi | Hasil |
|---|---|
| Product Service mati saat tambah keranjang | User Service membalas 503 |
| Produk tidak ditemukan | 404 diteruskan ke frontend |
| User Service mati saat checkout | Order Service membalas 503 |
| Keranjang kosong saat checkout | 400, struk tidak dibuat |
| Pesanan tersimpan tapi keranjang gagal dikosongkan | 502 dengan data pesanan |

## Cara menjalankan
Windows: klik dua kali `run.bat` (menyalakan 4 server dan membuka browser).
Manual:
```
php -S localhost:5001 -t user-service
php -S localhost:5002 -t product-service
php -S localhost:5003 -t order-service
php -S localhost:8000 -t frontend
```
