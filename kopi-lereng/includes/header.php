<?php if (!isset($pdo)) { require_once __DIR__ . '/config.php'; } ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>Kopi Lereng</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,500&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
  <div class="nav">
    <a href="index.php" class="brand"><div class="mark"></div>Kopi Lereng</a>
    <div class="navlinks">
      <a href="index.php">Produk</a>
      <?php if (isLoggedIn()): ?>
        <a href="orders.php">Riwayat Pesanan</a>
      <?php endif; ?>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <?php if (isLoggedIn()): ?>
        <span class="user-pill">Hai, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a class="cart-btn" href="cart.php">🛒 <span class="badge"><?= array_sum($_SESSION['cart'] ?? []) ?></span></a>
        <a class="add-btn" href="logout.php">Keluar</a>
      <?php else: ?>
        <a class="add-btn" href="login.php">Login</a>
        <a class="cart-btn" href="register.php">Daftar</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
