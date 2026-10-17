<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Menangani pra-koneksi browser (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Nama file penyimpan data produk lokal
$fileJson = __DIR__ . '/produk.json';

// Jika file JSON belum ada, buat pertama kali dengan data default
if (!file_exists($fileJson)) {
    $dataAwal = [
        ["id" => 101, "nama" => "Laptop Asus", "harga" => 8500000],
        ["id" => 102, "nama" => "Mouse Logitech", "harga" => 250000],
        ["id" => 103, "nama" => "Keyboard Mechanical", "harga" => 450000],
        ["id" => 104, "nama" => "Monitor Samsung", "harga" => 1500000],
        ["id" => 105, "nama" => "Headset Razer", "harga" => 750000],
        ["id" => 106, "nama" => "Kursi Gaming", "harga" => 1000000]
    ];
    file_put_contents($fileJson, json_encode($dataAwal, JSON_PRETTY_PRINT));
}

// Membaca data produk terbaru dari file JSON
$products = json_decode(file_get_contents($fileJson), true);
$method = $_SERVER['REQUEST_METHOD'];

// RUTE 1: Ambil data produk lewat GET.
//  - Tanpa ?id      -> semua produk (seperti dulu, frontend nggak terganggu)
//  - Dengan ?id=101 -> hanya SATU produk
// Nanti User Service memakai ini buat "menanyakan" nama dan harga asli produk.
if ($method === 'GET') {

    if (isset($_GET['id'])) {
        // (int) mengubah teks jadi angka. Kalau bukan angka (misal "abc"), hasilnya 0.
        $idDicari = (int)$_GET['id'];

        // User Service: "Gudang, ada produk nomor 101?"
        // Product Service: cek rak satu per satu sampai ketemu.
        foreach ($products as $produk) {
            if ((int)$produk['id'] === $idDicari) {
                echo json_encode($produk);   // ketemu -> serahkan produk ini saja
                exit;
            }
        }

        // Sampai rak terakhir nggak ketemu -> jawab jelas, bukan halaman kosong.
        // 404 = kode standar "yang dicari nggak ada".
        http_response_code(404);
        echo json_encode(["pesan" => "Produk dengan ID $idDicari tidak ditemukan"]);
        exit;
    }

    // Tanpa ?id -> kirim semua produk
    echo json_encode($products);
    exit;
}

// RUTE 2: Menambahkan Produk Baru TEPAT SATU PER SATU
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!empty($data['nama']) && isset($data['harga'])) {
        // Menyusun data produk baru
        $produkBaru = [
            "id" => time() . rand(10, 99), // ID unik berbasis waktu dan angka acak
            "nama" => htmlspecialchars($data['nama']),
            "harga" => (int)$data['harga']
        ];
        
        // Memasukkan produk baru ke dalam array produk saat ini
        $products[] = $produkBaru;
        
        // MENYIMPAN KEMBALI KE FILE (Ini mengunci data agar tidak duplikat atau macet)
        file_put_contents($fileJson, json_encode($products, JSON_PRETTY_PRINT));
        
        // Mengembalikan data terupdate ke frontend
        echo json_encode($products);
    } else {
        http_response_code(400);
        echo json_encode(["pesan" => "Nama atau harga produk tidak boleh kosong!"]);
    }
    exit;
}
?>
