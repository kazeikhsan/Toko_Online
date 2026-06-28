# Kopi Lereng — E-Commerce (PHP + MySQL)

Tugas e-commerce dengan login user, riwayat pesanan, checkout, simulasi pembayaran, dan tracking pengiriman.

## Cara Menjalankan (XAMPP / Laragon)

1. **Install XAMPP** (atau Laragon) jika belum punya — pastikan Apache & MySQL aktif.
2. Salin folder `kopi-lereng` ke dalam folder `htdocs` (XAMPP) atau `www` (Laragon).
3. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) → buat tab **Import** → pilih file `database.sql` → klik **Go**.
   - Ini otomatis membuat database `kopi_lereng` beserta tabel & data produk contoh.
4. Cek file `includes/config.php` — sesuaikan jika username/password MySQL kamu berbeda dari default (`root` / tanpa password).
5. Buka browser ke `http://localhost/kopi-lereng/index.php`.

## Alur Penggunaan

1. **Daftar** akun baru di `register.php` (atau login bila sudah punya akun).
2. Tambahkan produk kopi ke **keranjang** dari halaman produk.
3. Buka **Keranjang** → **Checkout** → isi alamat & pilih metode pembayaran → **Bayar Sekarang**.
4. Setelah checkout, kamu diarahkan ke halaman **Lacak Pesanan**.
   - Karena ini simulasi (tanpa integrasi kurir/payment gateway asli), gunakan tombol **"(Simulasi) Lanjutkan ke status berikutnya"** untuk memajukan status pesanan: Dibayar → Diproses → Dikirim → Tiba di Tujuan.
   - Saat status "Tiba di Tujuan", klik **Konfirmasi Barang Diterima**.
5. Semua pesanan (lama & baru) bisa dilihat di menu **Riwayat Pesanan**.

## Struktur Database

- `users` — data akun (password disimpan ter-hash dengan `password_hash()`)
- `products` — daftar produk kopi
- `orders` — data pesanan (alamat, metode bayar, total, status)
- `order_items` — rincian produk per pesanan
- `order_status_log` — riwayat waktu perubahan status (untuk timeline tracking)

## Struktur File

```
kopi-lereng/
├── database.sql          # Import ini ke phpMyAdmin
├── includes/
│   ├── config.php         # Koneksi database + helper function
│   ├── header.php
│   └── footer.php
├── assets/css/style.css
├── index.php              # Daftar produk
├── register.php
├── login.php
├── logout.php
├── cart.php               # Keranjang (disimpan di session)
├── checkout.php           # Form alamat + pembayaran → simpan ke DB
├── orders.php             # Riwayat pesanan
└── order_detail.php       # Detail & lacak status satu pesanan
```

## Catatan

- Pembayaran (QRIS/Transfer/COD) di sini **simulasi**, hanya mencatat metode yang dipilih, tidak terhubung ke payment gateway asli — sesuai kebutuhan tugas sekolah/kuliah.
- Status pengiriman dimajukan manual lewat tombol simulasi karena tidak ada integrasi kurir nyata. Kalau dosen/guru meminta otomatis, gampang diganti jadi `cron job` yang memajukan status setiap beberapa menit.
- Akun demo di `database.sql` sebaiknya diabaikan — daftar akun baru lewat halaman Register agar password hash valid.
