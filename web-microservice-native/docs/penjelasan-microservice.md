# Toko Mikroservis: Penjelasan Sistem

Dokumen ini menjelaskan sistem dengan bahasa sederhana. Pakai bersama diagram `arsitektur.drawio` (3 halaman) untuk presentasi.

## 1. Gambaran singkat

Toko online kecil yang dipecah menjadi empat bagian yang berdiri sendiri. Bayangkan toko sungguhan:

- **Etalase** (Frontend): tempat pembeli melihat barang dan menekan tombol.
- **Gudang** (Product Service): tahu daftar produk, nama, dan harga aslinya.
- **Bagian pelanggan** (User Service): menyimpan data pelanggan dan isi troli belanja.
- **Kasir** (Order Service): memproses pembayaran dan menyimpan struk.

Tiap bagian punya tugas sendiri, dan kalau butuh sesuatu dari bagian lain, dia "menelepon" bagian itu lewat HTTP.

## 2. Apa itu microservice?

Microservice adalah cara membangun aplikasi dengan memecahnya menjadi beberapa layanan kecil. Tiap layanan:

- punya **satu tugas** yang jelas,
- jalan sebagai **proses sendiri** (di sini: port sendiri),
- punya **data sendiri** (di sini: file JSON sendiri),
- berbicara dengan layanan lain lewat **HTTP + JSON**.

Lawannya adalah monolith: semua fitur dalam satu program besar. Di monolith, satu bagian yang rusak bisa menjatuhkan semuanya. Di microservice, satu bagian mati, bagian lain tetap hidup dan bisa memberi pesan error yang jelas.

## 3. Empat bagian sistem

### Frontend (port 8000)

- Tugas: menampilkan halaman dan meneruskan klik pengguna ke service yang tepat.
- Berkas: `index.html` (struktur), `style.css` (tampilan), `script.js` (logika dan panggilan API), `tema.js` (ganti tema).
- Tidak menyimpan data apa pun. Semua data diminta ke service lewat `fetch`.

### User Service (port 5001)

- Tugas: data pelanggan dan keranjang belanja.
- Data: `users.json`.
- Endpoint:
  - `GET /?id=1` mengambil profil dan isi keranjang.
  - `POST /?id=1` dengan body `{"produk_id": 101}` menambah produk ke keranjang. Nama dan harga **diambil dari Product Service**, bukan dari kiriman frontend.
  - `DELETE /?id=1` dengan body `{"id_produk": "..."}` menghapus satu baris keranjang.
  - `POST /?id=1&action=kosongkan` mengosongkan keranjang (dipanggil Order Service setelah pesanan dicatat).

### Product Service (port 5002)

- Tugas: daftar produk dan harga yang sah.
- Data: `produk.json`.
- Endpoint:
  - `GET /` mengambil semua produk.
  - `GET /?id=101` mengambil satu produk. Kalau tidak ada, balasannya 404 dengan pesan yang jelas.
  - `POST /` dengan body `{"nama": "...", "harga": 450000}` menambah produk baru.

### Order Service (port 5003)

- Tugas: checkout dan arsip pesanan (struk).
- Data: `orders.json`.
- Endpoint:
  - `GET /?user_id=1` mengambil riwayat pesanan satu pelanggan, terbaru di atas.
  - `POST /?user_id=1` melakukan checkout (lihat alur di bagian 5).

## 4. Teknologi yang dipakai

- **PHP** untuk ketiga service, dijalankan dengan server bawaan PHP (`php -S`) di dalam masing-masing container.
- **File JSON** sebagai penyimpanan data. Sederhana dan cukup untuk tugas ini.
- **HTML, CSS, dan JavaScript biasa** untuk frontend (tanpa framework). Komunikasi ke service memakai `fetch`.
- **HTTP + JSON** sebagai cara antar service berbicara (gaya REST).
- **Docker dan Docker Compose**: tiap service dikemas dalam container sendiri, dan satu perintah (`docker compose up --build`) menyalakan semuanya. Datanya disimpan di volume terpisah per service.
- **`run.bat`** sebagai cara cadangan di Windows tanpa Docker, dan **Git + GitHub** untuk kerja tim.

## 5. Cara kerja (empat alur)

### Alur A: membuka halaman

1. Browser membuka `localhost:8000`, lalu `script.js` jalan.
2. Frontend meminta daftar produk ke Product Service (`:5002`).
3. Frontend meminta profil dan keranjang ke User Service (`:5001`).
4. Frontend meminta riwayat pesanan ke Order Service (`:5003`).

### Alur B: menambah produk ke keranjang (diagram halaman 2)

