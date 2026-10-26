# Dokumentasi API

Semua service membalas JSON. Pesan error selalu berbentuk `{"pesan": "..."}`. Contoh di bawah sudah dicoba langsung terhadap service yang berjalan.

## Alamat dasar

- User Service: `http://localhost:5001/`
- Product Service: `http://localhost:5002/`
- Order Service: `http://localhost:5003/`

CORS dibuka untuk semua asal (`Access-Control-Allow-Origin: *`), supaya frontend di port 8000 boleh memanggil ketiganya.

## Kode status yang dipakai

- **200** berhasil.
- **201** berhasil membuat data baru (checkout).
- **400** permintaan tidak valid (data kurang atau salah format).
- **404** data yang dicari tidak ada.
- **405** method HTTP tidak didukung.
- **502** jawaban dari service lain tidak valid, atau langkah lanjutan gagal.
- **503** service tujuan tidak dapat dihubungi.

## Cara mencoba

Buka `http://localhost:8000`, tekan F12, pilih tab **Console**, lalu tempel contoh seperti ini:

```js
fetch('http://localhost:5002/?id=101').then(r => r.json()).then(console.log)
```

Untuk request GET sederhana, boleh juga langsung ditulis di address bar browser.

## User Service (port 5001)

Data pelanggan dan keranjang. Datanya di `users.json`. Parameter `id` adalah nomor pelanggan (1 sampai 4). Kalau `id` tidak diisi, dianggap 1.

### GET /?id=1
Mengambil profil dan isi keranjang.

Balasan 200:
```json
{
  "id": 1,
  "nama": "Budi Santoso",
  "email": "budi@email.com",
  "peran": "Pelanggan VIP",
  "keranjang": [
    { "id_produk": "6ad47450b34c0", "produk_id": 102, "nama": "Mouse Logitech", "harga": 250000 }
  ]
}
```
Catatan: `id_produk` adalah nomor baris di keranjang (dipakai untuk menghapus), sedangkan `produk_id` adalah nomor produk di toko.

Error: 404 `{"pesan": "Pengguna tidak ditemukan"}`.

### POST /?id=1
Menambah produk ke keranjang. Body:
```json
{ "produk_id": 102 }
```
User Service menanyakan nama dan harga ke Product Service, lalu menyimpan baris keranjangnya. Balasan 200 berupa data pelanggan lengkap (sama seperti GET).

Error:
- 400 `{"pesan": "produk_id wajib berupa bilangan bulat positif"}` (kosong, 101.7, "abc", true, 0, atau negatif).
- 404 `{"pesan": "Produk dengan ID 99999 tidak ditemukan"}` (diteruskan dari Product Service).
- 503 `{"pesan": "Product service tidak dapat dihubungi"}`.
- 502 `{"pesan": "Jawaban Product Service tidak valid"}`.

### DELETE /?id=1
Menghapus satu baris keranjang. Body:
```json
{ "id_produk": "6ad47450b34c0" }
```
Balasan 200 berupa data pelanggan terbaru. Kalau `id_produk` tidak cocok dengan baris mana pun, keranjang tidak berubah dan tetap dibalas 200.

Error: 400 `{"pesan": "ID produk tidak valid"}` kalau `id_produk` tidak dikirim.

### POST /?id=1&action=kosongkan
Mengosongkan keranjang. Dipanggil Order Service setelah pesanan dicatat. Balasan 200 berupa data pelanggan dengan `"keranjang": []`.

## Product Service (port 5002)

Daftar produk. Datanya di `produk.json`. Data awal: enam produk dengan id 101 sampai 106.

### GET /
Mengambil semua produk.

Balasan 200:
```json
[
  { "id": 101, "nama": "Laptop Asus", "harga": 8500000 },
  { "id": 102, "nama": "Mouse Logitech", "harga": 250000 }
]
```

### GET /?id=101
Mengambil satu produk.

Balasan 200:
```json
{ "id": 101, "nama": "Laptop Asus", "harga": 8500000 }
```

Error: 404 `{"pesan": "Produk dengan ID 99999 tidak ditemukan"}`. Kalau `id` bukan angka (misalnya `abc`), dianggap 0 dan hasilnya juga 404.

### POST /
Menambah produk baru. Body:
```json
{ "nama": "Webcam HD", "harga": 350000 }
```
`harga` harus bilangan bulat 0 atau lebih. Nama disimpan setelah di-escape (`htmlspecialchars`). ID baru adalah ID terbesar yang ada ditambah 1 (misalnya 107).

Balasan 200 berupa **seluruh daftar produk terbaru** (produk baru di urutan terakhir).

Error: 400 `{"pesan": "Nama wajib diisi dan harga harus bilangan bulat >= 0"}`.

Catatan keamanan: endpoint ini belum memakai login, jadi siapa pun bisa menambah produk. Ini batasan yang diakui (lihat dokumen skalabilitas dan ketahanan).

## Order Service (port 5003)

Checkout dan arsip pesanan. Datanya di `orders.json`. Semua rute wajib memakai parameter `user_id`.

Error umum: 400 `{"pesan": "user_id wajib diisi"}` kalau `user_id` kosong, bukan angka, atau kurang dari 1.

### GET /?user_id=1
Mengambil riwayat pesanan satu pelanggan, terbaru di atas. Kalau belum ada pesanan, balasannya `[]` (bukan error).

Balasan 200:
```json
[
  {
    "id_pesanan": "ORD-20261024175509-A3F1",
    "user_id": 1,
    "tanggal": "2026-10-24 17:55:09",
    "item": [
      { "id_produk": "6ad47450b34c0", "produk_id": 102, "nama": "Mouse Logitech", "harga": 250000 }
    ],
    "total": 250000
  }
]
```
Nomor pesanan berformat `ORD-` + tanggal dan jam + 4 karakter acak. Pesanan lama dari versi sebelumnya masih berformat `ORD-` + angka detik.

### POST /?user_id=1
Checkout. Tidak butuh body. Order Service mengambil keranjang dari User Service, mencatat pesanan, lalu meminta User Service mengosongkan keranjang.

Balasan 201: satu pesanan (bentuknya sama seperti satu elemen riwayat di atas).

Error:
- 400 `{"pesan": "Keranjang masih kosong"}`.
- 404 `{"pesan": "Pengguna tidak ditemukan"}`.
- 503 `{"pesan": "User service tidak dapat dihubungi"}`.
- 502 `{"pesan": "Jawaban User Service tidak valid"}`.
- 502 `{"pesan": "Pesanan tersimpan, tetapi keranjang gagal dikosongkan", "pesanan": { ... }}`. Pesanan tetap tercatat, hanya pengosongan keranjang yang gagal.

Method selain GET dan POST dibalas 405 `{"pesan": "Method tidak didukung"}`.

## Alur antar service

- Tambah ke keranjang: frontend → User Service → Product Service (`GET /?id=`).
- Checkout: frontend → Order Service → User Service (`GET /?id=`, lalu `POST /?id=&action=kosongkan`).
- Frontend tidak pernah memanggil service lain atas nama service yang sedang diminta. Panggilan antar service punya batas waktu 3 detik.

## Contoh uji dengan curl

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