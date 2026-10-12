<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Menangani pra-koneksi browser (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// "Database" kita: file JSON di folder yang sama (menggantikan $_SESSION)
$fileUsers = __DIR__ . '/users.json';

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
if ($method === 'GET') {
    echo json_encode($users[$userId]);
    exit;
}

// RUTE 2: Tambah produk ke keranjang ATAU checkout (POST)
if ($method === 'POST') {
    // 2.1 Checkout massal: ?action=checkout -> kosongkan keranjang
    if (isset($_GET['action']) && $_GET['action'] === 'checkout') {
        $users[$userId]['keranjang'] = [];
        simpanUsers($fileUsers, $users);
        echo json_encode($users[$userId]);
        exit;
    }

    // 2.2 Tambah produk ke keranjang
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['nama']) && isset($data['harga'])) {
        $users[$userId]['keranjang'][] = [
            "id_produk" => uniqid(),            // ID unik supaya hapus 1 item nggak salah target
            "nama"      => htmlspecialchars($data['nama']),
            "harga"     => (int)$data['harga']
        ];
        simpanUsers($fileUsers, $users);
        echo json_encode($users[$userId]);
    } else {
        http_response_code(400);
        echo json_encode(["pesan" => "Data produk tidak lengkap"]);
    }
    exit;
}

// RUTE 3: Hapus 1 produk spesifik dari keranjang (DELETE)
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['id_produk'])) {
        $keranjangBaru = [];