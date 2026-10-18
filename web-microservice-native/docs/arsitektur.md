# Arsitektur Aplikasi E-Commerce Mikroservis (PHP Native)

> Ditulis dari kode versi awal `main` (frontend, user-service, product-service).
> Order Service (:5003) dan integrasi user-service → product-service (versi Zaka/Dimas) belum dimasukkan. Lihat bagian "Rencana update".

## Komponen

| Komponen | Port | Teknologi | Tugas | Penyimpanan data |
|---|---|---|---|---|
| Frontend | (dibuka langsung `index.html`) | HTML + JavaScript (fetch) | Dashboard: profil, keranjang, daftar produk | - |
| User Service | 5001 | PHP native | Profil pengguna, keranjang, checkout | `$_SESSION` (memori server, sementara) |
| Product Service | 5002 | PHP native | Daftar produk, tambah produk | File `produk.json` |

Semua service mengembalikan JSON dan mengaktifkan CORS (`Access-Control-Allow-Origin: *`) supaya bisa dipanggil dari browser.

## Diagram alur

```mermaid
flowchart LR
    B[Browser<br/>Frontend index.html]
    U[User Service<br/>:5001]
    P[Product Service<br/>:5002]
    S[(Session PHP)]
    J[(produk.json)]

    B -- "GET /?id= (profil + keranjang)" --> U
    B -- "POST /?id= (tambah ke keranjang)" --> U
    B -- "DELETE /?id= (hapus item)" --> U
    B -- "POST /?id=&action=checkout" --> U
    U --- S

    B -- "GET / (daftar produk)" --> P
    B -- "POST / (tambah produk)" --> P
    P --- J
```

## Alur utama

### 1. Membuka dashboard
```mermaid
sequenceDiagram
    participant F as Frontend
    participant U as User Service :5001
    participant P as Product Service :5002
    F->>P: GET /
    P-->>F: [daftar produk]
    F->>U: GET /?id=1
    U-->>F: profil + keranjang
```

### 2. Memilih produk ke keranjang
```mermaid
sequenceDiagram
    participant F as Frontend
    participant U as User Service :5001
    Note over F: Klik "Pilih" pada produk dari Product Service
    F->>U: POST /?id=1 {nama, harga}
    U-->>F: data user terbaru (keranjang bertambah)
```
Catatan: pada versi awal, frontend mengirim `nama` dan `harga` produk ke User Service. User Service tidak memanggil Product Service.

### 3. Checkout
```mermaid
sequenceDiagram
    participant F as Frontend
    participant U as User Service :5001
    F->>U: POST /?id=1&action=checkout
    U-->>F: data user (keranjang dikosongkan)
```

### 4. Menambah produk ke toko
```mermaid
sequenceDiagram
    participant F as Frontend
    participant P as Product Service :5002
    F->>P: POST / {nama, harga}
    P->>P: simpan ke produk.json
    P-->>F: daftar produk terbaru
```

## Rencana update (isi setelah merge)
- Order Service (:5003): checkout dan riwayat pesanan.
- User Service memanggil Product Service lewat HTTP memakai `produk_id`.
- Perbarui diagram di atas setelah branch Zaka/Dimas di-merge.
