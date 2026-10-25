<?php
// Zona waktu WIB supaya jam di struk sesuai jam di Indonesia (default PHP: UTC)
date_default_timezone_set('Asia/Jakarta');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Menangani pra-koneksi browser (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ==========================================
// USER SERVICE (:5001) = data pelanggan + keranjang belanja
// Urusan pesanan/struk sudah pindah ke Order Service (:5003).
// Data disimpan di users.json, milik User Service sendiri.
// ==========================================

// "Database" kita: file JSON. Lokasinya bisa diatur lewat environment variable DATA_DIR
// (di Docker: folder /data yang menempel ke volume). Kalau tidak diatur, pakai folder service ini.
$folderData = getenv('DATA_DIR') ?: __DIR__;
$fileUsers  = $folderData . '/users.json';

// Membaca semua data user dari file
function bacaUsers($file) {
    return json_decode(file_get_contents($file), true);
}

// Menyimpan semua data user ke file (LOCK_EX = dikunci saat menulis, anti bentrok)
function simpanUsers($file, $users) {
    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT), LOCK_EX);
}

// Kalau file belum ada, buat pertama kali dengan data awal
if (!file_exists($fileUsers)) {
    $dataAwal = [
        1 => [
            "id" => 1, "nama" => "Budi Santoso", "email" => "budi@email.com", "peran" => "Pelanggan VIP",
            "keranjang" => [
                ["id_produk" => "1111" . time(), "nama" => "Laptop Asus", "harga" => 8500000]
            ]
        ],
        2 => ["id" => 2, "nama" => "Siti Aminah",  "email" => "siti@email.com", "peran" => "Pelanggan Reguler", "keranjang" => []],
        3 => ["id" => 3, "nama" => "Andi Wijaya",  "email" => "andi@email.com", "peran" => "Pelanggan Reguler", "keranjang" => []],
        4 => ["id" => 4, "nama" => "Dewi Sartika", "email" => "dewi@email.com", "peran" => "Pelanggan Reguler", "keranjang" => []]
    ];
    simpanUsers($fileUsers, $dataAwal);
}

// Alamat Product Service. Ibarat nomor telepon gudang di sebelah.
// Dibaca dari environment variable PRODUCT_SERVICE_URL supaya bisa diganti tanpa mengubah kode
// (di Docker: http://product-service:5002/). Kalau tidak diatur, dipakai alamat localhost seperti biasa.
define('PRODUCT_SERVICE_URL', getenv('PRODUCT_SERVICE_URL') ?: 'http://localhost:5002/');

// Fungsi ini "menelepon" Product Service buat nanya detail SATU produk.
// Hasilnya selalu berupa array: ['status' => kode, 'data' => isi jawaban]
//   status 200 = produk ketemu
//   status 404 = produk nggak ada di gudang
//   status 503 = gudangnya nggak bisa dihubungi sama sekali (service mati)
function ambilProduk($produkId) {
    $url = PRODUCT_SERVICE_URL . '?id=' . urlencode($produkId);

    $opsi = stream_context_create([
        'http' => [
            'timeout'       => 3,     // nunggu maksimal 3 detik, jangan menggantung selamanya
            'ignore_errors' => true   // tetap baca isi jawaban walau kodenya 404
        ]
    ]);

    // User Service: "Halo gudang, produk nomor 101 ada?"
    // Tanda @ menyembunyikan peringatan PHP, errornya kita tangani sendiri di bawah.
    $jawaban = @file_get_contents($url, false, $opsi);

    // false = telepon nggak tersambung (Product Service mati / salah alamat)
    if ($jawaban === false) {
        return ['status' => 503, 'data' => null];
    }

    // Baris pertama header jawaban berbentuk "HTTP/1.1 404 Not Found".
    // Kita ambil angka 3 digitnya (kode status).
    $status = 200;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $cocok)) {
        $status = (int)$cocok[1];
    }

    return ['status' => $status, 'data' => json_decode($jawaban, true)];
}

$users  = bacaUsers($fileUsers);
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$method = $_SERVER['REQUEST_METHOD'];

