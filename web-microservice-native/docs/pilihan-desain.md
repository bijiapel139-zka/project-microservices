# Pilihan Desain dan Alasannya

Dokumen ini menjawab dua pertanyaan: "kenapa dibuat begini?" dan "kenapa tidak pakai X?". Setiap keputusan ditulis dengan alasan, kompromi yang diterima, dan rencana ke depan.

## 1. Dipecah menjadi tiga service

- **Keputusan:** produk, pelanggan (beserta keranjang), dan pesanan masing-masing menjadi service sendiri: Product (5002), User (5001), Order (5003).
- **Alasan:** batasnya mengikuti tugas bisnis. Satu service punya satu tanggung jawab dan satu pemilik data. Perubahan di satu bagian tidak memaksa bagian lain ikut berubah.
- **Kompromi:** lebih banyak bagian yang harus dijalankan, dan ada panggilan antar service yang bisa gagal.
- **Kalau ditanya "kenapa tidak satu program saja?":** satu program (monolith) memang lebih mudah dibuat. Tapi tujuan tugas ini adalah memperlihatkan pemisahan layanan, kegagalan yang terisolasi, dan komunikasi antar layanan.

## 2. PHP native tanpa framework

- **Keputusan:** ketiga service ditulis dengan PHP biasa, tanpa Laravel atau framework lain.
- **Alasan:** mekanisme dasarnya terlihat jelas: membaca request, memilih rute, mengirim JSON, mengatur status HTTP. Mudah dijelaskan baris demi baris.
- **Kompromi:** validasi, routing, dan keamanan harus ditulis sendiri, jadi belum selengkap framework.
- **Kalau ditanya "kenapa tidak Laravel atau Express?":** framework mempercepat pekerjaan, tapi menyembunyikan hal yang ingin kami tunjukkan. Konsep microservice tidak bergantung pada bahasa atau framework.

## 3. File JSON sebagai penyimpanan, satu file per service

- **Keputusan:** `users.json`, `produk.json`, dan `orders.json`, masing-masing milik satu service.
- **Alasan:** tanpa instalasi database, jalan di laptop siapa saja, dan isinya bisa dibuka langsung saat demo. Satu file per service menjaga prinsip bahwa setiap service memiliki datanya sendiri dan tidak ada service yang membaca file milik service lain.
- **Kompromi:** bukan database sungguhan. Tidak ada transaksi, dan dua permintaan bersamaan bisa saling menimpa (lihat dokumen skalabilitas).
- **Rencana:** database terpisah untuk tiap service (misalnya SQLite atau MySQL).
- **Kalau ditanya "kenapa tidak database?":** untuk menjaga fokus pada arsitektur. Pola "satu service, satu data" sudah diterapkan, jadi mengganti JSON dengan database hanya mengubah lapisan penyimpanan.

## 4. Komunikasi sinkron lewat HTTP dan JSON

- **Keputusan:** antar service saling memanggil dengan permintaan HTTP biasa dan menjawab dengan JSON.
- **Alasan:** sederhana, bisa dicoba dari browser atau Console, dan alurnya mudah digambar.
- **Kompromi:** pemanggil harus menunggu jawaban. Kalau service tujuan mati, pemanggil ikut gagal. Karena itu setiap panggilan punya batas waktu 3 detik dan pesan error yang jelas.
- **Kalau ditanya "kenapa tidak pakai message queue (RabbitMQ, Kafka)?":** antrian membuat sistem lebih tahan gagal dan longgar ketergantungannya, tapi menambah komponen dan kerumitan. Untuk skala tugas ini HTTP cukup, dan antrian menjadi pengembangan lanjutan.

## 5. Harga diambil dari Product Service, bukan dari frontend

- **Keputusan:** frontend hanya mengirim `produk_id`. Nama dan harga selalu ditanyakan User Service ke Product Service.
- **Alasan:** data dari browser bisa diubah siapa saja. Harga yang sah harus datang dari pemiliknya.
- **Kompromi:** setiap penambahan ke keranjang butuh satu panggilan antar service. Harga lalu disimpan di baris keranjang (snapshot), jadi perubahan harga setelahnya tidak memengaruhi keranjang lama.

## 6. Checkout dikoordinasi Order Service

