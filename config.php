<?php
// Pengaturan koneksi database MySQL untuk XAMPP.
$host = 'localhost';
$dbname = '510_merchandise';
$username = 'root';
$password = ''; // Default XAMPP biasanya kosong. Sesuaikan jika MySQL-mu memakai password.

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Pastikan MySQL aktif dan database 510_merchandise sudah diimpor.');
}
