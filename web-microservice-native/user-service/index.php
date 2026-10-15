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

// RUTE 1: Ambil data lewat GET. Dua "loket" di pintu yang sama,
// dibedakan oleh parameter ?action=
if ($method === 'GET') {

    // Loket 1: ?action=riwayat -> cuma kirim daftar struk
    // Frontend: "Kasir, minta map struk punya pelanggan nomor 1 aja."
    if (isset($_GET['action']) && $_GET['action'] === 'riwayat') {
        // '??' = pakai isi laci 'riwayat' kalau ada,
        // kalau belum ada (belum pernah checkout) kasih [] kosong, bukan error.
        $riwayat = $users[$userId]['riwayat'] ?? [];
        echo json_encode($riwayat);
        exit;
    }

    // Loket 2 (bawaan): tanpa action -> kirim profil + keranjang
    echo json_encode($users[$userId]);
    exit;
}

// RUTE 2: Tambah produk ke keranjang ATAU checkout (POST)
if ($method === 'POST') {

    // 2.1 Checkout massal: ?action=checkout
    // Salin isi keranjang jadi "struk" di riwayat, BARU kosongkan keranjang.
    if (isset($_GET['action']) && $_GET['action'] === 'checkout') {

        // Ambil isi keranjang saat ini (isi troli di meja kasir)
        $keranjang = $users[$userId]['keranjang'];

        // Penjaga: troli kosong nggak boleh dibuatkan struk
        if (count($keranjang) === 0) {
            http_response_code(400);
            echo json_encode(["pesan" => "Keranjang masih kosong"]);
            exit;
        }

        // Hitung total belanja
        $total = 0;
        foreach ($keranjang as $item) {
            $total += $item['harga'];
        }

        // Susun "struk" pesanan
        $pesananBaru = [
            "id_pesanan" => "ORD-" . time(),      // nomor struk unik berbasis waktu
            "tanggal"    => date("Y-m-d H:i:s"),  // kapan checkout terjadi
            "item"       => $keranjang,           // salinan isi keranjang
            "total"      => $total                // total yang dibayar
        ];

        // Kalau user ini belum punya laci "riwayat" (data lama), buatkan dulu
        if (!isset($users[$userId]['riwayat'])) {
            $users[$userId]['riwayat'] = [];
        }

        // array_unshift = struk baru ditaruh PALING DEPAN (pesanan terbaru di atas)
        array_unshift($users[$userId]['riwayat'], $pesananBaru);

        // Struk sudah aman, baru kosongkan keranjang
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