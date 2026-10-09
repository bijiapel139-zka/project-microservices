<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Menangani pra-koneksi browser (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Simulasi database sederhana (menggunakan session untuk menyimpan sementara di memori server)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inisialisasi data akun jika belum ada di session
if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [
        1 => [
            "id" => 1, "nama" => "Budi Santoso", "email" => "budi@email.com", "peran" => "Pelanggan VIP",
            "keranjang" => [
                ["id_produk" => "1111" . time(), "nama" => "Laptop Asus", "harga" => 8500000]
            ]
        ],
        2 => [
            "id" => 2, "nama" => "Siti Aminah", "email" => "siti@email.com", "peran" => "Pelanggan Reguler",
            "keranjang" => []
        ],
        3 => [
            "id" => 3, "nama" => "Andi Wijaya", "email" => "andi@email.com", "peran" => "Pelanggan Reguler",
            "keranjang" => []
        ],
        4 => [
            "id" => 4, "nama" => "Dewi Sartika", "email" => "dewi@email.com", "peran" => "Pelanggan Reguler",
            "keranjang" => []
        ]
    ];
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$method = $_SERVER['REQUEST_METHOD'];

// RUTE 1: Mengambil Data Profil & Isi Keranjang Belanja (GET)
if ($method === 'GET') {
    if (array_key_exists($userId, $_SESSION['users'])) {
        echo json_encode($_SESSION['users'][$userId]);
    } else {
        http_response_code(404);
        echo json_encode(["pesan" => "Pengguna tidak ditemukan"]);
    }
    exit;
}

// RUTE 2: SATU BLOK POST UNTUK TAMBAH PRODUK & CHECKOUT
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // RUTE 2.1: Jika parameter URL mengandung ?action=checkout (Proses Checkout Massal)
    if (isset($_GET['action']) && $_GET['action'] === 'checkout') {
        $_SESSION['users'][$userId]['keranjang'] = []; // Kosongkan seluruh isi keranjang
        echo json_encode($_SESSION['users'][$userId]);
        exit;
    }
    
    // RUTE 2.2: Jika permintaannya adalah menambah produk ke keranjang
    if (isset($data['nama']) && isset($data['harga'])) {
        $produkBaru = [
            "id_produk" => rand(1000, 9999) . time(), // ID unik anti-bentrok saat hapus produk
            "nama" => $data['nama'],
            "harga" => (int)$data['harga']
        ];
        $_SESSION['users'][$userId]['keranjang'][] = $produkBaru;
        echo json_encode($_SESSION['users'][$userId]);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(["pesan" => "Data produk tidak lengkap"]);
        exit;
    }
}

// RUTE 3: Menghapus 1 Produk Spesifik dari Keranjang (DELETE)
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['id_produk'])) {
        $idHapus = $data['id_produk'];
        $keranjangLama = $_SESSION['users'][$userId]['keranjang'];
        $keranjangBaru = [];
        $sudahDihapus = false;

        foreach ($keranjangLama as $item) {
            // Jika ID cocok dan belum ada yang dihapus, lewati item ini (berarti menghapusnya)
            if ($item['id_produk'] == $idHapus && !$sudahDihapus) {
                $sudahDihapus = true; 
                continue; 
            }
            $keranjangBaru[] = $item;
        }
        
        $_SESSION['users'][$userId]['keranjang'] = $keranjangBaru;
        echo json_encode($_SESSION['users'][$userId]);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(["pesan" => "ID produk tidak valid"]);
        exit;
    }
}
?>
