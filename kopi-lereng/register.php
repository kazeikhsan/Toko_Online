<?php
require_once __DIR__ . '/includes/config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$password) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar. Silakan Login.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?,?,?,?)");
            $stmt->execute([$name, $email, $hash, $phone]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['flash'] = "Akun berhasil dibuat. Selamat datang, $name!";
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Daftar';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-box">
  <h2 class="display">Buat Akun</h2>
  <p class="sub">Daftar untuk bisa checkout dan melihat riwayat pesananmu.</p>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <div class="field"><label>Nama Lengkap</label><input name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required></div>
    <div class="field"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required></div>
    <div class="field"><label>No. HP</label><input name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"></div>
    <div class="field"><label>Password</label><input type="password" name="password" required minlength="6"></div>
    <button class="btn-main" type="submit">Daftar</button>
  </form>
  <div class="swap-link">Sudah punya akun? <a href="login.php">Login di sini</a></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
