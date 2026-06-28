<?php
require_once __DIR__ . '/includes/config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Email atau password salah.';
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-box">
  <h2 class="display">Login</h2>
  <p class="sub">Login untuk melanjutkan belanja dan melihat pesananmu.</p>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <div class="field"><label>Email</label><input type="email" name="email" required></div>
    <div class="field"><label>Password</label><input type="password" name="password" required></div>
    <button class="btn-main" type="submit">Login</button>
  </form>
  <div class="swap-link">Belum punya akun? <a href="register.php">Daftar di sini</a></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
