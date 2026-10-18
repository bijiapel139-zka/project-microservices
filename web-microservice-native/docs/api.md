# Dokumentasi API

> Berdasarkan kode terbaru di branch `main`. Semua endpoint mengembalikan JSON dan mendukung CORS (preflight `OPTIONS` dijawab kosong).
> Frontend: `http://localhost:8000`. Data disimpan di file JSON (`users.json`, `produk.json`, `orders.json`), dibuat otomatis dan tidak ikut Git.

| Service | URL dasar | Data |
|---|---|---|
| User Service | `http://localhost:5001` | `users.json` |
| Product Service | `http://localhost:5002` | `produk.json` |
| Order Service | `http://localhost:5003` | `orders.json` |

---

## 1. Product Service: `http://localhost:5002`

| Method | URL | Fungsi |
|---|---|---|
| GET | `/` | Semua produk |
| GET | `/?id={id}` | Satu produk (dipakai User Service) |
| POST | `/` | Tambah produk baru |

### GET `/`
Response `200`:
```json
[
  { "id": 101, "nama": "Laptop Asus", "harga": 8500000 },
  { "id": 102, "nama": "Mouse Logitech", "harga": 250000 },
  { "id": 103, "nama": "Keyboard Mechanical", "harga": 450000 },
  { "id": 104, "nama": "Monitor Samsung", "harga": 1500000 },
  { "id": 105, "nama": "Headset Razer", "harga": 750000 },
  { "id": 106, "nama": "Kursi Gaming", "harga": 1000000 }
]
```

### GET `/?id=101`
Response `200`:
```json
{ "id": 101, "nama": "Laptop Asus", "harga": 8500000 }
```
Response `404` jika tidak ada: `{ "pesan": "Produk dengan ID 999 tidak ditemukan" }`

### POST `/`
Request:
```json
{ "nama": "Webcam Logitech", "harga": 600000 }
```
Response `200`: seluruh daftar produk terbaru (produk baru di akhir; `id` dibuat otomatis).
Response `400` jika kosong: `{ "pesan": "Nama atau harga produk tidak boleh kosong!" }`

---

## 2. User Service: `http://localhost:5001`

Pengguna awal: ID 1 Budi Santoso, 2 Siti Aminah, 3 Andi Wijaya, 4 Dewi Sartika. Query `id` default 1. Semua rute memberi `404 { "pesan": "Pengguna tidak ditemukan" }` jika ID tidak ada.

| Method | URL | Fungsi |
|---|---|---|
| GET | `/?id={id}` | Profil + isi keranjang (juga dipanggil Order Service) |
| POST | `/?id={id}` | Tambah produk ke keranjang (body: `produk_id`) |
| POST | `/?id={id}&action=kosongkan` | Kosongkan keranjang (dipanggil Order Service) |
| DELETE | `/?id={id}` | Hapus 1 item dari keranjang |

### GET `/?id=1`
Response `200`:
```json
{
  "id": 1,
  "nama": "Budi Santoso",
  "email": "budi@email.com",
  "peran": "Pelanggan VIP",
  "keranjang": [
    { "id_produk": "11111760000000", "nama": "Laptop Asus", "harga": 8500000 }
  ]
}
```

### POST `/?id=1` (tambah ke keranjang)
Frontend hanya mengirim nomor produk. Nama dan harga diambil User Service dari Product Service.
Request:
```json
{ "produk_id": 102 }
```
Response `200`: objek user dengan keranjang terbaru. Item baru berbentuk:
```json
{ "id_produk": "6710a1b2c3d4e", "produk_id": 102, "nama": "Mouse Logitech", "harga": 250000 }
```
(`id_produk` = ID baris keranjang untuk tombol Hapus; `produk_id` = nomor produk di toko.)

