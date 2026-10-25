# Posisi Proyek di Dunia Cloud

Proyek ini berjalan di localhost. Dokumen ini menjelaskan di mana posisinya dibanding istilah-istilah cloud, apa yang sudah sesuai, dan langkah menuju cloud sungguhan.

## 1. Peta singkat model layanan cloud

- **IaaS (Infrastructure as a Service):** menyewa mesin virtual atau jaringan. Kita mengurus sistem operasi dan semua yang di atasnya. Contoh: Amazon EC2, Google Compute Engine.
- **PaaS (Platform as a Service):** menyewa platform. Kita cukup mengunggah aplikasi, platform mengurus server dan sistem operasi. Contoh: Heroku, Google App Engine.
- **CaaS (Containers as a Service):** menjalankan container tanpa mengurus mesinnya sendiri. Contoh: layanan container terkelola di berbagai penyedia cloud.
- **FaaS atau serverless:** kita hanya menulis fungsi. Fungsi dijalankan saat ada permintaan, lalu berhenti. Skala otomatis, dan penagihan sesuai pemakaian. Contoh: AWS Lambda, Google Cloud Functions, Azure Functions.
- **SaaS (Software as a Service):** aplikasi jadi yang dipakai langsung. Contoh: Gmail, Google Docs.

## 2. Posisi proyek ini

Proyek ini adalah **aplikasi microservice yang sudah dikemas dalam container Docker, berjalan lokal**. Ia **bukan serverless**, karena:

- ketiga service hidup terus selama dijalankan, bukan dipanggil saat ada permintaan lalu mati,
- kami sendiri yang menyalakan dan mengurus prosesnya (lewat Docker Compose atau `run.bat`),
- datanya disimpan di file lokal (stateful), dan port-nya tetap.

Microservice dan serverless adalah dua hal yang berbeda. Microservice menjawab "kode dipecah menjadi apa saja". Serverless menjawab "kode dijalankan dengan cara apa". Keduanya bisa digabung. Service pada proyek ini sudah berbentuk container, jadi cukup mudah dipindahkan ke layanan container di cloud. Untuk serverless, datanya perlu dipindah dulu dari file lokal ke penyimpanan terkelola.

## 3. Ciri aplikasi cloud-native: sudah atau belum

Sudah:

- dipecah menjadi service kecil dengan tanggung jawab jelas,
- setiap service punya data sendiri,
- komunikasi lewat API HTTP dan JSON,
- kegagalan satu service ditangani dengan pesan error yang jelas dan batas waktu 3 detik,
- bisa dijalankan dan dimatikan per service,
- dikemas dalam container (satu service satu container, dijalankan dengan Docker Compose),
- alamat service dan lokasi data diatur lewat environment variable, bukan tertulis mati di kode,
- data tiap service ada di volume sendiri.

Belum:

- service stateless dengan database terpisah (data masih file JSON di volume),
- endpoint pemeriksaan kesehatan (health check),
- pencatatan log dan pemantauan,
- CI/CD otomatis,
- autentikasi, HTTPS, dan pembatasan CORS.

## 4. Rute menuju cloud

1. **Alamat service lewat environment variable**, bukan tertulis di kode. Ini syarat agar service bisa saling menemukan di dalam container. _(Selesai.)_
2. **Docker Compose:** satu service satu container, dan volume data terpisah per service. _(Selesai.)_
3. **Health check** di tiap service, supaya platform tahu mana yang sehat.
4. **Database per service**, supaya service benar-benar stateless dan bisa digandakan.
5. **Deploy** ke mesin virtual (IaaS) atau layanan container (CaaS atau PaaS).
6. **CI/CD:** pengecekan otomatis setiap push, dan deploy otomatis dari `main`.
7. **Observabilitas:** log terpusat dan pemantauan sederhana.
8. **Keamanan:** autentikasi, HTTPS, CORS dibatasi, dan rahasia disimpan di luar kode.

## 5. Jawaban singkat untuk pertanyaan yang mungkin muncul

- **"Apakah ini serverless?"** Bukan. Ini microservice berbasis proses. Serverless adalah cara menjalankan, dan service ini bisa dipindah ke sana nanti.
- **"Mana cloud-nya?"** Saat ini masih berjalan lokal, tapi sudah dikemas dalam container Docker, yang merupakan syarat utama untuk dipindah ke cloud. Tahap berikutnya adalah deploy ke mesin virtual atau layanan container.
- **"Apa yang bikin ini siap cloud?"** Pemisahan service, data per service, dan komunikasi lewat API. Dan sudah dikemas dalam container dengan konfigurasi lewat environment variable. Yang belum: database terpisah, health check, dan keamanan (login, HTTPS).
- **"IaaS, PaaS, atau SaaS?"** Proyek ini adalah aplikasi yang dibangun di atas salah satu model itu. Kalau di-deploy ke mesin virtual, memakai IaaS. Kalau memakai layanan container atau platform, memakai CaaS atau PaaS. Bagi penggunanya, toko ini tampil sebagai SaaS.
