<?php
/**
 * Konfigurasi koneksi database.
 * Sesuaikan DB_HOST, DB_USER, DB_PASS dengan setup MySQL lokalmu (XAMPP/Laragon biasanya: root, password kosong).
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'kopi_lereng');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

session_start();

function rupiah($n) {
    return "Rp" . number_format($n, 0, ',', '.');
}

function productImagePath(array $product): string {
    $image = $product['image'] ?? '';
    if (!empty($image)) {
        return 'assets/img/' . $image;
    }

    $name = strtolower(trim($product['name'] ?? ''));
    $map = [
        'lereng arabika gayo' => 'assets/img/lereng-arabika-gayo.jpg',
        'lereng robusta lampung' => 'assets/img/lereng-robusta-lampung.jpg',
        'lereng toraja sapan' => 'assets/img/lereng-toraja-sapan.jpg',
        'lereng kintamani' => 'assets/img/lereng-kintamani.jpg',
        'lereng java preanger' => 'assets/img/lereng-java-preanger.jpg',
        'lereng flores bajawa' => 'assets/img/lereng-flores-bajawa.jpg',
    ];

    return $map[$name] ?? 'assets/img/coffee-cup.svg';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
