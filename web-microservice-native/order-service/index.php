<?php
// ==========================================
// ORDER SERVICE (:5003) = bagian kasir + arsip struk
//   GET  /?user_id=1  -> daftar pesanan milik user itu (terbaru di atas)
//   POST /?user_id=1  -> CHECKOUT: ambil keranjang dari User Service,
//                        catat pesanan, lalu minta User Service kosongkan keranjang
// Data disimpan di orders.json, milik Order Service sendiri.
// ==========================================

date_default_timezone_set('Asia/Jakarta');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Menangani pra-koneksi browser (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Alamat User Service. Ibarat nomor telepon bagian pelanggan.
const USER_SERVICE_URL = 'http://localhost:5001/';

// "Arsip" kita: file JSON di folder yang sama
$fileOrders = __DIR__ . '/orders.json';

function bacaOrders($file) {
    return json_decode(file_get_contents($file), true);
}

// LOCK_EX = file dikunci saat menulis, anti bentrok
function simpanOrders($file, $orders) {
    file_put_contents($file, json_encode($orders, JSON_PRETTY_PRINT), LOCK_EX);
}

// Kalau arsip belum ada, buat dulu (isinya kosong)
if (!file_exists($fileOrders)) {
    simpanOrders($fileOrders, []);
}

// Fungsi "menelepon" service lain. Hasilnya selalu: ['status' => kode, 'data' => isi jawaban]
//   status 503 = telepon nggak tersambung (service tujuan mati)
function panggilService($url, $method = 'GET') {
    $opsi = stream_context_create([
        'http' => [
            'method'        => $method,
            'header'        => "Content-Type: application/json\r\n",
            'content'       => ($method === 'POST') ? '{}' : '',
            'timeout'       => 3,      // nunggu maksimal 3 detik
            'ignore_errors' => true    // tetap baca isi jawaban walau kodenya 404/400
        ]
    ]);

    $jawaban = @file_get_contents($url, false, $opsi);

    if ($jawaban === false) {
        return ['status' => 503, 'data' => null];
    }

    // Ambil kode status 3 digit dari baris pertama header, misal "HTTP/1.1 404 Not Found"
    $status = 200;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $cocok)) {
        $status = (int)$cocok[1];
    }

    return ['status' => $status, 'data' => json_decode($jawaban, true)];
}

$orders = bacaOrders($fileOrders);
$method = $_SERVER['REQUEST_METHOD'];

// Semua rute butuh user_id yang valid
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id']) || (int)$_GET['user_id'] < 1) {
    http_response_code(400);
    echo json_encode(["pesan" => "user_id wajib diisi"]);
    exit;
}
$userId = (int)$_GET['user_id'];

// RUTE 1: Riwayat pesanan satu user (GET)
if ($method === 'GET') {
    $milikUser = [];
    foreach ($orders as $pesanan) {
        if ((int)$pesanan['user_id'] === $userId) {
            $milikUser[] = $pesanan;
        }
    }
    echo json_encode($milikUser);   // kalau belum ada pesanan -> [] (bukan error)
    exit;
}

// RUTE 2: Checkout (POST)
if ($method === 'POST') {

    // Langkah 1: tanya User Service, isi keranjang user ini apa?
    // Order Service: "Halo bagian pelanggan, troli pelanggan nomor 1 isinya apa?"
    $hasil = panggilService(USER_SERVICE_URL . '?id=' . $userId);

    if ($hasil['status'] === 503) {
        http_response_code(503);
        echo json_encode(["pesan" => "User service tidak dapat dihubungi"]);
        exit;
    }
    if ($hasil['status'] === 404) {
        http_response_code(404);
        echo json_encode(["pesan" => "Pengguna tidak ditemukan"]);
        exit;
    }
    if ($hasil['status'] !== 200 || !is_array($hasil['data'])) {
        http_response_code(502);
        echo json_encode(["pesan" => "Jawaban User Service tidak valid"]);
        exit;
    }

    $keranjang = $hasil['data']['keranjang'] ?? [];

    // Penjaga: troli kosong nggak boleh dibuatkan struk
    if (count($keranjang) === 0) {
        http_response_code(400);
        echo json_encode(["pesan" => "Keranjang masih kosong"]);
        exit;
    }

    // Langkah 2: hitung total belanja
    $total = 0;
    foreach ($keranjang as $item) {
        $total += (int)$item['harga'];
    }

    // Langkah 3: susun struk dan simpan ke arsip
    $pesananBaru = [
        "id_pesanan" => "ORD-" . time(),
        "user_id"    => $userId,
        "tanggal"    => date("Y-m-d H:i:s"),
        "item"       => $keranjang,
        "total"      => $total
    ];
    array_unshift($orders, $pesananBaru);   // taruh paling depan: terbaru di atas
    simpanOrders($fileOrders, $orders);

    // Langkah 4: struk sudah aman, baru minta User Service kosongkan trolinya
    // Urutannya sengaja begini: lebih baik pesanan tercatat dulu daripada keranjang
    // kosong tapi pesanan hilang.
    $kosong = panggilService(USER_SERVICE_URL . '?id=' . $userId . '&action=kosongkan', 'POST');
    if ($kosong['status'] !== 200) {
        http_response_code(502);
        echo json_encode([
            "pesan"   => "Pesanan tersimpan, tetapi keranjang gagal dikosongkan",
            "pesanan" => $pesananBaru
        ]);
        exit;
    }

    http_response_code(201);   // 201 = berhasil membuat data baru
    echo json_encode($pesananBaru);
    exit;
}

http_response_code(405);
echo json_encode(["pesan" => "Method tidak didukung"]);