-- ============================================
-- Database: kopi_lereng
-- Tugas E-Commerce — Skema MySQL
-- ============================================

CREATE DATABASE IF NOT EXISTS kopi_lereng CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kopi_lereng;

-- ---------- USERS ----------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,      -- disimpan dengan password_hash()
    phone VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- PRODUCTS ----------
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    tag VARCHAR(50) DEFAULT NULL,        -- Light Roast / Medium Roast / Dark Roast
    description TEXT,
    price INT NOT NULL,                  -- dalam Rupiah
    stock INT NOT NULL DEFAULT 100,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- ORDERS ----------
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(20) NOT NULL UNIQUE,   -- contoh: KL-482193
    user_id INT NOT NULL,
    recipient_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    city VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    payment_method ENUM('qris','transfer','cod') NOT NULL,
    subtotal INT NOT NULL,
    shipping_fee INT NOT NULL DEFAULT 20000,
    total INT NOT NULL,
    status ENUM('paid','processing','shipped','arrived','received') NOT NULL DEFAULT 'paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- ORDER ITEMS ----------
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,   -- disalin saat checkout (snapshot harga/nama)
    price INT NOT NULL,
    qty INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- ---------- ORDER STATUS HISTORY (untuk tracking timeline) ----------
CREATE TABLE IF NOT EXISTS order_status_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status ENUM('paid','processing','shipped','arrived','received') NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- DATA AWAL PRODUK
-- ============================================
INSERT INTO products (name, tag, description, price, stock, image) VALUES
('Lereng Arabika Gayo', 'Light Roast', 'Asam jeruk lembut, aroma bunga, finish manis seperti karamel.', 85000, 100, 'lereng-arabika-gayo.jpg'),
('Lereng Robusta Lampung', 'Dark Roast', 'Pekat, cokelat pahit, badan tebal — cocok untuk yang suka kuat.', 62000, 100, 'lereng-robusta-lampung.jpg'),
('Lereng Toraja Sapan', 'Medium Roast', 'Earthy, rempah hangat, sedikit pedas di akhir tegukan.', 98000, 100, 'lereng-toraja-sapan.jpg'),
('Lereng Kintamani', 'Light Roast', 'Citrus segar berpadu dengan aroma jeruk Bali yang khas.', 90000, 100, 'lereng-kintamani.jpg'),
('Lereng Java Preanger', 'Medium Roast', 'Seimbang, kacang panggang, cocok untuk diseduh setiap hari.', 78000, 100, 'lereng-java-preanger.jpg'),
('Lereng Flores Bajawa', 'Dark Roast', 'Cokelat hitam, sedikit smoky, after taste yang panjang.', 88000, 100, 'lereng-flores-bajawa.jpg');

-- ============================================
-- USER CONTOH (password: "password123")
-- Hash di bawah dibuat dengan password_hash('password123', PASSWORD_DEFAULT)
-- ============================================
INSERT INTO users (name, email, password, phone) VALUES
('Pengguna Demo', 'demo@kopilereng.test', '$2y$10$92H1aXG0qP0g0Q1z3m1V4OQhq9z2C8Y2qkzG2K1y2j0G2c1k1l1lO', '081234567890');
-- Catatan: hash di atas hanya placeholder. Saat register.php pertama kali dipakai,
-- password akan di-hash otomatis dan benar. Untuk akun demo ini, lebih aman
-- daftar ulang lewat halaman Register agar hash sesuai server kamu.
