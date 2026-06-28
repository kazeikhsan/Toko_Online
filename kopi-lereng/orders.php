<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$statusLabel = [
    'paid' => 'Pembayaran Diterima',
    'processing' => 'Diproses',
    'shipped' => 'Dikirim',
    'arrived' => 'Tiba di Tujuan',
    'received' => 'Diterima',
];

$pageTitle = 'Riwayat Pesanan';
include __DIR__ . '/includes/header.php';
?>
<div class="section">
  <div class="section-head"><h2>Riwayat Pesanan</h2><span class="note"><?= count($orders) ?> pesanan</span></div>

  <?php if (empty($orders)): ?>
    <div class="empty-note">Belum ada pesanan.<br><a href="index.php" style="color:var(--clay);font-weight:600;">Mulai belanja →</a></div>
  <?php else: ?>
    <?php foreach ($orders as $o): ?>
      <div class="order-card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
          <div>
            <div class="order-id">No. Pesanan <?= htmlspecialchars($o['order_code']) ?></div>
            <div style="font-size:0.8rem;color:var(--roast);opacity:0.7;margin-top:2px;"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></div>
          </div>
          <span class="status-pill <?= $o['status'] ?>"><?= $statusLabel[$o['status']] ?></span>
        </div>
        <div style="margin-top:14px;font-size:0.9rem;display:flex;justify-content:space-between;align-items:center;">
          <span>Total: <strong><?= rupiah($o['total']) ?></strong></span>
          <a href="order_detail.php?code=<?= urlencode($o['order_code']) ?>" class="add-btn">Lihat Detail</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
