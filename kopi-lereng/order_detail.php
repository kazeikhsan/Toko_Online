<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

$code = $_GET['code'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? AND user_id = ?");
$stmt->execute([$code, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    die("Pesanan tidak ditemukan.");
}

$steps = [
    'paid'       => ['title' => 'Pembayaran Diterima', 'desc' => 'Pesananmu sudah dibayar dan sedang diverifikasi.'],
    'processing' => ['title' => 'Pesanan Diproses', 'desc' => 'Biji kopi disiapkan, ditimbang, dan dikemas.'],
    'shipped'    => ['title' => 'Dalam Pengiriman', 'desc' => 'Paket sedang dalam perjalanan ke alamatmu.'],
    'arrived'    => ['title' => 'Tiba di Tujuan', 'desc' => 'Kurir sudah sampai. Cek dan terima paketmu.'],
    'received'   => ['title' => 'Pesanan Diterima', 'desc' => 'Kamu sudah konfirmasi pesanan diterima. Selamat menikmati!'],
];
$order_keys = array_keys($steps);
$currentIndex = array_search($order['status'], $order_keys);

// Aksi simulasi: lanjut ke status berikutnya (menggantikan peran kurir/gudang di dunia nyata)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'advance' && $currentIndex < array_search('arrived', $order_keys)) {
        $newStatus = $order_keys[$currentIndex + 1];
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $order['id']]);
        $pdo->prepare("INSERT INTO order_status_log (order_id, status) VALUES (?,?)")->execute([$order['id'], $newStatus]);
        header('Location: order_detail.php?code=' . urlencode($code));
        exit;
    }
    if ($action === 'receive' && $order['status'] === 'arrived') {
        $pdo->prepare("UPDATE orders SET status = 'received' WHERE id = ?")->execute([$order['id']]);
        $pdo->prepare("INSERT INTO order_status_log (order_id, status) VALUES (?, 'received')")->execute([$order['id']]);
        header('Location: order_detail.php?code=' . urlencode($code));
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM order_status_log WHERE order_id = ? ORDER BY changed_at");
$stmt->execute([$order['id']]);
$logs = [];
foreach ($stmt->fetchAll() as $l) { $logs[$l['status']] = $l['changed_at']; }

$currentIndex = array_search($order['status'], $order_keys);

$pageTitle = 'Lacak Pesanan';
include __DIR__ . '/includes/header.php';
?>
<div class="section">
  <a href="orders.php" style="font-size:0.85rem;color:var(--roast);">← Riwayat Pesanan</a>
  <div class="section-head" style="margin-top:16px;"><h2>Detail Pesanan</h2></div>

  <div class="checkout-grid">
    <div class="order-card">
      <div class="order-id">No. Pesanan <?= htmlspecialchars($order['order_code']) ?></div>
      <h3 class="display" style="margin:10px 0 24px;">Status Pesanan</h3>

      <div>
        <?php foreach ($order_keys as $i => $key):
          $cls = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : '');
        ?>
          <div class="brew-step <?= $cls ?>">
            <div class="dot-wrap"><div class="dot"></div><?php if ($i < count($order_keys)-1): ?><div class="stem"></div><?php endif; ?></div>
            <div class="txt">
              <h5><?= $steps[$key]['title'] ?></h5>
              <p><?= $steps[$key]['desc'] ?></p>
              <?php if (!empty($logs[$key])): ?><div class="time"><?= date('d M, H:i', strtotime($logs[$key])) ?></div><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($order['status'] === 'arrived'): ?>
        <form method="post"><button class="btn-main" name="action" value="receive" style="margin-top:18px;">Konfirmasi Barang Diterima</button></form>
      <?php elseif ($order['status'] === 'received'): ?>
        <button class="btn-main" disabled style="margin-top:18px;">✓ Pesanan Selesai</button>
      <?php else: ?>
        <form method="post"><button class="add-btn" name="action" value="advance" style="margin-top:18px;width:100%;padding:12px;">⏩ (Simulasi) Lanjutkan ke status berikutnya</button></form>
        <p style="font-size:0.72rem;color:var(--roast);opacity:0.55;margin-top:8px;">*Di dunia nyata status ini diperbarui otomatis oleh gudang/kurir. Tombol ini hanya untuk simulasi tugas.</p>
      <?php endif; ?>
    </div>

    <div class="summary-box">
      <h4 class="display" style="margin-bottom:14px;">Rincian Pesanan</h4>
      <?php foreach ($items as $it): ?>
        <div class="row"><span><?= htmlspecialchars($it['product_name']) ?> ×<?= $it['qty'] ?></span><span><?= rupiah($it['price'] * $it['qty']) ?></span></div>
      <?php endforeach; ?>
      <div class="row" style="margin-top:10px;"><span>Subtotal</span><span><?= rupiah($order['subtotal']) ?></span></div>
      <div class="row"><span>Ongkos Kirim</span><span><?= rupiah($order['shipping_fee']) ?></span></div>
      <div class="row total"><span>Total</span><span><?= rupiah($order['total']) ?></span></div>
      <hr style="margin:14px 0;border-color:var(--line);">
      <div style="font-size:0.85rem;line-height:1.6;">
        <strong><?= htmlspecialchars($order['recipient_name']) ?></strong><br>
        <?= htmlspecialchars($order['phone']) ?><br>
        <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?><br>
        <span style="opacity:0.7;">Bayar: <?= strtoupper($order['payment_method']) ?></span>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
