# POS Penjualan - Sistem Point of Sales

Sistem aplikasi web untuk mengelola penjualan barang dan transaksi pada koperasi pegawai. Aplikasi ini dibangun dengan HTML, CSS, JavaScript, dan PHP dengan database MySQL.

## Fitur Utama

- **Sistem Login yang Aman**: Autentikasi pengguna dengan enkripsi password dan session management
- **Dashboard Interaktif**: Tampilan dashboard dengan statistik dan informasi penting
- **Manajemen Penjualan**: Sistem untuk mengelola transaksi penjualan
- **Manajemen Barang**: Katalog barang dengan stok dan harga
- **Manajemen Customer**: Data customer dan pelanggan
- **Sistem Laporan**: Laporan pengadaan dan analisis
- **Responsive Design**: Tampilan yang optimal di desktop dan mobile
- **Keamanan Tinggi**: Proteksi terhadap serangan umum dan logging aktivitas

## Teknologi yang Digunakan

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Styling**: Custom CSS dengan Font Awesome icons
- **Security**: Password hashing, SQL injection protection, XSS protection

## Persyaratan Sistem

- **Web Server**: Apache/Nginx
- **PHP**: 7.4 atau lebih tinggi
- **MySQL**: 5.7 atau lebih tinggi
- **Browser**: Chrome, Firefox, Safari, Edge (versi terbaru)

## Instalasi

### 1. Persiapan Environment

Pastikan XAMPP atau web server sudah terinstall dan berjalan:
- Apache
- MySQL
- PHP

### 2. Clone atau Download Project

```bash
# Jika menggunakan Git
git clone <repository-url>
cd pointofsales

# Atau download dan extract ke folder htdocs
```

### 3. Setup Database

1. Buka phpMyAdmin atau MySQL client
2. Import file `pos_penjualan_database.sql`:
   ```sql
   SOURCE pos_penjualan_database.sql;
   ```
3. Atau copy isi file `pos_penjualan_database.sql` dan jalankan di phpMyAdmin

### 4. Konfigurasi Database

Edit file `config.php` jika diperlukan:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pos_penjualan');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 5. Setup Permissions

Pastikan folder memiliki permission yang tepat:
```bash
# Linux/Mac
chmod 755 assets/
chmod 755 logs/
chmod 755 uploads/

# Windows (biasanya tidak perlu)
```

### 6. Akses Aplikasi

Buka browser dan akses:
```
http://localhost/pointofsales
```

## Login Default

Setelah setup database, gunakan kredensial berikut untuk login pertama:

**Petugas:**
- **Username**: admin_petugas
- **Password**: admin123

**Manager:**
- **Username**: admin_manager
- **Password**: admin123

**⚠️ PENTING**: Ubah password default setelah login pertama kali!

## Struktur Folder

```
pointofsales/
├── index.html              # Halaman login
├── process_login.php       # Proses autentikasi
├── dashboard.php           # Dashboard utama
├── logout.php              # Script logout
├── config.php              # Konfigurasi aplikasi
├── pos_penjualan_database.sql # Setup database POS
├── README.md               # Dokumentasi
├── assets/
│   ├── css/
│   │   ├── style.css       # Styling login
│   │   └── dashboard.css   # Styling dashboard
│   ├── js/
│   │   ├── script.js       # JavaScript login
│   │   └── dashboard.js    # JavaScript dashboard
│   └── images/             # Folder gambar
├── logs/                   # Log aktivitas
└── uploads/                # File upload
```

## Penggunaan

### 1. Login

1. Buka aplikasi di browser
2. Masukkan username dan password
3. Klik tombol "Masuk"
4. Sistem akan mengarahkan ke dashboard

### 2. Dashboard

Dashboard menampilkan:
- Statistik penjualan
- Status stok barang
- Penjualan terbaru
- Aksi cepat untuk navigasi

### 3. Menu Navigasi

- **Dashboard**: Halaman utama dengan statistik
- **Penjualan**: Kelola transaksi penjualan
- **Barang**: Manajemen katalog barang
- **Customer**: Data customer dan pelanggan
- **Laporan**: Laporan dan analisis
- **Pengguna**: Manajemen user (Admin only)

## Keamanan

### Fitur Keamanan yang Diterapkan:

1. **Password Hashing**: Menggunakan `password_hash()` PHP
2. **SQL Injection Protection**: Prepared statements
3. **XSS Protection**: Input sanitization
4. **Session Security**: Secure session management
5. **CSRF Protection**: Token validation
6. **Rate Limiting**: Login attempt limiting
7. **Activity Logging**: Log semua aktivitas penting

### Rekomendasi Keamanan:

1. Ubah password default segera
2. Gunakan HTTPS di production
3. Update PHP dan MySQL secara berkala
4. Backup database secara rutin
5. Monitor log aktivitas

## Troubleshooting

### Database Connection Error

```
Error: Database connection failed
```

**Solusi:**
1. Pastikan MySQL service berjalan
2. Periksa konfigurasi database di `config.php`
3. Pastikan database `pos_penjualan` sudah dibuat

### Session Error

```
Warning: session_start() failed
```

**Solusi:**
1. Periksa permission folder
2. Pastikan PHP session extension aktif
3. Periksa konfigurasi PHP

### Login Gagal

```
Username atau password salah
```

**Solusi:**
1. Gunakan kredensial default: admin_petugas/admin123 atau admin_manager/admin123
2. Periksa apakah user sudah dibuat di database
3. Periksa log aktivitas untuk detail error

### CSS/JS Tidak Load

**Solusi:**
1. Periksa path file CSS/JS
2. Pastikan folder `assets` ada
3. Periksa permission file

## Pengembangan

### Menambah Fitur Baru

1. Buat file PHP baru di root directory
2. Tambahkan styling di `assets/css/`
3. Tambahkan JavaScript di `assets/js/`
4. Update menu navigasi di dashboard
5. Update database schema jika diperlukan

### Customization

1. **Logo**: Ganti icon di file HTML dan CSS
2. **Warna**: Edit CSS variables di file style
3. **Layout**: Modifikasi struktur HTML
4. **Database**: Sesuaikan dengan kebutuhan

## Support

Untuk bantuan teknis atau pertanyaan:

1. Periksa dokumentasi ini
2. Periksa log error di folder `logs/`
3. Periksa konfigurasi PHP dan MySQL
4. Pastikan semua persyaratan sistem terpenuhi

## Lisensi

Aplikasi ini dikembangkan untuk keperluan internal Koperasi Pegawai.

## Versi

**Versi**: 1.0.0  
**Tanggal**: September 2024  
**Developer**: [Nama Developer]

---

**Catatan**: Pastikan untuk melakukan backup database sebelum melakukan update atau perubahan konfigurasi.