// Semua rute butuh user yang valid
if (!array_key_exists($userId, $users)) {
    http_response_code(404);
    echo json_encode(["pesan" => "Pengguna tidak ditemukan"]);
    exit;
}

// RUTE 1: Ambil profil + isi keranjang (GET)
// Dipakai frontend buat nampilin profil, dan dipakai Order Service buat membaca keranjang.
if ($method === 'GET') {
    echo json_encode($users[$userId]);
    exit;
}

// RUTE 2: Kosongkan keranjang ATAU tambah produk ke keranjang (POST)
if ($method === 'POST') {

    // 2.1 Kosongkan keranjang: ?action=kosongkan
    // Dipanggil ORDER SERVICE setelah pesanan berhasil dicatat.
    // Order Service: "Pesanannya sudah kucatat, tolong kosongkan trolinya."
    // User Service : "Siap, trolinya kukosongkan."
    if (isset($_GET['action']) && $_GET['action'] === 'kosongkan') {
        $users[$userId]['keranjang'] = [];
        simpanUsers($fileUsers, $users);
        echo json_encode($users[$userId]);
        exit;
    }

    // 2.2 Tambah produk ke keranjang.
    // Frontend cukup kirim NOMOR produk: {"produk_id": 101}
    // Nama dan harga TIDAK dipercaya dari frontend, kita tanya langsung ke Product Service.
    $data = json_decode(file_get_contents('php://input'), true);

    // produk_id harus bilangan bulat positif: 101 dan "101" boleh, 101.7 / "abc" / true ditolak.
    $produkId = $data['produk_id'] ?? null;
    $produkId = is_bool($produkId) ? false
              : filter_var($produkId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($produkId === false) {
        http_response_code(400);
        echo json_encode(["pesan" => "produk_id wajib berupa bilangan bulat positif"]);
        exit;
    }

    // Telepon Product Service
    $hasil = ambilProduk($produkId);

    // Kasus 1: telepon nggak tersambung (Product Service mati)
    if ($hasil['status'] === 503) {
        http_response_code(503);
        echo json_encode(["pesan" => "Product service tidak dapat dihubungi"]);
        exit;
    }

    // Kasus 2: produk nggak ada -> teruskan jawaban 404 dari Product Service
    if ($hasil['status'] === 404) {
        http_response_code(404);
        echo json_encode(["pesan" => $hasil['data']['pesan'] ?? "Produk tidak ditemukan"]);
        exit;
    }

    // Kasus 3: jawaban aneh (status bukan 200, atau nggak ada nama/harga)
    $produk = $hasil['data'];
    if ($hasil['status'] !== 200 || !isset($produk['nama']) || !isset($produk['harga'])) {
        http_response_code(502);
        echo json_encode(["pesan" => "Jawaban Product Service tidak valid"]);
        exit;
    }

    // Semua aman -> masukkan ke keranjang pakai data ASLI dari Product Service
    $users[$userId]['keranjang'][] = [
        "id_produk" => uniqid(),            // id BARIS keranjang (dipakai tombol Hapus), JANGAN diubah
        "produk_id" => $produkId,           // nomor produk di toko (milik Product Service)
        "nama"      => $produk['nama'],     // diambil dari Product Service
        "harga"     => (int)$produk['harga']
    ];
    simpanUsers($fileUsers, $users);
    echo json_encode($users[$userId]);
    exit;
}

// RUTE 3: Hapus 1 produk spesifik dari keranjang (DELETE)
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['id_produk'])) {
        $keranjangBaru = [];
        $sudahDihapus  = false;

        foreach ($users[$userId]['keranjang'] as $item) {
            // Lewati (hapus) hanya 1 item yang ID-nya cocok
            if ($item['id_produk'] == $data['id_produk'] && !$sudahDihapus) {
                $sudahDihapus = true;
                continue;
            }
            $keranjangBaru[] = $item;
        }

        $users[$userId]['keranjang'] = $keranjangBaru;
        simpanUsers($fileUsers, $users);
        echo json_encode($users[$userId]);
    } else {
        http_response_code(400);
        echo json_encode(["pesan" => "ID produk tidak valid"]);
    }
    exit;
}
?>