1. Pengguna klik **Pilih**. Frontend mengirim **hanya nomor produk** ke User Service: `{"produk_id": 101}`.
2. User Service bertanya ke Product Service: `GET /?id=101`.
3. Product Service menjawab `{id, nama, harga}`.
4. User Service menyimpan baris keranjang (dengan nama dan harga asli) ke `users.json`.
5. User Service membalas ke frontend, dan frontend menampilkan keranjang.

Kenapa harga tidak dikirim frontend? Karena data dari browser bisa diubah siapa saja. Harga yang sah selalu datang dari pemiliknya, yaitu Product Service.

### Alur C: menghapus dari keranjang

1. Pengguna klik **Hapus**. Frontend mengirim `DELETE` ke User Service dengan id baris keranjang.
2. User Service membuang satu baris itu dan membalas keranjang terbaru.

### Alur D: checkout (diagram halaman 3)

1. Pengguna klik **Check Out**. Frontend mengirim `POST` ke Order Service.
2. Order Service meminta isi keranjang ke User Service.
3. Order Service menghitung total, membuat struk (`ORD-...`), dan menyimpannya ke `orders.json`.
4. Order Service meminta User Service mengosongkan keranjang.
5. Order Service membalas 201 berisi nomor pesanan. Frontend menampilkan nomornya dan memuat ulang keranjang serta riwayat.

Urutan sengaja begitu: **pesanan dicatat dulu, baru keranjang dikosongkan**. Lebih baik pesanan tercatat dengan keranjang masih penuh, daripada keranjang kosong tetapi pesanan hilang.

### Kalau ada yang gagal

- Product Service mati saat menambah ke keranjang: User Service membalas 503, "Product service tidak dapat dihubungi".
- Produk tidak ada: balasan 404 diteruskan ke frontend.
- Keranjang kosong saat checkout: Order Service membalas 400.
- User Service mati saat checkout: Order Service membalas 503, "User service tidak dapat dihubungi".
- Pesanan tercatat tetapi keranjang gagal dikosongkan: balasan 502 dengan penjelasan, dan pesanan tetap tersimpan.
- Semua panggilan antar service punya batas waktu 3 detik, jadi tidak menggantung selamanya.

## 6. Bukti bahwa ini benar-benar microservice

Cara paling meyakinkan adalah mematikan satu service saat demo:

- Matikan **Product Service** (tutup jendelanya, atau `docker stop toko-product-service`), lalu klik **Pilih**. Muncul pesan error yang jelas, dan bagian lain tetap hidup (profil, riwayat, tema).
- Matikan **User Service** (tutup jendelanya, atau `docker stop toko-user-service`), lalu klik **Check Out**. Order Service membalas bahwa User Service tidak dapat dihubungi.
- Setiap service punya file data sendiri (di Docker: volume sendiri), dan tidak ada yang membaca file milik service lain. Mau tahu data produk? Tanya ke Product Service lewat HTTP.

## 7. Keterbatasan dan rencana pengembangan

Keterbatasan yang jujur:

- Penyimpanan memakai file JSON, bukan database sungguhan. Cukup untuk demo, kurang untuk banyak pengguna.
- Belum ada login. Pelanggan dipilih lewat dropdown.
- Service saling bergantung secara langsung: User Service butuh Product Service untuk menambah keranjang, dan Order Service butuh User Service untuk checkout. Dampaknya diperkecil dengan batas waktu dan pesan error yang jelas.
- Checkout terdiri dari dua langkah yang tidak atomik. Kalau langkah kedua gagal, pesanan tetap tercatat dan pesan error menjelaskannya.
- Harga disimpan di keranjang saat produk ditambahkan. Kalau harga produk berubah sesudahnya, keranjang lama tidak ikut berubah.
- Frontend memanggil tiga service langsung (belum ada API gateway), dan CORS masih dibuka untuk semua asal.
- Tombol "Tambah produk" di web masih dalam pengerjaan. Backend-nya (`POST` ke Product Service) sudah siap.
- Server bawaan PHP (`php -S`) cocok untuk pengembangan, belum untuk produksi.

Rencana:

- Database sendiri untuk tiap service (misalnya MySQL atau SQLite).
- Login dan token, stok produk, serta jumlah (qty) per item.
- API gateway sebagai satu pintu masuk untuk frontend.
- Health check di tiap service, lalu deploy ke mesin virtual atau layanan container.

## 8. Cara menjalankan (ringkas)

**Dengan Docker (disarankan):**