- **Keputusan:** checkout ditangani Order Service. Dia meminta isi keranjang ke User Service, mencatat pesanan, lalu meminta User Service mengosongkan keranjang.
- **Alasan:** urusan pesanan terkumpul di satu tempat. User Service tidak perlu tahu cara membuat struk.
- **Urutan sengaja:** pesanan dicatat dulu, baru keranjang dikosongkan. Lebih baik pesanan tercatat dengan keranjang masih penuh daripada keranjang kosong tapi pesanan hilang.
- **Kompromi:** dua langkah itu tidak atomik. Kalau langkah kedua gagal, pesanan tetap tersimpan dan balasannya (502) menjelaskan keadaannya.
- **Rencana:** pola saga atau antrian untuk menangani kegagalan di tengah proses.

## 7. Frontend memanggil tiga service langsung

- **Keputusan:** browser berbicara ke port 5001, 5002, dan 5003 secara langsung. CORS dibuka supaya itu diizinkan.
- **Alasan:** paling sederhana, dan alurnya terlihat jelas di tab Network.
- **Kompromi:** frontend harus tahu alamat tiga service, dan CORS terbuka untuk semua asal kurang aman.
- **Kalau ditanya "kenapa tidak API Gateway?":** gateway menyatukan pintu masuk, menangani autentikasi, dan menyembunyikan alamat service. Itu pengembangan lanjutan yang masuk akal setelah autentikasi ada.

## 8. Dijalankan dengan Docker Compose

- **Keputusan:** tiap service dikemas dalam container sendiri (PHP untuk tiga service, nginx untuk frontend), dan `docker-compose.yml` menyalakan semuanya dengan satu perintah. `run.bat` tetap ada sebagai cara cadangan tanpa Docker.
- **Alasan:** hasilnya sama di laptop siapa saja dan tidak perlu memasang PHP. Tiap service juga jelas terlihat sebagai unit yang terpisah, dengan proses, port, dan data sendiri.
- **Kompromi:** butuh Docker Desktop. Server bawaan PHP (`php -S`) di dalam container tetap server pengembangan, bukan untuk produksi.
- **Kalau ditanya "kenapa tidak Kubernetes?":** Kubernetes berlebihan untuk tiga service. Ia lebih cocok untuk produksi dengan banyak instance. Docker Compose sudah cukup untuk kebutuhan ini.
- **Rencana:** deploy container ke mesin virtual atau layanan container di cloud.

## 9. Frontend HTML, CSS, dan JavaScript biasa

- **Keputusan:** tanpa React atau framework frontend. Tema memakai variabel CSS, dan ganti tema ditangani `tema.js` terpisah dari logika toko di `script.js`.
- **Alasan:** fokus tugas ada di sisi service. Memisahkan logika dan tampilan juga memudahkan kerja tim tanpa saling menimpa berkas.
- **Kompromi:** tampilan dibuat manual, jadi komponen tidak bisa dipakai ulang semudah di framework.

## 10. Alur kerja Git

- **Keputusan:** satu branch per fitur, `main` hanya menerima yang sudah dites, dan titik aman diberi tag (`stabil-v1`, `stabil-v2`, ...).
- **Alasan:** `main` selalu bisa dijalankan. Kalau sebuah fitur merusak sesuatu, kembali ke tag terakhir yang stabil.
- **Kompromi:** butuh disiplin merge dan tes sebelum menggabungkan.

## 11. Alamat service lewat environment variable

- **Keputusan:** alamat service lain dan lokasi data dibaca dari environment variable (`USER_SERVICE_URL`, `PRODUCT_SERVICE_URL`, `DATA_DIR`). Kalau tidak diatur, dipakai nilai bawaan (`localhost` dan folder service itu sendiri).
- **Alasan:** di dalam container, `localhost` berarti container itu sendiri, jadi service harus memanggil lewat nama service di Docker (misalnya `http://product-service:5002/`). Dengan environment variable, kode yang sama jalan di `run.bat` maupun di Docker tanpa diubah.
- **Kompromi:** ada satu hal lagi yang harus dikonfigurasi dan diingat.

## 12. Satu volume data per service

- **Keputusan:** tiap service punya volume sendiri (`product-data`, `user-data`, `order-data`) yang menempel ke folder `/data` di container.
- **Alasan:** data tidak hilang saat container dibuat ulang, dan prinsip "satu service, satu data" tetap terjaga: tidak ada service yang bisa membaca file milik service lain.
- **Kompromi:** data masih berupa file JSON, jadi batasan soal transaksi dan permintaan bersamaan tetap ada. Untuk reset data, pakai `docker compose down -v`.