Error:
| Kode | Penyebab | Body |
|---|---|---|
| 400 | `produk_id` kosong / bukan angka | `{ "pesan": "produk_id wajib diisi" }` |
| 404 | Produk tidak ada di Product Service | `{ "pesan": "Produk dengan ID ... tidak ditemukan" }` |
| 502 | Jawaban Product Service tidak valid | `{ "pesan": "Jawaban Product Service tidak valid" }` |
| 503 | Product Service mati | `{ "pesan": "Product service tidak dapat dihubungi" }` |

### POST `/?id=1&action=kosongkan`
Request: tanpa body. Response `200`: objek user dengan `"keranjang": []`.

### DELETE `/?id=1` (hapus item)
Request:
```json
{ "id_produk": "6710a1b2c3d4e" }
```
Response `200`: objek user tanpa item tersebut. Response `400` jika `id_produk` tidak ada: `{ "pesan": "ID produk tidak valid" }`

---

## 3. Order Service: `http://localhost:5003`

Semua rute wajib `?user_id={angka >= 1}`, jika tidak: `400 { "pesan": "user_id wajib diisi" }`.

| Method | URL | Fungsi |
|---|---|---|
| GET | `/?user_id={id}` | Riwayat pesanan user (terbaru di atas) |
| POST | `/?user_id={id}` | Checkout: catat pesanan dari keranjang |

### GET `/?user_id=1`
Response `200` (`[]` jika belum ada pesanan):
```json
[
  {
    "id_pesanan": "ORD-1760000000",
    "user_id": 1,
    "tanggal": "2026-10-18 17:00:00",
    "item": [
      { "id_produk": "6710a1b2c3d4e", "produk_id": 102, "nama": "Mouse Logitech", "harga": 250000 }
    ],
    "total": 250000
  }
]
```

### POST `/?user_id=1` (checkout)
Request: tanpa body.
Langkah di server: ambil keranjang dari User Service → hitung total → simpan pesanan → minta User Service mengosongkan keranjang.
Response `201`: objek pesanan seperti contoh di atas.

Error:
| Kode | Penyebab | Body |
|---|---|---|
| 400 | Keranjang kosong | `{ "pesan": "Keranjang masih kosong" }` |
| 404 | User tidak ada | `{ "pesan": "Pengguna tidak ditemukan" }` |
| 502 | Pesanan tersimpan, tapi keranjang gagal dikosongkan | `{ "pesan": "Pesanan tersimpan, tetapi keranjang gagal dikosongkan", "pesanan": { ... } }` |
| 503 | User Service mati | `{ "pesan": "User service tidak dapat dihubungi" }` |
| 405 | Method selain GET/POST | `{ "pesan": "Method tidak didukung" }` |

---

## 4. Alur lengkap yang dipakai frontend

| Aksi di halaman | Request |
|---|---|
| Buka halaman | `GET :5002/` lalu `GET :5001/?id=1` lalu `GET :5003/?user_id=1` |
| Ganti akun | `GET :5001/?id=N` lalu `GET :5003/?user_id=N` |
| Klik "Pilih" produk | `POST :5001/?id=N` dengan `{ "produk_id": ... }` (User Service lalu `GET :5002/?id=...`) |
| Klik "Hapus" | `DELETE :5001/?id=N` |
| Klik "Check Out" | `POST :5003/?user_id=N` (Order Service lalu `GET :5001/?id=N` dan `POST :5001/?id=N&action=kosongkan`) |

## 5. Contoh uji dengan curl
```bash
curl "http://localhost:5002/"
curl "http://localhost:5002/?id=101"
curl -X POST "http://localhost:5001/?id=1" -H "Content-Type: application/json" -d '{"produk_id":102}'
curl "http://localhost:5001/?id=1"
curl -X POST "http://localhost:5003/?user_id=1"
curl "http://localhost:5003/?user_id=1"
# Uji error
curl "http://localhost:5002/?id=999"
curl -X POST "http://localhost:5003/?user_id=2"
```