1. Pastikan Docker Desktop sudah menyala.
2. Di folder yang berisi `docker-compose.yml`, jalankan `docker compose up --build`.
3. Buka `http://localhost:8000`.
4. Untuk mematikan: Ctrl+C, lalu `docker compose down`.

**Tanpa Docker:**

1. Pastikan PHP terpasang (`php -v`).
2. Klik dua kali `run.bat`. Empat jendela terbuka: User `:5001`, Product `:5002`, Order `:5003`, dan Frontend `:8000`.
3. Buka `http://localhost:8000`.

Pilih salah satu cara saja, karena port-nya sama. Tanpa Docker, `users.json`, `produk.json`, dan `orders.json` dibuat otomatis saat pertama kali dijalankan dan tidak ikut Git. Dengan Docker, data disimpan di volume masing-masing service.

## 9. Kemungkinan pertanyaan dosen

- **Kenapa dipecah menjadi tiga service?** Supaya tiap bagian punya tugas dan data sendiri, bisa dikembangkan dan dimatikan sendiri-sendiri, dan satu bagian rusak tidak menjatuhkan semuanya.
- **Bagaimana service saling bicara?** Lewat HTTP dengan format JSON. Contoh: User Service memanggil `GET :5002/?id=101`.
- **Apa yang terjadi kalau Product Service mati?** Menambah ke keranjang gagal dengan pesan jelas (503). Fitur yang tidak butuh Product Service tetap jalan.
- **Kenapa data tidak dalam satu database?** Tiap service memiliki datanya sendiri, sehingga tidak saling merusak dan nanti bisa memakai jenis database berbeda.
- **Kenapa harga tidak diambil dari frontend?** Karena kiriman dari browser tidak bisa dipercaya. Harga yang sah milik Product Service.
- **Apa kelemahan microservice?** Lebih banyak bagian yang harus dijalankan, ada risiko jaringan antar service, dan debugging lebih rumit daripada satu program.
- **Kenapa pakai file JSON?** Supaya sederhana untuk tugas. Untuk produksi diganti database.
- **Apa gunanya Docker di sini?** Tiap service dikemas dalam container sendiri, jadi bisa dijalankan di laptop siapa saja dengan satu perintah tanpa memasang PHP. Container juga memperkuat pemisahan: tiap service punya proses, alamat, dan volume data sendiri.
- **Kalau container dihapus, datanya hilang?** Tidak, karena data ada di volume. Data baru hilang kalau volume sengaja dihapus (`docker compose down -v`).

## 10. Kerangka presentasi 5 sampai 7 menit

1. Tujuan: toko online kecil dengan arsitektur microservice.
2. Apa itu microservice (bagian 2) dan analogi toko (bagian 1).
3. Arsitektur: tampilkan diagram halaman 1.
4. Alur tambah ke keranjang: diagram halaman 2.
5. Alur checkout: diagram halaman 3.
6. Demo langsung (urutan di bawah).
7. Keterbatasan dan rencana (database per service, login, deploy ke cloud).
8. Penutup dan tanya jawab.

Urutan demo:

1. Jalankan `docker compose up --build` (atau klik `run.bat`), lalu tunjukkan keempat service berjalan (`docker compose ps`).
2. Buka `localhost:8000`, tunjukkan produk dan akun.
3. Klik **Pilih**. Buka DevTools (F12) tab Network dan tunjukkan bahwa body hanya berisi `produk_id`.
4. Matikan Product Service (`docker stop toko-product-service`, atau tutup jendelanya), klik **Pilih**, tunjukkan error yang jelas. Nyalakan lagi.
5. Klik **Check Out**, tunjukkan nomor pesanan dan riwayat. Kalau jalan lewat `run.bat`, buka juga `orders.json`.
6. Matikan User Service (`docker stop toko-user-service`, atau tutup jendelanya), klik **Check Out**, tunjukkan pesan "User service tidak dapat dihubungi".
7. Bonus: ganti tema, lalu buat tema kustom dari 25 warna.
8. Bonus Docker: `docker compose down`, lalu `docker compose up`. Riwayat pesanan masih ada karena datanya di volume.

## 11. Cara membuka diagram

1. Di VS Code, pasang ekstensi **Draw.io Integration**.
2. Buka file `docs/arsitektur.drawio`. Editor draw.io muncul, dan ada tiga tab halaman di bawah: Arsitektur, Tambah ke Keranjang, dan Checkout.
3. Untuk dimasukkan ke slide: menu **File → Export as → PNG** (atau SVG), pilih halaman yang diinginkan.
4. Kotak dan panah bisa digeser atau diedit langsung kalau mau mengubah warna dan tata letak.
