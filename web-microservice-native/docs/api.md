# Dokumentasi API

> Berdasarkan versi awal `main`. Semua endpoint berformat JSON dan mendukung CORS (preflight `OPTIONS` dijawab kosong).
> Cara menjalankan (contoh): `php -S localhost:5001 -t user-service` dan `php -S localhost:5002 -t product-service`.

---

## 1. User Service: `http://localhost:5001`

Data pengguna dummy (disimpan di session PHP): ID 1 Budi Santoso, 2 Siti Aminah, 3 Andi Wijaya, 4 Dewi Sartika. Query `id` default 1.

| Method | URL | Fungsi |
|---|---|---|
| GET | `/?id={id}` | Profil pengguna + isi keranjang |
| POST | `/?id={id}` | Tambah produk ke keranjang |
| POST | `/?id={id}&action=checkout` | Checkout, mengosongkan keranjang |
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
Response `404` jika ID tidak ada: `{ "pesan": "Pengguna tidak ditemukan" }`

### POST `/?id=1` (tambah ke keranjang)
Request:
```json
{ "nama": "Mouse Logitech", "harga": 250000 }
```
Response `200`: objek user lengkap dengan keranjang terbaru (`id_produk` dibuat otomatis).
Response `400` jika `nama`/`harga` kosong: `{ "pesan": "Data produk tidak lengkap" }`

### POST `/?id=1&action=checkout`
Request: tanpa body.
Response `200`: objek user dengan `"keranjang": []`.

### DELETE `/?id=1` (hapus item)
Request:
```json
{ "id_produk": "11111760000000" }
```
Response `200`: objek user dengan keranjang tanpa item tersebut.
Response `400` jika `id_produk` tidak ada: `{ "pesan": "ID produk tidak valid" }`

---

## 2. Product Service: `http://localhost:5002`

Data disimpan di `produk.json` (dibuat otomatis dengan 6 produk default jika belum ada).

| Method | URL | Fungsi |
|---|---|---|
| GET | `/` | Ambil semua produk |
| POST | `/` | Tambah 1 produk baru |

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

### POST `/`
Request:
```json
{ "nama": "Webcam Logitech", "harga": 600000 }
```
Response `200`: seluruh daftar produk terbaru (produk baru di akhir, `id` dibuat otomatis dari waktu + angka acak).
Response `400` jika kosong: `{ "pesan": "Nama atau harga produk tidak boleh kosong!" }`

---

## 3. Contoh uji dengan curl
```bash
curl "http://localhost:5002/"
curl -X POST "http://localhost:5002/" -H "Content-Type: application/json" -d '{"nama":"Webcam","harga":600000}'
curl "http://localhost:5001/?id=1"
curl -X POST "http://localhost:5001/?id=1" -H "Content-Type: application/json" -d '{"nama":"Mouse Logitech","harga":250000}'
curl -X POST "http://localhost:5001/?id=1&action=checkout"
```

## 4. Catatan
- Keranjang di User Service memakai `$_SESSION`. Karena frontend memanggil lewat `fetch` tanpa `credentials: 'include'`, data keranjang bisa tidak bertahan antar request. Perlu dicek saat testing.
- Frontend tidak memakai POST `/` Product Service di tombol "Pilih"; tombol itu memanggil User Service.

## 5. Yang belum ada (isi setelah merge)
- Order Service (:5003): checkout dan riwayat.
- Perubahan User Service ke Product Service lewat `produk_id` (branch Zaka).
- Alur lengkap frontend → user → product pada versi terbaru.
