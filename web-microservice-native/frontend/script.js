// ==========================================
// script.js = semua urusan LOGIKA + ngobrol ke API (milik Dimas / leader)
// Ibarat kurir: nganter permintaan dari halaman ke User Service (5001)
// dan Product Service (5002), lalu nampilin jawabannya.
// ==========================================

let currentUserId = 1;
let listItemKeranjang = []; // Variabel global penampung keranjang

// 1. Memuat Informasi Pengguna & Keranjang
function muatDataPengguna(id) {
    currentUserId = id;
    fetch(`http://localhost:5001/?id=${id}`)
        .then(res => res.json())
        .then(user => {
            document.getElementById('user-info').innerHTML = `
                <p><strong>Nama:</strong> ${user.nama}</p>
                <p><strong>Email:</strong> ${user.email}</p>
                <p><strong>Status:</strong> <span style="background:#f1c40f; padding:2px 6px; border-radius:4px;">${user.peran}</span></p>
            `;
            listItemKeranjang = user.keranjang || [];
            perbaruiTampilanKeranjang(listItemKeranjang);
            muatRiwayat(id);   // ganti akun = riwayat ikut ganti
        })
        .catch(err => console.error("Gagal terhubung ke User Service"));
}

function perbaruiTampilanKeranjang(keranjang) {
    const container = document.getElementById('cart-list');
    const totalText = document.getElementById('total-harga');
    container.innerHTML = '';

    if (!keranjang || keranjang.length === 0) {
        container.innerHTML = '<div>Keranjang belanja kosong.</div>';
        totalText.innerText = 'Total: Rp 0';
        return;
    }

    let total = 0;
    keranjang.forEach(item => {
        total += item.harga;
        const div = document.createElement('div');
        div.className = 'product-item';
        div.innerHTML = `
            <div><strong>${item.nama}</strong><br><span class="price">Rp ${item.harga.toLocaleString('id-ID')}</span></div>
            <button class="btn btn-danger" style="width:auto; padding:5px 10px;" onclick="hapusDariKeranjang('${item.id_produk}')">Hapus</button>
        `;
        container.appendChild(div);
    });
    totalText.innerText = `Total: Rp ${total.toLocaleString('id-ID')}`;
}

function tambahKeKeranjang(nama, harga) {
    fetch(`http://localhost:5001/?id=${currentUserId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nama: nama, harga: harga })
    })
    .then(res => res.json())
    .then(userUpdated => {
        listItemKeranjang = userUpdated.keranjang || [];
        perbaruiTampilanKeranjang(listItemKeranjang);
    });
}

function hapusDariKeranjang(idProduk) {
    fetch(`http://localhost:5001/?id=${currentUserId}`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_produk: idProduk })
    })
    .then(res => res.json())
    .then(userUpdated => {
        listItemKeranjang = userUpdated.keranjang || [];
        perbaruiTampilanKeranjang(listItemKeranjang);
    });
}

function gantiAkun(idBaru) {
    muatDataPengguna(idBaru);
}

// ==========================================
// RIWAYAT PESANAN (User Service, Port 5001)
// ==========================================

function muatRiwayat(id) {
    // Frontend: "Kasir, minta map struk punya pelanggan nomor sekian."
    fetch(`http://localhost:5001/?id=${id}&action=riwayat`)
        .then(res => res.json())
        .then(riwayat => {
            const container = document.getElementById('riwayat-list');
            container.innerHTML = '';

            if (!riwayat || riwayat.length === 0) {
                container.innerHTML = '<div>Belum ada pesanan.</div>';
                return;
            }

            riwayat.forEach(pesanan => {
                const daftarItem = pesanan.item
                    .map(it => `<li>${it.nama} - Rp ${it.harga.toLocaleString('id-ID')}</li>`)
                    .join('');

                const div = document.createElement('div');
                div.className = 'struk';
                div.innerHTML = `
                    <div class="struk-header">
                        <span><strong>${pesanan.id_pesanan}</strong></span>
                        <span>${pesanan.tanggal}</span>
                    </div>
                    <ul>${daftarItem}</ul>
                    <div class="struk-total">Total: Rp ${pesanan.total.toLocaleString('id-ID')}</div>
                `;
                container.appendChild(div);
            });
        })
        .catch(err => console.error("Gagal memuat riwayat dari User Service"));
}

// Proses Checkout Massal Terhubung Server
function prosesCheckout() {
    if (listItemKeranjang.length === 0) {
        alert("Keranjang belanja Anda masih kosong! Silakan pilih produk terlebih dahulu.");
        return;
    }

    let daftarBelanjaan = listItemKeranjang.map((item, index) => `${index + 1}. ${item.nama}`).join("\n");
    let totalBayar = document.getElementById('total-harga').innerText;

    let konfirmasi = confirm(`Apakah Anda yakin ingin checkout semua produk ini?\n\n${daftarBelanjaan}\n\n${totalBayar}`);

    if (konfirmasi) {
        fetch(`http://localhost:5001/?id=${currentUserId}&action=checkout`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(userUpdated => {
            alert("🎉 Transaksi Berhasil!\nSemua produk di atas telah sukses diproses.");
            listItemKeranjang = [];
            perbaruiTampilanKeranjang(listItemKeranjang);
            muatRiwayat(currentUserId);   // struk baru langsung muncul
        })
        .catch(err => alert("Gagal memproses checkout ke server."));
    }
}

// ==========================================
// MANAJEMEN PRODUK GLOBAL (PORT 5002)
// ==========================================

function perbaruiDaftarProdukToko(products) {
    const container = document.getElementById('product-list');
    container.innerHTML = '';
    products.forEach(prod => {
        const item = document.createElement('div');
        item.className = 'product-item';
        item.innerHTML = `
            <div><strong>${prod.nama}</strong><br><span class="price">Rp ${parseInt(prod.harga).toLocaleString('id-ID')}</span></div>
            <button class="btn" style="width:auto;" onclick="tambahKeKeranjang('${prod.nama}', ${prod.harga})">Pilih</button>
        `;
        container.appendChild(item);
    });
}

function muatSemuaProduk() {
    fetch('http://localhost:5002/')
        .then(res => res.json())
        .then(products => perbaruiDaftarProdukToko(products))
        .catch(err => console.error("Gagal terhubung ke Product Service"));
}

// Jalankan saat halaman pertama kali dibuka
muatSemuaProduk();
muatDataPengguna(currentUserId);