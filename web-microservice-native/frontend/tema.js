// ==========================================
// tema.js = tukang ganti tema
//   1) tema siap pakai (terang, gelap, hutan, senja)
//   2) tema kustom: pilih 1 warna latar + 1 warna aksen dari 25 warna
//   3) animasi riak saat tema berpindah
// Tidak menyentuh logika toko (itu urusan script.js).
// ==========================================

// ---------- 1. DATA ----------

// Nama tema. Harus sama dengan data-tema di index.html dan blok
// :root[data-tema="..."] di style.css ("kustom" tidak punya blok, warnanya dihitung di sini).
const DAFTAR_TEMA = ['terang', 'gelap', 'hutan', 'senja', 'kustom'];

// 25 warna yang disediakan. Ibarat kotak pensil warna: kamu ambil satu
// buat latar, satu buat aksen. Mau tambah warna? Tambah per 5 supaya grid-nya rapi.
const PALET = [
    // --- 7 warna terang ---
    { nama: 'Putih',      hex: '#ffffff' },
    { nama: 'Abu Muda',   hex: '#f4f4f5' },
    { nama: 'Krem',       hex: '#faf5e9' },
    { nama: 'Mawar Muda', hex: '#fdf0f3' },
    { nama: 'Mint',       hex: '#e9f6ef' },
    { nama: 'Langit',     hex: '#e8f1fb' },
    { nama: 'Lavender',   hex: '#f0ecfa' },
    // --- 8 warna gelap ---
    { nama: 'Hitam',      hex: '#0b0c0f' },
    { nama: 'Arang',      hex: '#1f2229' },
    { nama: 'Malam',      hex: '#0f172a' },
    { nama: 'Laut Dalam', hex: '#0b2530' },
    { nama: 'Rimba',      hex: '#0f1f17' },
    { nama: 'Anggur',     hex: '#1c1228' },
    { nama: 'Cokelat',    hex: '#231a15' },
    { nama: 'Marun',      hex: '#2a1216' },
    // --- 10 warna cerah (enak jadi aksen) ---
    { nama: 'Biru',       hex: '#2563eb' },
    { nama: 'Teal',       hex: '#0d9488' },
    { nama: 'Hijau',      hex: '#16a34a' },
    { nama: 'Lime',       hex: '#84cc16' },
    { nama: 'Kuning',     hex: '#eab308' },
    { nama: 'Oranye',     hex: '#f97316' },
    { nama: 'Merah',      hex: '#dc2626' },
    { nama: 'Pink',       hex: '#ec4899' },
    { nama: 'Ungu',       hex: '#7c3aed' },
    { nama: 'Indigo',     hex: '#4f46e5' }
];

// Keadaan saat ini (diisi dari "laci kecil" browser, lihat muatTersimpan)
let temaAktif  = 'terang';
let latarAktif = '#0f172a';   // warna latar bawaan untuk tema kustom (Malam)
let aksenAktif = '#f97316';   // warna aksen bawaan untuk tema kustom (Oranye)

// Elemen panel (diisi setelah halaman siap)
let panelKustom  = null;
let tombolKustom = null;

// ---------- 2. ALAT HITUNG WARNA ----------
// Dipakai supaya dari 2 warna pilihanmu, warna lain bisa dihitung otomatis.

// "#2563eb" -> [37, 99, 235]
function hexKeRgb(hex) {
    const h = hex.replace('#', '');
    return [0, 2, 4].map(i => parseInt(h.slice(i, i + 2), 16));
}

// [37, 99, 235] -> "#2563eb"
function rgbKeHex(rgb) {
    return '#' + rgb.map(v =>
        Math.round(Math.max(0, Math.min(255, v))).toString(16).padStart(2, '0')
    ).join('');
}

// Campur dua warna. t = 0 -> warna A saja, t = 1 -> warna B saja, 0.5 -> setengah-setengah.
function campur(hexA, hexB, t) {
    const a = hexKeRgb(hexA);
    const b = hexKeRgb(hexB);
    return rgbKeHex(a.map((v, i) => v + (b[i] - v) * t));
}

