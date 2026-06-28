<?php
require_once __DIR__ . '/includes/config.php';
$products = $pdo->query("SELECT * FROM products ORDER BY id")->fetchAll();
$pageTitle = 'Produk';
include __DIR__ . '/includes/header.php';
?>
<div class="section">
  <div class="section-head">
    <h2>Kopi Lereng</h2>
    <span class="note"><?= count($products) ?> produk tersedia</span>
  </div>
  <div class="grid">
    <?php foreach ($products as $p): ?>
      <div class="card">
        <div class="card-art">
          <img src="<?= htmlspecialchars(productImagePath($p)) ?>" alt="<?= htmlspecialchars($p['name']) ?>" data-title="<?= htmlspecialchars($p['name']) ?>" onclick="openImageModal(this.src, this.getAttribute('data-title'))">
        </div>
        <div class="card-body">
          <span class="card-tag"><?= htmlspecialchars($p['tag']) ?></span>
          <h3 class="display"><?= htmlspecialchars($p['name']) ?></h3>
          <p><?= htmlspecialchars($p['description']) ?></p>
          <div class="card-foot">
            <span class="price"><?= rupiah($p['price']) ?></span>
            <div style="font-size:0.8rem;color:#999;margin-top:4px;">Stok: <strong><?= $p['stock'] ?></strong></div>
            <?php if ($p['stock'] <= 0): ?>
              <button class="add-btn" type="button" disabled style="opacity:0.5;cursor:not-allowed;">Habis Terjual</button>
            <?php elseif (isLoggedIn()): ?>
              <form method="post" action="cart.php">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="action" value="add">
                <button class="add-btn" type="submit">+ Keranjang</button>
              </form>
            <?php else: ?>
              <a href="login.php" class="add-btn" style="display:inline-block;">Login </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<div class="image-modal" id="imageModal" onclick="closeImageModal(event)">
  <div class="modal-box">
    <button class="close-btn" type="button" onclick="closeImageModal(event)" aria-label="Tutup gambar">×</button>
    <img id="modalImage" src="" alt="Gambar produk">
    <div class="caption" id="modalCaption">Produk</div>
  </div>
</div>
<script>
function openImageModal(src, title) {
  const modal = document.getElementById('imageModal');
  const img = document.getElementById('modalImage');
  const caption = document.getElementById('modalCaption');
  if (!modal || !img || !caption) return;
  img.src = src;
  caption.textContent = title || 'Produk';
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeImageModal(event) {
  const modal = document.getElementById('imageModal');
  if (!modal) return;
  if (event && event.target !== modal && !modal.querySelector('.modal-box').contains(event.target)) return;
  modal.classList.remove('active');
  document.body.style.overflow = '';
}

document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape') {
    closeImageModal(null);
  }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
