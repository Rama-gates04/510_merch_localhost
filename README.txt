510 BAND MERCHANDISE MANAGER — LOCALHOST (XAMPP)

ISI FOLDER
- index.php    : halaman website + proses transaksi
- config.php   : koneksi PHP ke MySQL localhost
- database.sql : struktur database dan data contoh
- style.css    : tampilan website
- 510-spiritual-wallpaper.jpg : artwork banner dari pengguna

CARA MENJALANKAN (Windows + XAMPP)
1. Install dan buka XAMPP Control Panel.
2. Klik Start pada Apache dan MySQL.
3. Ekstrak folder 510_merchandise_localhost ke:
   C:\xampp\htdocs\510_merchandise_localhost
4. Buka http://localhost/phpmyadmin
5. Pilih tab Import, pilih file database.sql dari folder proyek, lalu klik Import/Go.
6. Buka website:
   http://localhost/510_merchandise_localhost/

CATATAN
- Ini versi PHP + MySQL untuk website localhost. Program C sebelumnya tidak langsung menjadi backend website; PHP menangani permintaan website dan MySQL menyimpan data.
- Harga/stok awal hanyalah data simulasi, bukan data resmi band.
- Jika MySQL menggunakan password, ubah $password di config.php.
- Jangan gunakan konfigurasi default untuk server publik/produksi.