// Seberapa "terang" sebuah warna menurut mata manusia (0 = hitam, 1 = putih)
function luminansi(hex) {
    const [r, g, b] = hexKeRgb(hex).map(v => {
        v /= 255;
        return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

// Rasio kontras dua warna. Semakin besar, semakin mudah dibaca. Di bawah 3 = susah dibaca.
function kontras(hexA, hexB) {
    const la = luminansi(hexA);
    const lb = luminansi(hexB);
    return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
}

// ---------- 3. HITUNG TEMA KUSTOM ----------
// Masuk: warna latar + warna aksen pilihanmu.
// Keluar: daftar "kotak cat" (variabel CSS) yang sama seperti blok tema di style.css.
function hitungTema(latar, aksen) {
    // Latar dianggap gelap kalau tulisan terang lebih terbaca di atasnya daripada tulisan gelap
    const gelap = kontras(latar, '#fafafa') > kontras(latar, '#18181b');

    const teks  = gelap ? '#f4f4f5' : '#18181b';
    const kartu = gelap ? campur(latar, '#ffffff', 0.06) : campur(latar, '#ffffff', 0.7);

    // Kalau aksen terlalu mirip dengan kartu (misal sama-sama gelap), geser pelan-pelan
    // ke arah warna teks sampai cukup terbaca.
    let warnaAksen = aksen;
    for (let i = 0; i < 8 && kontras(warnaAksen, kartu) < 3; i++) {
        warnaAksen = campur(warnaAksen, teks, 0.2);
    }

    // Tulisan di atas tombol aksen: putih atau hitam, pilih yang lebih terbaca
    const diAtasAksen = kontras(warnaAksen, '#ffffff') >= kontras(warnaAksen, '#111111')
        ? '#ffffff' : '#111111';

    const bahaya = gelap ? '#f87171' : '#dc2626';

    return {
        'color-scheme':   gelap ? 'dark' : 'light',          // kontrol form & scrollbar ikut
        '--bg':           latar,
        '--card':         kartu,
        '--text':         teks,
        '--muted':        campur(teks, latar, 0.45),
        '--border':       campur(latar, teks, 0.14),
        '--input':        gelap ? campur(latar, '#000000', 0.2) : '#ffffff',
        '--accent':       warnaAksen,
        '--accent-hover': campur(warnaAksen, gelap ? '#ffffff' : '#000000', 0.18),
        '--accent-soft':  campur(kartu, warnaAksen, 0.14),
        '--on-accent':    diAtasAksen,
        '--danger':       bahaya,
        '--danger-soft':  campur(kartu, bahaya, 0.12)
    };
}

// Daftar nama variabel yang kita tulis ke <html>, supaya bisa dihapus lagi
const KUNCI_KUSTOM = Object.keys(hitungTema('#ffffff', '#2563eb'));

// ---------- 4. SIMPAN & BACA PILIHAN ----------
function simpan() {
    try {
        localStorage.setItem('tema', temaAktif);
        localStorage.setItem('tema-latar', latarAktif);
        localStorage.setItem('tema-aksen', aksenAktif);
    } catch (err) { /* laci terkunci (mode privat): tema tetap jalan, cuma tidak diingat */ }
}

function muatTersimpan() {
    let t = null;
    try {
        t = localStorage.getItem('tema');
        const la = localStorage.getItem('tema-latar') || '';
        const ak = localStorage.getItem('tema-aksen') || '';
        if (/^#[0-9a-f]{6}$/i.test(la)) latarAktif = la.toLowerCase();
        if (/^#[0-9a-f]{6}$/i.test(ak)) aksenAktif = ak.toLowerCase();
    } catch (err) { /* abaikan */ }

    if (DAFTAR_TEMA.includes(t)) {
        temaAktif = t;
    } else {
        // Belum pernah memilih -> ikuti mode gelap/terang perangkat
        const perangkatGelap = window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: dark)').matches;
        temaAktif = perangkatGelap ? 'gelap' : 'terang';
    }
}

// ---------- 5. PASANG TEMA KE HALAMAN (tanpa animasi) ----------
function bersihkanKustom() {
    KUNCI_KUSTOM.forEach(k => document.documentElement.style.removeProperty(k));
}

function pasangTema() {
    const root = document.documentElement;

    if (temaAktif === 'kustom') {
        root.setAttribute('data-tema', 'kustom');
        const nilai = hitungTema(latarAktif, aksenAktif);
        KUNCI_KUSTOM.forEach(k => root.style.setProperty(k, nilai[k]));
    } else {
        bersihkanKustom();                       // buang warna kustom, biar blok CSS yang berlaku
        root.setAttribute('data-tema', temaAktif);
    }
    perbaruiPenanda();
}

// Tandai bulatan dan kotak warna yang sedang aktif (CSS memberi cincin pada aria-pressed="true")
function perbaruiPenanda() {
    document.querySelectorAll('.swatch').forEach(t => {
        t.setAttribute('aria-pressed', String(t.dataset.tema === temaAktif));
    });
    document.querySelectorAll('#palet-latar .warna').forEach(t => {
        t.setAttribute('aria-pressed', String(t.dataset.warna === latarAktif));
    });
    document.querySelectorAll('#palet-aksen .warna').forEach(t => {
        t.setAttribute('aria-pressed', String(t.dataset.warna === aksenAktif));
    });
}

// ---------- 6. ANIMASI RIAK ----------
// ubah = fungsi yang mengganti tema. Fungsi ini membungkusnya dengan efek riak
// yang menyebar dari titik elemen yang diklik.
function denganAnimasi(elemen, ubah) {
    const kotak = elemen.getBoundingClientRect();
    const x = kotak.left + kotak.width / 2;     // titik pusat riak = tengah tombol
    const y = kotak.top + kotak.height / 2;
    const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Cadangan: browser belum mendukung riak -> pudar halus lewat CSS (kelas .ganti-halus)
    if (!document.startViewTransition || kurangiGerak) {
        const root = document.documentElement;
        if (!kurangiGerak) {
            root.classList.add('ganti-halus');
            setTimeout(() => root.classList.remove('ganti-halus'), 500);
        }
        ubah();
        return;
    }

    // Jari-jari riak: cukup besar sampai menutup sudut layar yang paling jauh
    const radius = Math.hypot(
        Math.max(x, window.innerWidth - x),
        Math.max(y, window.innerHeight - y)
    );

    // Browser memotret halaman lama, kita jalankan perubahan tema, lalu browser memotret
    // halaman baru. Foto baru kita "buka" dari lingkaran kecil jadi lingkaran besar.
    const transisi = document.startViewTransition(ubah);
    transisi.ready.then(() => {
        document.documentElement.animate(
            { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${radius}px at ${x}px ${y}px)`] },
            { duration: 650, easing: 'cubic-bezier(0.4, 0, 0.2, 1)', pseudoElement: '::view-transition-new(root)' }
        );
    }).catch(() => { /* transisi dibatalkan (klik cepat berturut-turut): abaikan */ });
}

// ---------- 7. PANEL KUSTOM ----------
function bukaPanel() {
    panelKustom.hidden = false;
    tombolKustom.setAttribute('aria-expanded', 'true');
}
function tutupPanel() {
    panelKustom.hidden = true;
    tombolKustom.setAttribute('aria-expanded', 'false');
}

// Dipanggil saat sebuah warna di panel diklik. jenis = 'latar' atau 'aksen'.
function pilihWarna(jenis, hex, elemen) {
    denganAnimasi(elemen, () => {
        if (jenis === 'latar') latarAktif = hex;
        else aksenAktif = hex;
        temaAktif = 'kustom';          // memilih warna otomatis mengaktifkan tema kustom
        simpan();
        pasangTema();
    });
}

// Bikin 25 tombol warna di satu palet
function isiPalet(idPalet, jenis, namaJenis) {
    const wadah = document.getElementById(idPalet);
    PALET.forEach(w => {
        const tombol = document.createElement('button');
        tombol.className = 'warna';
        tombol.style.background = w.hex;
        tombol.dataset.warna = w.hex;
        tombol.title = w.nama;
        tombol.setAttribute('aria-label', `${namaJenis} ${w.nama}`);
        tombol.addEventListener('click', () => pilihWarna(jenis, w.hex, tombol));
        wadah.appendChild(tombol);
    });
}

// ---------- 8. JALANKAN ----------

// Dipasang SEKARANG (file ini ada di <head>), sebelum halaman digambar -> tidak berkedip
muatTersimpan();
pasangTema();

// Setelah halaman selesai dimuat, tombol-tombolnya sudah ada -> sambungkan kliknya
document.addEventListener('DOMContentLoaded', () => {
    panelKustom  = document.getElementById('panel-kustom');
    tombolKustom = document.querySelector('.swatch-kustom');

    isiPalet('palet-latar', 'latar', 'Latar');
    isiPalet('palet-aksen', 'aksen', 'Aksen');
    perbaruiPenanda();

    // Bulatan tema siap pakai (terang, gelap, hutan, senja)
    document.querySelectorAll('.swatch:not(.swatch-kustom)').forEach(tombol => {
        tombol.addEventListener('click', () => {
            denganAnimasi(tombol, () => {
                temaAktif = tombol.dataset.tema;
                simpan();
                pasangTema();
                tutupPanel();
            });
        });
    });

    // Bulatan pelangi: kalau belum kustom -> aktifkan dan buka panel. Kalau sudah -> buka/tutup panel saja.
    tombolKustom.addEventListener('click', () => {
        if (temaAktif === 'kustom') {
            panelKustom.hidden ? bukaPanel() : tutupPanel();
            return;
        }
        denganAnimasi(tombolKustom, () => {
            temaAktif = 'kustom';
            simpan();
            pasangTema();
            bukaPanel();
        });
    });

    // Klik di luar panel atau tekan Esc -> panel menutup
    document.addEventListener('click', e => {
        if (!panelKustom.hidden && !e.target.closest('.tema-wrap')) tutupPanel();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') tutupPanel();
    });
});