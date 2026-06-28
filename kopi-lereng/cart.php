<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($pid) {
        // Ambil stok barang
        $stmtStock = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmtStock->execute([$pid]);
        $product = $stmtStock->fetch();
        $availableStock = $product['stock'] ?? 0;
        
        $currentQty = $_SESSION['cart'][$pid] ?? 0;
        
        if ($action === 'add' && $pid) {
            if ($currentQty < $availableStock) {
                $_SESSION['cart'][$pid] = $currentQty + 1;
                $_SESSION['flash'] = 'Ditambahkan ke keranjang ☕';
            } else {
                $_SESSION['flash'] = 'Stok tidak cukup!';
            }
        } elseif ($action === 'inc' && $pid) {
            if ($currentQty < $availableStock) {
                $_SESSION['cart'][$pid] = $currentQty + 1;
            } else {
                $_SESSION['flash'] = 'Stok tidak cukup!';
            }
        } elseif ($action === 'dec' && $pid) {
            $_SESSION['cart'][$pid] = max(0, $currentQty - 1);
            if ($_SESSION['cart'][$pid] === 0) unset($_SESSION['cart'][$pid]);
        } elseif ($action === 'update' && $pid) {
            $newQty = (int)($_POST['qty'] ?? 0);
            if ($newQty <= 0) {
                unset($_SESSION['cart'][$pid]);
                $_SESSION['flash'] = 'Produk dihapus dari keranjang.';
            } elseif ($newQty > $availableStock) {
                $_SESSION['flash'] = 'Stok hanya tersedia ' . $availableStock . ' unit.';
                $_SESSION['cart'][$pid] = $availableStock;
            } else {
                $_SESSION['cart'][$pid] = $newQty;
                $_SESSION['flash'] = 'Qty berhasil diperbarui.';
            }
        } elseif ($action === 'remove' && $pid) {
            unset($_SESSION['cart'][$pid]);
        }
    }
    header('Location: cart.php');
    exit;
}

$items = [];
$subtotal = 0;
$stockWarnings = [];
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($in)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $p) {
        $qty = $_SESSION['cart'][$p['id']];
        
        // Cek stok
        if ($p['stock'] <= 0) {
            $stockWarnings[] = $p['name'] . ' habis terjual, dihapus dari keranjang.';
            unset($_SESSION['cart'][$p['id']]);
            continue;
        } elseif ($qty > $p['stock']) {
            $qty = $p['stock'];
            $_SESSION['cart'][$p['id']] = $qty;
            $stockWarnings[] = $p['name'] . ' stok terbatas, dikurangi menjadi ' . $qty . '.';
        }
        
        $items[] = ['product' => $p, 'qty' => $qty, 'line_total' => $p['price'] * $qty];
        $subtotal += $p['price'] * $qty;
    }
}

$pageTitle = 'Keranjang';
include __DIR__ . '/includes/header.php';
?>
<div class="section">
  <div class="section-head"><h2>Keranjang Belanja</h2></div>
  
  <?php if (!empty($stockWarnings)): ?>
    <div style="background:#ffe6e6;border-left:4px solid #ff4444;padding:12px;margin-bottom:16px;border-radius:4px;">
      <div style="color:#cc0000;font-weight:600;margin-bottom:4px;">⚠️ Perhatian Stok:</div>
      <div style="color:#666;font-size:0.9rem;"><?= implode('<br>', array_map('htmlspecialchars', $stockWarnings)) ?></div>
    </div>
  <?php endif; ?>

  <?php if (empty($items)): ?>
    <div class="empty-note">Keranjang masih kosong.<br><a href="index.php" style="color:var(--clay);font-weight:600;">Yuk pilih kopi favoritmu →</a></div>
  <?php else: ?>
    <table class="cart-table">
      <thead><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): $p = $it['product']; ?>
        <tr>
          <td>
            <div><?= htmlspecialchars($p['name']) ?></div>
            <div style="font-size:0.8rem;color:#999;margin-top:4px;">Stok tersedia: <strong><?= $p['stock'] ?></strong></div>
          </td>
          <td><?= rupiah($p['price']) ?></td>
          <td>
            <form method="post" class="qty-form" style="display:flex;align-items:center;gap:8px;">
              <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="action" value="update">
              <button type="button" class="qty-btn" onclick="decrementQty(this, <?= $p['stock'] ?>)">–</button>
              <input type="number" name="qty" value="<?= $it['qty'] ?>" min="1" max="<?= $p['stock'] ?>" class="qty-input" onchange="submitQtyForm(this.form)" onkeypress="if(event.key==='Enter') submitQtyForm(this.form)">
              <button type="button" class="qty-btn" onclick="incrementQty(this, <?= $p['stock'] ?>)">+</button>
            </form>
          </td>
          <td><?= rupiah($it['line_total']) ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
              <button name="action" value="remove" class="add-btn" style="padding:4px 10px;">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div style="max-width:340px;margin-left:auto;margin-top:24px;">
      <div class="summary-box">
        <div class="row"><span>Subtotal</span><span><?= rupiah($subtotal) ?></span></div>
        <div class="row"><span>Ongkos Kirim</span><span><?= rupiah(20000) ?></span></div>
        <div class="row total"><span>Total</span><span><?= rupiah($subtotal + 20000) ?></span></div>
        <?php if (isLoggedIn()): ?>
          <a href="checkout.php"><button class="btn-main" style="margin-top:12px;">Lanjut ke Checkout</button></a>
        <?php else: ?>
          <a href="login.php"><button class="btn-main" style="margin-top:12px;">Login untuk Checkout</button></a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
function incrementQty(btn, maxStock) {
  const form = btn.closest('form');
  const input = form.querySelector('input[name="qty"]');
  let currentQty = parseInt(input.value) || 1;
  if (currentQty < maxStock) {
    input.value = currentQty + 1;
    submitQtyForm(form);
  }
}

function decrementQty(btn, maxStock) {
  const form = btn.closest('form');
  const input = form.querySelector('input[name="qty"]');
  let currentQty = parseInt(input.value) || 1;
  if (currentQty > 1) {
    input.value = currentQty - 1;
    submitQtyForm(form);
  }
}

function submitQtyForm(form) {
  const input = form.querySelector('input[name="qty"]');
  let value = parseInt(input.value) || 0;
  
  if (value < 1) {
    input.value = 1;
    return;
  }
  
  const maxStock = parseInt(input.getAttribute('max')) || 100;
  if (value > maxStock) {
    alert('Stok hanya tersedia ' + maxStock + ' unit.');
    input.value = maxStock;
    return;
  }
  
  form.submit();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
